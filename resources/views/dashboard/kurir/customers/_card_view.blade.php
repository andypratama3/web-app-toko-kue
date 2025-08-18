@forelse ($customers as $customer)
    <div class="w-full max-w-sm p-4 bg-white border border-gray-200 rounded-lg shadow-md dark:bg-gray-800 dark:border-gray-700">
        <div class="flex items-center justify-between mb-2">
            <h4 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $customer->name }}</h4>
        </div>
        <p class="mb-1 text-sm text-gray-700 dark:text-gray-300">
            <i class="fa-solid fa-location-dot me-2"></i> {{ $customer->address }}
            ({{ $customer->region->name }})
        </p>
        @if ($customer->note)
            <p class="mb-1 text-xs italic text-gray-700 dark:text-gray-300">
                <i class="fa-solid fa-clipboard-list me-2"></i> {{ $customer->note }}
            </p>
        @endif
        <div class="flex justify-end pt-2 mt-2 space-x-3 border-t border-gray-200 dark:border-gray-700">
            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $customer->phone) }}" target="_blank"
                class="text-green-600 dark:text-green-500 hover:underline">
                <i class="text-lg fa-brands fa-whatsapp"></i>
            </a>
            <button type="button" data-modal-toggle="edit-modal-{{ $customer->id }}"
                class="text-blue-600 dark:text-blue-500 hover:underline">
                <i class="text-lg fa-solid fa-pen-to-square"></i>
            </button>
            <button type="button" data-modal-toggle="note-modal-{{ $customer->id }}"
                class="text-yellow-600 dark:text-yellow-500 hover:underline">
                <i class="text-lg fa-solid fa-clipboard"></i>
            </button>
            <button type="button" data-modal-toggle="delete-modal-{{ $customer->id }}"
                class="text-red-600 dark:text-red-500 hover:underline">
                <i class="text-lg fa-solid fa-trash"></i>
            </button>
        </div>
    </div>
@empty
    <div class="col-span-full text-center text-gray-500 dark:text-gray-400">
        Tidak ada data customer yang cocok.
    </div>
@endforelse
