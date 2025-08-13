@extends('layouts.argon')
@section('title', 'Data Customer')
@section('page_title', 'Customer')

@section('content')
    <div class="relative min-h-[715px] bg-white shadow-md dark:bg-gray-800 sm:rounded-lg">
        <div class="flex flex-col p-4 space-y-3 md:flex-row md:items-center md:justify-between md:space-y-0 md:space-x-4">
            {{-- Form pencarian --}}
            <form class="w-full md:w-1/2" action="{{ route('kurir.customers.index') }}" method="GET">
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
                    <input type="text" id="simple-search" name="search"
                        class="block w-full p-2 pl-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                        placeholder="Cari customer..." value="{{ request('search') }}">
                </div>
            </form>

            <button type="button" data-modal-toggle="crud-modal"
                class="flex items-center justify-center w-full px-4 py-2 text-sm font-medium text-white bg-blue-700 rounded-lg md:w-auto hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800">
                <i class="fas fa-plus me-2"></i>
                Tambah Data
            </button>
        </div>

        {{-- Desktop Table View --}}
        <div class="relative hidden overflow-x-auto min-h-[500px] md:block">
            <table class="w-full text-sm text-left text-gray-500 rtl:text-right dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-6 py-3">Nama Customer</th>
                        <th scope="col" class="px-6 py-3">Alamat</th>
                        <th scope="col" class="px-6 py-3">Nomor Telepon</th>
                        <th scope="col" class="px-6 py-3">Region</th>
                        <th scope="col" class="px-6 py-3">Note</th>
                        <th scope="col" class="px-4 py-3"><span class="sr-only">Aksi</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($customers as $customer)
                    <tr class="bg-white border-b border-gray-200 dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                        <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                            {{ $customer->name }}
                        </th>
                        <td class="px-6 py-4">{{ Str::limit($customer->address, 30) }}</td>
                        <td class="px-6 py-4">{{ $customer->phone }}</td>
                        <td class="px-6 py-4">{{ $customer->region->name }}</td>
                        <td class="px-6 py-4">{{ Str::limit($customer->note, 20) }}</td>
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
                                                    data-modal-target="edit-modal-{{ $customer->id }}"
                                                    data-modal-toggle="edit-modal-{{ $customer->id }}"
                                                    class="flex items-center w-full px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-600">
                                                    <span class="inline-block w-6 mr-2 text-center"><i
                                                            class="fas fa-edit"></i></span>
                                                    <span>Edit</span>
                                                </button>
                                            </li>
                                            <li>
                                                <button type="button"
                                                    data-modal-target="note-modal-{{ $customer->id }}"
                                                    data-modal-toggle="note-modal-{{ $customer->id }}"
                                                    class="flex items-center w-full px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-600">
                                                    <span class="inline-block w-6 mr-2 text-center"><i
                                                            class="fas fa-sticky-note"></i></span>
                                                    <span>Note</span>
                                                </button>
                                            </li>
                                        </ul>
                                        <div class="py-1">
                                            <button type="button"
                                                data-modal-target="delete-modal-{{ $customer->id }}"
                                                data-modal-toggle="delete-modal-{{ $customer->id }}"
                                                class="flex items-center w-full px-4 py-2 text-sm text-left text-red-600 hover:bg-gray-100 dark:hover:bg-gray-600 dark:text-red-500 dark:hover:text-white">
                                                <span class="inline-block w-6 mr-2 text-center"><i
                                                        class="fas fa-trash"></i></span>
                                                <span>Delete</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        {{-- <td class="px-6 py-4">
                            <div class="flex items-center space-x-4">
                                <button type="button" data-modal-toggle="edit-modal-{{ $customer->id }}" class="text-blue-600 dark:text-blue-500 hover:underline">
                                    <i class="fa-solid fa-pen-to-square"></i> Edit
                                </button>
                                <button type="button" data-modal-toggle="note-modal-{{ $customer->id }}" class="text-yellow-600 dark:text-yellow-500 hover:underline">
                                    <i class="fa-solid fa-clipboard"></i> Note
                                </button>
                                <button type="button" data-modal-toggle="delete-modal-{{ $customer->id }}" class="text-red-600 dark:text-red-500 hover:underline">
                                    <i class="fa-solid fa-trash"></i> Delete
                                </button>
                            </div>
                        </td> --}}
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                            Tidak ada data customer ditemukan.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Mobile Card View --}}
        <div class="grid grid-cols-1 gap-4 p-4 md:hidden">
            @forelse ($customers as $customer)
            <div class="w-full max-w-sm p-4 bg-white border border-gray-200 rounded-lg shadow-md dark:bg-gray-800 dark:border-gray-700">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $customer->name }}</h4>
                </div>
                <p class="mb-1 text-sm text-gray-700 dark:text-gray-300">
                    <i class="fa-solid fa-location-dot me-2"></i> {{ $customer->address }} ({{ $customer->region->name }})
                </p>
                @if($customer->note)
                <p class="mb-1 text-xs italic text-gray-700 dark:text-gray-300">
                    <i class="fa-solid fa-clipboard-list me-2"></i> {{ $customer->note }}
                </p>
                @endif
                <div class="flex justify-end pt-2 mt-2 space-x-3 border-t border-gray-200 dark:border-gray-700">
                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $customer->phone) }}" target="_blank" class="text-green-600 dark:text-green-500 hover:underline">
                        <i class="fa-brands fa-whatsapp text-lg"></i>
                    </a>
                    <button type="button" data-modal-toggle="edit-modal-{{ $customer->id }}" class="text-blue-600 dark:text-blue-500 hover:underline">
                        <i class="fa-solid fa-pen-to-square text-lg"></i>
                    </button>
                    <button type="button" data-modal-toggle="note-modal-{{ $customer->id }}" class="text-yellow-600 dark:text-yellow-500 hover:underline">
                        <i class="fa-solid fa-clipboard text-lg"></i>
                    </button>
                    <button type="button" data-modal-toggle="delete-modal-{{ $customer->id }}" class="text-red-600 dark:text-red-500 hover:underline">
                        <i class="fa-solid fa-trash text-lg"></i>
                    </button>
                </div>
            </div>
            @empty
            <div class="text-center text-gray-500 dark:text-gray-400">
                Tidak ada data customer ditemukan.
            </div>
            @endforelse
        </div>

        <div class="p-4">
            {{ $customers->withQueryString()->links() }}
        </div>
    </div>
@endsection

@push('modals')
    {{-- Memanggil semua modal dari file terpisah --}}
    @include('dashboard.kurir.customers.create')

    @foreach ($customers as $customer)
        @include('dashboard.kurir.customers.edit', ['customer' => $customer])
        @include('dashboard.kurir.customers.note', ['customer' => $customer])
        @include('dashboard.kurir.customers.delete', ['customer' => $customer])
    @endforeach
@endpush
