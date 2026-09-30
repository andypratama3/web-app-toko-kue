{{--
    SearchBar — input pencarian dengan ikon.
    text-base (16px) mencegah iOS auto-zoom saat fokus.
    max-w-md di desktop, full width di mobile.
--}}
@props([
    'id' => 'live-search-input',
    'placeholder' => 'Cari...',
    'value' => null,
    'wrapperClass' => '',
])

<form class="flex items-center {{ $wrapperClass }}" onsubmit="return false;">
    <label for="{{ $id }}" class="sr-only">{{ $placeholder }}</label>
    <div class="relative w-full">
        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
            <svg aria-hidden="true" class="w-4 h-4 text-gray-500 sm:w-5 sm:h-5 dark:text-gray-400" fill="currentColor"
                viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd"
                    d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                    clip-rule="evenodd" />
            </svg>
        </div>
        <input type="text" id="{{ $id }}" name="search" value="{{ $value }}" autocomplete="off"
            class="block w-full py-2 pl-9 text-base border border-gray-300 rounded-lg bg-gray-50 focus:border-primary-500 focus:ring-primary-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
            placeholder="{{ $placeholder }}">
    </div>
</form>
