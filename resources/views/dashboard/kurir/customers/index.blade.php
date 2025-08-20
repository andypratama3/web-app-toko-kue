@extends('layouts.argon')
@section('title', 'Data Customer')
@section('page_title', 'Customer')

@section('content')
    <div class="relative min-h-[715px] bg-white shadow-md dark:bg-gray-800 sm:rounded-lg">
        <div class="flex flex-col p-4 space-y-3 md:flex-row md:items-center md:justify-between md:space-y-0 md:space-x-4">
            <div class="w-full md:w-1/2">
                {{-- Form pencarian --}}
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
                            placeholder="Cari customer">
                    </div>
                </form>
            </div>
            <button type="button" data-modal-toggle="crud-modal"
                class="flex items-center justify-center w-full px-4 py-2 text-sm font-medium text-white bg-blue-700 rounded-lg md:w-auto hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800">
                <i class="fas fa-plus me-2"></i>
                Tambah Data
            </button>
        </div>

        {{-- Desktop Table View --}}
        <div class="relative hidden overflow-x-auto min-h-[580px] md:block">
            <table class="w-full text-sm text-left text-gray-500 rtl:text-right dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-center">No.</th>
                        <th scope="col" class="px-6 py-3 text-center">👤 Nama Customer</th>
                        <th scope="col" class="px-6 py-3 text-center">📍Alamat</th>
                        <th scope="col" class="px-6 py-3">Nomor Telepon</th>
                        <th scope="col" class="px-6 py-3 text-center">Region</th>
                        <th scope="col" class="px-6 py-3 text-center">📝 Note</th>
                        <th scope="col" class="px-4 py-3 text-center"><span class="sr-only">Aksi</span></th>
                    </tr>
                </thead>
                <tbody id="customer-results-container">
                    @include('dashboard.kurir.customers._table_rows', ['customers' => $customers])
                </tbody>
            </table>
        </div>

        {{-- Mobile Card View --}}
        <div id="customer-results-container-mobile" class="grid grid-cols-1 gap-4 p-4 md:hidden">
            @include('dashboard.kurir.customers._card_view', ['customers' => $customers])
        </div>

        <div class="flex justify-center w-full p-4 md:justify-end">
            {{ $customers->withQueryString()->links() }}
        </div>
    </div>
@endsection

@push('flowbite-modals')
    @include('dashboard.kurir.customers.create')

    @foreach ($customers as $customer)
        @include('dashboard.kurir.customers.edit', ['customer' => $customer])
        @include('dashboard.kurir.customers.note', ['customer' => $customer])
        @include('dashboard.kurir.customers.delete', ['customer' => $customer])
    @endforeach
@endpush

@push('page-scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            initializeLiveSearch({
                searchInputId: 'live-search-input',
                desktopContainerId: 'customer-results-container',
                mobileContainerId: 'customer-results-container-mobile'
            });
        });
    </script>
@endpush
