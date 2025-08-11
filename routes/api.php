<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Models\Product;
use App\Http\Controllers\Kurir\PesananController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// routes untuk mengambil produk (pesanan)
Route::get('/products', function () {
    return Product::select(
        'id',
        'name as nama',
        'price as harga',
        'image as gambar'
    )->get();
});

Route::post('/orders/checkout', [PesananController::class, 'checkout'])->name('orders.checkout');