<?php

namespace Tests\Feature\WhatsApp;

use App\Models\Region;
use App\Models\User;
use App\Models\WhatsAppConversation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminChatReplyTest extends TestCase
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
            'current_state' => 'ESCALATED_TO_HUMAN',
            'message_count' => 1,
        ]);
    }

    public function test_admin_can_reply_to_conversation(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid_admin_reply']]], 200),
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.chat.reply', $this->conversation->id), ['message' => 'Halo kak, pesanan sedang diproses.']);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('whatsapp_messages', [
            'conversation_id' => $this->conversation->id,
            'sender_type' => 'admin',
            'message_type' => 'text',
            'content' => 'Halo kak, pesanan sedang diproses.',
            'whatsapp_message_id' => 'wamid_admin_reply',
        ]);

        $this->assertSame(2, $this->conversation->fresh()->message_count);
    }

    public function test_admin_cannot_reply_to_conversation_of_another_region(): void
    {
        $otherRegion = Region::create(['name' => 'Surabaya', 'slug' => 'surabaya']);
        $otherConversation = WhatsAppConversation::create([
            'phone_number' => '6281234567890',
            'region_id' => $otherRegion->id,
            'status' => 'active',
            'current_state' => 'INIT',
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.chat.reply', $otherConversation->id), ['message' => 'Halo'])
            ->assertForbidden();
    }

    public function test_message_is_required(): void
    {
        $this->actingAs($this->admin)
            ->from(route('admin.chat.index'))
            ->post(route('admin.chat.reply', $this->conversation->id), ['message' => ''])
            ->assertSessionHasErrors('message');
    }

    public function test_reply_failure_returns_error_flash(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['error' => ['message' => 'session expired']], 400),
        ]);

        $this->actingAs($this->admin)
            ->from(route('admin.chat.show', $this->conversation->id))
            ->post(route('admin.chat.reply', $this->conversation->id), ['message' => 'Pesan test'])
            ->assertSessionHasErrors('message_send');

        $this->assertDatabaseMissing('whatsapp_messages', [
            'conversation_id' => $this->conversation->id,
            'sender_type' => 'admin',
        ]);
    }
}