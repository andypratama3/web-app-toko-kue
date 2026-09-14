@extends('layouts.argon')
@section('title', 'Broadcast WhatsApp')
@section('page_title', 'Broadcast WA')

@section('content')
<div class="relative min-h-[715px] bg-white shadow-md dark:bg-gray-800 sm:rounded-lg">
    {{-- Header --}}
    <div class="flex flex-col p-4 space-y-3 md:flex-row md:items-center md:justify-between md:space-y-0 md:space-x-4">
        <form class="flex flex-col w-full gap-3 md:flex-row md:items-center md:justify-between md:gap-4" method="GET">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Riwayat Broadcast</h2>
            <div class="flex items-center space-x-3">
                <select name="status" onchange="this.form.submit()"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">Semua Status</option>
                    <option value="queued" {{ request('status') === 'queued' ? 'selected' : '' }}>Antrian</option>
                    <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>Diproses</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Selesai</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Gagal</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                </select>
                <form method="POST" action="{{ route('admin.broadcast.sync') }}"
                    onsubmit="return confirm('Sinkronkan template dari Meta sekarang?')">
                    @csrf
                    <button type="submit"
                        class="inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300">
                        <i class="mr-1 fas fa-sync-alt"></i> Sinkronkan Template
                    </button>
                </form>
                <a href="{{ route('admin.broadcast.create') }}"
                    class="inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 focus:ring-4 focus:outline-none focus:ring-green-300">
                    <i class="mr-1 fas fa-paper-plane"></i> Broadcast Baru
                </a>
            </div>
        </form>
    </div>

    @if(session('success'))
        <div class="mx-4 mb-3 p-3 text-sm text-green-800 bg-green-100 rounded-lg dark:bg-green-900 dark:text-green-200">
            {{ session('success') }}
        </div>
    @endif
    @if(session('warning'))
        <div class="mx-4 mb-3 p-3 text-sm text-yellow-800 bg-yellow-100 rounded-lg dark:bg-yellow-900 dark:text-yellow-200">
            {{ session('warning') }}
        </div>
    @endif
    @if($errors->has('template_sync'))
        <div class="mx-4 mb-3 p-3 text-sm text-red-800 bg-red-100 rounded-lg dark:bg-red-900 dark:text-red-200">
            {{ $errors->first('template_sync') }}
        </div>
    @endif

    {{-- Table --}}
    <div class="overflow-x-auto min-h-[400px]">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-4 py-3">Judul</th>
                    <th scope="col" class="px-4 py-3">Template</th>
                    <th scope="col" class="px-4 py-3">Cabang</th>
                    <th scope="col" class="px-4 py-3">Status</th>
                    <th scope="col" class="px-4 py-3">Penerima</th>
                    <th scope="col" class="px-4 py-3">Terkirim</th>
                    <th scope="col" class="px-4 py-3">Dibuat</th>
                    <th scope="col" class="px-4 py-3 text-center"><span class="sr-only">Aksi</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse($broadcasts as $broadcast)
                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                        {{ $broadcast->title ?? 'Tanpa judul' }}
                        <div class="text-xs font-normal text-gray-400">{{ $broadcast->statusLabel() }}</div>
                    </td>
                    <td class="px-4 py-3">
                        <span class="text-xs bg-purple-100 text-purple-800 px-2 py-0.5 rounded dark:bg-purple-900 dark:text-purple-300">
                            {{ $broadcast->template->name ?? '-' }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        @if($broadcast->region)
                            {{ $broadcast->region->name }}
                        @else
                            <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded dark:bg-gray-700 dark:text-gray-300">Semua</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @php
                            $statusColors = [
                                'draft' => 'gray',
                                'queued' => 'yellow',
                                'processing' => 'blue',
                                'completed' => 'green',
                                'failed' => 'red',
                                'cancelled' => 'gray',
                            ];
                            $color = $statusColors[$broadcast->status] ?? 'gray';
                        @endphp
                        <span class="text-xs bg-{{ $color }}-100 text-{{ $color }}-800 px-2 py-0.5 rounded dark:bg-{{ $color }}-900 dark:text-{{ $color }}-300">
                            {{ $broadcast->statusLabel() }}
                        </span>
                    </td>
                    <td class="px-4 py-3">{{ $broadcast->recipient_count }}</td>
                    <td class="px-4 py-3">
                        {{ $broadcast->sent_count }}
                        @if($broadcast->failed_count)
                            <span class="text-xs text-red-500">({{ $broadcast->failed_count }} gagal)</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400">
                        {{ $broadcast->created_at->format('d M Y, H:i') }}
                    </td>
                    <td class="px-4 py-3 text-center whitespace-nowrap">
                        <a href="{{ route('admin.broadcast.show', $broadcast->id) }}"
                            class="font-medium text-blue-600 dark:text-blue-500 hover:underline">
                            Detail
                        </a>
                        @if(in_array($broadcast->status, ['queued', 'processing']))
                            <form method="POST" action="{{ route('admin.broadcast.cancel', $broadcast->id) }}" class="inline"
                                onsubmit="return confirm('Batalkan broadcast ini? Penerima yang belum terkirim akan dihentikan.')">
                                @csrf
                                <button type="submit" class="ml-2 font-medium text-red-600 dark:text-red-500 hover:underline">Batalkan</button>
                            </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                        Belum ada broadcast. Klik <strong>Broadcast Baru</strong> untuk membuat.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="p-4">
        {{ $broadcasts->links() }}
    </div>
</div>
@endsection