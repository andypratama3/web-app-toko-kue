@extends('layouts.argon')
@section('title', 'History Pesanan')
@section('page_title', 'History')

@section('content')
    <div class="flex-auto p-3 pt-0 -mx-3">
        <div class="p-2.5 bg-white shadow-md rounded-xl dark:bg-gray-800 dark:border-gray-700 min-h-[715px]">
            <h2 class="mb-6 text-black text-md dark:text-white">
                🙍🏻‍♂️ {{ Auth::user()->name ?? 'Kurir' }}
                🚩 {{ Auth::user()->region->name ?? 'N/A' }}
            </h2>
            <div class="overflow-x-auto">
                <table class="items-center w-full mb-0 align-top border-gray-200 text-slate-500">
                    <thead class="align-bottom">
                        <tr
                            class="text-xs font-bold text-left text-gray-500 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                            {{-- Menggunakan struktur header dari admin, tanpa Kurir & Aksi --}}
                            <th class="px-4 py-3">No.</th>
                            <th class="px-4 py-3">Invoice</th>
                            <th class="px-4 py-3">Customer</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($orders as $order)
                            <tr class="border-b dark:border-gray-700">
                                {{-- Penomoran yang benar untuk paginasi --}}
                                <td class="px-4 py-3 font-medium text-center text-gray-900 dark:text-white">
                                    {{ ($orders->currentPage() - 1) * $orders->perPage() + $loop->iteration }}
                                </td>
                                {{-- Tampilan Invoice & Tanggal --}}
                                <td class="px-4 py-2">
                                    <p class="mb-0 font-semibold leading-tight text-xs">{{ $order->invoice_number }}</p>
                                    <p class="mb-0 leading-tight text-xs text-slate-400">
                                        {{ $order->created_at->isoFormat('D MMM YYYY, HH:mm') }}
                                    </p>
                                </td>
                                {{-- Tampilan Customer & No. Telepon --}}
                                <td class="px-4 py-2">
                                    <p class="mb-0 font-semibold leading-tight text-xs">{{ $order->customer->name ?? '-' }}
                                    </p>
                                    <p class="mb-0 leading-tight text-xs text-slate-400">
                                        {{ $order->customer->phone ?? '-' }}</p>
                                </td>
                                {{-- Tampilan Status Lunas & Badge Retur --}}
                                <td class="px-4 py-2">
                                    <span
                                        class="text-xs font-medium px-2.5 py-0.5 rounded {{ $order->payment_status['class'] }}">
                                        {{ $order->payment_status['text'] }}
                                    </span>
                                    @if ($order->has_return)
                                        <span
                                            class="text-xs font-medium px-2.5 py-0.5 rounded bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300">
                                            Retur
                                        </span>
                                    @endif
                                </td>
                                {{-- Tampilan Total dengan logika retur --}}
                                <td class="px-4 py-2">
                                    @if ($order->has_return)
                                        <p
                                            class="mb-0 font-semibold leading-tight text-xs text-green-600 dark:text-green-400">
                                            Rp {{ number_format($order->final_total, 0, ',', '.') }}
                                        </p>
                                        <p class="mb-0 leading-tight text-xs text-slate-400 line-through">
                                            Rp {{ number_format($order->total_amount, 0, ',', '.') }}
                                        </p>
                                    @else
                                        <p class="mb-0 font-semibold leading-tight text-xs">
                                            Rp {{ number_format($order->total_amount, 0, ',', '.') }}
                                        </p>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                {{-- Menyesuaikan colspan karena kolom lebih sedikit --}}
                                <td colspan="5" class="py-6 text-center text-gray-500">Tidak ada pesanan history.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{-- Menambahkan link navigasi halaman --}}
            <div class="p-4">
                {{ $orders->links() }}
            </div>
        </div>
    </div>
@endsection
