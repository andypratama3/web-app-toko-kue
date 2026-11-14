<?php

namespace Tests\Feature\WhatsApp;

use App\Enums\OrderBotConversationState;
use App\Models\Region;
use App\Models\WhatsAppConversation;
use App\Services\WhatsApp\ConversationRegionResolver;
use App\Services\WhatsApp\OrderBotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Support\BuildsBotWorld;
use Tests\TestCase;

/**
 * Fase 2 — R-05 & R-11: kota pelanggan diingat, perintah global GANTI KOTA
 * tersedia dari state mana pun, dan keranjang disaring ulang saat pindah kota.
 */
class OrderBotCitySwitchTest extends TestCase
{
    use BuildsBotWorld;
    use RefreshDatabase;

    protected OrderBotService $bot;

    protected Region $surabaya;

    protected Region $malang;

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
        $this->bot = app(OrderBotService::class);
    }

    protected function say(WhatsAppConversation $conversation, string $text): void
    {
        $this->bot->handleMessage($conversation, $text);
    }

    public function test_ganti_kota_di_state_welcome_menampilkan_kota_kini_lalu_daftar(): void
    {
        $conversation = $this->makeConversation($this->surabaya);

        $this->say($conversation, 'GANTI KOTA');

        $this->assertTrue($this->recorder->hasText('Kota pengiriman saat ini: *Surabaya*.'));
        $this->assertTrue($this->recorder->hasText('Pilih Kota Pengiriman'));
        $this->assertTrue($conversation->fresh()->getContext('awaiting_branch'));
    }

    public function test_ganti_kota_bekerja_dari_state_form_dan_tidak_menelan_jawaban_alamat(): void
    {
        $conversation = $this->makeConversation(
            $this->surabaya,
            [
                'location_done' => true,
                'after_location' => 'cart',
                'form_step' => 2,
                'order_customer' => ['name' => 'Budi'],
            ],
            OrderBotConversationState::AWAITING_ORDER_FORM->value
        );

        $this->say($conversation, 'GANTI KOTA');

        $this->assertTrue($this->recorder->hasText('Pilih Kota Pengiriman'));
        $this->assertArrayNotHasKey('address', $conversation->fresh()->getContext('order_customer') ?? []);
    }

    public function test_kota_terkunci_tetap_bisa_diganti(): void
    {
        $conversation = $this->makeConversation($this->malang, [
            'branch_source' => ConversationRegionResolver::SOURCE_MANUAL,
        ]);

        $this->say($conversation, 'GANTI KOTA');
        $this->say($conversation, 'Surabaya');

        $fresh = $conversation->fresh();
        $this->assertSame($this->surabaya->id, $fresh->region_id);
        $this->assertSame(ConversationRegionResolver::SOURCE_MANUAL, $fresh->getContext('branch_source'));
        $this->assertNull($fresh->getContext('awaiting_branch'));
    }

    public function test_pindah_kota_membuang_hanya_produk_kota_lama(): void
    {
        $kategori = $this->makeCategory('Produk');

        $produkKota = $this->makeProduct($kategori, 'Kue Ijo', [
            ['name' => 'Isi 3', 'price' => 9000],
        ], $this->surabaya, 'Ala Carte');
        $produkUmum = $this->makeProduct($kategori, 'Nopia', [
            ['name' => 'Regular', 'price' => 8000],
        ], null, 'Ala Carte');

        $conversation = $this->makeConversation($this->surabaya, [
            'branch_source' => ConversationRegionResolver::SOURCE_MANUAL,
            'cart' => [
                [
                    'product_id' => $produkKota->id,
                    'product_name' => 'Kue Ijo',
                    'variant_id' => $produkKota->variants->first()->id,
                    'variant_name' => 'Isi 3',
                    'qty' => 1,
                ],
                [
                    'product_id' => $produkUmum->id,
                    'product_name' => 'Nopia',
                    'variant_id' => $produkUmum->variants->first()->id,
                    'variant_name' => 'Regular',
                    'qty' => 1,
                ],
            ],
        ]);

        $this->say($conversation, 'GANTI KOTA');
        $this->say($conversation, 'Malang');

        $fresh = $conversation->fresh();
        $this->assertSame($this->malang->id, $fresh->region_id);
        $this->assertTrue(
            $this->recorder->hasText('Item berikut sudah tidak tersedia'),
            'Baris kota lama harus diumumkan, bukan dibuang diam-diam.'
        );

        $cart = $fresh->getContext('cart') ?? [];
        $this->assertCount(1, $cart, 'Hanya produk kota lama yang dibuang; produk lintas kota tetap ada.');
        $this->assertSame('Nopia', $cart[0]['product_name']);
    }

    public function test_nomor_lama_yang_sudah_pilih_kota_tidak_ditanya_ulang(): void
    {
        $conversation = $this->makeConversation($this->surabaya, [
            'branch_source' => ConversationRegionResolver::SOURCE_MANUAL,
        ]);

        $this->say($conversation, 'PESAN');

        $this->assertFalse(
            $this->recorder->hasText('Pilih Kota Pengiriman'),
            'Kota yang sudah terkunci tidak boleh ditanya ulang (R-05).'
        );
        $this->assertTrue($this->recorder->hasText('Pilih Lokasi Pengiriman'));
        $this->assertNull($conversation->fresh()->getContext('awaiting_branch'));
        $this->assertSame($this->surabaya->id, $conversation->fresh()->region_id);
    }

    public function test_reset_stale_mempertahankan_kota_pilihan(): void
    {
        $conversation = $this->makeConversation(
            $this->surabaya,
            ['branch_source' => ConversationRegionResolver::SOURCE_MANUAL],
            OrderBotConversationState::AWAITING_ORDER_FORM->value
        );

        $this->assertTrue($this->bot->resetConversationIfStale($conversation, now()->subHours(3)));

        $fresh = $conversation->fresh();
        $this->assertSame(OrderBotConversationState::WELCOME_SENT->value, $fresh->current_state);
        $this->assertSame($this->surabaya->id, $fresh->region_id);
        $this->assertSame(ConversationRegionResolver::SOURCE_MANUAL, $fresh->getContext('branch_source'));
    }

    public function test_welcome_menyebut_kota_dan_perintah_ganti_kota(): void
    {
        $conversation = $this->makeConversation($this->surabaya);

        $this->say($conversation, 'halo');

        $text = $this->recorder->allText();
        $this->assertStringContainsString('Kota pengiriman: *Surabaya*', $text);
        $this->assertStringContainsString('*GANTI KOTA*', $text);
        $this->assertStringNotContainsString('Cabang ', $text);
    }
}
