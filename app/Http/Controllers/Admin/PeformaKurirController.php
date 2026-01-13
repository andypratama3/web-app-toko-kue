<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Order;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;

class PeformaKurirController extends Controller
{
    /**
     * Export ranking performa kurir ke PDF.
     */
    public function exportPdf(Request $request)
    {
        $admin = auth()->user();
        $regionId = $admin->region_id;

        $dates = explode(' - ', $request->daterange ?? '');

        $startDate = $dates[0] ?? null;
        $endDate   = $dates[1] ?? null;

        $orders = Order::with('items', "customer")->where('region_id', $regionId)
            ->where('status', 'diverifikasi_admin')
            ->when($startDate && $endDate, function ($q) use ($startDate, $endDate) {
                $q->whereBetween('created_at', [
                    Carbon::parse($startDate)->startOfDay(),
                    Carbon::parse($endDate)->endOfDay()
                ]);
            })
            ->get();

        $ranking = $orders
            ->groupBy('created_by_user_id')
            ->map(function ($orders, $kurirId) {
    
                $kurir = $orders->first()->createdBy; // ambil user kurir

                return [
                    'kurir_id'      => $kurirId,
                    'nama_kurir'    => $kurir?->name,
                    'jumlah_order'  => $orders->count(),
                    'total'         => $orders->sum('total_amount'),
                    'orders'        => $orders, // opsional, kalau masih butuh detail
                ];
            })
            ->sortByDesc('jumlah_order')
            ->values();

        $kurirIds = $ranking->pluck('kurir_id')->all();
        $kurirs = User::whereIn('id', $kurirIds)->get()->keyBy('id');

        $ranking = $ranking->map(function ($item, $i) use ($kurirs) {
            $user = $kurirs[$item['kurir_id']] ?? null;
            $item['nama_kurir'] = $user ? $user->name : '-';
            $item['total_customer'] = $user ? $user->customers()->count() : 0;
            $item['rank'] = $i + 1;
            return $item;
        });

        // dd($ranking->toArray());

        $pdf = Pdf::loadView('dashboard.admin.peforma-kurir.export-peforma-kurir', [
            'ranking' => $ranking,
            'daterange' => $request->daterange,
        ])->setPaper('a4', 'portrait')->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => false,
            'dpi' => 72,
            'defaultFont' => 'DejaVu Sans',
        ]);

        $filename = sprintf(
            'performa-kurir_%s_to_%s.pdf',
            $startDate,
            $endDate
        );
        return $pdf->download($filename);
    }
    /**
     * Display a listing of courier performance.
     */
    public function index(Request $request)
    {
        $admin = auth()->user();
        $regionId = $admin->region_id;

        $dates = explode(' - ', $request->daterange ?? '');

        $startDate = $dates[0] ?? null;
        $endDate   = $dates[1] ?? null;

        $orders = \App\Models\Order::where('region_id', $regionId)
            ->where('status', 'diverifikasi_admin')
            ->when($startDate && $endDate, function ($q) use ($startDate, $endDate) {
                $q->whereBetween('created_at', [
                    Carbon::parse($startDate)->startOfDay(),
                    Carbon::parse($endDate)->endOfDay()
                ]);
            })
            ->get();

        $ranking = $orders->groupBy('created_by_user_id')
            ->map(function ($orders, $kurirId) {

                $totalAmount = $orders->sum('total_amount');
                return [
                    'kurir_id' => $kurirId,
                    'jumlah_order' => $orders->count(),
                    'total' => $totalAmount,
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
