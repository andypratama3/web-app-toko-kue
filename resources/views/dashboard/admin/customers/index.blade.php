@extends('layouts.argon')
@section('title', 'Manajemen Customer')
@section('page_title', 'Customer')

@section('content')
    {{-- Container utama --}}
    <div class="relative overflow-hidden min-h-[715px] bg-white shadow-md dark:bg-gray-800 sm:rounded-lg">
        <div class="flex flex-col p-4 space-y-3 md:flex-row md:items-center md:justify-between md:space-y-0 md:space-x-4">
            <div class="w-full md:w-1/2">
                <form class="flex items-center" method="GET" action="{{ route('admin.customers.index') }}">
                    <label for="simple-search" class="sr-only">Cari</label>
                    <div class="relative w-full">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <svg aria-hidden="true" class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="currentColor"
                                viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd"
                                    d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                        <input type="text" id="simple-search" name="search" value="{{ request('search') }}"
                            class="block w-full p-2 pl-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                            placeholder="Cari customer">
                    </div>
                </form>
            </div>
            <div class="flex items-center w-full space-x-3 md:w-auto">
                <button type="button" data-modal-target="create-customer-modal" data-modal-toggle="create-customer-modal"
                    class="flex items-center justify-center w-full px-4 py-2 text-sm font-medium text-white bg-blue-700 rounded-lg md:w-auto hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800">
                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd"
                            d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                            clip-rule="evenodd"></path>
                    </svg>
                    Tambah Data
                </button>
            </div>
        </div>
        <div class="overflow-x-auto min-h-[580px]">
            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-center">No.</th>
                        <th scope="col" class="px-4 py-3 text-center">Nama Customer</th>
                        <th scope="col" class="px-4 py-3 text-center">Alamat</th>
                        <th scope="col" class="px-4 py-3 text-center">Nomor Telepon</th>
                        <th scope="col" class="px-4 py-3 text-center">Region</th>
                        <th scope="col" class="px-4 py-3 text-center">Note</th>
                        <th scope="col" class="px-4 py-3 text-center">Tanggal Bergabung</th>
                        <th scope="col" class="px-4 py-3 text-center"><span class="sr-only">Aksi</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($customers as $customer)
                        <tr class="border-b dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700">
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white text-center">
                                {{ ($customers->currentPage() - 1) * $customers->perPage() + $loop->iteration }}
                            </td>
                            <th scope="row"
                                class="px-4 py-3 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                {{ $customer->name }}</th>
                            <td class="px-4 py-3 text-center">{{ Str::limit($customer->address, 30) }}</td>
                            <td class="px-4 py-3 text-center">{{ $customer->phone }}</td>
                            <td class="px-4 py-3 text-center">{{ $customer->region->name ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-center">{{ Str::limit($customer->note, 20) }}</td>
                            <td class="px-4 py-3 text-center">{{ $customer->created_at->format('d M Y') }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="relative inline-block">
                                    <button data-dropdown-toggle="customer-actions-dropdown-{{ $customer->id }}"
                                        class="px-2 py-1 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:ring-2 focus:ring-gray-100 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <div id="customer-actions-dropdown-{{ $customer->id }}"
                                        class="z-50 hidden bg-white divide-y divide-gray-100 rounded shadow w-44 dark:bg-gray-700 dark:divide-gray-600">
                                        <ul class="py-1 text-sm text-gray-700 dark:text-gray-200"
                                            aria-labelledby="customer-actions-button-{{ $customer->id }}">
                                            <li>
                                                <button type="button"
                                                    data-modal-target="edit-customer-modal-{{ $customer->id }}"
                                                    data-modal-toggle="edit-customer-modal-{{ $customer->id }}"
                                                    class="flex items-center w-full px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-600">
                                                    <span class="inline-block w-6 mr-2 text-center"><i
                                                            class="fas fa-edit"></i></span>
                                                    <span>Edit</span>
                                                </button>
                                            </li>
                                            <li>
                                                <button type="button"
                                                    data-modal-target="note-customer-modal-{{ $customer->id }}"
                                                    data-modal-toggle="note-customer-modal-{{ $customer->id }}"
                                                    class="flex items-center w-full px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-600">
                                                    <span class="inline-block w-6 mr-2 text-center"><i
                                                            class="fas fa-sticky-note"></i></span>
                                                    <span>Note</span>
                                                </button>
                                            </li>
                                        </ul>
                                        <div class="py-1">
                                            <button type="button"
                                                data-modal-target="delete-customer-modal-{{ $customer->id }}"
                                                data-modal-toggle="delete-customer-modal-{{ $customer->id }}"
                                                class="flex items-center w-full px-4 py-2 text-sm text-left text-red-600 hover:bg-gray-100 dark:hover:bg-gray-600 dark:text-red-500 dark:hover:text-white">
                                                <span class="inline-block w-6 mr-2 text-center"><i
                                                        class="fas fa-trash"></i></span>
                                                <span>Delete</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-4 text-center text-gray-500">Tidak ada data customer.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <nav class="flex justify-center w-full p-4 md:justify-end" aria-label="Table navigation">
            {{ $customers->withQueryString()->links() }}
        </nav>
    </div>
@endsection

@push('flowbite-modals')
    {{-- Panggil Modal Tambah Customer --}}
    @include('dashboard.admin.customers.create')

    {{-- Panggil Modal Edit dan Hapus Customer (dalam loop) --}}
    @foreach ($customers as $customer)
        @include('dashboard.admin.customers.edit', ['customer' => $customer])
        @include('dashboard.admin.customers.note', ['customer' => $customer])
        @include('dashboard.admin.customers.delete', ['customer' => $customer])
    @endforeach
@endpush
