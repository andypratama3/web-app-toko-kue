@extends('layouts.argon')
@section('title', 'Daftar Pesanan Saya')
@section('page_title', 'Pesanan Kurir')

@section('content')
    <div class="flex-auto p-4">
        <div class="p-6 bg-white rounded-xl shadow-md dark:bg-gray-800 dark:border-gray-700">
            <h2 class="text-xl font-bold text-black mb-6 dark:text-white">
                Daftar Tugas Pesanan untuk Kurir: {{ Auth::user()->name ?? 'Pengguna' }}
                (Region: {{ Auth::user()->region->name ?? 'N/A' }})
            </h2>

            {{-- Menampilkan pesan error jika ada --}}
            @if (isset($error))
                <div class="p-4 mb-4 text-red-700 bg-red-100 border-l-4 border-red-500 rounded-md" role="alert">
                    <p class="font-bold">Error:</p>
                    <p>{{ $error }}</p>
                </div>
            @endif

            @if ($orders->isEmpty())
                <div class="p-4 text-blue-700 bg-blue-100 border-l-4 border-blue-500 rounded-md" role="alert">
                    <p class="font-bold">Info:</p>
                    <p>Tidak ada pesanan yang ditugaskan untuk Anda saat ini.</p>
                </div>
            @else
                {{-- Tampilan Desktop (Tabel) --}}
                <div class="hidden md:block overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-300">
                                    Nomor Invoice
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-300">
                                    Nama Pelanggan
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-300">
                                    Status
                                </th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-300">
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                            @foreach ($orders as $order)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-6 py-4 text-sm font-mono text-gray-900 whitespace-nowrap dark:text-white">
                                        {{-- Pastikan controller mengirimkan 'invoice_number' --}}
                                        {{ $order->invoice_number}}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap dark:text-gray-300">
                                        {{-- Mengakses nama dari relasi customer --}}
                                        {{ $order->customer->name ?? 'Pelanggan Dihapus' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm whitespace-nowrap">
                                        {{-- NOTE: Anda perlu menambahkan kolom 'status' di tabel 'orders' --}}
                                        {{-- Contoh: 'dikemas', 'diantar', 'selesai' --}}
                                        <span class="px-2.5 py-1 text-xs font-semibold rounded-full
                                            @switch($order->status ?? 'dikemas')
                                                @case('diantar')
                                                    bg-yellow-100 text-yellow-800
                                                    @break
                                                @case('selesai')
                                                    bg-green-100 text-green-800
                                                    @break
                                                @default
                                                    bg-blue-100 text-blue-800
                                            @endswitch
                                        ">
                                            {{ ucfirst($order->status ?? 'Dikemas') }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium text-center whitespace-nowrap">
                                        <div class="flex items-center justify-center space-x-2">
                                            {{-- Ganti '#' dengan route yang sesuai, contoh: route('kurir.orders.editStatus', $order->id) --}}
                                            <a href="#" class="px-3 py-1.5 text-xs font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">Ubah Status</a>
                                            {{-- Ganti '#' dengan route yang sesuai, contoh: route('kurir.orders.show', $order->id) --}}
                                            <a href="#" class="px-3 py-1.5 text-xs font-medium text-gray-900 bg-gray-200 rounded-lg hover:bg-gray-300 transition-colors">Rincian</a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Tampilan Mobile (Cards) --}}
                <div class="space-y-4 md:hidden">
                    @foreach ($orders as $order)
                        <div class="p-4 bg-white rounded-xl shadow-md dark:bg-gray-700">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <p class="text-xs font-semibold text-gray-600 dark:text-gray-300">Invoice</p>
                                    <p class="text-sm font-mono font-bold text-black dark:text-white">{{ $order->invoice_number ?? 'N/A' }}</p>
                                </div>
                                <span class="px-2.5 py-1 text-xs font-semibold rounded-full
                                    @switch($order->status ?? 'dikemas')
                                        @case('diantar')
                                            bg-yellow-100 text-yellow-800
                                            @break
                                        @case('selesai')
                                            bg-green-100 text-green-800
                                            @break
                                        @default
                                            bg-blue-100 text-blue-800
                                    @endswitch
                                ">
                                    {{ ucfirst($order->status ?? 'Dikemas') }}
                                </span>
                            </div>
                            <div class="mb-4">
                                <p class="text-xs font-semibold text-gray-600 dark:text-gray-300">Pelanggan</p>
                                <p class="text-sm text-gray-800 dark:text-gray-200">
                                    {{-- Mengakses nama dari relasi customer --}}
                                    {{ $order->customer->name ?? 'Pelanggan Dihapus' }}
                                </p>
                            </div>
                            <div class="flex pt-3 border-t border-gray-200 justify-end space-x-2 dark:border-gray-600">
                                <a href="#" class="px-3 py-1.5 text-xs font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">Ubah Status</a>
                                <a href="#" class="px-3 py-1.5 text-xs font-medium text-gray-900 bg-gray-200 rounded-lg hover:bg-gray-300 transition-colors">Rincian</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection
