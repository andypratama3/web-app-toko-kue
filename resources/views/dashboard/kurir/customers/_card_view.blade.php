@forelse ($customers as $customer)
    <div
        class="w-full max-w-sm p-4 bg-white border border-gray-200 rounded-lg shadow-md dark:bg-gray-800 dark:border-gray-700">
        {{-- <div class="flex items-center justify-between mb-2">
            <h4 class="text-lg font-semibold text-gray-900 dark:text-white">👤 {{ $customer->name }}</h4>
        </div> --}}
        <div class="flex items-center justify-between mb-2">
            <h4 class="flex items-center text-lg font-semibold text-gray-900 dark:text-white">
                <span>{{ $customer->name }}</span>
                {{-- Jika customer ditandai, tampilkan ikon --}}
                @if ($customer->is_flagged)
                    <i class="ml-2 text-red-500 fas fa-flag" title="Customer ditandai"></i>
                @endif
            </h4>
        </div>
        <p class="mb-1 text-sm text-gray-700 dark:text-gray-300">
            📍 {{ $customer->address }} ({{ $customer->region->name }})
        </p>
        @if ($customer->note)
            <p class="mb-1 text-xs italic text-gray-700 dark:text-gray-300">
                📌 {{ $customer->note }}
            </p>
        @endif
        <div class="flex justify-end pt-2 mb-0 space-x-3 border-t border-gray-200 dark:border-gray-700">
            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $customer->phone) }}" target="_blank"
                class="text-lg text-green-600 transition transform dark:text-green-500 hover:scale-125 active:scale-90">
                <i class="fab fa-whatsapp"></i>
            </a>
            <button type="button" data-modal-toggle="note-modal-{{ $customer->id }}"
                class="text-lg text-yellow-600 transition transform dark:text-yellow-500 hover:scale-125 active:scale-90">
                📋
            </button>
            <button type="button" data-modal-toggle="edit-modal-{{ $customer->id }}"
                class="text-lg text-blue-600 transition transform dark:text-blue-500 hover:scale-125 active:scale-90">
                ✍🏻
            </button>

            <button type="button" data-modal-toggle="delete-modal-{{ $customer->id }}"
                class="text-lg text-red-600 transition transform dark:text-red-500 hover:scale-125 active:scale-90">
                <i class="fas fa-trash"></i>
            </button>
        </div>

    </div>
@empty
    <div class="text-center text-gray-500 dark:text-gray-400">
        Tidak ada data customer ditemukan.
    </div>
@endforelse
