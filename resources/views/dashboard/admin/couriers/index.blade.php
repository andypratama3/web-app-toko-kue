@extends('layouts.argon')
@section('title', 'Manajemen Kurir')
@section('page_title', 'Kurir')

@section('content')
    {{-- Kontainer utama untuk tabel dan aksi --}}
    <div class="relative bg-white shadow-md dark:bg-gray-800 sm:rounded-lg">
        <div class="flex flex-col items-center justify-between p-4 space-y-3 md:flex-row md:space-y-0 md:space-x-4">
            <div class="w-full md:w-1/2">
                <form class="flex items-center">
                    <label for="simple-search" class="sr-only">Cari Kurir</label>
                    <div class="relative w-full">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <svg aria-hidden="true" class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <input type="text" id="simple-search" class="block w-full p-2 pl-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="Cari berdasarkan nama atau email">
                    </div>
                </form>
            </div>
            <div class="flex flex-col items-stretch justify-end flex-shrink-0 w-full space-y-2 md:w-auto md:flex-row md:space-y-0 md:items-center md:space-x-3">
                {{-- PERBAIKAN: Warna tombol disamakan menjadi biru agar konsisten --}}
                <button type="button" data-modal-target="create-courier-modal" data-modal-toggle="create-courier-modal" class="flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800">
                    <svg class="h-3.5 w-3.5 mr-2" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path clip-rule="evenodd" fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" />
                    </svg>
                    Tambah Kurir
                </button>
            </div>
        </div>
        {{-- PERBAIKAN: Class 'overflow-x-auto' dikembalikan agar tabel bisa digulir di layar kecil --}}
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-4 py-3">Nama Kurir</th>
                        <th scope="col" class="px-4 py-3">Email</th>
                        <th scope="col" class="px-4 py-3">Region</th>
                        <th scope="col" class="px-4 py-3">Tanggal Bergabung</th>
                        <th scope="col" class="px-4 py-3"><span class="sr-only">Aksi</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($couriers as $courier)
                    <tr class="border-b dark:border-gray-700">
                        <th scope="row" class="px-4 py-3 font-medium text-gray-900 whitespace-nowrap dark:text-white">{{ $courier->name }}</th>
                        <td class="px-4 py-3">{{ $courier->email }}</td>
                        <td class="px-4 py-3">{{ $courier->region->name }}</td>
                        <td class="px-4 py-3">{{ $courier->created_at->format('d M Y') }}</td>
                        <td class="px-4 py-3 text-right">
                            <div class="relative inline-block">
                                <button id="courier-actions-button-{{ $courier->id }}" data-dropdown-toggle="courier-actions-dropdown-{{ $courier->id }}" data-dropdown-placement="bottom-end" class="inline-flex items-center p-0.5 text-sm font-medium text-center text-gray-500 hover:text-gray-800 rounded-lg focus:outline-none dark:text-gray-400 dark:hover:text-gray-100" type="button">
                                    <svg class="w-5 h-5" aria-hidden="true" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z" />
                                    </svg>
                                </button>
                                <div id="courier-actions-dropdown-{{ $courier->id }}" class="z-50 hidden bg-white divide-y divide-gray-100 rounded shadow w-44 dark:bg-gray-700 dark:divide-gray-600">
                                    <ul class="py-1 text-sm text-gray-700 dark:text-gray-200" aria-labelledby="courier-actions-button-{{ $courier->id }}">
                                        <li><button type="button" data-modal-target="note-courier-modal-{{ $courier->id }}" data-modal-toggle="note-courier-modal-{{ $courier->id }}" class="block w-full px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">Note</button></li>
                                        <li><button type="button" data-modal-target="performance-courier-modal-{{ $courier->id }}" data-modal-toggle="performance-courier-modal-{{ $courier->id }}" class="block w-full px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">Performa</button></li>
                                        <li><button type="button" data-modal-target="edit-courier-modal-{{ $courier->id }}" data-modal-toggle="edit-courier-modal-{{ $courier->id }}" class="block w-full px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">Edit</button></li>
                                    </ul>
                                    <div class="py-1">
                                        <button type="button" data-modal-target="delete-courier-modal-{{ $courier->id }}" data-modal-toggle="delete-courier-modal-{{ $courier->id }}" class="block w-full px-4 py-2 text-sm text-left text-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 dark:text-gray-200 dark:hover:text-white">Hapus</button>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr class="border-b dark:border-gray-700">
                        <td colspan="5" class="px-4 py-3 text-center text-gray-500">Belum ada data kurir di region ini.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <nav class="flex flex-col items-start justify-between p-4 space-y-3 md:flex-row md:items-center md:space-y-0" aria-label="Table navigation">
            {{ $couriers->links() }}
        </nav>
    </div>

    {{-- ====================================================================== --}}
    {{-- SEMUA MODAL DIDEFINISIKAN DI LUAR KONTAINER UTAMA UNTUK KEBERSIHAN KODE --}}
    {{-- ====================================================================== --}}

    {{-- Modal untuk Tambah Kurir Baru --}}
    <div id="create-courier-modal" tabindex="-1" aria-hidden="true" class="fixed top-0 left-0 right-0 z-10 items-center justify-center hidden w-full h-full overflow-x-hidden overflow-y-auto md:inset-0">
        {{-- Backdrop dengan z-40 agar modal konten di atasnya --}}
        <div class="absolute inset-0 z-10 bg-gray-900 bg-opacity-40" aria-hidden="true"></div>
        {{-- Konten modal dengan z-50 agar di atas backdrop --}}
        <div class="relative z-50 w-full h-full max-w-2xl p-4 md:h-auto">
            <div class="relative p-4 bg-white rounded-lg shadow dark:bg-gray-800 sm:p-5">
                <div class="flex items-center justify-between pb-4 mb-4 border-b rounded-t sm:mb-5 dark:border-gray-600">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Tambah Kurir Baru</h3>
                    <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center dark:hover:bg-gray-600 dark:hover:text-white" data-modal-toggle="create-courier-modal">
                        <svg aria-hidden="true" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                        <span class="sr-only">Close modal</span>
                    </button>
                </div>
                <form action="{{ route('admin.couriers.store') }}" method="POST">
                    @csrf
                    <div class="grid gap-4 mb-4 sm:grid-cols-2">
                        {{-- Form Inputs --}}
                        <div>
                            <label for="name" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Nama Lengkap</label>
                            <input type="text" name="name" id="name" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" value="{{ old('name') }}" required>
                            @error('name') <p class="mt-2 text-sm text-red-600 dark:text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="email" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Alamat Email</label>
                            <input type="email" name="email" id="email" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" value="{{ old('email') }}" required>
                            @error('email') <p class="mt-2 text-sm text-red-600 dark:text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="password" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Password</label>
                            <input type="password" name="password" id="password" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" required>
                            @error('password') <p class="mt-2 text-sm text-red-600 dark:text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="password_confirmation" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Konfirmasi Password</label>
                            <input type="password" name="password_confirmation" id="password_confirmation" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" required>
                        </div>
                    </div>
                    <button type="submit" class="text-white inline-flex items-center bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                        <svg class="w-6 h-6 mr-1 -ml-1" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd"></path></svg>
                        Simpan Kurir
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Loop untuk semua Modal --}}
    @foreach ($couriers as $courier)
        {{-- Modal Edit Kurir --}}
        <div id="edit-courier-modal-{{ $courier->id }}" tabindex="-1" aria-hidden="true" class="fixed top-0 left-0 right-0 z-50 flex items-center justify-center hidden w-full h-full overflow-x-hidden overflow-y-auto md:inset-0">
            <div class="absolute inset-0 z-40 bg-gray-900 bg-opacity-50" aria-hidden="true"></div>
            <div class="relative z-50 w-full h-full max-w-2xl p-4 md:h-auto">
                <div class="relative p-4 bg-white rounded-lg shadow dark:bg-gray-800 sm:p-5">
                    {{-- Modal Content for Edit --}}
                </div>
            </div>
        </div>

        {{-- Modal Hapus Kurir --}}
        <div id="delete-courier-modal-{{ $courier->id }}" tabindex="-1" aria-hidden="true" class="fixed top-0 left-0 right-0 z-50 flex items-center justify-center hidden w-full h-full overflow-x-hidden overflow-y-auto md:inset-0">
            <div class="absolute inset-0 z-40 bg-gray-900 bg-opacity-50" aria-hidden="true"></div>
            <div class="relative z-50 w-full h-full max-w-md p-4 md:h-auto">
                <div class="relative p-4 text-center bg-white rounded-lg shadow dark:bg-gray-800 sm:p-5">
                    {{-- Modal Content for Delete --}}
                </div>
            </div>
        </div>

        {{-- Modal Placeholder untuk Note --}}
        <div id="note-courier-modal-{{ $courier->id }}" tabindex="-1" aria-hidden="true" class="fixed top-0 left-0 right-0 z-50 flex items-center justify-center hidden w-full h-full overflow-x-hidden overflow-y-auto md:inset-0">
            <div class="absolute inset-0 z-40 bg-gray-900 bg-opacity-50" aria-hidden="true"></div>
            <div class="relative z-50 w-full h-full max-w-lg p-4 md:h-auto">
                <div class="relative p-4 bg-white rounded-lg shadow dark:bg-gray-800 sm:p-5">
                    {{-- Modal Content for Note --}}
                </div>
            </div>
        </div>

        {{-- Modal Placeholder untuk Performa --}}
        <div id="performance-courier-modal-{{ $courier->id }}" tabindex="-1" aria-hidden="true" class="fixed top-0 left-0 right-0 z-50 flex items-center justify-center hidden w-full h-full overflow-x-hidden overflow-y-auto md:inset-0">
            <div class="absolute inset-0 z-40 bg-gray-900 bg-opacity-50" aria-hidden="true"></div>
            <div class="relative z-50 w-full h-full max-w-lg p-4 md:h-auto">
                <div class="relative p-4 bg-white rounded-lg shadow dark:bg-gray-800 sm:p-5">
                    {{-- Modal Content for Performance --}}
                </div>
            </div>
        </div>
    @endforeach

{{-- Script untuk membuka modal jika ada error validasi --}}
@if ($errors->any())
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const errorModalId = '{{ session('error_modal_id') }}';

        if (errorModalId) {
            const modal = document.getElementById(errorModalId);
            if (modal) {
                const modalInstance = new Modal(modal);
                modalInstance.show();
            }
        } else {
            const createModal = document.getElementById('create-courier-modal');
            if(createModal.querySelector('.text-red-600')){
                 const modalInstance = new Modal(createModal);
                 modalInstance.show();
            }
        }
    });
</script>
@endif

@endsection
