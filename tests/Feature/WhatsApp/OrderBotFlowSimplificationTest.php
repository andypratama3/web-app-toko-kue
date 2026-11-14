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
}
