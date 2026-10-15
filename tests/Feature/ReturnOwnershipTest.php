<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Region;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReturnOwnershipTest extends TestCase
{
    use RefreshDatabase;

    protected Region $region;
    protected User $kurirA;
    protected User $kurirB;
    protected Order $orderA;
    protected string $itemKey;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'kurir']);

        $this->region = Region::create(['name' => 'Malang', 'slug' => 'malang']);

        $this->kurirA = User::create([
            'name' => 'Kurir A', 'email' => 'a@test.com',
            'password' => bcrypt('password'), 'region_id' => $this->region->id,
        ]);
        $this->kurirA->assignRole('kurir');

        $this->kurirB = User::create([
            'name' => 'Kurir B', 'email' => 'b@test.com',
            'password' => bcrypt('password'), 'region_id' => $this->region->id,
        ]);
        $this->kurirB->assignRole('kurir');

        $customer = Customer::create([
            'name' => 'Budi', 'address' => 'Jl. Mawar 1', 'phone' => '0812000001',
            'region_id' => $this->region->id, 'added_by_user_id' => $this->kurirA->id,
        ]);

        $this->orderA = Order::create([
            'customer_id' => $customer->id,
            'phone' => $customer->phone,
            'address' => $customer->address,
            'total_amount' => 9000,
            'payment_method' => 'cash',
            'created_by_user_id' => $this->kurirA->id,
            'region_id' => $this->region->id,
            'status' => 'diterima_pembeli',
        ]);
        $category = Category::create(['name' => 'Produk', 'slug' => 'produk']);
        $product = Product::create([
            'category_id' => $category->id, 'region_id' => null,
            'name' => 'Kue Ijo', 'description' => 'd', 'is_active' => true,
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id, 'name' => 'Isi 3', 'price' => 9000, 'is_active' => true,
        ]);
        OrderItem::create([
            'order_id' => $this->orderA->id,
            'product_id' => $product->id, 'product_name' => 'Kue Ijo',
            'variant_id' => $variant->id, 'variant_name' => 'Isi 3',
            'quantity' => 1, 'price' => 9000, 'subtotal' => 9000,
        ]);
        $this->itemKey = $product->id . '-' . $variant->id;
    }

    public function test_kurir_lain_tidak_bisa_retur_order_orang(): void
    {
        $response = $this->actingAs($this->kurirB)->postJson(
            route('kurir.pesanan.requestReturn', $this->orderA->id),
            ['return_quantities' => [$this->itemKey => 1], 'reason' => 'Alasan retur cukup panjang']
        );

        $response->assertStatus(404);
        $this->assertEquals('diterima_pembeli', $this->orderA->fresh()->status);
    }

    public function test_pemilik_bisa_retur(): void
    {
        $response = $this->actingAs($this->kurirA)->postJson(
            route('kurir.pesanan.requestReturn', $this->orderA->id),
            ['return_quantities' => [$this->itemKey => 1], 'reason' => 'Alasan retur cukup panjang']
        );

        $response->assertOk();
        $this->assertEquals('menunggu_retur', $this->orderA->fresh()->status);
    }
}
