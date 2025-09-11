@extends('layouts.argon')
@section('title', 'Dashboard Kurir')
@section('page_title', 'Dashboard')

@section('content')
    <?php

    use App\Models\OrderReturn;
    use App\Models\OrderReturnProduct;
    use Carbon\Carbon;
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\Request;
    use Illuminate\Support\Str;

    // --- Inisialisasi Data Dasar ---
    $today = Carbon::today();
    $loggedInCourierId = Auth::id();

    // --- Pemetaan Status Pesanan ---
    $statusLabelMap = [
        'baru' => 'Baru',
        'dikemas' => 'Dikemas',
        'diambil' => 'Diambil',
        'diantar' => 'Diantar',
        'diterima_pembeli' => 'Diterima',
        'selesai' => 'Selesai',
        'menunggu_retur' => 'Menunggu Retur',
        'menunggu_verifikasi_admin' => 'Menunggu Verifikasi',
        'diverifikasi_admin' => 'Valid',
        'dikembalikan' => 'Retur',
        'dibatalkan' => 'Dibatalkan',
    ];

    // --- Statistik Harian Kurir ---
    $totalOrdersToday = App\Models\Order::where('created_by_user_id', $loggedInCourierId)->whereDate('created_at', $today)->count();
    $totalCustomersInRegion = App\Models\Customer::where('added_by_user_id', $loggedInCourierId)->count();
    $completedOrdersToday = App\Models\Order::where('created_by_user_id', $loggedInCourierId)->whereDate('updated_at', $today)->where('status', 'diverifikasi_admin')->count();
    $totalReturnedOrdersToday = OrderReturn::whereHas('order', function ($query) use ($loggedInCourierId) {
        $query->where('created_by_user_id', $loggedInCourierId);
    })
        ->whereDate('created_at', $today)
        ->count();

    // --- Data Pesanan Terbaru ---
    $latestOrders = App\Models\Order::where('created_by_user_id', $loggedInCourierId)
        ->where('status', '!=', 'diverifikasi_admin')
        ->with(['customer', 'items'])
        ->latest()
        ->take(3)
        ->get();

    // --- Logika untuk Data Grafik Pesanan ---
    $filter = Request::input('filter', 'last_7_days');
    $chartLabels = [];
    $chartData = [];
    $chartDataCompleted = [];
    $chartDataReturned = [];
    $chartTitle = 'Chart Pesanan';
    $currentYear = Carbon::now()->year;
    $currentMonth = Carbon::now()->month;
    $dateRangeText = '';

    switch ($filter) {
        case 'daily':
            $daysInMonth = Carbon::now()->daysInMonth;
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $date = Carbon::createFromDate($currentYear, $currentMonth, $day);
                $chartLabels[] = $date->format('d');
                $chartData[] = App\Models\Order::where('created_by_user_id', $loggedInCourierId)->whereDate('created_at', $date)->count();
                $chartDataCompleted[] = App\Models\Order::where('created_by_user_id', $loggedInCourierId)->whereDate('updated_at', $date)->where('status', 'selesai')->count();
                $chartDataReturned[] = App\Models\OrderReturn::whereHas('order', function ($query) use ($loggedInCourierId) {
                    $query->where('created_by_user_id', $loggedInCourierId);
                })
                    ->whereDate('created_at', $date)
                    ->count();
            }
            $dateRangeText = Carbon::now()->isoFormat('MMMM YYYY');
            break;

        case 'weekly':
            $startDate = Carbon::now()->startOfMonth();
            $endDate = Carbon::now()->endOfMonth();
            $weekNumber = 1;
            while ($startDate->lte($endDate)) {
                $weekEndDate = $startDate->copy()->endOfWeek(Carbon::SATURDAY);
                if ($weekEndDate->gt($endDate)) {
                    $weekEndDate = $endDate;
                }
                $chartLabels[] = 'Minggu Ke-' . $weekNumber;
                $chartData[] = App\Models\Order::where('created_by_user_id', $loggedInCourierId)
                    ->whereBetween('created_at', [$startDate, $weekEndDate])
                    ->count();
                $chartDataCompleted[] = App\Models\Order::where('created_by_user_id', $loggedInCourierId)
                    ->whereBetween('updated_at', [$startDate, $weekEndDate])
                    ->where('status', 'selesai')
                    ->count();
                $chartDataReturned[] = App\Models\OrderReturn::whereHas('order', function ($query) use ($loggedInCourierId) {
                    $query->where('created_by_user_id', $loggedInCourierId);
                })
                    ->whereBetween('created_at', [$startDate, $weekEndDate])
                    ->count();
                $startDate = $weekEndDate->copy()->addDay();
                $weekNumber++;
            }
            $dateRangeText = Carbon::now()->isoFormat('MMMM YYYY');
            break;

        case 'monthly':
            for ($month = 1; $month <= 12; $month++) {
                $date = Carbon::createFromDate($currentYear, $month, 1);
                $chartLabels[] = $date->isoFormat('MMM');
                $chartData[] = App\Models\Order::where('created_by_user_id', $loggedInCourierId)->whereYear('created_at', $currentYear)->whereMonth('created_at', $month)->count();
                $chartDataCompleted[] = App\Models\Order::where('created_by_user_id', $loggedInCourierId)->whereYear('updated_at', $currentYear)->whereMonth('updated_at', $month)->where('status', 'selesai')->count();
                $chartDataReturned[] = App\Models\OrderReturn::whereHas('order', function ($query) use ($loggedInCourierId) {
                    $query->where('created_by_user_id', $loggedInCourierId);
                })
                    ->whereYear('created_at', $currentYear)
                    ->whereMonth('created_at', $month)
                    ->count();
            }
            $dateRangeText = $currentYear;
            break;

        case 'last_7_days':
        default:
            $endDate = Carbon::today();
            $startDate = Carbon::today()->subDays(6);
            for ($i = 6; $i >= 0; $i--) {
                $date = Carbon::today()->subDays($i);
                $chartLabels[] = $date->format('d M');
                $chartData[] = App\Models\Order::where('created_by_user_id', $loggedInCourierId)->whereDate('created_at', $date)->count();
                $chartDataCompleted[] = App\Models\Order::where('created_by_user_id', $loggedInCourierId)->whereDate('updated_at', $date)->where('status', 'selesai')->count();
                $chartDataReturned[] = App\Models\OrderReturn::whereHas('order', function ($query) use ($loggedInCourierId) {
                    $query->where('created_by_user_id', $loggedInCourierId);
                })
                    ->whereDate('created_at', $date)
                    ->count();
            }
            $dateRangeText = $startDate->isoFormat('D MMM') . ' - ' . $endDate->isoFormat('D MMM');
            break;
    }

    $totalOrdersInRange = array_sum($chartData);
    $totalCompletedOrdersInRange = array_sum($chartDataCompleted);
    $totalReturnedOrdersInRange = array_sum($chartDataReturned);

    ?>
    <div class="w-full max-w full">
        <div class="flex flex-wrap gap-6 -mx-3">
            <div class="w-full max-w-full px-3 space-y-4 lg:flex-nome">
                <div class="mb-6">
                    <div
                        class="relative flex flex-col flex-none max-w-full gap-4 p-6 px-6 py-6 overflow-hidden border shadow-2xl rounded-3xl xl:gap-0 bg-gradient-to-br from-amber-50 via-orange-50 to-yellow-50 dark:from-slate-800 dark:via-slate-700 dark:to-slate-800 border-amber-100 dark:border-slate-600">
                        <div class="absolute top-0 right-0 w-32 h-32 opacity-10">
                            <div
                                class="w-full h-full transform rotate-45 translate-x-8 -translate-y-8 rounded-full bg-gradient-to-br from-yellow-400 to-orange-500">
                            </div>
                        </div>

                        <div class="relative z-10 flex items-center justify-between mb-6">
                            <div class="flex items-center gap-3">
                                <div class="p-3 shadow-lg bg-gradient-to-br from-yellow-400 to-orange-500 rounded-2xl">
                                    <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="flex items-baseline gap-2">
                                        <div class="text-2xl font-bold text-transparent bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text dark:from-white dark:to-gray-300"
                                            id="ucapan">
                                            </div>
                                        <div id="greeting-emoji" class="text-2xl">
                                            </div>
                                    </div>
                                    <div class="text-sm font-medium text-gray-600 dark:text-gray-400">Your Achievement Today
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="relative z-10 grid grid-cols-2 gap-4 md:grid-cols-4">
                            <div
                                class="p-3 border shadow-lg bg-white/60 dark:bg-slate-700/60 backdrop-blur-sm rounded-2xl border-white/20 dark:border-slate-600/20">
                                <div class="flex items-center space-x-3">
                                    <div class="flex-shrink-0">
                                        <div
                                            class="flex items-center justify-center w-10 h-10 bg-blue-100 rounded-full dark:bg-blue-900/50">
                                            <i class="text-lg text-blue-600 fas fa-shopping-basket dark:text-blue-400"></i>
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs text-gray-500 truncate dark:text-gray-400">Total Hari Ini</p>
                                        <p class="text-xl font-bold text-gray-800 truncate dark:text-white">
                                            {{ $totalOrdersToday }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div
                                class="p-3 border shadow-lg bg-white/60 dark:bg-slate-700/60 backdrop-blur-sm rounded-2xl border-white/20 dark:border-slate-600/20">
                                <div class="flex items-center space-x-3">
                                    <div class="flex-shrink-0">
                                        <div
                                            class="flex items-center justify-center w-10 h-10 bg-orange-100 rounded-full dark:bg-orange-900/50">
                                            <i class="text-lg text-orange-600 fas fa-users dark:text-orange-400"></i>
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs text-gray-500 truncate dark:text-gray-400">Customer</p>
                                        <p class="text-xl font-bold text-gray-800 truncate dark:text-white">
                                            {{ $totalCustomersInRegion }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div
                                class="p-3 border shadow-lg bg-white/60 dark:bg-slate-700/60 backdrop-blur-sm rounded-2xl border-white/20 dark:border-slate-600/20">
                                <div class="flex items-center space-x-3">
                                    <div class="flex-shrink-0">
                                        <div
                                            class="flex items-center justify-center w-10 h-10 bg-green-100 rounded-full dark:bg-green-900/50">
                                            <i class="text-lg text-green-600 fas fa-check-circle dark:text-green-400"></i>
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs text-gray-500 truncate dark:text-gray-400">Selesai</p>
                                        <p class="text-xl font-bold text-gray-800 truncate dark:text-white">
                                            {{ $completedOrdersToday }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div
                                class="p-3 border shadow-lg bg-white/60 dark:bg-slate-700/60 backdrop-blur-sm rounded-2xl border-white/20 dark:border-slate-600/20">
                                <div class="flex items-center space-x-3">
                                    <div class="flex-shrink-0">
                                        <div
                                            class="flex items-center justify-center w-10 h-10 bg-red-100 rounded-full dark:bg-red-900/50">
                                            <i class="text-lg text-red-600 fas fa-undo dark:text-red-400"></i>
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs text-gray-500 truncate dark:text-gray-400">Return</p>
                                        <p class="text-xl font-bold text-gray-800 truncate dark:text-white">
                                            {{ $totalReturnedOrdersToday }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid gap-4">
                    <div class="flex flex-wrap -mx-3">
                        <a href="{{ route('kurir.pesanan.create') }}" class="w-1/2 px-3">
                            <div
                                class="relative flex items-center justify-center p-4 overflow-hidden transition-all duration-300 ease-out transform border border-green-100 shadow-xl group md:justify-start bg-gradient-to-br from-green-50 to-emerald-50 dark:from-slate-800 dark:to-slate-700 rounded-2xl hover:shadow-2xl hover:scale-105 dark:border-slate-600">
                                <div
                                    class="absolute inset-0 transition-opacity duration-300 opacity-0 bg-gradient-to-br from-green-400/10 to-emerald-500/10 group-hover:opacity-100">
                                </div>
                                <div class="relative z-10 flex items-center">
                                    <div
                                        class="flex items-center justify-center w-12 h-12 transition-shadow duration-300 shadow-lg md:mr-3 bg-gradient-to-br from-green-400 to-emerald-500 rounded-xl group-hover:shadow-xl">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-white"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M12 3v2a2 2 0 01-2 2h4a2 2 0 01-2-2V3zm-2 9h4m-4 4h4" />
                                        </svg>
                                    </div>
                                    <div class="flex-1 hidden min-w-0 md:block">
                                        <div
                                            class="mb-1 text-lg font-bold text-gray-800 truncate transition-colors duration-300 dark:text-white group-hover:text-green-600 dark:group-hover:text-green-400">
                                            Pesanan</div>
                                        <div class="text-xs font-medium text-gray-600 truncate dark:text-gray-400">
                                            Buat pesanan baru</div>
                                    </div>
                                </div>
                            </div>
                        </a>

                        <button type="button" id="add-customer" class="w-1/2 px-3 mb-6 js-open-modal-btn"
                            data-target-modal="create-customer-modal">
                            <div
                                class="relative flex items-center justify-center p-4 overflow-hidden transition-all duration-300 ease-out transform border border-blue-100 shadow-xl group md:justify-start bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-slate-800 dark:to-slate-700 rounded-2xl hover:shadow-2xl hover:scale-105 dark:border-slate-600">
                                <div
                                    class="absolute inset-0 transition-opacity duration-300 opacity-0 bg-gradient-to-br from-blue-400/10 to-indigo-500/10 group-hover:opacity-100">
                                </div>
                                <div class="relative z-10 flex items-center">
                                    <div
                                        class="flex items-center justify-center w-12 h-12 transition-shadow duration-300 shadow-lg md:mr-3 bg-gradient-to-br from-blue-400 to-indigo-500 rounded-xl group-hover:shadow-xl">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-white"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M18 9v6m3-3h-6M9 16a4 4 0 100-8 4 4 0 000 8zm0 0c-2.21 0-4 1.79-4 4v1h8v-1c0-2.21-1.79-4-4-4z" />
                                        </svg>
                                    </div>
                                    <div class="flex-1 hidden min-w-0 text-left md:block">
                                        <div
                                            class="mb-1 text-lg font-bold text-gray-800 truncate transition-colors duration-300 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400">
                                            Customer</div>
                                        <div class="text-xs font-medium text-gray-600 truncate dark:text-gray-400">
                                            Tambah customer baru</div>
                                    </div>
                                </div>
                            </div>
                        </button>
                    </div>
                </div>
            </div>

            <div class="w-full max-w-full px-3 mt-0 mb-6 lg:mb-0 lg:flex-none">
                <div
                    class="relative flex flex-col min-w-0 overflow-hidden break-words border border-gray-100 shadow-2xl bg-gradient-to-br from-white to-gray-50 dark:from-slate-800 dark:to-slate-900 rounded-3xl bg-clip-border dark:border-slate-700">
                    <div class="p-6 pb-4 mb-0 rounded-t-4">
                        <div class="flex-1">
                            @if (Auth::user()->note)
                                <div class="flex items-center mb-4">
                                    <div class="p-2 mr-3 rounded-lg bg-gradient-to-br from-yellow-400 to-orange-500">
                                        <i class="text-sm text-white fas fa-sticky-note"></i>
                                    </div>
                                    <h6 class="mb-0 text-lg font-bold text-gray-800 dark:text-white">Catatan dari Admin
                                    </h6>
                                </div>
                                <div
                                    class="p-4 border-l-4 border-yellow-400 bg-gradient-to-r from-yellow-50 to-orange-50 dark:from-slate-700 dark:to-slate-600 rounded-2xl">
                                    <p class="font-medium leading-relaxed text-gray-700 dark:text-gray-300">
                                        "{{ Auth::user()->note }}"
                                    </p>
                                </div>
                            @else
                                <div class="flex items-center mb-4">
                                    <div class="p-2 mr-3 rounded-lg bg-gradient-to-br from-green-400 to-emerald-500">
                                        <i class="text-sm text-white fas fa-check-circle"></i>
                                    </div>
                                    <h6 class="mb-0 text-lg font-bold text-gray-800 dark:text-white">Tidak Ada Catatan Baru
                                    </h6>
                                </div>
                                <div
                                    class="p-4 border-l-4 border-green-400 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-slate-700 dark:to-slate-600 rounded-2xl">
                                    <p class="leading-relaxed text-gray-700 dark:text-gray-300">
                                        Saat ini tidak ada catatan baru dari admin.
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="w-full max-w-full px-3 mt-0 lg:flex-none">
                <div
                    class="relative overflow-hidden bg-white border border-gray-100 shadow-2xl rounded-3xl dark:bg-slate-800 dark:border-slate-700">
                    <div class="p-6">
                        <div class="flex flex-wrap items-center justify-between gap-4 mb-2">
                            <div class="flex flex-col">
                                <h6 class="flex items-center gap-2 text-lg font-bold text-gray-800 dark:text-white">
                                    <svg class="w-6 h-6 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none"
                                        viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                                    </svg>
                                    <span>{{ $chartTitle }}</span>
                                </h6>
                                <p class="text-sm text-gray-500 dark:text-gray-400">📆 {{ $dateRangeText }}</p>
                            </div>
                            <div class="flex items-center gap-4">
                                <div class="flex items-center">
                                    <span class="w-3 h-3 mr-2 bg-blue-500 rounded-full"></span>
                                    <span class="mr-1 text-sm font-medium text-gray-600 dark:text-gray-300">Total </span>
                                    <span
                                        class="text-sm font-bold text-gray-800 dark:text-white">{{ $totalOrdersInRange }}</span>
                                </div>
                                <div class="flex items-center">
                                    <span class="w-3 h-3 mr-2 bg-green-500 rounded-full"></span>
                                    <span class="mr-1 text-sm font-medium text-gray-600 dark:text-gray-300">Selesai </span>
                                    <span
                                        class="text-sm font-bold text-gray-800 dark:text-white">{{ $totalCompletedOrdersInRange }}</span>
                                </div>
                                <div class="flex items-center">
                                    <span class="w-3 h-3 mr-2 bg-red-500 rounded-full"></span>
                                    <span class="mr-1 text-sm font-medium text-gray-600 dark:text-gray-300">Return </span>
                                    <span
                                        class="text-sm font-bold text-gray-800 dark:text-white">{{ $totalReturnedOrdersInRange }}</span>
                                </div>
                                <div class="relative">
                                    <button id="chartFilterButton" type="button"
                                        class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-white focus:outline-none">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z">
                                            </path>
                                        </svg>
                                    </button>
                                    <div id="chartFilterDropdown"
                                        class="absolute right-0 z-20 hidden w-40 mt-2 bg-white border border-gray-200 rounded-lg shadow-lg dark:bg-slate-700 dark:border-slate-600">
                                        <a href="{{ url()->current() }}?filter=last_7_days"
                                            class="block px-4 py-2 text-sm font-semibold rounded-t-lg transition-colors duration-150 {{ $filter === 'last_7_days' ? 'bg-blue-100 text-blue-700 dark:bg-slate-600 dark:text-white' : 'text-gray-700 dark:text-gray-200 hover:bg-blue-50 dark:hover:bg-slate-600' }}">7
                                            Hari</a>
                                        <a href="{{ url()->current() }}?filter=daily"
                                            class="block px-4 py-2 text-sm font-semibold transition-colors duration-150 {{ $filter === 'daily' ? 'bg-blue-100 text-blue-700 dark:bg-slate-600 dark:text-white' : 'text-gray-700 dark:text-gray-200 hover:bg-blue-50 dark:hover:bg-slate-600' }}">Harian</a>
                                        <a href="{{ url()->current() }}?filter=weekly"
                                            class="block px-4 py-2 text-sm font-semibold transition-colors duration-150 {{ $filter === 'weekly' ? 'bg-blue-100 text-blue-700 dark:bg-slate-600 dark:text-white' : 'text-gray-700 dark:text-gray-200 hover:bg-blue-50 dark:hover:bg-slate-600' }}">Mingguan</a>
                                        <a href="{{ url()->current() }}?filter=monthly"
                                            class="block px-4 py-2 text-sm font-semibold rounded-b-lg transition-colors duration-150 {{ $filter === 'monthly' ? 'bg-blue-100 text-blue-700 dark:bg-slate-600 dark:text-white' : 'text-gray-700 dark:text-gray-200 hover:bg-blue-50 dark:hover:bg-slate-600' }}">Bulanan</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex-auto p-4 pt-0">
                        <div>
                            <canvas id="ordersChart" class="h-[250px] lg:h-[300px]"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap mt-6 -mx-3">
            <div class="w-full max-w-full px-3 mt-0 lg:flex-none">
                <div class="flex flex-wrap mt-6 -mx-3">
                    <div class="w-full max-w-full px-3 mt-0 lg:flex-none">
                        <div
                            class="relative flex flex-col min-w-0 mb-4 overflow-hidden break-words border border-gray-100 shadow-2xl bg-gradient-to-br from-white to-gray-50 dark:from-slate-800 dark:to-slate-900 rounded-3xl bg-clip-border dark:border-slate-700">
                            <div class="p-6 pb-0 mb-0 rounded-t-3xl">
                                <div class="flex items-center justify-between">
                                    <h5 class="mb-1 text-xl font-bold text-gray-800 dark:text-white">🎯 Pesanan Terbaru
                                    </h5>
                                    <div class="p-2 rounded-lg bg-gradient-to-br from-purple-400 to-pink-500">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                            <div class="flex-auto px-0 pt-0 pb-2">
                                <!-- TAMPILAN DESKTOP -->
                                <div class="hidden p-0 overflow-x-auto md:block">
                                    <table class="items-center w-full mb-0 align-top border-collapse text-slate-500">
                                        <thead class="align-bottom">
                                            <tr>
                                                <th
                                                    class="px-4 py-4 text-xs font-bold tracking-wider text-center text-gray-700 uppercase align-middle border-b border-gray-200 dark:text-gray-300 bg-gray-50 dark:bg-slate-700 dark:border-slate-600">
                                                    #</th>
                                                <th
                                                    class="px-6 py-4 text-xs font-bold tracking-wider text-left text-gray-700 uppercase align-middle border-b border-gray-200 dark:text-gray-300 bg-gray-50 dark:bg-slate-700 dark:border-slate-600">
                                                    Invoice</th>
                                                <th
                                                    class="px-6 py-4 text-xs font-bold tracking-wider text-left text-gray-700 uppercase align-middle border-b border-gray-200 dark:text-gray-300 bg-gray-50 dark:bg-slate-700 dark:border-slate-600">
                                                    Customer</th>
                                                <th
                                                    class="px-6 py-4 text-xs font-bold tracking-wider text-left text-gray-700 uppercase align-middle border-b border-gray-200 dark:text-gray-300 bg-gray-50 dark:bg-slate-700 dark:border-slate-600">
                                                    Alamat</th>
                                                <th
                                                    class="px-6 py-4 text-xs font-bold tracking-wider text-left text-gray-700 uppercase align-middle border-b border-gray-200 dark:text-gray-300 bg-gray-50 dark:bg-slate-700 dark:border-slate-600">
                                                    Total Pesanan</th>
                                                <th
                                                    class="px-6 py-4 text-xs font-bold tracking-wider text-center text-gray-700 uppercase align-middle border-b border-gray-200 dark:text-gray-300 bg-gray-50 dark:bg-slate-700 dark:border-slate-600">
                                                    Status</th>
                                                <th
                                                    class="px-4 py-4 text-xs font-bold tracking-wider text-center text-gray-700 uppercase align-middle border-b border-gray-200 dark:text-gray-300 bg-gray-50 dark:bg-slate-700 dark:border-slate-600">
                                                    Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($latestOrders as $loop => $order)
                                                @php
                                                    // Menghitung total awal dan total terbaru berdasarkan data retur
                                                    $initialTotal = 0;
                                                    foreach ($order->items as $item) {
                                                        $initialTotal += ($item->quantity ?? 0) * ($item->price ?? 0);
                                                    }

                                                    $latestTotal = $order->total_amount;
                                                    $showReturnedView = false;

                                                    if ($order->status === 'menunggu_retur') {
                                                        $showReturnedView = true;
                                                        $orderReturn = OrderReturn::where('order_id', $order->id)->first();
                                                        if ($orderReturn) {
                                                            $returnProducts = OrderReturnProduct::where('order_return_id', $orderReturn->id)->get();
                                                            $totalReturnValue = 0;
                                                            foreach ($returnProducts as $returnItem) {
                                                                $totalReturnValue += ($returnItem->quantity ?? 0) * ($returnItem->price ?? 0);
                                                            }
                                                            $latestTotal = $initialTotal - $totalReturnValue;
                                                        } else {
                                                            $latestTotal = $initialTotal;
                                                        }
                                                    } else {
                                                        $calculatedLatestTotal = 0;
                                                        $hasReturnedItems = false;
                                                        foreach ($order->items as $item) {
                                                            $initialQty = $item->quantity ?? 0;
                                                            $returnedQty = $item->returned_quantity ?? 0;
                                                            $price = $item->price ?? 0;
                                                            $calculatedLatestTotal += ($initialQty - $returnedQty) * $price;
                                                            if ($returnedQty > 0) $hasReturnedItems = true;
                                                        }
                                                        if ($hasReturnedItems && $calculatedLatestTotal < $initialTotal) {
                                                            $latestTotal = $calculatedLatestTotal;
                                                            $showReturnedView = true;
                                                        }
                                                    }
                                                @endphp

                                                @php
                                                    $shortInvoice = '#' . substr($order->invoice_number, -3);
                                                @endphp
                                                <tr
                                                    class="transition-colors duration-200 hover:bg-gray-50 dark:hover:bg-slate-700">
                                                    <td
                                                        class="p-4 text-center align-middle bg-transparent border-b dark:border-slate-600 whitespace-nowrap">
                                                        <span
                                                            class="text-sm font-semibold text-gray-600 dark:text-gray-300">{{ $loop->iteration }}</span>
                                                    </td>
                                                    <td
                                                        class="p-4 align-middle bg-transparent border-b dark:border-slate-600 whitespace-nowrap">
                                                        <span
                                                            class="text-sm font-semibold text-gray-800 dark:text-white">{{ $shortInvoice }}</span>
                                                    </td>
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
                                                        <p
                                                            class="mb-0 text-sm font-medium text-gray-700 dark:text-gray-300">
                                                            {{ Str::limit($order->address ?? 'Alamat tidak ada', 25) }}
                                                        </p>
                                                    </td>
                                                    <td class="p-4 align-middle bg-transparent border-b dark:border-slate-600 whitespace-nowrap">
                                                        <button type="button" class="w-full text-left transition-colors js-open-modal-btn hover:opacity-80"
                                                            data-target-modal="orderDetailsModal" onclick="fetchOrderDetails({{ $order->id }})">
                                                            @if ($showReturnedView)
                                                                <div>
                                                                    <p class="text-sm font-bold text-gray-400 dark:text-gray-500 line-through">
                                                                        Rp {{ number_format($initialTotal, 0, ',', '.') }}
                                                                    </p>
                                                                    <p class="text-lg font-extrabold text-green-600 dark:text-green-500">
                                                                        Rp {{ number_format($latestTotal, 0, ',', '.') }}
                                                                    </p>
                                                                </div>
                                                            @else
                                                                <p class="text-lg font-bold text-blue-600 dark:text-blue-400">
                                                                    Rp
                                                                    {{ number_format($order->total_amount, 0, ',', '.') }}
                                                                </p>
                                                            @endif
                                                        </button>
                                                    </td>
                                                    <td
                                                        class="p-4 text-sm leading-normal text-center align-middle bg-transparent border-b dark:border-slate-600 whitespace-nowrap">
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
                                                                case 'menunggu_retur':
                                                                    $statusClass = 'bg-red-100 text-red-800';
                                                                    break;
                                                                case 'menunggu_verifikasi_admin':
                                                                    $statusClass =
                                                                        'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300';
                                                                    break;
                                                                default:
                                                                    $statusClass = 'bg-gray-100 text-gray-800';
                                                                    break;
                                                            }
                                                        @endphp
                                                        <span
                                                            class="px-2.5 py-1 text-xs font-semibold rounded-full {{ $statusClass }}">{{ $statusText }}</span>
                                                    </td>
                                                    <td
                                                        class="p-4 text-center align-middle bg-transparent border-b dark:border-slate-600 whitespace-nowrap">
                                                        <button type="button"
                                                            class="p-2 text-gray-500 transition-colors rounded-full js-open-modal-btn hover:bg-gray-100 dark:hover:bg-slate-600"
                                                            title="Ubah Status" data-target-modal="statusStepperModal"
                                                            onclick="openStatusStepperModal({{ $order->id }})">
                                                            <svg class="w-6 h-6" fill="none" stroke="currentColor"
                                                                viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2.5" d="M9 5l7 7-7 7"></path>
                                                            </svg>
                                                        </button>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="7"
                                                        class="p-6 text-center text-gray-500 dark:text-gray-400">
                                                        Belum ada pesanan yang dibuat.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                <!-- TAMPILAN MOBILE -->
                                <div class="px-2 py-2 space-y-3 md:hidden">
                                    @forelse($latestOrders as $loop => $order)
                                        @php
                                            // Menghitung total awal dan total terbaru berdasarkan data retur untuk tampilan mobile
                                            $initialTotal = 0;
                                            foreach ($order->items as $item) {
                                                $initialTotal += ($item->quantity ?? 0) * ($item->price ?? 0);
                                            }

                                            $latestTotal = $order->total_amount;
                                            $showReturnedView = false;

                                            if ($order->status === 'menunggu_retur') {
                                                $showReturnedView = true;
                                                $orderReturn = OrderReturn::where('order_id', $order->id)->first();
                                                if ($orderReturn) {
                                                    $returnProducts = OrderReturnProduct::where('order_return_id', $orderReturn->id)->get();
                                                    $totalReturnValue = 0;
                                                    foreach ($returnProducts as $returnItem) {
                                                        $totalReturnValue += ($returnItem->quantity ?? 0) * ($returnItem->price ?? 0);
                                                    }
                                                    $latestTotal = $initialTotal - $totalReturnValue;
                                                } else {
                                                    $latestTotal = $initialTotal;
                                                }
                                            } else {
                                                $calculatedLatestTotal = 0;
                                                $hasReturnedItems = false;
                                                foreach ($order->items as $item) {
                                                    $initialQty = $item->quantity ?? 0;
                                                    $returnedQty = $item->returned_quantity ?? 0;
                                                    $price = $item->price ?? 0;
                                                    $calculatedLatestTotal += ($initialQty - $returnedQty) * $price;
                                                    if ($returnedQty > 0) $hasReturnedItems = true;
                                                }
                                                if ($hasReturnedItems && $calculatedLatestTotal < $initialTotal) {
                                                    $latestTotal = $calculatedLatestTotal;
                                                    $showReturnedView = true;
                                                }
                                            }
                                        @endphp
                                        @php
                                            $customerPhone = $order->phone ?? ($order->customer->phone ?? '');
                                            $formattedPhone = preg_replace('/[^0-9]/', '', $customerPhone);
                                            $shortInvoice = '#' . substr($order->invoice_number, -3);
                                            $customerName = $order->customer->name ?? 'Pelanggan Dihapus';
                                            $displayName = Str::words($customerName, 2, '');
                                        @endphp
                                        <div
                                            class="p-3 border shadow-lg bg-white/60 dark:bg-slate-700/60 backdrop-blur-sm rounded-2xl border-white/20 dark:border-slate-600/20">
                                            <div class="flex items-start justify-between w-full space-x-3">
                                                <div class="flex-shrink-0 pt-1">
                                                    <span
                                                        class="flex items-center justify-center w-6 h-6 text-xs font-bold text-gray-600 bg-gray-200 rounded-full dark:bg-slate-600 dark:text-gray-300">
                                                        {{ $loop->iteration }}
                                                    </span>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-baseline space-x-2">
                                                        <h6 class="text-base font-bold text-gray-800 dark:text-white">
                                                            {{ $displayName ?? 'Pelanggan Dihapus' }}
                                                        </h6>
                                                    </div>
                                                    <button type="button"
                                                        class="block w-full mt-2 text-sm text-left text-gray-500 transition-colors js-open-modal-btn dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400"
                                                        data-target-modal="orderDetailsModal"
                                                        onclick="fetchOrderDetails({{ $order->id }})">
                                                        <span>{{ $shortInvoice ?? 'N/A' }}</span>
                                                        <span class="mx-1">|</span>
                                                        @if ($showReturnedView)
                                                            <span class="inline-flex items-center gap-1">
                                                                <del class="text-xs mr-1 text-gray-500">Rp
                                                                    {{ number_format($initialTotal, 0, ',', '.') }}</del>
                                                                <span class="font-bold text-green-600 dark:text-green-500">Rp
                                                                    {{ number_format($latestTotal, 0, ',', '.') }}</span>
                                                            </span>
                                                        @else
                                                            <span>Rp
                                                                {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                                                        @endif
                                                    </button>
                                                </div>

                                                <div class="flex flex-col items-end flex-shrink-0 space-y-2">
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
                                                            case 'menunggu_retur':
                                                                $statusClass = 'bg-red-100 text-red-800';
                                                                break;
                                                            case 'menunggu_verifikasi_admin':
                                                                $statusClass =
                                                                    'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300';
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
                                                                class="text-green-500 transition-colors hover:text-green-600">
                                                                <i class="text-2xl fab fa-whatsapp"></i>
                                                            </a>
                                                        @endif
                                                        <button type="button"
                                                            class="text-gray-400 transition-colors js-open-modal-btn hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300"
                                                            title="Ubah Status" data-target-modal="statusStepperModal"
                                                            onclick="openStatusStepperModal({{ $order->id }})">
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
    </div>
    </div>
    </div>
    @push('flowbite-modals')
        @if (isset($customerCategories))
            @include('dashboard.kurir.customers.create', ['customerCategories' => $customerCategories])
        @else
            @include('dashboard.kurir.customers.create', ['customerCategories' => []])
        @endif
        @include('dashboard.kurir.pesanan.rincian-modal')
        @include('dashboard.kurir.pesanan.status-modal')
        @include('dashboard.kurir.pesanan.return-modal')
    @endpush

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Menangani dropdown untuk filter chart
            const chartFilterButton = document.getElementById('chartFilterButton');
            const chartFilterDropdown = document.getElementById('chartFilterDropdown');
            if (chartFilterButton && chartFilterDropdown) {
                chartFilterButton.addEventListener('click', function(e) {
                    e.stopPropagation();
                    chartFilterDropdown.classList.toggle('hidden');
                });
                document.addEventListener('click', function(e) {
                    if (!chartFilterDropdown.classList.contains('hidden')) {
                        if (!chartFilterButton.contains(e.target) && !chartFilterDropdown.contains(e
                                .target)) {
                            chartFilterDropdown.classList.add('hidden');
                        }
                    }
                });
            }

            // Inisialisasi Chart.js untuk menampilkan grafik pesanan
            var ctx = document.getElementById('ordersChart');
            if (ctx && window.Chart) {
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: @json($chartLabels),
                        datasets: [{
                                label: 'Total Pesanan',
                                data: @json($chartData),
                                borderColor: '#3b82f6',
                                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                fill: true,
                                tension: 0.4,
                                pointBackgroundColor: '#3b82f6',
                                pointBorderColor: '#fff',
                                pointBorderWidth: 2,
                                pointRadius: 5,
                                pointHoverRadius: 7,
                            },
                            {
                                label: 'Selesai',
                                data: @json($chartDataCompleted),
                                borderColor: '#22c55e',
                                backgroundColor: 'rgba(34, 197, 94, 0.1)',
                                fill: true,
                                tension: 0.4,
                                pointBackgroundColor: '#22c55e',
                                pointBorderColor: '#fff',
                                pointBorderWidth: 2,
                                pointRadius: 5,
                                pointHoverRadius: 7,
                            },
                            {
                                label: 'Return',
                                data: @json($chartDataReturned),
                                borderColor: '#ef4444',
                                backgroundColor: 'rgba(239, 68, 68, 0.1)',
                                fill: true,
                                tension: 0.4,
                                pointBackgroundColor: '#ef4444',
                                pointBorderColor: '#fff',
                                pointBorderWidth: 2,
                                pointRadius: 5,
                                pointHoverRadius: 7,
                            }
                        ]
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
                                        return `${context.dataset.label}: ${context.parsed.y}`;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        if (Math.floor(value) === value) {
                                            return value;
                                        }
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // Memperbarui ucapan selamat berdasarkan waktu
            const courierName = @json(Str::words(Auth::user()->name, 2, ''));
            const hour = new Date().getHours();
            let greetingText = '';
            let emoji = '';

            if (hour >= 4 && hour < 11) {
                greetingText = `Selamat Pagi, ${courierName}`;
                emoji = '🌤️';
            } else if (hour >= 11 && hour < 15) {
                greetingText = `Selamat Siang, ${courierName}`;
                emoji = '☀️';
            } else if (hour >= 15 && hour < 18) {
                greetingText = `Selamat Sore, ${courierName}`;
                emoji = '🌇';
            } else {
                greetingText = `Selamat Malam, ${courierName}`;
                emoji = '🌙';
            }

            document.getElementById("ucapan").textContent = greetingText;
            document.getElementById("greeting-emoji").textContent = emoji;
        });
    </script>


    <script>
        const APP_URL = "{{ url('/') }}";
        const STATUS_LABEL_MAP = @json($statusLabelMap);

        // Fungsi bantuan untuk mengambil CSRF token
        function getCsrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        }

        // Fungsi bantuan untuk menampilkan notifikasi toast
        function dispatchToast(message, type = 'success') {
            window.dispatchEvent(new CustomEvent('show-toast', {
                detail: {
                    type: type,
                    message: message
                }
            }));
        }

        // Menangani logika penambahan/pengurangan kuantitas di modal retur
        document.addEventListener('DOMContentLoaded', () => {
            const returnModal = document.getElementById('returnProductModal');
            if (returnModal) {
                returnModal.addEventListener('click', function(event) {
                    const button = event.target.closest('button');
                    if (!button) return;

                    const productContainer = event.target.closest('[data-return-key]');
                    if (!productContainer) return;

                    const quantitySpan = productContainer.querySelector('.quantity-input');
                    if (!quantitySpan) return;

                    let currentValue = parseInt(quantitySpan.textContent, 10);
                    const maxValue = parseInt(quantitySpan.dataset.max, 10);

                    if (button.classList.contains('quantity-plus') && currentValue < maxValue) {
                        quantitySpan.textContent = currentValue + 1;
                    } else if (button.classList.contains('quantity-minus') && currentValue > 0) {
                        quantitySpan.textContent = currentValue - 1;
                    } else if (button.classList.contains('remove-product')) {
                        quantitySpan.textContent = 0;
                    }
                });
            }
        });

        // Mengambil dan menampilkan detail pesanan pada modal
        async function fetchOrderDetails(orderId) {
            openModal('orderDetailsModal');
            const modalLoader = document.getElementById('modalLoader');
            const modalContent = document.getElementById('modalContent');
            modalContent.classList.add('hidden');
            modalLoader.classList.remove('hidden');
            modalLoader.innerHTML = `<svg class="w-8 h-8 mx-auto text-blue-600 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg><p class="mt-4 text-lg">Memuat Detail Pesanan...</p>`;
            try {
                const response = await fetch(`/kurir/pesanan/${orderId}/details`);
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Gagal mengambil data.');
                populateOrderDetailsModal(data);
            } catch (error) {
                modalLoader.innerHTML = `<div class="text-center"><p class="font-bold text-red-600">Gagal Memuat Data</p><p class="mt-2 text-sm">${error.message}</p></div>`;
            }
        }

        // Mengisi data ke dalam modal rincian pesanan
        function populateOrderDetailsModal(order) {
            document.getElementById('modalInvoiceNumber').textContent = order.invoice_number || 'N/A';
            document.getElementById('customerName').textContent = order.customer.name || 'N/A';
            document.getElementById('customerPhone').textContent = order.customer.phone || 'N/A';
            document.getElementById('customerAddress').textContent = order.customer.address || 'N/A';
            const companyNameEl = document.getElementById('customerCompanyName');
            if (order.customer.company_name && order.customer.company_name !== 'N/A') {
                companyNameEl.textContent = `🏢 ${order.customer.company_name}`;
                companyNameEl.classList.remove('hidden');
            } else {
                companyNameEl.classList.add('hidden');
            }
            document.getElementById('paymentMethod').textContent = order.payment_method || 'N/A';
            document.getElementById('orderCreatedAt').textContent = order.created_at || 'Tidak Tersedia';
            document.getElementById('orderPaidAt').textContent = order.paid_at ? (order.paid_at + (order.paid_at_label || '')) : 'Belum Lunas';
            document.getElementById('orderNotesContainer').textContent = order.note || '"Tidak ada catatan."';
            
            const statusBadge = document.getElementById('modalOrderStatusBadge');
            const statusIcon = document.getElementById('modalOrderStatusIcon');
            if (statusBadge) {
                const statusText = STATUS_LABEL_MAP[order.status] || (order.status.charAt(0).toUpperCase() + order.status.slice(1).replace(/_/g, ' '));
                statusBadge.textContent = statusText;

                let badgeColorClasses = 'bg-gray-100 text-gray-800';
                switch (order.status) {
                    case 'diambil': badgeColorClasses = 'bg-blue-100 text-blue-800'; break;
                    case 'diantar': badgeColorClasses = 'bg-yellow-100 text-yellow-800'; break;
                    case 'diterima_pembeli': badgeColorClasses = 'bg-purple-100 text-purple-800'; break;
                    case 'menunggu_retur': badgeColorClasses = 'bg-red-100 text-red-800'; break;
                    case 'menunggu_verifikasi_admin': badgeColorClasses = 'bg-orange-100 text-orange-800'; break;
                    case 'selesai': badgeColorClasses = 'bg-green-100 text-green-800'; break;
                }
                statusBadge.className = `flex-shrink-0 px-3 py-1 text-sm font-semibold rounded-full whitespace-nowrap ${badgeColorClasses}`;
                
                if (order.status === 'selesai' && statusIcon) {
                    statusIcon.innerHTML = `<svg class="w-8 h-8" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="#10b981" stroke-width="1.5" fill="#d1fae5"/><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4" stroke="#10b981" stroke-width="2"/></svg>`;
                    statusIcon.classList.remove('hidden');
                } else if (statusIcon) {
                    statusIcon.innerHTML = '';
                    statusIcon.classList.add('hidden');
                }
            }

            let calculatedInitialTotal = 0;
            let calculatedLatestTotal = 0;
            let isReturned = false;
            if (order.products && order.products.length > 0) {
                order.products.forEach(p => {
                    const initialQuantity = p.quantity || 0;
                    const returnedQuantity = p.returned_quantity || 0;
                    calculatedInitialTotal += initialQuantity * (p.price || 0);
                    calculatedLatestTotal += (initialQuantity - returnedQuantity) * (p.price || 0);
                    if (returnedQuantity > 0) isReturned = true;
                });
            }

            const singleTotalContainer = document.getElementById('singleTotalAmountContainer');
            const returnedTotalContainer = document.getElementById('returnedTotalAmountContainer');
            if (isReturned && calculatedInitialTotal !== calculatedLatestTotal) {
                document.getElementById('modalInitialTotalAmount').textContent = `Rp ${new Intl.NumberFormat('id-ID').format(calculatedInitialTotal)}`;
                document.getElementById('modalLatestTotalAmount').textContent = `Rp ${new Intl.NumberFormat('id-ID').format(calculatedLatestTotal)}`;
                singleTotalContainer.classList.add('hidden');
                returnedTotalContainer.classList.remove('hidden');
            } else {
                document.getElementById('modalTotalAmount').textContent = `Rp ${new Intl.NumberFormat('id-ID').format(order.total_amount || 0)}`;
                singleTotalContainer.classList.remove('hidden');
                returnedTotalContainer.classList.add('hidden');
            }

            const productDetailsDiv = document.getElementById('productDetails');
            productDetailsDiv.innerHTML = '';
            productDetailsDiv.className = 'flex flex-col space-y-2';
            if (order.products && order.products.length > 0) {
                order.products.forEach(product => {
                    const productItem = document.createElement('div');
                    productItem.className = 'p-3 border rounded-lg dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 flex items-start space-x-4';
                    const initialQty = product.quantity || 0;
                    const returnedQty = product.returned_quantity || 0;
                    const remainingQty = initialQty - returnedQty;
                    const price = product.price || 0;
                    const newSubtotal = remainingQty * price;

                    const iconHTML = `<div class="flex items-center justify-center flex-shrink-0 w-8 h-8 mt-1 bg-gray-200 rounded-lg dark:bg-gray-600"><svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg></div>`;
                    let quantityLine = `<p class="text-sm text-gray-600 dark:text-gray-300">Jumlah: ${initialQty}</p>`;
                    if (returnedQty > 0) {
                        quantityLine = `<p class="text-sm text-gray-600 dark:text-gray-300">Awal: <span class="font-medium text-gray-800 dark:text-gray-200">${initialQty}</span> | Retur: <span class="font-medium text-red-500">${returnedQty}</span> | Sisa: <span class="font-medium text-green-600">${remainingQty}</span></p>`;
                    }
                    const priceLine = `<p class="mt-1 text-sm font-medium text-gray-800 dark:text-gray-200">Rp ${new Intl.NumberFormat('id-ID').format(price)} &rarr; Rp ${new Intl.NumberFormat('id-ID').format(newSubtotal)}</p>`;
                    const detailsHTML = `<div class="flex-grow"><p class="font-bold text-gray-900 dark:text-white">${product.name} ${product.variant_name ? `(${product.variant_name})` : ''}</p>${quantityLine}${priceLine}</div>`;
                    productItem.innerHTML = iconHTML + detailsHTML;
                    productDetailsDiv.appendChild(productItem);
                });
            } else {
                productDetailsDiv.innerHTML = '<p class="text-center text-gray-500">Tidak ada produk.</p>';
            }

            const paymentUploadForm = document.getElementById('paymentUploadForm');
            const paymentProofUploaded = document.getElementById('paymentProofUploaded');
            const paymentUploadBlocker = document.getElementById('paymentUploadBlocker');
            const compressLink = document.getElementById('compress-link');
            paymentUploadForm.classList.add('hidden');
            paymentProofUploaded.classList.add('hidden');
            paymentUploadBlocker.classList.add('hidden');
            compressLink.classList.add('hidden');

            const getImageUrl = (path) => path ? `${APP_URL}/storage/${path.replace(/^public\//, '')}` : '';
            let proofPath = order.payment_proof || (order.order_return ? order.order_return.return_proof : null);

            if (proofPath) {
                document.getElementById('proofImage').src = getImageUrl(proofPath);
                document.getElementById('proofUploadedTitle').textContent = order.payment_proof ? 'Bukti Pembayaran' : 'Bukti Retur';
                paymentProofUploaded.classList.remove('hidden');
            } else if (order.status === 'diterima_pembeli' || order.status === 'menunggu_retur') {
                paymentUploadForm.classList.remove('hidden');
                compressLink.classList.remove('hidden');
                const isReturn = order.status === 'menunggu_retur';
                document.getElementById('paymentProofTitle').textContent = isReturn ? 'Unggah Bukti Retur' : 'Unggah Bukti Pembayaran';
                document.getElementById('uploadButtonText').textContent = isReturn ? 'Unggah Bukti Retur' : 'Unggah Bukti Pembayaran';
                paymentUploadForm.onsubmit = (e) => { e.preventDefault(); handleProofUpload(order.id, order.status); };
            } else {
                paymentUploadBlocker.classList.remove('hidden');
            }

            const returnRequestButtonContainer = document.getElementById('returnRequestButtonContainer');
            if (order.status === 'diterima_pembeli') {
                returnRequestButtonContainer.classList.remove('hidden');
                document.getElementById('requestReturnButton').onclick = () => openReturnProductModal(order);
            } else {
                returnRequestButtonContainer.classList.add('hidden');
            }

            document.getElementById('modalLoader').classList.add('hidden');
            document.getElementById('modalContent').classList.remove('hidden');
        }

        // Menangani unggah bukti pembayaran atau retur
        async function handleProofUpload(orderId, status) {
            const form = document.getElementById('paymentUploadForm');
            const submitButton = form.querySelector('button[type="submit"]');
            let url, nextStatus;

            if (status === 'diterima_pembeli') {
                url = `/kurir/pesanan/${orderId}/upload-proof`;
                nextStatus = 'selesai';
            } else if (status === 'menunggu_retur') {
                url = `/kurir/pesanan/${orderId}/upload-return-proof`;
                nextStatus = 'menunggu_verifikasi_admin';
            } else return;

            submitButton.disabled = true;
            try {
                const response = await fetch(url, { method: 'POST', body: new FormData(form), headers: { 'X-CSRF-TOKEN': getCsrfToken(), 'Accept': 'application/json' } });
                const result = await response.json();
                if (!response.ok) throw new Error(result.message);
                dispatchToast(result.message, 'success');
                fetchOrderDetails(orderId);
                updateTableRowStatus(orderId, nextStatus);
            } catch (error) {
                dispatchToast(error.message, 'error');
            } finally {
                submitButton.disabled = false;
            }
        }

        // Membuka modal untuk mengubah status pesanan
        async function openStatusStepperModal(orderId) {
            openModal('statusStepperModal');
            const modalLoader = document.getElementById('statusStepperModalLoader');
            const modalContent = document.getElementById('statusStepperModalContent');
            modalContent.classList.add('hidden');
            modalLoader.classList.remove('hidden');
            try {
                const response = await fetch(`/kurir/pesanan/${orderId}/details`);
                const data = await response.json();
                if (!response.ok) throw new Error(data.message);
                populateStatusStepperModal(data);
            } catch (error) {
                modalLoader.innerHTML = `<div class="text-center"><p class="font-bold text-red-600">Gagal Memuat</p><p class="mt-2 text-sm">${error.message}</p></div>`;
            }
        }

        // Mengisi data ke dalam modal status stepper
        function populateStatusStepperModal(order) {
            const statusMap = {
                'baru': { label: 'Baru', nextStatus: 'diambil', buttonText: 'Ubah ke Diambil' },
                'dikemas': { label: 'Dikemas', nextStatus: 'diambil', buttonText: 'Ubah ke Diambil' },
                'diambil': { label: 'Diambil', nextStatus: 'diantar', buttonText: 'Ubah ke Diantar' },
                'diantar': { label: 'Diantar', nextStatus: 'diterima_pembeli', buttonText: 'Ubah ke Diterima' },
                'diterima_pembeli': { label: 'Diterima Pembeli', nextStatus: null, buttonText: 'Menunggu Bukti Bayar' },
                'menunggu_retur': { label: 'Menunggu Retur', nextStatus: null, buttonText: 'Proses Retur' },
                'menunggu_verifikasi_admin': { label: 'Menunggu Verifikasi', nextStatus: null, buttonText: 'Menunggu Verifikasi' },
                'selesai': { label: 'Selesai', nextStatus: null, buttonText: 'Pesanan Selesai' },
                'diverifikasi_admin': { label: 'Diverifikasi', nextStatus: null, buttonText: 'Telah Diverifikasi' }
            };

            document.getElementById('modalStatusInvoiceNumber').textContent = order.invoice_number || 'N/A';
            document.getElementById('modalStatusCustomerName').textContent = order.customer.name || 'N/A';

            updateStepperUI(order);

            const updateButton = document.getElementById('updateStatusButton');
            const updateButtonText = document.getElementById('updateStatusButtonText');
            const currentStatusInfo = statusMap[order.status || 'baru'];

            if (currentStatusInfo) {
                updateButtonText.textContent = currentStatusInfo.buttonText;
                if (!currentStatusInfo.nextStatus || ['selesai', 'diverifikasi_admin', 'menunggu_verifikasi_admin', 'menunggu_retur'].includes(order.status)) {
                    updateButton.disabled = true;
                    updateButton.classList.add('opacity-50', 'cursor-not-allowed');
                } else {
                    updateButton.disabled = false;
                    updateButton.classList.remove('opacity-50', 'cursor-not-allowed');
                    updateButton.setAttribute('data-next-status', currentStatusInfo.nextStatus);
                }
            } else {
                updateButtonText.textContent = `Status Tidak Dikenal`;
                updateButton.disabled = true;
                updateButton.classList.add('opacity-50', 'cursor-not-allowed');
            }
            updateButton.setAttribute('data-order-id', order.id);

            document.getElementById('statusStepperModalLoader').classList.add('hidden');
            document.getElementById('statusStepperModalContent').classList.remove('hidden');
        }

        // Memperbarui tampilan visual dari status stepper
        function updateStepperUI(order) {
            const steps = {
                diambil: { at: order.picked_up_at, icon: 'fa-box' },
                diantar: { at: order.delivered_at, icon: 'fa-truck-moving' },
                diterima_pembeli: { at: order.received_by_buyer_at, icon: 'fa-home' }
            };
            const timeSpans = { diambil: 'pickedUpAt', diantar: 'deliveredAt', diterima_pembeli: 'receivedByBuyerAt' };

            Object.keys(steps).forEach(step => {
                const iconEl = document.getElementById(`step-${step}-icon`);
                const timeSpanEl = document.getElementById(timeSpans[step]);
                iconEl.classList.remove('bg-green-600', 'text-green-600', 'border-green-600');
                
                if (steps[step].at) {
                    iconEl.innerHTML = '<i class="text-green-600 fas fa-check-circle"></i>';
                    iconEl.classList.add('bg-green-600', 'text-green-600', 'border-green-600');
                    timeSpanEl.textContent = steps[step].at;
                } else {
                    iconEl.innerHTML = `<i class="fas ${steps[step].icon} text-green-600"></i>`;
                    timeSpanEl.textContent = '';
                }
            });
        }

        // Menangani permintaan pembaruan status pesanan
        async function handleStatusUpdate() {
            const updateButton = document.getElementById('updateStatusButton');
            const orderId = updateButton.getAttribute('data-order-id');
            const newStatus = updateButton.getAttribute('data-next-status');
            if (!orderId || !newStatus) return;

            const buttonText = document.getElementById('updateStatusButtonText');
            const buttonSpinner = document.getElementById('updateStatusButtonSpinner');
            buttonText.classList.add('hidden');
            buttonSpinner.classList.remove('hidden');
            updateButton.disabled = true;

            try {
                const response = await fetch(`/kurir/pesanan/${orderId}/update-status`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': getCsrfToken() },
                    body: JSON.stringify({ new_status: newStatus })
                });
                const result = await response.json();
                if (!response.ok) throw new Error(result.message || 'Gagal memperbarui status.');
                
                dispatchToast(result.message, 'success');
                openStatusStepperModal(orderId); // Muat ulang data modal
                updateTableRowStatus(orderId, result.order.status); // Perbarui baris di tabel utama
            } catch (error) {
                dispatchToast(`Gagal: ${error.message}`, 'error');
            } finally {
                buttonText.classList.remove('hidden');
                buttonSpinner.classList.add('hidden');
            }
        }

        // Memperbarui status pada baris tabel di halaman utama
        function updateTableRowStatus(orderId, newStatus) {
            const rows = document.querySelectorAll(`[data-order-id="${orderId}"]`);
            const statusText = STATUS_LABEL_MAP[newStatus] || newStatus.replace(/_/g, ' ');
            let newClasses = 'bg-gray-100 text-gray-800';
            switch (newStatus) {
                case 'diambil': newClasses = 'bg-blue-100 text-blue-800'; break;
                case 'diantar': newClasses = 'bg-yellow-100 text-yellow-800'; break;
                case 'diterima_pembeli': newClasses = 'bg-purple-100 text-purple-800'; break;
                case 'menunggu_retur': newClasses = 'bg-red-100 text-red-800'; break;
                case 'menunggu_verifikasi_admin': newClasses = 'bg-orange-100 text-orange-800'; break;
                case 'selesai': newClasses = 'bg-green-100 text-green-800'; break;
            }
            rows.forEach(row => {
                const statusSpan = row.querySelector('.status-badge');
                if (statusSpan) {
                    statusSpan.textContent = statusText;
                    statusSpan.className = `status-badge ${statusSpan.className.split(' ').slice(0, 4).join(' ')} ${newClasses}`;
                }
            });
        }
        
        // Membuka dan mengisi modal untuk permintaan retur produk
        function openReturnProductModal(order) {
            const returnModalLoader = document.getElementById('returnModalLoader');
            const returnModalContent = document.getElementById('returnModalContent');
            const desktopContainer = document.getElementById('return-product-list-desktop');
            const mobileContainer = document.getElementById('return-product-list-mobile');

            returnModalContent.classList.add('hidden');
            returnModalLoader.classList.remove('hidden');
            document.getElementById('returnOrderId').value = order.id;
            desktopContainer.innerHTML = '';
            mobileContainer.innerHTML = '';

            if (!order.products || order.products.length === 0) {
                const noProductHTML = '<p class="py-4 text-center text-gray-500">Tidak ada produk untuk diretur.</p>';
                desktopContainer.innerHTML = `<tr><td colspan="4">${noProductHTML}</td></tr>`;
                mobileContainer.innerHTML = noProductHTML;
            } else {
                order.products.forEach((product, index) => {
                    const productId = product.product_id || product.id;
                    const variantId = product.variant_id ?? 0;
                    const returnKey = `${productId}-${variantId}`;
                    const productImage = product.image_url || 'https://placehold.co/64x64/E2E8F0/64748B?text=No+Img';
                    const desktopRowHTML = `<tr data-return-key="${returnKey}"><td class="px-4 py-4">${index + 1}</td><td class="px-2 py-4"><div class="flex items-center"><div class="flex-shrink-0 w-16 h-16"><img class="object-cover w-16 h-16 rounded-md" src="${productImage}" alt="${product.name}"></div><div class="ml-4"><div class="font-medium">${product.name}</div>${product.variant_name ? `<div class="text-xs text-gray-400">${product.variant_name}</div>` : ''}<div class="text-sm text-gray-500">Jumlah Awal: ${product.quantity}</div></div></div></td><td class="py-4"><div class="flex items-center justify-center gap-2"><button type="button" class="px-2 transition rounded quantity-minus hover:bg-gray-300 dark:hover:bg-gray-700">–</button><span data-name="return_qty[${returnKey}]" data-max="${product.quantity}" class="px-2 bg-gray-200 rounded quantity-input dark:bg-gray-700">0</span><button type="button" class="px-2 transition rounded quantity-plus hover:bg-gray-300 dark:hover:bg-gray-700">+</button></div></td><td class="px-4 py-4 text-center"><button type="button" class="text-red-600 remove-product hover:text-red-900">🗑</button></td></tr>`;
                    const mobileCardHTML = `<div class="flex items-start gap-4 px-4 py-2 border-b dark:border-gray-700" data-return-key="${returnKey}"><div class="flex-shrink-0 w-24 h-24"><img class="object-cover w-24 h-24 rounded-md" src="${productImage}" alt="${product.name}"></div><div class="flex flex-col flex-1"><div class="flex items-center justify-between mb-1"><p class="font-bold">${product.name}</p><button type="button" class="text-red-600 remove-product">🗑</button></div>${product.variant_name ? `<p class="mb-1 text-xs text-gray-500">${product.variant_name}</p>` : ''}<p class="text-sm">Jumlah Awal: ${product.quantity}</p><div class="flex items-center justify-between mt-3"><div class="flex items-center gap-2"><button type="button" class="px-2 rounded quantity-minus">–</button><span data-name="return_qty[${returnKey}]" data-max="${product.quantity}" class="px-2 bg-gray-200 rounded quantity-input dark:bg-gray-700">0</span><button type="button" class="px-2 rounded quantity-plus">+</button></div></div></div></div>`;
                    desktopContainer.insertAdjacentHTML('beforeend', desktopRowHTML);
                    mobileContainer.insertAdjacentHTML('beforeend', mobileCardHTML);
                });
            }
            returnModalLoader.classList.add('hidden');
            returnModalContent.classList.remove('hidden');
            document.getElementById('returnProductForm').onsubmit = (e) => { e.preventDefault(); handleReturnRequestSubmit(order.id); };
        }

        // Menangani pengiriman formulir permintaan retur
        async function handleReturnRequestSubmit(orderId) {
            const form = document.getElementById('returnProductForm');
            const submitButton = document.getElementById('submitReturnRequestButton');
            const buttonText = document.getElementById('submitReturnRequestButtonText');
            const buttonSpinner = document.getElementById('submitReturnRequestButtonSpinner');

            submitButton.disabled = true;
            buttonText.classList.add('hidden');
            buttonSpinner.classList.remove('hidden');

            const returnQuantities = {};
            let hasValidReturn = false;
            form.querySelectorAll('.quantity-input').forEach(span => {
                const key = span.dataset.name.match(/\[(.*?)\]/)[1];
                const quantity = parseInt(span.textContent, 10);
                if (!isNaN(quantity) && quantity > 0) {
                    returnQuantities[key] = quantity;
                    hasValidReturn = true;
                }
            });

            if (!hasValidReturn) {
                dispatchToast('Anda harus memasukkan jumlah retur minimal 1.', 'error');
                submitButton.disabled = false;
                buttonText.classList.remove('hidden');
                buttonSpinner.classList.add('hidden');
                return;
            }

            try {
                const response = await fetch(`/kurir/pesanan/${orderId}/request-return`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': getCsrfToken() },
                    body: JSON.stringify({ return_quantities: returnQuantities })
                });
                const result = await response.json();
                if (!response.ok) {
                    throw new Error(result.message || 'Gagal mengajukan retur.');
                }
                dispatchToast(result.message, 'success');
                closeModal(document.getElementById('returnProductModal'));
                updateTableRowStatus(orderId, result.order.status);
                fetchOrderDetails(orderId);
            } catch (error) {
                dispatchToast(`Gagal: ${error.message}`, 'error');
            } finally {
                submitButton.disabled = false;
                buttonText.classList.remove('hidden');
                buttonSpinner.classList.add('hidden');
            }
        }
    </script>

    <script src="/assets/argon/js/plugins/chartjs.min.js"></script>
    <script src="/assets/argon/js/plugins/perfect-scrollbar.min.js" async></script>
    <script src="/assets-argon-dashboard-tailwind.js?v=1.0.1" async></script>

@endsection

