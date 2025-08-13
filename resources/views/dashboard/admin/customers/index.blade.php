@extends('layouts.argon')
@section('title', 'Manajemen Customer')
@section('page_title', 'Customer')

@section('content')
    {{-- Notifikasi --}}
    @if (session('success'))
        <div id="alert-success"
            class="flex items-center p-4 mb-4 text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400"
            role="alert">
            <svg class="flex-shrink-0 w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor"
                viewBox="0 0 20 20">
                <path
                    d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 8.207-4 4a1 1 0 0 1-1.414 0l-2-2a1 1 0 0 1 1.414-1.414L9 10.586l3.293-3.293a1 1 0 0 1 1.414 1.414Z" />
            </svg>
            <span class="sr-only">Success</span>
            <div class="ml-3 text-sm font-medium">{{ session('success') }}</div>
            <button type="button"
                class="ml-auto -mx-1.5 -my-1.5 bg-green-50 text-green-500 rounded-lg focus:ring-2 focus:ring-green-400 p-1.5 hover:bg-green-200 inline-flex items-center justify-center h-8 w-8 dark:bg-gray-800 dark:text-green-400 dark:hover:bg-gray-700"
                data-dismiss-target="#alert-success" aria-label="Close">
                <span class="sr-only">Close</span>
                <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 14 14">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                </svg>
            </button>
        </div>
    @endif

    {{-- Container utama --}}
    <div class="relative overflow-hidden bg-white shadow-md dark:bg-gray-800 sm:rounded-lg">
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
                    Tambah Customer
                </button>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-4 py-3">Nama</th>
                        <th scope="col" class="px-4 py-3">Alamat</th>
                        <th scope="col" class="px-4 py-3">No. HP</th>
                        <th scope="col" class="px-4 py-3">Region</th>
                        <th scope="col" class="px-4 py-3">Note</th>
                        <th scope="col" class="px-4 py-3">Tanggal Bergabung</th>
                        <th scope="col" class="px-4 py-3"><span class="sr-only">Aksi</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($customers as $customer)
                        <tr class="border-b dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700">
                            <th scope="row"
                                class="px-4 py-2 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                {{ $customer->name }}</th>
                            <td class="px-4 py-2">{{ Str::limit($customer->address, 30) }}</td>
                            <td class="px-4 py-2">{{ $customer->phone }}</td>
                            <td class="px-4 py-2">{{ $customer->region->name ?? 'N/A' }}</td>
                            <td class="px-4 py-2">{{ Str::limit($customer->note, 20) }}</td>
                            <td class="px-4 py-2">{{ $customer->created_at->format('d M Y') }}</td>
                            <td class="px-4 py-2 text-right">
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
                                {{-- <button data-dropdown-toggle="actions-dropdown-{{ $customer->id }}"
                                    class="px-2 py-1 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:ring-2 focus:ring-gray-100 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <div id="actions-dropdown-{{ $customer->id }}"
                                    class="z-10 hidden bg-white divide-y divide-gray-100 rounded shadow w-44 dark:bg-gray-700">
                                    <ul class="py-1 text-sm">
                                        <li>
                                            <button type="button"
                                                data-modal-target="edit-customer-modal-{{ $customer->id }}"
                                                data-modal-toggle="edit-customer-modal-{{ $customer->id }}"
                                                class="flex items-center w-full px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600">
                                                <i class="w-4 h-4 mr-2 fas fa-edit"></i> Edit
                                            </button>
                                        </li>
                                        <li>
                                            <button type="button"
                                                data-modal-target="note-customer-modal-{{ $customer->id }}"
                                                data-modal-toggle="note-customer-modal-{{ $customer->id }}"
                                                class="flex items-center w-full px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600">
                                                <i class="w-4 h-4 mr-2 fas fa-sticky-note"></i> Note
                                            </button>
                                        </li>
                                    </ul>
                                    <div class="py-1">
                                        <button type="button"
                                            data-modal-target="delete-customer-modal-{{ $customer->id }}"
                                            data-modal-toggle="delete-customer-modal-{{ $customer->id }}"
                                            class="flex items-center w-full px-4 py-2 text-red-500 hover:bg-gray-100 dark:hover:bg-gray-600">
                                            <i class="w-4 h-4 mr-2 fas fa-trash"></i> Hapus
                                        </button>
                                    </div> --}}
        </div>
        </td>
        </tr>
    @empty
        <tr>
            <td colspan="6" class="px-4 py-4 text-center text-gray-500">Tidak ada data customer.</td>
        </tr>
        @endforelse
        </tbody>
        </table>
    </div>
    <div class="p-4">
        {{ $customers->withQueryString()->links() }}
    </div>
    </div>
@endsection

@push('modals')
    {{-- Panggil Modal Tambah Customer --}}
    @include('dashboard.admin.customers.create')

    {{-- Panggil Modal Edit dan Hapus Customer (dalam loop) --}}
    @foreach ($customers as $customer)
        @include('dashboard.admin.customers.edit', ['customer' => $customer])
        @include('dashboard.admin.customers.note', ['customer' => $customer])
        @include('dashboard.admin.customers.delete', ['customer' => $customer])
    @endforeach
@endpush
