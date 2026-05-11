@extends('layouts.argon')
@section('title', 'Manajemen Customer')
@section('page_title', 'Customer')

@section('content')
    {{-- Container utama --}}
    <div class="relative min-h-[715px] bg-white shadow-md dark:bg-gray-800 sm:rounded-lg">
        {{-- Header: Search dan Tombol Tambah --}}
        <div class="flex flex-col p-4 space-y-3 md:flex-row md:items-center md:justify-between md:space-y-0 md:space-x-4">
            <div class="w-full md:w-1/2">
                <form class="flex items-center" onsubmit="return false;">
                    <label for="live-search-input" class="sr-only">Cari</label>
                    <div class="relative w-full">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <svg aria-hidden="true" class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="currentColor"
                                viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd"
                                    d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                        <input type="text" id="live-search-input" name="search" value="{{ request('search') }}"
                            class="block w-full p-2 pl-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                            placeholder="Cari customer...">
                    </div>
                </form>
            </div>
            <div class="flex items-center w-full space-x-3 md:w-auto">
                <button type="button" data-target-modal="create-customer-modal"
                    class="flex items-center justify-center w-full px-4 py-2 text-sm font-medium text-white bg-blue-700 rounded-lg js-open-modal-btn md:w-auto hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800">
                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd"
                            d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                            clip-rule="evenodd"></path>
                    </svg>
                    Tambah Data
                </button>
            </div>
        </div>
        {{-- Tabel --}}
        <div class="overflow-visible min-h-[580px]">
            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
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
        </div>
        {{-- Paginasi --}}
        <nav class="flex justify-center w-full p-4 md:justify-end" aria-label="Table navigation">
            {{ $customers->withQueryString()->links() }}
        </nav>
    </div>

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
