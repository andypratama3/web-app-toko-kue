<?php

namespace Tests\Feature\WhatsApp;

use App\Enums\OrderBotConversationState;
use App\Jobs\ForwardOrderToOwnerJob;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Region;
use App\Models\WhatsAppConversation;
use App\Services\WhatsApp\AdminNotificationRouterService;
use App\Services\WhatsApp\OrderBotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Optimasi & fitur lanjutan bot: batch konteks, cache produk,
 * retry forward owner, recovery keranjang, jalur error.
 */
class OrderBotOptimizationTest extends TestCase
{
    use RefreshDatabase;

    protected Region $region;
    protected Product $kue;
    protected ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.whatsapp.default_region' => 'Denpasar',
            'services.whatsapp.phone_number_id' => '123456',
            'services.whatsapp.access_token' => 'tok',
            'services.whatsapp.graph_version' => 'v21.0',
        ]);

        // Fake Graph dipasang per-test (silenceMeta / fake khusus) karena
        // fake yang didaftarkan lebih dulu menang — fake global di setUp akan
        // menutupi fake sequence/closure untuk test retry.
        \Spatie\Permission\Models\Role::create(['name' => 'admin']);

        $this->region = Region::create(['name' => 'Denpasar', 'slug' => 'denpasar']);
        $category = \App\Models\Category::create(['name' => 'Produk', 'slug' => 'produk']);
        $this->kue = Product::create([
            'category_id' => $category->id,
            'region_id' => null, 'name' => 'Kue Ijo', 'description' => 'ijo',
            'tag' => 'Ala Carte', 'is_active' => true,
        ]);
        $this->variant = ProductVariant::create([
            'product_id' => $this->kue->id, 'name' => 'Isi 3', 'price' => 9000, 'is_active' => true,
        ]);
    }

    /** Fake Graph sukses-default untuk test yang memicu outbound. */
    protected function silenceMeta(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid_ok']]], 200),
        ]);
    }

    // ---- setContextMany ----

    public function test_set_context_many_menyimpan_banyak_kunci(): void
    {
        $c = $this->makeConvo();

        $c->setContextMany([
            'form_step' => 3,
            'order_customer' => ['name' => 'Budi'],
            'delivery_address' => 'Jl. Mawar No 1',
        ]);
        $c->refresh();

        $this->assertEquals(3, $c->getContext('form_step'));
        $this->assertEquals('Budi', $c->getContext('order_customer')['name']);
        $this->assertEquals('Jl. Mawar No 1', $c->getContext('delivery_address'));
    }

    // ---- Zona waktu bisnis ----

    public function test_parse_tanggal_memakai_zona_waktu_bisnis(): void
    {
        config(['services.whatsapp.timezone' => 'Asia/Jakarta']);

        // 18:00 UTC = 01:00 WIB keesokan harinya (19 Okt 2026).
        \Carbon\Carbon::setTestNow(\Carbon\Carbon::parse('2026-10-18 18:00:00', 'UTC'));

        try {
            $bot = app(OrderBotService::class);
            $method = new \ReflectionMethod(OrderBotService::class, 'parseDeliveryDate');
            $method->setAccessible(true);

            $this->assertEquals('19 Oct 2026', $method->invoke($bot, 'hari ini'));
            $this->assertEquals('20 Oct 2026', $method->invoke($bot, 'besok'));
            $this->assertEquals('21 Oct 2026', $method->invoke($bot, 'lusa'));
            // 18 Okt sudah lewat menurut WIB → ditolak.
            $this->assertNull($method->invoke($bot, '18 okt 2026'));
            // 19 Okt (hari ini WIB) tetap diterima.
            $this->assertEquals('19 Oct 2026', $method->invoke($bot, '19/10/2026'));
        } finally {
            \Carbon\Carbon::setTestNow();
        }
    }

    // ---- Cache pencarian produk ----

    public function test_pencarian_produk_memakai_cache_id(): void
    {
        Cache::flush();
        $c = $this->makeConvo();

        $bot = app(OrderBotService::class);
        $method = new \ReflectionMethod(OrderBotService::class, 'searchProductsByKeyword');
        $method->setAccessible(true);

        $first = $method->invoke($bot, $c, 'Kue Ijo');
        $this->assertCount(1, $first);
        $this->assertEquals($this->kue->id, $first->first()->id);

        // Harga diubah — hasil pencarian harus memuat data SEGAR, bukan cache.
        $this->variant->update(['price' => 15000]);
        $second = $method->invoke($bot, $c, 'Kue Ijo');
        $this->assertEquals(15000, $second->first()->variants->first()->price);
    }

    // ---- Forward owner via job ----

    public function test_notify_new_order_mendispatch_job_forward_owner(): void
    {
        Queue::fake();
        $this->region->update(['owner_phone' => '628999000111']);

        // notifyNewOrder butuh minimal satu admin cabang agar tidak early-return.
        $admin = \App\Models\User::create([
            'name' => 'Admin Denpasar', 'email' => 'admin.dps@test.com',
            'password' => bcrypt('password'), 'region_id' => $this->region->id,
        ]);
        $admin->assignRole('admin');

        $customer = Customer::create([
            'name' => 'Budi', 'phone' => '628111111111',
            'address' => 'Jl. Test No 1', 'region_id' => $this->region->id,
        ]);
        $order = Order::create([
            'invoice_number' => 'INV-TEST-1', 'customer_id' => $customer->id,
            'phone' => $customer->phone, 'address' => $customer->address,
            'total_amount' => 9000, 'payment_method' => 'qris', 'status' => 'baru',
            'region_id' => $this->region->id, 'channel' => 'whatsapp',
        ]);

        app(AdminNotificationRouterService::class)->notifyNewOrder($order->id);

        Queue::assertPushed(ForwardOrderToOwnerJob::class, fn ($job) => $job->orderId === $order->id);
    }

    public function test_forward_owner_job_melempar_bila_gagal_agar_retry(): void
    {
        $this->region->update(['owner_phone' => '628999000111']);

        // Semua kirim gagal (500 berulang) → job harus melempar agar retry.
        Http::fake([
            'graph.facebook.com/*' => Http::response('', 500),
        ]);

        $order = $this->makeOrder();

        $this->expectException(\RuntimeException::class);
        (new ForwardOrderToOwnerJob($order->id))->handle(app(AdminNotificationRouterService::class));
    }

    public function test_forward_owner_job_tidak_retry_bila_owner_kosong(): void
    {
        $order = $this->makeOrder();

        // Tanpa nomor owner, tak ada gunanya retry — cukup dilewati.
        (new ForwardOrderToOwnerJob($order->id))->handle(app(AdminNotificationRouterService::class));
        $this->assertTrue(true);
    }

    public function test_gagal_forward_owner_menampilkan_notifikasi_admin(): void
    {
        $this->region->update(['owner_phone' => '628999000111']);

        // Admin cabang diperlukan agar notifyNewOrder tidak early-return.
        $admin = \App\Models\User::create([
            'name' => 'Admin Denpasar', 'email' => 'admin.fwd@test.com',
            'password' => bcrypt('password'), 'region_id' => $this->region->id,
        ]);
        $admin->assignRole('admin');

        // Meta selalu gagal → job (sync) gagal + fallback langsung gagal.
        Http::fake(['graph.facebook.com/*' => Http::response('', 500)]);

        $order = $this->makeOrder();

        app(AdminNotificationRouterService::class)->notifyNewOrder($order->id);

        $this->assertDatabaseHas('admin_notifications', [
            'order_id' => $order->id,
            'title' => 'Gagal meneruskan pesanan ke owner',
        ]);
    }

    // ---- Recovery keranjang ----

    public function test_recover_carts_mengirim_pengingat_sekali(): void
    {
        $this->silenceMeta();
        $c = $this->makeConvo([
            'cart' => [[
                'product_id' => $this->kue->id, 'product_name' => 'Kue Ijo',
                'variant_id' => $this->variant->id, 'variant_name' => 'Isi 3', 'qty' => 2,
            ]],
        ], 'PRODUCT_BROWSING');
        $c->update(['last_message_at' => now()->subMinutes(45)]);

        $this->artisan('whatsapp:recover-carts')->assertSuccessful();

        $countReminders = fn () => collect(Http::recorded())
            ->filter(fn ($pair) => str_contains($pair[0]['text']['body'] ?? '', 'Keranjangmu masih tersimpan'))
            ->count();

        $this->assertEquals(1, $countReminders());
        $c->refresh();
        $this->assertNotNull($c->getContext('cart_reminder_sent_at'));

        // Putaran kedua tak boleh mengirim ulang (total tetap 1).
        $this->artisan('whatsapp:recover-carts')->assertSuccessful();
        $this->assertEquals(1, $countReminders(), 'Pengingat hanya boleh sekali per keranjang');
    }

    public function test_recover_carts_menghiraukan_tanpa_keranjang(): void
    {
        $this->silenceMeta();
        $c = $this->makeConvo([], 'PRODUCT_BROWSING');
        $c->update(['last_message_at' => now()->subMinutes(45)]);

        $this->artisan('whatsapp:recover-carts')->assertSuccessful();
        Http::assertNotSent(fn ($req) => str_contains($req['text']['body'] ?? '', 'Keranjangmu masih tersimpan'));
    }

    public function test_recover_carts_menghiraukan_di_luar_jendela(): void
    {
        $this->silenceMeta();
        $c = $this->makeConvo([
            'cart' => [[
                'product_id' => $this->kue->id, 'product_name' => 'Kue Ijo',
                'variant_id' => $this->variant->id, 'variant_name' => 'Isi 3', 'qty' => 1,
            ]],
        ], 'PRODUCT_BROWSING');
        // 10 jam lalu (> max-hours default 6 jam) → di luar jendela.
        $c->update(['last_message_at' => now()->subHours(10)]);

        $this->artisan('whatsapp:recover-carts')->assertSuccessful();
        Http::assertNotSent(fn ($req) => str_contains($req['text']['body'] ?? '', 'Keranjangmu masih tersimpan'));
    }

    public function test_recover_carts_menghiraukan_yang_sudah_punya_order(): void
    {
        $this->silenceMeta();
        $c = $this->makeConvo([
            'cart' => [[
                'product_id' => $this->kue->id, 'product_name' => 'Kue Ijo',
                'variant_id' => $this->variant->id, 'variant_name' => 'Isi 3', 'qty' => 1,
            ]],
            'confirmed_order_id' => 999,
        ], 'PRODUCT_BROWSING');
        $c->update(['last_message_at' => now()->subMinutes(45)]);

        $this->artisan('whatsapp:recover-carts')->assertSuccessful();
        Http::assertNotSent(fn ($req) => str_contains($req['text']['body'] ?? '', 'Keranjangmu masih tersimpan'));
    }

    public function test_recover_carts_dry_run_tidak_mengirim(): void
    {
        $this->silenceMeta();
        $c = $this->makeConvo([
            'cart' => [[
                'product_id' => $this->kue->id, 'product_name' => 'Kue Ijo',
                'variant_id' => $this->variant->id, 'variant_name' => 'Isi 3', 'qty' => 1,
            ]],
        ], 'PRODUCT_BROWSING');
        $c->update(['last_message_at' => now()->subMinutes(45)]);

        $this->artisan('whatsapp:recover-carts', ['--dry-run' => true])->assertSuccessful();

        Http::assertNotSent(fn ($req) => str_contains($req['text']['body'] ?? '', 'Keranjangmu masih tersimpan'));
        $c->refresh();
        $this->assertNull($c->getContext('cart_reminder_sent_at'));
    }

    public function test_meta_retry_pada_connection_exception(): void
    {
        $calls = 0;
        Http::fake([
            'graph.facebook.com/*' => function () use (&$calls) {
                $calls++;
                if ($calls === 1) {
                    throw new ConnectionException('cURL error 28: timeout');
                }

                return Http::response(['messages' => [['id' => 'wamid_conn_ok']]], 200);
            },
        ]);

        $id = app(\App\Services\WhatsApp\WhatsappMetaService::class)
            ->sendText('628111111111', 'halo', false);

        $this->assertEquals('wamid_conn_ok', $id);
        $this->assertEquals(2, $calls, 'Harus retry sekali setelah connection error');
    }

    // ---- resetConversationIfStale membatalkan ghost order ----

    public function test_reset_stale_membatalkan_ghost_order(): void
    {
        $customer = Customer::create([
            'name' => 'Budi', 'phone' => '628112223333',
            'address' => 'Jl. Hantu No 1', 'region_id' => $this->region->id,
        ]);
        $ghost = Order::create([
            'invoice_number' => 'INV-GHOST-1', 'customer_id' => $customer->id,
            'phone' => $customer->phone, 'address' => $customer->address,
            'total_amount' => 9000, 'payment_method' => 'qris', 'status' => 'baru',
            'region_id' => $this->region->id, 'channel' => 'whatsapp',
        ]);

        $c = $this->makeConvo(['confirmed_order_id' => $ghost->id], 'AWAITING_PAYMENT_PROOF');

        app(OrderBotService::class)->resetConversationIfStale($c, now()->subHours(48));
        $ghost->refresh();

        $this->assertEquals('dibatalkan', $ghost->status);
    }

    // ---- helper ----

    protected function makeConvo(array $context = [], string $state = 'AWAITING_ORDER_FORM'): WhatsAppConversation
    {
        return WhatsAppConversation::create([
            'phone_number' => '6281' . random_int(100000000, 999999999),
            'region_id' => $this->region->id,
            'status' => 'active',
            'current_state' => OrderBotConversationState::from($state)->value,
            'context' => array_merge(['location_done' => true, 'form_step' => 0], $context),
        ]);
    }

    protected function makeOrder(): Order
    {
        $customer = Customer::create([
            'name' => 'Budi', 'phone' => '6289999' . random_int(1000, 9999),
            'address' => 'Jl. Test No 1', 'region_id' => $this->region->id,
        ]);

        return Order::create([
            'invoice_number' => 'INV-' . random_int(100000, 999999), 'customer_id' => $customer->id,
            'phone' => $customer->phone, 'address' => $customer->address,
            'total_amount' => 9000, 'payment_method' => 'qris', 'status' => 'baru',
            'region_id' => $this->region->id, 'channel' => 'whatsapp',
        ]);
    }
}
