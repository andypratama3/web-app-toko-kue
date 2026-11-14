<?php

namespace Tests\Support;

use App\Enums\OrderBotConversationState;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Region;
use App\Models\WhatsAppConversation;
use App\Services\WhatsApp\WhatsappMetaService;
use Illuminate\Support\Str;

/**
 * Dunia minimum untuk test bot: dua kota (Surabaya, Malang), kategori,
 * produk + varian, percakapan, dan rekaman pesan keluar.
 */
trait BuildsBotWorld
{
    protected RecordingWhatsappMetaService $recorder;

    protected function bindRecordingWhatsapp(): RecordingWhatsappMetaService
    {
        config([
            'services.whatsapp.phone_number_id' => config('services.whatsapp.phone_number_id') ?: 'test-phone-number-id',
            'services.whatsapp.access_token' => config('services.whatsapp.access_token') ?: 'test-token',
            'services.whatsapp.graph_version' => config('services.whatsapp.graph_version') ?: 'v21.0',
        ]);

        $this->recorder = new RecordingWhatsappMetaService;

        $this->app->instance(WhatsappMetaService::class, $this->recorder);

        return $this->recorder;
    }

    protected function makeRegion(string $name): Region
    {
        return Region::create([
            'name' => $name,
            'slug' => Str::slug($name),
            'is_active' => true,
        ]);
    }

    protected function makeCategory(string $name): Category
    {
        return Category::create([
            'name' => $name,
            'slug' => Str::slug($name),
        ]);
    }

    /**
     * @param  array<int, array{name: string, price: int}>  $variants
     */
    protected function makeProduct(
        Category $category,
        string $name,
        array $variants = [],
        ?Region $region = null,
        string $tag = '',
        string $description = ''
    ): Product {
        $product = Product::create([
            'category_id' => $category->id,
            'region_id' => $region?->id,
            'is_global' => $region === null,
            'name' => $name,
            'description' => $description !== '' ? $description : $name,
            'tag' => $tag,
            'is_active' => true,
        ]);

        foreach ($variants as $variant) {
            ProductVariant::create([
                'product_id' => $product->id,
                'name' => $variant['name'],
                'price' => $variant['price'],
                'is_active' => true,
            ]);
        }

        return $product;
    }

    protected function makeConversation(
        Region $region,
        array $context = [],
        string $state = OrderBotConversationState::WELCOME_SENT->value,
        string $phone = '6281000000001'
    ): WhatsAppConversation {
        return WhatsAppConversation::create([
            'phone_number' => $phone,
            'region_id' => $region->id,
            'status' => 'active',
            'current_state' => $state,
            'context' => $context,
        ]);
    }
}
