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
use Carbon\Carbon;

class PesananController extends Controller
{
    // ... (method index, create, showCustomer, checkout, showFilteredOrders, getOrderDetails tetap sama) ...
    public function index()
    {
        // Variabel $orders dan $error harus didefinisikan sebelum dilempar ke view
        $orders = collect();
        $error = null;
        // Logika untuk mengisi $orders dan $error seharusnya ada di sini,
        // kemungkinan besar dari method showFilteredOrders.
        // Redirect atau panggil method lain jika ini bukan entry point yang dimaksud.
        return $this->showFilteredOrders();
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

            // --- AWAL PERUBAHAN LOGIKA INVOICE ---

            // 5. Buat Nomor Urut Harian
            $orderCountToday = Order::whereDate('created_at', now())->count();
            $dailySequenceNumber = str_pad($orderCountToday, 3, '0', STR_PAD_LEFT);

            $tanggal = now()->format('dmy');

            $formattedRegionId = str_pad($loggedInUser->region_id, 2, '0', STR_PAD_LEFT);
            $formattedKurirId = str_pad($loggedInUser->id, 3, '0', STR_PAD_LEFT);
            $formattedCustomerId = str_pad($validated['customer_id'], 3, '0', STR_PAD_LEFT);

            $invoiceNumber = "INV/{$tanggal}/{$formattedRegionId}/{$formattedKurirId}/{$formattedCustomerId}/{$dailySequenceNumber}";

            $order->invoice_number = $invoiceNumber;
            $order->save();

            // --- AKHIR PERUBAHAN LOGIKA INVOICE ---

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
            $orders = Order::where('created_by_user_id', $loggedInUserId)
                ->with('customer')
                ->latest()
                ->get();

            foreach ($orders as $order) {
                $order->show_warning = false; // Nilai default

                // Cek jika pesanan belum lunas (berdasarkan bukti bayar)
                if (is_null($order->payment_proof)) {
                    // Hitung selisih hari dari tanggal pembuatan
                    $daysSinceCreation = Carbon::parse($order->created_at)->diffInDays(now());

                    if ($daysSinceCreation >= 5) {
                        $order->show_warning = true;
                    }
                }
            }

        } catch (\Exception $e) {
            \Log::error('Error fetching orders for courier ' . $loggedInUserId . ': ' . $e->getMessage());
            $error = 'Gagal memuat pesanan. Terjadi kesalahan pada server.';
            return view('dashboard.kurir.pesanan.index', compact('orders', 'error'));
        }

