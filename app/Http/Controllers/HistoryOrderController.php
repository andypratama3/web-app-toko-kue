<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\Order;

class HistoryOrderController extends Controller
{
    public function invoice($orderId)
    {
        $order = \App\Models\Order::with(['customer', 'createdBy', 'items'])->findOrFail($orderId);
        return view('dashboard.admin.historys.invoice', compact('order'));
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
