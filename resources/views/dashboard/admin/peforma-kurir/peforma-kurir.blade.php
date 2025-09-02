@extends('layouts.argon')
@section('title', 'Peforma Kurir')
@section('page_title', 'Peforma Kurir')

@section('content')
<div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 w-full">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-4 gap-4">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-2 md:mb-0">Peforma Kurir</h2>
        <form method="GET" class="flex flex-row flex-wrap gap-2 items-center">
            <div class="relative">
                {{-- Kelas 'appearance-none' di bawah ini berfungsi untuk MENGHAPUS panah dropdown bawaan browser. --}}
                <select name="month" class="appearance-none border rounded px-4 py-1 text-sm focus:ring focus:ring-blue-200 pr-8">
                    @foreach($months as $num => $name)
                        <option value="{{ $num }}" @if($selectedMonth == $num) selected @endif>{{ $name }}</option>
                    @endforeach
                </select>
                {{-- Icon dropdown custom dihapus --}}
            </div>

            <div class="relative">
                 {{-- Kelas 'appearance-none' di bawah ini juga MENGHAPUS panah dropdown bawaan browser. --}}
                <select name="year" class="appearance-none border rounded px-4 py-1 text-sm focus:ring focus:ring-blue-200 pr-8">
                    @foreach($years as $year)
                        <option value="{{ $year }}" @if($selectedYear == $year) selected @endif>{{ $year }}</option>
                    @endforeach
                </select>
                {{-- Icon dropdown custom dihapus --}}
            </div>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm font-semibold">Lihat</button>
        </form>
    </div>
    <div class="mb-4 text-gray-600 dark:text-gray-300 font-semibold">
        Ranking Peforma Kurir Bulan {{ $bulan }}
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 table-fixed">
            <thead class="bg-gray-100 dark:bg-gray-700">
                <tr>
                    <th class="w-24 px-4 py-2 text-left text-xs font-bold text-gray-700 dark:text-gray-200 uppercase">Rank</th>
                    <th class="px-4 py-2 text-center text-xs font-bold text-gray-700 dark:text-gray-200 uppercase">Nama Kurir</th>
                    <th class="px-4 py-2 text-center text-xs font-bold text-gray-700 dark:text-gray-200 uppercase">Jumlah Pesanan Selesai</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($ranking as $row)
                    <tr>
                        <td class="px-4 py-2 font-bold text-left">
                            @if($row['rank'] == 1)
                                🥇
                            @elseif($row['rank'] == 2)
                                🥈
                            @elseif($row['rank'] == 3)
                                🥉
                            @else
                                {{ $row['rank'] }}
                            @endif
                        </td>
                        <td class="px-4 py-2 text-center">{{ $row['nama_kurir'] }}</td>
                        <td class="px-4 py-2 text-center">{{ $row['jumlah_order'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">Belum ada data peforma kurir bulan ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

