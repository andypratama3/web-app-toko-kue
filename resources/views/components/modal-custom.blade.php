@props([
    'id',
    'title',
    'size' => '2xl'
])

@php
    $maxWidthClass = [
        'sm' => 'max-w-sm',
        'md' => 'max-w-md',
        'lg' => 'max-w-lg',
        'xl' => 'max-w-xl',
        '2xl' => 'max-w-2xl',
        '3xl' => 'max-w-3xl',
        '4xl' => 'max-w-4xl',
        '5xl' => 'max-w-5xl',
        '6xl' => 'max-w-6xl',
        '7xl' => 'max-w-7xl',
    ][$size] ?? 'max-w-2xl';
@endphp

{{-- Div utama modal, memiliki ID dan role="dialog" untuk JS kustom --}}
<div id="{{ $id }}" tabindex="-1" aria-hidden="true" role="dialog"
    class="fixed inset-0 top-0 left-0 right-0 z-50 flex items-center justify-center hidden w-full h-full overflow-x-hidden overflow-y-auto bg-gray-900 bg-opacity-50">

    <div class="relative w-full p-4 {{ $maxWidthClass }}">
        {{-- Konten Modal --}}
        <div class="relative bg-white rounded-lg shadow dark:bg-gray-700">
            {{-- Header Modal --}}
            <div class="flex items-center justify-between p-4 border-b border-gray-200 rounded-t md:p-5 dark:border-gray-600">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    {{ $title }}
                </h3>
                {{-- Tombol Tutup Modal --}}
                <button type="button"
                    class="inline-flex items-center justify-center w-8 h-8 text-sm text-gray-400 bg-transparent rounded-lg js-close-modal-btn hover:bg-gray-200 hover:text-gray-900 ms-auto dark:hover:bg-gray-600 dark:hover:text-white">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                    </svg>
                    <span class="sr-only">Close modal</span>
                </button>
            </div>
            {{-- Body Modal --}}
            <div class="p-4 md:p-5">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
