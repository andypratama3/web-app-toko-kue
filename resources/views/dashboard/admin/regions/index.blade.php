@extends('layouts.argon')
@section('title', 'Pengaturan Cabang')
@section('page_title', 'Pengaturan Cabang')

@section('content')
    <div class="relative min-h-[715px] bg-white shadow-md dark:bg-gray-800 sm:rounded-lg">
        {{-- Info banner --}}
        <div
            class="flex items-start gap-3 p-4 m-4 text-sm text-blue-800 border border-blue-200 rounded-lg bg-blue-50 dark:bg-gray-700 dark:border-gray-600 dark:text-blue-300">
            <i class="mt-0.5 fas fa-circle-info"></i>
            <div>
                <p class="font-medium">Nomor WhatsApp Owner Cabang</p>
                <p class="mt-1">
                    Notifikasi pesanan baru dan eskalasi otomatis diteruskan ke nomor owner cabang di bawah ini.
                    Pastikan nomornya benar dan aktif di WhatsApp. Nomor boleh ditulis dalam format
                    <span class="font-mono">0812…</span>, <span class="font-mono">62812…</span>, atau
                    <span class="font-mono">+62 812…</span> — akan disimpan otomatis sebagai <span
                        class="font-mono">62…</span>.
                </p>
                <p class="mt-1 text-xs">
                    Anda hanya dapat mengubah nomor owner untuk cabang Anda sendiri. Kosongkan kolom untuk
                    menonaktifkan penerusan ke owner.
                </p>
            </div>
        </div>

        <x-admin.responsive-table min-height="540">
            <table class="admin-table w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-center">No.</th>
                        <th scope="col" class="px-4 py-3">Cabang</th>
                        <th scope="col" class="px-4 py-3">Nomor Owner WhatsApp</th>
                        <th scope="col" class="px-4 py-3 text-center">Status Cabang</th>
                        <th scope="col" class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @include('dashboard.admin.regions._table_rows', [
                        'regions' => $regions,
                        'ownRegionId' => $ownRegionId,
                    ])
                </tbody>
            </table>
        </x-admin.responsive-table>
    </div>
@endsection

@push('flowbite-modals')
    @foreach ($regions as $region)
        @if ($region->id === $ownRegionId)
            @include('dashboard.admin.regions.edit', ['region' => $region])
        @endif
    @endforeach
@endpush

@push('page-scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            @if (session('error_modal_id'))
                if (typeof window.openModal === 'function') {
                    window.openModal(@json(session('error_modal_id')));
                }
            @endif
        });
    </script>
@endpush
