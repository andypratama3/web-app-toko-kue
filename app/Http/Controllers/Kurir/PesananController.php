<?php
// file: app/Http/Controllers/Kurir/PesananController.php

namespace App\Http\Controllers\Kurir;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PesananController extends Controller
{
    public function index()
    {
        return $this->showFilteredOrders();
    }

    /**
     * Menampilkan halaman pembuatan pesanan baru.
     * LOGIKA DARI FILE 2: Hanya menampilkan customer yang dibuat oleh kurir login.
     */
    public function create()
    {
        $user = Auth::user();
        $customers = Customer::select('id', 'company_name', 'name', 'address', 'phone', 'note')
            ->where('region_id', $user->region_id)
            ->where('added_by_user_id', $user->id) // Filter tambahan
            ->latest()
            ->get();

        return view('dashboard.kurir.pesanan.create', compact('customers'));
    }

    /**
     * Memproses dan menyimpan pesanan baru.
     * LOGIKA DARI FILE 2: Menggabungkan pembatasan pesanan aktif.
     */
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

        // LOGIKA DARI FILE 2: Pembatasan jumlah pesanan aktif
        $customer = Customer::with('category')->find($validated['customer_id']);
        if (!$customer) {
            return response()->json(['message' => 'Customer tidak ditemukan.'], 404);
        }

        $categoryName = strtolower($customer->category->name ?? '');
        $maxOrder = 0;
        if ($categoryName === 'reseller') $maxOrder = 7;
        elseif ($categoryName === 'supermarket') $maxOrder = 30;

