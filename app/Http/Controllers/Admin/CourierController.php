<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules;

class CourierController extends Controller
{
    /**
     * Menampilkan halaman manajemen kurir dengan data.
     */
    public function index()
    {
        // BENAR: Menggunakan region_id milik admin untuk filter
        $couriers = User::where('region_id', Auth::user()->region_id)
                        ->whereHas('roles', function ($query) {
                            $query->where('name', 'kurir');
                        })
                        ->latest()
                        ->paginate(10);

        return view('dashboard.admin.couriers.index', compact('couriers'));
    }

    /**
     * Method create() tidak lagi diperlukan karena form ada di dalam modal di halaman index.
     */
    public function create()
    {
        return redirect()->route('admin.couriers.index');
    }

    /**
     * Menyimpan kurir baru dari modal.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.couriers.index')
                ->withErrors($validator, 'create')
                ->withInput()
                ->with('error_modal_id', 'create-courier-modal');
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            // BENAR: Menggunakan region_id dari admin yang membuat
            'region_id' => Auth::user()->region_id,
        ]);

        $user->assignRole('kurir');

        return redirect()->route('admin.couriers.index')->with('success', 'Kurir baru berhasil ditambahkan.');
    }


    /**
     * Method edit() tidak lagi diperlukan karena form ada di dalam modal di halaman index.
     */
    public function edit(User $courier)
    {
        return redirect()->route('admin.couriers.index');
    }

    /**
     * Memperbarui data kurir dari modal.
     */
    public function update(Request $request, User $courier)
    {
        // BENAR: Membandingkan region_id (angka)
        if ($courier->region_id !== Auth::user()->region_id) {
            abort(403, 'AKSES DITOLAK');
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:'.User::class.',email,'.$courier->id],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.couriers.index')
                ->withErrors($validator, 'edit_'.$courier->id)
                ->withInput()
                ->with('error_modal_id', 'edit-courier-modal-' . $courier->id);
        }

        $courier->name = $request->name;
        $courier->email = $request->email;

        if ($request->filled('password')) {
            $courier->password = Hash::make($request->password);
        }

        $courier->save();

        return redirect()->route('admin.couriers.index')->with('success', 'Data kurir berhasil diperbarui.');
    }

    /**
     * Menghapus kurir.
     */
    public function destroy(User $courier)
    {
        // BENAR: Membandingkan region_id (angka)
        if ($courier->region_id !== Auth::user()->region_id) {
            abort(403, 'AKSES DITOLAK');
        }

        $courier->delete();

        return redirect()->route('admin.couriers.index')->with('success', 'Kurir berhasil dihapus.');
    }
}
