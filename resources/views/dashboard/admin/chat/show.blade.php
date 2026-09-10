@extends('layouts.argon')
@section('title', 'Chat - ' . $conversation->phone_number)
@section('page_title', 'Chat WhatsApp')

@section('content')
<div class="flex h-[calc(100vh-200px)] bg-white shadow-md dark:bg-gray-800 sm:rounded-lg overflow-hidden">
    {{-- Chat Area --}}
    <div class="flex flex-col flex-1">
        {{-- Chat Header --}}
        <div class="flex items-center justify-between p-4 border-b dark:border-gray-700">
            <div class="flex items-center space-x-3">
                <a href="{{ route('admin.chat.index') }}" class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                    </svg>
                </a>
                <div>
                    <h3 class="font-semibold text-gray-900 dark:text-white">
                        {{ $conversation->profile_name ?? $conversation->phone_number }}
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $conversation->phone_number }} |
                        <span class="inline-flex items-center">
                            @if($conversation->status === 'active')
                                <span class="w-1.5 h-1.5 mr-1 bg-green-500 rounded-full"></span>
                                Aktif
                            @else
                                Ditutup
                            @endif
                        </span>
                    </p>
                </div>
            </div>
            <div class="flex items-center space-x-2">
                <span class="text-xs px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded">
                    State: <strong>{{ $conversation->current_state }}</strong>
                </span>
                @if($conversation->customer)
                    <span class="text-xs px-2 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-300 rounded">
                        Customer: {{ $conversation->customer->name }}
                    </span>
                @endif

                <div class="flex items-center space-x-1">
                    @if($conversation->status === 'active' && $conversation->current_state !== 'ESCALATED_TO_HUMAN')
                        <form action="{{ route('admin.chat.escalate', $conversation->id) }}" method="POST">
                            @csrf
                            <button type="submit" title="Serahkan ke admin"
                                class="px-2 py-1 text-xs text-amber-700 bg-amber-100 rounded hover:bg-amber-200 dark:bg-amber-900 dark:text-amber-200">Escalate</button>
                        </form>
                    @endif
                    @if($conversation->current_state === 'ESCALATED_TO_HUMAN')
                        <form action="{{ route('admin.chat.resume', $conversation->id) }}" method="POST">
                            @csrf
                            <button type="submit" title="Kembalikan ke bot"
                                class="px-2 py-1 text-xs text-blue-700 bg-blue-100 rounded hover:bg-blue-200 dark:bg-blue-900 dark:text-blue-200">Bot</button>
                        </form>
                    @endif
                    @if($conversation->status === 'active')
                        <form action="{{ route('admin.chat.close', $conversation->id) }}" method="POST">
                            @csrf
                            <button type="submit" title="Tutup percakapan"
                                class="px-2 py-1 text-xs text-red-700 bg-red-100 rounded hover:bg-red-200 dark:bg-red-900 dark:text-red-200">Tutup</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        {{-- Messages --}}
        <div id="chat-messages" class="flex-1 overflow-y-auto p-4 space-y-4 bg-gray-50 dark:bg-gray-900">
            @foreach($conversation->messages as $message)
                @php $outgoing = in_array($message->sender_type, ['bot', 'admin']); @endphp
                <div class="flex {{ $outgoing ? 'justify-end' : 'justify-start' }}">
                    <div class="max-w-[70%] {{ $outgoing
                        ? 'bg-blue-600 text-white rounded-l-xl rounded-tr-xl'
                        : 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white border dark:border-gray-600 rounded-r-xl rounded-tl-xl'
                    }} px-4 py-2 shadow-sm">
                        <div class="text-sm">{{ $message->content }}</div>
                        <div class="text-xs {{ $outgoing ? 'text-blue-200' : 'text-gray-400' }} mt-1 text-right">
                            {{ $message->created_at->format('H:i') }}
                            @if($outgoing)
                                @if($message->status === 'read')
                                    <span class="ml-1">✓✓</span>
                                @elseif($message->status === 'delivered')
                                    <span class="ml-1">✓✓</span>
                                @else
                                    <span class="ml-1">✓</span>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Context Info --}}
        @if($conversation->context)
        <div class="p-3 border-t bg-yellow-50 dark:bg-yellow-900/20 dark:border-gray-700">
            <div class="text-xs font-semibold text-yellow-800 dark:text-yellow-300 mb-1">Draft Order Context:</div>
            <pre class="text-xs text-yellow-700 dark:text-yellow-400 whitespace-pre-wrap max-h-20 overflow-y-auto">{{ json_encode($conversation->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </div>
        @endif

        {{-- Reply Form --}}
        <form action="{{ route('admin.chat.reply', $conversation->id) }}" method="POST" class="p-4 border-t dark:border-gray-700 bg-white dark:bg-gray-800">
            @csrf
            @if($errors->any())
                <div class="mb-3 text-sm text-red-600 dark:text-red-400">{{ $errors->first() }}</div>
            @endif
            <div class="flex items-center space-x-2">
                <input type="text" name="message" required maxlength="4096" autocomplete="off"
                    placeholder="Tulis balasan ke customer..."
                    class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-blue-500 focus:ring-blue-500">
                <button type="submit"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg">
                    Kirim
                </button>
            </div>
        </form>
    </div>

    {{-- Sidebar Info --}}
    <div class="w-64 border-l dark:border-gray-700 p-4 overflow-y-auto hidden lg:block">
        <h4 class="font-semibold text-gray-900 dark:text-white mb-4">Info Percakapan</h4>

        <div class="space-y-3 text-sm">
            <div>
                <span class="text-gray-500 dark:text-gray-400">No. WhatsApp</span>
                <p class="font-medium text-gray-900 dark:text-white">{{ $conversation->phone_number }}</p>
            </div>
            <div>
                <span class="text-gray-500 dark:text-gray-400">Nama Profil</span>
                <p class="font-medium text-gray-900 dark:text-white">{{ $conversation->profile_name ?? '-' }}</p>
            </div>
            <div>
                <span class="text-gray-500 dark:text-gray-400">Region</span>
                <p class="font-medium text-gray-900 dark:text-white">{{ $conversation->region->name ?? '-' }}</p>
            </div>
            <div>
                <span class="text-gray-500 dark:text-gray-400">State</span>
                <p class="font-medium text-gray-900 dark:text-white">{{ $conversation->current_state }}</p>
            </div>
            <div>
                <span class="text-gray-500 dark:text-gray-400">Total Pesan</span>
                <p class="font-medium text-gray-900 dark:text-white">{{ $conversation->message_count }}</p>
            </div>
            <div>
                <span class="text-gray-500 dark:text-gray-400">Terakhir Aktif</span>
                <p class="font-medium text-gray-900 dark:text-white">
                    {{ $conversation->last_message_at ? $conversation->last_message_at->format('d M Y H:i') : '-' }}
                </p>
            </div>

            @if($conversation->customer)
            <hr class="dark:border-gray-700">
            <div>
                <span class="text-gray-500 dark:text-gray-400">Customer</span>
                <p class="font-medium text-gray-900 dark:text-white">{{ $conversation->customer->name }}</p>
            </div>
            <div>
                <span class="text-gray-500 dark:text-gray-400">Telepon</span>
                <p class="font-medium text-gray-900 dark:text-white">{{ $conversation->customer->phone }}</p>
            </div>
            <div>
                <span class="text-gray-500 dark:text-gray-400">Kategori</span>
                <p class="font-medium text-gray-900 dark:text-white">
                    {{ $conversation->customer->category->name ?? '-' }}
                </p>
            </div>
            @endif
        </div>
    </div>
</div>

<script>
    // Auto-scroll to bottom
    const chatContainer = document.getElementById('chat-messages');
    chatContainer.scrollTop = chatContainer.scrollHeight;
</script>
@endsection
