<?php

namespace App\Http\Controllers\Kurir;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;


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


    public function checkout(Request $request)
    {
        // 1. Validasi Data
        try {
            // Validasi di server untuk keamanan
            $validated = $request->validate([
                'customer_id' => 'required|exists:customers,id',
                'phone' => 'required|string|max:20',
                'address' => 'required|string',
                'payment_method' => 'required|string',
                'note' => 'nullable|string',
                'products' => 'required|json', // Products dikirim sebagai JSON string
                'payment_proof' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        }

        // 2. Memulai Transaksi Database
        // Ini memastikan semua operasi berhasil, atau tidak sama sekali
        DB::beginTransaction();
        try {
            // 3. Simpan Bukti Pembayaran (Jika Ada)
            $paymentProofPath = null;
            if ($request->hasFile('payment_proof')) {
                $file = $request->file('payment_proof');
                $paymentProofPath = $file->store('payment_proofs', 'public');
            }

            // 4. Buat Order Utama
            $order = Order::create([
                'customer_id' => $validated['customer_id'],
                'phone' => $validated['phone'],
                'address' => $validated['address'],
                'total_amount' => 0, // Akan dihitung nanti
                'payment_method' => $validated['payment_method'],
                'payment_proof' => $paymentProofPath,
                'note' => $validated['note'],
            ]);

            // 5. Proses dan Simpan Item Order
            $products = json_decode($validated['products'], true); // Decode JSON string menjadi array
            $totalAmount = 0;
            $orderItems = [];

            foreach ($products as $product) {
                $subtotal = $product['quantity'] * $product['price'];
                $totalAmount += $subtotal;

                $orderItems[] = new OrderItem([
                    'product_id' => $product['product_id'],
                    'product_name' => $product['product_name'],
                    'variant_id' => $product['variant_id'] ?? null,
                    'variant_name' => $product['variant_name'] ?? null,
                    'quantity' => $product['quantity'],
                    'price' => $product['price'],
                    'subtotal' => $subtotal,
                ]);
            }

            // Simpan semua item order sekaligus
            $order->items()->saveMany($orderItems);

            // 6. Update Total Amount pada Order Utama
            $order->update(['total_amount' => $totalAmount]);

            DB::commit(); // Selesaikan transaksi

            return response()->json([
                'message' => 'Pesanan berhasil disimpan.',
                'order_id' => $order->id
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack(); // Batalkan semua operasi jika ada yang gagal
            // Log error untuk debugging
            \Log::error('Checkout failed: ' . $e->getMessage());

            return response()->json([
                'message' => 'Gagal menyimpan pesanan. Silakan coba lagi.'
            ], 500);
        }
    }
}
