<?php

namespace App\Services\WhatsApp;

use App\Enums\OrderBotConversationState;
use App\Models\Customer;
use App\Models\CustomerCategory;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Region;
use App\Models\WhatsAppConversation;
use App\Support\Phone;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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

        // Pricelist: bisa diminta dari state apa pun tanpa keluar alur — kecuali saat
        // bot sedang menunggu jawaban field bebas (jawaban form / alamat), agar jawaban
        // customer yang kebetulan mengandung kata "harga" tidak ditelan.
        $acceptsPricelist = ! in_array($state, [
            OrderBotConversationState::INIT,
            OrderBotConversationState::AWAITING_ORDER_FORM,
            OrderBotConversationState::AWAITING_LOCATION_OR_ADDRESS,
        ], true);

        if ($acceptsPricelist
            && $this->matchesCommand(strtolower(trim($messageText)), ['pricelist', 'price list', 'daftar harga', 'list harga', 'harga'])
        ) {
            $this->sendPricelist($conversation);

            return;
        }

        // Escape hatch: keyword global ("BATAL", "MENU", "MULAI ULANG") valid dari state apa pun,
        // supaya user tidak pernah terjebak di tengah alur order.
        if ($this->checkGlobalEscape($conversation, $messageText)) {
            return;
        }

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

            if ($lat !== null && $lng !== null && is_numeric($lat) && is_numeric($lng)) {
                $this->finalizeCourierLocation($conversation, (float) $lat, (float) $lng);
            }
        } elseif ($state === OrderBotConversationState::AWAITING_DELIVERY_METHOD) {
            // Pin disimpan dulu — user tetap memilih metode sendiri (jangan paksa '3').
            $conversation->setContext('pending_location', [
                'latitude' => $locationData['latitude'] ?? null,
                'longitude' => $locationData['longitude'] ?? null,
            ]);
            $this->metaService->sendText($conversation->phone_number,
                "📍 Lokasi diterima dan disimpan.\n\nSilakan pilih dulu metode pengirimannya:"
            );
            $this->sendDeliveryMethodOptions($conversation);
        } else {
            // H2 FIX: never swallow a location message silently — reply with current step guide
            $this->metaService->sendText($conversation->phone_number,
                "📍 Lokasi diterima, tapi saat ini bot sedang menunggu:\n\n".
                $this->buildCurrentStepGuide($conversation)."\n\n".
                "Ketik *MENU* untuk mulai ulang, atau lanjutkan menjawab langkah yang sedang diminta."
            );
        }
    }

    /**
     * Hitung ongkir dari koordinat GPS + lanjutkan alur (dipakai pin langsung
     * maupun pin yang dititipkan saat memilih metode).
     */
    protected function finalizeCourierLocation(WhatsAppConversation $conversation, float $lat, float $lng): void
    {
        $distance = $this->deliveryZoneService->estimateDistance($lat, $lng, $conversation->region_id);
        $result = $this->deliveryZoneService->calculateOngkir($distance, $conversation->region_id);

        if ($result['needs_escalation']) {
            $conversation->clearContext();
            $conversation->update(['current_state' => OrderBotConversationState::ESCALATED_TO_HUMAN->value]);
            $regionName = $conversation->region?->name ?? config('services.whatsapp.default_region', 'cabang kami');
            $this->metaService->sendText($conversation->phone_number,
                "Mohon maaf, jarak pengiriman Anda sekitar {$distance}km dari toko kami.\n".
                "Untuk jarak di atas 14km, silakan hubungi admin kami untuk detail pengiriman.\n\n".
                "WA Admin: hubungi admin terdekat di cabang {$regionName}"
            );
            $this->notifyAdminOfEscalation($conversation, $distance, $regionName);

            return;
        }

        $conversation->setContext('distance_km', $distance);
        $conversation->setContext('ongkir', $result['ongkir']);
        $conversation->setContext('delivery_address', "Lokasi GPS ({$lat}, {$lng})");
        $conversation->setContext('pending_location', null);

        $context = $conversation->context ?? [];
        if (empty($context['delivery_method'])) {
            $this->advanceState($conversation, OrderBotConversationState::AWAITING_DELIVERY_METHOD);
            $this->askDeliveryMethod($conversation);

            return;
        }

        $this->advanceState($conversation, OrderBotConversationState::AWAITING_DELIVERY_SLOT);
        $this->sendSlotOptions($conversation);
    }

    /**
     * Escape hatch global. Keyword ini dicek SEBELUM state machine dipanggil,
     * sehingga user tidak pernah terkunci di tengah alur order.
     */
    protected function checkGlobalEscape(WhatsAppConversation $conversation, string $text): bool
    {
        $state = OrderBotConversationState::from($conversation->current_state);
        $lower = strtolower(trim($text));

        $orderFlowStates = [
            OrderBotConversationState::AWAITING_ORDER_FORM,
            OrderBotConversationState::AWAITING_DELIVERY_METHOD,
            OrderBotConversationState::AWAITING_LOCATION_OR_ADDRESS,
            OrderBotConversationState::AWAITING_DELIVERY_SLOT,
            OrderBotConversationState::ORDER_SUMMARY,
            OrderBotConversationState::AWAITING_PAYMENT_PROOF,
        ];

        // BATAL / CANCEL — kata utuh saja ("Batalyon" tidak ikut batal)
        if (in_array($state, array_merge($orderFlowStates, [OrderBotConversationState::PRODUCT_BROWSING]), true)
            && $this->matchesCommand($lower, ['batal', 'cancel', 'batalkan', 'batalin'])
        ) {
            $conversation->clearContext();
            $this->advanceState($conversation, OrderBotConversationState::MENU_SELECTION);
            $this->metaService->sendText(
                $conversation->phone_number,
                "🛑 Pesanan dibatalkan.\n\nBerikut pilihan kategori:"
            );
            $this->sendProductCategories($conversation);

            return true;
        }

        // MENU / KEMBALI — kata utuh saja ("menunggu" tidak ikut ke menu)
        if (in_array($state, $orderFlowStates, true)
            && $this->matchesCommand($lower, ['menu', 'utama', 'kembali', 'awal', 'back'])
        ) {
            $conversation->clearContext();
            $this->advanceState($conversation, OrderBotConversationState::MENU_SELECTION);
            $this->metaService->sendText(
                $conversation->phone_number,
                "📋 Kembali ke menu.\n\nBerikut pilihan kategori:"
            );
            $this->sendProductCategories($conversation);

            return true;
        }

        // PESAN / ORDER — mulai ulang formulir pesanan; juga dari ESCALATED / ORDER_CONFIRMED
        $canRestart = in_array($state, array_merge($orderFlowStates, [
            OrderBotConversationState::ESCALATED_TO_HUMAN,
            OrderBotConversationState::ORDER_CONFIRMED,
        ]), true);
        $isFormFieldAnswer = $conversation->getContext('form_step') !== null
            && (
                stripos($lower, 'alamat') !== false
                || stripos($lower, 'nama') !== false
                || stripos($lower, 'tangg') !== false
                || stripos($lower, 'jam') !== false
            );
        if ($canRestart
            && ! $isFormFieldAnswer
            && $this->matchesCommand($lower, ['mau pesan', 'pesan', 'order', 'ordering', 'mau order'])
        ) {
            $conversation->clearContext();
            $this->advanceState($conversation, OrderBotConversationState::AWAITING_ORDER_FORM);
            $this->metaService->sendText(
                $conversation->phone_number,
                "📝 Oke, kita mulai ulang formulir pesanannya ya 😊"
            );
            $this->askOrderForm($conversation);

            return true;
        }

        // PRODUK / KATALOG — kata utuh saja ("produksi" bukan perintah)
        if (in_array($state, $orderFlowStates, true)
            && $this->matchesCommand($lower, ['lihat produk', 'produk', 'katalog', 'catalog', 'kategori'])
        ) {
            $conversation->clearContext();
            $this->advanceState($conversation, OrderBotConversationState::PRODUCT_BROWSING);
            $this->sendProductCatalog($conversation);

            return true;
        }

        // HALO / BANTUAN — kata utuh saja ("assalamualaikum, Shinta" lolos ke form)
        if (in_array($state, $orderFlowStates, true)
            && ($this->matchesCommand($lower, ['halo', 'hai', 'hi', 'hello', 'salam', 'assalam'])
                || $this->matchesCommand($lower, ['bantuan', 'help', 'tolong']))
        ) {
            $this->metaService->sendText(
                $conversation->phone_number,
                "Halo! 😊 Sepertinya kamu masih di tengah proses pesanan.\n\n".
                "📌 *Yang sedang bot tunggu:*\n{$this->buildCurrentStepGuide($conversation)}\n\n".
                "💡 *Perintah yang bisa kamu pakai:*\n".
                "• *PESAN* — mulai ulang formulir pesanan dari awal\n".
                "• *PRODUK* — lihat katalog produk\n".
                "• *MENU* — kembali ke daftar kategori\n".
                "• *BATAL* — batalkan pesanan\n\n".
                'Atau langsung lanjutkan menjawab pertanyaan di atas.'
            );

            return true;
        }

        // MULAI ULANG / RESET — mulai dari awal, berlaku untuk semua state
        if ($this->matchesCommand($lower, ['mulai ulang', 'mulai dari awal', 'ulang dari awal', 'start ulang', 'restart', 'reset', 'ulangi', 'mulai lagi'])) {
            $conversation->clearContext();
            $conversation->update([
                'current_state' => OrderBotConversationState::WELCOME_SENT->value,
                'status' => 'active',
            ]);
            $this->sendWelcomeMessage($conversation);

            return true;
        }

        return false;
    }

    /**
     * Reset percakapan yang terbengkalai (stale) untuk user yang kembali setelah lama tidak aktif,
     * sehingga bot selalu bisa mulai dari awal lagi. Dipanggil dari webhook job SEBELUM
     * message count/last_message_at diperbarui.
     *
     * @param  \Illuminate\Support\Carbon|null  $lastActivity  waktu pesan masuk sebelumnya
     */
    public function resetConversationIfStale(WhatsAppConversation $conversation, ?\Illuminate\Support\Carbon $lastActivity): bool
    {
        if (! $lastActivity) {
            return false;
        }

        $thresholdMinutes = (int) config('services.whatsapp.session_expire_minutes', 60);

        if ($lastActivity->diffInMinutes(now()) < $thresholdMinutes) {
            return false;
        }

        $state = OrderBotConversationState::from($conversation->current_state);
        $intermediateStates = [
            OrderBotConversationState::WELCOME_SENT,
            OrderBotConversationState::MENU_SELECTION,
            OrderBotConversationState::PRODUCT_BROWSING,
            OrderBotConversationState::AWAITING_ORDER_FORM,
            OrderBotConversationState::AWAITING_DELIVERY_METHOD,
            OrderBotConversationState::AWAITING_LOCATION_OR_ADDRESS,
            OrderBotConversationState::AWAITING_DELIVERY_SLOT,
            OrderBotConversationState::ORDER_SUMMARY,
            OrderBotConversationState::AWAITING_PAYMENT_PROOF,
            OrderBotConversationState::ORDER_CONFIRMED,
        ];

        if (! in_array($state, $intermediateStates, true)) {
            return false;
        }

        $this->metaService->sendText(
            $conversation->phone_number,
            "Halo *{$conversation->profile_name}* 👋\n\n".
            'Sepertinya obrolan sebelumnya sudah lama. Mari mulai dari awal lagi 😊'."\n\n".
            "Ketik *PESAN* untuk mulai order,\n".
            'atau ketik *PRODUK* untuk melihat katalog.'
        );
        $conversation->clearContext();
        $conversation->update([
            'current_state' => OrderBotConversationState::WELCOME_SENT->value,
            'status' => 'active',
        ]);

        Log::channel('whatsapp')->info('🔄 Stale conversation reset', [
            'phone' => $conversation->phone_number,
            'previous_state' => $state->value,
            'inactive_minutes' => $lastActivity->diffInMinutes(now()),
        ]);

        return true;
    }

    protected function handleInit(WhatsAppConversation $conversation, string $text): void
    {
        $this->sendWelcomeMessage($conversation);
        $this->advanceState($conversation, OrderBotConversationState::WELCOME_SENT);
    }

    protected function handleAfterWelcome(WhatsAppConversation $conversation, string $text): void
    {
        // Jawaban atas pertanyaan cabang — tangani dulu sebelum intent lain
        if ($conversation->getContext('awaiting_branch')) {
            $this->handleBranchChoice($conversation, $text);

            return;
        }

        $lower = strtolower(trim($text));

        if ($this->matchesIntent($lower, ['mau pesan', 'pesan', 'order', 'ordering', 'mau order'])) {
            // Cabang dipilih eksplisit dulu agar katalog, ongkir & notif owner tepat
            $conversation->clearContext();
            $this->askBranchSelection($conversation);

            return;
        } elseif ($this->matchesIntent($lower, ['liat produk', 'lihat produk', 'produk', 'catalog', 'katalog'])) {
            $this->sendProductCatalog($conversation);
            $this->advanceState($conversation, OrderBotConversationState::PRODUCT_BROWSING);
        } elseif ($city = $this->detectCityMention($lower)) {
            $this->handleCityMention($conversation, $city);
        } elseif ($this->matchesIntent($lower, ['halo', 'hai', 'hi', 'hello', 'salam', 'assalam'])) {
            $this->sendWelcomeMessage($conversation);
        } else {
            $this->sendHelpMessage($conversation);
        }
    }

    /**
     * Tanya cabang pengiriman (Surabaya / Malang / ...) sebelum mulai order.
     * Pilihan dikunci sebagai atribusi manual agar tidak ditimpa otomatis.
     */
    /**
     * Opsi cabang bernomor yang ditampilkan ke user. Tombol balasan Meta
     * dibatasi 3, tetapi daftar teks memuat SEMUA cabang bila lebih dari 3.
     *
     * @return \Illuminate\Support\Collection<int, \App\Models\Region>
     */
    protected function branchOptions()
    {
        return Region::orderBy('name')->get();
    }

    /**
     * Cocokkan jawaban dengan nama/slug cabang dua arah:
     * "surabaya" cocok "Kota Surabaya" dan sebaliknya; "kota-surabaya"
     * sama dengan "kota surabaya". Jawaban pendek (<4 huruf) diabaikan.
     */
    protected function branchMatches(string $answer, string $name): bool
    {
        $norm = fn ($s) => trim((string) preg_replace('/\s+/', ' ', str_replace(['-', '_'], ' ', strtolower($s))));

        $answer = $norm($answer);
        $name = $norm($name);

        if ($answer === '' || mb_strlen($answer) < 4 || $name === '') {
            return false;
        }

        if ($answer === $name) {
            return true;
        }

        return $this->containsWholeWord($answer, $name)
            || $this->containsWholeWord($name, $answer);
    }

    protected function askBranchSelection(WhatsAppConversation $conversation): void
    {
        $regions = Region::orderBy('name')->get();
        $conversation->setContext('awaiting_branch', true);

        if ($regions->count() > 1) {
            $options = $this->branchOptions();
            $buttons = [];
            $lines = [];
            foreach ($options as $i => $region) {
                $num = (string) ($i + 1);
                $lines[] = "{$num}. {$region->name}";
                // Tombol hanya untuk 3 pertama (batas Meta); sisanya via angka/nama.
                if ($i < 3) {
                    $buttons[] = ['id' => 'branch_' . $region->id, 'title' => mb_substr($region->name, 0, 20)];
                }
            }

            $this->metaService->sendReplyButtons(
                $conversation->phone_number,
                "📍 *Pilih Cabang Pengiriman*\n\n" . implode("\n", $lines) . "\n\nPesanan, ongkir & harga mengikuti cabang yang dipilih.",
                $buttons,
                'Ketik angka atau nama cabang'
            );

            return;
        }

        // Hanya 1 cabang terdaftar — langsung ke langkah lokasi
        $conversation->setContext('awaiting_branch', null);
        $this->requestDeliveryLocation($conversation);
    }

    protected function handleBranchChoice(WhatsAppConversation $conversation, string $text): void
    {
        $answer = strtolower(trim($text));

        // Keluar dari pertanyaan cabang (tidak berputar selamanya).
        if ($this->matchesCommand($answer, ['batal', 'cancel', 'menu', 'utama', 'kembali', 'awal', 'back'])) {
            $conversation->setContext('awaiting_branch', null);
            $this->metaService->sendText($conversation->phone_number,
                'Baik, pilihan cabang dibatalkan.'
            );
            $this->sendWelcomeMessage($conversation);

            return;
        }

        $regions = Region::orderBy('name')->get();

        $chosen = null;

        // 1. ID tombol balasan: branch_5 (hanya yang sedang ditampilkan)
        if (preg_match('/^branch_(\d+)$/', $answer, $m)) {
            $allowed = $this->branchOptions()->pluck('id')->all();
            if (in_array((int) $m[1], $allowed, true)) {
                $chosen = $regions->firstWhere('id', (int) $m[1]);
            }
        }

        // 2. Angka urutan sesuai daftar bernomor yang ditampilkan
        if (! $chosen && is_numeric($answer)) {
            $chosen = $this->branchOptions()->values()->get((int) $answer - 1);
        }

        // 3. Nama / slug / alias kota, dua arah + normalisasi tanda hubung
        if (! $chosen) {
            if ($city = $this->detectCityMention($answer)) {
                $answer = strtolower($city);
            }
            $chosen = $regions->first(fn ($r) => $this->branchMatches($answer, (string) $r->name)
                || $this->branchMatches($answer, (string) $r->slug));
        }

        if (! $chosen) {
            $this->metaService->sendText($conversation->phone_number,
                'Mohon pilih salah satu cabang yang tersedia ya 😊'
            );
            $this->askBranchSelection($conversation);

            return;
        }

        // Kunci cabang (sumber manual — tidak ditimpa ConversationRegionResolver)
        $context = $conversation->context ?? [];
        $context['branch_source'] = ConversationRegionResolver::SOURCE_MANUAL;
        $context['awaiting_branch'] = null;
        $conversation->update(['region_id' => $chosen->id, 'context' => $context]);
        unset($conversation->region);

        $this->metaService->sendText($conversation->phone_number,
            "✅ Cabang *{$chosen->name}* dipilih.\n\nKetik *PRODUK* untuk lihat katalog cabang ini, atau lanjut 👇"
        );
        // Lokasi harus dipilih dulu saat chat agar tidak salah arah
        $this->requestDeliveryLocation($conversation);
    }

    protected function requestDeliveryLocation(WhatsAppConversation $conversation): void
    {
        $this->advanceState($conversation, OrderBotConversationState::AWAITING_LOCATION_OR_ADDRESS);
        $this->metaService->sendLocationRequest($conversation->phone_number,
            "📍 *Pilih Lokasi Pengiriman Dulu*

Agar arah pengiriman tepat, silakan kirim pin GPS lokasi Anda atau ketik alamat lengkap."
        );
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
            // Coba sebagai nama produk dulu ("tumpeng mini"), baru fallback kategori.
            $product = $this->findProductByKeyword($conversation, $this->extractProductQuery($lower));
            if ($product) {
                $conversation->setContext('selected_product_name', $product->name);
                $conversation->setContext('selected_category', $product->category->name ?? null);
                $this->advanceState($conversation, OrderBotConversationState::PRODUCT_BROWSING);
                $this->sendProductDetail($conversation, $product);
            } else {
                $this->sendProductCategories($conversation);
            }
        }
    }

    protected function handleProductBrowsing(WhatsAppConversation $conversation, string $text): void
    {
        $lower = strtolower(trim($text));

        // Perintah eksak dulu ("PESAN" saja), BUKAN substring — "mau tanya X" dan
        // "pilihan hampers" harus jadi pencarian produk, bukan lompat ke form.
        if (preg_match('/^(mau\s+)?(pesan|order|ordering)\s*$/iu', $lower)) {
            $this->askOrderForm($conversation);
            $this->advanceState($conversation, OrderBotConversationState::AWAITING_ORDER_FORM);
        } elseif ($this->matchesCommand($lower, ['kembali', 'back', 'menu'])) {
            $this->sendProductCategories($conversation);
            $this->advanceState($conversation, OrderBotConversationState::MENU_SELECTION);
        } else {
            $product = null;
            // Jika user memilih angka dari list produk — resolve dari urutan ID yang
            // disimpan saat katalog dikirim (deterministik, cocok dengan nomor tampil).
            if (is_numeric($lower)) {
                $catalogIds = $conversation->getContext('catalog_ids') ?? [];
                $productId = $catalogIds[(int) $lower - 1] ?? null;

                if ($productId) {
                    $regionId = $conversation->region_id;
                    $product = Product::where('id', $productId)
                        ->where('is_active', true)
                        ->where(function ($q) use ($regionId) {
                            $q->where('region_id', $regionId)->orWhereNull('region_id');
                        })
                        ->with(['category', 'variants' => fn ($q) => $q->where('is_active', true)])
                        ->first();
                }

                if (! $product) {
                    $this->metaService->sendText($conversation->phone_number,
                        'Nomor tidak ada di daftar. Ketik angka sesuai katalog, nama produk, atau *MENU* untuk kategori.'
                    );

                    return;
                }
            } else {
                // Dukung "2x Tumpeng Mini" + pertanyaan bebas ("apa itu ...", "info ...").
                [$browsingQty, $browsingQuery] = $this->extractQuantity($this->extractProductQuery($lower));
                $product = $browsingQuery === ''
                    ? null
                    : $this->findProductByKeyword($conversation, $browsingQuery);
                if ($product && $browsingQty > 1) {
                    $conversation->setContext('product_quantity', $browsingQty);
                }
            }
            if ($product) {
                $conversation->setContext('selected_product_name', $product->name);
                $conversation->setContext('selected_category', $product->category->name ?? null);
                $this->sendProductDetail($conversation, $product);
            } elseif ($this->matchesIntent($lower, ['pesan', 'order', 'pilih', 'ambil', 'mau'])) {
                // Fallback kompatibilitas: "pilih no 2", "mau order", dsb.
                $this->askOrderForm($conversation);
                $this->advanceState($conversation, OrderBotConversationState::AWAITING_ORDER_FORM);
            } else {
                $this->metaService->sendText($conversation->phone_number,
                    "Produk tidak ditemukan. Ketik nama produk yang tersedia, *PESAN* untuk mulai order, atau *MENU* untuk kategori."
                );
            }
        }
    }

    protected function handleOrderForm(WhatsAppConversation $conversation, string $text): void
    {
        $context = $conversation->context ?? [];
        $formStep = $context['form_step'] ?? 0;

        match ($formStep) {
            0 => $this->processProductNameStep($conversation, $text),
            1 => $this->processCombinedForm($conversation, $text),
            default => $this->resumeOrderForm($conversation),
        };
    }

    /**
     * Langkah 1/2: nama produk (+ jumlah opsional, mis. "2x Tumpeng Mini").
     */
    protected function processProductNameStep(WhatsAppConversation $conversation, string $text): void
    {
        [$quantity, $productName] = $this->extractQuantity($text);

        if ($productName === '') {
            $this->metaService->sendText($conversation->phone_number,
                'Nama produk belum terisi. Contoh: *Tumpeng Mini* atau *2x Tumpeng Mini* untuk 2 buah.'
            );

            return;
        }

        $conversation->setContext('order_form', ['product_name' => $productName]);
        $conversation->setContext('product_quantity', $quantity);
        $conversation->setContext('form_step', 1);

        $qtyText = $quantity > 1 ? " ({$quantity} buah)" : '';
        $this->metaService->sendText($conversation->phone_number,
            "✅ Produk: {$productName}{$qtyText}\n\nSilakan isi semua data pesanan dalam 1 pesan (pisahkan dengan |):\nNama Penerima | Alamat Lengkap | Tanggal Kirim (10 Sept 2026) | Jam Tiba (10:00)"
        );
    }

    /**
     * Parse awalan jumlah "2x " / "2X " / "2× ". Batas 1–100.
     *
     * @return array{0: int, 1: string} [quantity, nama produk bersih]
     */
    protected function extractQuantity(string $text): array
    {
        if (preg_match('/^(\d{1,3})\s*[x×]\s+(.+)$/iu', trim($text), $m)) {
            $qty = max(1, min(100, (int) $m[1]));

            return [$qty, trim($m[2])];
        }

        return [1, trim($text)];
    }

    /**
     * Pengaman: hanya maju ke metode bila form gabungan benar-benar lengkap,
     * jika tidak kembalikan ke langkah pengisian.
     */
    protected function resumeOrderForm(WhatsAppConversation $conversation): void
    {
        $form = $conversation->getContext('order_form') ?? [];

        $complete = ! empty($form['product_name'])
            && ! empty($form['recipient_name'])
            && ! empty($form['recipient_address'])
            && ! empty($form['delivery_date'])
            && ! empty($form['delivery_time']);

        if ($complete) {
            $this->askDeliveryMethod($conversation);

            return;
        }

        $conversation->setContext('form_step', 1);
        $this->metaService->sendText($conversation->phone_number,
            "Data pesanan belum lengkap. Silakan isi semua dalam 1 pesan (pisahkan dengan |):\nNama Penerima | Alamat Lengkap | Tanggal Kirim | Jam Tiba"
        );
    }

    protected function handleDeliveryMethod(WhatsAppConversation $conversation, string $text): void
    {
        // Terima "1", "1.", "1 - Diambil Sendiri", dst.
        $choice = trim($text);
        if (preg_match('/^([123])[\s.\-)].*/u', $choice, $m)) {
            $choice = $m[1];
        }

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
        $conversation->setContext('pending_location', null);
        $this->advanceState($conversation, OrderBotConversationState::AWAITING_DELIVERY_SLOT);
        $this->sendSlotOptions($conversation);
    }

    protected function processGrabGoSend(WhatsAppConversation $conversation): void
    {
        $conversation->setContext('delivery_method', 'grab_gosend');
        $conversation->setContext('ongkir', 0);
        $conversation->setContext('pending_location', null);
        $this->advanceState($conversation, OrderBotConversationState::AWAITING_DELIVERY_SLOT);

        $this->metaService->sendText($conversation->phone_number,
            "📦 *Grab/GoSend*\n\n".
            "Biaya Grab/GoSend ditanggung langsung ke driver (estimasi Rp100.000–150.000 untuk zona 10–15km).\n".
            "Silakan pesan driver sendiri setelah pesanan dikonfirmasi.\n\n".
            'Pilih slot waktu pengiriman:'
        );
        $this->sendSlotOptions($conversation);
    }

    protected function processInternalCourier(WhatsAppConversation $conversation): void
    {
        $conversation->setContext('delivery_method', 'internal_courier');

        // Pin yang dititipkan saat memilih metode langsung dipakai (tak usah kirim ulang).
        $pending = $conversation->getContext('pending_location');
        if (is_array($pending)
            && isset($pending['latitude'], $pending['longitude'])
            && is_numeric($pending['latitude']) && is_numeric($pending['longitude'])
        ) {
            $this->advanceState($conversation, OrderBotConversationState::AWAITING_LOCATION_OR_ADDRESS);
            $this->finalizeCourierLocation($conversation, (float) $pending['latitude'], (float) $pending['longitude']);

            return;
        }

        $this->advanceState($conversation, OrderBotConversationState::AWAITING_LOCATION_OR_ADDRESS);

        $this->metaService->sendLocationRequest($conversation->phone_number,
            "📍 *Kurir Internal*\n\n".
            'Silakan kirim lokasi pengiriman Anda (pin GPS), atau ketik alamat lengkap.'
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
            $conversation->clearContext();
            $conversation->update(['current_state' => OrderBotConversationState::ESCALATED_TO_HUMAN->value]);
            $regionName = $conversation->region?->name ?? config('services.whatsapp.default_region', 'cabang kami');
            $this->metaService->sendText($conversation->phone_number,
                "Mohon maaf, jarak pengiriman Anda diperkirakan sekitar {$distance}km.\n".
                "Untuk jarak di atas 14km, silakan hubungi admin kami.\n\n".
                "WA Admin: hubungi admin terdekat di cabang {$regionName}"
            );
            // M1 FIX: notify admin after escalation is committed
            $this->notifyAdminOfEscalation($conversation, $distance, $regionName);

            return;
        }

        $conversation->setContext('distance_km', $distance);
        $conversation->setContext('ongkir', $result['ongkir']);

        // Jika belum memilih metode pengiriman, lanjutkan ke pilihan metode (lokasi sudah dipilih dulu)
        $context = $conversation->context ?? [];
        if (empty($context['delivery_method'])) {
            $this->advanceState($conversation, OrderBotConversationState::AWAITING_DELIVERY_METHOD);
            $this->askDeliveryMethod($conversation);
            return;
        }

        $this->advanceState($conversation, OrderBotConversationState::AWAITING_DELIVERY_SLOT);
        $this->sendSlotOptions($conversation);
    }

    protected function handleDeliverySlot(WhatsAppConversation $conversation, string $text): void
    {
        // Terima "1", "1.", "slot_1", "09:00", dst. Angka polos tidak diubah.
        $slot = trim($text);
        if (! in_array($slot, ['1', '2', '3'], true)) {
            if (preg_match('/^slot_([123])$/', $slot, $m)) {
                $slot = $m[1];
            } elseif (preg_match('/^([123])[\s.\-)].+/u', $slot, $m)) {
                $slot = $m[1];
            } elseif (preg_match('/^\d{1,2}:\d{2}/', $slot, $m)) {
                $hour = (int) explode(':', $m[0])[0];
                $slot = $hour < 11 ? '1' : ($hour < 13 ? '2' : '3');
            }
        }
        $validSlots = ['1', '2', '3'];

        if (! in_array($slot, $validSlots, true)) {
            $this->metaService->sendText($conversation->phone_number,
                'Pilihan tidak valid. Silakan pilih *1*, *2*, atau *3* sesuai daftar.'
            );
            $this->sendSlotOptions($conversation);

            return;
        }

        $slots = [
            '1' => '09:00-11:00',
            '2' => '11:00-13:00',
            '3' => '13:00-17:00',
        ];

        $conversation->setContext('delivery_slot', $slots[$slot]);
        $this->advanceState($conversation, OrderBotConversationState::ORDER_SUMMARY);
        $this->sendOrderSummary($conversation);
    }

    protected function handleOrderSummary(WhatsAppConversation $conversation, string $text): void
    {
        $lower = strtolower(trim($text));

        // Mode ubah alamat: pesan berikutnya disimpan sebagai alamat baru.
        if ($conversation->getContext('editing_address')) {
            $this->saveEditedAddress($conversation, trim($text));

            return;
        }

        // Konfirmasi kata-utuh; abaikan bila pesan mengandung pertanyaan ("ya, berapa harganya?").
        $isConfirm = $this->matchesCommand($lower, ['fix', 'konfirmasi', 'confirm', 'ya', 'oke', 'ok', 'setuju', 'betul', 'benar', 'siap', 'deal'])
            && ! $this->matchesCommand($lower, ['berapa', 'harga', 'ongkir', 'jam', 'tanggal']);
        if ($isConfirm) {
            $this->confirmOrder($conversation);
        } elseif ($this->matchesCommand($lower, ['batal', 'cancel', 'ubah', 'ganti', 'rubah'])) {
            if (str_contains($lower, 'alamat')) {
                $conversation->setContext('editing_address', true);
                $this->metaService->sendText($conversation->phone_number,
                    "Silakan kirim alamat pengiriman yang baru.\n(Ketik *BATAL* untuk membatalkan pesanan.)"
                );
            } else {
                $conversation->clearContext();
                $this->advanceState($conversation, OrderBotConversationState::MENU_SELECTION);
                $this->metaService->sendText(
                    $conversation->phone_number,
                    "🛑 Pesanan dibatalkan.\n\nBerikut pilihan kategori:"
                );
                $this->sendProductCategories($conversation);
            }
        } else {
            $this->metaService->sendText($conversation->phone_number,
                'Ketik *FIX* untuk konfirmasi, *UBAH ALAMAT* untuk koreksi alamat, atau *BATAL* untuk membatalkan.'
            );
        }
    }

    /**
     * Simpan alamat koreksi dari ORDER_SUMMARY, hitung ulang ongkir bila
     * memakai kurir internal, lalu tampilkan ringkasan terbaru.
     */
    protected function saveEditedAddress(WhatsAppConversation $conversation, string $newAddress): void
    {
        if ($newAddress === '' || $this->matchesCommand($newAddress, ['batal', 'cancel'])) {
            $conversation->setContext('editing_address', null);
            $this->metaService->sendText($conversation->phone_number,
                'Alamat tidak diubah.'
            );
            $this->sendOrderSummary($conversation);

            return;
        }

        $form = $conversation->getContext('order_form') ?? [];
        $form['recipient_address'] = $newAddress;
        $conversation->setContext('order_form', $form);
        $conversation->setContext('editing_address', null);
        $conversation->setContext('delivery_address', $newAddress);

        // Ongkir mengikuti alamat baru untuk kurir internal.
        if (($conversation->getContext('delivery_method') ?? null) === 'internal_courier') {
            $distance = $this->deliveryZoneService->estimateDistanceByAddress($newAddress, $conversation->region_id);
            $result = $this->deliveryZoneService->calculateOngkir($distance, $conversation->region_id);

            if ($result['needs_escalation']) {
                $conversation->clearContext();
                $conversation->update(['current_state' => OrderBotConversationState::ESCALATED_TO_HUMAN->value]);
                $regionName = $conversation->region?->name ?? config('services.whatsapp.default_region', 'cabang kami');
                $this->metaService->sendText($conversation->phone_number,
                    "Mohon maaf, alamat baru diperkirakan sekitar {$distance}km (di atas 14km).\n" .
                    "Silakan hubungi admin kami.\n\n" .
                    "WA Admin: hubungi admin terdekat di cabang {$regionName}"
                );
                $this->notifyAdminOfEscalation($conversation, $distance, $regionName);

                return;
            }

            $conversation->setContext('distance_km', $distance);
            $conversation->setContext('ongkir', $result['ongkir']);
        }

        $this->metaService->sendText($conversation->phone_number, '✅ Alamat diperbarui.');
        $this->sendOrderSummary($conversation);
    }

    protected function handlePaymentProof(WhatsAppConversation $conversation, string $text, ?array $messageData): void
    {
        if (isset($messageData['media_id'])) {
            // Unduhan dilakukan sekali di webhook job; bila gagal di sana, minta
            // kirim ulang (jangan unduh dobel di critical path yang dibatasi timeout).
            $mediaPath = $messageData['media_path'] ?? null;
            if ($mediaPath === null && ! array_key_exists('media_path', $messageData)) {
                $mediaPath = $this->metaService->downloadMedia($messageData['media_id']);
            }

            if ($mediaPath) {
                $orderId = $conversation->getContext('confirmed_order_id');
                $order = $orderId ? Order::with(['customer', 'region', 'items'])->find($orderId) : null;

                // Bukti tanpa order yang jelas JANGAN diakui sukses — sesat & bukti hilang.
                if (! $order) {
                    Log::channel('whatsapp')->warning('⚠️ Bukti bayar tanpa order (orphan)', [
                        'phone' => $conversation->phone_number,
                        'confirmed_order_id' => $orderId,
                        'media_path' => $mediaPath,
                    ]);
                    $this->metaService->sendText($conversation->phone_number,
                        "Bukti sudah kami terima filenya, tapi tidak terhubung ke pesanan aktif.\n".
                        'Balas dengan nomor invoice Anda, atau ketik *PESAN* untuk buat pesanan baru agar bukti bisa diverifikasi admin.'
                    );

                    return;
                }

                $order->update(['payment_proof' => $mediaPath]);

                $this->metaService->sendText($conversation->phone_number,
                    "✅ *Bukti Pembayaran Diterima*\n\n".
                    "Bukti pembayaran Anda sudah kami terima dan akan diverifikasi oleh admin.\n\n".
                    'Pesanan Anda akan segera diproses. Terima kasih! 🙏'
                );

                $this->advanceState($conversation, OrderBotConversationState::ORDER_CONFIRMED);

                // Teruskan bukti bayar ke WA owner cabang (gagal forward tidak menggagalkan alur)
                $this->notificationRouter->forwardPaymentProof($order, $mediaPath);
            } else {
                $this->metaService->sendText($conversation->phone_number,
                    'Mohon maaf, gagal menerima gambar. Silakan kirim ulang bukti pembayaran.'
                );
            }
        } else {
            $lower = strtolower(trim($text));
            if ($this->matchesCommand($lower, ['sudah transfer', 'transfer', 'bukti', 'bayar', 'pembayaran'])) {
                $this->metaService->sendText($conversation->phone_number,
                    'Silakan kirim *gambar* bukti transfer/pembayaran.'
                );
            } elseif ($this->matchesCommand($lower, ['skip', 'lewati', 'nanti'])) {
                $this->metaService->sendText($conversation->phone_number,
                    "✅ Pesanan Anda sudah tersimpan.\n".
                    "Silakan kirim bukti pembayaran kapan saja.\n\n".
                    'Terima kasih! 🙏'
                );
                $this->advanceState($conversation, OrderBotConversationState::ORDER_CONFIRMED);
            } else {
                $this->metaService->sendText($conversation->phone_number,
                    'Silakan kirim gambar bukti pembayaran, atau ketik *SKIP* untuk melanjutkan.'
                );
            }
        }
    }

    protected function notifyAdminOfEscalation(WhatsAppConversation $conversation, float $distance, string $regionName): void
    {
        try {
            $router = new AdminNotificationRouterService(app(WhatsappMetaService::class));
            // AdminNotificationRouterService has notifyNewOrder / notifyOrderStatusUpdate; 
            // escalation uses direct DB insert into admin_notifications (simplest correct path)
            \App\Models\AdminNotification::create([
                'user_id' => null,
                'region_id' => $conversation->region_id,
                'title' => 'Eskalasi jarak kirim (>14km)',
                'message' => "⚠️ Pesanan dari {$conversation->phone_number} dibatalkan (jarak {$distance}km > 14km). Cabang: {$regionName}.",
                'is_read' => false,
                'type' => 'escalated',
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::channel('whatsapp')->warning('Admin escalation notify failed', ['error' => $e->getMessage()]);
        }
    }

    protected function handleClosedConversation(WhatsAppConversation $conversation, string $text): void
    {
        // Restart PESAN/ORDER dari ESCALATED/ORDER_CONFIRMED ditangani global escape
        // (checkGlobalEscape) sebelum sampai sini — satu jalur kanonis, satu pesan.
        $this->metaService->sendText($conversation->phone_number,
            "Halo! 👋\n\n".
            'Ada yang bisa kami bantu? Ketik *PESAN* untuk membuat pesanan baru.'
        );
        $this->advanceState($conversation, OrderBotConversationState::WELCOME_SENT);
    }

    // ========== MESSAGE BUILDERS ==========

    /**
     * Kembalikan pertanyaan yang sedang ditunggu bot pada state saat ini,
     * agar user yang bingung tahu harus menjawab/apa.
     */
    protected function buildCurrentStepGuide(WhatsAppConversation $conversation): string
    {
        $context = $conversation->context ?? [];
        $formStep = $context['form_step'] ?? 0;
        $state = OrderBotConversationState::from($conversation->current_state);

        return match ($state) {
            OrderBotConversationState::WELCOME_SENT => $conversation->getContext('awaiting_branch')
                ? 'Pilih cabang pengiriman (ketik angka / nama cabang).'
                : 'Ketik *PESAN* untuk mulai order, atau *PRODUK* untuk melihat katalog.',
            OrderBotConversationState::AWAITING_ORDER_FORM => match ($formStep) {
                0 => 'Isi nama produk yang ingin dipesan (contoh: Tumpeng Mini, atau 2x Tumpeng Mini).',
                1 => 'Isi semua dalam 1 pesan dipisah | : Nama Penerima | Alamat Lengkap | Tanggal Kirim | Jam Tiba.',
                default => 'Pilih metode pengiriman (1 Diambil Sendiri / 2 Grab/GoSend / 3 Kurir Internal).',
            },
            OrderBotConversationState::MENU_SELECTION => 'Pilih kategori (Tumpeng / Hampers / Ala Carte) atau ketik nama produk.',
            OrderBotConversationState::PRODUCT_BROWSING => 'Ketik angka / nama produk untuk detail, *PESAN* untuk order, atau *MENU* untuk kategori.',
            OrderBotConversationState::AWAITING_DELIVERY_METHOD => 'Pilih metode pengiriman (1 Diambil Sendiri / 2 Grab/GoSend / 3 Kurir Internal).',
            OrderBotConversationState::AWAITING_LOCATION_OR_ADDRESS => 'Kirim lokasi GPS (pin lokasi) atau ketik alamat lengkap.',
            OrderBotConversationState::AWAITING_DELIVERY_SLOT => 'Pilih slot waktu pengiriman (1 / 2 / 3).',
            OrderBotConversationState::ORDER_SUMMARY => 'Ketik *FIX* untuk konfirmasi, *UBAH ALAMAT* untuk koreksi alamat, atau *BATAL* untuk membatalkan.',
            OrderBotConversationState::AWAITING_PAYMENT_PROOF => 'Kirim gambar bukti pembayaran, atau ketik *SKIP* untuk lanjut.',
            default => 'Lanjutkan percakapan sesuai pesan terakhir bot.',
        };
    }

    protected function sendWelcomeMessage(WhatsAppConversation $conversation): void
    {
        $regionName = $conversation->region->name ?? config('services.whatsapp.default_region');
        $hour = (int) now()->format('H');
        $greeting = match (true) {
            $hour >= 5 && $hour < 11 => 'Selamat pagi',
            $hour >= 11 && $hour < 15 => 'Selamat siang',
            $hour >= 15 && $hour < 18 => 'Selamat sore',
            default => 'Selamat malam',
        };

        $this->metaService->sendText($conversation->phone_number,
            "{$greeting} dari *Kue Pandan Asli* 🍃\n".
            "Cabang {$regionName}\n\n".
            "Kami menjual kue tradisional Indonesia dengan rasa pandan alami.\n".
            "Tanpa toko offline — hanya pesan online melalui WhatsApp ini.\n\n".
            "📍 Lokasi: {$regionName}\n\n".
            'Ketik *PESAN* untuk mulai order, *PRODUK* untuk katalog, atau *HARGA* untuk pricelist.'
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
            'Ketik nama kategori jika list tidak muncul'
        );
    }

    protected function sendProductCatalog(WhatsAppConversation $conversation): void
    {
        $regionId = $conversation->region_id;
        $products = Product::where('is_active', true)
            ->where(function ($q) use ($regionId) {
                $q->where('region_id', $regionId)->orWhereNull('region_id');
            })
            ->with(['variants' => fn ($q) => $q->where('is_active', true)])
            ->get();

        $text = "📋 *KATALOG PRODUK*\n\n";

        $grouped = $products->groupBy(fn ($p) => $p->category->name ?? 'Lainnya');

        // Nomor global berurutan lintas kategori + simpan urutan ID agar pilihan
        // angka selalu cocok dengan yang ditampilkan (tidak tergantung urutan DB).
        $catalogIds = [];
        $num = 0;
        foreach ($grouped as $category => $items) {
            $text .= "*{$category}*\n";
            foreach ($items as $product) {
                $num++;
                $catalogIds[] = $product->id;
                $variants = $product->variants;
                $text .= "{$num}. {$product->name}\n";
                foreach ($variants as $variant) {
                    $text .= "  └ {$variant->name}: Rp ".number_format($variant->price, 0, ',', '.')."\n";
                }
            }
            $text .= "\n";
        }
        $conversation->setContext('catalog_ids', $catalogIds);

        $text .= 'Ketik *angka* untuk lihat foto & detail, atau *PESAN* untuk mulai order.';

        $this->sendLongText($conversation, $text);
    }

    protected function sendProductsByCategory(WhatsAppConversation $conversation, string $categoryName): void
    {
        $regionId = $conversation->region_id;

        $category = \App\Models\Category::where('name', 'like', "%{$categoryName}%")->first();

        if (! $category) {
            $this->metaService->sendText($conversation->phone_number, "Kategori '{$categoryName}' tidak ditemukan.");

            return;
        }

        $products = Product::where('category_id', $category->id)
            ->where('is_active', true)
            ->where(function ($q) use ($regionId) {
                $q->where('region_id', $regionId)->orWhereNull('region_id');
            })
            ->with(['variants' => fn ($q) => $q->where('is_active', true)])
            ->get();

        if ($products->isEmpty()) {
            $this->metaService->sendText($conversation->phone_number, "Belum ada produk di kategori {$categoryName} untuk cabang Anda.");

            return;
        }

        $text = "📦 *{$categoryName}*\n\n";

        $catalogIds = [];
        foreach ($products as $index => $product) {
            $num = $index + 1;
            $catalogIds[] = $product->id;
            $text .= "{$num}. *{$product->name}*\n";
            $text .= mb_substr($product->description ?? '', 0, 200)."\n";
            if ($product->variants->where('is_active', true)->isEmpty()) {
                $text .= "  (varian belum tersedia — tanya admin untuk harga)\n";
            }
            foreach ($product->variants as $variant) {
                $text .= "  💰 {$variant->name}: Rp ".number_format($variant->price, 0, ',', '.')."\n";
            }
            $text .= "\n";
        }
        $conversation->setContext('catalog_ids', $catalogIds);

        $text .= 'Ketik *angka* untuk lihat foto & detail, atau *PESAN* untuk mulai order.';

        $this->sendLongText($conversation, $text);
    }

    protected function sendProductDetail(WhatsAppConversation $conversation, Product $product): void
    {
        // Kirim foto produk dulu bila tersedia (gagal kirim gambar tidak menggagalkan teks detail)
        if ($product->image_path) {
            try {
                $imageUrl = rtrim((string) config('app.url'), '/') . Storage::url($product->image_path);
                $cheapest = $this->resolveCheapestVariant($product);
                $caption = "*{$product->name}*"
                    . ($cheapest ? "\nMulai Rp " . number_format($cheapest->price, 0, ',', '.') : '');
                $sent = $this->metaService->sendImage($conversation->phone_number, $imageUrl, $caption);
                if (! $sent) {
                    Log::channel('whatsapp')->warning('⚠️ Gagal kirim foto produk, lanjut teks saja', [
                        'product_id' => $product->id,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::channel('whatsapp')->warning('⚠️ Exception kirim foto produk', [
                    'product_id' => $product->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $text = "*{$product->name}*\n\n";
        $text .= "{$product->description}\n\n";
        $text .= "Varian:\n";
        foreach ($product->variants->where('is_active', true) as $variant) {
            $text .= "• {$variant->name}: Rp ".number_format($variant->price, 0, ',', '.')."\n";
        }
        $text .= "\nKetik *PESAN* untuk order produk ini.";

        $this->metaService->sendText($conversation->phone_number, $text);
    }

    /**
     * Pricelist ringkas (nama — varian termurah) dikelompokkan per kategori,
     * di-scope per cabang percakapan. Tidak mengubah state.
     */
    protected function sendPricelist(WhatsAppConversation $conversation): void
    {
        $regionId = $conversation->region_id;
        $products = Product::where('is_active', true)
            ->where(function ($q) use ($regionId) {
                $q->where('region_id', $regionId)->orWhereNull('region_id');
            })
            ->with(['category', 'variants' => fn ($q) => $q->where('is_active', true)])
            ->get();

        $regionName = $conversation->region->name ?? config('services.whatsapp.default_region');
        $text = "💰 *PRICELIST — Cabang {$regionName}*\n\n";

        $grouped = $products->groupBy(fn ($p) => $p->category->name ?? 'Lainnya');

        foreach ($grouped as $category => $items) {
            $text .= "*{$category}*\n";
            foreach ($items as $product) {
                $cheapest = $this->resolveCheapestVariant($product);
                $priceText = $cheapest
                    ? 'Rp ' . number_format($cheapest->price, 0, ',', '.') . " ({$cheapest->name})"
                    : 'Harga menyusul';
                $text .= "• {$product->name} — {$priceText}\n";
            }
            $text .= "\n";
        }

        $text .= 'Ketik nama produk untuk lihat foto & detail, atau ketik *PESAN* untuk mulai order.';

        $this->sendLongText($conversation, $text);
    }

    /**
     * Kupas awalan pertanyaan ("apa itu X", "info X", "detail X") menjadi keyword produk.
     */
    protected function extractProductQuery(string $text): string
    {
        $cleaned = preg_replace('/^(apa\s+itu|apakah|apa|info|detail|tentang|kue\s+apa|produk\s+apa)\b[\s:,.?]*/iu', '', trim($text));
        $cleaned = trim((string) $cleaned, " \t\n\r\0\x0B:?.,");

        return $cleaned !== '' ? $cleaned : $text;
    }

    protected function askOrderForm(WhatsAppConversation $conversation): void
    {
        $selectedProduct = $conversation->getContext('selected_product_name');

        if ($selectedProduct) {
            // Produk sudah dipilih (via angka atau keyword) — langsung gabungan
            $conversation->setContext('order_form', ['product_name' => $selectedProduct]);
            $conversation->setContext('form_step', 1);

            $this->metaService->sendText($conversation->phone_number,
                "✅ Produk: *{$selectedProduct}*\n\n".
                "Langkah 2/2 — isi semua data pesanan dalam 1 pesan (pisahkan dengan |):\n".
                "Nama Penerima | Alamat Lengkap | Tanggal Kirim (10 Sept 2026) | Jam Tiba (10:00)"
            );
        } else {
            $conversation->setContext('form_step', 0);
            $conversation->setContext('order_form', []);

            $this->metaService->sendText($conversation->phone_number,
                "📝 *FORMULIR PESANAN*\n\n".
                "Langkah 1/2: Nama produk yang ingin dipesan?\n".
                '(tambah jumlah di depan bila >1, contoh: *2x Tumpeng Mini*)'
            );
        }
    }


    protected function processCombinedForm(WhatsAppConversation $conversation, string $text): void
    {
        $parts = array_map('trim', explode('|', $text));

        // Kelebihan segmen (mis. "|" di dalam alamat) digabung ke alamat.
        if (count($parts) > 4) {
            $parts = [$parts[0], implode(' | ', array_slice($parts, 1, -2)), $parts[count($parts) - 2], $parts[count($parts) - 1]];
        }

        $labels = ['Nama Penerima', 'Alamat Lengkap', 'Tanggal Kirim', 'Jam Tiba'];
        if (count($parts) < 4) {
            $this->metaService->sendText($conversation->phone_number,
                "❌ Format belum lengkap (butuh 4 bagian). Contoh:\nShinta | Jl. Mawar No 10 Surabaya | 12 Okt 2026 | 10:00"
            );

            return;
        }

        foreach (array_slice($parts, 0, 4) as $i => $value) {
            if ($value === '') {
                $this->metaService->sendText($conversation->phone_number,
                    "❌ *{$labels[$i]}* masih kosong. Contoh lengkap:\nShinta | Jl. Mawar No 10 Surabaya | 12 Okt 2026 | 10:00"
                );

                return;
            }
        }

        $formData = $conversation->getContext('order_form') ?? [];
        $formData['recipient_name'] = $parts[0];
        $formData['recipient_address'] = $parts[1];
        $formData['delivery_date'] = $parts[2];
        $formData['delivery_time'] = $parts[3];
        $conversation->setContext('order_form', $formData);
        $conversation->setContext('form_step', 2); // done
        $this->askDeliveryMethod($conversation);
    }
    protected function askDeliveryMethod(WhatsAppConversation $conversation): void
    {
        $this->advanceState($conversation, OrderBotConversationState::AWAITING_DELIVERY_METHOD);

        $this->metaService->sendReplyButtons(
            $conversation->phone_number,
            "🚚 *Pilih Metode Pengiriman*\n\n".
            "1️⃣ Diambil sendiri (gratis ongkir)\n".
            "2️⃣ Grab/GoSend (biaya ditanggung ke driver)\n".
            '3️⃣ Kurir internal Kue Pandan Asli',
            [
                ['id' => 'delivery_1', 'title' => '1. Diambil Sendiri'],
                ['id' => 'delivery_2', 'title' => '2. Grab/GoSend'],
                ['id' => 'delivery_3', 'title' => '3. Kurir Internal'],
            ],
            'Pilih opsi pengiriman'
        );
    }

    protected function sendDeliveryMethodOptions(WhatsAppConversation $conversation): void
    {
        $this->metaService->sendReplyButtons(
            $conversation->phone_number,
            'Pilih metode pengiriman:',
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
            "🕐 *Pilih Slot Waktu Pengiriman*\n\n".
            "1️⃣ 09:00 - 11:00\n".
            "2️⃣ 11:00 - 13:00\n".
            '3️⃣ 13:00 - 17:00',
            [
                ['id' => 'slot_1', 'title' => '09:00-11:00'],
                ['id' => 'slot_2', 'title' => '11:00-13:00'],
                ['id' => 'slot_3', 'title' => '13:00-17:00'],
            ],
            'Pilih slot waktu'
        );
    }

    protected function sendOrderSummary(WhatsAppConversation $conversation): void
    {
        $context = $conversation->context ?? [];
        $form = $context['order_form'] ?? [];

        // Jangan render/tagihkan ringkasan pincang — kembalikan ke langkah yang hilang.
        if (empty($context['delivery_method'])) {
            $this->askDeliveryMethod($conversation);

            return;
        }
        if (empty($context['delivery_slot'])) {
            $this->sendSlotOptions($conversation);

            return;
        }

        $productName = $form['product_name'] ?? '-';
        $recipientName = $form['recipient_name'] ?? '-';
        $address = $form['recipient_address'] ?? $context['delivery_address'] ?? '-';
        $date = $form['delivery_date'] ?? '-';
        $time = $form['delivery_time'] ?? '-';
        $slot = $context['delivery_slot'] ?? '-';
        $method = $context['delivery_method'] ?? '-';
        $ongkir = $context['ongkir'] ?? 0;

        // Resolve harga live dari database (selalu varian termurah — sama seperti penagihan)
        $productPrice = $context['product_price'] ?? null;
        $resolvedVariantName = null;
        if ($productPrice === null && $productName !== '-') {
            $resolvedProduct = $this->resolveProduct($conversation, $productName);
            $resolvedVariant = $this->resolveCheapestVariant($resolvedProduct);
            $productPrice = $resolvedVariant?->price ?? 0;
            $resolvedVariantName = $resolvedVariant?->name;
        }

        $quantity = $context['product_quantity'] ?? 1;
        $totalProduct = $productPrice * $quantity;
        $totalAll = $totalProduct + $ongkir;

        $methodText = match ($method) {
            'self_pickup' => 'Diambil Sendiri',
            'grab_gosend' => 'Grab/GoSend',
            'internal_courier' => 'Kurir Internal',
            default => $method,
        };

        $text = "📋 *RINGKASAN PESANAN*\n\n";
        $text .= "Produk: {$productName}\n";
        if ($resolvedVariantName) {
            $text .= "Varian: {$resolvedVariantName}\n";
        }
        $text .= "Qty: {$quantity}\n";
        $text .= 'Harga: Rp '.number_format($productPrice, 0, ',', '.')."\n";
        $text .= 'Subtotal Produk: Rp '.number_format($totalProduct, 0, ',', '.')."\n";
        $text .= "Ongkir ({$methodText}): Rp ".number_format($ongkir, 0, ',', '.')."\n";
        $text .= "─────────────────\n";
        $text .= '*TOTAL: Rp '.number_format($totalAll, 0, ',', '.')."*\n\n";
        $text .= "Penerima: {$recipientName}\n";
        $text .= "Alamat: {$address}\n";
        $text .= "Tanggal: {$date}\n";
        $text .= "Jam: {$time} (slot {$slot})\n\n";
        $text .= 'Ketik *FIX* untuk konfirmasi, atau *BATAL* untuk membatalkan.';

        $this->metaService->sendText($conversation->phone_number, $text);
    }

    protected function confirmOrder(WhatsAppConversation $conversation): void
    {
        $context = $conversation->context ?? [];
        $form = $context['order_form'] ?? [];

        // Guard: konteks tak lengkap (mis. data basi) — jangan buat order pincang.
        if (empty($context['delivery_method']) || empty($context['delivery_slot']) || empty($form['product_name'])) {
            $this->metaService->sendText($conversation->phone_number,
                'Data pesanan belum lengkap. Mari ulangi dari metode pengiriman.'
            );
            $this->askDeliveryMethod($conversation);

            return;
        }

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
                            // M3 FIX: do not send HTTP inside DB transaction — collect and send after
                            $quotaMsg = "⚠️ Kuota order aktif Anda sudah mencapai batas ({$maxOrders} order).\n".
                                'Silakan tunggu pesanan sebelumnya selesai atau hubungi admin.';
                            $conversation->update(['context' => array_merge($conversation->context ?? [], ['_pending_msg' => $quotaMsg])]);
                            return null;
                        }
                    }
                }

                $product = $this->resolveProduct($conversation, $form['product_name'] ?? '');
                if (! $product) {
                    $conversation->update(['context' => array_merge($conversation->context ?? [], ['_pending_msg' => "❌ Produk *{$form['product_name']}* tidak ditemukan.\nSilakan ketik *MENU* untuk memilih kategori ulang, atau ketik nama produk yang tersedia."]) ]);
                    return null;
                }
                $variant = $this->resolveCheapestVariant($product);

                // M4 FIX: reject orders with no active variant (would bill Rp 0)
                if (! $variant) {
                    $conversation->update(['context' => array_merge($conversation->context ?? [], ['_pending_msg' => "❌ Produk *{$product->name}* tidak memiliki varian aktif yang tersedia.\nSilakan ketik *MENU* untuk memilih produk lain."]) ]);
                    return null;
                }

                $subtotal = $variant->price * ($context['product_quantity'] ?? 1);

                $order = Order::create([
                    'invoice_number' => $this->generateInvoiceNumber($conversation),
                    'customer_id' => $customer->id,
                    'phone' => $conversation->phone_number,
                    'address' => $form['recipient_address'] ?? $context['delivery_address'] ?? '',
                    'total_amount' => $subtotal + ($context['ongkir'] ?? 0),
                    'payment_method' => 'qris',
                    'note' => 'Penerima: ' . ($form['recipient_name'] ?? '-') . "\n".
                              'Tanggal kirim: ' . ($form['delivery_date'] ?? '-') . "\n".
                              'Jam kirim: ' . ($form['delivery_time'] ?? '-') . "\n".
                              'Slot: ' . ($context['delivery_slot'] ?? '-') . "\n".
                              'Metode: ' . ($context['delivery_method'] ?? '-') . "\n".
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
                        'price' => $variant?->price ?? 0,
                        'subtotal' => $subtotal,
                    ]);
                }

                return $order;
            });

            // M3 FIX: drain any pending messages collected inside the transaction (quota / missing product / missing variant)
            $pending = $conversation->getContext('_pending_msg');
            if ($pending) {
                $this->metaService->sendText($conversation->phone_number, $pending);
                $conversation->setContext('_pending_msg', null);
            }

            if (! $order) {
                return;
            }

            $conversation->setContext('confirmed_order_id', $order->id);
            $this->advanceState($conversation, OrderBotConversationState::AWAITING_PAYMENT_PROOF);

            // Notify admin immediately so the new order is never missed
            $this->notificationRouter->notifyNewOrder($order->id);

            $this->metaService->sendText($conversation->phone_number,
                "✅ *Pesanan Berhasil Dibuat!*\n\n".
                "Nomor Invoice: *{$order->invoice_number}*\n".
                'Total: *Rp '.number_format($order->total_amount, 0, ',', '.')."*\n\n".
                "Silakan lakukan pembayaran melalui QRIS, lalu kirim bukti transfer di sini.\n".
                'Atau ketik *SKIP* untuk melanjutkan tanpa mengirim bukti.'
            );

        } catch (\Exception $e) {
            Log::channel('whatsapp')->error('❌ Failed to confirm order', [
                'phone' => $conversation->phone_number,
                'error' => $e->getMessage(),
            ]);

            $this->metaService->sendText($conversation->phone_number,
                '❌ Terjadi kesalahan saat membuat pesanan. Silakan coba lagi atau hubungi admin.'
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
        $text = strtolower(trim($text));
        foreach ($keywords as $keyword) {
            $keyword = strtolower(trim($keyword));
            // Use word boundaries for short keywords (greetings, commands) to avoid false positives
            if (strlen($keyword) <= 3) {
                if (preg_match('/\b' . preg_quote($keyword, '/') . '\b/i', $text)) {
                    return true;
                }
            } else {
                if (str_contains($text, $keyword)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Pencocokan perintah berbasis kata utuh (case-insensitive) untuk SEMUA
     * panjang keyword. "Jl. Batalyon" tidak memicu BATAL, "menunggu" tidak
     * memicu MENU, "produksi" tidak memicu PRODUK, "assalamualaikum, Shinta"
     * tidak memicu HALO. Dipakai untuk semua perintah global/konfirmasi.
     */
    protected function matchesCommand(string $text, array $keywords): bool
    {
        $text = strtolower(trim($text));
        foreach ($keywords as $keyword) {
            $keyword = strtolower(trim($keyword));
            if ($keyword === '') {
                continue;
            }
            $words = preg_split('/\s+/', $keyword) ?: [];
            $quoted = array_map(fn ($w) => preg_quote($w, '/'), $words);
            if (preg_match('/\b' . implode('\s+', $quoted) . '\b/iu', $text)) {
                return true;
            }
        }

        return false;
    }

    protected function detectCityMention(string $text): ?string
    {
        $aliases = [
            'suroboyo' => 'Surabaya',
            'kota pahlawan' => 'Surabaya',
            'jawa timur' => 'Jawa Timur',
            'jatim' => 'Jawa Timur',
        ];

        foreach ($aliases as $keyword => $city) {
            if ($this->containsWholeWord($text, $keyword)) {
                return $city;
            }
        }

        foreach (Region::all() as $region) {
            foreach ([$region->slug, $region->name] as $needle) {
                if ($this->containsWholeWord($text, (string) $needle)) {
                    return (string) $region->name;
                }
            }
        }

        // Kota/area layanan dari tabel delivery_zones (Sidoarjo, Gresik, Batu,
        // Singosari, Lawang, Kuta, Ubud, Sanur, ...) → cabang pelayan.
        $bestRegion = null;
        $bestLength = 0;
        $zones = DeliveryZone::active()->with('region')->get();
        foreach ($zones as $zone) {
            if (! $zone->region) {
                continue;
            }
            $rawTokens = array_merge(
                preg_split('/[,\/;\n]+/', (string) $zone->landmark_keyword) ?: [],
                [(string) $zone->area_name]
            );
            foreach ($rawTokens as $raw) {
                $token = trim((string) $raw);
                if (mb_strlen($token) < 4) {
                    continue;
                }
                if (mb_strlen($token) > $bestLength && $this->containsWholeWord($text, $token)) {
                    $bestLength = mb_strlen($token);
                    $bestRegion = (string) $zone->region->name;
                }
            }
        }

        return $bestRegion;
    }

    protected function containsWholeWord(string $text, string $word): bool
    {
        $word = trim($word);
        if ($word === '') {
            return false;
        }

        return preg_match('/\b'.preg_quote($word, '/').'\b/i', $text) === 1;
    }

    protected function handleCityMention(WhatsAppConversation $conversation, string $city): void
    {
        $slug = strtolower(trim($city));
        $regionName = trim((string) ($conversation->region?->name ?? ''));
        $regionSlug = strtolower(trim((string) ($conversation->region?->slug ?? '')));
        $regionLabel = $regionName !== '' ? $regionName : 'cabang kami';

        $conversation->setContext('customer_city', $city);

        // Kota sama persis dengan cabang saat ini
        if ($slug !== '' && $regionSlug !== '' && $slug === $regionSlug) {
            $this->metaService->sendText($conversation->phone_number,
                "Benar! Kami dari cabang *{$regionLabel}* 😊\n\n".
                'Mau lihat menu, cek harga, cek ongkir, atau mulai pesanan?'
            );

            return;
        }

        // Wilayah payung (provinsi), pasti dilayani salah satu cabang
        if ($slug === 'jawa timur') {
            $this->metaService->sendText($conversation->phone_number,
                "Siap, kami melayani pengiriman di area *Jawa Timur* dari beberapa cabang 😊\n\n".
                "Nomor ini melayani area *{$regionLabel}*. Mau lihat menu, cek harga, atau mulai pesanan?"
            );

            return;
        }

        // Kota masuk area delivery cabang saat ini
        if ($this->regionServesCity($conversation, $slug)) {
            $this->metaService->sendText($conversation->phone_number,
                "Siap, area *{$city}* kami layani dari cabang *{$regionLabel}* 😊\n\n".
                'Mau lihat menu, cek harga, cek ongkir, atau mulai pesanan?'
            );

            return;
        }

        // Kota dilayani cabang lain
        $servingRegion = $this->findRegionServingCity($slug);
        if ($servingRegion !== null) {
            $this->metaService->sendText($conversation->phone_number,
                "Mohon info ya 😊 Nomor ini melayani area *{$regionLabel}*.\n".
                "Untuk pengiriman ke area *{$city}*, silakan hubungi admin cabang *{$servingRegion}*.\n\n".
                "Ada yang bisa kami bantu untuk area *{$regionLabel}*?"
            );

            return;
        }

        $this->metaService->sendText($conversation->phone_number,
            "Mohon info ya 😊 Nomor ini melayani area *{$regionLabel}*.\n".
            "Untuk pengiriman ke area *{$city}*, silakan hubungi admin kami.\n\n".
            'Ada yang bisa kami bantu?'
        );
    }

    protected function regionServesCity(WhatsAppConversation $conversation, string $slug): bool
    {
        return DeliveryZone::active()
            ->forRegion($conversation->region_id)
            ->get()
            ->contains(function (DeliveryZone $zone) use ($slug) {
                return str_contains(strtolower((string) $zone->landmark_keyword), $slug)
                    || str_contains(strtolower((string) $zone->area_name), $slug);
            });
    }

    protected function findRegionServingCity(string $slug): ?string
    {
        $zone = DeliveryZone::active()
            ->with('region')
            ->get()
            ->first(function (DeliveryZone $zone) use ($slug) {
                return str_contains(strtolower((string) $zone->landmark_keyword), $slug)
                    || str_contains(strtolower((string) $zone->area_name), $slug);
            });

        return $zone?->region?->name;
    }

    protected function findProductByKeyword(WhatsAppConversation $conversation, string $keyword): ?Product
    {
        $regionId = $conversation->region_id;
        $lowerKeyword = strtolower(trim($keyword));

        if ($lowerKeyword === '') {
            return null;
        }

        $query = Product::where('is_active', true)
            ->where(function ($q) use ($regionId) {
                $q->where('region_id', $regionId)->orWhereNull('region_id');
            })
            ->with(['variants' => fn ($q) => $q->where('is_active', true)]);

        // Prioritas: nama/tag persis dulu, baru partial
        $exactMatch = (clone $query)
            ->where(function ($q) use ($regionId, $lowerKeyword) {
                $q->where('region_id', $regionId)->orWhereNull('region_id');
            })
            ->where(function ($q) use ($lowerKeyword) {
                $q->whereRaw("LOWER(name) = ?", [$lowerKeyword])
                  ->orWhereRaw("LOWER(tag) = ?", [$lowerKeyword]);
            })
            ->first();

        if ($exactMatch) {
            return $exactMatch;
        }

        $partialMatch = $query
            ->where(function ($q) use ($lowerKeyword) {
                $q->whereRaw("LOWER(name) LIKE ?", ["%{$lowerKeyword}%"])
                  ->orWhereRaw("LOWER(tag) LIKE ?", ["%{$lowerKeyword}%"])
                  ->orWhereRaw("LOWER(description) LIKE ?", ["%{$lowerKeyword}%"]);
            })
            ->first();

        return $partialMatch;
    }

    protected function findOrCreateCustomer(WhatsAppConversation $conversation): Customer
    {
        $phone = Phone::normalize($conversation->phone_number);

        $customer = Customer::where('phone', $phone)->first();

        if (! $customer) {
            $form = $conversation->context['order_form'] ?? [];
            $customer = Customer::create([
                'name' => $form['recipient_name'] ?? $conversation->profile_name ?? 'Customer WA',
                'phone' => $phone,
                'address' => $form['recipient_address'] ?? '',
                'region_id' => $conversation->region_id,
                'added_by_user_id' => null,
            ]);
        } else {
            // Pelanggan lama: segarkan alamat + cabang dari pesanan terbaru
            // (nama dipertahankan — identitas pemesan via nomor WA).
            $form = $conversation->context['order_form'] ?? [];
            $updates = [];
            if (! empty($form['recipient_address']) && $form['recipient_address'] !== $customer->address) {
                $updates['address'] = $form['recipient_address'];
            }
            if ($conversation->region_id && $conversation->region_id !== $customer->region_id) {
                $updates['region_id'] = $conversation->region_id;
            }
            if ($updates !== []) {
                $customer->update($updates);
            }
        }

        if (! $conversation->customer_id) {
            $conversation->update(['customer_id' => $customer->id]);
        }

        return $customer;
    }

    protected function resolveProduct(WhatsAppConversation $conversation, string $productName): ?Product
    {
        $regionId = $conversation->region_id;
        $keyword = trim($productName);

        if ($keyword === '') {
            return null;
        }

        // Hindari wildcard LIKE dari input user (%/_) agar tak cocok ke semua produk
        $escaped = addcslashes($keyword, '%_\\');
        $lowerKeyword = strtolower($keyword);

        $base = Product::where('is_active', true)
            ->where(function ($q) use ($regionId) {
                $q->where('region_id', $regionId)->orWhereNull('region_id');
            })
            ->with(['variants' => fn ($q) => $q->where('is_active', true)]);

        // Prioritas: nama/tag persis (seperti findProductByKeyword), lalu partial deterministik
        $exact = (clone $base)
            ->where(function ($q) use ($lowerKeyword) {
                $q->whereRaw('LOWER(name) = ?', [$lowerKeyword])
                  ->orWhereRaw('LOWER(tag) = ?', [$lowerKeyword]);
            })
            ->first();

        if ($exact) {
            return $exact;
        }

        return (clone $base)
            ->where(function ($q) use ($escaped) {
                $q->where('name', 'like', "%{$escaped}%")
                  ->orWhere('tag', 'like', "%{$escaped}%");
            })
            ->orderByRaw('LENGTH(name) ASC')
            ->orderBy('id')
            ->first();
    }

    /**
     * Satu-satunya sumber varian termurah yang aktif — dipakai ringkasan,
     * konfirmasi, detail, dan pricelist agar harga yang ditampilkan SELALU
     * sama dengan yang ditagihkan.
     */
    protected function resolveCheapestVariant(?Product $product): ?ProductVariant
    {
        if (! $product) {
            return null;
        }

        return $product->variants->where('is_active', true)->sortBy('price')->first();
    }

    protected function generateInvoiceNumber(WhatsAppConversation $conversation): string
    {
        $now = now();
        $ddmm = $now->format('dm');
        $regionId = str_pad($conversation->region_id ?? 1, 2, '0', STR_PAD_LEFT);
        $courierId = '000';
        $customerId = str_pad($conversation->customer_id ?? 0, 3, '0', STR_PAD_LEFT);

        // Kunci baris agar dua konfirmasi bersamaan tak menghasilkan nomor kembar
        // (invoice_number unik di DB). Dipanggil di dalam DB::transaction().
        $dailyCount = Order::where('region_id', $conversation->region_id)
            ->whereDate('created_at', $now->toDateString())
            ->lockForUpdate()
            ->count() + 1;

        $sequence = str_pad($dailyCount, 3, '0', STR_PAD_LEFT);

        return "INV/{$ddmm}/{$regionId}/{$courierId}/{$customerId}/{$sequence}";
    }

    /**
     * Kirim teks panjang dengan aman (batas Meta 4096 karakter): potong per
     * baris menjadi beberapa pesan bila melebihi ~3500 karakter.
     */
    protected function sendLongText(WhatsAppConversation $conversation, string $text, int $chunkSize = 3500): void
    {
        if (mb_strlen($text) <= $chunkSize) {
            $this->metaService->sendText($conversation->phone_number, $text);

            return;
        }

        $chunks = [];
        $current = '';
        foreach (explode("\n", $text) as $line) {
            if (mb_strlen($current) + mb_strlen($line) + 1 > $chunkSize) {
                $chunks[] = $current;
                $current = '';
            }
            $current .= ($current === '' ? '' : "\n") . $line;
        }
        if ($current !== '') {
            $chunks[] = $current;
        }

        // Baris tunggal super-panjang tetap dipotong paksa agar terkirim
        foreach ($chunks as $chunk) {
            foreach (mb_str_split($chunk, $chunkSize) as $part) {
                $this->metaService->sendText($conversation->phone_number, $part);
            }
        }
    }

    protected function sendHelpMessage(WhatsAppConversation $conversation): void
    {
        $this->metaService->sendText($conversation->phone_number,
            "Halo! 👋 Ada yang bisa kami bantu?\n\n".
            "Ketik *PESAN* untuk membuat pesanan\n".
            "Ketik *PRODUK* untuk melihat katalog\n".
            "Ketik *HARGA* untuk pricelist\n".
            "Ketik *BANTUAN* untuk bantuan\n\n".
            'Atau langsung ketik produk yang Anda inginkan.'
        );
    }
}
