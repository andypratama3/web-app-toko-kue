<?php

namespace App\Http\Controllers\Kurir;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderDetail;
class PesananController extends Controller
{
    public function index()
    {
        return view('dashboard.kurir.pesanan.index');
    }

    //untuk menambah pesanan (dari button di dashboard)
    public function create()
    {
        $customers = Customer::select('id', 'name', 'address', 'phone', 'note')
            ->where('region_id', Auth::user()->region_id)
            ->latest()
            ->get();

        return view('dashboard.kurir.pesanan.create', compact('customers'));
    }

    public function showCustomer()
    {
        $customers = Customer::all(); // ambil semua customer
        return view('dashboard.kurir.pesanan.create', compact('customers'));
        // atau: return view('dashboard.kurir.pesanan.add', ['customers' => $customers]);
    }


    //controler form simpan pesanan
    public function checkout(Request $request)
    {
        dd($request->all());
        // 1. Validasi Data yang Masuk
        $validatedData = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'phone' => 'nullable|string|max:20', // Tambahkan validasi untuk phone
            'address' => 'nullable|string|max:255', // Tambahkan validasi untuk address
            'payment_method' => 'required|string|in:cash,tf,qr',
            'note' => 'nullable|string|max:500',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.product_name' => 'required|string|max:255', // Tambahkan jika ingin disimpan
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.price' => 'required|numeric|min:0',
        ]);

        try {
            // Memulai transaksi database
            DB::beginTransaction();

            // 2. Buat Entri Order Utama
            $order = Order::create([
                'customer_id' => $validatedData['customer_id'],
                'phone' => $validatedData['phone'], // Simpan phone
                'address' => $validatedData['address'], // Simpan address
                'payment_method' => $validatedData['payment_method'],
                'note' => $validatedData['note'],
                'total_amount' => 0, // Akan dihitung nanti
                'status' => 'pending', // Atau status awal lainnya
                // Anda mungkin perlu menambahkan user_id jika ada sistem otentikasi kurir
                // 'courier_id' => auth()->id(),
            ]);

            $totalAmount = 0;
            // 3. Simpan Detail Item Pesanan
            foreach ($validatedData['products'] as $product) {
                OrderDetail::create([
                    'order_id' => $order->id,
                    'product_id' => $product['product_id'],
                    'product_name' => $product['product_name'], // Simpan nama produk
                    'quantity' => $product['quantity'],
                    'price' => $product['price'],
                    'subtotal' => $product['quantity'] * $product['price'],
                ]);
                $totalAmount += ($product['quantity'] * $product['price']);
            }

            // 4. Perbarui Total Jumlah Order
            $order->update(['total_amount' => $totalAmount]);

            // Commit transaksi
            DB::commit();

            return response()->json(['message' => 'Pesanan berhasil disimpan!', 'order_id' => $order->id], 200);

        } catch (\Exception $e) {
            // Rollback transaksi jika terjadi kesalahan
            DB::rollBack();
            \Log::error('Checkout Error: ' . $e->getMessage()); // Catat error
            return response()->json(['message' => 'Terjadi kesalahan saat menyimpan pesanan. ' . $e->getMessage()], 500);
        }
    }
}
