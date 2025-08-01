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

        // Keamanan: Pastikan kurir hanya mengakses dashboard regionnya
        if ($kurir->region !== $region || !$kurir->hasRole('kurir')) {
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

    // controller tambah data customer
    public function tambahCust(string $region)
    {
        $kurir = Auth::user();

        // Pastikan hanya kurir dan sesuai region
        if ($kurir->region !== $region || !$kurir->hasRole('kurir')) {
            abort(403, 'AKSES DITOLAK');
        }

        return view('dashboard.kurir.modal.tmbh-customer', compact('kurir'));
    }

    // controller tambah pesanan
    public function tambahPesanan(string $region)
    {
        $kurir = Auth::user();

        // Pastikan hanya kurir dan sesuai region
        if ($kurir->region !== $region || !$kurir->hasRole('kurir')) {
            abort(403, 'AKSES DITOLAK');
        }

        return view('dashboard.kurir.pages.tmbh-pesanan', compact('kurir'));
    }

    // controller data customer
    public function dataCust(string $region)
    {
        $kurir = Auth::user();

        // Pastikan hanya kurir dan sesuai region
        if ($kurir->region !== $region || !$kurir->hasRole('kurir')) {
            abort(403, 'AKSES DITOLAK');
        }

        return view('dashboard.kurir.pages.data-customer', compact('kurir'));
    }


    // Method untuk menyimpan data customer baru (dari modal)
    public function store(Request $request)
    {
        $request->validate([
            'nama'   => 'required|string|max:255',
            'alamat' => 'required|string',
            'nohp'   => 'required|string|max:15',
            'region' => 'required|in:sby,mlg,bali',
            'note'   => 'nullable|string|max:2000',
        ]);

        Customer::create([
            'nama'   => $request->nama,
            'alamat' => $request->alamat,
            'nohp'   => $request->nohp,
            'region' => $request->region,
            'note'   => $request->note,
        ]);

        return redirect()->back()->with('success', 'Customer berhasil ditambahkan.');
    }

    // Untuk menampilkan data customer dr database
    public function showCustomer()
    {
        $customers = Customer::all();

        return view('dashboard.kurir.pages.data-customer', compact('customers'));
    }
}
