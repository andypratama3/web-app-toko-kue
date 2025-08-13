@extends('layouts.argon')
@section('title', 'Data Customer')
@section('page_title', 'Customer')
@section('content')
    {{-- Font Awesome CDN for icons --}}
    {{-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> --}}

    <div class="flex flex-wrap -mx-3">
        <div class="flex-none w-full max-w-full px-3">
            <div class="p-3 pb-0 mb-0 border-b-0 border-b-solid rounded-t-2xl border-b-transparent">

                <div class="flex flex-col gap-3 mb-4 md:flex-row md:items-center md:justify-between">
                    {{-- Form pencarian --}}
                    {{-- Atur action ke route yang sama (atau route yang menangani pencarian) dan method GET --}}
                    <form class="flex w-full md:max-w-md" action="{{ route('kurir.customers.index') }}" method="GET">
                        <label for="simple-search" class="sr-only">Cari</label>
                        <div class="relative w-full">
                            <input type="text" id="simple-search" name="search"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-3 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                placeholder="Cari di sini..."
                                value="{{ request('search') }}" {{-- Pertahankan nilai pencarian sebelumnya --}}
                            />
                        </div>
                        <button type="submit" class="px-4 py-2.5 ms-2 text-sm font-medium text-white bg-blue-700 rounded-lg border border-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                            <svg class="w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z" />
                            </svg>
                            <span class="sr-only">Cari</span>
                        </button>
                    </form>

                    <button type="button" id="add-customer" data-modal-toggle="crud-modal"
                        class="w-full px-3 py-2 text-sm font-medium text-white bg-blue-700 rounded-lg md:w-auto hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                        Tambah Data
                    </button>
                </div>
            </div>

            {{-- Desktop Table View --}}
            <div class="relative mx-3 overflow-x-auto hidden md:block"> {{-- Hidden on small screens --}}
                <table class="w-full text-sm text-left text-gray-500 rtl:text-right dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-6 py-3">Nama Customer</th>
                            <th scope="col" class="px-6 py-3">Nomor Telepon</th>
                            <th scope="col" class="px-6 py-3">Alamat</th>
                            <th scope="col" class="px-6 py-3">Region</th>
                            <th scope="col" class="px-6 py-3">Note</th>
                            <th scope="col" class="px-6 py-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($customers as $customer) {{-- Gunakan @forelse untuk menangani kasus tidak ada data --}}
                        <tr class="bg-white border-b border-gray-200 dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                {{ $customer->name }}
                            </th>
                            <td class="px-6 py-4">
                                {{ $customer->phone }}
                            </td>
                            <td class="px-6 py-4">
                                {{ $customer->address }}
                            </td>
                            <td class="px-6 py-4">
                                {{ $customer->region->name }}
                            </td>
                            <td class="px-6 py-4">
                                {{ $customer->note }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-4">
                                    <button type="button"
                                            data-modal-target="edit-modal-{{ $customer->id }}"
                                            data-modal-toggle="edit-modal-{{ $customer->id }}"
                                            class="text-blue-600 dark:text-blue-500 hover:underline">
                                        <i class="fa-solid fa-pen-to-square"></i> Edit
                                    </button>
                                    <button type="button"
                                            data-modal-target="note-modal-{{ $customer->id }}"
                                            data-modal-toggle="note-modal-{{ $customer->id }}"
                                            class="text-yellow-600 dark:text-yellow-500 hover:underline">
                                        <i class="fa-solid fa-clipboard"></i> Note
                                    </button>
                                    <button type="button"
                                            data-modal-target="delete-modal-{{ $customer->id }}"
                                            data-modal-toggle="delete-modal-{{ $customer->id }}"
                                            class="text-red-600 dark:text-red-500 hover:underline">
                                        <i class="fa-solid fa-trash"></i> Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty {{-- Tambahkan pesan jika tidak ada data ditemukan --}}
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
            <div class="flex flex-col items-center mx-3 mt-4 md:hidden"> {{-- Hidden on medium and larger screens --}}
                @forelse ($customers as $customer) {{-- Gunakan @forelse di sini juga --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 mb-4 border border-gray-200 dark:border-gray-700 w-full max-w-sm">
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $customer->name }}</h4>
                        <div class="flex items-center space-x-2 text-gray-500 dark:text-gray-400 text-sm">
                        </div>
                    </div>
                    <p class="text-gray-700 dark:text-gray-300 text-sm mb-1">
                        <i class="fa-solid fa-location-dot mr-2"></i> {{ $customer->address }} ({{ $customer->region->name }})
                    </p>
                    @if($customer->note)
                    <p class="text-gray-700 dark:text-gray-300 text-xs italic mb-1">
                        <i class="fa-solid fa-clipboard-list mr-2"></i> {{ $customer->note }}
                    </p>
                    @endif
                    <div class="flex justify-end space-x-3">
                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $customer->phone) }}?text={{ urlencode('Halo, saya tertarik dengan produk Anda') }}"
                            target="_blank"
                            class="text-green-600 dark:text-green-500 hover:text-green-700 dark:hover:text-green-400 focus:outline-none focus:ring-2 focus:ring-green-300 rounded-full px-1">
                            <i class="fa-brands fa-whatsapp text-lg"></i>
                        </a>
                        <button type="button"
                                data-modal-target="edit-modal-{{ $customer->id }}"
                                data-modal-toggle="edit-modal-{{ $customer->id }}"
                                class="text-blue-600 dark:text-blue-500 hover:text-blue-700 dark:hover:text-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-300 rounded-full px-1">
                            <i class="fa-solid fa-pen-to-square text-lg"></i>
                        </button>
                        <button type="button"
                                data-modal-target="note-modal-{{ $customer->id }}"
                                data-modal-toggle="note-modal-{{ $customer->id }}"
                                class="text-yellow-600 dark:text-yellow-500 hover:text-yellow-700 dark:hover:text-yellow-400 focus:outline-none focus:ring-2 focus:ring-yellow-300 rounded-full px-1">
                            <i class="fa-solid fa-clipboard text-lg"></i>
                        </button>
                        <button type="button"
                                data-modal-target="delete-modal-{{ $customer->id }}"
                                data-modal-toggle="delete-modal-{{ $customer->id }}"
                                class="text-red-600 dark:text-red-500 hover:text-red-700 dark:hover:text-red-400 focus:outline-none focus:ring-2 focus:ring-red-300 rounded-full px-1">
                            <i class="fa-solid fa-trash text-lg"></i>
                        </button>
                    </div>
                </div>
                @empty {{-- Tambahkan pesan jika tidak ada data ditemukan --}}
                <div class="text-center text-gray-500 dark:text-gray-400">
                    Tidak ada data customer ditemukan.
                </div>
                @endforelse
            </div>

            {{-- Modals for each customer --}}
            @foreach ($customers as $customer)
                <div id="edit-modal-{{ $customer->id }}" tabindex="-1" aria-hidden="true"
                    class="fixed inset-0 z-50 hidden overflow-x-hidden overflow-y-auto bg-gray-200 bg-opacity-50">
                    <div class="relative w-full max-w-2xl max-h-full p-4 mx-auto my-auto">
                        <div class="relative bg-white rounded-lg shadow dark:bg-gray-700">
                            <button type="button" class="absolute top-3 right-2.5 text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ml-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white" data-modal-hide="edit-modal-{{ $customer->id }}">
                                <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                                </svg>
                                <span class="sr-only">Tutup modal</span>
                            </button>
                            <div class="px-6 py-6 lg:px-8">
                                <h3 class="mb-4 text-xl font-medium text-gray-900 dark:text-white">Edit Customer</h3>
                                <form class="space-y-6" action="{{ route('kurir.customers.update', $customer->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="grid gap-4">
                                        <div>
                                            <label for="edit-name-{{ $customer->id }}" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Nama</label>
                                            <input type="text" name="name" id="edit-name-{{ $customer->id }}" value="{{ $customer->name }}"
                                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                                                required>
                                        </div>
                                        <div>
                                            <label for="edit-address-{{ $customer->id }}" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Alamat</label>
                                            <textarea name="address" id="edit-address-{{ $customer->id }}"
                                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                                                required>{{ $customer->address }}</textarea>
                                        </div>
                                        <div>
                                            <label for="edit-phone-{{ $customer->id }}" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">No. HP</label>
                                            <div class="relative">
                                                <div class="absolute inset-y-0 flex items-center px-2 pointer-events-none start-0">
                                                    +62
                                                </div>
                                                <input type="tel" name="phone" id="edit-phone-{{ $customer->id }}" value="{{ substr($customer->phone, 2) }}"
                                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                                    required>
                                            </div>
                                        </div>
                                        <div>
                                            <label for="edit-note-{{ $customer->id }}" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Catatan</label>
                                            <textarea id="edit-note-{{ $customer->id }}" name="note" rows="4"
                                                class="block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                                >{{ $customer->note }}</textarea>
                                        </div>
                                    </div>
                                    <div class="flex justify-end gap-3 mt-4">
                                        <button type="button" data-modal-hide="edit-modal-{{ $customer->id }}"
                                            class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-blue-300 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-500 dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-600">
                                            Batal
                                        </button>
                                        <button type="submit"
                                            class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                                            Update
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="note-modal-{{ $customer->id }}" tabindex="-1" aria-hidden="true"
                    class="fixed inset-0 z-50 hidden overflow-x-hidden overflow-y-auto bg-gray-200 bg-opacity-50">
                    <div class="relative w-full max-w-md max-h-full p-4 mx-auto my-auto">
                        <div class="relative bg-white rounded-lg shadow dark:bg-gray-700">
                            <button type="button" class="absolute top-3 right-2.5 text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ml-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white" data-modal-hide="note-modal-{{ $customer->id }}">
                                <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                                </svg>
                                <span class="sr-only">Tutup modal</span>
                            </button>
                            <div class="px-6 py-6 lg:px-8">
                                <h3 class="mb-4 text-xl font-medium text-gray-900 dark:text-white">Update Catatan</h3>
                                <form class="space-y-6" action="{{ route('kurir.customers.update-note', $customer->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div>
                                        <label for="note-{{ $customer->id }}" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Catatan</label>
                                        <textarea id="note-{{ $customer->id }}" name="note" rows="4" class="block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">{{ $customer->note }}</textarea>
                                    </div>
                                    <button type="submit" class="w-full text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">Update catatan</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="delete-modal-{{ $customer->id }}" tabindex="-1" aria-hidden="true"
                    class="fixed inset-0 z-50 hidden overflow-x-hidden overflow-y-auto bg-gray-200 bg-opacity-50">
                    <div class="relative w-full max-w-md max-h-full p-4 mx-auto my-auto">
                        <div class="relative bg-white rounded-lg shadow dark:bg-gray-700">
                            <button type="button" class="absolute top-3 right-2.5 text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ml-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white" data-modal-hide="delete-modal-{{ $customer->id }}">
                                <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                                </svg>
                                <span class="sr-only">Tutup modal</span>
                            </button>
                            <div class="px-6 py-6 lg:px-8">
                                <h3 class="mb-4 text-xl font-medium text-gray-900 dark:text-white">Konfirmasi Hapus</h3>
                                <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
                                    Apakah Anda yakin ingin menghapus customer <span class="font-semibold">{{ $customer->name }}</span>?
                                </p>
                                <form action="{{ route('kurir.customers.destroy', $customer->id) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <div class="flex justify-end gap-3">
                                        <button type="button" data-modal-hide="delete-modal-{{ $customer->id }}"
                                            class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-blue-300 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-500 dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-600">
                                            Batal
                                        </button>
                                        <button type="submit"
                                            class="text-white bg-red-700 hover:bg-red-800 focus:ring-4 focus:outline-none focus:ring-red-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-800">
                                            Hapus
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

            @if(session('success'))
            <div id="alert-success" class="fixed bottom-4 right-4 p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400" role="alert">
                {{ session('success') }}
                <button type="button" class="ml-auto -mx-1.5 -my-1.5" data-dismiss-target="#alert-success" aria-label="Tutup">
                    <span class="sr-only">Tutup</span>
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                </button>
            </div>
            @endif
        </div>
    </div>
</div>
@include('dashboard.kurir.customers.create')

<script>
    // Auto hide alert after 3 seconds
    document.addEventListener('DOMContentLoaded', function() {
        const alert = document.getElementById('alert-success');
        if (alert) {
            setTimeout(function() {
                alert.style.display = 'none';
            }, 3000);
        }
    });

    // Initialize modal functionality
    document.addEventListener('DOMContentLoaded', function() {
        const modalToggles = document.querySelectorAll('[data-modal-toggle]');
        modalToggles.forEach(toggle => {
            toggle.addEventListener('click', function() {
                const modalId = this.getAttribute('data-modal-target');
                const modal = document.getElementById(modalId);
                if (modal) {
                    modal.classList.toggle('hidden');
                }
            });
        });

        const modalHides = document.querySelectorAll('[data-modal-hide]');
        modalHides.forEach(hide => {
            hide.addEventListener('click', function() {
                const modalId = this.getAttribute('data-modal-hide');
                const modal = document.getElementById(modalId);
                if (modal) {
                    modal.classList.add('hidden');
                }
            });
        });
    });
</script>

@endsection
