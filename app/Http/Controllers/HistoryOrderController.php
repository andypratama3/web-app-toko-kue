<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\Order;

class HistoryOrderController extends Controller
{
    public function downloadInvoice($orderId)
    {
        $order = \App\Models\Order::with(['customer', 'createdBy', 'items'])->findOrFail($orderId);
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
    $order = \App\Models\Order::with(['customer', 'createdBy', 'items'])->findOrFail($orderId);
    $isPdf = false;
    return view('dashboard.admin.historys.invoice', compact('order', 'isPdf'));
    }
    public function index()
    {
        $user = Auth::user();
        $viewName = '';
        $orders = collect();

        if ($user->hasRole('admin')) {
            $viewName = 'dashboard.admin.historys.index';
            $orders = Order::with(['customer', 'createdBy'])
                ->where('region_id', $user->region_id)
                ->where('status', 'diverifikasi_admin')
                ->latest()
                ->get();
        } elseif ($user->hasRole('kurir')) {
            $viewName = 'dashboard.kurir.historys.index';
            $orders = Order::with(['customer'])
                ->where('created_by_user_id', $user->id)
                ->where('status', 'diverifikasi_admin')
                ->latest()
                ->get();
        } else {
            abort(403, 'Unauthorized');
        }

        return view($viewName, compact('orders'));
    }
}