        return view('dashboard.kurir.pesanan.index', compact('orders'));
    }

    /**
     * Mengambil detail pesanan berdasarkan ID.
     */
    public function getOrderDetails($id)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Tidak terautentikasi'], 401);
        }

        try {
            $order = Order::with(['customer', 'items'])
                ->where('id', $id)
                ->where('created_by_user_id', Auth::id())
                ->firstOrFail();

            $paidAtLabel = '';
            $paidAtFormatted = null;

            if ($order->paid_at) {
                $createdAt = Carbon::parse($order->created_at)->startOfDay();
                $paidAt = Carbon::parse($order->paid_at)->startOfDay();
                $diffInDays = $createdAt->diffInDays($paidAt);

                if ($diffInDays == 1) {
                    $paidAtLabel = ' (Harian)';
                } elseif ($diffInDays >= 2 && $diffInDays <= 7) {
                    $paidAtLabel = ' (Mingguan)';
                }

                $paidAtFormatted = Carbon::parse($order->paid_at)->isoFormat('D MMMM YYYY, HH:mm');
            }

            $formattedOrder = [
                'id' => $order->id,
                'invoice_number' => $order->invoice_number,
                'status' => $order->status,
                'total_amount' => $order->total_amount,
                'payment_method' => $order->payment_method,
                'created_at' => $order->created_at->isoFormat('D MMMM YYYY, HH:mm'),
                'paid_at' => $paidAtFormatted,
                'paid_at_label' => $paidAtLabel,
                'payment_proof' => $order->payment_proof,
                'picked_up_at' => $order->picked_up_at ? Carbon::parse($order->picked_up_at)->isoFormat('D MMMM YYYY, HH:mm') : null,
                'delivered_at' => $order->delivered_at ? Carbon::parse($order->delivered_at)->isoFormat('D MMMM YYYY, HH:mm') : null,
                'received_by_buyer_at' => $order->received_by_buyer_at ? Carbon::parse($order->received_by_buyer_at)->isoFormat('D MMMM YYYY, HH:mm') : null,

                'customer' => [
                    'name' => $order->customer->name ?? 'N/A',
                    'phone' => $order->customer->phone ?? 'N/A',
                    'address' => $order->customer->address ?? 'N/A',
                ],
                'products' => $order->items->map(function ($item) {
                    return [
                        'id' => $item->product_id,
                        'name' => $item->product_name,
                        'price' => $item->price,
                        'quantity' => $item->quantity,
                        'variant_name' => $item->variant_name,
                    ];
                })->toArray()
            ];

            return response()->json($formattedOrder);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Pesanan tidak ditemukan.'], 404);
        } catch (\Exception $e) {
            \Log::error('Error fetching order details for order ID ' . $id . ': ' . $e->getMessage());
            return response()->json(['message' => 'Terjadi kesalahan internal.'], 500);
        }
    }

    /**
     * Metode baru untuk mengunggah bukti pembayaran.
     */
    public function uploadPaymentProof(Request $request, $id)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Tidak terautentikasi'], 401);
        }

        try {
            $validated = $request->validate([
                'payment_proof' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            ]);

            $order = Order::where('id', $id)
                ->where('created_by_user_id', Auth::id())
                ->firstOrFail();

            // --- LOGIKA BARU: Validasi Status Pesanan ---
            // Hanya izinkan unggah jika status adalah 'diterima_pembeli' atau 'selesai' (untuk kasus unggah ulang)
            if (!in_array($order->status, ['diterima_pembeli', 'selesai'])) {
                return response()->json(['message' => 'Bukti pembayaran hanya bisa diunggah setelah pesanan diterima oleh pembeli.'], 403); // 403 Forbidden
            }
            // --- AKHIR LOGIKA BARU ---

            if ($order->payment_proof) {
                Storage::disk('public')->delete($order->payment_proof);
            }

            $path = $request->file('payment_proof')->store('payment_proofs', 'public');

            $order->payment_proof = $path;
            $order->status = 'selesai';
            $order->paid_at = now();
            $order->save();

            return response()->json(['message' => 'Bukti pembayaran berhasil diunggah. Pesanan selesai!'], 200);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validasi gagal.', 'errors' => $e->errors()], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Pesanan tidak ditemukan.'], 404);
        } catch (\Exception $e) {
            \Log::error('Error uploading payment proof for order ID ' . $id . ': ' . $e->getMessage());
            return response()->json(['message' => 'Terjadi kesalahan internal.'], 500);
        }
    }


    /**
     * Metode baru untuk mengubah status pesanan.
     */
    public function updateOrderStatus(Request $request, $id)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Tidak terautentikasi'], 401);
        }

        try {
            $validated = $request->validate([
                'new_status' => 'required|string|in:diambil,diantar,diterima_pembeli',
            ]);

            $order = Order::where('id', $id)
                ->where('created_by_user_id', Auth::id())
                ->firstOrFail();

            $newStatus = $validated['new_status'];
            $updateData = ['status' => $newStatus]; // Default update

            switch ($newStatus) {
                case 'diambil':
                    if ($order->status === 'dikemas' || is_null($order->picked_up_at)) {
                        $updateData['picked_up_at'] = now();
                        $updateData['status'] = 'diambil';
                    } else {
                        if (in_array($order->status, ['diantar', 'diterima_pembeli', 'selesai'])) {
                            return response()->json(['message' => 'Status tidak dapat diubah ke "Diambil" dari status saat ini.'], 400);
                        }
                    }
                    break;
                case 'diantar':
                    if (is_null($order->picked_up_at)) $updateData['picked_up_at'] = now();
                    if (is_null($order->delivered_at)) {
                        $updateData['delivered_at'] = now();
                        $updateData['status'] = 'diantar';
                    } else {
                        if (in_array($order->status, ['diterima_pembeli', 'selesai'])) {
                            return response()->json(['message' => 'Status tidak dapat diubah ke "Diantar" dari status saat ini.'], 400);
                        }
                    }
                    break;
                case 'diterima_pembeli':
                    if (is_null($order->picked_up_at)) $updateData['picked_up_at'] = now();
                    if (is_null($order->delivered_at)) $updateData['delivered_at'] = now();
                    if (is_null($order->received_by_buyer_at)) {
                        $updateData['received_by_buyer_at'] = now();
                        $updateData['status'] = 'diterima_pembeli';
                    } else {
                        if ($order->status === 'selesai') {
                            return response()->json(['message' => 'Status sudah "Selesai".'], 400);
                        }
                    }
                    break;
            }

            $order->update($updateData);

            $updatedOrder = Order::with(['customer', 'items'])
                ->where('id', $id)
                ->where('created_by_user_id', Auth::id())
                ->firstOrFail();

            $formattedUpdatedOrder = [
                'id' => $updatedOrder->id,
                'status' => $updatedOrder->status,
                'picked_up_at' => $updatedOrder->picked_up_at ? Carbon::parse($updatedOrder->picked_up_at)->isoFormat('D MMMM YYYY, HH:mm') : null,
                'delivered_at' => $updatedOrder->delivered_at ? Carbon::parse($updatedOrder->delivered_at)->isoFormat('D MMMM YYYY, HH:mm') : null,
                'received_by_buyer_at' => $updatedOrder->received_by_buyer_at ? Carbon::parse($updatedOrder->received_by_buyer_at)->isoFormat('D MMMM YYYY, HH:mm') : null,
            ];

            return response()->json(['message' => 'Status pesanan berhasil diperbarui.', 'order' => $formattedUpdatedOrder], 200);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validasi gagal.', 'errors' => $e->errors()], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Pesanan tidak ditemukan.'], 404);
        } catch (\Exception $e) {
            \Log::error('Error updating order status for order ID ' . $id . ': ' . $e->getMessage());
            return response()->json(['message' => 'Terjadi kesalahan internal.'], 500);
        }
    }
}
