<?php

namespace Tests\Feature\WhatsApp;

use App\Enums\OrderBotConversationState;
use App\Models\Region;
use App\Models\User;
use App\Models\WhatsAppConversation;
use App\Services\WhatsApp\ConversationRegionResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ChatConversationActionsTest extends TestCase
{
    use RefreshDatabase;

    protected Region $region;
    protected User $admin;
    protected WhatsAppConversation $conversation;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'admin']);

        $this->region = Region::create(['name' => 'Malang', 'slug' => 'malang']);
        $this->admin = User::create([
            'name' => 'Admin Malang',
            'email' => 'admin.malang@test.com',
            'password' => bcrypt('password'),
            'region_id' => $this->region->id,
        ]);
        $this->admin->assignRole('admin');

        $this->conversation = WhatsAppConversation::create([
            'phone_number' => '6282217160075',
            'profile_name' => 'Andy',
            'region_id' => $this->region->id,
            'status' => 'active',
            'current_state' => OrderBotConversationState::INIT->value,
            'context' => ['product_name' => 'Tumpeng'],
        ]);
    }

    public function test_admin_can_close_conversation(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.chat.close', $this->conversation->id))
            ->assertRedirect();

        $this->assertSame('closed', $this->conversation->fresh()->status);
    }

    public function test_admin_can_escalate_conversation(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.chat.escalate', $this->conversation->id))
            ->assertRedirect();

        $this->assertSame(OrderBotConversationState::ESCALATED_TO_HUMAN->value, $this->conversation->fresh()->current_state);
    }

    public function test_admin_can_resume_conversation_to_bot(): void
    {
        $this->conversation->update(['current_state' => OrderBotConversationState::ESCALATED_TO_HUMAN->value]);

        $this->actingAs($this->admin)
            ->post(route('admin.chat.resume', $this->conversation->id))
            ->assertRedirect();

        $fresh = $this->conversation->fresh();

        $this->assertSame(OrderBotConversationState::INIT->value, $fresh->current_state);
        $this->assertSame('active', $fresh->status);
        $this->assertNull($fresh->context);
    }

    public function test_admin_sees_only_own_branch_by_default(): void
    {
        $otherRegion = Region::create(['name' => 'Surabaya', 'slug' => 'surabaya']);
        $otherConversation = WhatsAppConversation::create([
            'phone_number' => '6281234567890',
            'region_id' => $otherRegion->id,
            'status' => 'active',
            'current_state' => OrderBotConversationState::INIT->value,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.chat.index'))
            ->assertOk()
            ->assertSee($this->conversation->phone_number)
            ->assertDontSee($otherConversation->phone_number);
    }

    public function test_admin_can_view_all_branches_with_all_filter(): void
    {
        $otherRegion = Region::create(['name' => 'Surabaya', 'slug' => 'surabaya']);
        $otherConversation = WhatsAppConversation::create([
            'phone_number' => '6281234567890',
            'region_id' => $otherRegion->id,
            'status' => 'active',
            'current_state' => OrderBotConversationState::INIT->value,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.chat.index', ['region' => 'all']))
            ->assertOk()
            ->assertSee($this->conversation->phone_number)
            ->assertSee($otherConversation->phone_number);
    }

    public function test_admin_can_filter_by_specific_branch(): void
    {
        $otherRegion = Region::create(['name' => 'Surabaya', 'slug' => 'surabaya']);
        $otherConversation = WhatsAppConversation::create([
            'phone_number' => '6281234567890',
            'region_id' => $otherRegion->id,
            'status' => 'active',
            'current_state' => OrderBotConversationState::INIT->value,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.chat.index', ['region' => $otherRegion->id]))
            ->assertOk()
            ->assertSee($otherConversation->phone_number)
            ->assertDontSee($this->conversation->phone_number);
    }

    public function test_admin_can_act_on_conversation_of_other_branch(): void
    {
        $otherRegion = Region::create(['name' => 'Surabaya', 'slug' => 'surabaya']);
        $otherConversation = WhatsAppConversation::create([
            'phone_number' => '6281234567890',
            'region_id' => $otherRegion->id,
            'status' => 'active',
            'current_state' => OrderBotConversationState::INIT->value,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.chat.close', $otherConversation->id))
            ->assertRedirect();

        $this->assertSame('closed', $otherConversation->fresh()->status);
    }

    public function test_admin_can_assign_conversation_to_branch_manually(): void
    {
        $otherRegion = Region::create(['name' => 'Surabaya', 'slug' => 'surabaya']);

        $this->actingAs($this->admin)
            ->patch(route('admin.chat.region', $this->conversation->id), [
                'region_id' => $otherRegion->id,
            ])
            ->assertRedirect();

        $fresh = $this->conversation->fresh();

        $this->assertSame($otherRegion->id, (int) $fresh->region_id);
        $this->assertSame(
            ConversationRegionResolver::SOURCE_MANUAL,
            $fresh->getContext('branch_source')
        );
    }
}
