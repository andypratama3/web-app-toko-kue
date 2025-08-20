<?php

namespace App\Http\Controllers; // Pindahkan ke namespace utama

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    /**
     * Format nomor telepon ke standar 62.
     */
    private function formatPhoneNumber($phone)
    {
        if (empty($phone)) {
            return null;
        }
        $cleanedPhone = preg_replace('/[^\d]/', '', $phone);
        $baseNumber = preg_replace('/^(0|\+?62)/', '', $cleanedPhone);
        return '62' . $baseNumber;
    }

    /**
     * Menampilkan daftar customer berdasarkan role.
     */

    public function index(Request $request)
    {
        $user = Auth::user();
        $search = $request->input('search');

        $customersQuery = Customer::where('region_id', $user->region_id)
            ->when($search, function ($query, $searchTerm) {
                $query->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('phone', 'like', "%{$searchTerm}%")
                    ->orWhere('address', 'like', "%{$searchTerm}%");
            });

        $customers = $customersQuery->latest()->paginate(10);

        // Jika ini adalah request AJAX dari live search
        if ($request->ajax()) {
            // Tentukan path view berdasarkan role user
            $baseViewPath = $user->hasRole('admin')
                ? 'dashboard.admin.customers.'
                : 'dashboard.kurir.customers.';

            if ($user->hasRole('admin')) {
                // Hanya render dan kirim HTML untuk tabel desktop
                $desktopHtml = view($baseViewPath . '_table_rows', compact('customers'))->render();
                return response()->json([
                    'desktop_html' => $desktopHtml,
                ]);
            }
            // JIKA PENGGUNA ADALAH KURIR (ATAU ROLE LAINNYA):
            else {
                // Render dan kirim HTML untuk desktop dan mobile
                $desktopHtml = view($baseViewPath . '_table_rows', compact('customers'))->render();
                $mobileHtml = view($baseViewPath . '_card_view', compact('customers'))->render();

                return response()->json([
                    'desktop_html' => $desktopHtml,
                    'mobile_html' => $mobileHtml,
                ]);
            }
        }

        // Jika request biasa, tampilkan halaman lengkap
        $view = $user->hasRole('admin')
            ? 'dashboard.admin.customers.index'
            : 'dashboard.kurir.customers.index';

        return view($view, compact('customers'));
    }

    /**
     * Menyimpan customer baru.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $formattedPhone = $this->formatPhoneNumber($request->phone);
        $request->merge(['phone' => $formattedPhone]);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'address' => [
                'required',
                'string',
                Rule::unique('customers')->where(function ($query) use ($formattedPhone, $user) {
                    return $query->where('phone', $formattedPhone)
                        ->where('region_id', $user->region_id);
                }),
            ],
            'phone' => 'required|string|max:20',
            'note' => 'nullable|string',
        ], [
            'address.unique' => 'Customer dengan alamat dan nomor telepon ini sudah terdaftar.'
        ]);

        $routeName = $user->hasRole('admin') ? 'admin.customers.index' : 'kurir.customers.index';

        if ($validator->fails()) {
            return redirect()->route($routeName)
                ->withErrors($validator)
                ->withInput()
                ->with('error', $validator->errors()->first('address'));
        }

        $customer = Customer::create([
            'name' => $request->name,
            'address' => $request->address,
            'phone' => $formattedPhone,
            'note' => $request->note,
            'region_id' => $user->region_id,
        ]);

        return redirect()->route($routeName)->with('success', 'Customer "' . $customer->name . '" berhasil ditambahkan.');
    }

    /**
     * Memperbarui data customer.
     */
    public function update(Request $request, Customer $customer)
    {
        $user = Auth::user();
        if ($customer->region_id !== $user->region_id) {
            abort(403, 'AKSES DITOLAK');
        }

        $formattedPhone = $this->formatPhoneNumber($request->phone);
        $request->merge(['phone' => $formattedPhone]);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'address' => [
                'required',
                'string',
                Rule::unique('customers')->where(function ($query) use ($formattedPhone, $user) {
                    return $query->where('phone', $formattedPhone)
                        ->where('region_id', $user->region_id);
                })->ignore($customer->id),
            ],
            'phone' => 'required|string|max:20',
            'note' => 'nullable|string',
        ], [
            'address.unique' => 'Customer dengan alamat dan nomor telepon ini sudah terdaftar.'
        ]);

        $routeName = $user->hasRole('admin') ? 'admin.customers.index' : 'kurir.customers.index';

        if ($validator->fails()) {
            return redirect()->route($routeName)
                ->withErrors($validator)
                ->withInput()
                ->with('error', $validator->errors()->first('address'));
        }

        $customer->update([
            'name' => $request->name,
            'address' => $request->address,
            'phone' => $formattedPhone,
            'note' => $request->note
        ]);

        return redirect()->route($routeName)->with('success', 'Data customer "' . $customer->name . '" berhasil diperbarui.');
    }

    /**
     * Memperbarui catatan customer.
     */
    public function updateNote(Request $request, Customer $customer)
    {
        $user = Auth::user();
        if ($customer->region_id !== $user->region_id) {
            abort(403, 'AKSES DITOLAK');
        }

        $request->validate(['note' => 'nullable|string']);
        $customer->update(['note' => $request->note]);

        $routeName = $user->hasRole('admin') ? 'admin.customers.index' : 'kurir.customers.index';
        return redirect()->route($routeName)->with('success', 'Catatan untuk "' . $customer->name . '" berhasil diperbarui.');
    }

    /**
     * Menghapus customer.
     */
    public function destroy(Customer $customer)
    {
        $user = Auth::user();
        if ($customer->region_id !== $user->region_id) {
            abort(403, 'AKSES DITOLAK');
        }

        $customerName = $customer->name;
        $customer->delete();

        $routeName = $user->hasRole('admin') ? 'admin.customers.index' : 'kurir.customers.index';
        return redirect()->route($routeName)->with('success', 'Customer "' . $customerName . '" berhasil dihapus.');
    }
}
