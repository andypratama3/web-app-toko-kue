<?php

namespace Tests\Feature\WhatsApp;

use App\Models\Customer;
use App\Models\DeliveryZone;
use App\Models\Region;
use App\Models\WhatsAppConversation;
use App\Services\WhatsApp\ConversationRegionResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationRegionResolverTest extends TestCase
{
    use RefreshDatabase;

    protected function makeRegion(string $name, string $slug): Region
    {
        return Region::create(['name' => $name, 'slug' => $slug]);
    }

    protected function makeConversation(array $attributes = []): WhatsAppConversation
    {
        return WhatsAppConversation::create(array_merge([
            'phone_number' => '6281234567890',
            'status' => 'active',
            'current_state' => 'INIT',
        ], $attributes));
    }

    public function test_it_returns_null_when_nothing_can_identify_the_branch(): void
    {
        $conversation = $this->makeConversation();

        $this->assertNull(app(ConversationRegionResolver::class)->resolve($conversation));
    }

    public function test_it_uses_the_default_region_when_no_signal_exists(): void
    {
        $malang = $this->makeRegion('Malang', 'malang');

        $conversation = $this->makeConversation(['region_id' => $malang->id]);

        $resolver = app(ConversationRegionResolver::class);

        $this->assertSame($malang->id, $resolver->apply($conversation->refresh()));
        $this->assertTrue($resolver->isDefault($conversation->refresh()));
    }

    public function test_it_derives_branch_from_linked_customer(): void
    {
        $malang = $this->makeRegion('Malang', 'malang');
        $denpasar = $this->makeRegion('Denpasar', 'denpasar');

        $customer = Customer::create([
            'name' => 'Budi',
            'phone' => '6281234567890',
            'address' => 'Jl. Merdeka No. 1',
            'region_id' => $denpasar->id,
        ]);

        $conversation = $this->makeConversation(['customer_id' => $customer->id]);

        $resolver = app(ConversationRegionResolver::class);

        $this->assertSame($denpasar->id, $resolver->apply($conversation->refresh()));
        $this->assertSame(
            ConversationRegionResolver::SOURCE_CUSTOMER,
            $conversation->fresh()->getContext('branch_source')
        );
    }

    public function test_customer_branch_overrides_default_region(): void
    {
        $malang = $this->makeRegion('Malang', 'malang');
        $denpasar = $this->makeRegion('Denpasar', 'denpasar');

        $customer = Customer::create([
            'name' => 'Budi',
            'phone' => '6281234567890',
            'address' => 'Jl. Merdeka No. 1',
            'region_id' => $denpasar->id,
        ]);

        $conversation = $this->makeConversation([
            'customer_id' => $customer->id,
            'region_id' => $malang->id,
        ]);

        $resolver = app(ConversationRegionResolver::class);

        $this->assertSame($denpasar->id, $resolver->apply($conversation->refresh()));
    }

    public function test_it_derives_branch_from_delivery_address_landmark(): void
    {
        $malang = $this->makeRegion('Malang', 'malang');
        $surabaya = $this->makeRegion('Surabaya', 'surabaya');

        DeliveryZone::create([
            'region_id' => $surabaya->id,
            'distance_km' => 3,
            'ongkir' => 10000,
            'area_name' => 'Pusat Kota Surabaya',
            'landmark_keyword' => 'tunjungan, gubeng, kenjeran',
            'is_active' => true,
        ]);
        DeliveryZone::create([
            'region_id' => $malang->id,
            'distance_km' => 3,
            'ongkir' => 10000,
            'area_name' => 'Pusat Kota Malang',
            'landmark_keyword' => 'langgar, dinoyo',
            'is_active' => true,
        ]);

        $conversation = $this->makeConversation([
            'region_id' => $malang->id,
            'context' => ['delivery_address' => 'Jl. GubengAYA No. 10'],
        ]);

        $resolver = app(ConversationRegionResolver::class);

        $this->assertSame($surabaya->id, $resolver->apply($conversation->refresh()));
        $this->assertSame(
            ConversationRegionResolver::SOURCE_ADDRESS,
            $conversation->fresh()->getContext('branch_source')
        );
    }

    public function test_it_reads_address_from_order_form(): void
    {
        $malang = $this->makeRegion('Malang', 'malang');
        $denpasar = $this->makeRegion('Denpasar', 'denpasar');

        DeliveryZone::create([
            'region_id' => $denpasar->id,
            'distance_km' => 3,
            'ongkir' => 10000,
            'area_name' => 'Ubud',
            'landmark_keyword' => 'ubud, gianyar',
            'is_active' => true,
        ]);

        $conversation = $this->makeConversation([
            'region_id' => $malang->id,
            'context' => ['order_form' => ['recipient_address' => 'Jl. Raya Ubud No. 5']],
        ]);

        $resolver = app(ConversationRegionResolver::class);

        $this->assertSame($denpasar->id, $resolver->apply($conversation->refresh()));
    }

    public function test_manual_assignment_is_not_overwritten_by_auto_attribution(): void
    {
        $malang = $this->makeRegion('Malang', 'malang');
        $surabaya = $this->makeRegion('Surabaya', 'surabaya');

        DeliveryZone::create([
            'region_id' => $surabaya->id,
            'distance_km' => 3,
            'ongkir' => 10000,
            'area_name' => 'Pusat Kota Surabaya',
            'landmark_keyword' => 'gubeng, kenjeran',
            'is_active' => true,
        ]);

        $conversation = $this->makeConversation([
            'region_id' => $malang->id,
            'context' => ['delivery_address' => 'Kenjeran GG No. 3'],
        ]);

        $resolver = app(ConversationRegionResolver::class);
        $resolver->assignManually($conversation, $malang->id);

        $this->assertSame($malang->id, $resolver->apply($conversation->refresh()));
        $this->assertSame(
            ConversationRegionResolver::SOURCE_MANUAL,
            $conversation->fresh()->getContext('branch_source')
        );
    }

    public function test_sync_unresolved_only_touches_default_attributions(): void
    {
        $malang = $this->makeRegion('Malang', 'malang');
        $surabaya = $this->makeRegion('Surabaya', 'surabaya');

        DeliveryZone::create([
            'region_id' => $surabaya->id,
            'distance_km' => 3,
            'ongkir' => 10000,
            'area_name' => 'Pusat Kota Surabaya',
            'landmark_keyword' => 'gubeng',
            'is_active' => true,
        ]);

        $auto = $this->makeConversation([
            'phone_number' => '6281111111111',
            'region_id' => $malang->id,
            'context' => ['delivery_address' => 'Gubeng, Surabaya'],
        ]);

        $locked = $this->makeConversation([
            'phone_number' => '6282222222222',
            'region_id' => $malang->id,
            'context' => ['branch_source' => ConversationRegionResolver::SOURCE_MANUAL],
        ]);

        $unresolved = $this->makeConversation([
            'phone_number' => '6283333333333',
            'region_id' => $malang->id,
        ]);

        $updated = app(ConversationRegionResolver::class)->syncUnresolved();

        $this->assertSame(1, $updated);
        $this->assertSame($surabaya->id, (int) $auto->fresh()->region_id);
        $this->assertSame($malang->id, (int) $locked->fresh()->region_id);
        $this->assertSame($malang->id, (int) $unresolved->fresh()->region_id);
    }
}
