<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\KurirDashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\Admin\CourierController;
use App\Http\Controllers\CustomerController;
// use App\Http\Controllers\Admin\CustomerController;
// use App\Http\Controllers\Kurir\KurirCustomerController;
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
        // Dashboard per region
        Route::get('dashboard/{region}', [AdminDashboardController::class, 'index'])->name('dashboard');

        // Profile admin
        Route::get('profile', [AdminDashboardController::class, 'profile'])->name('profile');
        Route::put('profile', [AdminDashboardController::class, 'updateProfile'])->name('profile.update');
        Route::put('profile/password', [AdminDashboardController::class, 'updatePassword'])->name('profile.password');

        // Produk (resource)
        Route::resource('products', ProductController::class);

        // Manajemen kurir
        Route::resource('couriers', CourierController::class)
            ->parameters(['couriers' => 'courier']);

        Route::put('couriers/{courier}/note', [CourierController::class, 'updateNote'])
            ->name('couriers.updateNote');

        // Route untuk CRUD Customer menggunakan controller gabungan
        Route::resource('customers', CustomerController::class)->except(['show', 'create', 'edit']);
        Route::put('customers/{customer}/note', [CustomerController::class, 'updateNote'])->name('customers.updateNote');
    });

    // ---------- KURIR ----------
    Route::prefix('kurir')->name('kurir.')->middleware('role:kurir')->group(function () {
        // Dashboard per region
        Route::get('dashboard/{region}', [KurirDashboardController::class, 'index'])->name('dashboard');

        // Profile kurir
        Route::get('profile', [KurirDashboardController::class, 'profile'])->name('profile');
        Route::put('profile', [KurirDashboardController::class, 'updateProfile'])->name('profile.update');
        Route::put('profile/password', [KurirDashboardController::class, 'updatePassword'])->name('profile.password');

        // Modal create customer (dalam konteks region)
        // Route::get('dashboard/{region}/create', [KurirCustomerController::class, 'create'])
        //     ->name('customers.create');

        //routing button pesanan (dashboard)
        Route::prefix('pesanan')->name('pesanan.')->group(function () {
            Route::get('/create', [PesananController::class, 'create'])->name('create');
        });

        // Route resource untuk data customer menggunakan controller gabungan
        Route::resource('customers', CustomerController::class)
            ->parameters(['customers' => 'customer']);

        // Route untuk update note customer
        Route::put('customers/{customer}/note', [CustomerController::class, 'updateNote'])
            ->name('customers.update-note');

        Route::get('/pesanan', [PesananController::class, 'index'])
            ->name('pesanan.index');

        //Route untuk ambil data customer dipesanan
        Route::get('kurir/dashboard/create', [PesananController::class, 'showCustomer'])
            ->name('customer.showCustomer');

        Route::post('/orders/checkout', [PesananController::class, 'checkout'])
            ->name('orders.checkout');
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
