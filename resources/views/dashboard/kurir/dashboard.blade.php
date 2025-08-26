@extends('layouts.argon')
@section('title', 'Dashboard Kurir')
@section('page_title', 'Dashboard')

@section('content')
<?php

use Carbon\Carbon; // Menggunakan Carbon untuk memanipulasi tanggal dan waktu
use Illuminate\Support\Facades\Auth; // Mengimpor facade Auth
use Illuminate\Support\Str; // Mengimpor helper String

$today = Carbon::today(); // Mendapatkan tanggal hari ini

// Mendapatkan region_id dari kurir yang sedang login
$loggedInCourierRegionId = Auth::user()->region_id;
$loggedInCourierId = Auth::id();

// Menghitung total pesanan yang dibuat hari ini
$totalOrdersToday = App\Models\Order::whereDate('created_at', $today)->count();

// Menghitung jumlah semua pelanggan yang terdaftar di tabel customers sesuai dengan region_id kurir yang login
$totalCustomersInRegion = App\Models\Customer::where('region_id', $loggedInCourierRegionId)->count();

// Menghitung jumlah pesanan dengan status 'selesai' yang diperbarui hari ini
$completedOrdersToday = App\Models\Order::whereDate('updated_at', $today)
    ->where('status', 'selesai') // Filter berdasarkan status 'selesai'
    ->count();

// Menghitung jumlah pesanan yang statusnya 'diterima_pembeli' dan telah diterima pembeli hari ini
$receivedByBuyerToday = App\Models\Order::whereDate('received_by_buyer_at', $today)
    ->where('status', 'diterima_pembeli') // Filter berdasarkan status 'diterima_pembeli'
    ->count();

// Mengambil 3 pesanan terbaru yang dibuat oleh kurir yang login
$latestOrders = App\Models\Order::where('created_by_user_id', $loggedInCourierId)
    ->with(['customer', 'items']) // Eager load relasi customer dan items
    ->latest() // Mengurutkan berdasarkan created_at (terbaru dulu)
    ->take(3) // Membatasi hanya 3 hasil
    ->get();
