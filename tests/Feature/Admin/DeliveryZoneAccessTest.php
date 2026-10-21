<?php

namespace Tests\Feature\Admin;

use App\Models\DeliveryZone;
use App\Models\Region;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DeliveryZoneAccessTest extends TestCase
{
    use RefreshDatabase;

    protected Region $malang;

    protected Region $surabaya;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'admin', 'guard_name' => 'web']);
        Role::create(['name' => 'super_admin', 'guard_name' => 'web']);

        $this->malang = Region::create(['name' => 'Malang', 'slug' => 'malang', 'is_active' => true]);
        $this->surabaya = Region::create(['name' => 'Surabaya', 'slug' => 'surabaya', 'is_active' => true]);

        $this->admin = User::create([
            'name' => 'Admin Malang',
            'email' => 'admin.zone@test.com',
            'password' => bcrypt('password'),
            'region_id' => $this->malang->id,
        ]);
        $this->admin->assignRole('admin');

        DeliveryZone::create([
            'region_id' => $this->malang->id,
            'area_name' => 'Malang Selatan',
            'distance_km' => 5,
            'ongkir' => 10000,
            'is_active' => true,
        ]);
        DeliveryZone::create([
            'region_id' => $this->surabaya->id,
            'area_name' => 'Surabaya Barat',
            'distance_km' => 6,
            'ongkir' => 12000,
            'is_active' => true,
        ]);
    }

    public function test_admin_biasa_hanya_lihat_zona_cabang_sendiri(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/delivery-zones');

        $response->assertOk();
        $response->assertSee('Malang Selatan');
        $response->assertDontSee('Surabaya Barat');
    }

    public function test_super_admin_lihat_zona_semua_cabang(): void
    {
        $this->admin->assignRole('super_admin');

        $response = $this->actingAs($this->admin)->get('/admin/delivery-zones');

        $response->assertOk();
        $response->assertSee('Malang Selatan');
        $response->assertSee('Surabaya Barat');
    }

    public function test_admin_biasa_tidak_bisa_buat_zona_cabang_lain(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/delivery-zones', [
            'region_id' => $this->surabaya->id,
            'area_name' => 'Gudang Piere',
            'distance_km' => 7,
            'ongkir' => 13000,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('delivery_zones', ['area_name' => 'Gudang Piere']);
    }

    public function test_super_admin_bisa_buat_zona_cabang_lain(): void
    {
        $this->admin->assignRole('super_admin');

        $response = $this->actingAs($this->admin)->post('/admin/delivery-zones', [
            'region_id' => $this->surabaya->id,
            'area_name' => 'Gudang Piere',
            'distance_km' => 7,
            'ongkir' => 13000,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('delivery_zones', ['area_name' => 'Gudang Piere', 'region_id' => $this->surabaya->id]);
    }
}
