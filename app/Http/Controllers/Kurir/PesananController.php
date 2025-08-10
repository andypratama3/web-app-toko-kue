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
    public function store(Request $request)
    {
        // 1. Validasi data yang masuk
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'payment_method' => 'required|string',
            'note' => 'nullable|string',
            'products' => 'required|array',
            'products.*.product_id' => 'required|integer',
            'products.*.product_name' => 'required|string',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.price' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            // 2. Hitung total harga
            $totalPrice = collect($request->products)->sum(function ($product) {
                return $product['quantity'] * $product['price'];
            });

            // 3. Buat record pesanan utama
            $order = Order::create([
                'customer_id' => $request->customer_id,
                'total_price' => $totalPrice,
                'payment_method' => $request->payment_method,
                'note' => $request->note,
            ]);

            // 4. Buat detail pesanan untuk setiap produk
            foreach ($request->products as $product) {
                OrderDetail::create([
                    'order_id' => $order->id,
                    'product_id' => $product['product_id'],
                    'product_name' => $product['product_name'],
                    'quantity' => $product['quantity'],
                    'price' => $product['price'],
                    'subtotal' => $product['quantity'] * $product['price'],
                ]);
            }

            DB::commit();

            return response()->json(['message' => 'Pesanan berhasil disimpan!'], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal menyimpan pesanan.', 'error' => $e->getMessage()], 500);
        }
    }
}
