<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\KurirDashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\Admin\CourierController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\Kurir\PesananController;
use App\Models\Product;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', fn() => view('livewire.homepage'));

Route::post('/logout', function (Request $request) {
    Auth::guard('web')->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/login');
})->name('logout');

// Protected: auth + jetstream session + verified
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {

    // ---------- ADMIN ----------
    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
        // ... (kode admin tidak diubah)
        Route::get('dashboard/{region}', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('profile', [AdminDashboardController::class, 'profile'])->name('profile');
        Route::put('profile', [AdminDashboardController::class, 'updateProfile'])->name('profile.update');
        Route::put('profile/password', [AdminDashboardController::class, 'updatePassword'])->name('profile.password');
        Route::resource('products', ProductController::class)->only(['index']);
        Route::resource('couriers', CourierController::class)
            ->parameters(['couriers' => 'courier']);
        Route::put('couriers/{courier}/note', [CourierController::class, 'updateNote'])
            ->name('couriers.updateNote');
        Route::resource('customers', CustomerController::class)->except(['show', 'create', 'edit']);
        Route::put('customers/{customer}/note', [CustomerController::class, 'updateNote'])->name('customers.updateNote');
        Route::post('customers/{customer}/flag', [CustomerController::class, 'toggleFlag'])->name('customers.toggleFlag');
    });

    // ---------- KURIR ----------
    Route::prefix('kurir')->name('kurir.')->middleware('role:kurir')->group(function () {
        Route::get('dashboard/{region}', [KurirDashboardController::class, 'index'])->name('dashboard');
        Route::get('products', [ProductController::class, 'index'])->name('products.index');
        Route::get('produk/json', function () {
            return \App\Models\Product::where('is_active', true)
                ->with(['variants' => function ($q) {
                    $q->select('id', 'product_id', 'name', 'price');
                }])
                ->get(['id', 'name', 'image_path'])
                ->map(function ($p) {
                    return [
                        'id' => $p->id,
                        'name' => $p->name,
                        'image' => $p->image_path ? asset($p->image_path) : null,
                        'variants' => $p->variants->map(function ($v) {
                            return [
                                'id' => $v->id,
                                'name' => $v->name,
                                'price' => $v->price,
                            ];
                        }),
                    ];
                });
        })->name('produk.json');
        Route::get('profile', [KurirDashboardController::class, 'profile'])->name('profile');
        Route::put('profile', [KurirDashboardController::class, 'updateProfile'])->name('profile.update');
        Route::put('profile/password', [KurirDashboardController::class, 'updatePassword'])->name('profile.password');

        Route::prefix('pesanan')->name('pesanan.')->group(function () {
            // URL: /kurir/pesanan
            Route::get('/', [PesananController::class, 'showFilteredOrders'])->name('index');
            // URL: /kurir/pesanan/create
            Route::get('/create', [PesananController::class, 'create'])->name('create');
            // URL: /kurir/pesanan/{id}/details
            Route::get('/{id}/details', [PesananController::class, 'getOrderDetails'])->name('details');
        });

        Route::resource('customers', CustomerController::class)
            ->parameters(['customers' => 'customer']);
        Route::put('customers/{customer}/note', [CustomerController::class, 'updateNote'])
            ->name('customers.update-note');

        //Route untuk ambil data customer dipesanan
        Route::get('kurir/dashboard/create', [PesananController::class, 'showCustomer'])
            ->name('customer.showCustomer');

        // Route untuk upload bukti pembayaran
        Route::post('/pesanan/{id}/upload-proof', [PesananController::class, 'uploadPaymentProof'])
            ->name('kurir.pesanan.uploadProof');

        // Route untuk update status pesanan
        Route::post('/pesanan/{id}/update-status', [PesananController::class, 'updateOrderStatus'])
            ->name('pesanan.update-status');
    });

    // Route untuk checkout pesanan
    Route::middleware(['auth', 'role:kurir'])->prefix('kurir')->name('kurir.')->group(function () {
        Route::post('/orders/checkout', [PesananController::class, 'checkout'])->name('orders.checkout');
    });

    // ---------- COMMON DASHBOARD REDIRECT ----------
    Route::get('dashboard', function () {
        if (!auth()->check()) {
            return redirect()->route('login');
        }
        $user = auth()->user();
        $regionSlug = optional($user->region)->name ? strtolower($user->region->name) : null;
        if (!$regionSlug) {
            abort(403, 'User tidak memiliki region yang valid. Silakan hubungi administrator.');
        }
        if ($user->hasRole('admin')) {
            return redirect()->route('admin.dashboard', ['region' => $regionSlug]);
        } elseif ($user->hasRole('kurir')) {
            return redirect()->route('kurir.dashboard', ['region' => $regionSlug]);
        }
        abort(403, 'User tidak memiliki role yang valid. Silakan hubungi administrator.');
    })->name('dashboard');
});
