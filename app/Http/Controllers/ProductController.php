<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Menampilkan daftar produk (read-only).
     */
    public function index()
    {
        $categories = Category::with(['products' => function ($query) {
            $query->with('variants')->where('is_active', true);
        }])->get();

        // Untuk kurir, kita bisa membuat view terpisah jika diperlukan
        // atau menggunakan @can di view admin untuk menyembunyikan tombol.
        // Untuk saat ini, kita asumsikan admin dan kurir melihat halaman yang sama.
        return view('dashboard.admin.products.index', compact('categories'));
    }

    /**
     * Fitur Create, Store, Edit, Update, dan Destroy dinonaktifkan.
     * Method-method di bawah ini bisa dihapus atau dibiarkan kosong.
     */

    // public function create()
    // {
    //     // Dinonaktifkan
    // }

    // public function store(Request $request)
    // {
    //     // Dinonaktifkan
    // }

    // public function edit(Product $product)
    // {
    //     // Dinonaktifkan
    // }

    // public function update(Request $request, Product $product)
    // {
    //     // Dinonaktifkan
    // }

    // public function destroy(Product $product)
    // {
    //     // Dinonaktifkan
    // }
}
