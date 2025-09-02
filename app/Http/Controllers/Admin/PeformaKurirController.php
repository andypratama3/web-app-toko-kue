<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PeformaKurirController extends Controller
{
    /**
     * Display a listing of courier performance.
     */
    public function index(Request $request)
    {
        $admin = auth()->user();
        $regionId = $admin->region_id;

        // Ambil tahun dan bulan dari request, default ke bulan & tahun sekarang
        $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);

    // Dropdown tahun: dari tahun paling awal di order sampai 10 tahun ke depan
    $minYear = \App\Models\Order::min(DB::raw('YEAR(created_at)')) ?? now()->year;
    $maxYear = now()->year + 10;
    $years = range($minYear, $maxYear);

        // Untuk dropdown bulan
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        $startOfMonth = now()->setYear($year)->setMonth($month)->startOfMonth();
        $endOfMonth = now()->setYear($year)->setMonth($month)->endOfMonth();

        // Ambil data order yang sudah diverifikasi admin, region sesuai admin, bulan & tahun terpilih
        $orders = \App\Models\Order::where('region_id', $regionId)
            ->where('status', 'diverifikasi_admin')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->get();

        // Group by kurir dan hitung jumlah order
        $ranking = $orders->groupBy('created_by_user_id')
            ->map(function ($orders, $kurirId) {
                return [
                    'kurir_id' => $kurirId,
                    'jumlah_order' => $orders->count(),
                ];
            })
            ->sortByDesc('jumlah_order')
            ->values();

        // Ambil data nama kurir
        $kurirIds = $ranking->pluck('kurir_id')->all();
        $kurirs = \App\Models\User::whereIn('id', $kurirIds)->get()->keyBy('id');

        // Gabungkan nama kurir dan total customer yang dihandle ke ranking
        $ranking = $ranking->map(function ($item, $i) use ($kurirs) {
            $user = $kurirs[$item['kurir_id']] ?? null;
            $item['nama_kurir'] = $user ? $user->name : '-';
            $item['total_customer'] = $user ? $user->customers()->count() : 0;
            $item['rank'] = $i + 1;
            return $item;
        });

        return view('dashboard.admin.peforma-kurir.peforma-kurir', [
            'ranking' => $ranking,
            'bulan' => $months[$month] . ' ' . $year,
            'selectedMonth' => $month,
            'selectedYear' => $year,
            'months' => $months,
            'years' => $years,
        ]);
    }

    /**
     * Display the specified courier performance.
     */
    public function show($kurir)
    {
        // Untuk sementara return view kosong dengan parameter kurir
        // Nanti akan diisi dengan logika untuk menampilkan detail peforma kurir tertentu
        return view('dashboard.admin.peforma-kurir.peforma-kurir', compact('kurir'));
    }
}
