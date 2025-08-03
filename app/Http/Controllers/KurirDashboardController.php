<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Customer;

class KurirDashboardController extends Controller
{
    public function index(string $region)
    {
        $kurir = Auth::user();

        // BENAR: Membandingkan slug dari URL dengan slug dari relasi region
        if ($kurir->region->slug !== $region || !$kurir->hasRole('kurir')) {
            abort(403, 'AKSES DITOLAK');
        }

        return view('dashboard.kurir.dashboard', compact('kurir'));
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
