<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Models\User;

class OrderController extends Controller
{
    // Daftar pesanan pada region admin
    public function index()
    {
        $admin = Auth::user();
        $orders = Order::with(['customer', 'createdBy'])
            ->where('region_id', $admin->region_id)
            ->latest()
            ->get();
        return view('dashboard.admin.order-list.index', compact('orders'));
    }

    // Detail pesanan untuk modal verifikasi
    public function details($id)
    {
        $admin = Auth::user();
        $order = Order::with(['customer', 'createdBy', 'items'])
            ->where('region_id', $admin->region_id)
            ->findOrFail($id);
        return response()->json([
            'id' => $order->id,
            'invoice_number' => $order->invoice_number,
            'status' => $order->status,
            'total_amount' => $order->total_amount,
            'payment_proof' => $order->payment_proof,
            'customer' => [
                'name' => $order->customer->name ?? '-',
            ],
            'kurir_name' => $order->createdBy->name ?? '-',
            'items' => $order->items->map(function ($item) {
                return [
                    'product_name' => $item->product_name,
                    'variant_name' => $item->variant_name,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'subtotal' => $item->subtotal,
                ];
            }),
        ]);
    }

    // Verifikasi pesanan (ubah status menjadi diverifikasi_admin)
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

    // Tolak verifikasi pesanan (kembalikan status ke sebelumnya)
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
