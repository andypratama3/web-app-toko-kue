<?php

namespace Tests\Feature\WhatsApp;

use App\Enums\OrderBotConversationState;
use App\Models\AdminNotification;
use App\Models\Region;
use App\Models\WhatsAppConversation;
use App\Services\WhatsApp\OrderBotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Kurir eksternal (Grab/GoSend) yang dipesan admin + pesanan luar jangkauan
 * (>14km) yang tidak lagi dead-end tetapi ditawarkan kurir eksternal.
 */
class OrderBotExternalCourierTest extends TestCase
{
    use RefreshDatabase;

    protected Region $region;

    protected OrderBotService $bot;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.whatsapp.default_region' => 'Denpasar']);

        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid_test']]], 200),
        ]);

        $this->region = Region::create(['name' => 'Denpasar', 'slug' => 'denpasar', 'is_active' => true]);

        $this->bot = app(OrderBotService::class);
    }

    protected function conversation(array $context, string $state, string $phone = '6281200000001'): WhatsAppConversation
    {
        return WhatsAppConversation::create([
            'phone_number' => $phone,
            'region_id' => $this->region->id,
            'status' => 'active',
            'current_state' => $state,
            'context' => $context,
        ]);
    }

    public function test_grab_gosend_minta_lokasi_dulu(): void
    {
        $c = $this->conversation([], OrderBotConversationState::AWAITING_DELIVERY_METHOD->value);

        $this->bot->handleMessage($c, '2');

        $c->refresh();
        $this->assertSame(OrderBotConversationState::AWAITING_LOCATION_OR_ADDRESS->value, $c->current_state);
        $this->assertSame('grab_gosend', $c->getContext('delivery_method'));
    }

    public function test_grab_gosend_dengan_alamat_langsung_ke_slot(): void
    {
        $c = $this->conversation([
            'order_customer' => ['address' => 'Jl. Mawar No 10 Denpasar'],
            'location_done' => true,
        ], OrderBotConversationState::AWAITING_DELIVERY_METHOD->value);

        $this->bot->handleMessage($c, '2');
        $c->refresh();

        $this->assertEquals(OrderBotConversationState::AWAITING_DELIVERY_SLOT->value, $c->current_state);
        $this->assertSame('grab_gosend', $c->getContext('delivery_method'));
        $this->assertSame(0, (int) $c->getContext('ongkir'));
    }

    public function test_grab_gosend_pin_tidak_hitung_ongkir(): void
    {
        $c = $this->conversation(
            ['delivery_method' => 'grab_gosend'],
            OrderBotConversationState::AWAITING_LOCATION_OR_ADDRESS->value
        );

        // Koordinat dekat Denpasar — untuk kurir internal ini ongkir berbayar,
        // tapi Grab/GoSend harus tetap ongkir 0 (dibayar ke driver).
        $this->bot->handleLocationMessage($c, ['latitude' => -8.67, 'longitude' => 115.21]);

        $c->refresh();
        $this->assertSame(OrderBotConversationState::AWAITING_DELIVERY_SLOT->value, $c->current_state);
        $this->assertSame(0, (int) $c->getContext('ongkir'));
        $this->assertFalse((bool) $c->getContext('out_of_zone'));
    }

    public function test_luar_jangkauan_tetap_bisa_lanjut_dan_notif_admin(): void
    {
        $c = $this->conversation([], OrderBotConversationState::AWAITING_LOCATION_OR_ADDRESS->value);

        // ~28km dari Denpasar → di luar jangkauan kurir internal.
        $this->bot->handleLocationMessage($c, ['latitude' => -8.90, 'longitude' => 115.2167]);

        $c->refresh();
        $this->assertSame(OrderBotConversationState::AWAITING_DELIVERY_METHOD->value, $c->current_state);
        $this->assertNotSame(OrderBotConversationState::ESCALATED_TO_HUMAN->value, $c->current_state);
        $this->assertTrue((bool) $c->getContext('out_of_zone'));

        $this->assertGreaterThanOrEqual(1, AdminNotification::where('region_id', $this->region->id)
            ->where('type', 'escalated')->count());
    }

    public function test_luar_jangkauan_kurir_internal_ditolak(): void
    {
        $c = $this->conversation(
            ['out_of_zone' => true, 'ongkir' => 0],
            OrderBotConversationState::AWAITING_DELIVERY_METHOD->value
        );

        $this->bot->handleMessage($c, '3');

        $c->refresh();
        $this->assertSame(OrderBotConversationState::AWAITING_DELIVERY_METHOD->value, $c->current_state);
        $this->assertNotSame('internal_courier', $c->getContext('delivery_method'));
    }

    public function test_luar_jangkauan_pilih_grab_lanjut_ke_slot(): void
    {
        $c = $this->conversation([
            'out_of_zone' => true,
            'order_customer' => ['address' => 'Jl. Jauh No 1 Tabanan'],
        ], OrderBotConversationState::AWAITING_DELIVERY_METHOD->value);

        $this->bot->handleMessage($c, '2');

        $c->refresh();
        $this->assertSame(OrderBotConversationState::AWAITING_DELIVERY_SLOT->value, $c->current_state);
        $this->assertSame('grab_gosend', $c->getContext('delivery_method'));
    }
}
