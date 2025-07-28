<?php

// Pastikan namespace controller benar
namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth; // Import Auth facade

class ProductController extends Controller
{
    /**
     * Menampilkan daftar produk.
     * Sebaiknya ini menjadi halaman terpisah, bukan di dashboard utama.
     */
    public function index()
    {
        $products = \App\Models\Product::latest()->paginate(12); // Ambil 12 produk terbaru

        // Anda perlu membuat view baru untuk ini, contoh: 'products.index'
        return view('dashboard.admin.products.index', compact('products'));
    }

    /**
     * Menampilkan form untuk membuat produk baru.
     */
    // public function create()
    // {
    //     // Sesuaikan path view dengan struktur folder Anda
    //     return view('dashboard.admin.products.create');
    // }

    /**
     * Menyimpan produk baru ke dalam database.
     */
    // public function store(Request $request)
    // {
    //     $validatedData = $request->validate([
    //         'name' => 'required|string|max:255',
    //         'description' => 'required|string',
    //         'price' => 'required|numeric|min:0',
    //         'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
    //     ]);

    //     if ($request->hasFile('image')) {
    //         $path = $request->file('image')->store('products', 'public');
    //         $validatedData['image'] = $path;
    //     }

    //     Product::create($validatedData);

    //     // Perbaiki redirect agar menyertakan parameter region
    //     $region = Auth::user()->region ?? 'default';
    //     return redirect()->route('admin.dashboard', ['region' => $region])->with('success', 'Produk baru berhasil ditambahkan.');
    // }

    /**
     * Menampilkan detail satu produk.
     */
    public function show(Product $product)
    {
        // Sesuaikan path view dengan struktur folder Anda
        return view('dashboard.admin.products.show', compact('product'));
    }

    /**
     * Menampilkan form untuk mengedit produk.
     */
    // public function edit(Product $product)
    // {
    //     // Sesuaikan path view dengan struktur folder Anda
    //     return view('dashboard.admin.products.edit', compact('product'));
    // }

    // /**
    //  * Memperbarui data produk di database.
    //  */
    // public function update(Request $request, Product $product)
    // {
    //     $validatedData = $request->validate([
    //         'name' => 'required|string|max:255',
    //         'description' => 'required|string',
    //         'price' => 'required|numeric|min:0',
    //         'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
    //     ]);

    //     if ($request->hasFile('image')) {
    //         if ($product->image) {
    //             Storage::disk('public')->delete($product->image);
    //         }
    //         $path = $request->file('image')->store('products', 'public');
    //         $validatedData['image'] = $path;
    //     }

    //     $product->update($validatedData);

    //     // Perbaiki redirect agar menyertakan parameter region
    //     $region = Auth::user()->region ?? 'default';
    //     return redirect()->route('admin.dashboard', ['region' => $region])->with('success', 'Data produk berhasil diperbarui.');
    // }

    /**
     * Menghapus produk dari database.
     */
    // public function destroy(Product $product)
    // {
    //     if ($product->image) {
    //         Storage::disk('public')->delete($product->image);
    //     }

    //     $product->delete();

    //     // Perbaiki redirect agar menyertakan parameter region
    //     $region = Auth::user()->region ?? 'default';
    //     return redirect()->route('admin.dashboard', ['region' => $region])->with('success', 'Produk berhasil dihapus.');
    // }
}
