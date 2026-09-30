{{--
    Fab — Floating Action Button untuk mobile, inline tombol biasa di desktop.

    Menggantikan tombol "Tambah Data" yang membelah header jadi berdesakan di
    layar sempit. Desktop tetap memakai slot `actions` (x-admin.page-header).

    Pemakaian:
        <x-admin.fab modal="create-customer-modal" label="Tambah Data">
            <i class="fas fa-plus"></i>
        </x-admin.fab>
--}}
@props([
    'modal' => null,
    'label' => 'Tambah',
    'variant' => 'blue',
    'class' => '',
])

@php
    $palette = [
        'blue' => 'bg-blue-700 hover:bg-blue-800 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800',
        'green' => 'bg-greenlight hover:bg-[#6d9159] focus:ring-green-300 dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-800',
        'red' => 'bg-red-600 hover:bg-red-700 focus:ring-red-300 dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-800',
    ];
    $tone = $palette[$variant] ?? $palette['blue'];
@endphp

{{-- mobile: fixed pojok kanan bawah, 56px (≥44px) --}}
<button type="button"
    @if ($modal) data-target-modal="{{ $modal }}" @endif
    aria-label="{{ $label }}"
    title="{{ $label }}"
    class="admin-touch-target fixed bottom-4 right-4 z-50 h-14 w-14 gap-2 text-white rounded-full shadow-lg js-open-modal-btn focus:outline-none focus:ring-4 {{ $tone }} {{ $class }} md:hidden">
    <span class="text-lg leading-none">{{ $slot }}</span>
    <span class="sr-only">{{ $label }}</span>
</button>
