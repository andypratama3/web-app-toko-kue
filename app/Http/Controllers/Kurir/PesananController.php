<?php

namespace App\Http\Controllers\Kurir;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customer;
use Illuminate\Support\Facades\Auth;

class PesananController extends Controller
{
    //for dummy data
    public function index()
    {
        $customers = Customer::select('nama', 'no_hp', 'alamat', 'region', 'note')
            ->where('region', Auth::user()->region_id)
            ->latest()
            ->paginate(10);


        return view('dashboard.kurir.pesanan.index', compact('customers'));
    }

    //untuk menambah pesanan (dari button di dashboard)
    public function tambahPesanan()
    {
        return view('dashboard.kurir.pesanan.add'); 
    }
}
