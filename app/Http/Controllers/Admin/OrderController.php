<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;

class OrderController extends Controller
{
    /**
     * Menampilkan daftar pesanan pada region admin.
     * Logika peringatan diperbarui sesuai kategori customer.
     */
    public function index()
    {
        $admin = Auth::user();

        // 1. Eager load relasi customer beserta kategorinya ('customer.category')
        // Ini lebih efisien daripada memuatnya satu per satu di dalam loop.
        $orders = Order::with(['customer.category', 'createdBy'])
            ->where('region_id', $admin->region_id)
            ->latest()
            ->get();

        // 2. Logika warning dinamis berdasarkan kategori customer
        foreach ($orders as $order) {
            $order->show_warning = false;

            // Lanjutkan hanya jika bukti pembayaran kosong dan data customer lengkap
            if (is_null($order->payment_proof) && $order->customer && $order->customer->category) {

                $categoryName = strtolower($order->customer->category->name);
                $warningDays = 0; // Default tidak ada warning

                // Tetapkan ambang batas hari berdasarkan kategori
                if ($categoryName === 'reseller') {
                    $warningDays = 5;
                } elseif ($categoryName === 'supermarket') {
                    $warningDays = 28;
                }

                // Jika kategori sesuai dan ambang batas hari terlewati, tampilkan warning
                if ($warningDays > 0) {
                    $daysSinceCreation = Carbon::parse($order->created_at)->diffInDays(now());
                    if ($daysSinceCreation >= $warningDays) {
                        $order->show_warning = true;
                    }
                }
            }
        }

        return view('dashboard.admin.order-list.index', compact('orders'));
    }

    /**
     * Mengambil detail pesanan untuk modal verifikasi.
     * Diperbarui agar selaras dengan detail yang ada di PesananController.
     */
    public function details($id)
    {
        $admin = Auth::user();
        try {
            $order = Order::with(['customer', 'createdBy', 'items'])
                ->where('region_id', $admin->region_id)
                ->findOrFail($id);

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
                'kurir_name' => $order->createdBy->name ?? '-', // Tetap ada karena admin perlu tahu siapa kurirnya
                'items' => $order->items->map(function ($item) {
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
            \Log::error('Error fetching order details for order ID ' . $id . ' by admin: ' . $e->getMessage());
            return response()->json(['message' => 'Terjadi kesalahan internal.'], 500);
        }
    }

    /**
     * Verifikasi pesanan (ubah status menjadi diverifikasi_admin).
     */
    public function verify($id)
    {
        $admin = Auth::user();
        $order = Order::where('region_id', $admin->region_id)
            ->where('status', 'selesai')
            ->findOrFail($id);
        $order->status = 'diverifikasi_admin';
        $order->save();
        return response()->json(['message' => 'Pesanan berhasil diverifikasi.']);
    }

    /**
     * Tolak verifikasi pesanan (kembalikan status ke diterima_pembeli).
     */
    public function reject($id)
    {
        $admin = Auth::user();
        $order = Order::where('region_id', $admin->region_id)
            ->where('status', 'selesai')
            ->findOrFail($id);

        // 1. Hapus file bukti pembayaran yang lama dari storage
        if ($order->payment_proof) {
            Storage::disk('public')->delete($order->payment_proof);
        }

        // 2. Kembalikan status & hapus path file dari database
        $order->status = 'diterima_pembeli';
        $order->payment_proof = null; // Kosongkan field bukti pembayaran
        // paid_at biarkan terisi sebagai penanda bahwa ini adalah penolakan
        $order->save();

        return response()->json(['message' => 'Verifikasi pesanan ditolak. Status dikembalikan.']);
    }
}
