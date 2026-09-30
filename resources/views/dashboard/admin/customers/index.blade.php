@extends('layouts.argon')
@section('title', 'Manajemen Customer')
@section('page_title', 'Customer')

@section('content')
    {{-- Container utama --}}
    <div class="relative min-h-[715px] bg-white shadow-md dark:bg-gray-800 sm:rounded-lg">
        {{-- Header: judul + search + tombol tambah (FAB di mobile) --}}
        <div class="border-b border-gray-200 p-3 sm:p-4 dark:border-gray-700">
            <x-admin.page-header :title="'Daftar Customer'" subtitle="Kelola data customer.">
                <x-slot:search>
                    <x-admin.search-bar id="live-search-input" placeholder="Cari customer..."
                        :value="request('search')" />
                </x-slot:search>

                <x-slot:actions>
                    <button type="button" data-target-modal="create-customer-modal"
                        class="admin-touch-target w-full gap-2 px-4 py-2 text-sm font-medium text-white bg-blue-700 rounded-lg js-open-modal-btn md:w-auto hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800">
                        <i class="fas fa-plus"></i>
                        Tambah Data
                    </button>
                </x-slot:actions>
            </x-admin.page-header>
        </div>

        {{-- Tabel → card view di mobile --}}
        <x-admin.responsive-table min-height="580">
            <table class="admin-table w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-center">No.</th>
                        <th scope="col" class="px-4 py-3">Nama Perusahaan</th>
                        <th scope="col" class="px-4 py-3">Nama Customer</th>
                        <th scope="col" class="px-4 py-3">Alamat</th>
                        <th scope="col" class="px-4 py-3 text-center">Nomor Telepon</th>
                        <th scope="col" class="px-4 py-3 text-center">Note</th>
                        <th scope="col" class="px-4 py-3 text-center"><span class="sr-only">Aksi</span></th>
                    </tr>
                </thead>
                <tbody id="customer-results-container">
                    {{-- Konten tabel akan dimuat di sini oleh live search --}}
                    @include('dashboard.admin.customers._table_rows', ['customers' => $customers])
                </tbody>
            </table>
        </x-admin.responsive-table>

        {{-- Paginasi --}}
        <nav class="flex justify-center w-full p-3 sm:p-4 md:justify-end" aria-label="Table navigation">
            {{ $customers->withQueryString()->links() }}
        </nav>
    </div>

    {{-- FAB "Tambah Data" — hanya tampil di mobile --}}
    <x-admin.fab modal="create-customer-modal" label="Tambah Data" variant="blue">
        <i class="fas fa-plus"></i>
    </x-admin.fab>

    {{-- Container untuk modals hasil live search --}}
    <div id="customer-modals-container"></div>
@endsection

@push('flowbite-modals')
    @include('dashboard.admin.customers.create', [
        'customerCategories' => $customerCategories,
        'couriers' => $couriers,
    ])

    @foreach ($customers as $customer)
        @include('dashboard.admin.customers.show', ['customer' => $customer])
        @include('dashboard.admin.customers.edit', [
            'customer' => $customer,
            'customerCategories' => $customerCategories,
            'couriers' => $couriers,
        ])
        @include('dashboard.admin.customers.note', ['customer' => $customer])
        @include('dashboard.admin.customers.delete', ['customer' => $customer])
        @include('dashboard.admin.customers.rekap', ['customer' => $customer])
    @endforeach
@endpush

@push('page-scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            initializeLiveSearch({
                searchInputId: 'live-search-input',
                desktopContainerId: 'customer-results-container'
            });

            const resultsContainer = document.getElementById('customer-results-container');
            resultsContainer.addEventListener('click', function(event) {
                const flagButton = event.target.closest('.toggle-flag-btn');
                if (flagButton) {
                    event.preventDefault();
                    toggleCustomerFlag(flagButton);
                }
            });
        });

        async function toggleCustomerFlag(button) {
            const url = button.dataset.url;
            const icon = button.querySelector('i');
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                });

                if (!response.ok) throw new Error('Network response was not ok');

                const data = await response.json();
                if (data.success) {
                    // DIUBAH: Logika untuk mengubah tampilan bendera secara dinamis
                    if (data.is_flagged) {
                        // Jika status menjadi DITANDAI
                        icon.classList.remove('text-gray-400', 'hover:text-gray-600');
                        icon.classList.add('text-red-500');
                        icon.setAttribute('title', 'Customer Bermasalah. Klik untuk menghapus tanda.');
                    } else {
                        // Jika status menjadi NORMAL (tanda dihilangkan)
                        icon.classList.remove('text-red-500');
                        icon.classList.add('text-gray-400', 'hover:text-gray-600');
                        icon.setAttribute('title', 'Tandai sebagai customer bermasalah.');
                    }
                }
            } catch (error) {
                console.error('Flag toggle error:', error);
                alert('Gagal mengubah status customer.');
            }
        }
    </script>
@endpush
