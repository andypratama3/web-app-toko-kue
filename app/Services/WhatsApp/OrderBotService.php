<?php

namespace App\Services\WhatsApp;

use App\Enums\OrderBotConversationState;
use App\Models\Customer;
use App\Models\CustomerCategory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Region;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use App\Support\Phone;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderBotService
{
    protected WhatsappMetaService $metaService;
    protected DeliveryZoneService $deliveryZoneService;
    protected AdminNotificationRouterService $notificationRouter;

    public function __construct(
        WhatsappMetaService $metaService,
        DeliveryZoneService $deliveryZoneService,
        AdminNotificationRouterService $notificationRouter
    ) {
        $this->metaService = $metaService;
        $this->deliveryZoneService = $deliveryZoneService;
        $this->notificationRouter = $notificationRouter;
    }

    public function handleMessage(WhatsAppConversation $conversation, string $messageText, ?array $messageData = null): void
    {
        $state = OrderBotConversationState::from($conversation->current_state);

        Log::channel('whatsapp')->info('🤖 Processing message', [
            'phone' => $conversation->phone_number,
            'state' => $state->value,
            'message' => substr($messageText, 0, 100),
        ]);

        match ($state) {
            OrderBotConversationState::INIT => $this->handleInit($conversation, $messageText),
            OrderBotConversationState::WELCOME_SENT => $this->handleAfterWelcome($conversation, $messageText),
            OrderBotConversationState::MENU_SELECTION => $this->handleMenuSelection($conversation, $messageText),
            OrderBotConversationState::PRODUCT_BROWSING => $this->handleProductBrowsing($conversation, $messageText),
            OrderBotConversationState::AWAITING_ORDER_FORM => $this->handleOrderForm($conversation, $messageText),
            OrderBotConversationState::AWAITING_DELIVERY_METHOD => $this->handleDeliveryMethod($conversation, $messageText),
            OrderBotConversationState::AWAITING_LOCATION_OR_ADDRESS => $this->handleLocationOrAddress($conversation, $messageText, $messageData),
            OrderBotConversationState::AWAITING_DELIVERY_SLOT => $this->handleDeliverySlot($conversation, $messageText),
            OrderBotConversationState::ORDER_SUMMARY => $this->handleOrderSummary($conversation, $messageText),
            OrderBotConversationState::AWAITING_PAYMENT_PROOF => $this->handlePaymentProof($conversation, $messageText, $messageData),
            OrderBotConversationState::ORDER_CONFIRMED, OrderBotConversationState::CLOSED, OrderBotConversationState::ESCALATED_TO_HUMAN => $this->handleClosedConversation($conversation, $messageText),
        };
    }

    public function handleLocationMessage(WhatsAppConversation $conversation, array $locationData): void
    {
        $state = OrderBotConversationState::from($conversation->current_state);

        if ($state === OrderBotConversationState::AWAITING_LOCATION_OR_ADDRESS) {
            $lat = $locationData['latitude'] ?? null;
            $lng = $locationData['longitude'] ?? null;

            if ($lat && $lng) {
                $distance = $this->deliveryZoneService->estimateDistance($lat, $lng, $conversation->region_id);
                $result = $this->deliveryZoneService->calculateOngkir($distance, $conversation->region_id);

                if ($result['needs_escalation']) {
                    $conversation->update(['current_state' => OrderBotConversationState::ESCALATED_TO_HUMAN->value]);
                    $this->metaService->sendText($conversation->phone_number,
                        "Mohon maaf, jarak pengiriman Anda sekitar {$distance}km dari toko kami.\n" .
                        "Untuk jarak di atas 14km, silakan hubungi admin kami untuk detail pengiriman.\n\n" .
                        "WA Admin: hubungi admin terdekat di cabang {$conversation->region->name}"
                    );
                    return;
                }

                $conversation->setContext('distance_km', $distance);
                $conversation->setContext('ongkir', $result['ongkir']);
                $conversation->setContext('delivery_address', "Lokasi GPS ({$lat}, {$lng})");
                $this->advanceState($conversation, OrderBotConversationState::AWAITING_DELIVERY_SLOT);
                $this->sendSlotOptions($conversation);
            }
        } elseif ($state === OrderBotConversationState::AWAITING_DELIVERY_METHOD) {
            $this->handleDeliveryMethod($conversation, '3');
        }
    }

    protected function handleInit(WhatsAppConversation $conversation, string $text): void
    {
        $this->sendWelcomeMessage($conversation);
        $this->advanceState($conversation, OrderBotConversationState::WELCOME_SENT);
    }

    protected function handleAfterWelcome(WhatsAppConversation $conversation, string $text): void
    {
        $lower = strtolower(trim($text));

        if ($this->matchesIntent($lower, ['mau pesan', 'pesan', 'order', 'ordering', 'mau order'])) {
            $this->sendProductCategories($conversation);
            $this->advanceState($conversation, OrderBotConversationState::MENU_SELECTION);
        } elseif ($this->matchesIntent($lower, ['liat produk', 'lihat produk', 'produk', 'catalog', 'katalog'])) {
            $this->sendProductCatalog($conversation);
            $this->advanceState($conversation, OrderBotConversationState::PRODUCT_BROWSING);
        } elseif ($this->matchesIntent($lower, ['halo', 'hai', 'hi', 'hello', 'salam', 'assalam'])) {
            $this->sendWelcomeMessage($conversation);
        } else {
            $this->sendHelpMessage($conversation);
        }
    }

    protected function handleMenuSelection(WhatsAppConversation $conversation, string $text): void
    {
        $lower = strtolower(trim($text));

        if ($this->matchesIntent($lower, ['tumpeng'])) {
            $this->sendProductsByCategory($conversation, 'Tumpeng');
            $this->advanceState($conversation, OrderBotConversationState::PRODUCT_BROWSING);
            $conversation->setContext('selected_category', 'Tumpeng');
        } elseif ($this->matchesIntent($lower, ['hampers'])) {
            $this->sendProductsByCategory($conversation, 'Hampers');
            $this->advanceState($conversation, OrderBotConversationState::PRODUCT_BROWSING);
            $conversation->setContext('selected_category', 'Hampers');
        } elseif ($this->matchesIntent($lower, ['ala carte', 'ala-carte', 'produk', 'kue', 'carte'])) {
            $this->sendProductsByCategory($conversation, 'Produk');
            $this->advanceState($conversation, OrderBotConversationState::PRODUCT_BROWSING);
            $conversation->setContext('selected_category', 'Produk');
        } else {
            $this->sendProductCategories($conversation);
        }
    }

    protected function handleProductBrowsing(WhatsAppConversation $conversation, string $text): void
    {
        $lower = strtolower(trim($text));

        if ($this->matchesIntent($lower, ['pesan', 'order', 'pilih', 'ambil', 'mau'])) {
            $this->askOrderForm($conversation);
            $this->advanceState($conversation, OrderBotConversationState::AWAITING_ORDER_FORM);
        } elseif ($this->matchesIntent($lower, ['kembali', 'back', 'menu'])) {
            $this->sendProductCategories($conversation);
            $this->advanceState($conversation, OrderBotConversationState::MENU_SELECTION);
        } else {
            $product = $this->findProductByKeyword($conversation, $lower);
            if ($product) {
                $this->sendProductDetail($conversation, $product);
            } else {
                $this->metaService->sendText($conversation->phone_number,
                    "Produk tidak ditemukan. Ketik 'pesan' untuk mulai order, atau 'menu' untuk lihat kategori."
                );
            }
        }
    }

    protected function handleOrderForm(WhatsAppConversation $conversation, string $text): void
    {
        $context = $conversation->context ?? [];
        $formStep = $context['form_step'] ?? 0;
        $formData = $context['order_form'] ?? [];

        match ($formStep) {
            0 => $this->processOrderFormStep($conversation, $formData, 'product_name', $text),
            1 => $this->processOrderFormStep($conversation, $formData, 'recipient_name', $text),
            2 => $this->processOrderFormStep($conversation, $formData, 'recipient_address', $text),
            3 => $this->processOrderFormStep($conversation, $formData, 'delivery_date', $text),
            4 => $this->processOrderFormStep($conversation, $formData, 'delivery_time', $text),
            default => $this->askDeliveryMethod($conversation),
        };
    }

    protected function processOrderFormStep(WhatsAppConversation $conversation, array $formData, string $field, string $value): void
    {
        $formData[$field] = trim($value);
        $conversation->setContext('order_form', $formData);
        $conversation->setContext('form_step', array_search($field, ['product_name', 'recipient_name', 'recipient_address', 'delivery_date', 'delivery_time']) + 1);

        match ($field) {
            'product_name' => $this->metaService->sendText($conversation->phone_number, "✅ Produk: {$value}\n\nSiapa nama penerima?"),
            'recipient_name' => $this->metaService->sendText($conversation->phone_number, "✅ Penerima: {$value}\n\nAlamat pengiriman lengkap?"),
            'recipient_address' => $this->metaService->sendText($conversation->phone_number, "✅ Alamat: {$value}\n\nTanggal kirim (contoh: 10 September 2026)?"),
            'delivery_date' => $this->metaService->sendText($conversation->phone_number, "✅ Tanggal: {$value}\n\nJam tiba yang diinginkan? (contoh: 10:00)"),
            'delivery_time' => $this->askDeliveryMethod($conversation),
        };
    }

    protected function handleDeliveryMethod(WhatsAppConversation $conversation, string $text): void
    {
        $choice = trim($text);

        match ($choice) {
            '1' => $this->processSelfPickup($conversation),
            '2' => $this->processGrabGoSend($conversation),
            '3' => $this->processInternalCourier($conversation),
            default => $this->sendDeliveryMethodOptions($conversation),
        };
    }

    protected function processSelfPickup(WhatsAppConversation $conversation): void
    {
        $conversation->setContext('delivery_method', 'self_pickup');
        $conversation->setContext('ongkir', 0);
        $this->advanceState($conversation, OrderBotConversationState::AWAITING_DELIVERY_SLOT);
        $this->sendSlotOptions($conversation);
    }

    protected function processGrabGoSend(WhatsAppConversation $conversation): void
    {
        $conversation->setContext('delivery_method', 'grab_gosend');
        $conversation->setContext('ongkir', 0);
        $this->advanceState($conversation, OrderBotConversationState::AWAITING_DELIVERY_SLOT);

        $this->metaService->sendText($conversation->phone_number,
            "📦 *Grab/GoSend*\n\n" .
            "Biaya Grab/GoSend ditanggung langsung ke driver (estimasi Rp100.000–150.000 untuk zona 10–15km).\n" .
            "Silakan pesan driver sendiri setelah pesanan dikonfirmasi.\n\n" .
            "Pilih slot waktu pengiriman:"
        );
        $this->sendSlotOptions($conversation);
    }

    protected function processInternalCourier(WhatsAppConversation $conversation): void
    {
        $conversation->setContext('delivery_method', 'internal_courier');
        $this->advanceState($conversation, OrderBotConversationState::AWAITING_LOCATION_OR_ADDRESS);

        $this->metaService->sendLocationRequest($conversation->phone_number,
            "📍 *Kurir Internal*\n\n" .
            "Silakan kirim lokasi pengiriman Anda (pin GPS), atau ketik alamat lengkap."
        );
    }

    protected function handleLocationOrAddress(WhatsAppConversation $conversation, string $text, ?array $messageData): void
    {
        if ($messageData && isset($messageData['latitude'])) {
            $this->handleLocationMessage($conversation, $messageData);
            return;
        }

        $address = trim($text);
        $conversation->setContext('delivery_address', $address);

        $distance = $this->deliveryZoneService->estimateDistanceByAddress($address, $conversation->region_id);
        $result = $this->deliveryZoneService->calculateOngkir($distance, $conversation->region_id);

        if ($result['needs_escalation']) {
            $conversation->update(['current_state' => OrderBotConversationState::ESCALATED_TO_HUMAN->value]);
            $this->metaService->sendText($conversation->phone_number,
                "Mohon maaf, jarak pengiriman Anda diperkirakan sekitar {$distance}km.\n" .
                "Untuk jarak di atas 14km, silakan hubungi admin kami.\n\n" .
                "WA Admin: hubungi admin terdekat di cabang {$conversation->region->name}"
            );
            return;
        }

        $conversation->setContext('distance_km', $distance);
        $conversation->setContext('ongkir', $result['ongkir']);
        $this->advanceState($conversation, OrderBotConversationState::AWAITING_DELIVERY_SLOT);
        $this->sendSlotOptions($conversation);
    }

    protected function handleDeliverySlot(WhatsAppConversation $conversation, string $text): void
    {
        $slot = trim($text);
        $validSlots = ['1', '2', '3', '4'];

        if (!in_array($slot, $validSlots)) {
            $this->sendSlotOptions($conversation);
            return;
        }

        $slots = [
            '1' => '09:00-11:00',
            '2' => '11:00-13:00',
            '3' => '13:00-15:00',
            '4' => '15:00-17:00',
        ];

        $conversation->setContext('delivery_slot', $slots[$slot]);
        $this->advanceState($conversation, OrderBotConversationState::ORDER_SUMMARY);
        $this->sendOrderSummary($conversation);
    }

    protected function handleOrderSummary(WhatsAppConversation $conversation, string $text): void
    {
        $lower = strtolower(trim($text));

        if ($this->matchesIntent($lower, ['fix', 'oke', 'ok', 'ya', 'konfirmasi', 'confirm', 'lanjut'])) {
            $this->confirmOrder($conversation);
        } elseif ($this->matchesIntent($lower, ['batal', 'cancel', 'ubah'])) {
            $conversation->clearContext();
            $this->advanceState($conversation, OrderBotConversationState::MENU_SELECTION);
            $this->sendProductCategories($conversation);
        } else {
            $this->metaService->sendText($conversation->phone_number,
                "Ketik *FIX* untuk konfirmasi pesanan, atau *BATAL* untuk membatalkan."
            );
        }
    }

    protected function handlePaymentProof(WhatsAppConversation $conversation, string $text, ?array $messageData): void
    {
        if (isset($messageData['media_id'])) {
            $mediaPath = $messageData['media_path'] ?? $this->metaService->downloadMedia($messageData['media_id']);

            if ($mediaPath) {
                $orderId = $conversation->getContext('confirmed_order_id');
                if ($orderId) {
                    Order::where('id', $orderId)->update(['payment_proof' => $mediaPath]);
                }

                $this->metaService->sendText($conversation->phone_number,
                    "✅ *Bukti Pembayaran Diterima*\n\n" .
                    "Bukti pembayaran Anda sudah kami terima dan akan diverifikasi oleh admin.\n\n" .
                    "Pesanan Anda akan segera diproses. Terima kasih! 🙏"
                );

                $this->advanceState($conversation, OrderBotConversationState::ORDER_CONFIRMED);
            } else {
                $this->metaService->sendText($conversation->phone_number,
                    "Mohon maaf, gagal menerima gambar. Silakan kirim ulang bukti pembayaran."
                );
            }
        } else {
            $lower = strtolower(trim($text));
            if ($this->matchesIntent($lower, ['sudah transfer', 'transfer', 'bukti', 'bayar'])) {
                $this->metaService->sendText($conversation->phone_number,
                    "Silakan kirim *gambar* bukti transfer/pembayaran."
                );
            } elseif ($this->matchesIntent($lower, ['skip', 'lewati', 'nanti'])) {
                $this->metaService->sendText($conversation->phone_number,
                    "✅ Pesanan Anda sudah tersimpan.\n" .
                    "Silakan kirim bukti pembayaran kapan saja.\n\n" .
                    "Terima kasih! 🙏"
                );
                $this->advanceState($conversation, OrderBotConversationState::ORDER_CONFIRMED);
            } else {
                $this->metaService->sendText($conversation->phone_number,
                    "Silakan kirim gambar bukti pembayaran, atau ketik *SKIP* untuk melanjutkan."
                );
            }
        }
    }

    protected function handleClosedConversation(WhatsAppConversation $conversation, string $text): void
    {
        $this->metaService->sendText($conversation->phone_number,
            "Halo! 👋\n\n" .
            "Ada yang bisa kami bantu? Ketik *PESAN* untuk membuat pesanan baru."
        );
        $this->advanceState($conversation, OrderBotConversationState::WELCOME_SENT);
    }

    // ========== MESSAGE BUILDERS ==========

    protected function sendWelcomeMessage(WhatsAppConversation $conversation): void
    {
        $regionName = $conversation->region->name ?? config('services.whatsapp.default_region');

        $this->metaService->sendText($conversation->phone_number,
            "Selamat datang di *Kue Pandan Asli* 🍃\n" .
            "Cabang {$regionName}\n\n" .
            "Kami menjual kue tradisional Indonesia dengan rasa pandan alami.\n" .
            "Tanpa toko offline — hanya pesan online melalui WhatsApp ini.\n\n" .
            "📍 Lokasi: {$regionName}\n\n" .
            "Ketik *PESAN* untuk mulai order, atau ketik *PRODUK* untuk melihat katalog."
        );
    }

    protected function sendProductCategories(WhatsAppConversation $conversation): void
    {
        $sections = [
            [
                'title' => 'Pilih Kategori',
                'rows' => [
                    ['id' => 'cat_tumpeng', 'title' => 'Tumpeng', 'description' => 'Tumpeng Mini & Besar untuk acara spesial'],
                    ['id' => 'cat_hampers', 'title' => 'Hampers', 'description' => 'Paket hampers hadiah istimewa'],
                    ['id' => 'cat_alacarte', 'title' => 'Ala Carte', 'description' => 'Kue individual sesuai selera'],
                ],
            ],
        ];

        $this->metaService->sendListMessage(
            $conversation->phone_number,
            "🛒 *Pilih Kategori Produk*\n\nSilakan pilih kategori yang ingin Anda lihat:",
            $sections,
            "Ketik nama kategori jika list tidak muncul"
        );
    }

    protected function sendProductCatalog(WhatsAppConversation $conversation): void
    {
        $regionId = $conversation->region_id;
        $products = Product::where('is_active', true)
            ->where(function ($q) use ($regionId) {
                $q->where('region_id', $regionId)->orWhereNull('region_id');
            })
            ->with(['variants' => fn($q) => $q->where('is_active', true)])
            ->get();

        $text = "📋 *KATALOG PRODUK*\n\n";

        $grouped = $products->groupBy(fn($p) => $p->category->name ?? 'Lainnya');

        foreach ($grouped as $category => $items) {
            $text .= "*{$category}*\n";
            foreach ($items as $product) {
                $variants = $product->variants;
                $text .= "• {$product->name}\n";
                foreach ($variants as $variant) {
                    $text .= "  └ {$variant->name}: Rp " . number_format($variant->price, 0, ',', '.') . "\n";
                }
            }
            $text .= "\n";
        }

        $text .= "Ketik *PESAN* untuk mulai order.";

        $this->metaService->sendText($conversation->phone_number, $text);
    }

    protected function sendProductsByCategory(WhatsAppConversation $conversation, string $categoryName): void
    {
        $regionId = $conversation->region_id;

        $category = \App\Models\Category::where('name', 'like', "%{$categoryName}%")->first();

        if (!$category) {
            $this->metaService->sendText($conversation->phone_number, "Kategori '{$categoryName}' tidak ditemukan.");
            return;
        }

        $products = Product::where('category_id', $category->id)
            ->where('is_active', true)
            ->where(function ($q) use ($regionId) {
                $q->where('region_id', $regionId)->orWhereNull('region_id');
            })
            ->with(['variants' => fn($q) => $q->where('is_active', true)])
            ->get();

        if ($products->isEmpty()) {
            $this->metaService->sendText($conversation->phone_number, "Belum ada produk di kategori {$categoryName} untuk cabang Anda.");
            return;
        }

        $text = "📦 *{$categoryName}*\n\n";

        foreach ($products as $index => $product) {
            $text .= "*{$product->name}*\n";
            $text .= "{$product->description}\n";
            foreach ($product->variants as $variant) {
                $text .= "  💰 {$variant->name}: Rp " . number_format($variant->price, 0, ',', '.') . "\n";
            }
            $text .= "\n";
        }

        $text .= "Ketik *PESAN* untuk mulai order.";

        $this->metaService->sendText($conversation->phone_number, $text);
    }

    protected function sendProductDetail(WhatsAppConversation $conversation, Product $product): void
    {
        $text = "*{$product->name}*\n\n";
        $text .= "{$product->description}\n\n";
        $text .= "Varian:\n";
        foreach ($product->variants->where('is_active', true) as $variant) {
            $text .= "• {$variant->name}: Rp " . number_format($variant->price, 0, ',', '.') . "\n";
        }
        $text .= "\nKetik *PESAN* untuk order produk ini.";

        $this->metaService->sendText($conversation->phone_number, $text);
    }

    protected function askOrderForm(WhatsAppConversation $conversation): void
    {
        $conversation->setContext('form_step', 0);
        $conversation->setContext('order_form', []);

        $this->metaService->sendText($conversation->phone_number,
            "📝 *FORMULIR PESANAN*\n\n" .
            "Silakan isi data pesanan Anda.\n\n" .
            "Langkah 1/5: Nama produk yang ingin dipesan?"
        );
    }

    protected function askDeliveryMethod(WhatsAppConversation $conversation): void
    {
        $this->advanceState($conversation, OrderBotConversationState::AWAITING_DELIVERY_METHOD);

        $this->metaService->sendReplyButtons(
            $conversation->phone_number,
            "🚚 *Pilih Metode Pengiriman*\n\n" .
            "1️⃣ Diambil sendiri (gratis ongkir)\n" .
            "2️⃣ Grab/GoSend (biaya ditanggung ke driver)\n" .
            "3️⃣ Kurir internal Kue Pandan Asli",
            [
                ['id' => 'delivery_1', 'title' => '1. Diambil Sendiri'],
                ['id' => 'delivery_2', 'title' => '2. Grab/GoSend'],
                ['id' => 'delivery_3', 'title' => '3. Kurir Internal'],
            ],
            "Pilih opsi pengiriman"
        );
    }

    protected function sendDeliveryMethodOptions(WhatsAppConversation $conversation): void
    {
        $this->metaService->sendReplyButtons(
            $conversation->phone_number,
            "Pilih metode pengiriman:",
            [
                ['id' => 'delivery_1', 'title' => '1. Diambil Sendiri'],
                ['id' => 'delivery_2', 'title' => '2. Grab/GoSend'],
                ['id' => 'delivery_3', 'title' => '3. Kurir Internal'],
            ]
        );
    }

    protected function sendSlotOptions(WhatsAppConversation $conversation): void
    {
        $this->metaService->sendReplyButtons(
            $conversation->phone_number,
            "🕐 *Pilih Slot Waktu Pengiriman*\n\n" .
            "1️⃣ 09:00 - 11:00\n" .
            "2️⃣ 11:00 - 13:00\n" .
            "3️⃣ 13:00 - 15:00\n" .
            "4️⃣ 15:00 - 17:00",
            [
                ['id' => 'slot_1', 'title' => '09:00-11:00'],
                ['id' => 'slot_2', 'title' => '11:00-13:00'],
                ['id' => 'slot_3', 'title' => '13:00-15:00'],
                ['id' => 'slot_4', 'title' => '15:00-17:00'],
            ],
            "Pilih slot waktu"
        );
    }

    protected function sendOrderSummary(WhatsAppConversation $conversation): void
    {
        $context = $conversation->context ?? [];
        $form = $context['order_form'] ?? [];

        $productName = $form['product_name'] ?? '-';
        $recipientName = $form['recipient_name'] ?? '-';
        $address = $form['recipient_address'] ?? $context['delivery_address'] ?? '-';
        $date = $form['delivery_date'] ?? '-';
        $time = $form['delivery_time'] ?? '-';
        $slot = $context['delivery_slot'] ?? '-';
        $method = $context['delivery_method'] ?? '-';
        $ongkir = $context['ongkir'] ?? 0;

        // Resolve harga live dari database (draft order context tidak menyimpan harga)
        $productPrice = $context['product_price'] ?? null;
        if ($productPrice === null && $productName !== '-') {
            $resolvedProduct = $this->resolveProduct($conversation, $productName);
            $resolvedVariant = $resolvedProduct ? $resolvedProduct->variants->where('is_active', true)->first() : null;
            $productPrice = $resolvedVariant?->price ?? 0;
        }

        $quantity = $context['product_quantity'] ?? 1;
        $totalProduct = $productPrice * $quantity;
        $totalAll = $totalProduct + $ongkir;

        $methodText = match($method) {
            'self_pickup' => 'Diambil Sendiri',
            'grab_gosend' => 'Grab/GoSend',
            'internal_courier' => 'Kurir Internal',
            default => $method,
        };

        $text = "📋 *RINGKASAN PESANAN*\n\n";
        $text .= "Produk: {$productName}\n";
        $text .= "Qty: {$quantity}\n";
        $text .= "Harga: Rp " . number_format($productPrice, 0, ',', '.') . "\n";
        $text .= "Subtotal Produk: Rp " . number_format($totalProduct, 0, ',', '.') . "\n";
        $text .= "Ongkir ({$methodText}): Rp " . number_format($ongkir, 0, ',', '.') . "\n";
        $text .= "─────────────────\n";
        $text .= "*TOTAL: Rp " . number_format($totalAll, 0, ',', '.') . "*\n\n";
        $text .= "Penerima: {$recipientName}\n";
        $text .= "Alamat: {$address}\n";
        $text .= "Tanggal: {$date}\n";
        $text .= "Jam: {$time} (slot {$slot})\n\n";
        $text .= "Ketik *FIX* untuk konfirmasi, atau *BATAL* untuk membatalkan.";

        $this->metaService->sendText($conversation->phone_number, $text);
    }

    protected function confirmOrder(WhatsAppConversation $conversation): void
    {
        $context = $conversation->context ?? [];
        $form = $context['order_form'] ?? [];

        try {
            $order = DB::transaction(function () use ($conversation, $form, $context) {
                $customer = $this->findOrCreateCustomer($conversation);

                // Check customer category quota
                if ($customer->customer_category_id) {
                    $category = CustomerCategory::find($customer->customer_category_id);
                    if ($category) {
                        $maxOrders = strtolower($category->name) === 'supermarket' ? 30 : 7;
                        $activeOrders = Order::where('customer_id', $customer->id)
                            ->whereNotIn('status', ['selesai', 'diverifikasi_admin', 'dibatalkan'])
                            ->count();

                        if ($activeOrders >= $maxOrders) {
                            $this->metaService->sendText($conversation->phone_number,
                                "⚠️ Kuota order aktif Anda sudah mencapai batas ({$maxOrders} order).\n" .
                                "Silakan tunggu pesanan sebelumnya selesai atau hubungi admin."
                            );
                            return null;
                        }
                    }
                }

                $product = $this->resolveProduct($conversation, $form['product_name'] ?? '');
                $variant = $product ? $product->variants->where('is_active', true)->first() : null;

                $subtotal = ($variant->price ?? 0) * ($context['product_quantity'] ?? 1);

                $order = Order::create([
                    'invoice_number' => $this->generateInvoiceNumber($conversation),
                    'customer_id' => $customer->id,
                    'phone' => $conversation->phone_number,
                    'address' => $form['recipient_address'] ?? $context['delivery_address'] ?? '',
                    'total_amount' => $subtotal + ($context['ongkir'] ?? 0),
                    'payment_method' => 'qris',
                    'note' => "Penerima: {$form['recipient_name']}\n" .
                              "Tanggal kirim: {$form['delivery_date']}\n" .
                              "Jam kirim: {$form['delivery_time']}\n" .
                              "Slot: {$context['delivery_slot']}\n" .
                              "Metode: {$context['delivery_method']}\n" .
                              (isset($context['distance_km']) ? "Jarak: {$context['distance_km']}km\n" : ''),
                    'created_by_user_id' => null, // Bot system
                    'region_id' => $conversation->region_id,
                    'status' => 'baru',
                    'channel' => 'whatsapp',
                ]);

                if ($product) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'variant_id' => $variant?->id,
                        'variant_name' => $variant?->name,
                        'quantity' => $context['product_quantity'] ?? 1,
                        'price' => $variant->price ?? 0,
                        'subtotal' => $subtotal,
                    ]);
                }

                return $order;
            });

            if (!$order) return;

            $conversation->setContext('confirmed_order_id', $order->id);
            $this->advanceState($conversation, OrderBotConversationState::AWAITING_PAYMENT_PROOF);

            // Notify admin immediately so the new order is never missed
            $this->notificationRouter->notifyNewOrder($order->id);

            $this->metaService->sendText($conversation->phone_number,
                "✅ *Pesanan Berhasil Dibuat!*\n\n" .
                "Nomor Invoice: *{$order->invoice_number}*\n" .
                "Total: *Rp " . number_format($order->total_amount, 0, ',', '.') . "*\n\n" .
                "Silakan lakukan pembayaran melalui QRIS, lalu kirim bukti transfer di sini.\n" .
                "Atau ketik *SKIP* untuk melanjutkan tanpa mengirim bukti."
            );

        } catch (\Exception $e) {
            Log::channel('whatsapp')->error('❌ Failed to confirm order', [
                'phone' => $conversation->phone_number,
                'error' => $e->getMessage(),
            ]);

            $this->metaService->sendText($conversation->phone_number,
                "❌ Terjadi kesalahan saat membuat pesanan. Silakan coba lagi atau hubungi admin."
            );
        }
    }

    // ========== HELPERS ==========

    protected function advanceState(WhatsAppConversation $conversation, OrderBotConversationState $newState): void
    {
        $conversation->update(['current_state' => $newState->value]);
        Log::channel('whatsapp')->info('🔄 State transition', [
            'phone' => $conversation->phone_number,
            'new_state' => $newState->value,
        ]);
    }

    protected function matchesIntent(string $text, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if (str_contains($text, $keyword)) {
                return true;
            }
        }
        return false;
    }

    protected function findProductByKeyword(WhatsAppConversation $conversation, string $keyword): ?Product
    {
        $regionId = $conversation->region_id;

        return Product::where('is_active', true)
            ->where(function ($q) use ($regionId) {
                $q->where('region_id', $regionId)->orWhereNull('region_id');
            })
            ->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('tag', 'like', "%{$keyword}%")
                  ->orWhere('description', 'like', "%{$keyword}%");
            })
            ->with(['variants' => fn($q) => $q->where('is_active', true)])
            ->first();
    }

    protected function findOrCreateCustomer(WhatsAppConversation $conversation): Customer
    {
        $phone = Phone::normalize($conversation->phone_number);

        $customer = Customer::where('phone', $phone)->first();

        if (!$customer) {
            $form = $conversation->context['order_form'] ?? [];
            $customer = Customer::create([
                'name' => $form['recipient_name'] ?? $conversation->profile_name ?? 'Customer WA',
                'phone' => $phone,
                'address' => $form['recipient_address'] ?? '',
                'region_id' => $conversation->region_id,
                'added_by_user_id' => null,
            ]);
        }

        if (!$conversation->customer_id) {
            $conversation->update(['customer_id' => $customer->id]);
        }

        return $customer;
    }

    protected function resolveProduct(WhatsAppConversation $conversation, string $productName): ?Product
    {
        $regionId = $conversation->region_id;

        return Product::where('is_active', true)
            ->where(function ($q) use ($regionId) {
                $q->where('region_id', $regionId)->orWhereNull('region_id');
            })
            ->where('name', 'like', "%{$productName}%")
            ->with(['variants' => fn($q) => $q->where('is_active', true)])
            ->first();
    }

    protected function generateInvoiceNumber(WhatsAppConversation $conversation): string
    {
        $now = now();
        $ddmm = $now->format('dm');
        $regionId = str_pad($conversation->region_id ?? 1, 2, '0', STR_PAD_LEFT);
        $courierId = '000';
        $customerId = str_pad($conversation->customer_id ?? 0, 3, '0', STR_PAD_LEFT);

        $dailyCount = Order::where('region_id', $conversation->region_id)
            ->whereDate('created_at', $now->toDateString())
            ->count() + 1;

        $sequence = str_pad($dailyCount, 3, '0', STR_PAD_LEFT);

        return "INV/{$ddmm}/{$regionId}/{$courierId}/{$customerId}/{$sequence}";
    }

    protected function sendHelpMessage(WhatsAppConversation $conversation): void
    {
        $this->metaService->sendText($conversation->phone_number,
            "Halo! 👋 Ada yang bisa kami bantu?\n\n" .
            "Ketik *PESAN* untuk membuat pesanan\n" .
            "Ketik *PRODUK* untuk melihat katalog\n" .
            "Ketik *BANTUAN* untuk bantuan\n\n" .
            "Atau langsung ketik produk yang Anda inginkan."
        );
    }
}
