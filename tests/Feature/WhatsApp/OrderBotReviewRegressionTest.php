<?php

namespace Tests\Feature\WhatsApp;

use App\Enums\OrderBotConversationState;
use App\Jobs\ForwardOrderToOwnerJob;
use App\Models\AdminNotification;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantPrice;
use App\Models\Region;
use App\Models\User;
use App\Models\WhatsAppConversation;
use App\Services\WhatsApp\AdminNotificationRouterService;
use App\Services\WhatsApp\ConversationRegionResolver;
use App\Services\WhatsApp\OrderBotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\Support\BuildsBotWorld;
use Tests\TestCase;

/**
 * Regresi review whole-branch Fase 9: enam temuan Important dan lima Minor.
 */
class OrderBotReviewRegressionTest extends TestCase
{
    use BuildsBotWorld;
    use RefreshDatabase;

    protected OrderBotService $bot;

    protected Region $surabaya;

    protected Region $malang;

    protected Product $product;

    protected ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.whatsapp.default_region' => 'Surabaya',
            'bot.admin_phone' => null,
        ]);

        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid_test']]], 200),
        ]);

        $this->bindRecordingWhatsapp();
        $this->surabaya = $this->makeRegion('Surabaya');
        $this->malang = $this->makeRegion('Malang');

        $category = $this->makeCategory('Kue Tradisional');
        $this->product = $this->makeProduct($category, 'Kue Pandan', [
            ['name' => 'Reguler', 'price' => 12000],
        ]);
        $this->variant = $this->product->variants()->first();

        $this->bot = app(OrderBotService::class);
    }

    protected function say(WhatsAppConversation $conversation, string $text, ?array $data = null): void
    {
        $this->bot->handleMessage($conversation, $text, $data);
    }

    protected function orderContext(array $overrides = []): array
    {
        return array_merge([
            'cart' => [[
                'product_id' => $this->product->id,
                'product_name' => $this->product->name,
                'variant_id' => $this->variant->id,
                'variant_name' => $this->variant->name,
                'qty' => 1,
            ]],
            'order_customer' => [
                'name' => 'Budi',
                'address' => 'Jl. Pahlawan No 10 Surabaya',
                'date' => now()->addDays(3)->format('d M Y'),
            ],
            'recipient_phone' => '6281234567890',
            'delivery_method' => 'self_pickup',
            'delivery_slot' => '09:00-11:00',
            'ongkir' => 0,
            'form_step' => 5,
        ], $overrides);
    }

    protected function makeOrder(WhatsAppConversation $conversation, array $overrides = []): Order
    {
        $customer = Customer::create([
            'name' => 'Budi',
            'phone' => $conversation->phone_number,
            'address' => 'Jl. Pahlawan No 10 Surabaya',
            'region_id' => $conversation->region_id,
        ]);

        return Order::create(array_merge([
            'invoice_number' => 'INV/REV/'.Str::random(6),
            'customer_id' => $customer->id,
            'phone' => $conversation->phone_number,
            'recipient_phone' => '6281234567890',
            'address' => 'Jl. Pahlawan No 10 Surabaya',
            'total_amount' => 12000,
            'payment_method' => 'qris',
            'status' => 'baru',
            'region_id' => $conversation->region_id,
            'channel' => 'whatsapp',
        ], $overrides));
    }

    // I-1 — MENU setelah FIX tidak boleh meninggalkan order yatim.

    public function test_menu_setelah_fix_membatalkan_order_belum_dibayar(): void
    {
        $conversation = $this->makeConversation(
            $this->surabaya,
            $this->orderContext(),
            OrderBotConversationState::ORDER_SUMMARY->value
        );

        $this->say($conversation, 'FIX');
        $order = Order::firstOrFail();
        $this->assertSame('baru', $order->status);

        $this->say($conversation, 'MENU');

        $fresh = $conversation->fresh();
        $this->assertSame('dibatalkan', $order->fresh()->status, 'Order belum dibayar harus dibatalkan saat MENU.');
        $this->assertNull($fresh->getContext('confirmed_order_id'));
        $this->assertSame(OrderBotConversationState::MENU_SELECTION->value, $fresh->current_state);
        $this->assertSame(1, Order::count(), 'Tidak boleh ada order ganda.');
    }

    // I-2 — GANTI KOTA harus melepas seluruh state pengiriman kota lama.

    public function test_ganti_kota_membatalkan_order_dan_membersihkan_state_pengiriman(): void
    {
        $conversation = $this->makeConversation(
            $this->surabaya,
            $this->orderContext([
                'delivery_method' => 'internal_courier',
                'ongkir' => 15000,
                'distance_km' => 5,
                'location_done' => true,
                'location_source' => 'pin',
                'delivery_lat' => -7.25,
                'delivery_lng' => 112.75,
                'delivery_address' => 'Jl. Pahlawan No 10 Surabaya',
            ]),
            OrderBotConversationState::ORDER_SUMMARY->value
        );
        $order = $this->makeOrder($conversation);
        $conversation->setContext('confirmed_order_id', $order->id);

        $this->say($conversation, 'GANTI KOTA');
        $this->say($conversation, 'Malang');

        $fresh = $conversation->fresh();
        $this->assertSame($this->malang->id, $fresh->region_id);
        $this->assertSame('dibatalkan', $order->fresh()->status);

        foreach ([
            'delivery_method', 'delivery_slot', 'ongkir', 'distance_km',
            'location_done', 'location_source', 'delivery_lat', 'delivery_lng',
            'delivery_address', 'confirmed_order_id',
        ] as $key) {
            $this->assertNull($fresh->getContext($key), "Konteks {$key} harus dibersihkan saat pindah kota.");
        }

        $this->assertNotEmpty($fresh->getContext('cart'), 'Keranjang lintas kota harus dipertahankan.');
        $this->assertSame('Budi', $fresh->getContext('order_customer')['name']);
        $this->assertSame('6281234567890', $fresh->getContext('recipient_phone'));
    }

    // I-3 — SKIP kedua tetap di ORDER_CONFIRMED dan bukti susulan tetap tertempel.

    public function test_skip_kedua_tetap_di_order_confirmed_dan_bukti_susulan_tertempel(): void
    {
        $conversation = $this->makeConversation(
            $this->surabaya,
            [],
            OrderBotConversationState::AWAITING_PAYMENT_PROOF->value
        );
        $order = $this->makeOrder($conversation);
        $conversation->setContext('confirmed_order_id', $order->id);

        $this->say($conversation, 'SKIP');
        $this->assertSame(OrderBotConversationState::ORDER_CONFIRMED->value, $conversation->fresh()->current_state);

        $this->say($conversation, 'SKIP');

        $fresh = $conversation->fresh();
        $this->assertSame(OrderBotConversationState::ORDER_CONFIRMED->value, $fresh->current_state);
        $this->assertSame($order->id, $fresh->getContext('confirmed_order_id'));

        $this->say($conversation, '[Gambar]', [
            'media_id' => 'media_late',
            'media_path' => 'payment_proofs/late.jpg',
        ]);

        $this->assertSame('payment_proofs/late.jpg', $order->fresh()->payment_proof);
    }

    // I-4 — Nomor penerima wajib muncul di ringkasan dan notifikasi admin/owner.

    public function test_ringkasan_dan_notifikasi_memuat_nomor_penerima(): void
    {
        Role::create(['name' => 'admin']);
        $admin = User::create([
            'name' => 'Admin Surabaya',
            'email' => 'admin.sby@test.com',
            'password' => bcrypt('password'),
            'region_id' => $this->surabaya->id,
        ]);
        $admin->assignRole('admin');

        $conversation = $this->makeConversation(
            $this->surabaya,
            $this->orderContext(),
            OrderBotConversationState::ORDER_SUMMARY->value,
            '6281000000001'
        );

        $this->say($conversation, 'UBAH ALAMAT');
        $this->say($conversation, 'Jl. Melati No 5 Surabaya');

        $this->assertStringContainsString(
            'HP Penerima: 6281234567890',
            $this->recorder->allText(),
            'Ringkasan pelanggan harus memuat nomor penerima.'
        );

        $this->say($conversation, 'FIX');
        $order = Order::firstOrFail();

        app(AdminNotificationRouterService::class)->notifyNewOrder($order->id);

        $notification = AdminNotification::where('order_id', $order->id)
            ->where('type', 'new_order')
            ->firstOrFail();
        $this->assertStringContainsString('HP Penerima: 6281234567890', $notification->message);

        $this->recorder->reset();
        $this->surabaya->update(['owner_phone' => '628999000111']);
        app(AdminNotificationRouterService::class)->forwardOrderToOwner($order);
        $this->assertStringContainsString('HP Penerima: 6281234567890', $this->recorder->allText());
    }

    // I-5 — D1: BOT_ADMIN_PHONE utama, regions.owner_phone fallback.

    public function test_sambutan_memakai_bot_admin_phone_lalu_fallback_owner_kota(): void
    {
        $this->surabaya->update(['owner_phone' => '628999000111']);
        $conversation = $this->makeConversation($this->surabaya, [], OrderBotConversationState::WELCOME_SENT->value);

        $this->say($conversation, 'halo');
        $this->assertStringContainsString('Admin WhatsApp: 628999000111', $this->recorder->allText());

        $this->recorder->reset();
        config(['bot.admin_phone' => '+62 851-1365-8557']);
        $this->say($conversation, 'halo');

        $text = $this->recorder->allText();
        $this->assertStringContainsString('Admin WhatsApp: 6285113658557', $text);
        $this->assertStringNotContainsString('628999000111', $text);
    }

    public function test_forward_order_memakai_bot_admin_phone_lalu_fallback_owner_kota(): void
    {
        $this->surabaya->update(['owner_phone' => '628999000111']);
        $conversation = $this->makeConversation($this->surabaya);
        $order = $this->makeOrder($conversation);
        $router = app(AdminNotificationRouterService::class);

        config(['bot.admin_phone' => '6285113658557']);
        $this->assertTrue($router->forwardOrderToOwner($order));
        $this->assertSame('6285113658557', $this->recorder->sent[0]['to']);

        $this->recorder->reset();
        config(['bot.admin_phone' => null]);
        $this->assertTrue($router->forwardOrderToOwner($order));
        $this->assertSame('628999000111', $this->recorder->sent[0]['to']);
    }

    public function test_job_forward_tetap_jalan_bila_hanya_bot_admin_phone_tersedia(): void
    {
        $conversation = $this->makeConversation($this->surabaya);
        $order = $this->makeOrder($conversation);

        config(['bot.admin_phone' => '6285113658557']);

        (new ForwardOrderToOwnerJob($order->id))->handle(app(AdminNotificationRouterService::class));

        $this->assertSame('6285113658557', $this->recorder->sent[0]['to']);
    }

    // I-6 — Harga yang ditampilkan harus harga kota (PriceResolver).

    public function test_detail_produk_dan_kandidat_pencarian_memakai_harga_kota(): void
    {
        $category = $this->makeCategory('Produk Kota');
        $detailProduct = $this->makeProduct($category, 'Kue Kota', [
            ['name' => 'Kecil', 'price' => 5000],
            ['name' => 'Besar', 'price' => 20000],
        ]);
        $detailProduct->update(['image_path' => 'assets/kue-kota.jpg']);

        $variants = $detailProduct->variants()->get()->keyBy('name');
        ProductVariantPrice::create([
            'product_variant_id' => $variants['Kecil']->id,
            'region_id' => $this->surabaya->id,
            'price' => 25000,
        ]);
        ProductVariantPrice::create([
            'product_variant_id' => $variants['Besar']->id,
            'region_id' => $this->surabaya->id,
            'price' => 10000,
        ]);

        $conversation = $this->makeConversation(
            $this->surabaya,
            [],
            OrderBotConversationState::PRODUCT_BROWSING->value
        );

        $this->say($conversation, 'Kue Kota');

        $captions = array_filter(array_map(
            fn ($message) => $message['payload']['caption'] ?? null,
            $this->recorder->messagesOfType('image')
        ));
        $this->assertNotEmpty($captions);
        $this->assertStringContainsString('Mulai Rp 10.000', implode("\n", $captions));

        $text = $this->recorder->allText();
        $this->assertStringContainsString('Kecil: Rp 25.000', $text);
        $this->assertStringContainsString('Besar: Rp 10.000', $text);

        // Kandidat pencarian: produk termurah versi kota, bukan harga dasar.
        $this->makeProduct($category, 'Kue Banding', [
            ['name' => 'A', 'price' => 5000],
            ['name' => 'B', 'price' => 15000],
        ]);
        $banding = Product::where('name', 'Kue Banding')->firstOrFail();
        $bandingVariants = $banding->variants()->get()->keyBy('name');
        ProductVariantPrice::create([
            'product_variant_id' => $bandingVariants['A']->id,
            'region_id' => $this->surabaya->id,
            'price' => 20000,
        ]);
        ProductVariantPrice::create([
            'product_variant_id' => $bandingVariants['B']->id,
            'region_id' => $this->surabaya->id,
            'price' => 10000,
        ]);

        $this->recorder->reset();
        $form = $this->makeConversation(
            $this->surabaya,
            ['form_step' => 0],
            OrderBotConversationState::AWAITING_ORDER_FORM->value,
            '6281000000002'
        );
        $this->say($form, 'Kue');

        $optionsText = $this->recorder->allText();
        $this->assertStringContainsString('— Rp 10.000', $optionsText);
        $this->assertStringNotContainsString('— Rp 5.000', $optionsText);
    }

    // M-2 — Jawaban command-like di langkah nomor penerima tetap divalidasi sebagai nomor.

    public function test_menu_saat_langkah_nomor_penerima_tidak_keluar_form(): void
    {
        $conversation = $this->makeConversation(
            $this->surabaya,
            $this->orderContext(['form_step' => 6]),
            OrderBotConversationState::AWAITING_ORDER_FORM->value
        );

        $this->say($conversation, 'MENU');

        $fresh = $conversation->fresh();
        $this->assertSame(OrderBotConversationState::AWAITING_ORDER_FORM->value, $fresh->current_state);
        $this->assertSame(6, $fresh->getContext('form_step'));
        $this->assertStringContainsString('Nomor HP tidak valid', $this->recorder->allText());
    }

    // M-3 — Reset stale mempertahankan nomor penerima dan atribusi kota manual.

    public function test_reset_stale_mempertahankan_nomor_penerima_dan_kota_manual(): void
    {
        $conversation = $this->makeConversation(
            $this->surabaya,
            $this->orderContext([
                'branch_source' => ConversationRegionResolver::SOURCE_MANUAL,
            ])
        );

        $this->assertTrue($this->bot->resetConversationIfStale($conversation, now()->subHours(2)));

        $fresh = $conversation->fresh();
        $this->assertSame('6281234567890', $fresh->getContext('recipient_phone'));
        $this->assertSame(ConversationRegionResolver::SOURCE_MANUAL, $fresh->getContext('branch_source'));
        $this->assertNotEmpty($fresh->getContext('cart'));
    }

    // M-4 — Footer tidak boleh menawarkan perintah yang tidak dirutekan.

    public function test_footer_state_nonaktif_hanya_menawarkan_perintah_yang_didukung(): void
    {
        $this->assertSame('Perintah: *PESAN* · *MENU*', $this->bot->footerFor(OrderBotConversationState::ORDER_CONFIRMED));
        $this->assertSame('Perintah: *PESAN*', $this->bot->footerFor(OrderBotConversationState::CLOSED));
        $this->assertSame('Perintah: *PESAN*', $this->bot->footerFor(OrderBotConversationState::ESCALATED_TO_HUMAN));
        $this->assertStringNotContainsString('MENU', $this->bot->footerFor(OrderBotConversationState::CLOSED));
        $this->assertStringNotContainsString('MENU', $this->bot->footerFor(OrderBotConversationState::ESCALATED_TO_HUMAN));
    }

    public function test_pesan_dari_state_closed_membuka_katalog(): void
    {
        $conversation = $this->makeConversation($this->surabaya, [], OrderBotConversationState::CLOSED->value);

        $this->say($conversation, 'PESAN');

        $this->assertSame(OrderBotConversationState::AWAITING_ORDER_FORM->value, $conversation->fresh()->current_state);
        $this->assertTrue($this->recorder->hasText('List Produk — Kue Pandan Asli'));
    }

    // M-5 — D7: kata "cabang" hilang dari copy admin/owner.

    public function test_copy_admin_dan_owner_tidak_memakai_kata_cabang(): void
    {
        $this->surabaya->update(['owner_phone' => '628999000111']);
        $conversation = $this->makeConversation($this->surabaya);
        $order = $this->makeOrder($conversation);
        $router = app(AdminNotificationRouterService::class);

        $this->assertTrue($router->forwardOrderToOwner($order));
        $ownerText = $this->recorder->allText();
        $this->assertStringContainsString('Kota: Surabaya', $ownerText);
        $this->assertStringNotContainsString('Cabang:', $ownerText);

        $this->recorder->reset();
        $this->assertTrue($router->sendOwnerTestMessage($this->surabaya));
        $testText = $this->recorder->allText();
        $this->assertStringContainsString('Kota: Surabaya', $testText);
        $this->assertStringNotContainsString('Cabang:', $testText);

        $router->notifyOwnerForwardFailure($order, 'uji');
        $notification = AdminNotification::where('order_id', $order->id)
            ->where('title', 'Gagal meneruskan pesanan ke owner')
            ->firstOrFail();
        $this->assertStringNotContainsStringIgnoringCase('cabang', $notification->message);
    }
}
