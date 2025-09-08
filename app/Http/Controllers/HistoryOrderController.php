<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Storage;

class HistoryOrderController extends Controller
{
    /**
     * Endpoint JSON untuk detail history pesanan & retur (untuk modal show)
     * VERSI DIPERBAIKI
     */
    public function details(Order $order)
    {
        // Pastikan admin hanya bisa mengakses order di regionnya
        if (Auth::user()->region_id !== $order->region_id) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        try {
            // Eager load semua relasi yang dibutuhkan untuk modal
            $order->load([
                'customer',
                'createdBy', // Relasi untuk kurir
                'items',
                'returns' => function ($query) {
                    // Ambil retur yang aktif (tidak ditolak) dan urutkan dari yang terbaru
                    $query->where('status', '!=', 'ditolak')->latest();
                },
                'returns.returnedProducts.product', // Relasi ke produk yg diretur
                'returns.returnedProducts.variant'  // Relasi ke varian yg diretur
            ]);

            $paidAtFormatted = $order->paid_at ? Carbon::parse($order->paid_at)->isoFormat('D MMMM YYYY, HH:mm') : 'Belum Lunas';

            // Ambil retur aktif pertama dari koleksi yang sudah di-load
            $activeReturn = $order->returns->first();

            // Format data agar mudah dikonsumsi oleh JavaScript
            $formattedOrder = [
                'id' => $order->id,
                'invoice_number' => $order->invoice_number,
                'customer_name' => $order->customer->name ?? 'N/A',
                'customer_phone' => $order->customer->phone ?? 'N/A',
                'customer_company' => $order->customer->company_name ?? 'N/A',
                'customer_address' => $order->customer->address ?? 'N/A',
                'payment_method' => $order->payment_method ?? '-',
                'total_amount' => $order->total_amount ?? 0,
                'created_at' => $order->created_at->isoFormat('D MMMM YYYY, HH:mm'),
                'paid_at' => $paidAtFormatted,
                'payment_proof_url' => $order->payment_proof ? Storage::url($order->payment_proof) : null,

                'items' => $order->items->map(fn($item) => [
                    'name' => $item->product_name,
                    'variant' => $item->variant_name,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'subtotal' => $item->subtotal,
                ])->toArray(),

                // Sertakan detail retur jika ada
                'return_details' => $activeReturn ? [
                    'status' => $activeReturn->status,
                    'total_amount_returned' => $activeReturn->total_amount_returned,
                    'return_proof_url' => $activeReturn->return_proof ? Storage::url($activeReturn->return_proof) : null,
                    'returned_products' => $activeReturn->returnedProducts->map(function ($p) {
                        $productName = $p->product ? $p->product->name : 'Produk Telah Dihapus';
                        $variantName = $p->variant ? $p->variant->name : null;
                        return [
                            'name' => $productName,
                            'variant' => $variantName,
                            'quantity' => $p->quantity,
                            'price' => $p->price,
                            'subtotal' => $p->subtotal,
                        ];
                    })->toArray()
                ] : null,
            ];

            return response()->json($formattedOrder);
        } catch (\Exception $e) {
            Log::error('Error fetching history details for order ID ' . $order->id . ': ' . $e->getMessage());
            return response()->json(['message' => 'Terjadi kesalahan internal.'], 500);
        }
    }

    public function downloadInvoice($orderId)
    {
        $order = \App\Models\Order::with([
            'customer',
            'createdBy',
            'items',
            'returns' => function ($query) {
                $query->where('status', '!=', 'ditolak')->latest();
            },
            'returns.returnedProducts.product',
            'returns.returnedProducts.variant',
        ])->findOrFail($orderId);
        $isPdf = true;
        $pdf = \PDF::loadView('dashboard.admin.historys.invoice', compact('order', 'isPdf'))
            ->setPaper('A4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'margin_top'    => 20,
                'margin_right'  => 20,
                'margin_bottom' => 20,
                'margin_left'   => 20,
            ]);
        $customerName = preg_replace('/[^A-Za-z0-9]/', '', $order->customer->name ?? 'Customer');
        $invoiceNumber = str_replace(['/', '\\'], '-', $order->invoice_number);
        $filename = $customerName . '-' . $invoiceNumber . '.pdf';
        return $pdf->download($filename);
    }

    public function invoice($orderId)
    {
        $order = \App\Models\Order::with([
            'customer',
            'createdBy',
            'items',
            'returns' => function ($query) {
                $query->where('status', '!=', 'ditolak')->latest();
            },
            'returns.returnedProducts.product',
            'returns.returnedProducts.variant',
        ])->findOrFail($orderId);
        $isPdf = false;
        return view('dashboard.admin.historys.invoice', compact('order', 'isPdf'));
    }

    public function index(Request $request) // Tambahkan Request
    {
        $user = Auth::user();

        if (!$user->hasRole('admin') && !$user->hasRole('kurir')) {
            abort(403, 'Unauthorized');
        }

        $ordersQuery = Order::with(['customer', 'createdBy', 'returns' => function ($query) {
            $query->where('status', '!=', 'ditolak')->latest();
        }]);

        if ($user->hasRole('admin')) {
            $ordersQuery->where('region_id', $user->region_id);
        } else {
            $ordersQuery->where('created_by_user_id', $user->id);
        }

        $orders = $ordersQuery->where('status', 'diverifikasi_admin')
            ->latest()
            ->paginate(10);

        foreach ($orders as $order) {
            $order->has_return = $order->returns->isNotEmpty();
            $order->final_total = $order->has_return ? $order->total_amount - $order->returns->first()->total_amount_returned : $order->total_amount;
            $order->payment_status = $order->paid_at
                ? ['text' => 'Lunas', 'class' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300']
                : ['text' => 'Belum Lunas', 'class' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300'];
        }

        // BARU: Logika untuk menangani request AJAX (untuk load more/infinite scroll)
        if ($request->ajax()) {
            $viewPath = $user->hasRole('admin') ? 'dashboard.admin.historys._card' : 'dashboard.kurir.historys._card';
            $html = view($viewPath, ['orders' => $orders])->render();
            return response()->json(['html' => $html]);
        }

        $viewName = $user->hasRole('admin')
            ? 'dashboard.admin.historys.index'
            : 'dashboard.kurir.historys.index';

        return view($viewName, compact('orders'));
    }
}
