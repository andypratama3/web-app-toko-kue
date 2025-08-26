<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules;

class CourierController extends Controller
{
    /**
     * Menampilkan halaman manajemen kurir dengan data.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $user = Auth::user();
        
        $couriersQuery = User::with('customers')
            ->where('region_id', $user->region_id)
            ->whereHas('roles', fn($query) => $query->where('name', 'kurir'))
            ->when($search, function ($query, $searchTerm) {
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('name', 'like', "%{$searchTerm}%")
                        ->orWhere('email', 'like', "%{$searchTerm}%");
                });
            });

        $couriers = $couriersQuery->latest()->paginate(10);

        if ($request->ajax()) {
            $desktopHtml = view('dashboard.admin.couriers._table_rows', compact('couriers'))->render();
            return response()->json(['desktop_html' => $desktopHtml]);
        }

        return view('dashboard.admin.couriers.index', compact('couriers'));
    }

    /**
     * Menyimpan kurir baru dari modal.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.couriers.index')
                ->withErrors($validator, 'create')
                ->withInput()
                ->with('error', 'Gagal menambahkan kurir. ' . $validator->errors()->first());
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'region_id' => Auth::user()->region_id,
        ]);

        $user->assignRole('kurir');

        return redirect()->route('admin.couriers.index')->with('success', 'Kurir "' . $user->name . '" berhasil ditambahkan.');
    }

    /**
     * Memperbarui data kurir dari modal.
     */
    public function update(Request $request, User $courier)
    {
        if ($courier->region_id !== Auth::user()->region_id) {
            abort(403, 'AKSES DITOLAK');
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:' . User::class . ',email,' . $courier->id],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.couriers.index')
                ->withErrors($validator, 'edit_' . $courier->id)
                ->withInput()
                ->with('error_modal_id', 'edit-courier-modal-' . $courier->id);
        }

        $courier->name = $request->name;
        $courier->email = $request->email;

        if ($request->filled('password')) {
            $courier->password = Hash::make($request->password);
        }

        $courier->save();

        return redirect()->route('admin.couriers.index')->with('success', 'Data kurir "' . $courier->name . '" berhasil diperbarui.');
    }

    /**
     * Memperbarui catatan untuk kurir.
     */
    public function updateNote(Request $request, User $courier)
    {
        if ($courier->region_id !== Auth::user()->region_id) {
            abort(403, 'AKSES DITOLAK');
        }

        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $courier->note = $validated['note'];
        $courier->save();

        return redirect()->route('admin.couriers.index')
            ->with('success', 'Catatan untuk kurir "' . $courier->name . '" berhasil diperbarui.');
    }

    /**
     * Menghapus kurir.
     */
    public function destroy(User $courier)
    {
        if ($courier->region_id !== Auth::user()->region_id) {
            abort(403, 'AKSES DITOLAK');
        }

        $courierName = $courier->name;
        $courier->delete();

        return redirect()->route('admin.couriers.index')->with('success', 'Kurir "' . $courierName . '" berhasil dihapus.');
    }
}
