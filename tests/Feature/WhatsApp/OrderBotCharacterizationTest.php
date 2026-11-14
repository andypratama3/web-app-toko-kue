<?php

namespace Tests\Feature\WhatsApp;

use App\Enums\OrderBotConversationState;
use App\Models\Category;
use App\Models\Product;
use App\Models\Region;
use App\Models\WhatsAppConversation;
use App\Services\WhatsApp\OrderBotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Support\BuildsBotWorld;
use Tests\TestCase;

/**
 * Characterization test: mengunci perilaku bot yang sudah berjalan sebelum
 * revisi klien (R-01…R-18) dikerjakan. Assertion sengaja hanya pada fragmen
 * kunci, bukan teks utuh, supaya perubahan copy di fase berikutnya tetap
 * terdeteksi tanpa membuat test rapuh.
 */
class OrderBotCharacterizationTest extends TestCase
{
    use BuildsBotWorld;
    use RefreshDatabase;

    protected Region $surabaya;

    protected Region $malang;

    protected Category $kategori;

    protected Product $ijo;

    protected OrderBotService $bot;

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

        $this->kategori = $this->makeCategory('Produk');

        $this->ijo = $this->makeProduct($this->kategori, 'Kue Ijo', [
            ['name' => 'Isi 3', 'price' => 9000],
            ['name' => 'Isi 5', 'price' => 15000],
            ['name' => 'Isi 12', 'price' => 40000],
        ], $this->surabaya, 'Ala Carte');

        $this->bot = app(OrderBotService::class);
    }

    protected function say(WhatsAppConversation $conversation, string $text): void
    {
        $this->bot->handleMessage($conversation, $text);
    }

    public function test_sapaan_awal_mengirim_welcome_dan_state_tetap(): void
    {
        $conversation = $this->makeConversation($this->surabaya);

        $this->say($conversation, 'halo');

        $this->assertTrue($this->recorder->hasText('Kue Pandan Asli'));
        $this->assertTrue($this->recorder->hasText('*PESAN*'));
        $this->assertSame(
            OrderBotConversationState::WELCOME_SENT->value,
            $conversation->fresh()->current_state
        );
    }

    public function test_pesan_dengan_kota_known_langsung_membuka_katalog(): void
    {
        $conversation = $this->makeConversation($this->surabaya);

        $this->say($conversation, 'PESAN');

        $this->assertTrue($this->recorder->hasText('List Produk — Kue Pandan Asli'));
        $this->assertNull($conversation->fresh()->getContext('awaiting_branch'));
        $this->assertSame(
            OrderBotConversationState::AWAITING_ORDER_FORM->value,
            $conversation->fresh()->current_state
        );
    }

    public function test_produk_menampilkan_katalog_dan_masuk_state_pemilihan_produk(): void
    {
        $conversation = $this->makeConversation($this->surabaya);

        $this->say($conversation, 'PRODUK');

        $this->assertTrue($this->recorder->hasText('List Produk — Kue Pandan Asli'));
        $this->assertTrue($this->recorder->hasText('Kue Ijo'));
        $this->assertSame(
            OrderBotConversationState::AWAITING_ORDER_FORM->value,
            $conversation->fresh()->current_state
        );
    }

    public function test_harga_menampilkan_pricelist_tanpa_mengubah_state(): void
    {
        $conversation = $this->makeConversation($this->surabaya);

        $this->say($conversation, 'HARGA');

        $this->assertTrue($this->recorder->hasText('PRICELIST'));
        $this->assertTrue($this->recorder->hasText('Kue Ijo'));
        $this->assertSame(
            OrderBotConversationState::WELCOME_SENT->value,
            $conversation->fresh()->current_state
        );
    }

    public function test_pilih_produk_bervarian_lalu_pilih_nomor_masuk_keranjang(): void
    {
        $conversation = $this->makeConversation($this->surabaya, [
            'location_done' => true,
            'form_step' => 0,
        ], OrderBotConversationState::AWAITING_ORDER_FORM->value);

        $this->say($conversation, 'Kue Ijo');

        $conversation->refresh();
        $this->assertNotEmpty($conversation->getContext('pending_options'));
        $this->assertTrue($this->recorder->hasText('Pilih ukuran/varian'));

        $this->recorder->reset();
        $this->say($conversation, '3');

        $conversation->refresh();
        $cart = $conversation->getContext('cart');
        $this->assertCount(1, $cart);
        $this->assertSame('Kue Ijo', $cart[0]['product_name']);
        $this->assertSame('Isi 12', $cart[0]['variant_name']);
        $this->assertSame(1, $cart[0]['qty']);
        $this->assertTrue($this->recorder->hasText('Masuk keranjang'));
    }

    public function test_lanjut_mengisi_form_sampai_ringkasan(): void
    {
        $conversation = $this->makeConversation($this->surabaya, [
            'location_done' => true,
            'form_step' => 4,
            'cart' => [[
                'product_id' => $this->ijo->id,
                'product_name' => 'Kue Ijo',
                'variant_id' => $this->ijo->variants()->where('name', 'Isi 5')->first()->id,
                'variant_name' => 'Isi 5',
                'qty' => 2,
            ]],
        ], OrderBotConversationState::AWAITING_ORDER_FORM->value);

        $this->say($conversation, 'LANJUT');
        $this->assertTrue($this->recorder->hasText('Siapa nama penerimanya'));

        $this->say($conversation, 'Shinta');
        $this->assertTrue($this->recorder->hasText('Apa alamat lengkapnya'));

        $this->say($conversation, 'Jl. Mawar No 10 Surabaya');
        $this->assertTrue($this->recorder->hasText('Nomor HP penerima'));

        $this->say($conversation, '081234567890');
        $this->assertTrue($this->recorder->hasText('Mau dikirim tanggal berapa'));

        $this->say($conversation, now()->addDays(3)->format('d/m/Y'));
        $this->assertTrue($this->recorder->hasText('Pilih Metode Pengiriman'));

        $this->say($conversation, '1');
        $this->assertTrue($this->recorder->hasText('Pilih Jam Pengiriman'));

        $this->say($conversation, 'pagi');

        $conversation->refresh();
        $this->assertSame(
            OrderBotConversationState::ORDER_SUMMARY->value,
            $conversation->current_state
        );
        $this->assertTrue($this->recorder->hasText('RINGKASAN PESANAN'));
        $this->assertTrue($this->recorder->hasText('Shinta'));
        $this->assertTrue($this->recorder->hasText('Kue Ijo'));
    }

    public function test_batal_mengosongkan_konteks_dan_kembali_ke_menu(): void
    {
        $conversation = $this->makeConversation($this->surabaya, [
            'location_done' => true,
            'form_step' => 4,
            'cart' => [[
                'product_id' => $this->ijo->id,
                'product_name' => 'Kue Ijo',
                'variant_id' => $this->ijo->variants()->where('name', 'Isi 5')->first()->id,
                'variant_name' => 'Isi 5',
                'qty' => 1,
            ]],
        ], OrderBotConversationState::AWAITING_ORDER_FORM->value);

        $this->say($conversation, 'BATAL');

        $conversation->refresh();
        $this->assertEmpty($conversation->getContext('cart'));
        $this->assertTrue($this->recorder->hasText('dibatalkan'));
    }
}
