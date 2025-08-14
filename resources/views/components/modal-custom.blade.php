@props([
    'id',
    'title',
    'toggle',
    'size' => '2xl' // Ukuran default adalah '2xl'
])

@php
    // Menentukan kelas Tailwind CSS berdasarkan prop 'size'
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
    ][$size] ?? 'max-w-2xl'; // Default fallback
@endphp

{{--
  Modal component that can be reused.
  It includes styling for the modal panel, header, and body.
  The size is now dynamic based on the 'size' prop.
--}}
<div id="{{ $id }}" tabindex="-1" aria-hidden="true"
    class="fixed inset-0 top-0 left-0 right-0 z-50 flex items-center justify-center hidden w-full h-full overflow-x-hidden overflow-y-auto transition-opacity duration-300">

    {{-- Kelas max-w-* sekarang dinamis berdasarkan prop $size --}}
    <div class="relative w-full p-4 transition-transform duration-300 ease-out transform scale-95 {{ $maxWidthClass }}">
        {{-- Modal content --}}
        <div class="relative bg-white rounded-lg shadow dark:bg-gray-700">
            {{-- Modal header --}}
            <div class="flex items-center justify-between p-4 border-b border-gray-200 rounded-t md:p-5 dark:border-gray-600 bg-gray">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    {{ $title }}
                </h3>
                <button type="button"
                    class="inline-flex items-center justify-center w-8 h-8 text-sm text-gray-400 bg-transparent rounded-lg hover:bg-gray-200 hover:text-gray-900 ms-auto dark:hover:bg-gray-600 dark:hover:text-white"
                    data-modal-toggle="{{ $toggle }}">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                    </svg>
                    <span class="sr-only">Close modal</span>
                </button>
            </div>

            {{-- Modal body --}}
            <div class="p-4 md:p-5">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
