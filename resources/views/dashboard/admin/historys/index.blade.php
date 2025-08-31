@extends('layouts.argon')
@section('title', 'History Pesanan')
@section('page_title', 'History')

@section('content')
    <div class="flex-auto p-3 pt-0 -mx-3">
        <div class="p-2.5 bg-white shadow-md rounded-xl dark:bg-gray-800 dark:border-gray-700 min-h-[715px]">
            <h2 class="mb-6 text-black text-md dark:text-white">
                👤 {{ Auth::user()->name ?? 'Admin' }}
                🚩 {{ Auth::user()->region->name ?? 'N/A' }}
            </h2>
            <div class="overflow-x-auto">
                <table class="items-center w-full mb-0 align-top border-gray-200 text-slate-500">
                    <thead class="align-bottom">
                        <tr
                            class="text-xs font-bold text-left text-gray-500 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                            <th class="px-4 py-3">No</th>
                            <th class="px-4 py-3">Invoice</th>
                            <th class="px-4 py-3">Customer</th>
                            <th class="px-4 py-3">Kurir</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Total</th>
                            <th scope="col" class="px-4 py-3 text-center"><span class="sr-only">Aksi</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($orders as $order)
                            <tr class="text-sm font-normal text-gray-700 border-b dark:text-gray-400 dark:border-gray-700">
                                <td class="px-4 py-2">{{ $loop->iteration }}</td>
                                <td class="px-4 py-2 font-mono">{{ $order->invoice_number }}</td>
                                <td class="px-4 py-2">{{ $order->customer->name ?? '-' }}</td>
                                <td class="px-4 py-2">{{ $order->createdBy->name ?? '-' }}</td>
                                <td class="px-4 py-2">
                                    <span
                                        class="inline-block px-2 py-1 text-xs font-semibold text-green-800 bg-green-100 rounded">
                                        {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                                    </span>
                                </td>
                                <td class="px-4 py-2">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right">
                                    <div class="relative inline-block">
                                        {{-- Tombol Dropdown Aksi --}}
                                        <button data-target-dropdown="order-actions-dropdown-{{ $order->id }}"
                                            class="px-2 py-1 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg js-dropdown-toggle hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:ring-2 focus:ring-gray-100 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        {{-- Konten Dropdown --}}
                                        <div id="order-actions-dropdown-{{ $order->id }}"
                                            class="absolute right-0 z-50 hidden mt-2 bg-white divide-y divide-gray-100 rounded shadow js-dropdown-menu w-44 dark:bg-gray-700 dark:divide-gray-600">
                                            <ul class="py-1 text-sm text-gray-700 dark:text-gray-200">
                                                {{-- Tombol Direct WA --}}
                                                <li>
                                                    @php
                                                        $wa_number = $order->customer->phone ?? $order->phone ?? null;
                                                        if ($wa_number) {
                                                            $wa_number = preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $wa_number));
                                                        }
                                                        $customer_name = $order->customer->name ?? '-';
                                                        $wa_message = "Yth. Bapak/Ibu *{$customer_name}*,\n\n" .
                                                            "Kami mengonfirmasi bahwa pesanan Anda telah selesai.\n\n" .
                                                            "Sebagai referensi, transaksi ini tercatat dengan nomor invoice berikut: *{$order->invoice_number}*.\n\n" .
                                                            "Terimakasih sudah berbelanja di Toko Kami.\n\n" .
                                                            "Hormat kami.\n*Admin Kue Pandan Asli*";
                                                        $wa_message = urlencode($wa_message);
                                                    @endphp
                                                    @if($wa_number)
                                                        <a href="https://wa.me/{{ $wa_number }}?text={{ $wa_message }}" target="_blank" rel="noopener"
                                                            class="flex items-center w-full px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-600">
                                                            <span class="inline-block w-6 mr-2 text-center"><i class="fab fa-whatsapp text-green-500"></i></span>
                                                            <span class="text-green-500">Kirim</span>
                                                        </a>
                                                    @else
                                                        <span class="flex items-center w-full px-4 py-2 text-left text-gray-400 cursor-not-allowed">
                                                            <span class="inline-block w-6 mr-2 text-center"><i class="fab fa-whatsapp"></i></span>
                                                            <span>No WA</span>
                                                        </span>
                                                    @endif
                                                </li>
                                                {{-- Tombol Download --}}
                                                <li>
                                                    <a href="{{ route('admin.historys.download', $order->id) }}" class="flex items-center w-full px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-600">
                                                        <span class="inline-block w-6 mr-2 text-center"><i class="fas fa-download"></i></span>
                                                        <span>Download</span>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a href="{{ route('admin.historys.invoice', $order->id) }}" target="_blank" rel="noopener"
                                                        class="flex items-center w-full px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-600">
                                                        <span class="inline-block w-6 mr-2 text-center"><i class="fas fa-file-invoice"></i></span>
                                                        <span>Lihat Invoice</span>
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-6 text-center text-gray-500">Tidak ada pesanan history.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
