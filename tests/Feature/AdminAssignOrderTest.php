<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Region;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminAssignOrderTest extends TestCase
{
    use RefreshDatabase;

    protected Region $region;
    protected User $admin;
    protected User $kurir;
    protected Order $botOrder;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'admin']);
        Role::create(['name' => 'kurir']);

        $this->region = Region::create(['name' => 'Surabaya', 'slug' => 'surabaya']);

        $this->admin = User::create([
            'name' => 'Admin', 'email' => 'admin@test.com',
            'password' => bcrypt('password'), 'region_id' => $this->region->id,
        ]);
        $this->admin->assignRole('admin');

        $this->kurir = User::create([
            'name' => 'Kurir', 'email' => 'kurir@test.com',
            'password' => bcrypt('password'), 'region_id' => $this->region->id,
        ]);
        $this->kurir->assignRole('kurir');

        $customer = Customer::create([
            'name' => 'WA Buyer', 'address' => 'Jl. X', 'phone' => '0812000001',
            'region_id' => $this->region->id,
        ]);

        $this->botOrder = Order::create([
            'customer_id' => $customer->id,
            'phone' => $customer->phone,
            'address' => $customer->address,
            'total_amount' => 40000,
            'payment_method' => 'qris',
            'created_by_user_id' => null,
            'region_id' => $this->region->id,
            'status' => 'baru',
            'channel' => 'whatsapp',
        ]);
    }

    public function test_admin_bisa_tugaskan_order_bot_ke_kurir(): void
    {
        $response = $this->actingAs($this->admin)->postJson(
            route('admin.orders.assign', $this->botOrder->id),
            ['courier_id' => $this->kurir->id]
        );

        $response->assertOk();
        $fresh = $this->botOrder->fresh();
        $this->assertEquals($this->kurir->id, $fresh->created_by_user_id);
        $this->assertEquals('dikemas', $fresh->status);
    }

    public function test_tugaskan_kurir_luar_region_ditolak(): void
    {
        $otherRegion = Region::create(['name' => 'Malang', 'slug' => 'malang']);
        $outsider = User::create([
            'name' => 'Luar', 'email' => 'luar@test.com',
            'password' => bcrypt('password'), 'region_id' => $otherRegion->id,
        ]);
        $outsider->assignRole('kurir');

        $response = $this->actingAs($this->admin)->postJson(
            route('admin.orders.assign', $this->botOrder->id),
            ['courier_id' => $outsider->id]
        );

        $response->assertStatus(422);
        $this->assertNull($this->botOrder->fresh()->created_by_user_id);
    }

    public function test_order_sudah_bertuan_tidak_bisa_ditugaskan(): void
    {
        $this->botOrder->update(['created_by_user_id' => $this->kurir->id, 'status' => 'diambil']);

        $response = $this->actingAs($this->admin)->postJson(
            route('admin.orders.assign', $this->botOrder->id),
            ['courier_id' => $this->kurir->id]
        );

        $response->assertStatus(404);
    }
}
