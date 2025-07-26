<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AdminDashboardController extends Controller
{
    public function index(string $region)
    {
        $admin = Auth::user();

        // Keamanan: Pastikan hanya admin dari region yang benar yang bisa mengakses
        if ($admin->region !== $region || !$admin->hasRole('admin')) {
            abort(403, 'AKSES DITOLAK');
        }

        // Ambil data kurir HANYA dari region admin yang sedang login
        $couriers = User::role('kurir')
                        ->where('region', $admin->region)
                        ->get();

        return view('dashboard.admin.dashboard', compact('admin', 'couriers'));
    }

    public function profile()
    {
        $admin = Auth::user();

        // Cek hanya role admin
        if (!$admin->hasRole('admin')) {
            abort(403, 'AKSES DITOLAK');
        }

        return view('dashboard.admin.profile.profile', compact('admin'));
    }
}
