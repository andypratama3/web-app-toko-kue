<?php

namespace Tests\Feature\WhatsApp;

use App\Enums\OrderBotConversationState;
use App\Models\Category;
use App\Models\CustomerCategory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Region;
use App\Models\User;
use App\Models\WhatsAppConversation;
use App\Services\WhatsApp\OrderBotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OrderBotVariantSelectionTest extends TestCase
{
    use RefreshDatabase;

    protected Region $region;
    protected OrderBotService $bot;
    protected WhatsAppConversation $conversation;
    protected Product $ijo;
    protected Product $tumpeng;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.whatsapp.default_region' => 'Denpasar']);

        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => ['id' => 'wamid_test']], 200),
            'lookaside.fbsbx.com/*' => Http::response('fake-image', 200, ['Content-Type' => 'image/jpeg']),
        ]);

        Role::create(['name' => 'admin']);
        Role::create(['name' => 'kurir']);

        $this->region = Region::create(['name' => 'Denpasar', 'slug' => 'denpasar']);
        CustomerCategory::create(['name' => 'Reseller']);

        $admin = User::create([
            'name' => 'Admin', 'email' => 'admin@test.com',
            'password' => bcrypt('password'), 'region_id' => $this->region->id,
        ]);
        $admin->assignRole('admin');

        $produk = Category::create(['name' => 'Produk', 'slug' => 'produk']);
        $tumpengCat = Category::create(['name' => 'Tumpeng', 'slug' => 'tumpeng']);

        $this->ijo = Product::create([
            'category_id' => $produk->id, 'region_id' => null,
            'name' => 'Kue Ijo', 'description' => 'ijo', 'tag' => 'Ala Carte', 'is_active' => true,
        ]);
        ProductVariant::create(['product_id' => $this->ijo->id, 'name' => 'Isi 3', 'price' => 9000, 'is_active' => true]);
        ProductVariant::create(['product_id' => $this->ijo->id, 'name' => 'Isi 5', 'price' => 15000, 'is_active' => true]);
        ProductVariant::create(['product_id' => $this->ijo->id, 'name' => 'Isi 12', 'price' => 40000, 'is_active' => true]);

        $this->tumpeng = Product::create([
            'category_id' => $tumpengCat->id, 'region_id' => null,
            'name' => 'Tumpeng Mini Mix', 'description' => 'tumpeng', 'tag' => 'Hampers', 'is_active' => true,
        ]);
        ProductVariant::create(['product_id' => $this->tumpeng->id, 'name' => 'Paket Mini', 'price' => 250000, 'is_active' => true]);

        $this->bot = app(OrderBotService::class);

        $this->conversation = WhatsAppConversation::create([
            'phone_number' => '6281234567890',
            'region_id' => $this->region->id,
            'status' => 'active',
            'current_state' => OrderBotConversationState::AWAITING_ORDER_FORM->value,
            'context' => ['form_step' => 0],
        ]);
    }

    public function test_produk_multi_varian_minta_pilih_varian(): void
    {
        $this->bot->handleMessage($this->conversation, 'Kue Ijo');

        $this->conversation->refresh();
        $options = $this->conversation->getContext('pending_options');
        $this->assertCount(3, $options);
        $this->assertEquals('variant', $options[0]['type']);
        // Belum lanjut ke formulir gabungan
        $this->assertNull($this->conversation->getContext('order_form')['product_name'] ?? null);
    }

    public function test_pilih_varian_isi_12_lanjut_formulir(): void
    {
        $this->bot->handleMessage($this->conversation, 'Kue Ijo');
        $this->bot->handleMessage($this->conversation, '3');

        $this->conversation->refresh();
        $this->assertEmpty($this->conversation->getContext('pending_options') ?? []);
        $this->assertEquals(1, $this->conversation->getContext('form_step'));

        $variant12 = ProductVariant::where('product_id', $this->ijo->id)->where('name', 'Isi 12')->first();
        $this->assertEquals($variant12->id, $this->conversation->getContext('selected_variant_id'));
    }

    public function test_angka_varian_di_luar_daftar_ditolak(): void
    {
        $this->bot->handleMessage($this->conversation, 'Kue Ijo');
        $this->bot->handleMessage($this->conversation, '9');

        $this->conversation->refresh();
        $this->assertNotEmpty($this->conversation->getContext('pending_options'));
        $this->assertNull($this->conversation->getContext('selected_variant_id'));
    }

    public function test_produk_satu_varian_langsung_formulir(): void
    {
        $this->bot->handleMessage($this->conversation, 'Tumpeng Mini Mix');

        $this->conversation->refresh();
        $this->assertEmpty($this->conversation->getContext('pending_options') ?? []);
        $this->assertEquals(1, $this->conversation->getContext('form_step'));
        $this->assertNotNull($this->conversation->getContext('selected_variant_id'));
        $this->assertEquals('Tumpeng Mini Mix', $this->conversation->getContext('order_form')['product_name']);
    }

    public function test_produk_tidak_ada_langsung_ditolak_di_awal(): void
    {
        $this->bot->handleMessage($this->conversation, 'Kue Mars');

        $this->conversation->refresh();
        $this->assertNull($this->conversation->getContext('order_form')['product_name'] ?? null);
        $this->assertEquals(0, $this->conversation->getContext('form_step'));
    }

    public function test_tagihan_memakai_varian_pilihan_bukan_termurah(): void
    {
        $this->bot->handleMessage($this->conversation, '2x Kue Ijo');
        $this->bot->handleMessage($this->conversation, '3'); // Isi 12 @40000

        $this->conversation->refresh();

        $variant = $this->invokeResolveSelectedVariant($this->conversation, $this->ijo);
        $this->assertEquals(40000, $variant->price);
        $this->assertEquals(2, $this->conversation->getContext('product_quantity'));
    }

    public function test_menu_nama_produk_spesifik_langsung_detail(): void
    {
        $this->conversation->update([
            'current_state' => OrderBotConversationState::MENU_SELECTION->value,
        ]);

        $this->bot->handleMessage($this->conversation, 'kue ijo');

        $this->conversation->refresh();
        $this->assertEquals(
            OrderBotConversationState::PRODUCT_BROWSING->value,
            $this->conversation->current_state
        );
        $this->assertEquals('Kue Ijo', $this->conversation->getContext('selected_product_name'));
    }

    protected function invokeResolveSelectedVariant(WhatsAppConversation $conversation, Product $product)
    {
        $method = new \ReflectionMethod(OrderBotService::class, 'resolveSelectedVariant');
        $method->setAccessible(true);

        return $method->invoke($this->bot, $conversation, $product);
    }
}
