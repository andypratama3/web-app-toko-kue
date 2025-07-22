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

        return view('dashboards.admin.index', compact('admin', 'couriers'));
    }
}
