@extends('layouts.argon')
@section('title', 'Peforma Customer')
@section('page_title', 'Peforma Customer')

@section('content')

<div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 w-full">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-4 gap-4">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-2 md:mb-0">Peforma Customer</h2>
        <form method="GET" class="flex flex-row flex-wrap gap-2 items-center">
            <div class="relative">
                <select name="month" class="appearance-none border rounded px-4 py-1 text-sm focus:ring focus:ring-blue-200 pr-8">
                    @foreach($months as $num => $name)
                    <option value="{{ $num }}" @if($selectedMonth==$num) selected @endif>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="relative">
                <select name="year" class="appearance-none border rounded px-4 py-1 text-sm focus:ring focus:ring-blue-200 pr-8">
                    @foreach($years as $year)
                    <option value="{{ $year }}" @if($selectedYear==$year) selected @endif>{{ $year }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm font-semibold">Lihat</button>
            <a href="{{ route('admin.peforma-customer.export.pdf', ['month' => $selectedMonth, 'year' => $selectedYear]) }}" target="_blank" class="bg-orange-500 hover:bg-orange-600 text-white px-3 py-1 rounded text-sm font-semibold flex items-center"><i class="fas fa-file-export mr-1"></i> Export</a>
    </div>
    

    <div x-data="{ show: true }" x-show="show" class="mb-4">
        <div class="bg-green-100 border text-black dark:bg-slate-400 dark:text-white px-4 py-3 rounded relative flex items-center justify-between" role="alert">
            <span class="ml-2">Ranking hanya menampilkan customer kategori <b>Reseller</b>. Setiap pelanggan akan mendapatkan "Skor Performa" yang dihitung dari kombinasi "Skor Pembelian" dan "Skor Retur".
            </span>
            <button type="button" @click="show = false" class="ml-4 text-blue-700 hover:text-blue-900 focus:outline-none">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>

    <div class="mb-4 text-gray-600 dark:text-gray-300 font-semibold">
        Ranking Peforma Customer Bulan {{ $bulan ?? '' }}
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 table-fixed">
            <thead class="bg-gray-100 dark:bg-gray-700">
                <tr>
                    <th class="w-24 px-4 py-2 text-left text-xs font-bold text-gray-700 dark:text-gray-200 uppercase">Rank</th>
                    <th class="px-4 py-2 text-center text-xs font-bold text-gray-700 dark:text-gray-200 uppercase">Nama Customer</th>
                    <th class="px-4 py-2 text-center text-xs font-bold text-gray-700 dark:text-gray-200 uppercase">Total Pembelian</th>
                    <th class="px-4 py-2 text-center text-xs font-bold text-gray-700 dark:text-gray-200 uppercase">Total Retur</th>
                    <th class="px-4 py-2 text-center text-xs font-bold text-gray-700 dark:text-gray-200 uppercase">Skor Akhir</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-200">
                @forelse($ranking as $row)
                <tr>
                    <td class="px-4 py-2 font-bold text-left">
                        @if($row->peringkat == 1)
                        🥇
                        @elseif($row->peringkat == 2)
                        🥈
                        @elseif($row->peringkat == 3)
                        🥉
                        @else
                        {{ $row->peringkat }}
                        @endif
                    </td>
                    <td class="px-4 py-2 text-center">{{ $row->nama_customer }}</td>
                    <td class="px-4 py-2 text-center">{{ number_format($row->total_pembelian, 0, ',', '.') }}</td>
                    <td class="px-4 py-2 text-center">{{ number_format($row->total_retur, 0, ',', '.') }}</td>
                    <td class="px-4 py-2 text-center">{{ $row->skor_akhir }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">Belum ada data peforma customer bulan ini.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection