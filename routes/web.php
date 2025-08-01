<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\KurirDashboardController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('livewire.homepage');
});

Route::post('/logout', function (Request $request) {
    Auth::guard('web')->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/login');
})->name('logout');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    // Route untuk Admin
    Route::get('/admin/dashboard/{region}', [AdminDashboardController::class, 'index'])
        ->name('admin.dashboard');

    // Route untuk Kurir
    Route::get('/kurir/dashboard/{region}', [KurirDashboardController::class, 'index'])
        ->name('kurir.dashboard');
});

// Route untuk menghindari error Route [dashboard] not defined
Route::get('/dashboard', function () {
    // Redirect ke dashboard sesuai role dan region jika sudah login
    if (auth()->check()) {
        $user = auth()->user();
        $regionSlug = strtolower($user->region->name ?? '');
        if ($user->hasRole('admin')) {
            return redirect()->route('admin.dashboard', ['region' => $regionSlug]);
        } elseif ($user->hasRole('kurir')) {
            return redirect()->route('kurir.dashboard', ['region' => $regionSlug]);
        }
    }
    abort(403, 'Unauthorized');
})->name('dashboard');

// Route untuk profile admin
Route::get('/admin/profile', [AdminDashboardController::class, 'profile'])->name('admin.profile');
// Route untuk profile kurir
Route::get('/kurir/profile', [KurirDashboardController::class, 'profile'])->name('kurir.profile');

// Route untuk form tambah data customer
Route::get('/{region}/dashboard-kurir/modal/tmbh-customer', [KurirDashboardController::class, 'tambahCust'])
    ->middleware(['auth', 'verified'])
    ->name('kurir.modal.tmbh-customer');

// Route untuk form tambah pesanan
Route::get('/{region}/dashboard-kurir/pages/tmbh-pesanan', [KurirDashboardController::class, 'tambahPesanan'])
    ->middleware(['auth', 'verified'])
    ->name('kurir.pages.tmbh-pesanan');

// Route untuk sidebar data-customer (menampilkan semua data customer)
Route::get('/{region}/dashboard-kurir/pages/data-seller', [KurirDashboardController::class, 'dataCust'])
    ->middleware(['auth', 'verified'])
    ->name('kurir.pages.data-customer');

// Route untuk menambahkan customer baru (dari modal)
Route::post('/customer/store', [KurirDashboardController::class, 'store'])->name('customer.store');

// Route untuk menampilkan data customer
Route::get('/dashboard-kurir/pages/data-seller', [KurirDashboardController::class, 'showCustomer'])->name('customer.showCustomer');

