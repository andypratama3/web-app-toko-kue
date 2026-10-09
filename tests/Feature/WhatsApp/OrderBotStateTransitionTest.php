<?php

namespace Tests\Feature\WhatsApp;

use App\Enums\OrderBotConversationState;
use App\Models\Category;
use App\Models\Customer;
use App\Models\CustomerCategory;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Region;
use App\Models\User;
use App\Models\WhatsAppConversation;
use App\Services\WhatsApp\OrderBotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OrderBotStateTransitionTest extends TestCase
{
    use RefreshDatabase;

    protected Region $region;
    protected OrderBotService $botService;
    protected WhatsAppConversation $conversation;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.whatsapp.default_region' => 'Denpasar']);

        $this->region = Region::create(['name' => 'Denpasar', 'slug' => 'denpasar']);
        CustomerCategory::create(['name' => 'Reseller']);

        // Fake Meta API calls
        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => ['id' => 'wamid_test']], 200),
            'lookaside.fbsbx.com/*' => Http::response('fake-image', 200, ['Content-Type' => 'image/jpeg']),
        ]);

        // Create default roles
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'kurir']);

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'region_id' => $this->region->id,
        ]);
        $admin->assignRole('admin');

        $category = Category::create(['name' => 'Tumpeng', 'slug' => 'tumpeng']);
        $product = Product::create([
            'category_id' => $category->id,
            'region_id' => $this->region->id,
            'name' => 'Tumpeng Mini Mix',
            'description' => 'Tumpeng mini',
            'is_active' => true,
        ]);
        ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'Paket Mini',
            'price' => 250000,
            'is_active' => true,
        ]);

        $this->botService = app(OrderBotService::class);

        $this->conversation = WhatsAppConversation::create([
            'phone_number' => '6281234567890',
            'region_id' => $this->region->id,
            'status' => 'active',
            'current_state' => OrderBotConversationState::INIT->value,
        ]);
    }

    public function test_init_to_welcome_sent(): void
    {
        $this->botService->handleMessage($this->conversation, 'Halo');

        $this->conversation->refresh();
        $this->assertEquals(OrderBotConversationState::WELCOME_SENT->value, $this->conversation->current_state);
    }

    public function test_welcome_sent_to_branch_selection_via_pesan(): void
    {
        Region::create(['name' => 'Surabaya', 'slug' => 'surabaya']);

        $this->conversation->update(['current_state' => OrderBotConversationState::WELCOME_SENT->value]);

        $this->botService->handleMessage($this->conversation, 'Mau pesan');

        $this->conversation->refresh();
        // Filter cabang eksplisit: tetap di WELCOME_SENT dengan flag awaiting_branch
        $this->assertEquals(OrderBotConversationState::WELCOME_SENT->value, $this->conversation->current_state);
        $this->assertTrue((bool) $this->conversation->getContext('awaiting_branch'));
    }

    public function test_welcome_sent_skips_branch_when_single_region(): void
    {
        $this->conversation->update(['current_state' => OrderBotConversationState::WELCOME_SENT->value]);

        $this->botService->handleMessage($this->conversation, 'Mau pesan');

        $this->conversation->refresh();
        // Hanya 1 cabang di DB — langsung ke langkah lokasi
        $this->assertEquals(OrderBotConversationState::AWAITING_LOCATION_OR_ADDRESS->value, $this->conversation->current_state);
        $this->assertNull($this->conversation->getContext('awaiting_branch'));
    }

    public function test_branch_choice_by_name_locks_region_and_requests_location(): void
    {
        Region::create(['name' => 'Surabaya', 'slug' => 'surabaya']);

        $this->conversation->update([
            'current_state' => OrderBotConversationState::WELCOME_SENT->value,
            'context' => ['awaiting_branch' => true],
        ]);

        $this->botService->handleMessage($this->conversation, 'Surabaya');

        $this->conversation->refresh();
        $surabaya = Region::where('slug', 'surabaya')->first();
        $this->assertEquals($surabaya->id, $this->conversation->region_id);
        $this->assertEquals('manual', $this->conversation->getContext('branch_source'));
        $this->assertNull($this->conversation->getContext('awaiting_branch'));
        $this->assertEquals(OrderBotConversationState::AWAITING_LOCATION_OR_ADDRESS->value, $this->conversation->current_state);
    }

    public function test_pricelist_request_preserves_state(): void
    {
        $this->conversation->update([
            'current_state' => OrderBotConversationState::ORDER_SUMMARY->value,
            'context' => [
                'delivery_method' => 'self_pickup',
                'order_form' => ['product_name' => 'Test'],
            ],
        ]);

        $this->botService->handleMessage($this->conversation, 'harga');

        $this->conversation->refresh();
        $this->assertEquals(OrderBotConversationState::ORDER_SUMMARY->value, $this->conversation->current_state);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_apa_itu_question_finds_product_detail(): void
    {
        $this->conversation->update(['current_state' => OrderBotConversationState::PRODUCT_BROWSING->value]);

        $this->botService->handleMessage($this->conversation, 'apa itu Tumpeng Mini Mix?');

        $this->conversation->refresh();
        $this->assertEquals('Tumpeng Mini Mix', $this->conversation->getContext('selected_product_name'));
        $this->assertEquals(OrderBotConversationState::PRODUCT_BROWSING->value, $this->conversation->current_state);
    }

    public function test_confirm_forwards_order_format_to_owner(): void
    {
        $this->region->update(['owner_phone' => '628999000111']);

        $this->conversation->update([
            'current_state' => OrderBotConversationState::ORDER_SUMMARY->value,
            'context' => [
                'delivery_method' => 'self_pickup',
                'delivery_slot' => '09:00-11:00',
                'ongkir' => 0,
                'product_quantity' => 1,
                'order_form' => [
                    'product_name' => 'Tumpeng Mini',
                    'recipient_name' => 'Budi',
                    'recipient_address' => 'Jl. Test No.1',
                    'delivery_date' => '10 September 2026',
                    'delivery_time' => '10:00',
                ],
            ],
        ]);

        $this->botService->handleMessage($this->conversation, 'FIX');

        $this->assertDatabaseCount('orders', 1);
        Http::assertSent(function ($request) {
            $data = $request->data();

            return ($data['to'] ?? null) === '628999000111'
                && str_contains($data['text']['body'] ?? '', 'PESANAN BARU');
        });
    }

    public function test_payment_proof_is_forwarded_to_owner(): void
    {
        $this->region->update(['owner_phone' => '628999000111']);

        $order = Order::create([
            'invoice_number' => 'INV/0101/01/000/001/001',
            'customer_id' => Customer::create([
                'name' => 'Budi',
                'phone' => '6281234567890',
                'address' => 'Jl. Test No.1',
                'region_id' => $this->region->id,
            ])->id,
            'phone' => '6281234567890',
            'address' => 'Jl. Test No.1',
            'total_amount' => 250000,
            'payment_method' => 'qris',
            'region_id' => $this->region->id,
            'status' => 'baru',
            'channel' => 'whatsapp',
        ]);

        $this->conversation->update([
            'current_state' => OrderBotConversationState::AWAITING_PAYMENT_PROOF->value,
            'context' => ['confirmed_order_id' => $order->id],
        ]);

        $this->botService->handleMessage(
            $this->conversation,
            '[Gambar]',
            ['media_id' => 'media_test_123', 'media_path' => 'payment_proofs/bukti_test.jpg']
        );

        $this->conversation->refresh();
        $this->assertEquals(OrderBotConversationState::ORDER_CONFIRMED->value, $this->conversation->current_state);
        $this->assertEquals('payment_proofs/bukti_test.jpg', $order->fresh()->payment_proof);
        Http::assertSent(function ($request) {
            $data = $request->data();

            return ($data['to'] ?? null) === '628999000111'
                && ($data['type'] ?? null) === 'image';
        });
    }

    public function test_menu_selection_to_product_browsing_tumpeng(): void
    {
        $this->conversation->update(['current_state' => OrderBotConversationState::MENU_SELECTION->value]);

        $this->botService->handleMessage($this->conversation, 'Tumpeng');

        $this->conversation->refresh();
        $this->assertEquals(OrderBotConversationState::PRODUCT_BROWSING->value, $this->conversation->current_state);
        $this->assertEquals('Tumpeng', $this->conversation->getContext('selected_category'));
    }

    public function test_product_browsing_to_awaiting_order_form(): void
    {
        $this->conversation->update(['current_state' => OrderBotConversationState::PRODUCT_BROWSING->value]);

        $this->botService->handleMessage($this->conversation, 'Pesan');

        $this->conversation->refresh();
        $this->assertEquals(OrderBotConversationState::AWAITING_ORDER_FORM->value, $this->conversation->current_state);
    }

    public function test_awaiting_delivery_method_self_pickup(): void
    {
        $this->conversation->update([
            'current_state' => OrderBotConversationState::AWAITING_DELIVERY_METHOD->value,
            'context' => [
                'form_step' => 5,
                'order_form' => [
                    'product_name' => 'Tumpeng Mini',
                    'recipient_name' => 'Budi',
                    'recipient_address' => 'Jl. Test No.1',
                    'delivery_date' => '10 September 2026',
                    'delivery_time' => '10:00',
                ],
            ],
        ]);

        $this->botService->handleMessage($this->conversation, '1');

        $this->conversation->refresh();
        $this->assertEquals(OrderBotConversationState::AWAITING_DELIVERY_SLOT->value, $this->conversation->current_state);
        $this->assertEquals('self_pickup', $this->conversation->getContext('delivery_method'));
        $this->assertEquals(0, $this->conversation->getContext('ongkir'));
    }

    public function test_delivery_slot_to_order_summary(): void
    {
        $this->conversation->update([
            'current_state' => OrderBotConversationState::AWAITING_DELIVERY_SLOT->value,
            'context' => [
                'delivery_method' => 'self_pickup',
                'ongkir' => 0,
                'product_price' => 250000,
                'product_quantity' => 1,
                'order_form' => [
                    'product_name' => 'Tumpeng Mini',
                    'recipient_name' => 'Budi',
                    'recipient_address' => 'Jl. Test No.1',
                    'delivery_date' => '10 September 2026',
                    'delivery_time' => '10:00',
                ],
            ],
        ]);

        $this->botService->handleMessage($this->conversation, '1');

        $this->conversation->refresh();
        $this->assertEquals(OrderBotConversationState::ORDER_SUMMARY->value, $this->conversation->current_state);
        $this->assertEquals('09:00-11:00', $this->conversation->getContext('delivery_slot'));
    }

    public function test_order_summary_fix_creates_order(): void
    {
        $this->conversation->update([
            'current_state' => OrderBotConversationState::ORDER_SUMMARY->value,
            'context' => [
                'delivery_method' => 'self_pickup',
                'delivery_slot' => '09:00-11:00',
                'ongkir' => 0,
                'product_price' => 250000,
                'product_quantity' => 1,
                'order_form' => [
                    'product_name' => 'Tumpeng Mini',
                    'recipient_name' => 'Budi',
                    'recipient_address' => 'Jl. Test No.1',
                    'delivery_date' => '10 September 2026',
                    'delivery_time' => '10:00',
                ],
            ],
        ]);

        $this->botService->handleMessage($this->conversation, 'FIX');

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseHas('orders', [
            'status' => 'baru',
            'channel' => 'whatsapp',
            'payment_method' => 'qris',
        ]);

        $this->assertDatabaseCount('order_items', 1);

        $this->conversation->refresh();
        $this->assertEquals(OrderBotConversationState::AWAITING_PAYMENT_PROOF->value, $this->conversation->current_state);
        $this->assertNotNull($this->conversation->getContext('confirmed_order_id'));
    }

    public function test_order_summary_batal_cancels_order(): void
    {
        $this->conversation->update([
            'current_state' => OrderBotConversationState::ORDER_SUMMARY->value,
            'context' => [
                'delivery_method' => 'self_pickup',
                'order_form' => ['product_name' => 'Test'],
            ],
        ]);

        $this->botService->handleMessage($this->conversation, 'BATAL');

        $this->conversation->refresh();
        $this->assertEquals(OrderBotConversationState::MENU_SELECTION->value, $this->conversation->current_state);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_customer_is_created_on_order_confirm(): void
    {
        $this->assertDatabaseCount('customers', 0);

        $this->conversation->update([
            'current_state' => OrderBotConversationState::ORDER_SUMMARY->value,
            'context' => [
                'delivery_method' => 'self_pickup',
                'delivery_slot' => '09:00-11:00',
                'ongkir' => 0,
                'product_price' => 250000,
                'product_quantity' => 1,
                'order_form' => [
                    'product_name' => 'Tumpeng Mini',
                    'recipient_name' => 'Budi Santoso',
                    'recipient_address' => 'Jl. Test No.1 Denpasar',
                    'delivery_date' => '10 September 2026',
                    'delivery_time' => '10:00',
                ],
            ],
        ]);

        $this->botService->handleMessage($this->conversation, 'fix');

        $this->assertDatabaseCount('customers', 1);
        $this->assertDatabaseHas('customers', [
            'name' => 'Budi Santoso',
            'phone' => '6281234567890',
            'region_id' => $this->region->id,
        ]);
    }

    public function test_invoice_number_format(): void
    {
        $this->conversation->update([
            'current_state' => OrderBotConversationState::ORDER_SUMMARY->value,
            'context' => [
                'delivery_method' => 'self_pickup',
                'delivery_slot' => '09:00-11:00',
                'ongkir' => 0,
                'product_price' => 250000,
                'product_quantity' => 1,
                'order_form' => [
                    'product_name' => 'Tumpeng Mini',
                    'recipient_name' => 'Test',
                    'recipient_address' => 'Test Address',
                    'delivery_date' => '10 September 2026',
                    'delivery_time' => '10:00',
                ],
            ],
        ]);

        $this->botService->handleMessage($this->conversation, 'FIX');

        $order = Order::first();
        $this->assertMatchesRegularExpression('/^INV\/\d{4}\/\d{2}\/\d{3}\/\d{3}\/\d{3}$/', $order->invoice_number);
    }

    public function test_pesan_during_form_restarts_order_form(): void
    {
        // Simulasi user yang "nyangkut" di tengah form (state AWAITING_LOCATION_OR_ADDRESS)
        // lalu mengetik "PESAN" — harus memulai ulang formulir, bukan ditelan sebagai alamat.
        $this->conversation->update([
            'current_state' => OrderBotConversationState::AWAITING_LOCATION_OR_ADDRESS->value,
            'context' => [
                'delivery_method' => 'kurir_internal',
                'order_form' => [
                    'product_name' => 'Tumpeng Mini',
                    'recipient_name' => 'Budi',
                    'recipient_address' => 'Jl. Lama No.1',
                    'delivery_date' => '10 September 2026',
                    'delivery_time' => '10:00',
                ],
            ],
        ]);

        $this->botService->handleMessage($this->conversation, 'PESAN');

        $this->conversation->refresh();
        $this->assertEquals(OrderBotConversationState::AWAITING_ORDER_FORM->value, $this->conversation->current_state);
        $this->assertEquals(0, $this->conversation->getContext('form_step'));
        $this->assertSame([], $this->conversation->getContext('order_form'));
    }

    public function test_pesan_during_form_step_does_not_swallow_as_field(): void
    {
        // Dari log asli: user berada di form step delivery_date lalu mengetik "halo"/"pesan".
        // Kedua kata itu tidak boleh tersimpan sebagai isi field.
        $this->conversation->update([
            'current_state' => OrderBotConversationState::AWAITING_ORDER_FORM->value,
            'context' => ['form_step' => 3, 'order_form' => ['product_name' => 'Tumpeng Mini']],
        ]);

        $this->botService->handleMessage($this->conversation, 'halo');

        $this->conversation->refresh();
        $this->assertEquals(OrderBotConversationState::AWAITING_ORDER_FORM->value, $this->conversation->current_state);
        $this->assertEquals(3, $this->conversation->getContext('form_step'));
        $this->assertNull($this->conversation->getContext('order_form.delivery_date'));

        $this->botService->handleMessage($this->conversation, 'PESAN');

        $this->conversation->refresh();
        $this->assertEquals(OrderBotConversationState::AWAITING_ORDER_FORM->value, $this->conversation->current_state);
        $this->assertEquals(0, $this->conversation->getContext('form_step'));
    }

    public function test_halo_during_form_shows_guidance_without_leaving_form(): void
    {
        $this->conversation->update([
            'current_state' => OrderBotConversationState::AWAITING_LOCATION_OR_ADDRESS->value,
            'context' => ['delivery_method' => 'kurir_internal'],
        ]);

        $this->botService->handleMessage($this->conversation, 'Halo');

        $this->conversation->refresh();
        $this->assertEquals(OrderBotConversationState::AWAITING_LOCATION_OR_ADDRESS->value, $this->conversation->current_state);
    }

    public function test_batalyon_address_is_not_treated_as_cancel(): void
    {
        $this->conversation->update([
            'current_state' => OrderBotConversationState::AWAITING_ORDER_FORM->value,
            'context' => ['form_step' => 1, 'order_form' => ['product_name' => 'Tumpeng Mini Mix']],
        ]);

        $this->botService->handleMessage($this->conversation, 'Shinta | Jl. Batalyon No 10 Surabaya | 12 Okt 2026 | 10:00');

        $this->conversation->refresh();
        $this->assertEquals(OrderBotConversationState::AWAITING_DELIVERY_METHOD->value, $this->conversation->current_state);
        $this->assertEquals('Jl. Batalyon No 10 Surabaya', $this->conversation->getContext('order_form.recipient_address'));
    }

    public function test_menunggu_word_does_not_escape_to_menu(): void
    {
        $this->conversation->update([
            'current_state' => OrderBotConversationState::AWAITING_ORDER_FORM->value,
            'context' => ['form_step' => 1, 'order_form' => ['product_name' => 'Tumpeng Mini Mix']],
        ]);

        $this->botService->handleMessage($this->conversation, 'Budi | Jl. Menunggu No 1 | 12 Okt 2026 | 10:00');

        $this->conversation->refresh();
        $this->assertEquals(OrderBotConversationState::AWAITING_DELIVERY_METHOD->value, $this->conversation->current_state);
    }

    public function test_confirm_with_yes_word_creates_order(): void
    {
        $this->conversation->update([
            'current_state' => OrderBotConversationState::ORDER_SUMMARY->value,
            'context' => [
                'delivery_method' => 'self_pickup',
                'delivery_slot' => '09:00-11:00',
                'ongkir' => 0,
                'order_form' => [
                    'product_name' => 'Tumpeng Mini',
                    'recipient_name' => 'Budi',
                    'recipient_address' => 'Jl. Test No.1',
                    'delivery_date' => '10 September 2026',
                    'delivery_time' => '10:00',
                ],
            ],
        ]);

        $this->botService->handleMessage($this->conversation, 'Ya, oke');

        $this->assertDatabaseCount('orders', 1);
    }

    public function test_lanjut_phrase_and_price_question_do_not_confirm(): void
    {
        $context = [
            'delivery_method' => 'self_pickup',
            'delivery_slot' => '09:00-11:00',
            'ongkir' => 0,
            'order_form' => ['product_name' => 'Tumpeng Mini'],
        ];

        $this->conversation->update([
            'current_state' => OrderBotConversationState::ORDER_SUMMARY->value,
            'context' => $context,
        ]);
        $this->botService->handleMessage($this->conversation, 'mau lanjut mikir dulu');
        $this->assertDatabaseCount('orders', 0);
        $this->assertEquals(OrderBotConversationState::ORDER_SUMMARY->value, $this->conversation->refresh()->current_state);

        $this->botService->handleMessage($this->conversation, 'ya, berapa harganya?');
        $this->assertDatabaseCount('orders', 0);
        $this->assertEquals(OrderBotConversationState::ORDER_SUMMARY->value, $this->conversation->refresh()->current_state);
    }

    public function test_proof_without_order_is_rejected_not_confirmed(): void
    {
        $this->conversation->update([
            'current_state' => OrderBotConversationState::AWAITING_PAYMENT_PROOF->value,
            'context' => [],
        ]);

        $this->botService->handleMessage(
            $this->conversation,
            '[Gambar]',
            ['media_id' => 'media_orphan', 'media_path' => 'payment_proofs/orphan.jpg']
        );

        $this->conversation->refresh();
        $this->assertEquals(OrderBotConversationState::AWAITING_PAYMENT_PROOF->value, $this->conversation->current_state);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_combined_form_rejects_empty_field(): void
    {
        $this->conversation->update([
            'current_state' => OrderBotConversationState::AWAITING_ORDER_FORM->value,
            'context' => ['form_step' => 1, 'order_form' => ['product_name' => 'Tumpeng Mini Mix']],
        ]);

        $this->botService->handleMessage($this->conversation, 'Shinta |  | 12 Okt 2026 | 10:00');

        $this->conversation->refresh();
        $this->assertEquals(OrderBotConversationState::AWAITING_ORDER_FORM->value, $this->conversation->current_state);
        $this->assertEquals(1, $this->conversation->getContext('form_step'));
    }

    public function test_combined_form_joins_extra_pipes_into_address(): void
    {
        $this->conversation->update([
            'current_state' => OrderBotConversationState::AWAITING_ORDER_FORM->value,
            'context' => ['form_step' => 1, 'order_form' => ['product_name' => 'Tumpeng Mini Mix']],
        ]);

        $this->botService->handleMessage($this->conversation, 'Shinta | Toko A | Cabang B | 12 Okt 2026 | 10:00');

        $this->conversation->refresh();
        $this->assertEquals(OrderBotConversationState::AWAITING_DELIVERY_METHOD->value, $this->conversation->current_state);
        $this->assertEquals('Toko A | Cabang B', $this->conversation->getContext('order_form.recipient_address'));
    }

    public function test_quantity_prefix_flows_to_order_item(): void
    {
        $this->conversation->update([
            'current_state' => OrderBotConversationState::AWAITING_ORDER_FORM->value,
            'context' => ['form_step' => 0, 'order_form' => []],
        ]);

        $this->botService->handleMessage($this->conversation, '2x Tumpeng Mini Mix');

        $this->conversation->refresh();
        $this->assertEquals(1, $this->conversation->getContext('form_step'));
        $this->assertEquals(2, $this->conversation->getContext('product_quantity'));
        $this->assertEquals('Tumpeng Mini Mix', $this->conversation->getContext('order_form.product_name'));

        $this->conversation->update([
            'current_state' => OrderBotConversationState::ORDER_SUMMARY->value,
            'context' => [
                'delivery_method' => 'self_pickup',
                'delivery_slot' => '09:00-11:00',
                'ongkir' => 0,
                'product_quantity' => 2,
                'order_form' => [
                    'product_name' => 'Tumpeng Mini Mix',
                    'recipient_name' => 'Budi',
                    'recipient_address' => 'Jl. Test No.1',
                    'delivery_date' => '10 September 2026',
                    'delivery_time' => '10:00',
                ],
            ],
        ]);
        $this->botService->handleMessage($this->conversation, 'FIX');

        $order = Order::first();
        $this->assertNotNull($order);
        $this->assertEquals(2, $order->items->first()->quantity);
        $this->assertEquals(500000, (int) $order->total_amount);
    }

    public function test_ubah_alamat_flow_updates_and_resummarizes(): void
    {
        $this->conversation->update([
            'current_state' => OrderBotConversationState::ORDER_SUMMARY->value,
            'context' => [
                'delivery_method' => 'self_pickup',
                'delivery_slot' => '09:00-11:00',
                'ongkir' => 0,
                'order_form' => [
                    'product_name' => 'Tumpeng Mini Mix',
                    'recipient_name' => 'Budi',
                    'recipient_address' => 'Jl. Lama No.1',
                    'delivery_date' => '10 September 2026',
                    'delivery_time' => '10:00',
                ],
            ],
        ]);

        $this->botService->handleMessage($this->conversation, 'ubah alamat');
        $this->conversation->refresh();
        $this->assertTrue((bool) $this->conversation->getContext('editing_address'));
        $this->assertDatabaseCount('orders', 0);

        $this->botService->handleMessage($this->conversation, 'Jl. Baru No 9 Surabaya');
        $this->conversation->refresh();
        $this->assertNull($this->conversation->getContext('editing_address'));
        $this->assertEquals('Jl. Baru No 9 Surabaya', $this->conversation->getContext('order_form.recipient_address'));
        $this->assertEquals(OrderBotConversationState::ORDER_SUMMARY->value, $this->conversation->current_state);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_menanti_question_is_not_skip(): void
    {
        $this->conversation->update([
            'current_state' => OrderBotConversationState::AWAITING_PAYMENT_PROOF->value,
            'context' => [],
        ]);

        $this->botService->handleMessage($this->conversation, 'saya menanti konfirmasi admin');

        $this->conversation->refresh();
        $this->assertEquals(OrderBotConversationState::AWAITING_PAYMENT_PROOF->value, $this->conversation->current_state);
    }

    public function test_catalog_numbering_matches_selection(): void
    {
        $category = Category::create(['name' => 'Hampers', 'slug' => 'hampers']);
        Product::create([
            'category_id' => $category->id,
            'region_id' => $this->region->id,
            'name' => 'Hampers Natal',
            'description' => 'Hampers natal',
            'is_active' => true,
        ]);

        $this->conversation->update(['current_state' => OrderBotConversationState::WELCOME_SENT->value]);
        $this->botService->handleMessage($this->conversation, 'produk');

        $this->conversation->refresh();
        $this->assertEquals(OrderBotConversationState::PRODUCT_BROWSING->value, $this->conversation->current_state);
        $this->assertCount(2, $this->conversation->getContext('catalog_ids'));

        $this->botService->handleMessage($this->conversation, '2');
        $this->conversation->refresh();
        $this->assertEquals('Hampers Natal', $this->conversation->getContext('selected_product_name'));
    }

    public function test_branch_escape_batal_cancels_selection(): void
    {
        Region::create(['name' => 'Surabaya', 'slug' => 'surabaya']);

        $this->conversation->update([
            'current_state' => OrderBotConversationState::WELCOME_SENT->value,
            'context' => ['awaiting_branch' => true],
        ]);

        $this->botService->handleMessage($this->conversation, 'batal');

        $this->conversation->refresh();
        $this->assertNull($this->conversation->getContext('awaiting_branch'));
        $this->assertEquals(OrderBotConversationState::WELCOME_SENT->value, $this->conversation->current_state);
    }

    public function test_produk_during_form_goes_to_catalog(): void
    {
        $this->conversation->update([
            'current_state' => OrderBotConversationState::AWAITING_DELIVERY_METHOD->value,
            'context' => ['order_form' => ['product_name' => 'Tumpeng Mini']],
        ]);

        $this->botService->handleMessage($this->conversation, 'produk');

        $this->conversation->refresh();
        $this->assertEquals(OrderBotConversationState::PRODUCT_BROWSING->value, $this->conversation->current_state);
    }
}