        if ($maxOrder > 0) {
            $activeOrderCount = Order::where('customer_id', $customer->id)
                ->where('created_by_user_id', Auth::id())
                ->where('status', '!=', 'diverifikasi_admin')
                ->count();

            if ($activeOrderCount >= $maxOrder) {
                return response()->json([
                    'message' => "Batas maksimal pesanan aktif untuk customer kategori $categoryName adalah $maxOrder. Pesanan sebelumnya harus diverifikasi admin terlebih dahulu."
                ], 422);
            }
        }
        // AKHIR BLOK PEMBATASAN PESANAN

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
            Log::error('Checkout failed: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal menyimpan pesanan. Terjadi kesalahan internal.'], 500);
        }
    }

    /**
     * Menampilkan daftar pesanan yang dibuat oleh kurir.
     * LOGIKA GABUNGAN: Filter status aktif dari File 2, logika Peringatan dari File 1.
     */
    public function showFilteredOrders()
    {
        if (!Auth::check()) {
            return redirect('/login')->with('error', 'Anda harus login untuk melihat pesanan.');
        }

        $loggedInUser = Auth::user();
        $orders = collect();
        $error = null;

        if (is_null($loggedInUser->region_id)) {
            Log::warning('User ' . $loggedInUser->id . ' does not have a region_id.');
            $error = 'Region Anda tidak terdaftar. Silakan hubungi administrator.';
            return view('dashboard.kurir.pesanan.index', compact('orders', 'error'));
        }

        try {
            $orders = Order::where('created_by_user_id', $loggedInUser->id)
                ->where('status', '!=', 'diverifikasi_admin') // Filter dari File 2
                ->with('customer')
                ->latest()
                ->get();

            // Logika Peringatan dari File 1
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
            Log::error('Error fetching orders for courier ' . $loggedInUser->id . ': ' . $e->getMessage());
            $error = 'Gagal memuat pesanan. Terjadi kesalahan pada server.';
            return view('dashboard.kurir.pesanan.index', compact('orders', 'error'));
        }

        return view('dashboard.kurir.pesanan.index', compact('orders'));
    }

    /**
     * Mengambil detail pesanan berdasarkan ID.
     * LOGIKA DARI FILE 1: Implementasi paling lengkap dan konsisten.
     */
    public function getOrderDetails($id)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Tidak terautentikasi'], 401);
        }

        try {
            $order = Order::with(['customer', 'items.product'])
                ->where('id', $id)
                ->where('created_by_user_id', Auth::id())
                ->firstOrFail();

            $paidAtLabel = '';
            $paidAtFormatted = null;

            if ($order->paid_at) {
                $createdAt = Carbon::parse($order->created_at)->startOfDay();
                $paidAt = Carbon::parse($order->paid_at)->startOfDay();
                $diffInDays = $createdAt->diffInDays($paidAt);

                if ($diffInDays == 1) $paidAtLabel = ' (Harian)';
                elseif ($diffInDays >= 2 && $diffInDays <= 7) $paidAtLabel = ' (Mingguan)';
                $paidAtFormatted = Carbon::parse($order->paid_at)->isoFormat('D MMMM YYYY, HH:mm');
            }

            $activeReturn = $order->returns()->where('status', '!=', 'ditolak')->latest()->first();

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
                    'company_name' => $order->customer->company_name ?? 'N/A',
                    'name' => $order->customer->name ?? 'N/A',
                    'phone' => $order->customer->phone ?? 'N/A',
                    'address' => $order->customer->address ?? 'N/A',
                ],
                'products' => $order->items->map(function ($item) {
                    $returnedQuantity = DB::table('order_returns')
                        ->join('order_return_products', 'order_returns.id', '=', 'order_return_products.order_return_id')
                        ->where('order_returns.order_id', $item->order_id)
                        ->where('order_return_products.product_id', $item->product_id)
                        ->where('order_return_products.product_variant_id', $item->variant_id)
                        ->where('order_returns.status', '!=', 'ditolak')
                        ->sum('order_return_products.quantity');

                    return [
                        'product_id' => $item->product_id,
                        'variant_id' => $item->variant_id,
                        'name' => $item->product_name,
                        'variant_name' => $item->variant_name,
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                        'image_url' => $item->product->image_url ?? null,
                        'returned_quantity' => $returnedQuantity,
                    ];
                })->toArray(),
                'order_return' => $activeReturn ? [
                    'id' => $activeReturn->id,
                    'status' => $activeReturn->status,
                    'return_proof' => $activeReturn->return_proof,
                    'total_amount_returned' => $activeReturn->total_amount_returned,
                ] : null,
            ];

            return response()->json($formattedOrder);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Pesanan tidak ditemukan.'], 404);
        } catch (\Exception $e) {
            Log::error('Error fetching order details for order ID ' . $id . ': ' . $e->getMessage());
            return response()->json(['message' => 'Terjadi kesalahan internal.'], 500);
        }
    }

    /**
     * Mengunggah bukti pembayaran.
     * LOGIKA DARI FILE 1: Penamaan file kustom yang lebih baik.
     */
    public function uploadPaymentProof(Request $request, $id)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Tidak terautentikasi'], 401);
        }

        try {
            $request->validate(['payment_proof' => 'required|image|mimes:jpeg,png,jpg|max:2048']);

            $order = Order::where('id', $id)->where('created_by_user_id', Auth::id())->firstOrFail();

            if (!in_array($order->status, ['diterima_pembeli', 'selesai'])) {
                return response()->json(['message' => 'Bukti pembayaran hanya bisa diunggah setelah pesanan diterima oleh pembeli.'], 403);
            }

            if ($order->payment_proof) {
                Storage::disk('public')->delete($order->payment_proof);
            }

            $file = $request->file('payment_proof');
            $extension = $file->getClientOriginalExtension();
            $sanitizedInvoiceNumber = str_replace('/', '-', $order->invoice_number);
            $fileName = $sanitizedInvoiceNumber . '.' . $extension;
            $path = $file->storeAs('payment_proofs', $fileName, 'public');

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
            Log::error('Error uploading payment proof for order ID ' . $id . ': ' . $e->getMessage());
            return response()->json(['message' => 'Terjadi kesalahan internal.'], 500);
        }
    }

    /**
     * Mengubah status pesanan (diambil, diantar, diterima).
     */
    public function updateOrderStatus(Request $request, $id)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Tidak terautentikasi'], 401);
        }

        try {
            $validated = $request->validate(['new_status' => 'required|string|in:diambil,diantar,diterima_pembeli']);

            $order = Order::where('id', $id)->where('created_by_user_id', Auth::id())->firstOrFail();
            $newStatus = $validated['new_status'];
            $updateData = ['status' => $newStatus];

            switch ($newStatus) {
                case 'diambil':
                    if (in_array($order->status, ['diantar', 'diterima_pembeli', 'selesai'])) {
                        return response()->json(['message' => 'Status tidak dapat diubah kembali ke "Diambil".'], 400);
                    }
                    if (is_null($order->picked_up_at)) $updateData['picked_up_at'] = now();
                    break;
                case 'diantar':
                    if (in_array($order->status, ['diterima_pembeli', 'selesai'])) {
                        return response()->json(['message' => 'Status tidak dapat diubah kembali ke "Diantar".'], 400);
                    }
                    if (is_null($order->picked_up_at)) $updateData['picked_up_at'] = now();
                    if (is_null($order->delivered_at)) $updateData['delivered_at'] = now();
                    break;
                case 'diterima_pembeli':
                    if ($order->status === 'selesai') {
                        return response()->json(['message' => 'Status sudah "Selesai".'], 400);
                    }
                    if (is_null($order->picked_up_at)) $updateData['picked_up_at'] = now();
                    if (is_null($order->delivered_at)) $updateData['delivered_at'] = now();
                    if (is_null($order->received_by_buyer_at)) $updateData['received_by_buyer_at'] = now();
                    break;
            }

            $order->update($updateData);

            $updatedOrder = Order::find($id); // Re-fetch untuk data terbaru

            return response()->json([
                'message' => 'Status pesanan berhasil diperbarui.',
                'order' => [
                    'id' => $updatedOrder->id,
                    'status' => $updatedOrder->status,
                    'picked_up_at' => $updatedOrder->picked_up_at ? Carbon::parse($updatedOrder->picked_up_at)->isoFormat('D MMMM YYYY, HH:mm') : null,
                    'delivered_at' => $updatedOrder->delivered_at ? Carbon::parse($updatedOrder->delivered_at)->isoFormat('D MMMM YYYY, HH:mm') : null,
                    'received_by_buyer_at' => $updatedOrder->received_by_buyer_at ? Carbon::parse($updatedOrder->received_by_buyer_at)->isoFormat('D MMMM YYYY, HH:mm') : null,
                ]
            ], 200);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validasi gagal.', 'errors' => $e->errors()], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Pesanan tidak ditemukan.'], 404);
        } catch (\Exception $e) {
            Log::error('Error updating order status for order ID ' . $id . ': ' . $e->getMessage());
            return response()->json(['message' => 'Terjadi kesalahan internal.'], 500);
        }
    }

    /**
     * Mengambil item dari pesanan terakhir customer.
     * LOGIKA DARI FILE 2: Fitur baru yang penting.
     */
    public function getLastOrder($id)
    {
        $lastOrder = Order::where('customer_id', $id)->latest()->first();

        if (!$lastOrder) {
            return response()->json(['items' => []]);
        }

        $lastOrder->load('items');

        $cartItems = $lastOrder->items->map(function ($item) {
            return [
                'product_id'   => $item->product_id,
                'product_name' => $item->product_name,
                'variant_id'   => $item->variant_id,
                'variant_name' => $item->variant_name,
                'price'        => $item->price,
                'qty'          => $item->quantity,
            ];
        });

        return response()->json(['items' => $cartItems]);
    }
}
