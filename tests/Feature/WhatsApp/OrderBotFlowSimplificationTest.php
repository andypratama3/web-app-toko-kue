<?php

namespace Tests\Feature\WhatsApp;

use App\Enums\OrderBotConversationState;
use App\Models\Region;
use App\Models\WhatsAppConversation;
use App\Services\WhatsApp\OrderBotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Support\BuildsBotWorld;
use Tests\TestCase;

class OrderBotFlowSimplificationTest extends TestCase
{
    use BuildsBotWorld;
    use RefreshDatabase;

    protected Region $surabaya;

    protected OrderBotService $bot;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.whatsapp.default_region' => 'Surabaya',
            'bot.admin_phone' => null,
        ]);

        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => ['id' => 'wamid_test']], 200),
            'lookaside.fbsbx.com/*' => Http::response('fake-image', 200, ['Content-Type' => 'image/jpeg']),
        ]);

        $this->bindRecordingWhatsapp();
        $this->surabaya = $this->makeRegion('Surabaya');
        $this->bot = app(OrderBotService::class);
    }

    protected function say(WhatsAppConversation $conversation, string $text): void
    {
        $this->bot->handleMessage($conversation, $text);
    }

    public function test_sambutan_ringkas_memuat_kota_dan_empat_perintah_utama(): void
    {
        $conversation = $this->makeConversation($this->surabaya);

        $this->say($conversation, 'halo');

        $text = $this->recorder->allText();
        $this->assertStringContainsString('Kue Pandan Asli', $text);
        $this->assertStringContainsString('Kota pengiriman: *Surabaya*', $text);
        $this->assertStringContainsString('*PESAN*', $text);
        $this->assertStringContainsString('*MENU*', $text);
        $this->assertStringContainsString('*HARGA*', $text);
        $this->assertStringContainsString('*GANTI KOTA*', $text);
        $this->assertStringNotContainsString('Tanpa toko offline', $text);
        $this->assertStringNotContainsString('Kami menjual kue tradisional', $text);
    }

    public function test_kontak_admin_disembunyikan_karena_bot_admin_phone_kosong(): void
    {
        $conversation = $this->makeConversation($this->surabaya);

        $this->say($conversation, 'halo');

        $this->assertStringNotContainsString('Admin WhatsApp', $this->recorder->allText());
    }

    public function test_kontak_admin_muncul_dan_selalu_merupakan_satu_nomor(): void
    {
        config(['bot.admin_phone' => '+62 851-1365-8557']);
        $conversation = $this->makeConversation($this->surabaya, phone: '6281000000099');

        $this->say($conversation, 'halo');

        $text = $this->recorder->allText();
        $this->assertStringContainsString('Admin WhatsApp', $text);
        $this->assertStringContainsString('6285113658557', $text);
        $this->assertSame(1, substr_count($text, 'Admin WhatsApp'));
    }

    public function test_footer_per_state_satu_baris_dan_hanya_perintah_yang_relevan(): void
    {
        $expected = [
            OrderBotConversationState::INIT->value => 'Perintah: *PESAN* · *MENU* · *HARGA* · *GANTI KOTA*',
            OrderBotConversationState::WELCOME_SENT->value => 'Perintah: *PESAN* · *MENU* · *HARGA* · *GANTI KOTA*',
            OrderBotConversationState::MENU_SELECTION->value => 'Perintah: *PESAN* · *MENU* · *HARGA* · *GANTI KOTA*',
            OrderBotConversationState::PRODUCT_BROWSING->value => 'Perintah: *PESAN* · *MENU* · *HARGA* · *GANTI KOTA*',
            OrderBotConversationState::AWAITING_ORDER_FORM->value => 'Perintah: *BATAL* · *GANTI KOTA*',
            OrderBotConversationState::AWAITING_DELIVERY_METHOD->value => 'Perintah: *BATAL* · *GANTI KOTA*',
            OrderBotConversationState::AWAITING_LOCATION_OR_ADDRESS->value => 'Perintah: *BATAL* · *GANTI KOTA*',
            OrderBotConversationState::AWAITING_DELIVERY_SLOT->value => 'Perintah: *BATAL* · *GANTI KOTA*',
            OrderBotConversationState::ORDER_SUMMARY->value => 'Perintah: *FIX* · *UBAH ALAMAT* · *BATAL*',
            OrderBotConversationState::AWAITING_PAYMENT_PROOF->value => 'Perintah: *SKIP* · *BATAL*',
            OrderBotConversationState::ORDER_CONFIRMED->value => 'Perintah: *PESAN* · *MENU*',
            OrderBotConversationState::CLOSED->value => 'Perintah: *PESAN* · *MENU*',
            OrderBotConversationState::ESCALATED_TO_HUMAN->value => 'Perintah: *PESAN* · *MENU*',
        ];

        foreach ($expected as $state => $footer) {
            $this->assertSame(
                $footer,
                $this->bot->footerFor(OrderBotConversationState::from($state)),
                "Footer state {$state} tidak sesuai."
            );
            $this->assertStringNotContainsString("\n", $footer);
        }
    }

    public function test_sambutan_berakhir_dengan_footer_state(): void
    {
        $conversation = $this->makeConversation($this->surabaya);

        $this->say($conversation, 'halo');

        $this->assertStringEndsWith(
            $this->bot->footerFor(OrderBotConversationState::WELCOME_SENT),
            trim($this->recorder->allText())
        );
    }

    public function test_pesan_dan_produk_membuka_pintu_katalog_yang_sama(): void
    {
        $category = $this->makeCategory('Produk');
        $this->makeProduct($category, 'Kue Ijo', [
            ['name' => 'Isi 3', 'price' => 9000],
        ]);
        $conversation = $this->makeConversation($this->surabaya);

        $this->say($conversation, 'PESAN');

        $this->assertSame(
            OrderBotConversationState::AWAITING_ORDER_FORM->value,
            $conversation->fresh()->current_state
        );
        $this->assertSame(1, substr_count($this->recorder->allText(), 'List Produk — Kue Pandan Asli'));
        $this->assertCount(1, $conversation->fresh()->getContext('catalog_ids'));

        $this->recorder->reset();
        $this->say($conversation, 'PRODUK');

        $this->assertSame(
            OrderBotConversationState::AWAITING_ORDER_FORM->value,
            $conversation->fresh()->current_state
        );
        $this->assertSame(1, substr_count($this->recorder->allText(), 'List Produk — Kue Pandan Asli'));
    }

    public function test_harga_membuka_pricelist_dan_menu_membuka_kategori(): void
    {
        $conversation = $this->makeConversation($this->surabaya);

        $this->say($conversation, 'HARGA');

        $this->assertTrue($this->recorder->hasText('PRICELIST'));
        $this->assertSame(
            OrderBotConversationState::WELCOME_SENT->value,
            $conversation->fresh()->current_state
        );

        $this->recorder->reset();
        $this->say($conversation, 'MENU');

        $lists = $this->recorder->messagesOfType('list');
        $this->assertNotEmpty($lists);
        $this->assertStringContainsString('Pilih Kategori', $lists[0]['payload']['text']);
        $this->assertSame(
            OrderBotConversationState::MENU_SELECTION->value,
            $conversation->fresh()->current_state
        );
    }

    public function test_harga_dari_katalog_membuka_pricelist(): void
    {
        $category = $this->makeCategory('Produk');
        $this->makeProduct($category, 'Kue Ijo', [
            ['name' => 'Isi 3', 'price' => 9000],
        ]);
        $conversation = $this->makeConversation($this->surabaya);

        $this->say($conversation, 'PESAN');
        $this->recorder->reset();
        $this->say($conversation, 'HARGA');

        $this->assertTrue($this->recorder->hasText('PRICELIST'));
        $this->assertSame(
            OrderBotConversationState::AWAITING_ORDER_FORM->value,
            $conversation->fresh()->current_state
        );
        $this->assertSame([], $conversation->fresh()->getContext('catalog_ids'));
    }

    public function test_kota_dipilih_sebelum_katalog_dan_tidak_minta_lokasi_awal(): void
    {
        $malang = $this->makeRegion('Malang');
        $category = $this->makeCategory('Produk');
        $this->makeProduct($category, 'Kue Global', [
            ['name' => 'Regular', 'price' => 9000],
        ]);
        $conversation = $this->makeConversation($malang, [], OrderBotConversationState::WELCOME_SENT->value);
        $conversation->update(['region_id' => null]);

        $this->say($conversation, 'PESAN');
        $this->assertTrue($conversation->fresh()->getContext('awaiting_branch'));

        $this->say($conversation, 'Surabaya');

        $fresh = $conversation->fresh();
        $this->assertSame($this->surabaya->id, $fresh->region_id);
        $this->assertSame(
            OrderBotConversationState::AWAITING_ORDER_FORM->value,
            $fresh->current_state
        );
        $this->assertFalse($this->recorder->hasText('Pilih Lokasi Pengiriman'));
        $this->assertTrue($this->recorder->hasText('List Produk — Kue Pandan Asli'));
    }

    public function test_pesan_selama_memilih_kota_tidak_meninggalkan_flag_kota_aktif(): void
    {
        $conversation = $this->makeConversation($this->surabaya, [
            'awaiting_branch' => true,
            'after_city_action' => 'catalog',
        ], OrderBotConversationState::WELCOME_SENT->value);

        $this->say($conversation, 'PESAN');

        $fresh = $conversation->fresh();
        $this->assertNull($fresh->getContext('awaiting_branch'));
        $this->assertNull($fresh->getContext('after_city_action'));
        $this->assertSame(
            OrderBotConversationState::AWAITING_ORDER_FORM->value,
            $fresh->current_state
        );
        $this->assertTrue($this->recorder->hasText('List Produk — Kue Pandan Asli'));
    }

    public function test_teks_bebas_di_state_yang_tidak_mengharapkannya_dibalas_ringkas(): void
    {
        $conversation = $this->makeConversation($this->surabaya);

        $this->say($conversation, 'apa kabar');

        $text = $this->recorder->allText();
        $this->assertStringContainsString('Ketik *MENU* untuk melihat pilihan', $text);
        $this->assertStringContainsString(
            $this->bot->footerFor(OrderBotConversationState::WELCOME_SENT),
            $text
        );
        $this->assertStringNotContainsString('Apa yang bisa kami bantu', $text);
    }

    public function test_setiap_state_aktif_memiliki_jalan_keluar_menu(): void
    {
        foreach (OrderBotConversationState::cases() as $state) {
            if (! $state->isActive()) {
                continue;
            }

            $context = $state === OrderBotConversationState::AWAITING_ORDER_FORM
                ? ['form_step' => 0]
                : [];
            $conversation = $this->makeConversation(
                $this->surabaya,
                $context,
                $state->value,
                '6281000000'.substr(md5($state->value), 0, 4)
            );
            $this->recorder->reset();

            $this->say($conversation, 'MENU');

            $this->assertNotEmpty($this->recorder->sent, "State {$state->value} tidak merespons MENU.");
            $this->assertSame(
                OrderBotConversationState::MENU_SELECTION->value,
                $conversation->fresh()->current_state,
                "State {$state->value} tidak keluar lewat MENU."
            );
        }
    }

    public function test_setiap_state_aktif_memiliki_jalan_keluar_batal(): void
    {
        foreach (OrderBotConversationState::cases() as $state) {
            if (! $state->isActive()) {
                continue;
            }

            $context = $state === OrderBotConversationState::AWAITING_ORDER_FORM
                ? ['form_step' => 0]
                : [];
            $conversation = $this->makeConversation(
                $this->surabaya,
                $context,
                $state->value,
                '6281000001'.substr(md5($state->value), 0, 4)
            );
            $this->recorder->reset();

            $this->say($conversation, 'BATAL');

            $this->assertNotEmpty($this->recorder->sent, "State {$state->value} tidak merespons BATAL.");
            $this->assertSame(
                OrderBotConversationState::MENU_SELECTION->value,
                $conversation->fresh()->current_state,
                "State {$state->value} tidak keluar lewat BATAL."
            );
        }
    }

    public function test_setiap_state_aktif_memiliki_jalan_keluar_ganti_kota(): void
    {
        $this->makeRegion('Malang');

        foreach (OrderBotConversationState::cases() as $state) {
            if (! $state->isActive()) {
                continue;
            }

            $context = $state === OrderBotConversationState::AWAITING_ORDER_FORM
                ? ['form_step' => 0]
                : [];
            $conversation = $this->makeConversation(
                $this->surabaya,
                $context,
                $state->value,
                '6281000002'.substr(md5($state->value), 0, 4)
            );
            $this->recorder->reset();

            $this->say($conversation, 'GANTI KOTA');

            $this->assertNotEmpty($this->recorder->sent, "State {$state->value} tidak merespons GANTI KOTA.");
            $this->assertTrue(
                $conversation->fresh()->getContext('awaiting_branch'),
                "State {$state->value} tidak membuka pilihan kota."
            );
        }
    }
}
