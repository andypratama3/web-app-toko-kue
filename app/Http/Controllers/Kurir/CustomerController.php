<?php

namespace App\Http\Controllers\Kurir;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules;
use App\Models\Customer;

class CustomerController extends Controller
{
    /**
     * Menampilkan halaman manajemen kurir dengan data.
     */
    public function index()
    {
        $customers = Customer::select('nama', 'no_hp', 'alamat', 'region', 'note')
            ->where('region', Auth::user()->region_id)
            ->latest()
            ->paginate(10);


        return view('dashboard.kurir.customers.index', compact('customers'));
    }
}
