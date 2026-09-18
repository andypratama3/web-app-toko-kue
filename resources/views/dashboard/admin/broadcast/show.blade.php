@extends('layouts.argon')
@section('title', 'Detail Broadcast')
@section('page_title', 'Detail Broadcast')

@section('content')
<div class="min-h-[715px] bg-white shadow-md dark:bg-gray-800 sm:rounded-lg">
    <div class="p-6">
        <div class="flex flex-col gap-3 mb-6 md:flex-row md:items-start md:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">{{ $broadcast->title ?? 'Broadcast tanpa judul' }}</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Dibuat oleh {{ $broadcast->creator->name ?? 'Sistem' }} pada {{ $broadcast->created_at->format('d M Y, H:i') }}
                    @if($broadcast->region) &middot; Cabang {{ $broadcast->region->name }} @else &middot; Semua Cabang @endif
                </p>
            </div>
            <div class="flex items-center gap-2">
                @if(in_array($broadcast->status, ['queued', 'processing']))
                    <div class="flex items-center gap-2 px-3 py-1.5 text-sm text-blue-700 bg-blue-100 rounded-lg dark:bg-blue-900 dark:text-blue-200">
                        <span class="w-2.5 h-2.5 bg-blue-500 rounded-full animate-pulse"></span>
                        {{ $broadcast->statusLabel() }}
                    </div>
                @else
                    <span class="px-3 py-1.5 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg dark:bg-gray-200 dark:text-white">
                        {{ $broadcast->statusLabel() }}
                    </span>
                @endif
            </div>
        </div>

        {{-- Ringkasan --}}
        <div class="grid grid-cols-2 gap-4 mb-6 md:grid-cols-4" id="broadcast-stats">
            <div class="p-4 border rounded-lg dark:border-gray-600">
                <div class="text-sm text-gray-500 dark:text-gray-400">Total Penerima</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white" id="stat-total">{{ $broadcast->recipient_count }}</div>
            </div>
            <div class="p-4 border rounded-lg dark:border-gray-600">
                <div class="text-sm text-gray-500 dark:text-gray-400">Terkirim</div>
                <div class="text-2xl font-bold text-green-600" id="stat-sent">{{ $broadcast->sent_count }}</div>
            </div>
            <div class="p-4 border rounded-lg dark:border-gray-600">
                <div class="text-sm text-gray-500 dark:text-gray-400">Gagal</div>
                <div class="text-2xl font-bold text-red-600" id="stat-failed">{{ $broadcast->failed_count }}</div>
            </div>
            <div class="p-4 border rounded-lg dark:border-gray-600">
                <div class="text-sm text-gray-500 dark:text-gray-400">Sisa</div>
                <div class="text-2xl font-bold text-yellow-600" id="stat-pending">
                    {{ $broadcast->recipient_count - $broadcast->sent_count - $broadcast->failed_count }}
                </div>
            </div>
        </div>

        {{-- Progress bar --}}
        <div class="mb-6">
            <div class="flex justify-between mb-1 text-xs text-gray-500 dark:text-gray-400">
                <span>Progres Pengiriman</span>
                <span id="progress-label">
                    {{ $broadcast->recipient_count > 0 ? round((($broadcast->sent_count + $broadcast->failed_count) / $broadcast->recipient_count) * 100) : 0 }}%
                </span>
            </div>
            <div class="w-full h-2.5 bg-gray-200 rounded-full dark:bg-gray-700">
                <div class="h-2.5 bg-green-500 rounded-full transition-all duration-500" id="progress-bar"
                    style="width: {{ $broadcast->recipient_count > 0 ? round((($broadcast->sent_count + $broadcast->failed_count) / $broadcast->recipient_count) * 100) : 0 }}%">
                </div>
            </div>
        </div>

        <div class="mb-6">
            <a href="{{ route('admin.broadcast.index') }}" class="text-sm font-medium text-blue-600 dark:text-blue-500 hover:underline">
                &larr; Kembali ke daftar broadcast
            </a>
        </div>

        {{-- Pratinjau pesan --}}
        <div class="mb-6 p-4 border rounded-lg dark:border-gray-600">
            <h3 class="mb-2 text-sm font-semibold text-gray-900 dark:text-white">Pratinjau Pesan</h3>

            @if($broadcast->header_media_url)
                <div class="mb-3">
                    @if($broadcast->header_media_type === 'video')
                        <video controls playsinline preload="metadata"
                            class="max-h-56 rounded-lg border border-gray-200 dark:border-gray-600">
                            <source src="{{ $broadcast->header_media_url }}" type="video/mp4">
                            Browser tidak mendukung video.
                        </video>
                    @else
                        <img src="{{ $broadcast->header_media_url }}" alt="Media header broadcast"
                            class="max-h-56 object-cover rounded-lg border border-gray-200 dark:border-gray-600"
                            onerror="this.style.display='none'">
                    @endif
                    <div class="mt-1 text-xs text-gray-400">
                        Media header: <span class="font-medium text-gray-500 dark:text-gray-300">{{ strtoupper($broadcast->header_media_type) }}</span>
                        <span class="break-all">{{ $broadcast->header_media_url }}</span>
                    </div>
                </div>
            @endif

            <div class="p-3 text-sm bg-gray-50 border border-gray-200 rounded-lg whitespace-pre-wrap dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                {{ $broadcast->body_preview ?? $broadcast->template->body_text ?? '-' }}
            </div>
            <div class="mt-2 text-xs text-gray-400">
                Template: <strong>{{ $broadcast->template->name ?? '-' }}</strong>
                @if($broadcast->template)
                    ({{ \App\Services\WhatsApp\WhatsappMetaService::templateCategoryLabel($broadcast->template->category ?? '') }})
                @endif
            </div>
        </div>

        @if(in_array($broadcast->status, ['queued', 'processing']))
            <form method="POST" action="{{ route('admin.broadcast.cancel', $broadcast->id) }}"
                onsubmit="return confirm('Batalkan broadcast ini?')" class="mb-6">
                @csrf
                <button type="submit"
                    class="inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">
                    <i class="mr-1 fas fa-ban"></i> Batalkan Broadcast
                </button>
            </form>
        @endif

        {{-- Daftar penerima --}}
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-4 py-3">Nama</th>
                        <th scope="col" class="px-4 py-3">No. WhatsApp</th>
                        <th scope="col" class="px-4 py-3">Status</th>
                        <th scope="col" class="px-4 py-3">Pesan</th>
                        <th scope="col" class="px-4 py-3">Terkirim</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recipients as $recipient)
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $recipient->name ?? '-' }}</td>
                        <td class="px-4 py-3">{{ $recipient->phone }}</td>
                        <td class="px-4 py-3">
                            @if($recipient->status === 'sent')
                                <span class="text-xs bg-green-100 text-green-800 px-2 py-0.5 rounded dark:bg-green-900 dark:text-green-300">Terkirim</span>
                            @elseif($recipient->status === 'failed')
                                <span class="text-xs bg-red-100 text-red-800 px-2 py-0.5 rounded dark:bg-red-900 dark:text-red-300">Gagal</span>
                            @else
                                <span class="text-xs bg-yellow-100 text-yellow-800 px-2 py-0.5 rounded dark:bg-yellow-900 dark:text-yellow-300">Menunggu</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 max-w-[220px] truncate" title="{{ $recipient->error ?? '' }}">
                            {{ $recipient->error ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400">
                            {{ $recipient->sent_at ? $recipient->sent_at->format('d M Y, H:i:s') : '-' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">Belum ada penerima.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4">
            {{ $recipients->links() }}
        </div>
    </div>
</div>

@if(in_array($broadcast->status, ['queued', 'processing']))
<script>
    // Auto-refresh progress selama broadcast masih berjalan
    (function poll() {
        const url = new URL(window.location.href);
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(res => res.text())
            .then(html => {
                const doc = new DOMParser().parseFromString(html, 'text/html');
                const set = (id) => {
                    const el = doc.getElementById(id);
                    if (el && document.getElementById(id)) {
                        document.getElementById(id).textContent = el.textContent;
                    }
                };
                set('stat-sent');
                set('stat-failed');
                set('stat-pending');
                set('progress-label');
                const bar = doc.getElementById('progress-bar');
                if (bar && document.getElementById('progress-bar')) {
                    document.getElementById('progress-bar').style.width = bar.style.width;
                }
            })
            .catch(() => {})
            .finally(() => {
                setTimeout(poll, 5000);
            });
    })();
</script>
@endif
@endsection