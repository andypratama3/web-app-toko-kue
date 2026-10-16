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
            OrderBotConversationState::AWAITING_ORDER_FORM => $this->handleOrderForm($conversation, $messageText, $messageData),
            OrderBotConversationState::AWAITING_DELIVERY_METHOD => $this->handleDeliveryMethod($conversation, $messageText),
            OrderBotConversationState::AWAITING_LOCATION_OR_ADDRESS => $this->handleLocationOrAddress($conversation, $messageText, $messageData),
            OrderBotConversationState::AWAITING_DELIVERY_SLOT => $this->handleDeliverySlot($conversation, $messageText),
            OrderBotConversationState::ORDER_SUMMARY => $this->handleOrderSummary($conversation, $messageText),
            OrderBotConversationState::AWAITING_PAYMENT_PROOF => $this->handlePaymentProof($conversation, $messageText, $messageData),
            OrderBotConversationState::ORDER_CONFIRMED, OrderBotConversationState::CLOSED, OrderBotConversationState::ESCALATED_TO_HUMAN => $this->handleClosedConversation($conversation, $messageText, $messageData),
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
        } elseif ($state === OrderBotConversationState::ORDER_SUMMARY
            && $conversation->getContext('editing_address')
            && isset($locationData['latitude'], $locationData['longitude'])
            && is_numeric($locationData['latitude']) && is_numeric($locationData['longitude'])
        ) {
            // Koreksi alamat via pin GPS saat UBAH ALAMAT (bukan teks).
            $this->saveEditedPinAddress(
                $conversation, (float) $locationData['latitude'], (float) $locationData['longitude']
            );
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
            $snapshot = $this->snapshotOrderDetails($conversation);
            $conversation->clearContext();
            $conversation->update(['current_state' => OrderBotConversationState::ESCALATED_TO_HUMAN->value]);
            $regionName = $conversation->region?->name ?? config('services.whatsapp.default_region', 'cabang kami');
            $this->metaService->sendText($conversation->phone_number,
                "Mohon maaf, jarak pengiriman Anda sekitar {$distance}km dari toko kami.\n".
                "Untuk jarak di atas 14km, silakan hubungi admin kami untuk detail pengiriman.\n\n".
                "WA Admin: hubungi admin terdekat di cabang {$regionName}"
            );
            $this->notifyAdminOfEscalation($conversation, $distance, $regionName, $snapshot ?? '');

            return;
        }

        $conversation->setContext('distance_km', $distance);
        $conversation->setContext('ongkir', $result['ongkir']);
        $conversation->setContext('delivery_address', "Lokasi GPS ({$lat}, {$lng})");
        $conversation->setContext('pending_location', null);
        $conversation->setContext('location_done', true);

        // Kembali ke pilih produk bila lokasi diminta di tengah order.
        if ($conversation->getContext('after_location') === 'product') {
            $conversation->setContext('after_location', null);
            $this->advanceState($conversation, OrderBotConversationState::AWAITING_ORDER_FORM);
            $this->askOrderForm($conversation);

            return;
        }

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

        // Jawaban field bebas (nama/alamat/tanggal) harus MENANG atas escape
        // MENU/PRODUK/HALO/PESAN: "Jl. Kembali", "Warung Menu", "Halo, saya Budi"
        // adalah isi field, bukan perintah. BATAL tetap selalu bisa.
        $isFieldAnswerStep = $state === OrderBotConversationState::AWAITING_ORDER_FORM
            && in_array($conversation->getContext('form_step'), [1, 2, 3], true);

        // BATAL / CANCEL — kata utuh saja ("Batalyon" tidak ikut batal).
        // Berlaku juga pasca-SKIP (ORDER_CONFIRMED) agar ghost order bisa dibatalkan user.
        if (in_array($state, array_merge($orderFlowStates, [
            OrderBotConversationState::PRODUCT_BROWSING,
            OrderBotConversationState::ORDER_CONFIRMED,
        ]), true)
            && $this->matchesCommand($lower, ['batal', 'cancel', 'batalkan', 'batalin'])
        ) {
            // Anti ghost order: batal pasca-FIX membatalkan order 'baru' yang
            // belum dibayar (kalau sudah diproses kurir/admin, biarkan).
            $this->cancelFreshUnpaidOrder($conversation);
            $conversation->clearContext();
            $this->advanceState($conversation, OrderBotConversationState::MENU_SELECTION);
            $this->metaService->sendText(
                $conversation->phone_number,
                "🛑 Pesanan dibatalkan.\n\nBerikut pilihan kategori:"
            );
            $this->sendProductCategories($conversation);

            return true;
        }

        // MENU / KEMBALI — kata utuh saja ("menunggu" tidak ikut ke menu).
        // Dilewati saat menjawab field bebas (langkah 1-3).
        if (! $isFieldAnswerStep
            && in_array($state, $orderFlowStates, true)
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
        $isFormFieldAnswer = $isFieldAnswerStep
            || ($conversation->getContext('form_step') !== null
            && (
                stripos($lower, 'alamat') !== false
                || stripos($lower, 'nama') !== false
                || stripos($lower, 'tangg') !== false
                || stripos($lower, 'jam') !== false
            ));
        // Perintah restart yang diketik persis (bukan bagian alamat) tetap
        // boleh mengulang walau di tengah isian field.
        $isExactRestart = in_array($lower, ['pesan', 'order', 'ordering', 'mau pesan', 'mau order'], true);
        if ($canRestart
            && (! $isFormFieldAnswer || $isExactRestart)
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

        // PRODUK / KATALOG — kata utuh saja ("produksi" bukan perintah).
        // Dilewati saat menjawab field bebas.
        if (! $isFieldAnswerStep
            && in_array($state, $orderFlowStates, true)
            && $this->matchesCommand($lower, ['lihat produk', 'produk', 'katalog', 'catalog', 'kategori'])
        ) {
            $conversation->clearContext();
            $this->advanceState($conversation, OrderBotConversationState::PRODUCT_BROWSING);
            $this->sendProductCatalog($conversation);

            return true;
        }

        // HALO / BANTUAN — kata utuh saja ("assalamualaikum, Shinta" lolos ke form).
        // Dilewati saat menjawab field bebas ("Halo, saya Budi" = nama).
        if (! $isFieldAnswerStep
            && in_array($state, $orderFlowStates, true)
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
            "Halo" . ($conversation->profile_name ? " *{$conversation->profile_name}*" : "") . " 👋\n\n".
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
        // Hanya cabang aktif (Denpasar dinonaktifkan).
        return Region::active()->orderBy('name')->get();
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
        $regions = Region::active()->orderBy('name')->get();
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
        $conversation->setContext('after_location', 'product');
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

        $regions = Region::active()->orderBy('name')->get();

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
        $conversation->setContext('after_location', 'product');
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

        // Produk dulu: "kue ijo" / "tumpeng mini" langsung ke detail, bukan
        // tersedot intent kategori ("kue" → Produk, "tumpeng" → Tumpeng).
        $candidates = $this->searchProductsByKeyword($conversation, $this->extractProductQuery($lower));
        if ($candidates->count() === 1) {
            $product = $candidates->first();
            $conversation->setContext('selected_product_name', $product->name);
            $conversation->setContext('selected_category', $product->category->name ?? null);
            $this->advanceState($conversation, OrderBotConversationState::PRODUCT_BROWSING);
            $this->sendProductDetail($conversation, $product);

            return;
        }
        if ($candidates->count() > 1
            && $candidates->pluck('category.name')->filter()->unique()->count() === 1
        ) {
            // Mis. "mix" → semua di kategori Produk: tampilkan kategorinya.
            $categoryName = $candidates->first()->category?->name;
            if ($categoryName) {
                $this->sendProductsByCategory($conversation, $categoryName);
                $this->advanceState($conversation, OrderBotConversationState::PRODUCT_BROWSING);
                $conversation->setContext('selected_category', $categoryName);

                return;
            }
        }

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

                // Pilihan angka polos = qty 1 (reset sisa qty dari pilihan sebelumnya).
                $conversation->setContext('product_quantity', 1);
                $conversation->forgetContext('qty_note');
            } else {
                // Dukung "2x Tumpeng Mini" + pertanyaan bebas ("apa itu ...", "info ...").
                [$rawBrowsingQty, $browsingQuery] = $this->extractQuantity($this->extractProductQuery($lower));
                [$browsingQty, $browsingNote] = $this->normalizeQuantity($rawBrowsingQty);
                $product = $browsingQuery === ''
                    ? null
                    : $this->findProductByKeyword($conversation, $browsingQuery);
                if ($product) {
                    $conversation->setContext('product_quantity', $browsingQty);
                    if ($browsingNote !== '') {
                        $conversation->setContext('qty_note', $browsingNote);
                    }
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

    protected function handleOrderForm(WhatsAppConversation $conversation, string $text, ?array $messageData = null): void
    {
        // Foto di tengah form (mis. foto kue yang diinginkan) tidak bisa
        // dikenali — arahkan baik-baik, jangan telan diam-diam.
        if (! empty($messageData['media_id'])) {
            $this->metaService->sendText($conversation->phone_number,
                "Maaf, foto tidak bisa kami kenali isinya. 🙏\n\n".
                $this->buildCurrentStepGuide($conversation)
            );

            return;
        }

        $context = $conversation->context ?? [];
        // Prioritas: user sedang memilih dari daftar bernomor (produk/varian).
        if (! empty($conversation->getContext('pending_options'))) {
            $this->processOptionStep($conversation, $text);

            return;
        }

        $formStep = $context['form_step'] ?? 0;

        // Alur sederhana (ramah 30+): 0 produk → 1 nama → 2 alamat → 3 tanggal → 4 tambah/LANJUT.
        // Tanpa format pipa, satu pertanyaan per pesan.
        match ($formStep) {
            0 => $this->processProductNameStep($conversation, $text),
            1 => $this->processNameStep($conversation, $text),
            2 => $this->processAddressStep($conversation, $text),
            3 => $this->processDateStep($conversation, $text),
            4 => $this->processAddMoreStep($conversation, $text),
            default => $this->resumeOrderForm($conversation),
        };
    }

    /**
     * Langkah 1: nama produk (+ jumlah opsional, mis. "2x Tumpeng Mini").
     *
     * Produk divalidasi LANGSUNG di sini (tidak menunggu akhir alur) agar
     * salah ketik langsung ketahuan. Bila cocok >1 produk / >1 varian,
     * user diminta memilih dari daftar bernomor.
     */
    protected function processProductNameStep(WhatsAppConversation $conversation, string $text): void
    {
        [$rawQty, $productName] = $this->extractQuantity($text);

        if ($productName === '') {
            $this->metaService->sendText($conversation->phone_number,
                'Nama produk belum terisi. Balas *nomor / nama* produknya, mis. *1* atau *2x Kue Ijo*.'
            );

            return;
        }

        [$quantity, $qtyNote] = $this->normalizeQuantity($rawQty);
        if ($qtyNote !== '') {
            $conversation->setContext('qty_note', $qtyNote);
        } else {
            $conversation->forgetContext('qty_note');
        }

        // Jawaban angka = nomor dari katalog yang baru dikirim.
        if (ctype_digit($productName)) {
            $catalogIds = $conversation->getContext('catalog_ids') ?? [];
            $productId = $catalogIds[(int) $productName - 1] ?? null;
            $regionId = $conversation->region_id;
            $product = $productId
                ? Product::where('is_active', true)
                    ->where(fn ($q) => $q->where('region_id', $regionId)->orWhereNull('region_id'))
                    ->with(['variants' => fn ($q) => $q->where('is_active', true)])
                    ->find($productId)
                : null;

            if (! $product) {
                $this->metaService->sendText($conversation->phone_number,
                    'Nomor tidak ada di daftar. Balas *nomor / nama* produknya ya.'
                );

                return;
            }

            $this->selectProductFlow($conversation, $product, $quantity);

            return;
        }

        $candidates = $this->searchProductsByKeyword($conversation, $productName);

        if (count($candidates) === 0) {
            $this->metaService->sendText($conversation->phone_number,
                "❌ Produk *{$productName}* tidak ditemukan.\n".
                "Coba ketik nama lain (mis. *Kue Ijo*, *Hampers B*), atau ketik *PRODUK* untuk melihat katalog."
            );

            return;
        }

        if (count($candidates) > 1) {
            $options = $candidates->map(fn ($p) => [
                'type' => 'product',
                'id' => $p->id,
                'label' => $p->name,
                'price' => $this->resolveCheapestVariant($p)?->price,
            ])->values()->all();
            $conversation->setContext('pending_options', $options);
            $conversation->setContext('product_quantity', $quantity);
            $this->sendOptionList($conversation, 'Ditemukan beberapa produk yang cocok. Pilih salah satu:',
                'Ketik angka pilihan Anda, atau *KEMBALI* untuk pilih ulang.');

            return;
        }

        $this->selectProductFlow($conversation, $candidates->first(), $quantity);
    }

    /**
     * Cari kandidat produk (aktif, sesuai region) untuk kata kunci.
     * Urut: nama persis dulu, lalu nama terpendek (paling spesifik).
     *
     * @return \Illuminate\Support\Collection<int, Product>
     */
    protected function searchProductsByKeyword(WhatsAppConversation $conversation, string $keyword)
    {
        $regionId = $conversation->region_id;
        $keyword = trim($keyword);

        if ($keyword === '') {
            return collect();
        }

        // Hindari wildcard LIKE dari input user (%/_) agar tak cocok ke semua produk.
        $escaped = addcslashes($keyword, '%_\\');
        $lowerKeyword = strtolower($keyword);

        $base = Product::where('is_active', true)
            ->where(function ($q) use ($regionId) {
                $q->where('region_id', $regionId)->orWhereNull('region_id');
            })
            ->with(['category', 'variants' => fn ($q) => $q->where('is_active', true)->orderBy('price')]);

        $exact = (clone $base)
            ->where(function ($q) use ($lowerKeyword) {
                $q->whereRaw('LOWER(name) = ?', [$lowerKeyword])
                  ->orWhereRaw('LOWER(tag) = ?', [$lowerKeyword]);
            })
            ->get();

        if ($exact->isNotEmpty()) {
            return $exact->take(6);
        }

        return (clone $base)
            ->where(function ($q) use ($escaped) {
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$escaped}%"])
                  ->orWhereRaw('LOWER(tag) LIKE ?', ["%{$escaped}%"]);
            })
            ->orderByRaw('LENGTH(name) ASC')
            ->orderBy('id')
            ->limit(6)
            ->get();
    }

    /**
     * Produk sudah pasti ada: simpan pilihan, lalu minta pilih varian
     * bila variannya lebih dari 1. Selalu set ulang jumlah (anti-bocor
     * sisa qty dari produk sebelumnya).
     */
    protected function selectProductFlow(WhatsAppConversation $conversation, Product $product, int $quantity = 1): void
    {
        $quantity = max(1, min(100, $quantity));
        $conversation->setContext('selected_product_id', $product->id);
        $conversation->setContext('selected_product_name', $product->name);
        $conversation->setContext('product_quantity', $quantity);
        // Pilihan varian lama (produk sebelumnya) tidak berlaku lagi.
        $conversation->forgetContext('selected_variant_id');

        $variants = $product->variants->where('is_active', true)->sortBy('price')->values();

        if ($variants->isEmpty()) {
            // Bersihkan pilihan agar sesi tidak menunjuk produk mati.
            $conversation->forgetContext('selected_product_id');
            $conversation->forgetContext('selected_product_name');
            $this->metaService->sendText($conversation->phone_number,
                "❌ Produk *{$product->name}* sedang tidak tersedia (tidak ada varian aktif).\n".
                'Silakan pilih produk lain, atau ketik *PRODUK* untuk melihat katalog.'
            );

            return;
        }

        if ($variants->count() > 1) {
            $options = $variants->map(fn ($v) => [
                'type' => 'variant',
                'id' => $v->id,
                'label' => $v->name,
                'price' => $v->price,
            ])->all();
            $conversation->setContext('pending_options', $options);
            $qtyText = $quantity > 1 ? " ({$quantity} buah)" : '';
            $this->sendOptionList($conversation, "✅ Produk: *{$product->name}*{$qtyText}\nPilih ukuran/varian:",
                'Ketik angka pilihan Anda, atau *KEMBALI* untuk pilih ulang.');

            return;
        }

        $variant = $variants->first();
        $this->addToCart($conversation, $product, $variant, $quantity);
    }

    /**
     * Masukkan pilihan ke keranjang, lalu tawarkan tambah produk / lanjut.
     * Satu-satunya pintu masuk keranjang (varian tunggal maupun hasil pilih).
     */
    protected function addToCart(WhatsAppConversation $conversation, Product $product, ProductVariant $variant, int $quantity): void
    {
        $quantity = max(1, min(100, $quantity));
        $cart = $conversation->getContext('cart') ?? [];

        // Gabung bila produk+varian sama sudah ada (hindari baris ganda).
        $found = false;
        foreach ($cart as &$row) {
            if ((int) $row['product_id'] === (int) $product->id && (int) $row['variant_id'] === (int) $variant->id) {
                $row['qty'] = min(100, (int) $row['qty'] + $quantity);
                $found = true;
                break;
            }
        }
        unset($row);

        if (! $found) {
            $cart[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'variant_id' => $variant->id,
                'variant_name' => $variant->name,
                'qty' => $quantity,
            ];
        }

        $conversation->setContext('cart', $cart);
        // Bersihkan pilihan sementara produk ini.
        $conversation->forgetContext('selected_product_id');
        $conversation->forgetContext('selected_product_name');
        $conversation->forgetContext('selected_variant_id');
        $conversation->forgetContext('product_quantity');

        $this->askAddMore($conversation);
    }

    /**
     * Ringkasan keranjang + tawaran: tambah produk lain atau LANJUT.
     */
    protected function askAddMore(WhatsAppConversation $conversation): void
    {
        $conversation->setContext('form_step', 4);
        $cart = $conversation->getContext('cart') ?? [];

        $lines = [];
        $totalQty = 0;
        foreach ($cart as $i => $row) {
            $n = $i + 1;
            $lines[] = "{$n}. {$row['qty']}x {$row['product_name']} ({$row['variant_name']})";
            $totalQty += (int) $row['qty'];
        }

        $qtyNote = $conversation->getContext('qty_note') ?? '';
        $conversation->forgetContext('qty_note');

        $this->metaService->sendText($conversation->phone_number,
            "✅ Masuk keranjang{$qtyNote}:\n" . implode("\n", $lines) . "\n\n" .
            "Mau tambah produk lain? Ketik *nama/nomor* produknya (bisa pakai jumlah, mis. *2x Kue Ijo*).\n\n" .
            "Kalau sudah cukup, ketik *LANJUT*.\n" .
            "(Ketik *HAPUS* untuk keluarkan item terakhir.)"
        );
    }

    /**
     * Langkah tambah/LANJUT: produk baru → alur pilih; LANJUT → data penerima.
     */
    protected function processAddMoreStep(WhatsAppConversation $conversation, string $text): void
    {
        $lower = strtolower(trim($text));

        // LANJUT/HAPUS hanya bila PESAN PERSIS perintahnya — "1x selesai
        // serikaya" mengandung kata "selesai" tapi itu BUKAN perintah lanjut.
        if (in_array($lower, ['lanjut', 'lanjutkan', 'selesai', 'cukup', 'sudah', 'udah', 'checkout'], true)) {
            $cart = $conversation->getContext('cart') ?? [];
            if ($cart === []) {
                $conversation->setContext('form_step', 0);
                $this->askOrderForm($conversation);

                return;
            }
            $conversation->setContext('form_step', 1);
            $this->metaService->sendText($conversation->phone_number,
                "Siap! Sekarang data penerima ya.\n\n*Siapa nama penerimanya?*\n(bisa nama sendiri)"
            );

            return;
        }

        // HAPUS [n]: perintah persis ("hapus" / "hapus 2"). Teks lain yang
        // mengandung kata hapus tetap dianggap nama produk.
        if ($lower === 'hapus' || preg_match('/^hapus\s+(\d{1,2})$/iu', $lower, $hm)) {
            $cart = $conversation->getContext('cart') ?? [];
            $target = null;
            if (isset($hm[1])) {
                $idx = (int) $hm[1] - 1;
                if (isset($cart[$idx])) {
                    $target = $cart[$idx];
                    array_splice($cart, $idx, 1);
                }
            }
            if ($target === null) {
                $target = array_pop($cart);
            }
            $conversation->setContext('cart', $cart);
            if ($target) {
                $this->metaService->sendText($conversation->phone_number,
                    "🗑️ Dikeluarkan: {$target['qty']}x {$target['product_name']} ({$target['variant_name']})."
                );
            } else {
                $this->metaService->sendText($conversation->phone_number,
                    'Keranjang sudah kosong. Ketik *nama/nomor* produk untuk mulai lagi.'
                );
            }
            if ($cart === []) {
                $conversation->setContext('form_step', 0);
                $this->askOrderForm($conversation);

                return;
            }
            $this->askAddMore($conversation);

            return;
        }

        // Selain itu = nama/nomor produk baru (dukung "2x ...").
        $this->processProductNameStep($conversation, $text);
    }

    /**
     * Langkah 1 data penerima: NAMA (satu pertanyaan).
     */
    protected function processNameStep(WhatsAppConversation $conversation, string $text): void
    {
        $name = trim($text);
        $lower = strtolower($name);
        // Tolak yang jelas-jelas bukan nama: terlalu pendek, angka saja,
        // atau TEPAT berupa perintah/sapaan ("fix", "halo"). Kalimat yang
        // MENGANDUNG kata itu ("Halo, saya Budi") tetap diterima.
        $commandWords = ['lanjut', 'lanjutkan', 'selesai', 'cukup', 'sudah', 'udah', 'checkout', 'fix', 'hapus', 'batal', 'menu', 'pesan', 'produk', 'skip', 'ya', 'ok', 'oke', 'halo', 'hai', 'pagi', 'siang', 'sore', 'malam'];
        if (mb_strlen($name) < 2
            || ctype_digit(str_replace([' ', '.', '-'], '', $name))
            || in_array($lower, $commandWords, true)
        ) {
            $this->metaService->sendText($conversation->phone_number,
                "*Siapa nama penerimanya?*\nTulis nama orangnya ya (mis. Budi Santoso), bukan angka/perintah."
            );

            return;
        }

        $customer = $conversation->getContext('order_customer') ?? [];
        $customer['name'] = mb_substr($name, 0, 100);
        $conversation->setContext('order_customer', $customer);
        $conversation->setContext('form_step', 2);
        $this->metaService->sendText($conversation->phone_number,
            "Terima kasih, *{$customer['name']}*.\n\n*Apa alamat lengkapnya?*\nTulis jalan + nomor + patokan (mis. Jl. Mawar No 10, dekat pasar)."
        );
    }

    /**
     * Langkah 2 data penerima: ALAMAT (satu pertanyaan).
     */
    protected function processAddressStep(WhatsAppConversation $conversation, string $text): void
    {
        $address = trim($text);
        if (mb_strlen($address) < 10) {
            $this->metaService->sendText($conversation->phone_number,
                "*Apa alamat lengkapnya?*\nTulis agak lengkap ya (minimal jalan + nomor), mis. Jl. Mawar No 10 Surabaya."
            );

            return;
        }

        $customer = $conversation->getContext('order_customer') ?? [];
        $customer['address'] = mb_substr($address, 0, 500);
        $conversation->setContext('order_customer', $customer);
        $conversation->setContext('form_step', 3);
        $this->metaService->sendText($conversation->phone_number,
            "Alamat dicatat.\n\n*Mau dikirim tanggal berapa?*\nContoh: {$this->orderExampleDate()} (boleh juga 25/12/2026 atau 25 Des 2026)"
        );
    }

    /**
     * Langkah 3 data penerima: TANGGAL (satu pertanyaan, divalidasi).
     */
    protected function processDateStep(WhatsAppConversation $conversation, string $text): void
    {
        $parsed = $this->parseDeliveryDate($text);
        if (! $parsed) {
            $this->metaService->sendText($conversation->phone_number,
                "Tanggalnya belum jelas / sudah lewat.\n\n*Mau dikirim tanggal berapa?*\nContoh: {$this->orderExampleDate()}"
            );

            return;
        }

        $customer = $conversation->getContext('order_customer') ?? [];
        $customer['date'] = $parsed;
        $conversation->setContext('order_customer', $customer);
        $conversation->setContext('form_step', 5); // data penerima selesai

        // Jalur lokasi-dulu sudah punya metode+slot: sinkronkan alamat &
        // ongkir dulu (alamat form bisa beda dari pin GPS), lalu ringkasan.
        if (! empty($conversation->getContext('delivery_method'))
            && ! empty($conversation->getContext('delivery_slot'))) {
            if (! $this->refreshAddressAndOngkir($conversation)) {
                return; // Alamat tak dikenali: user diminta pin GPS.
            }
            $this->advanceState($conversation, OrderBotConversationState::ORDER_SUMMARY);
            $this->sendOrderSummary($conversation);

            return;
        }

        $this->askDeliveryMethod($conversation);
    }

    /**
     * Parse tanggal kirim ala chat Indonesia → 'd M Y'.
     * Terima: besok/lusa/hari ini, 25 12 2026, 25/12/2026, 25-12-2026,
     * 25 Des 2026, 25 Desember 2026, 25 Des (tahun terdekat ke depan).
     * Tolak: teks bebas & tanggal lewat. Null bila tak valid.
     */
    protected function parseDeliveryDate(string $input): ?string
    {
        $input = trim($input);
        if ($input === '') {
            return null;
        }

        $today = now()->startOfDay();
        $lower = strtolower($input);
        if (preg_match('/\b(hari\s+ini|sekarang)\b/iu', $lower)) {
            return $today->format('d M Y');
        }
        if (preg_match('/\bbesok\b/iu', $lower)) {
            return $today->copy()->addDay()->format('d M Y');
        }
        if (preg_match('/\blusa\b/iu', $lower)) {
            return $today->copy()->addDays(2)->format('d M Y');
        }

        $months = [
            'jan' => '01', 'januari' => '01', 'january' => '01',
            'feb' => '02', 'februari' => '02', 'february' => '02',
            'mar' => '03', 'maret' => '03', 'march' => '03',
            'apr' => '04', 'april' => '04',
            'mei' => '05', 'may' => '05',
            'jun' => '06', 'juni' => '06', 'june' => '06',
            'jul' => '07', 'juli' => '07', 'july' => '07',
            'agu' => '08', 'agustus' => '08', 'aug' => '08', 'august' => '08',
            'sep' => '09', 'sept' => '09', 'september' => '09',
            'okt' => '10', 'oktober' => '10', 'oct' => '10', 'october' => '10',
            'nov' => '11', 'november' => '11',
            'des' => '12', 'desember' => '12', 'dec' => '12', 'december' => '12',
        ];

        $day = $month = $year = null;
        if (preg_match('/^(\d{1,2})[\s\/\-.]+(\d{1,2})[\s\/\-.]+(\d{2,4})$/u', $input, $m)) {
            $day = (int) $m[1];
            $month = (int) $m[2];
            $year = (int) $m[3];
            if ($year < 100) {
                $year += 2000;
            }
        } elseif (preg_match('/^(\d{1,2})\s+([a-zA-Z]+)\s+(\d{2,4})?$/u', $input, $m)) {
            $key = strtolower($m[2]);
            if (! isset($months[$key])) {
                return null;
            }
            $day = (int) $m[1];
            $month = (int) $months[$key];
            $year = isset($m[3]) && $m[3] !== '' ? (int) $m[3] : null;
            if ($year !== null && $year < 100) {
                $year += 2000;
            }
            if ($year === null) {
                // Tanpa tahun: ambil tanggal terdekat ke depan.
                $year = (int) $today->format('Y');
                $candidate = \Carbon\Carbon::create($year, $month, $day);
                if (! $candidate || $candidate->startOfDay()->lt($today)) {
                    $year++;
                }
            }
        } else {
            return null;
        }

        if (! checkdate($month, $day, $year)) {
            return null;
        }

        $date = \Carbon\Carbon::create($year, $month, $day)->startOfDay();
        if ($date->lt(now()->startOfDay())) {
            return null;
        }

        return $date->format('d M Y');
    }

    /**
     * Contoh tanggal kirim dinamis (lusa) agar tidak basi.
     */
    protected function orderExampleDate(): string
    {
        return now()->addDays(2)->format('d M Y');
    }

    /**
     * Kirim daftar bernomor dari pending_options.
     */
    protected function sendOptionList(WhatsAppConversation $conversation, string $header, string $footer): void
    {
        $options = $conversation->getContext('pending_options') ?? [];
        $text = $header."\n\n";
        foreach ($options as $i => $opt) {
            $n = $i + 1;
            $price = isset($opt['price']) && $opt['price'] !== null
                ? ' — Rp '.number_format($opt['price'], 0, ',', '.')
                : '';
            $text .= "{$n}. {$opt['label']}{$price}\n";
        }
        $text .= "\n".$footer;
        $this->metaService->sendText($conversation->phone_number, $text);
    }

    /**
     * Proses jawaban angka atas pending_options (produk atau varian).
     */
    protected function processOptionStep(WhatsAppConversation $conversation, string $text): void
    {
        // KEMBALI = batal pilih ini saja (keranjang aman), kembali pilih produk.
        if ($this->matchesCommand(strtolower(trim($text)), ['kembali', 'back', 'ganti', 'ulang'])) {
            $conversation->forgetContext('pending_options');
            $conversation->forgetContext('selected_product_id');
            $conversation->forgetContext('selected_product_name');
            $conversation->forgetContext('selected_variant_id');
            $conversation->setContext('form_step', 0);
            $this->askOrderForm($conversation);

            return;
        }

        $options = $conversation->getContext('pending_options') ?? [];
        $choice = 0;
        // Terima "3", "3.", "3 - Isi 12", "pilihan 3", "nomor 3", "no 2".
        $normalized = trim($text);
        if (preg_match('/^(?:pilihan|nomor|no\.?)\s*(\d{1,2})/iu', $normalized, $m)) {
            $choice = (int) $m[1];
        } elseif (preg_match('/^(\d{1,2})(?:[\s.\-):]|$)/u', $normalized, $m)) {
            $choice = (int) $m[1];
        }

        if ($choice < 1 || $choice > count($options)) {
            $this->metaService->sendText($conversation->phone_number,
                'Pilih angka 1–'.count($options).' sesuai daftar di atas, atau ketik *BATAL* untuk membatalkan.'
            );

            return;
        }

        $opt = $options[$choice - 1];
        $conversation->forgetContext('pending_options');
        $quantity = max(1, (int) ($conversation->getContext('product_quantity') ?? 1));

        if ($opt['type'] === 'product') {
            $regionId = $conversation->region_id;
            $product = Product::where('is_active', true)
                ->where(function ($q) use ($regionId) {
                    $q->where('region_id', $regionId)->orWhereNull('region_id');
                })
                ->with(['variants' => fn ($q) => $q->where('is_active', true)])
                ->find($opt['id']);
            if (! $product) {
                $this->metaService->sendText($conversation->phone_number,
                    'Produk tersebut sudah tidak tersedia. Silakan ketik nama produk lain atau *PRODUK* untuk katalog.'
                );

                return;
            }
            $this->selectProductFlow($conversation, $product, $quantity);

            return;
        }

        // Pilihan varian: validasi ulang ke DB (aktif & milik produk terpilih).
        $productId = $conversation->getContext('selected_product_id');
        $variant = ProductVariant::where('is_active', true)
            ->where('id', $opt['id'])
            ->when($productId, fn ($q) => $q->where('product_id', $productId))
            ->first();

        if (! $variant) {
            $this->metaService->sendText($conversation->phone_number,
                'Varian tersebut sudah tidak tersedia. Silakan ketik *PESAN* untuk mengulang.'
            );

            return;
        }

        $product = $productId
            ? Product::where('is_active', true)->find($productId)
            : $variant->product;
        if (! $product) {
            $this->metaService->sendText($conversation->phone_number,
                'Produk tersebut sudah tidak tersedia. Silakan ketik *PESAN* untuk mengulang.'
            );

            return;
        }

        $this->addToCart($conversation, $product, $variant, $quantity);
    }

    /**
     * Parse awalan jumlah "2x ", "2 ", "2X ", "2× ". Batas 1–100 DITERAPKAN
     * pemanggil (agar bisa memberi tahu user bila dibatasi).
     * Angka polos ("3") dikembalikan utuh sebagai nama (nomor katalog).
     *
     * @return array{0: int, 1: string} [quantity mentah, nama produk bersih]
     */
    protected function extractQuantity(string $text): array
    {
        if (preg_match('/^(-?\d{1,4})\s*[x×]?\s+(.+)$/iu', trim($text), $m)) {
            return [(int) $m[1], trim($m[2])];
        }

        return [1, trim($text)];
    }

    /**
     * Normalisasi jumlah ke 1–100. Kembalikan [qty, catatan].
     *
     * @return array{0: int, 1: string}
     */
    protected function normalizeQuantity(int $qty): array
    {
        if ($qty < 1) {
            return [1, ' (jumlah minimal 1 ya)'];
        }
        if ($qty > 100) {
            return [100, ' (maksimal 100 per item ya)'];
        }

        return [$qty, ''];
    }

    /**
     * Pengaman: hanya maju ke metode bila form gabungan benar-benar lengkap,
     * jika tidak kembalikan ke langkah pengisian.
     */
    protected function resumeOrderForm(WhatsAppConversation $conversation): void
    {
        // Kembalikan ke pertanyaan terakhir sesuai langkah (0 produk, 1 nama,
        // 2 alamat, 3 tanggal, 4 tambah/LANJUT).
        $step = (int) ($conversation->getContext('form_step') ?? 0);
        $customer = $conversation->getContext('order_customer') ?? [];
        $cart = $conversation->getContext('cart') ?? [];

        if ($step === 4 && $cart !== []) {
            $this->askAddMore($conversation);

            return;
        }

        if ($step >= 3 && ! empty($customer['address'])) {
            $conversation->setContext('form_step', 3);
            $this->metaService->sendText($conversation->phone_number,
                "*Mau dikirim tanggal berapa?*\nContoh: {$this->orderExampleDate()}"
            );

            return;
        }

        if ($step >= 2 && ! empty($customer['name'])) {
            $conversation->setContext('form_step', 2);
            $this->metaService->sendText($conversation->phone_number,
                "*Apa alamat lengkapnya?*\nTulis jalan + nomor + patokan."
            );

            return;
        }

        if ($step >= 1 && $cart !== []) {
            $conversation->setContext('form_step', 1);
            $this->metaService->sendText($conversation->phone_number,
                "*Siapa nama penerimanya?*\n(bisa nama sendiri)"
            );

            return;
        }

        $conversation->setContext('form_step', 0);
        $this->askOrderForm($conversation);
    }

    protected function handleDeliveryMethod(WhatsAppConversation $conversation, string $text): void
    {
        // Terima "1", "1.", "1 - Diambil Sendiri", dst. + bahasa natural.
        $choice = trim($text);
        if (preg_match('/^([123])[\s.\-)].*/u', $choice, $m)) {
            $choice = $m[1];
        } else {
            $lowerChoice = strtolower($choice);
            if (preg_match('/\b(ambil|sendiri|diambil|datang|toko)\b/iu', $lowerChoice)) {
                $choice = '1';
            } elseif (preg_match('/\b(grab|gosend|go-send|gojek|ojek|driver|online)\b/iu', $lowerChoice)) {
                $choice = '2';
            } elseif (preg_match('/\b(kurir|internal|antar|diantar|toko.*antar)\b/iu', $lowerChoice)) {
                $choice = '3';
            }
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
        $conversation->setContext('location_done', true);

        // Kembali ke pilih produk bila lokasi diminta di tengah order.
        if ($conversation->getContext('after_location') === 'product') {
            $conversation->setContext('after_location', null);
            $this->advanceState($conversation, OrderBotConversationState::AWAITING_ORDER_FORM);
            $this->askOrderForm($conversation);

            return;
        }

        $distance = $this->deliveryZoneService->estimateDistanceByAddress($address, $conversation->region_id);

        // Alamat tak dikenali zona mana pun: JANGAN default diam-diam —
        // minta pin GPS agar ongkir dihitung akurat.
        if ($distance === null) {
            $this->advanceState($conversation, OrderBotConversationState::AWAITING_LOCATION_OR_ADDRESS);
            $this->metaService->sendLocationRequest($conversation->phone_number,
                "Alamat *{$address}* belum kami kenali areanya. 📍\n\n".
                'Agar ongkir tepat, silakan kirim pin GPS lokasi Anda, atau tulis alamat dengan patokan yang lebih jelas (mis. nama jalan + kecamatan).'
            );

            return;
        }

        $result = $this->deliveryZoneService->calculateOngkir($distance, $conversation->region_id);

        if ($result['needs_escalation']) {
            $snapshot = $this->snapshotOrderDetails($conversation);
            $conversation->clearContext();
            $conversation->update(['current_state' => OrderBotConversationState::ESCALATED_TO_HUMAN->value]);
            $regionName = $conversation->region?->name ?? config('services.whatsapp.default_region', 'cabang kami');
            $this->metaService->sendText($conversation->phone_number,
                "Mohon maaf, jarak pengiriman Anda diperkirakan sekitar {$distance}km.\n".
                "Untuk jarak di atas 14km, silakan hubungi admin kami.\n\n".
                "WA Admin: hubungi admin terdekat di cabang {$regionName}"
            );
            // M1 FIX: notify admin after escalation is committed
            $this->notifyAdminOfEscalation($conversation, $distance, $regionName, $snapshot ?? '');

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
        // Terima "1", "1.", "slot_1", "09:00", dst. + bahasa natural + angka polos.
        $slot = trim($text);
        $lowerSlot = strtolower($slot);
        if (preg_match('/\b(pagi|morning)\b/iu', $lowerSlot)) {
            $slot = '1';
        } elseif (preg_match('/\b(siang|tengah hari|midday)\b/iu', $lowerSlot)) {
            $slot = '2';
        } elseif (preg_match('/\b(sore|malam|evening|petang)\b/iu', $lowerSlot)) {
            $slot = '3';
        } elseif (! in_array($slot, ['1', '2', '3'], true)) {
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

        // Tanpa keranjang → minta produk dulu, jangan ringkasan pincang.
        // Tanpa data penerima → mulai dari nama.
        if (($conversation->getContext('cart') ?? []) === []) {
            $this->advanceState($conversation, OrderBotConversationState::AWAITING_ORDER_FORM);
            $this->askOrderForm($conversation);

            return;
        }

        $customer = $conversation->getContext('order_customer') ?? [];
        if (empty($customer['name']) || empty($customer['address']) || empty($customer['date'])) {
            $conversation->setContext('form_step', 1);
            $this->advanceState($conversation, OrderBotConversationState::AWAITING_ORDER_FORM);
            $this->metaService->sendText($conversation->phone_number,
                "Lengkapi dulu data penerima ya.\n\n*Siapa nama penerimanya?*"
            );

            return;
        }

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
        } elseif ($this->matchesCommand($lower, ['ubah', 'ganti', 'rubah'])) {
            if (str_contains($lower, 'alamat')) {
                $conversation->setContext('editing_address', true);
                $this->metaService->sendText($conversation->phone_number,
                    "Silakan kirim alamat pengiriman yang baru.\n(Ketik *BATAL* untuk membatalkan pesanan.)"
                );
            } else {
                // Jangan batalkan diam-diam — pembatalan hanya via BATAL global.
                $this->metaService->sendText($conversation->phone_number,
                    'Mau mengubah apa? Ketik *UBAH ALAMAT* untuk koreksi alamat, *FIX* untuk konfirmasi, atau *BATAL* untuk membatalkan.'
                );
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
    /**
     * Koreksi alamat via pin GPS (dipakai saat UBAH ALAMAT + kirim pin).
     */
    protected function saveEditedPinAddress(WhatsAppConversation $conversation, float $lat, float $lng): void
    {
        $distance = $this->deliveryZoneService->estimateDistance($lat, $lng, $conversation->region_id);
        $result = $this->deliveryZoneService->calculateOngkir($distance, $conversation->region_id);

        if (! empty($result['needs_escalation'])) {
            $snapshot = $this->snapshotOrderDetails($conversation);
            $conversation->clearContext();
            $conversation->update(['current_state' => OrderBotConversationState::ESCALATED_TO_HUMAN->value]);
            $regionName = $conversation->region?->name ?? config('services.whatsapp.default_region', 'cabang kami');
            $this->metaService->sendText($conversation->phone_number,
                "Mohon maaf, lokasi baru di luar jangkauan (sekitar {$distance}km).\nSilakan hubungi admin kami."
            );
            $this->notifyAdminOfEscalation($conversation, $distance, $regionName, $snapshot);

            return;
        }

        $customer = $conversation->getContext('order_customer') ?? [];
        $customer['address'] = "Lokasi GPS ({$lat}, {$lng})";
        $conversation->setContext('order_customer', $customer);
        $conversation->setContext('editing_address', null);
        $conversation->setContext('delivery_address', $customer['address']);
        $conversation->setContext('distance_km', $distance);
        $conversation->setContext('ongkir', $result['ongkir'] ?? 0);

        $this->metaService->sendText($conversation->phone_number, '✅ Alamat diperbarui dari pin GPS.');
        $this->sendOrderSummary($conversation);
    }

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

        $customer = $conversation->getContext('order_customer') ?? [];
        $customer['address'] = $newAddress;
        $conversation->setContext('order_customer', $customer);
        $conversation->setContext('editing_address', null);
        $conversation->setContext('delivery_address', $newAddress);

        // Ongkir mengikuti alamat baru untuk kurir internal.
        if (($conversation->getContext('delivery_method') ?? null) === 'internal_courier') {
            $distance = $this->deliveryZoneService->estimateDistanceByAddress($newAddress, $conversation->region_id);

            if ($distance === null) {
                $this->metaService->sendText($conversation->phone_number,
                    "Alamat baru belum kami kenali areanya, jadi ongkir belum bisa dihitung ulang. 📍\n\n".
                    'Silakan kirim pin GPS lokasi Anda.'
                );

                return;
            }

            $result = $this->deliveryZoneService->calculateOngkir($distance, $conversation->region_id);

            if ($result['needs_escalation']) {
                $snapshot = $this->snapshotOrderDetails($conversation);
                $conversation->clearContext();
                $conversation->update(['current_state' => OrderBotConversationState::ESCALATED_TO_HUMAN->value]);
                $regionName = $conversation->region?->name ?? config('services.whatsapp.default_region', 'cabang kami');
                $this->metaService->sendText($conversation->phone_number,
                    "Mohon maaf, alamat baru diperkirakan sekitar {$distance}km (di atas 14km).\n" .
                    "Silakan hubungi admin kami.\n\n" .
                    "WA Admin: hubungi admin terdekat di cabang {$regionName}"
                );
                $this->notifyAdminOfEscalation($conversation, $distance, $regionName, $snapshot);

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
            // Tautan bukti telat via nomor invoice: "INV/..." → sambungkan sesi.
            if ($linked = $this->linkOrderByInvoiceText($conversation, $text)) {
                $this->metaService->sendText($conversation->phone_number,
                    "✅ Pesanan *{$linked->invoice_number}* tersambung.\nSilakan kirim *gambar* bukti pembayarannya."
                );

                return;
            }
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

    /**
     * Ringkasan progres order untuk notifikasi eskalasi. Panggil SEBELUM
     * clearContext agar detail tidak hilang.
     */
    protected function snapshotOrderDetails(WhatsAppConversation $conversation): string
    {
        $ctx = $conversation->context ?? [];
        $customer = $ctx['order_customer'] ?? [];
        $cart = $ctx['cart'] ?? [];
        $items = [];
        foreach ($cart as $row) {
            $items[] = ($row['qty'] ?? 1) . 'x ' . ($row['product_name'] ?? '?');
        }

        return 'Produk: ' . ($items !== [] ? implode(', ', $items) : '-')
            . ' | Penerima: ' . ($customer['name'] ?? '-')
            . ' | Alamat: ' . ($customer['address'] ?? $ctx['delivery_address'] ?? '-');
    }

    protected function notifyAdminOfEscalation(WhatsAppConversation $conversation, float $distance, string $regionName, string $details = ''): void
    {
        try {
            if ($details === '') {
                $details = $this->snapshotOrderDetails($conversation);
            }

            \App\Models\AdminNotification::create([
                'user_id' => null,
                'region_id' => $conversation->region_id,
                'title' => 'Eskalasi jarak kirim (>14km)',
                'message' => "⚠️ Pesanan dari {$conversation->phone_number} butuh penanganan (jarak {$distance}km > 14km). Cabang: {$regionName}. {$details}",
                'is_read' => false,
                'type' => 'escalated',
            ]);

            // WA ke owner agar eskalasi tidak menggantung sunyi.
            $this->notificationRouter->forwardEscalationToOwner(
                $conversation->region_id,
                "🚨 *Eskalasi Jarak*\nNo: {$conversation->phone_number}\nJarak: {$distance}km\n{$details}\nSegera hubungi customer ya."
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::channel('whatsapp')->warning('Admin escalation notify failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Batalkan order bot yang masih 'baru' + belum ada bukti (ghost order)
     * saat user membatalkan. Order yang sudah diproses TIDAK disentuh.
     */
    protected function cancelFreshUnpaidOrder(WhatsAppConversation $conversation): void
    {
        try {
            $orderId = $conversation->getContext('confirmed_order_id');
            if (! $orderId) {
                return;
            }
            $order = Order::find($orderId);
            if (! $order || $order->status !== 'baru' || ! empty($order->payment_proof)) {
                return;
            }
            $order->update(['status' => 'dibatalkan']);
            Log::channel('whatsapp')->info('🚫 Ghost order dibatalkan via BATAL', [
                'phone' => $conversation->phone_number,
                'order_id' => $order->id,
                'invoice' => $order->invoice_number,
            ]);
        } catch (\Throwable $e) {
            Log::channel('whatsapp')->warning('Gagal auto-batal order', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Cari order milik nomor ini dari teks berisi nomor invoice (mis. "INV/1210/...").
     * Bila ketemu + belum ada bukti → sambungkan sebagai confirmed_order_id.
     */
    protected function linkOrderByInvoiceText(WhatsAppConversation $conversation, string $text): ?Order
    {
        if (! preg_match('/INV\/[\w\/-]{3,60}/i', $text, $m)) {
            return null;
        }
        $order = Order::where('invoice_number', $m[0])
            ->where(function ($q) use ($conversation) {
                $q->where('phone', $conversation->phone_number)
                  ->orWhere('customer_id', $conversation->customer_id);
            })
            ->latest('id')
            ->first();

        if (! $order || ! empty($order->payment_proof)) {
            return null;
        }

        $conversation->setContext('confirmed_order_id', $order->id);

        return $order;
    }

    protected function handleClosedConversation(WhatsAppConversation $conversation, string $text, ?array $messageData = null): void
    {
        // Bukti telat (setelah SKIP/konfirmasi): tempelkan ke order, jangan abaikan.
        $mediaPath = $messageData['media_path'] ?? null;
        if ($mediaPath) {
            $this->attachLateProof($conversation, $mediaPath);

            return;
        }
        // Tautan via nomor invoice yang diketik.
        if ($linked = $this->linkOrderByInvoiceText($conversation, $text)) {
            $this->metaService->sendText($conversation->phone_number,
                "✅ Pesanan *{$linked->invoice_number}* tersambung.\nSilakan kirim *gambar* bukti pembayarannya."
            );

            return;
        }
        // Restart PESAN/ORDER dari ESCALATED/ORDER_CONFIRMED ditangani global escape
        // (checkGlobalEscape) sebelum sampai sini — satu jalur kanonis, satu pesan.
        $this->metaService->sendText($conversation->phone_number,
            "Halo! 👋\n\n".
            'Ada yang bisa kami bantu? Ketik *PESAN* untuk membuat pesanan baru.'
        );
        $this->advanceState($conversation, OrderBotConversationState::WELCOME_SENT);
    }

    /**
     * Tempelkan bukti susulan ke order milik nomor ini yang belum ada bukti.
     */
    protected function attachLateProof(WhatsAppConversation $conversation, string $mediaPath): void
    {
        $orderId = $conversation->getContext('confirmed_order_id');
        $order = $orderId ? Order::find($orderId) : null;

        if (! $order || ! empty($order->payment_proof)) {
            $order = Order::where(function ($q) use ($conversation) {
                $q->where('phone', $conversation->phone_number)
                  ->orWhere('customer_id', $conversation->customer_id);
            })
                ->whereNull('payment_proof')
                ->whereNotIn('status', ['dibatalkan', 'selesai', 'diverifikasi_admin'])
                ->latest('id')
                ->first();
        }

        if (! $order) {
            Log::channel('whatsapp')->warning('⚠️ Bukti susulan tanpa order cocok', [
                'phone' => $conversation->phone_number,
                'media_path' => $mediaPath,
            ]);
            $this->metaService->sendText($conversation->phone_number,
                "Bukti kami terima, tapi tidak ada pesanan aktif tanpa bukti di nomor ini.\n".
                'Balas dengan nomor invoice Anda (mis. INV/...) agar bisa ditempelkan.'
            );

            return;
        }

        $order->update(['payment_proof' => $mediaPath]);
        $conversation->setContext('confirmed_order_id', $order->id);
        $this->metaService->sendText($conversation->phone_number,
            "✅ *Bukti susulan diterima* dan ditempelkan ke *{$order->invoice_number}*.\nTerima kasih! 🙏"
        );
        try {
            $this->notificationRouter->forwardPaymentProof($order, $mediaPath);
        } catch (\Throwable $e) {
            Log::channel('whatsapp')->warning('Forward bukti susulan gagal', ['error' => $e->getMessage()]);
        }
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
            OrderBotConversationState::AWAITING_ORDER_FORM => $conversation->getContext('pending_options')
                ? 'Pilih nomor dari daftar yang ditampilkan (atau ketik *BATAL* untuk membatalkan).'
                : match ($formStep) {
                    0 => 'Pilih produk: ketik nomor/nama dari katalog (boleh pakai jumlah, mis. 2x Kue Ijo).',
                    1 => 'Tulis nama penerima pesanan.',
                    2 => 'Tulis alamat lengkap penerima.',
                    3 => 'Tulis tanggal kirim yang diinginkan.',
                    4 => 'Ketik nama produk untuk tambah, atau *LANJUT* bila sudah cukup.',
                    default => 'Lanjutkan pesanan Anda.',
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
            ->with(['category', 'variants' => fn ($q) => $q->where('is_active', true)])
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
                // Produk katalog memakai path public/assets/..., bukan storage.
                $imagePath = $product->image_path;
                if (str_starts_with($imagePath, 'assets/') || str_starts_with($imagePath, 'http')) {
                    $imageUrl = str_starts_with($imagePath, 'http')
                        ? $imagePath
                        : rtrim((string) config('app.url'), '/') . '/' . ltrim($imagePath, '/');
                } else {
                    $imageUrl = rtrim((string) config('app.url'), '/') . Storage::url($imagePath);
                }
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
     * Pricelist lengkap (semua varian + harga) dikelompokkan per kategori,
     * di-scope per cabang percakapan. Tidak mengubah state.
     */
    protected function sendPricelist(WhatsAppConversation $conversation): void
    {
        $regionId = $conversation->region_id;
        $products = Product::where('is_active', true)
            ->where(function ($q) use ($regionId) {
                $q->where('region_id', $regionId)->orWhereNull('region_id');
            })
            ->with(['category', 'variants' => fn ($q) => $q->where('is_active', true)->orderBy('price')])
            ->get();

        $regionName = $conversation->region->name ?? config('services.whatsapp.default_region');
        $text = "💰 *PRICELIST — Cabang {$regionName}*\n\n";

        $grouped = $products->groupBy(fn ($p) => $p->category->name ?? 'Lainnya');

        foreach ($grouped as $category => $items) {
            $text .= "*{$category}*\n";
            foreach ($items as $product) {
                $text .= "• {$product->name}\n";
                if ($product->variants->isEmpty()) {
                    $text .= "  Harga menyusul\n";
                }
                foreach ($product->variants as $variant) {
                    $text .= '  - ' . $variant->name . ': Rp ' . number_format($variant->price, 0, ',', '.') . "\n";
                }
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
        // Lokasi WAJIB duluan (cabang benar): belum ada lokasi → cabang + pin dulu,
        // lalu kembali ke pilih produk.
        if (! $conversation->getContext('location_done')) {
            $conversation->setContext('after_location', 'product');
            $this->askBranchSelection($conversation);

            return;
        }

        $selectedProduct = $conversation->getContext('selected_product_name');

        if ($selectedProduct) {
            // Produk sudah dipilih (via angka katalog / keyword) — validasi ulang
            // ke DB lalu lanjut ke pilih varian / keranjang.
            $product = $this->resolveProduct($conversation, $selectedProduct);
            if (! $product) {
                $conversation->forgetContext('selected_product_name');
                $conversation->forgetContext('selected_product_id');
                $conversation->forgetContext('selected_variant_id');
                $conversation->forgetContext('pending_options');
            } else {
                $quantity = max(1, (int) ($conversation->getContext('product_quantity') ?? 1));
                $this->selectProductFlow($conversation, $product, $quantity);

                return;
            }
        }

        $conversation->setContext('form_step', 0);
        $this->sendCompactCatalog($conversation);
    }

    /**
     * Katalog ringkas bernomor (nama — mulai harga) khusus untuk memilih.
     * Nomor tersimpan di catalog_ids seperti katalog penuh.
     */
    protected function sendCompactCatalog(WhatsAppConversation $conversation): void
    {
        $regionId = $conversation->region_id;
        $products = Product::where('is_active', true)
            ->where(function ($q) use ($regionId) {
                $q->where('region_id', $regionId)->orWhereNull('region_id');
            })
            ->with(['variants' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('id')
            ->limit(30)
            ->get();

        $text = "🛒 *Mau pesan apa?*\n\n";
        $catalogIds = [];
        foreach ($products->values() as $i => $product) {
            $catalogIds[] = $product->id;
            $cheapest = $this->resolveCheapestVariant($product);
            $price = $cheapest ? 'Rp ' . number_format($cheapest->price, 0, ',', '.') : 'tanya admin';
            $text .= ($i + 1) . ". {$product->name} — mulai {$price}\n";
        }
        $conversation->setContext('catalog_ids', $catalogIds);

        $text .= "\nBalas *nomor / nama* produknya (boleh pakai jumlah, mis. *2x Kue Ijo*).";
        $this->metaService->sendText($conversation->phone_number, $text);
    }


    /**
     * Samakan delivery_address dengan alamat penerima + hitung ulang
     * ongkir kurir internal (hindari alamat Surabaya + ongkir Sidoarjo).
     * Return false bila alamat tak dikenali/kosong (pemanggil batalkan ringkasan).
     */
    protected function refreshAddressAndOngkir(WhatsAppConversation $conversation): bool
    {
        $customer = $conversation->getContext('order_customer') ?? [];
        $newAddress = trim($customer['address'] ?? '');
        if ($newAddress === '') {
            return false;
        }

        $conversation->setContext('delivery_address', $newAddress);

        if (($conversation->getContext('delivery_method') ?? null) !== 'internal_courier') {
            return true;
        }

        $distance = $this->deliveryZoneService->estimateDistanceByAddress($newAddress, $conversation->region_id);

        if ($distance === null) {
            $this->advanceState($conversation, OrderBotConversationState::AWAITING_LOCATION_OR_ADDRESS);
            $this->metaService->sendLocationRequest($conversation->phone_number,
                "Alamat *{$newAddress}* belum kami kenali areanya. 📍\n\n".
                'Agar ongkir tepat, silakan kirim pin GPS lokasi Anda.'
            );

            return false;
        }

        $result = $this->deliveryZoneService->calculateOngkir($distance, $conversation->region_id);

        if (! empty($result['needs_escalation'])) {
            $snapshot = $this->snapshotOrderDetails($conversation);
            $conversation->clearContext();
            $conversation->update(['current_state' => OrderBotConversationState::ESCALATED_TO_HUMAN->value]);
            $regionName = $conversation->region?->name ?? config('services.whatsapp.default_region', 'cabang kami');
            $this->metaService->sendText($conversation->phone_number,
                "Mohon maaf, jarak pengiriman melebihi jangkauan kami (sekitar {$distance}km).\n".
                "Admin kami akan menghubungimu untuk solusi pengiriman ya 🙏"
            );
            $this->notifyAdminOfEscalation($conversation, $distance, $regionName, $snapshot);

            return false;
        }

        $conversation->setContext('distance_km', $distance);
        $conversation->setContext('ongkir', $result['ongkir'] ?? 0);

        return true;
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
            "🕐 *Pilih Jam Pengiriman*\n\n".
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

    /**
     * Resolve tiap baris keranjang ke harga live DB. Satu pintu untuk
     * ringkasan & penagihan agar SELALU sama.
     *
     * @return array{lines: array, total: int, all_valid: bool}
     */
    protected function resolveCartLines(WhatsAppConversation $conversation): array
    {
        $cart = $conversation->getContext('cart') ?? [];
        $lines = [];
        $total = 0;
        $allValid = $cart !== [];

        foreach ($cart as $row) {
            $variant = ProductVariant::where('is_active', true)
                ->where('id', $row['variant_id'] ?? null)
                ->where('product_id', $row['product_id'] ?? null)
                ->first();
            $product = Product::where('is_active', true)->find($row['product_id'] ?? null);

            if (! $variant || ! $product) {
                $allValid = false;
                $lines[] = [
                    'product_name' => $row['product_name'] ?? 'Produk',
                    'variant_name' => $row['variant_name'] ?? '-',
                    'qty' => (int) ($row['qty'] ?? 1),
                    'price' => 0,
                    'subtotal' => 0,
                    'valid' => false,
                    'product_id' => $row['product_id'] ?? null,
                    'variant_id' => $row['variant_id'] ?? null,
                ];
                continue;
            }

            $qty = max(1, (int) ($row['qty'] ?? 1));
            $subtotal = $variant->price * $qty;
            $total += $subtotal;
            $lines[] = [
                'product_name' => $product->name,
                'variant_name' => $variant->name,
                'qty' => $qty,
                'price' => (int) $variant->price,
                'subtotal' => $subtotal,
                'valid' => true,
                'product_id' => $product->id,
                'variant_id' => $variant->id,
            ];
        }

        return ['lines' => $lines, 'total' => $total, 'all_valid' => $allValid];
    }

    protected function sendOrderSummary(WhatsAppConversation $conversation): void
    {
        $context = $conversation->context ?? [];
        $customer = $context['order_customer'] ?? [];

        // Jangan render/tagihkan ringkasan pincang — kembalikan ke langkah yang hilang.
        $resolved = $this->resolveCartLines($conversation);
        if ($resolved['lines'] === []) {
            $this->advanceState($conversation, OrderBotConversationState::AWAITING_ORDER_FORM);
            $this->askOrderForm($conversation);

            return;
        }
        if (! $resolved['all_valid']) {
            $conversation->setContext('cart', []);
            $this->metaService->sendText($conversation->phone_number,
                'Ada item yang sudah tidak tersedia. Keranjang dikosongkan — silakan pilih ulang ya.'
            );
            $this->advanceState($conversation, OrderBotConversationState::AWAITING_ORDER_FORM);
            $this->askOrderForm($conversation);

            return;
        }
        if (empty($customer['name']) || empty($customer['address']) || empty($customer['date'])) {
            $conversation->setContext('form_step', 1);
            $this->advanceState($conversation, OrderBotConversationState::AWAITING_ORDER_FORM);
            $this->metaService->sendText($conversation->phone_number,
                "Data penerima belum lengkap.\n\n*Siapa nama penerimanya?*"
            );

            return;
        }
        if (empty($context['delivery_method'])) {
            $this->askDeliveryMethod($conversation);

            return;
        }
        if (empty($context['delivery_slot'])) {
            $this->advanceState($conversation, OrderBotConversationState::AWAITING_DELIVERY_SLOT);
            $this->sendSlotOptions($conversation);

            return;
        }

        $recipientName = $customer['name'];
        $address = $customer['address'];
        $date = $customer['date'];
        $slot = $context['delivery_slot'];
        $method = $context['delivery_method'];
        $ongkir = $context['ongkir'] ?? 0;

        $totalProduct = $resolved['total'];
        $totalAll = $totalProduct + $ongkir;

        $methodText = match ($method) {
            'self_pickup' => 'Diambil Sendiri',
            'grab_gosend' => 'Grab/GoSend',
            'internal_courier' => 'Kurir Internal',
            default => $method,
        };

        $text = "📋 *RINGKASAN PESANAN*\n\n";
        foreach ($resolved['lines'] as $i => $line) {
            $n = $i + 1;
            $text .= "{$n}. {$line['qty']}x {$line['product_name']} ({$line['variant_name']})\n";
            $text .= '   Rp ' . number_format($line['subtotal'], 0, ',', '.') . "\n";
        }
        $text .= 'Subtotal Produk: Rp ' . number_format($totalProduct, 0, ',', '.') . "\n";
        // Grab/GoSend dibayar langsung ke driver — jangan tampil "Rp 0" yang menyesatkan.
        $text .= $method === 'grab_gosend'
            ? "Ongkir ({$methodText}): dibayar langsung ke driver\n"
            : "Ongkir ({$methodText}): Rp " . number_format($ongkir, 0, ',', '.') . "\n";
        $text .= "─────────────────\n";
        $text .= '*TOTAL: Rp ' . number_format($totalAll, 0, ',', '.') . "*\n\n";
        $text .= "Penerima: {$recipientName}\n";
        $text .= "Alamat: {$address}\n";
        $text .= "Tanggal: {$date}\n";
        $text .= "Jam pengiriman: slot {$slot}\n\n";
        $text .= 'Ketik *FIX* untuk konfirmasi, *UBAH ALAMAT* bila alamat salah, atau *BATAL* untuk membatalkan.';

        $this->metaService->sendText($conversation->phone_number, $text);
    }

    protected function confirmOrder(WhatsAppConversation $conversation): void
    {
        $context = $conversation->context ?? [];
        $customer = $context['order_customer'] ?? [];

        // Guard: konteks tak lengkap (mis. data basi) — kembalikan ke
        // langkah yang hilang, JANGAN lempar balik ke metode membabi buta.
        $resolved = $this->resolveCartLines($conversation);
        if ($resolved['lines'] === [] || ! $resolved['all_valid']) {
            $conversation->setContext('cart', []);
            $this->metaService->sendText($conversation->phone_number,
                'Keranjang kosong / ada item tak tersedia. Mari pilih produknya dulu.'
            );
            $this->advanceState($conversation, OrderBotConversationState::AWAITING_ORDER_FORM);
            $this->askOrderForm($conversation);

            return;
        }
        if (empty($customer['name']) || empty($customer['address']) || empty($customer['date'])) {
            $conversation->setContext('form_step', 1);
            $this->advanceState($conversation, OrderBotConversationState::AWAITING_ORDER_FORM);
            $this->metaService->sendText($conversation->phone_number,
                "Data penerima belum lengkap.\n\n*Siapa nama penerimanya?*"
            );

            return;
        }
        if (empty($context['delivery_method']) || empty($context['delivery_slot'])) {
            $this->metaService->sendText($conversation->phone_number,
                'Data pesanan belum lengkap. Mari ulangi dari metode pengiriman.'
            );
            $this->askDeliveryMethod($conversation);

            return;
        }

        // Idempotensi FIX ganda (double-tap / retry Meta): kalau order untuk
        // sesi ini sudah terbuat, kirim ulang konfirmasinya, JANGAN buat baru.
        $preConfirmedId = $context['confirmed_order_id'] ?? null;
        if ($preConfirmedId && ($preOrder = Order::find($preConfirmedId))) {
            $this->advanceState($conversation, OrderBotConversationState::AWAITING_PAYMENT_PROOF);
            $this->metaService->sendText($conversation->phone_number,
                "✅ Pesanan Anda sudah tercatat.\n\n".
                "Nomor Invoice: *{$preOrder->invoice_number}*\n".
                'Total: *Rp '.number_format($preOrder->total_amount, 0, ',', '.')."*\n\n".
                "Silakan kirim bukti pembayaran di sini, atau ketik *SKIP* untuk lanjut."
            );

            return;
        }

        try {
            $order = DB::transaction(function () use ($conversation, $customer, $context, $resolved) {
                // Kunci baris percakapan agar dua FIX bersamaan terserialisasi:
                // transaksi kedua antre di row-lock, lalu membaca hasil commit
                // pertama (ID order asli) dan memakai ulang order yang sama.
                $locked = WhatsAppConversation::where('id', $conversation->id)->lockForUpdate()->first();
                $lockedCtx = $locked?->context ?? [];
                if (! empty($lockedCtx['confirmed_order_id'])
                    && ($dup = Order::find($lockedCtx['confirmed_order_id']))) {
                    $conversation->update(['context' => array_merge($lockedCtx, ['_was_duplicate' => true])]);

                    return $dup;
                }

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

                $order = Order::create([
                    'invoice_number' => $this->generateInvoiceNumber($conversation),
                    'customer_id' => $customer->id,
                    'phone' => $conversation->phone_number,
                    'address' => $customer['address'] ?? $context['delivery_address'] ?? '',
                    'total_amount' => $resolved['total'] + ($context['ongkir'] ?? 0),
                    'payment_method' => 'qris',
                    'note' => 'Penerima: ' . ($customer['name'] ?? '-') . "\n".
                              'Tanggal kirim: ' . ($customer['date'] ?? '-') . "\n".
                              'Slot: ' . ($context['delivery_slot'] ?? '-') . "\n".
                              'Metode: ' . ($context['delivery_method'] ?? '-') . "\n".
                              (isset($context['distance_km']) ? "Jarak: {$context['distance_km']}km\n" : ''),
                    'created_by_user_id' => null, // Bot system
                    'region_id' => $conversation->region_id,
                    'status' => 'baru',
                    'channel' => 'whatsapp',
                ]);

                foreach ($resolved['lines'] as $line) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $line['product_id'],
                        'product_name' => $line['product_name'],
                        'variant_id' => $line['variant_id'],
                        'variant_name' => $line['variant_name'],
                        'quantity' => $line['qty'],
                        'price' => $line['price'],
                        'subtotal' => $line['subtotal'],
                    ]);
                }

                // ID asli ditulis DI DALAM transaksi yang sama (bukan sesudah
                // commit) — inilah yang membuat row-lock di atas benar-benar
                // menutup race double-FIX.
                $conversation->update(['context' => array_merge(
                    $lockedCtx, ['confirmed_order_id' => $order->id]
                )]);

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

            // Duplikat dari race (terdeteksi di dalam transaksi): jangan
            // notif ganda, cukup kirim ulang konfirmasi yang sudah ada.
            if ($conversation->getContext('_was_duplicate')) {
                $conversation->setContext('_was_duplicate', null);
                $this->advanceState($conversation, OrderBotConversationState::AWAITING_PAYMENT_PROOF);
                $this->metaService->sendText($conversation->phone_number,
                    "✅ Pesanan Anda sudah tercatat (tidak ganda).\n\n".
                    "Nomor Invoice: *{$order->invoice_number}*\n".
                    'Total: *Rp '.number_format($order->total_amount, 0, ',', '.')."*\n\n".
                    "Silakan kirim bukti pembayaran di sini, atau ketik *SKIP* untuk lanjut."
                );

                return;
            }

            $conversation->setContext('confirmed_order_id', $order->id);
            $this->advanceState($conversation, OrderBotConversationState::AWAITING_PAYMENT_PROOF);

            // Notify admin immediately so the new order is never missed
            $this->notificationRouter->notifyNewOrder($order->id);

            $sentId = $this->metaService->sendText($conversation->phone_number,
                "✅ *Pesanan Berhasil Dibuat!*\n\n".
                "Nomor Invoice: *{$order->invoice_number}*\n".
                'Total: *Rp '.number_format($order->total_amount, 0, ',', '.')."*\n\n".
                "Silakan lakukan pembayaran melalui QRIS, lalu kirim bukti transfer di sini.\n".
                'Atau ketik *SKIP* untuk melanjutkan tanpa mengirim bukti.'
            );

            // Instruksi bayar TAK SAMPAI tapi order sudah jadi: jangan diam —
            // catat agar admin follow-up manual (telpon/chat).
            if (! $sentId) {
                try {
                    \App\Models\AdminNotification::create([
                        'user_id' => null,
                        'region_id' => $order->region_id,
                        'order_id' => $order->id,
                        'type' => 'system',
                        'title' => 'Instruksi bayar gagal terkirim',
                        'message' => "Order {$order->invoice_number} ({$conversation->phone_number}) tercatat, tapi pesan QRIS gagal dikirim Meta. Segera hubungi customer manual.",
                        'is_read' => false,
                    ]);
                } catch (\Throwable $e) {
                    Log::channel('whatsapp')->warning('Gagal catat notif kirim-gagal', ['error' => $e->getMessage()]);
                }
            }

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

        foreach (Region::active()->get() as $region) {
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
            $cust = $conversation->getContext('order_customer') ?? [];
            $customer = Customer::create([
                'name' => $cust['name'] ?? $conversation->profile_name ?? 'Customer WA',
                'phone' => $phone,
                'address' => $cust['address'] ?? '',
                'region_id' => $conversation->region_id,
                'added_by_user_id' => null,
            ]);
        } else {
            // Pelanggan lama: segarkan alamat SAJA. Region TIDAK ditimpa —
            // satu order kado ke kota lain tidak boleh meracuni atribusi
            // cabang untuk semua order berikutnya (katalog/ongkir/notif).
            $cust = $conversation->getContext('order_customer') ?? [];
            $updates = [];
            if (! empty($cust['address']) && $cust['address'] !== $customer->address) {
                $updates['address'] = $cust['address'];
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
