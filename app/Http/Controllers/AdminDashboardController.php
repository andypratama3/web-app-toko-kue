<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Models\User;

class AdminDashboardController extends Controller
{
    public function index(string $region)
    {
        $admin = Auth::user();

        // Keamanan: Pastikan hanya admin yang bisa mengakses
        if (!$admin->hasRole('admin')) {
            abort(403, 'AKSES DITOLAK');
        }

        // Ambil semua produk tanpa filter region
        $products = \App\Models\Product::paginate(12);

        // BENAR: Mengambil kurir dengan memfilter relasi 'region' berdasarkan slug
        $couriers = User::role('kurir')->whereHas('region', function ($query) use ($region) {
            $query->where('slug', $region);
        })->get();

        return view('dashboard.admin.dashboard', compact('products', 'couriers'));
    }

    public function profile()
    {
        $admin = Auth::user();

        // Cek hanya role admin
        if (!$admin->hasRole('admin')) {
            abort(403, 'AKSES DITOLAK');
        }

        return view('dashboard.admin.profile.profile', compact('admin'));
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        // Cek hanya role admin
        if (!$user->hasRole('admin')) {
            abort(403, 'AKSES DITOLAK');
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'photo' => ['nullable', 'image', 'max:1024'],
        ]);

        $user->name = $request->name;
        $user->email = $request->email;

        if ($request->hasFile('photo')) {
            $user->updateProfilePhoto($request->file('photo'));
        }

        $user->save();

        return redirect()->route('admin.profile')->with('success', 'Profile updated successfully.');
    }

    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        // Cek hanya role admin
        if (!$user->hasRole('admin')) {
            abort(403, 'AKSES DITOLAK');
        }

        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'The provided password does not match your current password.']);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return redirect()->route('admin.profile')->with('success', 'Password updated successfully.');
    }
}
