{{--
    PageHeader — judul halaman + subtitle + slot search + slot actions.
    Mobile-first: judul text-lg → sm:text-xl → md:text-2xl.
    Slot `search`  : input pencarian (full width di mobile).
    Slot `actions` : tombol aksi (menjadi FAB di mobile lewat x-admin.fab).
--}}
@props([
    'title' => null,
    'subtitle' => null,
])

<div class="flex flex-col gap-2 sm:gap-3 md:flex-row md:items-center md:justify-between md:gap-4">
    <div class="min-w-0">
        @if ($title)
            <h2 class="text-lg font-semibold text-gray-800 sm:text-xl md:text-2xl dark:text-white">
                {{ $title }}
            </h2>
        @endif
        @if ($subtitle)
            <p class="mt-0.5 text-xs text-gray-500 sm:text-sm dark:text-gray-400">
                {{ $subtitle }}
            </p>
        @endif
    </div>

    {{-- Search: full width di mobile, max-w-md di desktop --}}
    @isset($search)
        <div class="w-full md:w-auto md:max-w-md">
            {{ $search }}
        </div>
    @endisset

    {{-- Aksi: disembunyikan di mobile (digantikan FAB) --}}
    @isset($actions)
        <div class="hidden w-full shrink-0 items-center gap-2 sm:gap-3 md:flex md:w-auto">
            {{ $actions }}
        </div>
    @endisset
</div>
