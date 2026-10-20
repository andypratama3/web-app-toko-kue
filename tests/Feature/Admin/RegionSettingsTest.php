<?php

namespace Tests\Feature\Admin;

use App\Models\Region;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RegionSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected Region $region;

    protected Region $otherRegion;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'admin']);
        Role::create(['name' => 'kurir']);

        $this->region = Region::create(['name' => 'Malang', 'slug' => 'malang', 'is_active' => true]);
        $this->otherRegion = Region::create(['name' => 'Surabaya', 'slug' => 'surabaya', 'is_active' => true]);

        $this->admin = User::create([
            'name' => 'Admin Malang',
            'email' => 'admin.malang@test.com',
            'password' => bcrypt('password'),
            'region_id' => $this->region->id,
        ]);
        $this->admin->assignRole('admin');
    }

    private function fakeMeta(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid_test']]], 200),
        ]);
    }

    public function test_admin_can_view_region_settings_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.regions.index'));

        $response->assertOk();
        $response->assertSee('Pengaturan Cabang');
        $response->assertSee('Malang');
    }

    public function test_update_normalizes_owner_phone(): void
    {
        $response = $this->actingAs($this->admin)
            ->put(route('admin.regions.update', $this->region->id), [
                'owner_phone' => '0812-3456-7890',
            ]);

        $response->assertRedirect(route('admin.regions.index'));
        $response->assertSessionHasNoErrors();

        $this->assertSame('6281234567890', $this->region->fresh()->owner_phone);
    }

    public function test_update_normalizes_plus_prefix(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.regions.update', $this->region->id), [
                'owner_phone' => '+62 812-3456-7890',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('6281234567890', $this->region->fresh()->owner_phone);
    }

    public function test_update_clears_owner_phone_when_empty(): void
    {
        $this->region->update(['owner_phone' => '6281234567890']);

        $this->actingAs($this->admin)
            ->put(route('admin.regions.update', $this->region->id), [
                'owner_phone' => '',
            ])
            ->assertSessionHasNoErrors();

        $this->assertNull($this->region->fresh()->owner_phone);
    }

    public function test_update_rejects_invalid_phone(): void
    {
        $response = $this->actingAs($this->admin)
            ->put(route('admin.regions.update', $this->region->id), [
                'owner_phone' => 'bukan-nomor',
            ]);

        $response->assertSessionHasErrors('owner_phone');
        $this->assertNull($this->region->fresh()->owner_phone);
    }

    public function test_admin_cannot_update_another_region(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.regions.update', $this->otherRegion->id), [
                'owner_phone' => '081234567890',
            ])
            ->assertForbidden();

        $this->assertNull($this->otherRegion->fresh()->owner_phone);
    }

    public function test_send_test_uses_text_when_no_template_configured(): void
    {
        config(['services.whatsapp.owner_notify_template' => null]);
        $this->region->update(['owner_phone' => '6281234567890']);
        $this->fakeMeta();

        $this->actingAs($this->admin)
            ->post(route('admin.regions.sendTest', $this->region->id))
            ->assertRedirect();

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'graph.facebook.com')
                && $request['type'] === 'text'
                && $request['to'] === '6281234567890';
        });
    }

    public function test_send_test_uses_template_when_configured(): void
    {
        config(['services.whatsapp.owner_notify_template' => 'notif_owner']);
        $this->region->update(['owner_phone' => '6281234567890']);
        $this->fakeMeta();

        $this->actingAs($this->admin)
            ->post(route('admin.regions.sendTest', $this->region->id))
            ->assertRedirect();

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'graph.facebook.com')
                && $request['type'] === 'template'
                && $request['template']['name'] === 'notif_owner'
                && $request['to'] === '6281234567890';
        });
    }

    public function test_send_test_without_owner_phone_shows_error(): void
    {
        $this->region->update(['owner_phone' => null]);

        $this->actingAs($this->admin)
            ->post(route('admin.regions.sendTest', $this->region->id))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_kurir_cannot_access_region_settings(): void
    {
        $kurir = User::create([
            'name' => 'Kurir Malang',
            'email' => 'kurir.malang@test.com',
            'password' => bcrypt('password'),
            'region_id' => $this->region->id,
        ]);
        $kurir->assignRole('kurir');

        $this->actingAs($kurir)
            ->get(route('admin.regions.index'))
            ->assertForbidden();
    }
}
