<?php

namespace Tests\Unit\Support;

use App\Models\Product;
use App\Support\ProductImage;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductImageTest extends TestCase
{
    private const LEGACY_EXISTS = 'assets/homepage/product/kue-ijo.jpg';

    private const LEGACY_MISSING = 'assets/homepage/product/tidak-ada.jpg';

    public function test_placeholder_file_exists_in_public_directory(): void
    {
        $this->assertFileExists(public_path(ProductImage::PLACEHOLDER));
    }

    public function test_exists_rejects_empty_path(): void
    {
        $this->assertFalse(ProductImage::exists(null));
        $this->assertFalse(ProductImage::exists(''));
        $this->assertFalse(ProductImage::exists('   '));
    }

    public function test_exists_detects_legacy_public_asset(): void
    {
        $this->assertTrue(ProductImage::exists(self::LEGACY_EXISTS));
    }

    public function test_exists_rejects_missing_legacy_public_asset(): void
    {
        $this->assertFalse(ProductImage::exists(self::LEGACY_MISSING));
    }

    public function test_exists_accepts_external_url(): void
    {
        $this->assertTrue(ProductImage::exists('https://cdn.example.com/kue.jpg'));
        $this->assertTrue(ProductImage::exists('http://cdn.example.com/kue.jpg'));
    }

    public function test_exists_detects_file_on_public_disk(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/kue-baru.jpg', 'x');

        $this->assertTrue(ProductImage::exists('products/kue-baru.jpg'));
        $this->assertFalse(ProductImage::exists('products/tidak-ada.jpg'));
    }

    public function test_url_returns_placeholder_when_path_missing(): void
    {
        $this->assertSame(ProductImage::placeholderUrl(), ProductImage::url(null));
        $this->assertSame(ProductImage::placeholderUrl(), ProductImage::url(''));
        $this->assertSame(ProductImage::placeholderUrl(), ProductImage::url(self::LEGACY_MISSING));
    }

    public function test_url_builds_absolute_url_for_legacy_public_asset(): void
    {
        $this->assertSame(
            rtrim((string) config('app.url'), '/').'/'.self::LEGACY_EXISTS,
            ProductImage::url(self::LEGACY_EXISTS)
        );
    }

    public function test_url_delegates_to_public_disk_for_uploaded_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/kue-baru.jpg', 'x');

        $this->assertSame(
            Storage::disk('public')->url('products/kue-baru.jpg'),
            ProductImage::url('products/kue-baru.jpg')
        );
    }

    public function test_url_returns_external_url_untouched(): void
    {
        $this->assertSame('https://cdn.example.com/kue.jpg', ProductImage::url('https://cdn.example.com/kue.jpg'));
    }

    public function test_product_image_url_accessor_uses_helper(): void
    {
        $legacy = new Product(['image_path' => self::LEGACY_EXISTS]);
        $missing = new Product(['image_path' => self::LEGACY_MISSING]);
        $empty = new Product;

        $this->assertSame(ProductImage::url(self::LEGACY_EXISTS), $legacy->image_url);
        $this->assertSame(ProductImage::placeholderUrl(), $missing->image_url);
        $this->assertSame(ProductImage::placeholderUrl(), $empty->image_url);
    }
}
