<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\KurirDashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\Admin\CourierController;
// use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Kurir\CustomerController;
use App\Http\Controllers\Kurir\PesananController;

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

        // Produk (resource)
        Route::resource('products', ProductController::class);

        // Manajemen kurir
        Route::resource('couriers', CourierController::class)
            ->parameters(['couriers' => 'courier']);
        Route::put('couriers/{courier}/note', [CourierController::class, 'updateNote'])
            ->name('couriers.updateNote');

        // Route untuk CRUD Customer
        Route::resource('customers', CustomerController::class)->except(['show', 'create', 'edit']);
    });

    // ---------- KURIR ----------
    Route::prefix('kurir')->name('kurir.')->middleware('role:kurir')->group(function () {
        // Dashboard per region
        Route::get('dashboard/{region}', [KurirDashboardController::class, 'index'])->name('dashboard');

        // Profile kurir
        Route::get('profile', [KurirDashboardController::class, 'profile'])->name('profile');

        // Route untuk Kurir
        Route::get('/kurir/dashboard/{region}', [KurirDashboardController::class, 'index'])
            ->name('kurir.dashboard');

        // Route data customer untuk kurir (sidebar)
        
            Route::resource('customers', CustomerController::class, [
                'parameters' => ['customers' => 'customer']
            ]);
        

        // Route data pesanan untuk kurir (sidebar)
        
            Route::resource('pesanan', PesananController::class, [
                'parameters' => ['pesanan' => 'pesanan']
            ]);
        

        // Route untuk menambah pesanan (button di dashboard)
        Route::get('/dashboard-kurir/pesanan/add', [PesananController::class, 'tambahPesanan'])
            ->middleware(['auth', 'verified'])
            ->name('kurir.pesanan.add');
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
