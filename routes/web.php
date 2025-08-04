<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\KurirDashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\Admin\CourierController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', fn () => view('livewire.homepage'));

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

        // Produk (resource)
        Route::resource('products', ProductController::class);

        // Manajemen kurir
        Route::resource('couriers', CourierController::class)
            ->parameters(['couriers' => 'courier']);
        Route::put('couriers/{courier}/note', [CourierController::class, 'updateNote'])
            ->name('couriers.updateNote');
    });

    // ---------- KURIR ----------
    Route::prefix('kurir')->name('kurir.')->middleware('role:kurir')->group(function () {
        // Dashboard per region
        Route::get('dashboard/{region}', [KurirDashboardController::class, 'index'])->name('dashboard');

        // Profile kurir
        Route::get('profile', [KurirDashboardController::class, 'profile'])->name('profile');

        // Modal tambah customer (dalam konteks region)
        Route::get('dashboard/{region}/modal/tambah-customer', [KurirDashboardController::class, 'tambahCust'])
            ->name('modal.tambah-customer');

        // Halaman tambah pesanan
        Route::get('dashboard/{region}/pages/tambah-pesanan', [KurirDashboardController::class, 'tambahPesanan'])
            ->name('pages.tambah-pesanan');

        // Sidebar data customer (tampilkan semua)
        Route::get('dashboard/{region}/pages/data-seller', [KurirDashboardController::class, 'dataCust'])
            ->name('pages.data-customer');

        // Tambah customer baru (dari modal)
        Route::post('customer/store', [KurirDashboardController::class, 'store'])->name('customer.store');

        // Tampilkan customer (jika ini berbeda dari dataCust)
        Route::get('dashboard-kurir/pages/data-seller', [KurirDashboardController::class, 'showCustomer'])
            ->name('customer.showCustomer');
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