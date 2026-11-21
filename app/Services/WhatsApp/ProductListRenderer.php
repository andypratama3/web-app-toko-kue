<?php

namespace App\Services\WhatsApp;

use App\Models\Category;
use App\Models\Product;
use App\Models\WhatsAppConversation;
use App\Services\Pricing\PriceResolver;

class ProductListRenderer
{
    protected PriceResolver $priceResolver;

    public function __construct(PriceResolver $priceResolver)
    {
        $this->priceResolver = $priceResolver;
    }

    /**
     * Render daftar produk sesuai mode dan simpan catalog_ids ke konteks percakapan.
     *
     * @param  'catalog'|'compact'|'pricelist'|'by_category'  $mode
     * @return string|null 'empty' bila kategori kosong, null bila kategori tidak
     *                     ditemukan, teks render bila berhasil.
     */
    public function render(
        WhatsAppConversation $conversation,
        string $mode,
        ?string $categoryName = null
    ): ?string {
        return match ($mode) {
            'catalog' => $this->catalog($conversation),
            'compact' => $this->compact($conversation),
            'pricelist' => $this->pricelist($conversation),
            'by_category' => $this->byCategory($conversation, $categoryName ?? ''),
            default => $this->catalog($conversation),
        };
    }

    /**
     * Katalog penuh, dikelompokkan per kategori, nomor berurutan lintas kategori.
     * R-04/R-09: judul memuat nama kota. R-12: sub-produk awalan `-`.
     */
    protected function catalog(WhatsAppConversation $conversation): string
    {
        $regionId = $conversation->region_id;
        [$products, $regionName] = $this->orderedProducts($conversation);
        $text = "⭐ *List Produk — Kue Pandan Asli {$regionName}*\n\n";

        $grouped = $products->groupBy(fn ($p) => $p->category->name ?? 'Lainnya');

        $catalogIds = [];
        $num = 0;
        foreach ($grouped as $category => $items) {
            $text .= "*{$category}*\n";
            foreach ($items as $product) {
                $num++;
                $catalogIds[] = $product->id;
                $text .= $this->productLine($num, $product, $regionId);
            }
            $text .= "\n";
        }

        $this->saveCatalogIds($conversation, $catalogIds);

        $text .= 'Ketik *angka* untuk pesan produk itu, atau *MENU* untuk pilih kategori.';

        return $text;
    }

