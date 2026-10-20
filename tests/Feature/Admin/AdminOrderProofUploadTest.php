<?php

namespace Tests\Feature\Admin;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Region;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminOrderProofUploadTest extends TestCase
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
            'email' => 'admin.proof@test.com',
            'password' => bcrypt('password'),
            'region_id' => $this->region->id,
        ]);
        $this->admin->assignRole('admin');
    }

    protected function makeOrder(Region $region, string $status = 'baru'): Order
    {
        $customer = Customer::create([
            'name' => 'Pembeli',
            'address' => 'Jl. Mawar No 1',
            'phone' => '628111111111',
            'region_id' => $region->id,
        ]);

        return Order::create([
            'customer_id' => $customer->id,
            'phone' => '628111111111',
            'address' => 'Jl. Mawar No 1',
            'payment_method' => 'Transfer',
            'total_amount' => 50000,
            'region_id' => $region->id,
            'status' => $status,
        ]);
    }

    public function test_admin_can_upload_payment_proof(): void
    {
        Storage::fake('public');
        $order = $this->makeOrder($this->region);

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.orders.uploadProof', $order->id), [
                'payment_proof' => UploadedFile::fake()->image('bukti.jpg'),
            ]);

        $response->assertOk();
        $response->assertJsonPath('message', 'Bukti berhasil diunggah.');

        $order->refresh();
        $this->assertNotNull($order->payment_proof);
        Storage::disk('public')->assertExists($order->payment_proof);
    }

    public function test_admin_cannot_upload_to_other_region_order(): void
    {
        Storage::fake('public');
        $order = $this->makeOrder($this->otherRegion);

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.orders.uploadProof', $order->id), [
                'payment_proof' => UploadedFile::fake()->image('bukti.jpg'),
            ]);

        $response->assertNotFound();
        $this->assertNull($order->fresh()->payment_proof);
    }

    public function test_upload_rejects_non_image(): void
    {
        Storage::fake('public');
        $order = $this->makeOrder($this->region);

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.orders.uploadProof', $order->id), [
                'payment_proof' => UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'),
            ]);

        $response->assertStatus(422);
        $this->assertNull($order->fresh()->payment_proof);
    }

    public function test_verify_succeeds_after_admin_upload(): void
    {
        Storage::fake('public');
        $order = $this->makeOrder($this->region, 'menunggu_verifikasi_admin');

        $this->actingAs($this->admin)
            ->postJson(route('admin.orders.uploadProof', $order->id), [
                'payment_proof' => UploadedFile::fake()->image('bukti.jpg'),
            ])->assertOk();

        $this->actingAs($this->admin)
            ->postJson(route('admin.orders.verify', $order->id))
            ->assertOk();

        $this->assertSame('diverifikasi_admin', $order->fresh()->status);
    }
}
