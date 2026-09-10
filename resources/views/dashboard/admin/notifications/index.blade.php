@extends('layouts.argon')
@section('title', 'Notifikasi Admin')
@section('page_title', 'Notifikasi')

@section('content')
<div class="relative min-h-[500px] bg-white shadow-md dark:bg-gray-800 sm:rounded-lg">
    {{-- Header --}}
    <div class="flex flex-col p-4 space-y-3 md:flex-row md:items-center md:justify-between md:space-y-0 md:space-x-4">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Notifikasi</h2>
        <div class="flex items-center space-x-3">
            <form action="{{ route('admin.notifications.read-all') }}" method="POST">
                @csrf
                <button type="submit"
                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                    Tandai semua dibaca
                </button>
            </form>
        </div>
    </div>

    {{-- List --}}
    <div class="p-4 pt-0">
        @if($notifications->isEmpty())
            <div class="py-12 text-center text-gray-500 dark:text-gray-400">
                Tidak ada notifikasi.
            </div>
        @else
            <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($notifications as $notification)
                    <li class="py-3 {{ $notification->is_read ? 'opacity-60' : '' }}">
                        <div class="flex items-start justify-between space-x-3">
                            <div class="min-w-0">
                                <div class="flex items-center space-x-2">
                                    @unless($notification->is_read)
                                        <span class="w-2 h-2 bg-red-500 rounded-full"></span>
                                    @endunless
                                    <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ $notification->title }}
                                    </span>
                                </div>
                                <div class="mt-1 text-sm text-gray-600 dark:text-gray-300 whitespace-pre-line">
                                    {{ $notification->message }}
                                </div>
                                <div class="mt-1 text-xs text-gray-400">
                                    {{ $notification->created_at->format('d M Y H:i') }}
                                    @if($notification->order)
                                        · <a class="text-blue-600 dark:text-blue-400 hover:underline"
                                            href="{{ route('admin.historys.index') }}">#{{ $notification->order->invoice_number }}</a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
            <div class="p-4 pt-2">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</div>
@endsection