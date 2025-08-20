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
    public function index(){
        return view('dashboard.kurir.pesanan.index', compact('orders', 'error'));
    }

    //untuk menambah pesanan (dari button di dashboard)
    public function create(){
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
            $validated = $request->validate([
                'customer_id' => 'required|exists:customers,id',
                'phone' => 'required|string|max:20',
                'address' => 'required|string',
                'payment_method' => 'required|string',
                'note' => 'nullable|string',
                'products' => 'required|json',
                'payment_proof' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $e->errors()], 422);
        }

        // 2. Memulai Transaksi Database
        DB::beginTransaction();
        try {
            // 3. Simpan Bukti Pembayaran (Jika Ada)
            $paymentProofPath = null;
            if ($request->hasFile('payment_proof')) {
                $paymentProofPath = $request->file('payment_proof')->store('payment_proofs', 'public');
            }

            // Ambil data kurir yang sedang login
            $loggedInUser = Auth::user();

            // 4. Buat Order Utama (tanpa nomor invoice terlebih dahulu)
            $order = Order::create([
                'customer_id' => $validated['customer_id'],
                'phone' => $validated['phone'],
                'address' => $validated['address'],
                'total_amount' => 0, // Akan di-update nanti
                'payment_method' => $validated['payment_method'],
                'payment_proof' => $paymentProofPath,
                'note' => $validated['note'],
                'created_by_user_id' => $loggedInUser->id,
                'region_id' => $loggedInUser->region_id,
            ]);

            // --- LOGIKA INVOICE ---
            // 5. Buat Nomor Invoice setelah mendapatkan ID Order
            $tanggal = now()->format('dmy');
            $regionId = $loggedInUser->region_id;
            $kurirId = $loggedInUser->id;
            $customerId = $validated['customer_id'];
            $orderId = $order->id;

            // Format: INV/TGLBLNTHN/ID_REGION/ID_KURIR/ID_CUSTOMER/NO_ORDER
            $invoiceNumber = "INV/{$tanggal}/{$regionId}/{$kurirId}/{$customerId}/{$orderId}";

            // Simpan nomor invoice ke order yang baru dibuat
            $order->invoice_number = $invoiceNumber;
            $order->save();

            // 6. Proses dan Simpan Item Order
            $products = json_decode($validated['products'], true);
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

            $order->items()->saveMany($orderItems);

            // 7. Update Total Amount pada Order Utama
            $order->update(['total_amount' => $totalAmount]);

            DB::commit(); // Selesaikan transaksi

            return response()->json([
                'message' => 'Pesanan berhasil disimpan.',
                'order_id' => $order->id,
                'invoice_number' => $invoiceNumber, 
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Checkout failed: ' . $e->getMessage());

            return response()->json(['message' => 'Gagal menyimpan pesanan. Terjadi kesalahan internal.'], 500);
        }
    }


    /**
     * Menampilkan daftar pesanan yang dibuat oleh kurir yang sedang login.
     */
    public function showFilteredOrders()
    {
        if (!Auth::check()) {
            return redirect('/login')->with('error', 'Anda harus login untuk melihat pesanan.');
        }

        $loggedInUser = Auth::user();

        if (is_null($loggedInUser->region_id)) {
            \Log::warning('User ' . $loggedInUser->id . ' does not have a region_id.');
            $orders = collect();
            $error = 'Region Anda tidak terdaftar. Silakan hubungi administrator.';
            return view('dashboard.kurir.pesanan.index', compact('orders', 'error'));
        }
        
        $loggedInUserId = $loggedInUser->id;
        $orders = collect();

        try {
            // Ambil semua pesanan dimana 'created_by_user_id' cocok dengan ID kurir yang sedang login.
            $orders = Order::where('created_by_user_id', $loggedInUserId)
                ->with('customer') 
                ->latest() // Urutkan dari yang terbaru
                ->get();
                
        } catch (\Exception $e) {
            \Log::error('Error fetching orders for courier ' . $loggedInUserId . ': ' . $e->getMessage());
            $error = 'Gagal memuat pesanan. Terjadi kesalahan pada server.';
            return view('dashboard.kurir.pesanan.index', compact('orders', 'error'));
        }

        return view('dashboard.kurir.pesanan.index', compact('orders'));
    }
}