    /**
     * Urutan produk kanonis: per kategori (urutan kemunculan menurut id),
     * lalu id. Dipakai katalog penuh DAN katalog ringkas supaya nomor yang
     * sama menunjuk produk yang sama di kedua daftar.
     *
     * @return array{0: \Illuminate\Database\Eloquent\Collection<int, Product>, 1: string}
     */
    protected function orderedProducts(WhatsAppConversation $conversation): array
    {
        $products = Product::where('is_active', true)
            ->visibleInRegion($conversation->region_id)
            ->with(['category', 'variants' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('id')
            ->get();

        $ordered = $products
            ->groupBy(fn ($p) => $p->category->name ?? 'Lainnya')
            ->flatMap(fn ($items) => $items->values())
            ->values();

        $regionName = $conversation->region?->name
            ?? config('services.whatsapp.default_region', 'kota kami');

        return [$ordered, $regionName];
    }

    /**
     * Katalog ringkas bernomor untuk pemilihan (R-10: "Mau pesan apa?").
     * Nomor mengikuti urutan kanonis yang sama dengan katalog penuh.
     */
    protected function compact(WhatsAppConversation $conversation): string
    {
        $regionId = $conversation->region_id;
        [$products, $regionName] = $this->orderedProducts($conversation);

        $text = "⭐ *List Produk — Kue Pandan Asli {$regionName}*\n\n🛒 *Mau pesan apa?*\n\n";

        $catalogIds = [];
        foreach ($products as $i => $product) {
            $catalogIds[] = $product->id;
            $cheapest = $this->priceResolver->cheapestVariant($product, $regionId);
            $price = $cheapest ? 'Rp '.number_format($this->priceResolver->priceFor($cheapest, $regionId), 0, ',', '.') : 'tanya admin';
            $text .= ($i + 1).". {$product->name} — mulai {$price}\n";
        }

        $this->saveCatalogIds($conversation, $catalogIds);

        $text .= "\nBalas *nomor / nama* produknya (boleh pakai jumlah, mis. *2x Kue Ijo*).";

        return $text;
    }

    /**
     * Pricelist lengkap (semua varian + harga) dikelompokkan per kategori.
     * Tidak menyimpan catalog_ids (bukan daftar bernomor untuk pilihan angka).
     */
    protected function pricelist(WhatsAppConversation $conversation): string
    {
        $regionId = $conversation->region_id;
        $products = Product::where('is_active', true)
            ->visibleInRegion($regionId)
            ->with(['category', 'variants' => fn ($q) => $q->where('is_active', true)->orderBy('price')])
            ->orderBy('id')
            ->get();

        $regionName = $conversation->region?->name ?? config('services.whatsapp.default_region', 'kota kami');
        $text = "💰 *PRICELIST — {$regionName}*\n\n";

        $grouped = $products->groupBy(fn ($p) => $p->category->name ?? 'Lainnya');

        foreach ($grouped as $category => $items) {
            $text .= "*{$category}*\n";
            foreach ($items as $product) {
                $text .= "• {$product->name}\n";
                $text .= $this->descriptionLine($product);
                if ($product->variants->isEmpty()) {
                    $text .= "  Harga menyusul\n";
                }
                foreach ($product->variants->sortBy(fn ($v) => $this->priceResolver->priceFor($v, $regionId)) as $variant) {
                    $text .= '  - '.$variant->name.': Rp '.number_format($this->priceResolver->priceFor($variant, $regionId), 0, ',', '.')."\n";
                }
            }
            $text .= "\n";
        }

        $text .= 'Ketik nama produk untuk lihat foto & detail, atau ketik *PESAN* untuk mulai order.';

        // Pricelist bukan daftar bernomor: kosongkan catalog_ids agar nomor yang
        // diketik setelah HARGA tidak menunjuk produk dari daftar sebelumnya.
        $conversation->setContext('catalog_ids', []);
        $this->clearSelection($conversation);

        return $text;
    }

    /**
     * Produk dalam satu kategori.
     *
     * @return 'empty'|null|string 'empty' bila kategori kosong, null bila kategori
     *                             tidak ditemukan, teks render bila berhasil.
     */
    protected function byCategory(WhatsAppConversation $conversation, string $categoryName)
    {
        $regionId = $conversation->region_id;

        // Nama kategori persis dulu — LIKE '%Kue%' bisa menangkap "Kue Basah"
        // padahal yang dipilih kategori "Kue".
        $category = Category::whereRaw('LOWER(name) = ?', [strtolower(trim($categoryName))])->first()
            ?? Category::where('name', 'like', "%{$categoryName}%")->orderBy('id')->first();

        if (! $category) {
            return null;
        }

        $products = Product::where('category_id', $category->id)
            ->where('is_active', true)
            ->visibleInRegion($regionId)
            ->with(['variants' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('id')
            ->get();

        if ($products->isEmpty()) {
            return 'empty';
        }

        $regionName = $conversation->region?->name ?? config('services.whatsapp.default_region', 'kota kami');
        $text = "📦 *{$category->name} — Kue Pandan Asli {$regionName}*\n\n";

        $catalogIds = [];
        foreach ($products as $index => $product) {
            $num = $index + 1;
            $catalogIds[] = $product->id;
            $text .= "{$num}. *{$product->name}*\n";
            $text .= $this->descriptionLine($product);
            $variants = $product->variants->where('is_active', true)
                ->sortBy(fn ($variant) => $this->priceResolver->priceFor($variant, $regionId))
                ->values();
            if ($variants->isEmpty()) {
                $text .= "  (varian belum tersedia — tanya admin untuk harga)\n";
            }
            foreach ($variants as $variant) {
                $text .= "  - {$variant->name}: Rp ".number_format($this->priceResolver->priceFor($variant, $regionId), 0, ',', '.')."\n";
            }
            $text .= "\n";
        }

        $this->saveCatalogIds($conversation, $catalogIds);

        $text .= 'Ketik *angka* untuk lihat foto & detail, atau *PESAN* untuk mulai order.';

        return $text;
    }

    /**
     * Satu baris produk dalam katalog penuh (R-8: sub-produk awalan `-`).
     */
    protected function productLine(int $num, Product $product, int $regionId): string
    {
        $line = "{$num}. {$product->name}\n";
        $line .= $this->descriptionLine($product);
        $variants = $product->variants->where('is_active', true)
            ->sortBy(fn ($variant) => $this->priceResolver->priceFor($variant, $regionId))
            ->values();
        if ($variants->isEmpty()) {
            $line .= "  - (varian belum tersedia — tanya admin untuk harga)\n";
        }
        foreach ($variants as $variant) {
            $line .= "  - {$variant->name}: Rp ".number_format($this->priceResolver->priceFor($variant, $regionId), 0, ',', '.')."\n";
        }

        return $line;
    }

    /**
     * Deskripsi produk sesuai config 'bot.description_mode' (R-13).
     *
     * 'only_after_berisi' hanya mengambil bagian setelah kata "Berisi:".
     */
    public function descriptionText(Product $product): string
    {
        $mode = config('bot.description_mode', 'full');
        if ($mode === 'off') {
            return '';
        }

        $description = trim((string) ($product->description ?? ''));
        if ($description === '') {
            return '';
        }

        if ($mode === 'only_after_berisi') {
            $description = $this->afterBerisi($description);
        }

        return $description;
    }

    protected function descriptionLine(Product $product): string
    {
        $text = $this->descriptionText($product);

        return $text === '' ? '' : "  {$text}\n";
    }

    protected function afterBerisi(string $description): string
    {
        $keyword = 'berisi:';
        $pos = stripos($description, $keyword);
        if ($pos === false) {
            return '';
        }

        return trim(substr($description, $pos + mb_strlen($keyword)));
    }

    protected function saveCatalogIds(WhatsAppConversation $conversation, array $catalogIds): void
    {
        $conversation->setContext('catalog_ids', $catalogIds);
        $this->clearSelection($conversation);
    }

    protected function clearSelection(WhatsAppConversation $conversation): void
    {
        // Pilihan produk lama tidak boleh menumpangi daftar baru.
        $conversation->forgetContext('selected_product_name');
        $conversation->forgetContext('selected_product_id');
        $conversation->forgetContext('selected_variant_id');
        $conversation->forgetContext('pending_options');
    }
}
