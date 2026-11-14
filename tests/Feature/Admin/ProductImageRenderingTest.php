<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\Region;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductImageRenderingTest extends TestCase
{
    use RefreshDatabase;

    protected Region $region;

    protected User $admin;

    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'admin']);

        $this->region = Region::create(['name' => 'Malang', 'slug' => 'malang', 'is_active' => true]);

        $this->admin = User::create([
            'name' => 'Admin Malang',
            'email' => 'admin.produk@test.com',
            'password' => bcrypt('password'),
            'region_id' => $this->region->id,
        ]);
        $this->admin->assignRole('admin');

        $this->category = Category::create(['name' => 'Kue Basah', 'slug' => 'kue-basah']);
    }

    private function makeProduct(string $name, ?string $imagePath): Product
    {
        return Product::create([
            'category_id' => $this->category->id,
            'region_id' => $this->region->id,
            'name' => $name,
            'description' => 'Deskripsi '.$name,
            'image_path' => $imagePath,
            'is_active' => true,
        ]);
    }

    public function test_index_renders_legacy_asset_with_absolute_url(): void
    {
        $product = $this->makeProduct('Kue Ijo', 'assets/homepage/product/kue-ijo.jpg');

        $response = $this->actingAs($this->admin)->get(route('admin.products.index'));

        $response->assertOk();
        $response->assertSee($product->image_url, false);
        $response->assertSee(rtrim(config('app.url'), '/').'/assets/homepage/product/kue-ijo.jpg', false);
        $response->assertDontSee('/storage/assets/homepage/product', false);
    }

    public function test_index_renders_placeholder_when_photo_missing(): void
    {
        $this->makeProduct('Kue Tanpa Foto', 'assets/homepage/product/tidak-ada.jpg');

        $response = $this->actingAs($this->admin)->get(route('admin.products.index'));

        $response->assertOk();
        $response->assertSee(rtrim(config('app.url'), '/').'/images/placeholder-product.png', false);
        $response->assertDontSee('tidak-ada.jpg', false);
    }

    public function test_show_renders_placeholder_when_photo_missing(): void
    {
        $product = $this->makeProduct('Kue Tanpa Foto', null);

        $response = $this->actingAs($this->admin)->get(route('admin.products.show', $product->id));

        $response->assertOk();
        $response->assertSee($product->image_url, false);
        $response->assertSee('/images/placeholder-product.png', false);
    }
}
