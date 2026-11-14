<?php

namespace Tests\Feature\WhatsApp;

use App\Enums\OrderBotConversationState;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariantPrice;
use App\Models\Region;
use App\Models\WhatsAppConversation;
use App\Services\WhatsApp\OrderBotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Support\BuildsBotWorld;
use Tests\TestCase;

/**
 * Fase 3 — R-01, R-06, R-15: harga mengikuti kota percakapan dan katalog
 * hanya berisi produk kota itu + produk global.
 */
class OrderBotRegionPriceTest extends TestCase
{
    use BuildsBotWorld;
    use RefreshDatabase;

    protected OrderBotService $bot;

    protected Region $surabaya;

    protected Region $malang;

    protected Product $kueIjo;

    protected Product $kueSurabaya;

    protected Product $kueMalang;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.whatsapp.default_region' => 'Surabaya']);

        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => ['id' => 'wamid_test']], 200),
            'lookaside.fbsbx.com/*' => Http::response('fake-image', 200, ['Content-Type' => 'image/jpeg']),
        ]);

        $this->bindRecordingWhatsapp();
        $this->surabaya = $this->makeRegion('Surabaya');
        $this->malang = $this->makeRegion('Malang');
        $category = $this->makeCategory('Produk');

        $this->kueIjo = $this->makeProduct($category, 'Kue Ijo', [
            ['name' => 'Isi 3', 'price' => 10000],
            ['name' => 'Isi 5', 'price' => 15000],
        ]);
        $this->kueSurabaya = $this->makeProduct($category, 'Kue Surabaya', [
            ['name' => 'Box', 'price' => 20000],
        ], $this->surabaya);
        $this->kueMalang = $this->makeProduct($category, 'Kue Malang', [
            ['name' => 'Box', 'price' => 30000],
        ], $this->malang);

        $isiTiga = $this->kueIjo->variants()->where('name', 'Isi 3')->first();
        ProductVariantPrice::create([
            'product_variant_id' => $isiTiga->id,
            'region_id' => $this->surabaya->id,
            'price' => 12000,
        ]);

        $boxSurabaya = $this->kueSurabaya->variants()->where('name', 'Box')->first();
        ProductVariantPrice::create([
            'product_variant_id' => $boxSurabaya->id,
            'region_id' => $this->surabaya->id,
            'price' => 25000,
        ]);

        $this->bot = app(OrderBotService::class);
    }

    protected function say(WhatsAppConversation $conversation, string $text): void
    {
        $this->bot->handleMessage($conversation, $text);
    }

    public function test_pricelist_memakai_harga_kota_masing_masing(): void
    {
        $surabaya = $this->makeConversation($this->surabaya, [], phone: '6281000000011');
        $this->say($surabaya, 'HARGA');
        $this->assertTrue($this->recorder->hasText('Rp 12.000'));

        $this->recorder->reset();
        $malang = $this->makeConversation($this->malang, [], phone: '6281000000012');
        $this->say($malang, 'HARGA');
        $this->assertTrue($this->recorder->hasText('Rp 10.000'));
        $this->assertFalse($this->recorder->hasText('Rp 12.000'));
    }

    public function test_katalog_hanya_berisi_produk_kota_sendiri_dan_global(): void
    {
        $surabaya = $this->makeConversation($this->surabaya, [], phone: '6281000000013');
        $this->say($surabaya, 'PRODUK');

        $this->assertTrue($this->recorder->hasText('Kue Ijo'));
        $this->assertTrue($this->recorder->hasText('Kue Surabaya'));
        $this->assertFalse($this->recorder->hasText('Kue Malang'));

        $this->recorder->reset();
        $malang = $this->makeConversation($this->malang, [], phone: '6281000000014');
        $this->say($malang, 'PRODUK');

        $this->assertTrue($this->recorder->hasText('Kue Ijo'));
        $this->assertTrue($this->recorder->hasText('Kue Malang'));
        $this->assertFalse($this->recorder->hasText('Kue Surabaya'));
    }

    public function test_pilihan_varian_memakai_harga_kota(): void
    {
        $conversation = $this->makeConversation(
            $this->surabaya,
            ['location_done' => true, 'form_step' => 0],
            OrderBotConversationState::AWAITING_ORDER_FORM->value
        );

        $this->say($conversation, 'Kue Ijo');

        $options = $conversation->fresh()->getContext('pending_options');
        $this->assertCount(2, $options);
        $this->assertSame('Isi 3', $options[0]['label']);
        $this->assertSame(12000, $options[0]['price']);
    }

    public function test_order_memakai_harga_kota_bukan_harga_dasar(): void
    {
        $conversation = $this->makeConversation(
            $this->surabaya,
            ['location_done' => true, 'form_step' => 0],
            OrderBotConversationState::AWAITING_ORDER_FORM->value,
            '6281000000015'
        );

        $this->say($conversation, '2x Kue Surabaya');
        $this->say($conversation, 'LANJUT');
        $this->say($conversation, '081234567890'); // nomor penerima (R-18)
        $this->say($conversation, 'Shinta');
        $this->say($conversation, 'Jl. Mawar No 10 Surabaya');
        $this->say($conversation, now()->addDays(3)->format('d M Y'));

        $conversation->setContext('delivery_method', 'self_pickup');
        $conversation->setContext('delivery_slot', '09:00-11:00');
        $conversation->setContext('ongkir', 0);
        $conversation->update(['current_state' => OrderBotConversationState::ORDER_SUMMARY->value]);

        $this->say($conversation, 'FIX');

        $order = Order::firstOrFail();
        $this->assertSame(50000, (int) $order->total_amount);
    }
}
