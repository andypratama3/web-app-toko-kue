<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Models\Customer;

class KurirDashboardController extends Controller
{
    public function index(string $region)
    {
        $kurir = Auth::user();

        // BENAR: Membandingkan slug dari URL dengan slug dari relasi region
        if (!$kurir->hasRole('kurir')) {
            abort(403, 'AKSES DITOLAK');
        }

        return view('dashboard.kurir.dashboard', compact('kurir'));
    }

    // controller profile
    public function profile()
    {
        $kurir = Auth::user();

        // Cek hanya role kurir
        if (!$kurir->hasRole('kurir')) {
            abort(403, 'AKSES DITOLAK');
        }

        return view('dashboard.kurir.profile.profile', compact('kurir'));
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        // Cek hanya role kurir
        if (!$user->hasRole('kurir')) {
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

        return redirect()->route('kurir.profile')->with('success', 'Profile updated successfully.');
    }

    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        // Cek hanya role kurir
        if (!$user->hasRole('kurir')) {
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

        return redirect()->route('kurir.profile')->with('success', 'Password updated successfully.');
    }
}
