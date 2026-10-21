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

class AdminOrderCrossBranchTest extends TestCase
{
    use RefreshDatabase;

    protected Region $malang;

    protected Region $surabaya;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'admin', 'guard_name' => 'web']);
        Role::create(['name' => 'kurir', 'guard_name' => 'web']);
        Role::create(['name' => 'super_admin', 'guard_name' => 'web']);

        $this->malang = Region::create(['name' => 'Malang', 'slug' => 'malang', 'is_active' => true]);
        $this->surabaya = Region::create(['name' => 'Surabaya', 'slug' => 'surabaya', 'is_active' => true]);

        $this->admin = User::create([
            'name' => 'Admin Surabaya',
            'email' => 'admin.cross@test.com',
            'password' => bcrypt('password'),
            'region_id' => $this->surabaya->id,
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

    public function test_admin_biasa_hanya_lihat_order_cabang_sendiri(): void
    {
        $this->makeOrder($this->malang, 'menunggu_verifikasi_admin');
        $this->makeOrder($this->surabaya, 'menunggu_verifikasi_admin');

        $response = $this->actingAs($this->admin)->get('/admin/orders');

        $response->assertOk();
        $this->assertCount(1, $response->viewData('orders'));
    }

    public function test_super_admin_lihat_order_semua_cabang(): void
    {
        $this->admin->assignRole('super_admin');
        $this->makeOrder($this->malang, 'menunggu_verifikasi_admin');
        $this->makeOrder($this->surabaya, 'menunggu_verifikasi_admin');

        $response = $this->actingAs($this->admin)->get('/admin/orders');

        $response->assertOk();
        $this->assertCount(2, $response->viewData('orders'));
    }

    public function test_tombol_verifikasi_aktif_untuk_super_admin_di_order_cabang_lain(): void
    {
        $this->admin->assignRole('super_admin');
        $order = $this->makeOrder($this->malang, 'menunggu_verifikasi_admin');

        $response = $this->actingAs($this->admin)->get('/admin/orders');

        $response->assertOk();
        $html = $response->getContent();
        $this->assertStringContainsString('data-order-id="'.$order->id.'"', $html);
        // Tombol aktif pakai kelas biru; yang disabled pakai kelas abu-abu.
        $this->assertMatchesRegularExpression(
            '/bg-blue-600[^>]*data-target-modal="verifyOrderModal"[^>]*data-order-id="'.$order->id.'"/s',
            $html
        );
    }

    public function test_tombol_verifikasi_nonaktif_untuk_admin_biasa_di_order_cabang_lain(): void
    {
        $order = $this->makeOrder($this->malang, 'menunggu_verifikasi_admin');

        $response = $this->actingAs($this->admin)->get('/admin/orders');

        $response->assertOk();
        $html = $response->getContent();
        $this->assertStringNotContainsString('data-order-id="'.$order->id.'"', $html);
    }

    public function test_super_admin_bisa_verifikasi_order_cabang_lain(): void
    {
        Storage::fake('public');
        $this->admin->assignRole('super_admin');
        $order = $this->makeOrder($this->malang, 'menunggu_verifikasi_admin');

        $this->actingAs($this->admin)
            ->postJson(route('admin.orders.uploadProof', $order->id), [
                'payment_proof' => UploadedFile::fake()->image('bukti.jpg'),
            ])->assertOk();

        $this->actingAs($this->admin)
            ->postJson(route('admin.orders.verify', $order->id))
            ->assertOk();

        $this->assertSame('diverifikasi_admin', $order->fresh()->status);
    }

    public function test_admin_biasa_tetap_terbatas_di_cabang_sendiri(): void
    {
        $order = $this->makeOrder($this->malang, 'menunggu_verifikasi_admin');

        $this->actingAs($this->admin)
            ->postJson(route('admin.orders.verify', $order->id))
            ->assertNotFound();
    }
}
