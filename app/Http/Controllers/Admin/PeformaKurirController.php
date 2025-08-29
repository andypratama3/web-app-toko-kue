<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PeformaKurirController extends Controller
{
    /**
     * Display a listing of courier performance.
     */
    public function index()
    {
        // Untuk sementara return view kosong, nanti akan diisi dengan logika bisnis
        return view('dashboard.admin.peforma-kurir.peforma-kurir');
    }

    /**
     * Display the specified courier performance.
     */
    public function show($kurir)
    {
        // Untuk sementara return view kosong dengan parameter kurir
        // Nanti akan diisi dengan logika untuk menampilkan detail peforma kurir tertentu
        return view('dashboard.admin.peforma-kurir.peforma-kurir', compact('kurir'));
    }
}
