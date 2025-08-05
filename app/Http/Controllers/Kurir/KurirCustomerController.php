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

class KurirCustomerController extends Controller
{
    /**
     * Format nomor telepon ke standar +62.
     */
    private function formatPhoneNumber($phone)
    {
        if (empty($phone)) {
            return null;
        }

        // Hapus karakter selain angka, kecuali tanda '+' di awal
        $cleanedPhone = preg_replace('/[^\d+]/', '', $phone);

        // Hapus awalan umum (0, 62, +62) untuk mendapatkan nomor dasar
        $baseNumber = preg_replace('/^(0|\+?62)/', '', $cleanedPhone);

        return '62' . $baseNumber;
    }

    /**
     * Menampilkan halaman manajemen customer dengan data.
     */
    public function index()
    {
        $customers = Customer::where('region_id', Auth::user()->region_id)
            ->latest()
            ->paginate(10);

        return view('dashboard.kurir.customers.index', compact('customers'));
    }

    /**
     * Menampilkan form create customer.
     */
    public function create()
    {
        return view('dashboard.kurir.customers.create');
    }

    /**
     * Menyimpan data customer baru.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'phone' => 'required|string|max:20',
            'note' => 'nullable|string',
        ]);

        Customer::create([
            'name' => $request->name,
            'address' => $request->address,
            'phone' => $this->formatPhoneNumber($request->phone),
            'note' => $request->note,
            'region_id' => Auth::user()->region_id,
        ]);

        return redirect()->route('kurir.customers.index')->with('success', 'Customer berhasil ditambahkan!');
    }
}
