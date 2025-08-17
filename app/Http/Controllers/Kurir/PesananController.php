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


    /**
     * Menampilkan daftar pesanan yang difilter sesuai dengan region dan ID kurir yang login.
     * Metode ini yang akan digunakan untuk route /kurir/orders.
     */
    public function showFilteredOrders()
    {
        // 1. Pastikan user sudah login. Middleware 'auth' di route seharusnya sudah menangani ini.
        if (!Auth::check()) {
            // Ini adalah pengaman jika somehow request tidak melewati middleware 'auth'
            return redirect('/login')->with('error', 'Anda harus login untuk melihat pesanan.');
        }

        $loggedInUser = Auth::user();

        // Validasi bahwa user yang login memiliki region_id
        if (is_null($loggedInUser->region_id)) {
            // Log atau berikan pesan kesalahan jika user tidak memiliki region yang valid
            \Log::warning('User ' . $loggedInUser->id . ' does not have a region_id, cannot filter orders.');
            // Kembalikan view dengan koleksi kosong dan pesan error
            return view('kurir.pesanan.index', ['orders' => collect(), 'error' => 'Region Anda tidak terdaftar. Silakan hubungi administrator.']);
        }

        // Mendapatkan ID kurir yang sedang login
        $loggedInKurirId = $loggedInUser->id;

        // Mendapatkan ID region kurir yang sedang login
        $loggedInKurirRegionId = $loggedInUser->region_id;

        // Mendapatkan role ID untuk kurir (sesuai diskusi kita, yaitu 2)
        $kurirRoleId = 2; // Pastikan ini sesuai dengan ID role 'kurir' di tabel 'roles' Anda

        // Inisialisasi variabel $orders sebagai koleksi kosong sebagai jaring pengaman
        $orders = collect();

        try {
            // Kueri menggunakan Eloquent dengan join
            // Relasi antara tabel-tabel:
            // orders (customer_id) -> customers (region_id) -> regions
            // users (region_id) -> regions
            // users (id) -> model_has_roles (model_id)
            $orders = Order::select(
                    'orders.id AS order_id',
                    'orders.total_amount',
                    'orders.created_at',
                    'orders.phone AS customer_phone_on_order',
                    'orders.address AS customer_address_on_order',
                    'customers.name AS customer_name',
                    'regions.name AS region_name', // Nama region dari tabel regions (melalui customer)
                    'users.name AS kurir_name',    // Nama kurir dari tabel users
                    'users.id AS kurir_id',        // ID kurir
                    'users.region_id AS kurir_region_id' // Region ID kurir
                )
                ->join('customers', 'orders.customer_id', '=', 'customers.id')
                ->join('regions', 'customers.region_id', '=', 'regions.id')
                // Join users pada region yang sama dengan customers
                // Ini akan mencocokkan pesanan dengan kurir yang berada di region pesanan tersebut.
                ->join('users', 'users.region_id', '=', 'regions.id')
                // Join model_has_roles untuk memfilter berdasarkan role 'kurir'
                ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
                ->where('model_has_roles.role_id', $kurirRoleId)       // Filter hanya untuk role kurir
                ->where('users.id', $loggedInKurirId)                   // Filter hanya untuk kurir yang sedang login
                ->where('regions.id', $loggedInKurirRegionId)           // Filter pesanan di region kurir yang login
                ->get(); // Ambil semua hasil kueri

        } catch (\Exception $e) {
            // Log error untuk debugging lebih lanjut
            \Log::error('Error fetching orders for courier ' . $loggedInKurirId . ' in region ' . $loggedInKurirRegionId . ': ' . $e->getMessage());
            // Kembalikan view dengan koleksi kosong dan pesan error ke UI
            return view('kurir.pesanan.index', ['orders' => collect(), 'error' => 'Gagal memuat pesanan. Terjadi kesalahan pada server.']);
        }

        // Kirim data koleksi $orders ke view
        return view('dashboard.kurir.pesanan.index', compact('orders'));
    }
}