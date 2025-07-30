<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AdminDashboardController extends Controller
{
    public function index(string $region)
    {
        $admin = Auth::user();

        // Keamanan: Pastikan hanya admin yang bisa mengakses
        if (!$admin->hasRole('admin')) {
            abort(403, 'AKSES DITOLAK');
        }

        // Ambil semua produk tanpa filter region
        $products = \App\Models\Product::paginate(12);

        // Ambil kurir sesuai region
        $couriers = \App\Models\User::role('kurir')->where('region', $region)->get();

        return view('dashboard.admin.dashboard', compact('products', 'couriers'));
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
