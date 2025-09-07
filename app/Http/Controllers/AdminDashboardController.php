<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Models\User;
use App\Models\Order;
use App\Models\Customer;
use App\Models\Region;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    public function index(string $region)
    {
        $admin = Auth::user();

        if (!$admin->hasRole('admin')) {
            abort(403, 'AKSES DITOLAK');
        }

        $regionModel = Region::where('slug', $region)->firstOrFail();
        $regionId = $regionModel->id;

        // --- DATA UTAMA UNTUK HARI INI ---
        $incomeToday = Order::where('region_id', $regionId)
            ->whereDate('created_at', Carbon::today())
            ->where(function ($query) {
                $query->whereDoesntHave('returns')->orWhereHas('returns', function ($subQuery) {
                    $subQuery->where('status', 'selesai');
                });
            })
            ->sum('total_amount');

        $totalSalesToday = Order::where('region_id', $regionId)->whereDate('created_at', Carbon::today())->count();
        $totalCustomersInRegion = Customer::where('region_id', $regionId)->count();
        $newCustomersToday = Customer::where('region_id', $regionId)->whereDate('created_at', Carbon::today())->count();

        // --- LOGIKA BARU UNTUK PERHITUNGAN PERSENTASE ---

        // 1. Income: Hari ini vs Kemarin
        $incomeYesterday = Order::where('region_id', $regionId)
            ->whereDate('created_at', Carbon::yesterday())
            ->where(function ($query) {
                $query->whereDoesntHave('returns')->orWhereHas('returns', function ($subQuery) {
                    $subQuery->where('status', 'selesai');
                });
            })
            ->sum('total_amount');
        if ($incomeYesterday > 0) {
            $incomePercentageChange = (($incomeToday - $incomeYesterday) / $incomeYesterday) * 100;
        } elseif ($incomeToday > 0) {
            $incomePercentageChange = 100; // Jika kemarin 0 dan hari ini ada, anggap naik 100%
        } else {
            $incomePercentageChange = 0;
        }

        // 2. Total Sales: Bulan ini vs Bulan lalu
        $totalSalesThisMonth = Order::where('region_id', $regionId)->whereYear('created_at', Carbon::now()->year)->whereMonth('created_at', Carbon::now()->month)->count();
        $totalSalesLastMonth = Order::where('region_id', $regionId)->whereYear('created_at', Carbon::now()->subMonth()->year)->whereMonth('created_at', Carbon::now()->subMonth()->month)->count();
        if ($totalSalesLastMonth > 0) {
            $salesPercentageChange = (($totalSalesThisMonth - $totalSalesLastMonth) / $totalSalesLastMonth) * 100;
        } elseif ($totalSalesThisMonth > 0) {
            $salesPercentageChange = 100;
        } else {
            $salesPercentageChange = 0;
        }

        // 3. Customer (Region): Minggu ini vs Minggu lalu
        $newCustomersThisWeek = Customer::where('region_id', $regionId)->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->count();
        $newCustomersLastWeek = Customer::where('region_id', $regionId)->whereBetween('created_at', [Carbon::now()->subWeek()->startOfWeek(), Carbon::now()->subWeek()->endOfWeek()])->count();
        if ($newCustomersLastWeek > 0) {
            $customerPercentageChange = (($newCustomersThisWeek - $newCustomersLastWeek) / $newCustomersLastWeek) * 100;
        } elseif ($newCustomersThisWeek > 0) {
            $customerPercentageChange = 100;
        } else {
            $customerPercentageChange = 0;
        }


        // 4. New Customer: Hari ini vs Kemarin
        $newCustomersYesterday = Customer::where('region_id', $regionId)->whereDate('created_at', Carbon::yesterday())->count();
        if ($newCustomersYesterday > 0) {
            $newCustomerPercentageChange = (($newCustomersToday - $newCustomersYesterday) / $newCustomersYesterday) * 100;
        } elseif ($newCustomersToday > 0) {
            $newCustomerPercentageChange = 100;
        } else {
            $newCustomerPercentageChange = 0;
        }


        $couriers = User::role('kurir')->whereHas('region', function ($query) use ($region) {
            $query->where('slug', $region);
        })->latest()->paginate(5, ['*'], 'couriers_page');

        // Kirim semua variabel ke view, termasuk variabel persentase yang baru
        return view('dashboard.admin.dashboard', compact(
            'couriers',
            'incomeToday',
            'totalSalesToday',
            'totalCustomersInRegion',
            'newCustomersToday',
            'incomePercentageChange',
            'salesPercentageChange',
            'customerPercentageChange',
            'newCustomerPercentageChange'
        ));
    }

    public function profile()
    {
        $admin = Auth::user();
        if (!$admin->hasRole('admin')) {
            abort(403, 'AKSES DITOLAK');
        }
        return view('dashboard.admin.profile.profile', compact('admin'));
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();
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
