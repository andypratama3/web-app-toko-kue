<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminNotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $notifications = AdminNotification::with(['order', 'region'])
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere('region_id', $user->region_id);
            })
            ->when($request->read === 'true', function ($q) {
                $q->where('is_read', true);
            }, function ($q) {
                $q->unread();
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('dashboard.admin.notifications.index', compact('notifications'));
    }

    public function markAllRead()
    {
        $user = Auth::user();

        AdminNotification::where('is_read', false)
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere('region_id', $user->region_id);
            })
            ->update(['is_read' => true]);

        return back()->with('success', 'Semua notifikasi ditandai dibaca.');
    }

    public function unreadCount(): int
    {
        $user = Auth::user();

        if (!$user) {
            return 0;
        }

        return AdminNotification::unread()
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere('region_id', $user->region_id);
            })
            ->count();
    }
}