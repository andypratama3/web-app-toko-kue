<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PeformaCustomerController extends Controller
{
    /**
     * Display a listing of customer performance.
     */
    public function index()
    {
        // Untuk sementara return view kosong, nanti akan diisi dengan logika bisnis
        return view('dashboard.admin.peforma-customer.peforma-customer');
    }

    /**
     * Display the specified customer performance.
     */
    public function show($customer)
    {
        // Untuk sementara return view kosong dengan parameter customer
        // Nanti akan diisi dengan logika untuk menampilkan detail peforma customer tertentu
        return view('dashboard.admin.peforma-customer.peforma-customer', compact('customer'));
    }
}
