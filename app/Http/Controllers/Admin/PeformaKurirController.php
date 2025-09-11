<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

class PeformaKurirController extends Controller
{
    /**
     * Export ranking performa kurir ke PDF.
     */
    public function exportPdf(Request $request)
    {
        $admin = auth()->user();
        $regionId = $admin->region_id;

        $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);

        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        $startOfMonth = now()->setYear($year)->setMonth($month)->startOfMonth();
        $endOfMonth = now()->setYear($year)->setMonth($month)->endOfMonth();

        $orders = \App\Models\Order::where('region_id', $regionId)
            ->where('status', 'diverifikasi_admin')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->get();

        $ranking = $orders->groupBy('created_by_user_id')
            ->map(function ($orders, $kurirId) {
                return [
                    'kurir_id' => $kurirId,
                    'jumlah_order' => $orders->count(),
                ];
            })
            ->sortByDesc('jumlah_order')
            ->values();

        $kurirIds = $ranking->pluck('kurir_id')->all();
        $kurirs = \App\Models\User::whereIn('id', $kurirIds)->get()->keyBy('id');

        $ranking = $ranking->map(function ($item, $i) use ($kurirs) {
            $user = $kurirs[$item['kurir_id']] ?? null;
            $item['nama_kurir'] = $user ? $user->name : '-';
            $item['total_customer'] = $user ? $user->customers()->count() : 0;
            $item['rank'] = $i + 1;
            return $item;
        });

        $bulan = $months[$month] . ' ' . $year;

        $pdf = Pdf::loadView('dashboard.admin.peforma-kurir.export-peforma-kurir', [
            'ranking' => $ranking,
            'bulan' => $bulan,
        ]);
        return $pdf->download('peforma-kurir-'.$bulan.'.pdf');
    }
    /**
     * Display a listing of courier performance.
     */
    public function index(Request $request)
    {
        $admin = auth()->user();
        $regionId = $admin->region_id;

        $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);

        $minYear = \App\Models\Order::min(DB::raw('YEAR(created_at)')) ?? now()->year;
        $maxYear = now()->year + 10;
        $years = range($minYear, $maxYear);

        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        $startOfMonth = now()->setYear($year)->setMonth($month)->startOfMonth();
        $endOfMonth = now()->setYear($year)->setMonth($month)->endOfMonth();

        $orders = \App\Models\Order::where('region_id', $regionId)
            ->where('status', 'diverifikasi_admin')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->get();

        $ranking = $orders->groupBy('created_by_user_id')
            ->map(function ($orders, $kurirId) {
                return [
                    'kurir_id' => $kurirId,
                    'jumlah_order' => $orders->count(),
                ];
            })
            ->sortByDesc('jumlah_order')
            ->values();

        $kurirIds = $ranking->pluck('kurir_id')->all();
        $kurirs = \App\Models\User::whereIn('id', $kurirIds)->get()->keyBy('id');

        $ranking = $ranking->map(function ($item, $i) use ($kurirs) {
            $user = $kurirs[$item['kurir_id']] ?? null;
            $item['nama_kurir'] = $user ? $user->name : '-';
            $item['total_customer'] = $user ? $user->customers()->count() : 0;
            $item['rank'] = $i + 1;
            return $item;
        });

        // [!code focus:start]
        // BUAT PAGINASI MANUAL
        $perPage = 10; // Tentukan jumlah item per halaman
        $currentPage = Paginator::resolveCurrentPage('page');
        $currentPageItems = $ranking->slice(($currentPage - 1) * $perPage, $perPage)->all();
        $paginatedRanking = new LengthAwarePaginator(
            $currentPageItems,
            count($ranking),
            $perPage,
            $currentPage,
            ['path' => Paginator::resolveCurrentPath()]
        );
        // [!code focus:end]

        return view('dashboard.admin.peforma-kurir.peforma-kurir', [
            'ranking' => $paginatedRanking, // [!code focus]
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
