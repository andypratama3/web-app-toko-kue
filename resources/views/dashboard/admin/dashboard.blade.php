@extends('layouts.argon')
@section('title', 'Admin Dashboard')
@section('page_title', 'Admin')

@section('content')

    @php
    use Illuminate\Support\Facades\DB;
    $user = Auth::user();
    $lastSession = DB::table('sessions')
    ->where('user_id', Auth::id())
    ->orderByDesc('last_activity')
    ->first();
    $lastLogin = $lastSession ?
    \Carbon\Carbon::createFromTimestamp($lastSession->last_activity)->setTimezone('Asia/Jakarta')->format('d M Y,
    H:i:s') : 'Tidak tersedia';

        // Mengatur zona waktu ke Asia/Jakarta (atau zona waktu server Anda)
    date_default_timezone_set('Asia/Jakarta');
    $hour = date('G'); // Mendapatkan jam dalam format 24-jam (0-23)
    $greeting = '';
    $icon = '';

    if ($hour >= 5 && $hour < 11) {
      $greeting = 'Selamat Pagi';
      $icon = '☀️'; // Ikon matahari pagi
    } elseif ($hour >= 11 && $hour < 15) {
      $greeting = 'Selamat Siang';
      $icon = '🏙️'; // Ikon kota di siang hari
    } elseif ($hour >= 15 && $hour < 18) {
      $greeting = 'Selamat Sore';
      $icon = '🌇'; // Ikon matahari terbenam
    } else {
      $greeting = 'Selamat Malam';
      $icon = '🌙'; // Ikon bulan
    }
    @endphp

<div class="bg-green-100 dark:bg-green-800 p-6 rounded-lg shadow">
  <div>
    <h2 class="text-2xl font-bold text-gray-800 dark:text-white">
      Hai, {{ $greeting }} <span class="text-violet-600 dark:text-violet-400">{{ $user->name ?? 'User' }}</span>! {{ $icon }}
    </h2>
    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
      Senang melihat Anda kembali. Berikut ringkasan aktivitas hari ini.
    </p>
  </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 p-6">

    <div class="relative flex flex-col min-w-0 break-words bg-white shadow-xl dark:bg-slate-850 dark:shadow-dark-xl rounded-2xl bg-clip-border">
        <div class="flex-auto p-4">
            <div class="flex flex-row -mx-3">
                <div class="flex-none w-2/3 max-w-full px-3">
                    <div>
                        <p class="mb-0 font-sans text-sm font-semibold leading-normal uppercase dark:text-white dark:opacity-60">
                            INCOME</p>
                        <h5 class="mb-2 font-bold dark:text-white">IDR 5.000.000</h5>
                        <p class="mb-0 dark:text-white dark:opacity-60">
                            <span class="text-sm font-bold leading-normal text-emerald-500">+55%</span>
                            since yesterday
                        </p>
                    </div>
                </div>
                <div class="px-3 text-right basis-1/3">
                    <div class="inline-block w-12 h-12 text-center rounded-circle bg-gradient-to-tl from-blue-500 to-violet-500">
                        <i class="fas fa-dollar-sign leading-none text-lg relative top-3.5 text-white"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="relative flex flex-col min-w-0 break-words bg-white shadow-xl dark:bg-slate-850 dark:shadow-dark-xl rounded-2xl bg-clip-border">
        <div class="flex-auto p-4">
            <div class="flex flex-row -mx-3">
                <div class="flex-none w-2/3 max-w-full px-3">
                    <div>
                        <p class="mb-0 font-sans text-sm font-semibold leading-normal uppercase dark:text-white dark:opacity-60">
                            Total Sales</p>
                        <h5 class="mb-2 font-bold dark:text-white">120</h5>
                        <p class="mb-0 dark:text-white dark:opacity-60">
                            <span class="text-sm font-bold leading-normal text-emerald-500">+5%</span>
                            than last month
                        </p>
                    </div>
                </div>
                <div class="px-3 text-right basis-1/3">
                    <div class="inline-block w-12 h-12 text-center rounded-circle bg-gradient-to-tl from-orange-500 to-yellow-500">
                        <i class="fas fa-chart-line leading-none text-lg relative top-3.5 text-white"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="relative flex flex-col min-w-0 break-words bg-white shadow-xl dark:bg-slate-850 dark:shadow-dark-xl rounded-2xl bg-clip-border">
        <div class="flex-auto p-4">
            <div class="flex flex-row -mx-3">
                <div class="flex-none w-2/3 max-w-full px-3">
                    <div>
                        <p class="mb-0 font-sans text-sm font-semibold leading-normal uppercase dark:text-white dark:opacity-60">
                            Customer {{ $user->region->name ?? 'Tidak ada region' }}</p>
                        <h5 class="mb-2 font-bold dark:text-white">2,300</h5>
                        <p class="mb-0 dark:text-white dark:opacity-60">
                            <span class="text-sm font-bold leading-normal text-emerald-500">+3%</span>
                            since last week
                        </p>
                    </div>
                </div>
                <div class="px-3 text-right basis-1/3">
                    <div class="inline-block w-12 h-12 text-center rounded-circle bg-gradient-to-tl from-red-600 to-orange-600">
                        <i class="fas fa-users leading-none text-lg relative top-3.5 text-white"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="relative flex flex-col min-w-0 break-words bg-white shadow-xl dark:bg-slate-850 dark:shadow-dark-xl rounded-2xl bg-clip-border">
        <div class="flex-auto p-4">
            <div class="flex flex-row -mx-3">
                <div class="flex-none w-2/3 max-w-full px-3">
                    <div>
                        <p class="mb-0 font-sans text-sm font-semibold leading-normal uppercase dark:text-white dark:opacity-60">
                            New Customer</p>
                        <h5 class="mb-2 font-bold dark:text-white">+3,462</h5>
                        <p class="mb-0 dark:text-white dark:opacity-60">
                            <span class="text-sm font-bold leading-normal text-red-600">-2%</span>
                            since last quarter
                        </p>
                    </div>
                </div>
                <div class="px-3 text-right basis-1/3">
                    <div class="inline-block w-12 h-12 text-center rounded-circle bg-gradient-to-tl from-emerald-500 to-teal-400">
                        <i class="fas fa-user-plus leading-none text-lg relative top-3.5 text-white"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div>
        <h2 class="mt-3 text-xl font-semibold text-gray-900 dark:text-white sm:text-2xl">📊 Grafik</h2>
    </div>
    <div class="md:col-span-2 xl:col-span-4 w-full bg-white rounded-lg shadow-xl dark:bg-gray-800 p-4 md:p-6">
        <div class="flex justify-between mb-5">

            <div>
                <h5 class="leading-none text-3xl font-bold text-gray-900 dark:text-white pb-2">$12,423</h5>
                <p class="text-base font-normal text-gray-500 dark:text-gray-400">Sales this week</p>
            </div>
            <div class="flex items-center px-2.5 py-0.5 text-base font-semibold text-green-500 dark:text-green-500 text-center">
                23%
                <svg class="w-3 h-3 ms-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 14">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13V1m0 0L1 5m4-4 4 4" />
                </svg>
            </div>
        </div>
        <div id="legend-chart"></div>
        <div class="grid grid-cols-1 items-center border-gray-200 border-t dark:border-gray-700 justify-between mt-5">
            <div class="flex justify-between items-center pt-5">
                <button id="dropdownDefaultButton" data-dropdown-toggle="lastDaysdropdown" data-dropdown-placement="bottom" class="text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-900 text-center inline-flex items-center dark:hover:text-white" type="button">
                    Last 7 days
                    <svg class="w-2.5 m-2.5 ms-1.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4" />
                    </svg>
                </button>
                <div id="lastDaysdropdown" class="z-10 hidden bg-white divide-y divide-gray-100 rounded-lg shadow-sm w-44 dark:bg-gray-700">
                    <ul class="py-2 text-sm text-gray-700 dark:text-gray-200" aria-labelledby="dropdownDefaultButton">
                        <li>
                            <a href="#" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">Yesterday</a>
                        </li>
                        <li>
                            <a href="#" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">Today</a>
                        </li>
                        <li>
                            <a href="#" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">Last 7 days</a>
                        </li>
                        <li>
                            <a href="#" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">Last 30 days</a>
                        </li>
                        <li>
                            <a href="#" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">Last 90 days</a>
                        </li>
                    </ul>
                </div>
                <a href="#" class="uppercase text-sm font-semibold inline-flex items-center rounded-lg text-blue-600 hover:text-blue-700 dark:hover:text-blue-500 hover:bg-gray-100 dark:hover:bg-gray-700 dark:focus:ring-gray-700 dark:border-gray-700 px-3 py-2">
                    Sales Report
                    <svg class="w-2.5 h-2.5 ms-1.5 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4" />
                    </svg>
                </a>
            </div>
        </div>
    </div>

</div>


<div class="w-full p-6">
    <div class="w-full overflow-hidden bg-white rounded-2xl shadow-xl dark:bg-slate-850">
        
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
            📦 Monitoring Kurir di Region {{ Auth::user()->region->name }}
            </h3>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm text-left text-gray-500 dark:text-gray-400">
                
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-6 py-3">
                            Nama Kurir
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Email
                        </th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($couriers as $kurir)
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                        <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                            {{ $kurir->name }}
                        </th>
                        <td class="px-6 py-4">
                            {{ $kurir->email }}
                        </td>
                    </tr>
                    @empty
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                        <td colspan="2" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                            Tidak ada data kurir di region ini.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>


{{-- Dynamic Product Section --}}
{{-- <div class="max-w-screen-xl px-4 mx-auto 2xl:px-0"> --}}
<div class="items-end justify-between mb-4 space-y-4 sm:flex sm:space-y-0 md:mb-8">
    <div>
        <h2 class="mt-3 text-xl font-semibold text-gray-900 dark:text-white sm:text-2xl">Data Produk</h2>
    </div>
    {{-- <div class="flex items-center space-x-4">
            <a href="{{ route('admin.products.create') }}" class="flex items-center justify-center w-full px-3 py-2
    text-sm font-medium text-white bg-green-700 rounded-lg hover:bg-green-800 focus:ring-4 focus:ring-green-300
    sm:w-auto dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-800">
    <svg class="w-5 h-5 mr-2 -ml-1" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
        <path fill-rule="evenodd"
            d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z"
            clip-rule="evenodd"></path>
    </svg>
    Tambah Produk
    </a>
</div> --}}
</div>

{{-- Product Grid --}}
<div class="grid gap-4 mb-4 sm:grid-cols-2 md:mb-8 lg:grid-cols-3 xl:grid-cols-4">
    @forelse ($products as $product)
    <div
        class="overflow-hidden transition-all duration-300 bg-white shadow-lg rounded-2xl hover:shadow-xl hover:-translate-y-1">
        <div class="relative overflow-hidden">
            <img src="{{ asset($product->image) }}" alt="{{ $product->name }}" loading="lazy"
                class="object-cover w-full h-48 transition-transform duration-300 hover:scale-105 cursor-zoom-in zoomable">
        </div>
        <div class="p-5">
            <h3 class="font-bold text-lg text-[#2C3E50] mb-2">{{ $product->name }}</h3>
            {{-- Deskripsi dengan toggle --}}
            <div x-data="{ open: false }">
                <p class="mb-3 text-sm text-gray-600" :class="open ? '' : 'line-clamp-2'">
                    {{ $product->description }}
                </p>
                {{-- Tombol hanya muncul jika teksnya panjang --}}
                @if (strlen($product->description) > 100)
                {{-- Sesuaikan panjang karakter jika perlu --}}
                <button @click="open = !open"
                    class="mb-2 text-xs font-semibold text-blue-600 focus:outline-none hover:underline">
                    <span x-show="!open">Selengkapnya</span>
                    <span x-show="open">Tutup</span>
                </button>
                @endif
            </div>
            <div class="flex items-center justify-between">
                <span class="text-[#8BA870] font-bold text-lg">Rp
                    {{ number_format($product->price, 0, ',', '.') }}</span>
                <button
                    class="bg-[#8BA870] text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-[#7a965e] transition">
                    Detail
                </button>
            </div>
        </div>
    </div>
    @empty
    <div class="p-6 text-center text-gray-500 col-span-full dark:text-gray-400">
        <p>Belum ada produk yang ditambahkan.</p>
    </div>
    @endforelse
</div>
<div class="w-full text-center">
    {{ $products->links() }}
</div>
{{-- </div> --}}
@endsection
