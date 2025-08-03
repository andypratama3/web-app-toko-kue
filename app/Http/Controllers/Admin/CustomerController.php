<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerController extends Controller
{
    /**
     * Format nomor telepon ke standar +62.
     * Menghapus awalan 0, 62, atau +62 yang mungkin ada sebelum menambahkan +62.
     *
     * @param string|null $phone
     * @return string|null
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

    public function index(Request $request)
    {
        $search = $request->input('search');

        $customers = Customer::where('region_id', Auth::user()->region_id)
            ->when($search, function ($query, $searchTerm) {
                $query->where('name', 'like', "%{$searchTerm}%")
                      ->orWhere('phone', 'like', "%{$searchTerm}%");
            })
            ->latest()
            ->paginate(10);

        return view('dashboard.admin.customers.index', compact('customers'));
    }

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
            'phone' => $this->formatPhoneNumber($request->phone), // Gunakan fungsi format
            'note' => $request->note,
            'region_id' => Auth::user()->region_id,
        ]);

        return redirect()->route('admin.customers.index')->with('success', 'Customer baru berhasil ditambahkan.');
    }

    public function update(Request $request, Customer $customer)
    {
        if ($customer->region_id !== Auth::user()->region_id) {
            abort(403, 'AKSES DITOLAK');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'phone' => 'required|string|max:20',
            'note' => 'nullable|string',
        ]);

        $customer->update([
            'name' => $request->name,
            'address' => $request->address,
            'phone' => $this->formatPhoneNumber($request->phone), // Gunakan fungsi format
            'note' => $request->note,
        ]);

        return redirect()->route('admin.customers.index')->with('success', 'Data customer berhasil diperbarui.');
    }

    public function destroy(Customer $customer)
    {
        if ($customer->region_id !== Auth::user()->region_id) {
            abort(403, 'AKSES DITOLAK');
        }

        $customer->delete();

        return redirect()->route('admin.customers.index')->with('success', 'Customer berhasil dihapus.');
    }
}
