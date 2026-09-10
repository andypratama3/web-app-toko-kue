@extends('layouts.argon')
@section('title', 'Monitor Chat WhatsApp')
@section('page_title', 'Chat WA')

@section('content')
<div class="relative min-h-[715px] bg-white shadow-md dark:bg-gray-800 sm:rounded-lg">
    {{-- Header --}}
    <div class="flex flex-col p-4 space-y-3 md:flex-row md:items-center md:justify-between md:space-y-0 md:space-x-4">
        <form class="flex flex-col w-full gap-3 md:flex-row md:items-center md:justify-between md:gap-4" method="GET">
            <div class="w-full md:w-1/2">
                <label for="search" class="sr-only">Cari</label>
                <div class="relative w-full">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <svg aria-hidden="true" class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <input type="text" id="search" name="search" value="{{ request('search') }}"
                        class="block w-full p-2 pl-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                        placeholder="Cari nomor HP atau nama...">
                </div>
            </div>
            <div class="flex items-center space-x-3">
                <select name="status" onchange="this.form.submit()"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">Semua Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                    <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>Ditutup</option>
                </select>
            </div>
        </form>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 gap-4 p-4 md:grid-cols-4" id="stats-container">
        <div class="p-4 border rounded-lg dark:border-gray-600">
            <div class="text-sm text-gray-500 dark:text-gray-400">Total Percakapan</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white" id="stat-total">-</div>
        </div>
        <div class="p-4 border rounded-lg dark:border-gray-600">
            <div class="text-sm text-gray-500 dark:text-gray-400">Percakapan Aktif</div>
            <div class="text-2xl font-bold text-green-600" id="stat-active">-</div>
        </div>
        <div class="p-4 border rounded-lg dark:border-gray-600">
            <div class="text-sm text-gray-500 dark:text-gray-400">Pesan Hari Ini</div>
            <div class="text-2xl font-bold text-blue-600" id="stat-messages">-</div>
        </div>
        <div class="p-4 border rounded-lg dark:border-gray-600">
            <div class="text-sm text-gray-500 dark:text-gray-400">Escalated</div>
            <div class="text-2xl font-bold text-red-600" id="stat-escalated">-</div>
        </div>
    </div>

    {{-- Table --}}
    <div class="overflow-visible min-h-[400px]">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-4 py-3">No. WhatsApp</th>
                    <th scope="col" class="px-4 py-3">Nama</th>
                    <th scope="col" class="px-4 py-3">Customer</th>
                    <th scope="col" class="px-4 py-3">State</th>
                    <th scope="col" class="px-4 py-3">Status</th>
                    <th scope="col" class="px-4 py-3">Pesan Terakhir</th>
                    <th scope="col" class="px-4 py-3">Waktu</th>
                    <th scope="col" class="px-4 py-3 text-center"><span class="sr-only">Aksi</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse($conversations as $conversation)
                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                        {{ $conversation->phone_number }}
                    </td>
                    <td class="px-4 py-3">{{ $conversation->profile_name ?? '-' }}</td>
                    <td class="px-4 py-3">
                        @if($conversation->customer)
                            <span class="text-xs bg-blue-100 text-blue-800 px-2 py-0.5 rounded dark:bg-blue-900 dark:text-blue-300">
                                {{ $conversation->customer->name }}
                            </span>
                        @else
                            <span class="text-xs text-gray-400">Belum terdaftar</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @php
                            $stateColors = [
                                'ESCALATED_TO_HUMAN' => 'red',
                                'AWAITING_PAYMENT_PROOF' => 'yellow',
                                'ORDER_CONFIRMED' => 'green',
                                'INIT' => 'gray',
                            ];
                            $color = $stateColors[$conversation->current_state] ?? 'blue';
                        @endphp
                        <span class="text-xs bg-{{ $color }}-100 text-{{ $color }}-800 px-2 py-0.5 rounded dark:bg-{{ $color }}-900 dark:text-{{ $color }}-300">
                            {{ $conversation->current_state }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        @if($conversation->status === 'active')
                            <span class="inline-flex items-center">
                                <span class="w-2 h-2 mr-1 bg-green-500 rounded-full animate-pulse"></span>
                                Aktif
                            </span>
                        @else
                            <span class="text-gray-400">Ditutup</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 max-w-[200px] truncate">
                        {{ $conversation->latestMessage->content ?? '-' }}
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-500">
                        {{ $conversation->last_message_at ? $conversation->last_message_at->diffForHumans() : '-' }}
                    </td>
                    <td class="px-4 py-3 text-center">
                        <a href="{{ route('admin.chat.show', $conversation->id) }}"
                            class="font-medium text-blue-600 dark:text-blue-500 hover:underline">
                            Lihat Chat
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                        Belum ada percakapan WhatsApp.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="p-4">
        {{ $conversations->links() }}
    </div>
</div>

<script>
    // Load stats
    fetch('{{ route('admin.chat.stats') }}')
        .then(r => r.json())
        .then(data => {
            document.getElementById('stat-total').textContent = data.total_conversations;
            document.getElementById('stat-active').textContent = data.active_conversations;
            document.getElementById('stat-messages').textContent = data.messages_today;
            document.getElementById('stat-escalated').textContent = data.escalated;
        })
        .catch(() => {});
</script>
@endsection
