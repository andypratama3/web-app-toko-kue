@extends('layouts.argon')
@section('title', 'Monitor Chat WhatsApp')
@section('page_title', 'Chat WA')

@section('content')
@php
    $stateBadges = [
        'ESCALATED_TO_HUMAN' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
        'AWAITING_PAYMENT_PROOF' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
        'ORDER_CONFIRMED' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
        'INIT' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
    ];
@endphp

<div class="relative bg-white shadow-md dark:bg-gray-800 sm:rounded-lg">
    @if(session('success'))
        <div role="status"
            class="m-4 mb-0 px-4 py-3 text-sm text-green-800 bg-green-100 border border-green-200 rounded-lg dark:bg-green-900 dark:text-green-300 dark:border-green-700">
            {{ session('success') }}
        </div>
    @endif

    {{-- Filter --}}
    <div class="p-4">
        <form method="GET" class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="relative w-full sm:max-w-xs lg:max-w-md">
                <label for="search" class="sr-only">Cari percakapan</label>
                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                    <svg aria-hidden="true" class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="currentColor"
                        viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                            clip-rule="evenodd" />
                    </svg>
                </div>
                <input type="text" id="search" name="search" value="{{ request('search') }}"
                    class="block w-full h-11 p-2 pl-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                    placeholder="Cari nomor HP atau nama...">
            </div>
            <div class="flex items-center gap-3 w-full sm:w-auto">
                <select name="region" onchange="this.form.submit()" aria-label="Filter cabang"
                    class="w-full sm:w-auto h-11 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:ring-blue-500 block p-2 pr-8 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="all" {{ request('region') === 'all' ? 'selected' : '' }}>
                        Semua Cabang
                    </option>
                    @foreach($regions as $regionOption)
                        <option value="{{ $regionOption->id }}"
                            {{ (string) $regionOption->id === (string) $regionId ? 'selected' : '' }}>
                            {{ $regionOption->name }}
                        </option>
                    @endforeach
                </select>
                <select name="status" onchange="this.form.submit()"
                    class="w-full sm:w-auto h-11 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:ring-blue-500 block p-2 pr-8 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">Semua Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                    <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>Ditutup</option>
                </select>
                <button type="submit"
                    class="h-11 px-4 text-sm font-semibold text-white rounded-lg bg-greenlight hover:bg-greendark sm:hidden">
                    Cari
                </button>
            </div>
        </form>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 gap-3 p-4 pt-0 sm:gap-4 md:grid-cols-4" id="stats-container">
        <div class="min-w-0 p-3 border rounded-lg sm:p-4 dark:border-gray-600">
            <div class="text-xs break-words sm:text-sm text-gray-500 dark:text-gray-400">Total Percakapan</div>
            <div class="text-xl break-words sm:text-2xl font-bold text-gray-900 dark:text-white" id="stat-total">-</div>
        </div>
        <div class="min-w-0 p-3 border rounded-lg sm:p-4 dark:border-gray-600">
            <div class="text-xs break-words sm:text-sm text-gray-500 dark:text-gray-400">Percakapan Aktif</div>
            <div class="text-xl break-words sm:text-2xl font-bold text-green-600" id="stat-active">-</div>
        </div>
        <div class="min-w-0 p-3 border rounded-lg sm:p-4 dark:border-gray-600">
            <div class="text-xs break-words sm:text-sm text-gray-500 dark:text-gray-400">Pesan Hari Ini</div>
            <div class="text-xl break-words sm:text-2xl font-bold text-blue-600" id="stat-messages">-</div>
        </div>
        <div class="min-w-0 p-3 border rounded-lg sm:p-4 dark:border-gray-600">
            <div class="text-xs break-words sm:text-sm text-gray-500 dark:text-gray-400">Escalated</div>
            <div class="text-xl break-words sm:text-2xl font-bold text-red-600" id="stat-escalated">-</div>
        </div>
    </div>

    {{-- Conversations: mobile card list (md:hidden) --}}
    <div class="space-y-3 p-3 pt-0 md:hidden">
        @forelse($conversations as $conversation)
            @php
                $stateBadge = $stateBadges[$conversation->current_state]
                    ?? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300';
                $lastMessage = $conversation->latestMessage->content ?? '-';
            @endphp
            <article class="p-3 border border-gray-200 rounded-lg dark:border-gray-700">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="font-semibold text-gray-900 break-all dark:text-white">
                            {{ $conversation->phone_number }}
                        </p>
                        <p class="text-sm truncate text-gray-500 dark:text-gray-400">
                            {{ $conversation->profile_name ?? 'Tanpa nama' }}
                        </p>
                    </div>
                    @if($conversation->status === 'active')
                        <span
                            class="inline-flex shrink-0 items-center text-xs font-medium text-green-600 dark:text-green-400">
                            <span class="w-2 h-2 mr-1 bg-green-500 rounded-full animate-pulse"></span>
                            Aktif
                        </span>
                    @else
                        <span class="shrink-0 text-xs text-gray-400">Ditutup</span>
                    @endif
                </div>

                <div class="flex flex-wrap items-center gap-2 mt-2">
                    @if($conversation->customer)
                        <span
                            class="inline-block max-w-full text-xs break-words bg-blue-100 text-blue-800 px-2 py-0.5 rounded dark:bg-blue-900 dark:text-blue-300">
                            {{ $conversation->customer->name }}
                        </span>
                    @else
                        <span class="text-xs text-gray-400">Belum terdaftar</span>
                    @endif
                    <span
                        class="inline-block max-w-full px-2 py-0.5 text-xs break-words rounded {{ $stateBadge }}">
                        {{ $conversation->current_state }}
                    </span>
                </div>

                <p class="mt-2 text-sm break-words text-gray-500 line-clamp-2 dark:text-gray-400" title="{{ $lastMessage }}">
                    {{ $lastMessage }}
                </p>

                <form method="POST" action="{{ route('admin.chat.region', $conversation) }}"
                    class="mt-3">
                    @csrf
                    @method('PATCH')
                    <label class="sr-only" for="region-{{ $conversation->id }}">Atur cabang</label>
                    <div class="flex items-center gap-1">
                        <select id="region-{{ $conversation->id }}" name="region_id" onchange="this.form.submit()"
                            class="h-10 px-2 pr-7 text-xs bg-gray-50 border border-gray-300 rounded-lg text-gray-900 focus:ring-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            @foreach($regions as $regionOption)
                                <option value="{{ $regionOption->id }}"
                                    {{ (int) $regionOption->id === (int) $conversation->region_id ? 'selected' : '' }}>
                                    {{ $regionOption->name }}
                                </option>
                            @endforeach
                        </select>
                        <noscript>
                            <button type="submit"
                                class="h-10 px-3 text-xs font-semibold text-white rounded-lg bg-greenlight hover:bg-greendark">
                                Simpan
                            </button>
                        </noscript>
                    </div>
                </form>

                <div class="flex flex-wrap items-center justify-between gap-2 mt-3">
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $conversation->last_message_at ? $conversation->last_message_at->diffForHumans() : '-' }}
                    </span>
                    <a href="{{ route('admin.chat.show', $conversation->id) }}"
                        class="inline-flex items-center justify-center w-full min-h-[44px] px-4 text-sm font-medium text-white rounded-lg sm:w-auto bg-blue-600 hover:bg-blue-700 focus:outline-none focus-visible:ring focus-visible:ring-blue-300 dark:focus-visible:ring-blue-800">
                        Lihat Chat
                    </a>
                </div>
            </article>
        @empty
            <p class="py-8 text-sm text-center text-gray-500 dark:text-gray-400">
                Belum ada percakapan WhatsApp.
            </p>
        @endforelse
    </div>

    {{-- Conversations: desktop table (hidden md:block) --}}
    {{-- Catatan: class "hidden" TIDAK dipakai di wrapper tabel ini. argon-dashboard-tailwind.css
         dimuat setelah app.css dan juga mendefinisikan .hidden{display:none} tanpa media query,
         sehingga menimpa .md\:block dan membuat tabel tidak terlihat di desktop.
         Penyembunyian di layar kecil ditulis eksplisit lewat .chat-table-wrap di bawah. --}}
    <style>
        @media (max-width: 767px) {
            .chat-table-wrap {
                display: none;
            }
        }
    </style>
    <div class="chat-table-wrap overflow-x-auto">
        <table class="w-full min-w-[880px] text-sm text-left text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-4 py-3 whitespace-nowrap">No. WhatsApp</th>
                    <th scope="col" class="px-4 py-3">Nama</th>
                    <th scope="col" class="px-4 py-3">Customer</th>
                    <th scope="col" class="px-4 py-3">Cabang</th>
                    <th scope="col" class="px-4 py-3">State</th>
                    <th scope="col" class="px-4 py-3">Status</th>
                    <th scope="col" class="px-4 py-3">Pesan Terakhir</th>
                    <th scope="col" class="px-4 py-3 whitespace-nowrap">Waktu</th>
                    <th scope="col" class="px-4 py-3 text-center"><span class="sr-only">Aksi</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse($conversations as $conversation)
                    @php
                        $stateBadge = $stateBadges[$conversation->current_state]
                            ?? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300';
                        $lastMessage = $conversation->latestMessage->content ?? '-';
                    @endphp
                    <tr
                        class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                        <td class="px-4 py-3 font-medium whitespace-nowrap text-gray-900 dark:text-white">
                            {{ $conversation->phone_number }}
                        </td>
                        <td class="px-4 py-3">{{ $conversation->profile_name ?? '-' }}</td>
                        <td class="px-4 py-3">
                            @if($conversation->customer)
                                <span
                                    class="inline-block max-w-[180px] text-xs break-words bg-blue-100 text-blue-800 px-2 py-0.5 rounded dark:bg-blue-900 dark:text-blue-300">
                                    {{ $conversation->customer->name }}
                                </span>
                            @else
                                <span class="text-xs text-gray-400">Belum terdaftar</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <form method="POST" action="{{ route('admin.chat.region', $conversation) }}"
                                class="flex items-center gap-1">
                                @csrf
                                @method('PATCH')
                                <select name="region_id" onchange="this.form.submit()" aria-label="Atur cabang"
                                    class="h-9 px-2 pr-7 text-xs bg-gray-50 border border-gray-300 rounded-lg text-gray-900 focus:ring-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    @foreach($regions as $regionOption)
                                        <option value="{{ $regionOption->id }}"
                                            {{ (int) $regionOption->id === (int) $conversation->region_id ? 'selected' : '' }}>
                                            {{ $regionOption->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <noscript>
                                    <button type="submit"
                                        class="h-9 px-2 text-xs font-semibold text-white rounded-lg bg-greenlight hover:bg-greendark">
                                        Simpan
                                    </button>
                                </noscript>
                            </form>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-block max-w-[160px] px-2 py-0.5 text-xs break-words rounded {{ $stateBadge }}">
                                {{ $conversation->current_state }}
                            </span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            @if($conversation->status === 'active')
                                <span class="inline-flex items-center">
                                    <span class="w-2 h-2 mr-1 bg-green-500 rounded-full animate-pulse"></span>
                                    Aktif
                                </span>
                            @else
                                <span class="text-gray-400">Ditutup</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 max-w-[240px] truncate" title="{{ $lastMessage }}">
                            {{ $lastMessage }}
                        </td>
                        <td class="px-4 py-3 text-xs whitespace-nowrap text-gray-500 dark:text-gray-400">
                            {{ $conversation->last_message_at ? $conversation->last_message_at->diffForHumans() : '-' }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <a href="{{ route('admin.chat.show', $conversation->id) }}"
                                class="inline-flex items-center justify-center min-h-[40px] px-2 font-medium text-blue-600 dark:text-blue-500 hover:underline focus:outline-none focus-visible:ring focus-visible:ring-blue-300 dark:focus-visible:ring-blue-800">
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
    <div class="p-4 overflow-x-auto">
        <div class="flex justify-center min-w-max">
            {{ $conversations->withQueryString()->links() }}
        </div>
    </div>
</div>

@php
    $statsRegion = request('region') ?? ($regionId !== null ? (string) $regionId : 'all');
@endphp
<script>
    // Stats ikut filter cabang yang sedang dipilih agar angkanya cocok dengan daftar
    fetch(@json(route('admin.chat.stats', ['region' => $statsRegion])))
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
