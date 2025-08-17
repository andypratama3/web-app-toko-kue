@extends('layouts.argon')
@section('title', 'Daftar Pesanan Saya')
@section('page_title', 'Pesanan Kurir')

@section('content')
    <div class="flex-auto p-4">
        <div class="bg-white p-6 rounded-xl shadow-md dark:bg-gray-800 dark:border-gray-700">
            <h2 class="text-xl font-bold mb-6 text-black dark:text-white">
                Daftar Pesanan untuk {{ Auth::user()->name ?? 'Pengguna' }}
                (Region: {{ Auth::user()->region->name ?? 'N/A' }})
            </h2>

            {{-- Menampilkan pesan error jika ada --}}
            @if (isset($error))
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-md mb-4" role="alert">
                    <p class="font-bold">Error:</p>
                    <p>{{ $error }}</p>
                </div>
            @endif

            @if ($orders->isEmpty())
                <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 rounded-md" role="alert">
                    <p class="font-bold">Info:</p>
                    <p>Tidak ada pesanan yang ditemukan untuk Anda di region ini.</p>
                </div>
            @else
                {{-- Tampilan Desktop (Tabel) --}}
                <div class="hidden md:block overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-300">
                                    ID Pesanan
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-300">
                                    Pelanggan
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-300">
                                    Alamat Pengiriman
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-300">
                                    No. Telepon
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-300">
                                    Total Amount
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-300">
                                    Tanggal Pesan
                                </th>
                                {{-- Tambahkan kolom lain jika diperlukan --}}
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                            @foreach ($orders as $order)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $order->order_id }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                        {{ $order->customer_name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                        {{ $order->customer_address_on_order }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                        {{ $order->customer_phone_on_order }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                        Rp {{ number_format($order->total_amount, 2, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                        {{ \Carbon\Carbon::parse($order->created_at)->format('d M Y H:i') }}
                                    </td>
                                    {{-- Tambahkan data lain jika diperlukan --}}
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Tampilan Mobile (Cards) --}}
                <div class="md:hidden space-y-4">
                    @foreach ($orders as $order)
                        <div class="bg-white p-4 rounded-xl shadow-md dark:bg-gray-700">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-xs font-semibold text-gray-600 dark:text-gray-300">ID Pesanan:</span>
                                <span class="text-sm font-bold text-black dark:text-white">{{ $order->order_id }}</span>
                            </div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-xs font-semibold text-gray-600 dark:text-gray-300">Pelanggan:</span>
                                <span class="text-sm text-gray-800 dark:text-gray-200">{{ $order->customer_name }}</span>
                            </div>
                            <div class="mb-2">
                                <span class="text-xs font-semibold text-gray-600 dark:text-gray-300 block">Alamat:</span>
                                <span class="text-sm text-gray-800 dark:text-gray-200">{{ $order->customer_address_on_order }}</span>
                            </div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-xs font-semibold text-gray-600 dark:text-gray-300">Telepon:</span>
                                <span class="text-sm text-gray-800 dark:text-gray-200">{{ $order->customer_phone_on_order }}</span>
                            </div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-xs font-semibold text-gray-600 dark:text-gray-300">Total:</span>
                                <span class="text-md font-bold text-green-600 dark:text-green-400">Rp {{ number_format($order->total_amount, 2, ',', '.') }}</span>
                            </div>
                            <div class="text-right text-xs text-gray-500 dark:text-gray-400">
                                {{ \Carbon\Carbon::parse($order->created_at)->format('d M Y H:i') }}
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection
