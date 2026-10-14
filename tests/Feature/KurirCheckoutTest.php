<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Region;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class KurirCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected Region $region;
    protected User $kurir;
    protected Customer $customer;
    protected Product $product;
    protected ProductVariant $variantKecil;
    protected ProductVariant $variantBesar;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'kurir']);

        $this->region = Region::create(['name' => 'Malang', 'slug' => 'malang']);

        $this->kurir = User::create([
            'name' => 'Kurir',
            'email' => 'kurir@test.com',
            'password' => bcrypt('password'),
            'region_id' => $this->region->id,
        ]);
        $this->kurir->assignRole('kurir');

        $this->customer = Customer::create([
            'name' => 'Budi',
            'address' => 'Jl. Mawar 1',
            'phone' => '0812000001',
            'region_id' => $this->region->id,
            'added_by_user_id' => $this->kurir->id,
        ]);

        $category = Category::create(['name' => 'Produk', 'slug' => 'produk']);
        $this->product = Product::create([
            'category_id' => $category->id,
            'region_id' => null,
            'name' => 'Kue Ijo',
            'description' => 'desc',
            'is_active' => true,
        ]);
        $this->variantKecil = ProductVariant::create([
            'product_id' => $this->product->id, 'name' => 'Isi 3', 'price' => 9000, 'is_active' => true,
        ]);
        $this->variantBesar = ProductVariant::create([
            'product_id' => $this->product->id, 'name' => 'Isi 12', 'price' => 40000, 'is_active' => true,
        ]);
    }

    protected function checkoutPayload(array $products, array $overrides = []): array
    {
        return array_merge([
            'customer_id' => $this->customer->id,
            'phone' => $this->customer->phone,
            'address' => $this->customer->address,
            'payment_method' => 'COD',
            'products' => json_encode($products),
        ], $overrides);
    }

    protected function item(int $qty, int $price, ?int $variantId = null): array
    {
        return [
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'variant_id' => $variantId ?? $this->variantKecil->id,
            'variant_name' => 'Isi 3',
            'quantity' => $qty,
            'price' => $price,
        ];
    }

    public function test_harga_dimanipulasi_klien_diabaikan_ditagih_harga_db(): void
    {
        $response = $this->actingAs($this->kurir)->postJson(
            route('kurir.orders.checkout'),
            $this->checkoutPayload([$this->item(2, 1000)])
        );

        $response->assertOk();
        $order = Order::first();
        $this->assertEquals(18000, $order->total_amount);
        $this->assertEquals(9000, $order->items->first()->price);
    }

    public function test_varian_besar_ditagih_sesuai_harga_db(): void
    {
        $response = $this->actingAs($this->kurir)->postJson(
            route('kurir.orders.checkout'),
            $this->checkoutPayload([$this->item(1, 1, $this->variantBesar->id)])
        );

        $response->assertOk();
        $this->assertEquals(40000, Order::first()->total_amount);
    }

    public function test_keranjang_kosong_ditolak(): void
    {
        $response = $this->actingAs($this->kurir)->postJson(
            route('kurir.orders.checkout'),
            $this->checkoutPayload([])
        );

        $response->assertStatus(422);
        $this->assertEquals(0, Order::count());
    }

    public function test_qty_nol_ditolak(): void
    {
        $response = $this->actingAs($this->kurir)->postJson(
            route('kurir.orders.checkout'),
            $this->checkoutPayload([$this->item(0, 9000)])
        );

        $response->assertStatus(422);
        $this->assertEquals(0, Order::count());
    }

    public function test_varian_tidak_valid_ditolak(): void
    {
        $response = $this->actingAs($this->kurir)->postJson(
            route('kurir.orders.checkout'),
            $this->checkoutPayload([$this->item(1, 9000, 999999)])
        );

        $response->assertStatus(422);
        $this->assertEquals(0, Order::count());
    }

    public function test_tanpa_varian_ditolak(): void
    {
        $item = $this->item(1, 9000);
        unset($item['variant_id']);

        $response = $this->actingAs($this->kurir)->postJson(
            route('kurir.orders.checkout'),
            $this->checkoutPayload([$item])
        );

        $response->assertStatus(422);
        $this->assertEquals(0, Order::count());
    }

    public function test_customer_luar_region_ditolak(): void
    {
        $otherRegion = Region::create(['name' => 'Denpasar', 'slug' => 'denpasar']);
        $outsider = Customer::create([
            'name' => 'Luar', 'address' => 'Jl. X', 'phone' => '0812000002',
            'region_id' => $otherRegion->id,
        ]);

        $response = $this->actingAs($this->kurir)->postJson(
            route('kurir.orders.checkout'),
            $this->checkoutPayload([$this->item(1, 9000)], ['customer_id' => $outsider->id])
        );

        $response->assertStatus(422);
        $this->assertEquals(0, Order::count());
    }

    public function test_invoice_unik_untuk_checkout_berurutan(): void
    {
        $this->actingAs($this->kurir)->postJson(
            route('kurir.orders.checkout'), $this->checkoutPayload([$this->item(1, 9000)])
        )->assertOk();
        $this->actingAs($this->kurir)->postJson(
            route('kurir.orders.checkout'), $this->checkoutPayload([$this->item(1, 9000)])
        )->assertOk();

        $this->assertEquals(2, Order::distinct('invoice_number')->count('invoice_number'));
    }
}
