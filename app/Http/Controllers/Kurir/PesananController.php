<?php
// file: app/Http/Controllers/Kurir/PesananController.php

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
use Illuminate\Support\Facades\Log;

class PesananController extends Controller
{
    public function index()
    {
        $orders = collect();
        $error = null;
        return $this->showFilteredOrders();
    }

    public function create()
    {
        $user = Auth::user();
        $customers = Customer::select('id', 'name', 'address', 'phone', 'note')
            ->where('region_id', $user->region_id)
            ->where('added_by_user_id', $user->id)
            ->latest()
            ->get();

        return view('dashboard.kurir.pesanan.create', compact('customers'));
    }

    public function showCustomer()
    {
        $customers = Customer::all();
        return view('dashboard.kurir.pesanan.create', compact('customers'));
    }

    public function checkout(Request $request)
    {
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

        // --- AWAL BLOK PERUBAHAN ---
        $customer = Customer::with('category')->find($validated['customer_id']);
        if (!$customer) {
            return response()->json(['message' => 'Customer tidak ditemukan.'], 404);
        }

        // Tentukan batas maksimal pesanan berdasarkan kategori customer
        $categoryName = strtolower($customer->category->name ?? '');
        $maxOrder = 0;
        if ($categoryName === 'reseller') {
            $maxOrder = 7;
        } elseif ($categoryName === 'supermarket') {
            $maxOrder = 30;
        }

        // Jika customer termasuk kategori yang memiliki batasan
        if ($maxOrder > 0) {
            // Hitung pesanan aktif (status BUKAN 'diverifikasi_admin')
            $activeOrderCount = Order::where('customer_id', $customer->id)
                ->where('created_by_user_id', Auth::id())
                ->where('status', '!=', 'diverifikasi_admin') // Diubah dari whereNotIn('status', ['selesai'])
                ->count();

            // Jika jumlah pesanan aktif sudah mencapai atau melebihi batas
            if ($activeOrderCount >= $maxOrder) {
                return response()->json([
                    'message' => "Batas maksimal pesanan aktif untuk customer kategori $categoryName adalah $maxOrder. Pesanan sebelumnya harus diverifikasi admin terlebih dahulu."
                ], 422); // Kirim status 422 Unprocessable Entity
            }
        }

        DB::beginTransaction();
        try {
            $paymentProofPath = null;
            if ($request->hasFile('payment_proof')) {
                $paymentProofPath = $request->file('payment_proof')->store('payment_proofs', 'public');
            }

            $loggedInUser = Auth::user();

            $order = Order::create([
                'customer_id' => $validated['customer_id'],
                'phone' => $validated['phone'],
                'address' => $validated['address'],
                'total_amount' => 0,
                'payment_method' => $validated['payment_method'],
                'payment_proof' => $paymentProofPath,
                'note' => $validated['note'],
                'created_by_user_id' => $loggedInUser->id,
                'region_id' => $loggedInUser->region_id,
            ]);

            $orderCountToday = Order::whereDate('created_at', now())->count();
            $dailySequenceNumber = str_pad($orderCountToday, 3, '0', STR_PAD_LEFT);
            $tanggal = now()->format('dmy');
            $formattedRegionId = str_pad($loggedInUser->region_id, 2, '0', STR_PAD_LEFT);
            $formattedKurirId = str_pad($loggedInUser->id, 3, '0', STR_PAD_LEFT);
            $formattedCustomerId = str_pad($validated['customer_id'], 3, '0', STR_PAD_LEFT);
            $invoiceNumber = "INV/{$tanggal}/{$formattedRegionId}/{$formattedKurirId}/{$formattedCustomerId}/{$dailySequenceNumber}";

            $order->invoice_number = $invoiceNumber;
            $order->save();

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
            $order->update(['total_amount' => $totalAmount]);

            DB::commit();

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
                ->where('status', '!=', 'diverifikasi_admin')
                ->with('customer')
                ->latest()
                ->get();

            foreach ($orders as $order) {
                $order->show_warning = false;
                if (is_null($order->payment_proof)) {
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
     * Metode untuk mengunggah bukti pembayaran.
     */
    public function uploadPaymentProof(Request $request, $id)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Tidak terautentikasi'], 401);
        }

        try {
            $request->validate([
                'payment_proof' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            ]);

            $order = Order::where('id', $id)
                ->where('created_by_user_id', Auth::id())
                ->firstOrFail();

            // Hanya izinkan unggah jika status adalah 'diterima_pembeli'.
            if ($order->status !== 'diterima_pembeli') {
                return response()->json(['message' => 'Bukti pembayaran hanya bisa diunggah setelah pesanan diterima oleh pembeli.'], 403);
            }

            // Hapus bukti lama jika ada (untuk skenario re-upload setelah ditolak)
            if ($order->payment_proof) {
                Storage::disk('public')->delete($order->payment_proof);
            }

            $path = $request->file('payment_proof')->store('payment_proofs', 'public');

            $order->payment_proof = $path;
            $order->status = 'selesai'; // Kembalikan status ke 'selesai' untuk diverifikasi ulang
            $order->paid_at = now(); // Perbarui waktu lunas
            $order->save();

            return response()->json(['message' => 'Bukti pembayaran berhasil diunggah ulang. Pesanan menunggu verifikasi admin.'], 200);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validasi gagal.', 'errors' => $e->errors()], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Pesanan tidak ditemukan.'], 404);
        } catch (\Exception $e) {
            \Log::error('Error uploading payment proof for order ID ' . $id . ': ' . $e->getMessage());
            return response()->json(['message' => 'Terjadi kesalahan internal.'], 500);
        }
    }

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
            $updateData = ['status' => $newStatus];

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

    /**
     * FUNGSI BARU UNTUK MENGAMBIL ITEM DARI PESANAN TERAKHIR CUSTOMER
     */
    public function getLastOrder($id)
    {
        // 1. Cari pesanan terakhir dari customer berdasarkan ID, diurutkan dari yang terbaru
        $lastOrder = Order::where('customer_id', $id)
                          ->latest() // Mengurutkan berdasarkan 'created_at' secara descending
                          ->first();

        // 2. Jika tidak ada pesanan sebelumnya, kembalikan response kosong
        if (!$lastOrder) {
            return response()->json(['items' => []]);
        }

        // 3. Ambil relasi 'items' dari pesanan yang ditemukan
        $lastOrder->load('items');

        // 4. Ubah format data items agar sesuai dengan struktur 'cart' di JavaScript
        $cartItems = $lastOrder->items->map(function ($item) {
            return [
                'product_id'   => $item->product_id,
                'product_name' => $item->product_name,
                'variant_id'   => $item->variant_id,
                'variant_name' => $item->variant_name,
                'price'        => $item->price,
                'qty'          => $item->quantity, // 'quantity' dari DB diubah menjadi 'qty' untuk cart JS
            ];
        });

        // 5. Kembalikan data items dalam format JSON
        return response()->json(['items' => $cartItems]);
    }
}
