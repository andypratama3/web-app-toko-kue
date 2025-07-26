<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class KurirDashboardController extends Controller
{
    public function index(string $region)
    {
        $kurir = Auth::user();

        // Keamanan: Pastikan kurir hanya mengakses dashboard regionnya
        if ($kurir->region !== $region || !$kurir->hasRole('kurir')) {
            abort(403, 'AKSES DITOLAK');
        }

        return view('dashboard.kurir.dashboard', compact('kurir'));
    }

    // controller data seller
    public function tambahSeller(string $region)
    {
        $kurir = Auth::user();

        // Pastikan hanya kurir dan sesuai region
        if ($kurir->region !== $region || !$kurir->hasRole('kurir')) {
            abort(403, 'AKSES DITOLAK');
        }

        return view('dashboards.kurir.tambah-seller', compact('kurir'));
    }

    // controller profile
    public function profile()
    {
        $kurir = Auth::user();

        // Cek hanya role admin
        if (!$kurir->hasRole('kurir')) {
            abort(403, 'AKSES DITOLAK');
        }

        return view('dashboard.kurir.profile.profile', compact('kurir'));
    }
}