?>
<!-- Enhanced Dashboard Cards -->
<div class="w-full max-w full">
    <div class="flex flex-wrap gap-6 -mx-3">
        <div class="w-full max-w-full px-3 space-y-4 lg:flex-nome">
            <!-- Enhanced Achievement Card -->
            <div class="mb-6">
                <div
                    class="relative overflow-hidden flex flex-col flex-none max-w-full gap-4 p-6 px-6 py-6 shadow-2xl rounded-3xl xl:gap-0 bg-gradient-to-br from-amber-50 via-orange-50 to-yellow-50 dark:from-slate-800 dark:via-slate-700 dark:to-slate-800 border border-amber-100 dark:border-slate-600">
                    <!-- Background Pattern -->
                    <div class="absolute top-0 right-0 w-32 h-32 opacity-10">
                        <div
                            class="w-full h-full bg-gradient-to-br from-yellow-400 to-orange-500 rounded-full transform rotate-45 translate-x-8 -translate-y-8">
                        </div>
                    </div>

                    <div class="relative z-10 flex items-center justify-between mb-6">
                        <div class="flex items-center gap-3">
                            <div class="p-3 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-2xl shadow-lg">
                                <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                            </div>
                            <div>
                                <div class="text-2xl font-bold bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent dark:from-white dark:to-gray-300"
                                    id="ucapan">Selamat</div>
                                <div class="text-sm text-gray-600 dark:text-gray-400 font-medium">Your Achievement Today
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Stats Grid -->
                    <div class="relative z-10 grid grid-cols-2 md:grid-cols-4 gap-4">
                        <!-- Total Pesanan Card -->
                        <div class="p-3 bg-white/60 dark:bg-slate-700/60 backdrop-blur-sm rounded-2xl border border-white/20 dark:border-slate-600/20 shadow-lg">
                            <div class="flex items-center space-x-3">
                                <div class="flex-shrink-0">
                                    <div class="flex items-center justify-center w-10 h-10 bg-blue-100 rounded-full dark:bg-blue-900/50">
                                        <i class="text-lg text-blue-600 fas fa-shopping-basket dark:text-blue-400"></i>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs text-gray-500 truncate dark:text-gray-400">Total</p>
                                    <p class="text-xl font-bold text-gray-800 truncate dark:text-white">{{ $totalOrdersToday }}</p>
                                </div>
                            </div>
                        </div>
                        <!-- Jumlah Customer Card -->
                        <div class="p-3 bg-white/60 dark:bg-slate-700/60 backdrop-blur-sm rounded-2xl border border-white/20 dark:border-slate-600/20 shadow-lg">
                            <div class="flex items-center space-x-3">
                                <div class="flex-shrink-0">
                                    <div class="flex items-center justify-center w-10 h-10 bg-orange-100 rounded-full dark:bg-orange-900/50">
                                        <i class="text-lg text-orange-600 fas fa-users dark:text-orange-400"></i>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs text-gray-500 truncate dark:text-gray-400">Customer</p>
                                    <p class="text-xl font-bold text-gray-800 truncate dark:text-white">{{ $totalCustomersInRegion }}</p>
                                </div>
                            </div>
                        </div>
                        <!-- Selesai Card -->
                        <div class="p-3 bg-white/60 dark:bg-slate-700/60 backdrop-blur-sm rounded-2xl border border-white/20 dark:border-slate-600/20 shadow-lg">
                            <div class="flex items-center space-x-3">
                                <div class="flex-shrink-0">
                                    <div class="flex items-center justify-center w-10 h-10 bg-green-100 rounded-full dark:bg-green-900/50">
                                        <i class="text-lg text-green-600 fas fa-check-circle dark:text-green-400"></i>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs text-gray-500 truncate dark:text-gray-400">Selesai</p>
                                    <p class="text-xl font-bold text-gray-800 truncate dark:text-white">{{ $completedOrdersToday }}</p>
                                </div>
                            </div>
                        </div>
                        <!-- Diterima Pembeli Card -->
                        <div class="p-3 bg-white/60 dark:bg-slate-700/60 backdrop-blur-sm rounded-2xl border border-white/20 dark:border-slate-600/20 shadow-lg">
                            <div class="flex items-center space-x-3">
                                <div class="flex-shrink-0">
                                    <div class="flex items-center justify-center w-10 h-10 bg-purple-100 rounded-full dark:bg-purple-900/50">
                                        <i class="text-lg text-purple-600 fas fa-handshake dark:text-purple-400"></i>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs text-gray-500 truncate dark:text-gray-400">Diterima</p>
                                    <p class="text-xl font-bold text-gray-800 truncate dark:text-white">{{ $receivedByBuyerToday }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Enhanced Action Cards -->
            <div class="grid gap-4">
                <div class="flex flex-wrap -mx-3">
                    <!-- Enhanced Pesanan Card -->
                    <a href="{{ route('kurir.pesanan.create') }}" class="w-1/2 px-3">
                        <div
                            class="group relative overflow-hidden flex items-center justify-center md:justify-start p-4 transition-all duration-300 ease-out transform bg-gradient-to-br from-green-50 to-emerald-50 dark:from-slate-800 dark:to-slate-700 shadow-xl rounded-2xl hover:shadow-2xl hover:scale-105 border border-green-100 dark:border-slate-600">
                            <!-- Background Animation -->
                            <div
                                class="absolute inset-0 bg-gradient-to-br from-green-400/10 to-emerald-500/10 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                            </div>
                            <div class="relative z-10 flex items-center">
                                <div
                                    class="flex items-center justify-center w-12 h-12 md:mr-3 bg-gradient-to-br from-green-400 to-emerald-500 rounded-xl shadow-lg group-hover:shadow-xl transition-shadow duration-300">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-white"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M12 3v2a2 2 0 01-2 2h4a2 2 0 01-2-2V3zm-2 9h4m-4 4h4" />
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0 hidden md:block">
                                    <div
                                        class="text-lg font-bold text-gray-800 dark:text-white mb-1 group-hover:text-green-600 dark:group-hover:text-green-400 transition-colors duration-300 truncate">
                                        Pesanan</div>
                                    <div
                                        class="text-xs text-gray-600 dark:text-gray-400 font-medium truncate">
                                        Buat pesanan baru</div>
                                </div>
                                <div
                                    class="opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex-shrink-0 hidden md:flex ml-auto">
                                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5l7 7-7 7" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </a>

                    <!-- Enhanced Customer Card -->
                    <button type="button" id="add-customer" class="w-1/2 px-3 mb-6" data-modal-target="crud-modal"
                        data-modal-toggle="crud-modal">
                        <div
                            class="group relative overflow-hidden flex items-center justify-center md:justify-start p-4 transition-all duration-300 ease-out transform bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-slate-800 dark:to-slate-700 shadow-xl rounded-2xl hover:shadow-2xl hover:scale-105 border border-blue-100 dark:border-slate-600">
                            <!-- Background Animation -->
                            <div
                                class="absolute inset-0 bg-gradient-to-br from-blue-400/10 to-indigo-500/10 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                            </div>
                            <div class="relative z-10 flex items-center">
                                <div
                                    class="flex items-center justify-center w-12 h-12 md:mr-3 bg-gradient-to-br from-blue-400 to-indigo-500 rounded-xl shadow-lg group-hover:shadow-xl transition-shadow duration-300">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-white"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M18 9v6m3-3h-6M9 16a4 4 0 100-8 4 4 0 000 8zm0 0c-2.21 0-4 1.79-4 4v1h8v-1c0-2.21-1.79-4-4-4z" />
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0 text-left hidden md:block">
                                    <div
                                        class="text-lg font-bold text-gray-800 dark:text-white mb-1 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors duration-300 truncate">
                                        Customer</div>
                                    <div
                                        class="text-xs text-gray-600 dark:text-gray-400 font-medium truncate">
                                        Tambah customer baru</div>
                                </div>
                                <div
                                    class="opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex-shrink-0 hidden md:flex ml-auto">
                                    <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </button>
                </div>
            </div>
        </div>

        <!-- Enhanced Notes Card -->
        <div class="w-full max-w-full px-3 mt-0 mb-6 lg:mb-0 lg:flex-none">
            <div
                class="relative overflow-hidden flex flex-col min-w-0 break-words bg-gradient-to-br from-white to-gray-50 dark:from-slate-800 dark:to-slate-900 shadow-2xl rounded-3xl bg-clip-border border border-gray-100 dark:border-slate-700">
                <div class="p-6 pb-4 mb-0 rounded-t-4">
                    <div class="flex-1">
                        @if (Auth::user()->note)
                        <div class="flex items-center mb-4">
                            <div class="p-2 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-lg mr-3">
                                <i class="text-sm text-white fas fa-sticky-note"></i>
                            </div>
                            <h6 class="mb-0 text-lg font-bold text-gray-800 dark:text-white">Catatan dari Admin</h6>
                        </div>
                        <div
                            class="p-4 bg-gradient-to-r from-yellow-50 to-orange-50 dark:from-slate-700 dark:to-slate-600 rounded-2xl border-l-4 border-yellow-400">
                            <p class="text-gray-700 dark:text-gray-300 leading-relaxed font-medium">
                                "{{ Auth::user()->note }}"
                            </p>
                        </div>
                        @else
                        <div class="flex items-center mb-4">
                            <div class="p-2 bg-gradient-to-br from-green-400 to-emerald-500 rounded-lg mr-3">
                                <i class="text-sm text-white fas fa-check-circle"></i>
                            </div>
                            <h6 class="mb-0 text-lg font-bold text-gray-800 dark:text-white">Tidak Ada Catatan Baru
                            </h6>
                        </div>
                        <div
                            class="p-4 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-slate-700 dark:to-slate-600 rounded-2xl border-l-4 border-green-400">
                            <p class="text-gray-700 dark:text-gray-300 leading-relaxed">
                                Saat ini tidak ada catatan baru dari admin.
                            </p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Enhanced Income Chart -->
        <div class="w-full max-w-full px-3 mt-0 lg:flex-none">
            <div
                class="relative overflow-hidden shadow-2xl rounded-3xl bg-gradient-to-br from-white to-blue-50 dark:from-slate-800 dark:to-slate-900 border border-blue-100 dark:border-slate-700">
                <div class="p-6 pt-4 pb-0 rounded-t-3xl">
                    <div class="flex items-center justify-between">
                        <div>
                            <h6 class="text-lg font-bold text-gray-800 dark:text-white mb-2">Income</h6>
                            <div class="flex items-center">
                                <div class="p-2 bg-gradient-to-br from-green-400 to-emerald-500 rounded-lg mr-3">
                                    <i class="fa fa-arrow-up text-white text-sm"></i>
                                </div>
                                <span
                                    class="text-2xl font-bold bg-gradient-to-r from-green-600 to-emerald-600 bg-clip-text text-transparent">Rp
                                    550.000</span>
                            </div>
                        </div>
                        <div class="p-2 bg-gradient-to-br from-blue-400 to-indigo-500 rounded-xl">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="flex-auto p-4">
                    <div class="bg-white/50 dark:bg-slate-700/50 rounded-2xl p-4">
                        <canvas id="savingsChart" class="h-[200px] lg:h-[300px]"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced Latest Orders -->
    <div class="flex flex-wrap mt-6 -mx-3">
        <div class="w-full max-w-full px-3 mt-0 lg:flex-none">
            <div
                class="relative overflow-hidden flex flex-col min-w-0 mb-4 break-words bg-gradient-to-br from-white to-gray-50 dark:from-slate-800 dark:to-slate-900 shadow-2xl rounded-3xl bg-clip-border border border-gray-100 dark:border-slate-700">
                <div class="p-6 pb-0 mb-0 rounded-t-3xl">
                    <div class="flex items-center justify-between">
                        <h5 class="text-xl font-bold text-gray-800 dark:text-white">Latest Orders</h5>
                        <div class="p-2 bg-gradient-to-br from-purple-400 to-pink-500 rounded-lg">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="flex-auto px-0 pt-0 pb-2">
                    <!-- Desktop Table View -->
                    <div class="hidden p-0 overflow-x-auto md:block">
                        <table class="items-center w-full mb-0 align-top border-collapse text-slate-500">
                            <thead class="align-bottom">
                                <tr>
                                    <th
                                        class="px-6 py-4 font-bold text-left text-gray-700 dark:text-gray-300 uppercase align-middle bg-gray-50 dark:bg-slate-700 border-b border-gray-200 dark:border-slate-600 text-xs tracking-wider">
                                        Nama</th>
                                    <th
                                        class="px-6 py-4 font-bold text-left text-gray-700 dark:text-gray-300 uppercase align-middle bg-gray-50 dark:bg-slate-700 border-b border-gray-200 dark:border-slate-600 text-xs tracking-wider">
                                        Alamat</th>
                                    <th
                                        class="px-6 py-4 font-bold text-center text-gray-700 dark:text-gray-300 uppercase align-middle bg-gray-50 dark:bg-slate-700 border-b border-gray-200 dark:border-slate-600 text-xs tracking-wider">
                                        Pesanan</th>
                                    <th
                                        class="px-6 py-4 font-bold text-center text-gray-700 dark:text-gray-300 uppercase align-middle bg-gray-50 dark:bg-slate-700 border-b border-gray-200 dark:border-slate-600 text-xs tracking-wider">
                                        Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($latestOrders as $order)
                                <tr
                                    class="hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors duration-200">
                                    <td
                                        class="p-4 align-middle bg-transparent border-b dark:border-slate-600 whitespace-nowrap">
                                        <div class="flex px-2 py-1">
                                            <div class="flex flex-col justify-center">
                                                <h6
                                                    class="mb-0 text-sm font-semibold leading-normal text-gray-800 dark:text-white">
                                                    {{ $order->customer->name ?? 'Pelanggan Dihapus' }}
                                                </h6>
                                                <p
                                                    class="mb-0 text-xs leading-tight text-gray-500 dark:text-gray-400">
                                                    {{ $order->phone ?? ($order->customer->phone ?? 'N/A') }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td
                                        class="p-4 align-middle bg-transparent border-b dark:border-slate-600 whitespace-nowrap">
                                        <p class="mb-0 text-sm font-medium text-gray-700 dark:text-gray-300">
                                            {{ Str::limit($order->address ?? 'Alamat tidak ada', 25) }}
                                        </p>
                                    </td>
                                    <td
                                        class="p-4 text-center bg-transparent border-b dark:border-slate-600 whitespace-nowrap">
                                        <span class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                            {{ $order->items->first()->product_name ?? 'N/A' }}
                                            @if ($order->items->count() > 1)
                                            <span
                                                class="text-xs text-gray-400">(+{{ $order->items->count() - 1 }})</span>
                                            @endif
                                        </span>
                                    </td>
                                    <td
                                        class="p-4 text-sm leading-normal text-center align-middle bg-transparent border-b dark:border-slate-600 whitespace-nowrap">
                                        @php
                                        $status = $order->status ?? 'dikemas';
                                        $statusText = ucfirst(str_replace('_', ' ', $status));
                                        $statusClass = '';
                                        switch ($status) {
                                        case 'diambil':
                                        $statusClass = 'bg-gradient-to-r from-blue-500 to-indigo-500';
                                        break;
                                        case 'diantar':
                                        $statusClass = 'bg-gradient-to-r from-yellow-500 to-orange-500';
                                        break;
                                        case 'diterima_pembeli':
                                        $statusClass = 'bg-gradient-to-r from-purple-500 to-pink-500';
                                        break;
                                        case 'selesai':
                                        $statusClass = 'bg-gradient-to-r from-green-500 to-emerald-500';
                                        break;
                                        default:
                                        $statusClass = 'bg-gradient-to-r from-gray-500 to-gray-600';
                                        break;
                                        }
                                        @endphp
                                        <span
                                            class="{{ $statusClass }} px-3 py-2 text-xs rounded-full font-bold uppercase text-white shadow-lg">{{ $statusText }}</span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="p-6 text-center text-gray-500 dark:text-gray-400">
                                        Belum ada pesanan yang dibuat.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile Card View -->
                    <div class="px-2 py-2 space-y-3 md:hidden">
                        @forelse($latestOrders as $loop => $order)
                        @php
                        $customerPhone = $order->phone ?? ($order->customer->phone ?? '');
                        $formattedPhone = preg_replace('/[^0-9]/', '', $customerPhone);
                        $shortInvoice = '#' . substr($order->invoice_number, -3);
                        $customerName = $order->customer->name ?? 'Pelanggan Dihapus';
                        $displayName = Str::words($customerName, 2, '');
                        @endphp
                        <div
                            class="p-3 bg-white/60 dark:bg-slate-700/60 backdrop-blur-sm rounded-2xl border border-white/20 dark:border-slate-600/20 shadow-lg">
                            <div class="flex items-start justify-between w-full space-x-3">
                                <div class="flex-shrink-0 pt-1">
                                    <span class="flex items-center justify-center w-6 h-6 text-xs font-bold text-gray-600 bg-gray-200 rounded-full dark:bg-slate-600 dark:text-gray-300">
                                        {{ $loop->iteration }}
                                    </span>
                                </div>
                                <!-- Left side: Info -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-baseline space-x-2">
                                        <h6 class=" text-base text-lg font-bold text-gray-800 dark:text-white">
                                            {{ $displayName ?? 'Pelanggan Dihapus' }}
                                        </h6>
                                    </div>
                                    <button type="button" data-modal-toggle="orderDetailsModal"
                                        onclick="fetchOrderDetails({{ $order->id }})"
                                        class="block w-full mt-2 text-sm text-left text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                                        <span>{{ $shortInvoice ?? 'N/A' }}</span>
                                        <span class="mx-1">|</span>
                                        <span>Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                                    </button>
                                </div>

                                <!-- Right side: Actions -->
                                <div class="flex flex-col items-end flex-shrink-0 space-y-2">
                                    <!-- Status Badge -->
                                    @php
                                    $status = $order->status ?? 'dikemas';
                                    $statusText = ucfirst(str_replace('_', ' ', $status));
                                    $statusClass = '';
                                    switch ($status) {
                                    case 'diambil':
                                    $statusClass = 'bg-blue-100 text-blue-800';
                                    break;
                                    case 'diantar':
                                    $statusClass = 'bg-yellow-100 text-yellow-800';
                                    break;
                                    case 'diterima_pembeli':
                                    $statusClass = 'bg-purple-100 text-purple-800';
                                    break;
                                    case 'selesai':
                                    $statusClass = 'bg-green-100 text-green-800';
                                    break;
                                    default:
                                    $statusClass = 'bg-gray-100 text-gray-800';
                                    break;
                                    }
                                    @endphp
                                    <span
                                        class="px-2 py-1 text-xs font-semibold rounded-full {{ $statusClass }}">
                                        {{ $statusText }}
                                    </span>
                                    <div class="flex items-center space-x-3">
                                        @if ($formattedPhone)
                                        <a href="https://wa.me/{{ $formattedPhone }}" target="_blank"
                                            class="text-green-500 hover:text-green-600 transition-colors">
                                            <i class="text-2xl fab fa-whatsapp"></i>
                                        </a>
                                        @endif
                                        <button type="button" data-modal-toggle="statusStepperModal"
                                            onclick="openStatusStepperModal({{ $order->id }})"
                                            class="text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300 transition-colors">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    stroke-width="2.5" d="M9 5l7 7-7 7"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="p-6 text-center text-gray-500 dark:text-gray-400">
                            Belum ada pesanan yang dibuat.
                        </div>
                        @endforelse
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
</div>
<!-- end cards -->

@include('dashboard.kurir.pesanan.rincian-modal')
@include('dashboard.kurir.pesanan.status-modal')



@include('dashboard.kurir.customers.create')

<!-- Enhanced Scripts -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Modal functionality
        const modalToggle = document.querySelector('[data-modal-toggle="crud-modal"]');
        const modal = document.getElementById('crud-modal');

        if (modalToggle && modal) {
            modalToggle.addEventListener('click', function() {
                modal.classList.remove('hidden');
            });

            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.classList.add('hidden');
                }
            });

            const closeButton = modal.querySelector('[data-modal-toggle="crud-modal"]');
            if (closeButton) {
                closeButton.addEventListener('click', function() {
                    modal.classList.add('hidden');
                });
            }
        }

        // Enhanced Chart
        var ctx = document.getElementById('savingsChart');
        if (ctx && window.Chart) {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May'],
                    datasets: [{
                        label: 'Income',
                        data: [20, 110, 55, 100, 30],
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#3b82f6',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 3,
                        pointRadius: 6,
                        pointHoverRadius: 8,
                        pointHoverBackgroundColor: '#1d4ed8',
                        pointHoverBorderColor: '#fff',
                        pointHoverBorderWidth: 3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            enabled: true,
                            callbacks: {
                                label: function(context) {
                                    return 'Rp ' + context.parsed.y + 'K';
                                }
                            },
                            backgroundColor: 'rgba(255, 255, 255, 0.95)',
                            titleColor: '#1f2937',
                            bodyColor: '#374151',
                            borderColor: '#3b82f6',
                            borderWidth: 2,
                            displayColors: false,
                            padding: 12,
                            cornerRadius: 8
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                color: '#6b7280',
                                callback: function(value) {
                                    return value + 'K';
                                }
                            },
                            grid: {
                                color: 'rgba(107, 114, 128, 0.1)',
                                drawBorder: false
                            }
                        },
                        x: {
                            ticks: {
                                color: '#6b7280'
                            },
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }

        // Time-based greeting
        function getGreeting() {
            const hour = new Date().getHours();
            if (hour >= 4 && hour < 11) return "Selamat Pagi 🌤️";
            else if (hour >= 11 && hour < 15) return "Selamat Siang ☀️";
            else if (hour >= 15 && hour < 18) return "Selamat Sore 🌇";
            else return "Selamat Malam 🌙";
        }

        document.getElementById("ucapan").textContent = getGreeting();
    });




    // --------------------------------------------------------------------

    function getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }

    async function fetchOrderDetails(orderId) {
        const modalLoader = document.getElementById('modalLoader');
        const modalContent = document.getElementById('modalContent');

        modalContent.classList.add('hidden');
        modalLoader.classList.remove('hidden');
        modalLoader.innerHTML = `
                <svg class="w-8 h-8 text-blue-600 animate-spin mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="mt-4 text-lg font-medium text-gray-700 dark:text-gray-300">Memuat Detail Pesanan...</p>`;

        try {
            const response = await fetch(`/kurir/pesanan/${orderId}/details`, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken()
                }
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.message);

            populateOrderDetailsModal(data);
        } catch (error) {
            console.error('Error fetching order details:', error);
            modalLoader.innerHTML = `<div class="text-center"><p class="font-bold text-red-600">Gagal Memuat Data</p><p class="mt-2 text-sm text-gray-500">${error.message}</p></div>`;
        }
    }

    function populateOrderDetailsModal(order) {
        // Mengisi data umum (invoice, customer, dll)
        document.getElementById('modalInvoiceNumber').textContent = order.invoice_number || 'N/A';
        document.getElementById('customerName').textContent = order.customer.name || 'N/A';
        document.getElementById('customerPhone').textContent = order.customer.phone || 'N/A';
        document.getElementById('customerAddress').textContent = order.customer.address || 'N/A';
        document.getElementById('paymentMethod').textContent = order.payment_method || 'N/A';
        document.getElementById('orderCreatedAt').textContent = order.created_at || 'Tidak tersedia';

        const orderPaidAtEl = document.getElementById('orderPaidAt');
        if (order.paid_at) {
            orderPaidAtEl.textContent = order.paid_at + (order.paid_at_label || ' ✅');
        } else {
            orderPaidAtEl.textContent = 'Belum Dibayar ❌';
        }

        const productDetailsDiv = document.getElementById('productDetails');
        productDetailsDiv.innerHTML = '';
        if (order.products && order.products.length > 0) {
            order.products.forEach(product => {
                const productItem = document.createElement('div');
                productItem.className = 'p-3 border rounded-lg dark:border-gray-700';
                productItem.innerHTML = `<p class="font-semibold text-gray-900 dark:text-white">${product.name} ${product.variant_name ? `(${product.variant_name})` : ''}</p><p class="text-sm text-gray-700 dark:text-gray-300">Jumlah: ${product.quantity}</p><p class="text-sm text-gray-700 dark:text-gray-300">Harga: Rp ${new Intl.NumberFormat('id-ID').format(product.price)}</p>`;
                productDetailsDiv.appendChild(productItem);
            });
        } else {
            productDetailsDiv.innerHTML = '<p class="text-gray-700 dark:text-gray-300">Tidak ada produk.</p>';
        }

        document.getElementById('modalTotalAmount').textContent = `Rp ${new Intl.NumberFormat('id-ID').format(order.total_amount || 0)}`;

        const paymentUploadForm = document.getElementById('paymentUploadForm');
        const paymentProofUploaded = document.getElementById('paymentProofUploaded');
        const paymentUploadBlocker = document.getElementById('paymentUploadBlocker'); // Ambil div blocker
        const fileInput = document.getElementById('payment_proof_file');
        const submitButton = paymentUploadForm.querySelector('button[type="submit"]');

        // Sembunyikan semua elemen terkait pembayaran terlebih dahulu
        paymentUploadForm.classList.add('hidden');
        paymentProofUploaded.classList.add('hidden');
        paymentUploadBlocker.classList.add('hidden');

        // Skenario 1: Bukti sudah diunggah (status 'selesai')
        if (order.payment_proof) {
            paymentProofUploaded.classList.remove('hidden');
        }
        // Skenario 2: Pesanan sudah diterima pembeli, siap untuk unggah bukti
        else if (order.status === 'diterima_pembeli') {
            paymentUploadForm.classList.remove('hidden');

            // Atur form
            fileInput.value = '';
            submitButton.disabled = true;
            fileInput.onchange = () => {
                submitButton.disabled = fileInput.files.length === 0;
            };
            paymentUploadForm.onsubmit = (e) => {
                e.preventDefault();
                handlePaymentUpload(order.id);
            };
        }
        // Skenario 3: Pesanan belum diterima pembeli, belum bisa unggah bukti
        else {
            paymentUploadBlocker.classList.remove('hidden');
        }

        document.getElementById('modalLoader').classList.add('hidden');
        document.getElementById('modalContent').classList.remove('hidden');
    }

    async function handlePaymentUpload(orderId) {
        const form = document.getElementById('paymentUploadForm');
        const submitButton = form.querySelector('button[type="submit"]');
        const buttonText = document.getElementById('uploadButtonText');
        const buttonSpinner = document.getElementById('uploadButtonSpinner');
        const buttonStatus = document.getElementById('uploadButtonStatus');
        const formData = new FormData(form);

        submitButton.disabled = true;
        buttonText.classList.add('hidden');
        buttonSpinner.classList.remove('hidden');
        buttonStatus.classList.remove('hidden');

        try {
            const response = await fetch(`/kurir/pesanan/${orderId}/upload-proof`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken()
                },
                body: formData
            });
            const result = await response.json();
            if (!response.ok) {
                let errorMessage = result.message;
                if (result.errors?.payment_proof) {
                    errorMessage += `\n- ${result.errors.payment_proof.join('\n- ')}`;
                }
                throw new Error(errorMessage);
            }
            // Setelah unggahan berhasil, ambil kembali detail dan perbarui status tabel
            fetchOrderDetails(orderId);
            // Asumsikan 'selesai' adalah status setelah unggahan bukti pembayaran
            updateTableRowStatus(orderId, 'selesai');
            showCustomAlert(result.message, 'success');
        } catch (error) {
            // Menggunakan kotak pesan kustom sebagai ganti alert
            showCustomAlert(`Upload Gagal: ${error.message}`);
            submitButton.disabled = false;
            buttonText.classList.remove('hidden');
            buttonSpinner.classList.add('hidden');
            buttonStatus.classList.add('hidden');
        }
    }


    // --- Logika Modal Stepper Status ---
    let currentOrderIdForStatus = null;

    async function openStatusStepperModal(orderId) {
        currentOrderIdForStatus = orderId;
        const modalLoader = document.getElementById('statusStepperModalLoader');
        const modalContent = document.getElementById('statusStepperModalContent');
        const updateButton = document.getElementById('updateStatusButton');
        const updateButtonText = document.getElementById('updateStatusButtonText');
        const updateButtonSpinner = document.getElementById('updateStatusButtonSpinner');

        // Reset button state
        updateButtonText.classList.remove('hidden');
        updateButtonSpinner.classList.add('hidden');
        updateButton.disabled = false;
        updateButton.classList.remove('opacity-50', 'cursor-not-allowed');
        updateButtonText.textContent = 'Memuat...';


        modalContent.classList.add('hidden');
        modalLoader.classList.remove('hidden');
        modalLoader.innerHTML = `
                <svg class="w-8 h-8 text-blue-600 animate-spin mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="mt-4 text-lg font-medium text-gray-700 dark:text-gray-300">Memuat Status Pesanan...</p>`;

        try {
            const response = await fetch(`/kurir/pesanan/${orderId}/details`, { // Reuse details endpoint for all status info
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken()
                }
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.message);

            populateStatusStepperModal(data);
        } catch (error) {
            console.error('Error fetching order status details:', error);
            modalLoader.innerHTML = `<div class="text-center"><p class="font-bold text-red-600">Gagal Memuat Data</p><p class="mt-2 text-sm text-gray-500">${error.message}</p></div>`;
            updateButton.disabled = true;
            updateButton.classList.add('opacity-50', 'cursor-not-allowed');
            updateButtonText.textContent = 'Error Memuat';
            updateButtonText.classList.remove('hidden');
            updateButtonSpinner.classList.add('hidden');
        }
    }


    function populateStatusStepperModal(order) {
        const statusMap = {
            'dikemas': {
                label: 'Dikemas',
                nextStatus: 'diambil',
                buttonText: 'Ubah Status ke Diambil'
            },
            'diambil': {
                label: 'Diambil',
                nextStatus: 'diantar',
                buttonText: 'Ubah Status ke Diantar'
            },
            'diantar': {
                label: 'Diantar',
                nextStatus: 'diterima_pembeli',
                buttonText: 'Ubah Status ke Diterima Pembeli'
            },
            'diterima_pembeli': {
                label: 'Diterima Pembeli',
                nextStatus: null,
                buttonText: 'Menunggu Pembayaran'
            },
            'selesai': {
                label: 'Selesai (Lunas)',
                nextStatus: null,
                buttonText: 'Pesanan Selesai'
            },
        };

        document.getElementById('modalStatusInvoiceNumber').textContent = order.invoice_number || 'N/A';
        document.getElementById('modalStatusCustomerName').textContent = order.customer.name || 'N/A';

        const currentStatus = order.status;
        const effectiveStatus = currentStatus && statusMap[currentStatus] ? currentStatus : 'dikemas';

        const updateButton = document.getElementById('updateStatusButton');
        const updateButtonText = document.getElementById('updateStatusButtonText');
        const currentOrderStatusText = document.getElementById('currentOrderStatusText');

        // Reset semua langkah ke default
        const steps = ['diambil', 'diantar', 'diterima_pembeli'];
        steps.forEach(step => {
            const iconEl = document.getElementById(`step-${step}-icon`);
            // Reset Ikon
            iconEl.classList.remove('bg-blue-600', 'text-white', 'border-blue-600', 'bg-green-600', 'border-green-600');
            iconEl.classList.add('border-gray-300', 'text-gray-500', 'dark:border-gray-600', 'dark:text-gray-400', 'bg-white', 'dark:bg-gray-700');

            // Reset Garis (Desktop & Mobile)
            const lineDesktop = document.getElementById(`line-${step === 'diambil' ? 'diantar' : step}`);
            const lineMobile = document.getElementById(`line-${step}-mobile`);
            if (lineDesktop) {
                lineDesktop.classList.remove('bg-blue-600', 'bg-green-600');
                lineDesktop.classList.add('bg-gray-200', 'dark:bg-gray-600');
            }
            if (lineMobile) {
                lineMobile.classList.remove('bg-blue-600', 'bg-green-600');
                lineMobile.classList.add('bg-gray-200', 'dark:bg-gray-600');
            }
        });
        document.getElementById('step-diambil-icon').innerHTML = '<i class="fas fa-box"></i>';
        document.getElementById('step-diantar-icon').innerHTML = '<i class="fas fa-truck-moving"></i>';
        document.getElementById('step-diterima_pembeli-icon').innerHTML = '<i class="fas fa-home"></i>';
        document.getElementById('pickedUpAt').textContent = '';
        document.getElementById('deliveredAt').textContent = '';
        document.getElementById('receivedByBuyerAt').textContent = '';

        // currentOrderStatusText.textContent = `Status saat ini: ${statusMap[effectiveStatus]?.label || 'Tidak Diketahui'}`;

        // Perbarui stepper berdasarkan data timestamp
        if (order.picked_up_at) {
            const icon = document.getElementById('step-diambil-icon');
            const lineMobile = document.getElementById('line-diambil-mobile');

            icon.classList.remove('border-gray-300', 'text-gray-500', 'dark:border-gray-600', 'dark:text-gray-400', 'bg-white', 'dark:bg-gray-700');
            icon.classList.add('bg-green-600', 'text-white', 'border-green-600');
            lineMobile.classList.remove('bg-gray-200', 'dark:bg-gray-600');
            lineMobile.classList.add('bg-green-600');
            icon.innerHTML = '<i class="fas fa-check-circle"></i>';
            document.getElementById('pickedUpAt').textContent = order.picked_up_at;
        }

        if (order.delivered_at) {
            const icon = document.getElementById('step-diantar-icon');
            const lineDesktop = document.getElementById('line-diantar');
            const lineMobile = document.getElementById('line-diantar-mobile');

            icon.classList.remove('border-gray-300', 'text-gray-500', 'dark:border-gray-600', 'dark:text-gray-400', 'bg-white', 'dark:bg-gray-700');
            icon.classList.add('bg-green-600', 'text-white', 'border-green-600');
            lineDesktop.classList.remove('bg-gray-200', 'dark:bg-gray-600');
            lineDesktop.classList.add('bg-green-600');
            lineMobile.classList.remove('bg-gray-200', 'dark:bg-gray-600');
            lineMobile.classList.add('bg-green-600');
            icon.innerHTML = '<i class="fas fa-check-circle"></i>';
            document.getElementById('deliveredAt').textContent = order.delivered_at;
        }

        if (order.received_by_buyer_at) {
            const icon = document.getElementById('step-diterima_pembeli-icon');
            const lineDesktop = document.getElementById('line-diterima_pembeli');

            icon.classList.remove('border-gray-300', 'text-gray-500', 'dark:border-gray-600', 'dark:text-gray-400', 'bg-white', 'dark:bg-gray-700');
            icon.classList.add('bg-green-600', 'text-white', 'border-green-600');
            if (lineDesktop) { // Check if lineDesktop exists before adding classes
                lineDesktop.classList.remove('bg-gray-200', 'dark:bg-gray-600');
                lineDesktop.classList.add('bg-green-600');
            }

            icon.innerHTML = '<i class="fas fa-check-circle"></i>';
            document.getElementById('receivedByBuyerAt').textContent = order.received_by_buyer_at;
        }

        const nextStatusInfo = statusMap[effectiveStatus];
        if (nextStatusInfo && nextStatusInfo.nextStatus) {
            updateButtonText.textContent = nextStatusInfo.buttonText;
            updateButton.setAttribute('data-next-status', nextStatusInfo.nextStatus);
            updateButton.disabled = false;
            updateButton.classList.remove('opacity-50', 'cursor-not-allowed');
        } else {
            updateButtonText.textContent = statusMap[effectiveStatus]?.buttonText || 'Tidak ada tindakan';
            updateButton.setAttribute('data-next-status', '');
            updateButton.disabled = true;
            updateButton.classList.add('opacity-50', 'cursor-not-allowed');
        }

        updateButton.setAttribute('data-order-id', order.id);

        document.getElementById('statusStepperModalLoader').classList.add('hidden');
        document.getElementById('statusStepperModalContent').classList.remove('hidden');
    }

    // Fungsi handleStatusUpdate, updateTableRowStatus, getCsrfToken, fetchOrderDetails, populateOrderDetailsModal, handlePaymentUpload, showCustomAlert, dan event listener lainnya tetap sama.
    // ... (fungsi-fungsi lain tetap sama) ...
    function getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }

    async function fetchOrderDetails(orderId) {
        const modalLoader = document.getElementById('modalLoader');
        const modalContent = document.getElementById('modalContent');

        modalContent.classList.add('hidden');
        modalLoader.classList.remove('hidden');
        modalLoader.innerHTML = `
                <svg class="w-8 h-8 text-blue-600 animate-spin mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="mt-4 text-lg font-medium text-gray-700 dark:text-gray-300">Memuat Detail Pesanan...</p>`;

        try {
            const response = await fetch(`/kurir/pesanan/${orderId}/details`, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken()
                }
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.message);

            populateOrderDetailsModal(data);
        } catch (error) {
            console.error('Error fetching order details:', error);
            modalLoader.innerHTML = `<div class="text-center"><p class="font-bold text-red-600">Gagal Memuat Data</p><p class="mt-2 text-sm text-gray-500">${error.message}</p></div>`;
        }
    }

    function populateOrderDetailsModal(order) {
        // Mengisi data umum (invoice, customer, dll)
        document.getElementById('modalInvoiceNumber').textContent = order.invoice_number || 'N/A';
        document.getElementById('customerName').textContent = order.customer.name || 'N/A';
        document.getElementById('customerPhone').textContent = order.customer.phone || 'N/A';
        document.getElementById('customerAddress').textContent = order.customer.address || 'N/A';
        document.getElementById('paymentMethod').textContent = order.payment_method || 'N/A';
        document.getElementById('orderCreatedAt').textContent = order.created_at || 'Tidak tersedia';

        const orderPaidAtEl = document.getElementById('orderPaidAt');
        if (order.paid_at) {
            orderPaidAtEl.textContent = order.paid_at + (order.paid_at_label || ' ✅');
        } else {
            orderPaidAtEl.textContent = 'Belum Dibayar ❌';
        }

        const productDetailsDiv = document.getElementById('productDetails');
        productDetailsDiv.innerHTML = '';
        if (order.products && order.products.length > 0) {
            order.products.forEach(product => {
                const productItem = document.createElement('div');
                productItem.className = 'p-3 border rounded-lg dark:border-gray-700';
                productItem.innerHTML = `<p class="font-semibold text-gray-900 dark:text-white">${product.name} ${product.variant_name ? `(${product.variant_name})` : ''}</p><p class="text-sm text-gray-700 dark:text-gray-300">Jumlah: ${product.quantity}</p><p class="text-sm text-gray-700 dark:text-gray-300">Harga: Rp ${new Intl.NumberFormat('id-ID').format(product.price)}</p>`;
                productDetailsDiv.appendChild(productItem);
            });
        } else {
            productDetailsDiv.innerHTML = '<p class="text-gray-700 dark:text-gray-300">Tidak ada produk.</p>';
        }

        document.getElementById('modalTotalAmount').textContent = `Rp ${new Intl.NumberFormat('id-ID').format(order.total_amount || 0)}`;

        const paymentUploadForm = document.getElementById('paymentUploadForm');
        const paymentProofUploaded = document.getElementById('paymentProofUploaded');
        const paymentUploadBlocker = document.getElementById('paymentUploadBlocker'); // Ambil div blocker
        const fileInput = document.getElementById('payment_proof_file');
        const submitButton = paymentUploadForm.querySelector('button[type="submit"]');

        // Sembunyikan semua elemen terkait pembayaran terlebih dahulu
        paymentUploadForm.classList.add('hidden');
        paymentProofUploaded.classList.add('hidden');
        paymentUploadBlocker.classList.add('hidden');

        // Skenario 1: Bukti sudah diunggah (status 'selesai')
        if (order.payment_proof) {
            paymentProofUploaded.classList.remove('hidden');
        }
        // Skenario 2: Pesanan sudah diterima pembeli, siap untuk unggah bukti
        else if (order.status === 'diterima_pembeli') {
            paymentUploadForm.classList.remove('hidden');

            // Atur form
            fileInput.value = '';
            submitButton.disabled = true;
            fileInput.onchange = () => {
                submitButton.disabled = fileInput.files.length === 0;
            };
            paymentUploadForm.onsubmit = (e) => {
                e.preventDefault();
                handlePaymentUpload(order.id);
            };
        }
        // Skenario 3: Pesanan belum diterima pembeli, belum bisa unggah bukti
        else {
            paymentUploadBlocker.classList.remove('hidden');
        }

        document.getElementById('modalLoader').classList.add('hidden');
        document.getElementById('modalContent').classList.remove('hidden');
    }

    async function handlePaymentUpload(orderId) {
        const form = document.getElementById('paymentUploadForm');
        const submitButton = form.querySelector('button[type="submit"]');
        const buttonText = document.getElementById('uploadButtonText');
        const buttonSpinner = document.getElementById('uploadButtonSpinner');
        const buttonStatus = document.getElementById('uploadButtonStatus');
        const formData = new FormData(form);

        submitButton.disabled = true;
        buttonText.classList.add('hidden');
        buttonSpinner.classList.remove('hidden');
        buttonStatus.classList.remove('hidden');

        try {
            const response = await fetch(`/kurir/pesanan/${orderId}/upload-proof`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken()
                },
                body: formData
            });
            const result = await response.json();
            if (!response.ok) {
                let errorMessage = result.message;
                if (result.errors?.payment_proof) {
                    errorMessage += `\n- ${result.errors.payment_proof.join('\n- ')}`;
                }
                throw new Error(errorMessage);
            }
            // Setelah unggahan berhasil, ambil kembali detail dan perbarui status tabel
            fetchOrderDetails(orderId);
            // Asumsikan 'selesai' adalah status setelah unggahan bukti pembayaran
            updateTableRowStatus(orderId, 'selesai');
            showCustomAlert(result.message, 'success');
        } catch (error) {
            // Menggunakan kotak pesan kustom sebagai ganti alert
            showCustomAlert(`Upload Gagal: ${error.message}`);
            submitButton.disabled = false;
            buttonText.classList.remove('hidden');
            buttonSpinner.classList.add('hidden');
            buttonStatus.classList.add('hidden');
        }
    }

    async function handleStatusUpdate() {
        const updateButton = document.getElementById('updateStatusButton');
        const orderId = updateButton.getAttribute('data-order-id');
        const newStatus = updateButton.getAttribute('data-next-status');

        if (!orderId || !newStatus) {
            showCustomAlert('Error: Status atau Order ID tidak ditemukan.');
            return;
        }

        const buttonText = document.getElementById('updateStatusButtonText');
        const buttonSpinner = document.getElementById('updateStatusButtonSpinner');
        buttonText.classList.add('hidden');
        buttonSpinner.classList.remove('hidden');
        updateButton.disabled = true;
        updateButton.classList.add('opacity-50', 'cursor-not-allowed');


        try {
            const response = await fetch(`/kurir/pesanan/${orderId}/update-status`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken()
                },
                body: JSON.stringify({
                    new_status: newStatus
                })
            });
            const result = await response.json();
            if (!response.ok) throw new Error(result.message);

            await openStatusStepperModal(orderId);
            updateTableRowStatus(orderId, result.order.status);
            showCustomAlert(result.message, 'success');

        } catch (error) {
            console.error('Error updating order status:', error);
            showCustomAlert(`Gagal memperbarui status: ${error.message}`);
        } finally {
            buttonText.classList.remove('hidden');
            buttonSpinner.classList.add('hidden');
        }
    }

    // Fungsi untuk memperbarui status di baris/kartu tabel utama
    function updateTableRowStatus(orderId, newStatus) {
        const tableRows = document.querySelectorAll(`[data-order-id="${orderId}"]`);

        tableRows.forEach(row => {
            const statusSpan = row.querySelector('.px-2\\.5.py-1.text-xs.font-semibold.rounded-full');
            if (statusSpan) {
                statusSpan.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1).replace('_', ' ');
                statusSpan.className = 'px-2.5 py-1 text-xs font-semibold rounded-full '; // Reset kelas
                switch (newStatus) {
                    case 'dikemas':
                    case 'diambil':
                        statusSpan.classList.add('bg-blue-100', 'text-blue-800');
                        break;
                    case 'diantar':
                        statusSpan.classList.add('bg-yellow-100', 'text-yellow-800');
                        break;
                    case 'diterima_pembeli':
                        statusSpan.classList.add('bg-purple-100', 'text-purple-800');
                        break;
                    case 'selesai':
                        statusSpan.classList.add('bg-green-100', 'text-green-800');
                        break;
                    default:
                        statusSpan.classList.add('bg-gray-100', 'text-gray-800');
                        break;
                }
            }
        });
    }

    // Lampirkan pendengar event ke tombol perbarui status di modal
    document.addEventListener('DOMContentLoaded', () => {
        const updateButton = document.getElementById('updateStatusButton');
        if (updateButton) {
            updateButton.addEventListener('click', handleStatusUpdate);
        }
    });

    // Kotak Peringatan Kustom (mengganti alert default)
    function showCustomAlert(message, type = 'error') {
        const alertContainer = document.getElementById('customAlertContainer') || document.createElement('div');
        alertContainer.id = 'customAlertContainer';
        alertContainer.className = 'fixed top-4 right-4 z-[9999] flex flex-col space-y-2';
        document.body.appendChild(alertContainer);

        const alertDiv = document.createElement('div');
        alertDiv.className = `p-4 rounded-md shadow-lg flex items-center space-x-3 transition-all duration-300 transform translate-x-full opacity-0 ${
            type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
        }`;
        alertDiv.innerHTML = `
            <svg class="w-6 h-6 ${type === 'success' ? 'text-green-600' : 'text-red-600'}" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                ${type === 'success' ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>' : '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>'}
            </svg>
            <p class="font-medium">${message}</p>
            <button class="ml-auto focus:outline-none" onclick="this.closest('div').remove()">
                <svg class="w-4 h-4 ${type === 'success' ? 'text-green-600' : 'text-red-600'}" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        `;
        alertContainer.appendChild(alertDiv); // Perbaikan di sini, sebelumnya alertContainer.appendChild(alertContainer);

        setTimeout(() => {
            alertDiv.classList.remove('translate-x-full', 'opacity-0');
            alertDiv.classList.add('translate-x-0', 'opacity-100');
        }, 100);

        setTimeout(() => {
            alertDiv.classList.remove('translate-x-0', 'opacity-100');
            alertDiv.classList.add('translate-x-full', 'opacity-0');
            alertDiv.addEventListener('transitionend', () => alertDiv.remove());
        }, 5000);
    }
</script>

<script src="/assets/argon/js/plugins/chartjs.min.js"></script>
<script src="/assets/argon/js/plugins/perfect-scrollbar.min.js" async></script>
<script src="/assets/argon/js/assets/argon-dashboard-tailwind.js?v=1.0.1" async></script>

@endsection