<?php

namespace Tests\Feature\WhatsApp;

use App\Enums\OrderBotConversationState;
use App\Models\Region;
use App\Services\WhatsApp\OrderBotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Support\BuildsBotWorld;
use Tests\TestCase;

/**
 * R-13 — mode deskripsi produk: default only_after_berisi aktif di katalog
 * dan detail; 'full' menampilkan seluruh deskripsi.
 */
class ProductDescriptionModeTest extends TestCase
{
    use BuildsBotWorld;
    use RefreshDatabase;

    protected OrderBotService $bot;

    protected Region $region;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => ['id' => 'wamid_test']], 200),
        ]);

        $this->bindRecordingWhatsapp();
        $this->region = $this->makeRegion('Surabaya');
        $category = $this->makeCategory('Tumpeng');

        $this->makeProduct($category, 'Tumpeng Spesial', [['name' => 'Paket', 'price' => 50000]], null, '',
            'Porsi besar. Berisi: nasi tumpeng, ayam');
        $this->makeProduct($category, 'Kue Polos', [['name' => 'Satu', 'price' => 10000]], null, '',
            'Kue hijau tradisional');

        $this->bot = app(OrderBotService::class);
    }

    public function test_katalog_default_hanya_menampilkan_isi_setelah_berisi(): void
    {
        $conversation = $this->makeConversation($this->region, [], phone: '628111000010');

        $this->bot->handleMessage($conversation, 'PRODUK');

        $this->assertTrue($this->recorder->hasText('nasi tumpeng, ayam'));
        $this->assertFalse($this->recorder->hasText('Porsi besar.'));
        $this->assertFalse($this->recorder->hasText('Kue hijau tradisional'));
    }

    public function test_detail_produk_mengikuti_mode_deskripsi(): void
    {
        $conversation = $this->makeConversation(
            $this->region,
            [],
            OrderBotConversationState::PRODUCT_BROWSING->value,
            '628111000011'
        );

        $this->bot->handleMessage($conversation, 'Tumpeng Spesial');

        $text = $this->recorder->allText();
        $this->assertStringContainsString('nasi tumpeng, ayam', $text);
        $this->assertStringNotContainsString('Porsi besar.', $text);
        $this->assertStringContainsString("Varian:\n- Paket: Rp 50.000", $text);
    }

    public function test_mode_full_menampilkan_seluruh_deskripsi(): void
    {
        config(['bot.description_mode' => 'full']);

        $conversation = $this->makeConversation($this->region, [], phone: '628111000012');

        $this->bot->handleMessage($conversation, 'PRODUK');

        $this->assertTrue($this->recorder->hasText('Porsi besar.'));
        $this->assertTrue($this->recorder->hasText('nasi tumpeng, ayam'));
    }
}
