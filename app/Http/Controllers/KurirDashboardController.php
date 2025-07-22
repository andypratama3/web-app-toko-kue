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

        return view('dashboards.kurir.index', compact('kurir'));
    }
}
