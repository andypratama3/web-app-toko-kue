@extends('layouts.argon')
@section('title', 'Manajemen Kurir')
@section('page_title', 'Kurir')

@section('content')
    {{-- Notifikasi Sukses/Error --}}
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
            <div class="ml-3 text-sm font-medium">
                {{ session('success') }}
            </div>
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

    @if (session('error'))
        <div id="alert-error"
            class="flex items-center p-4 mb-4 text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400"
            role="alert">
            <svg class="flex-shrink-0 w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor"
                viewBox="0 0 20 20">
                <path
                    d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 11.793a1 1 0 1 1-1.414 1.414L10 11.414l-2.293 2.293a1 1 0 0 1-1.414-1.414L8.586 10 6.293 7.707a1 1 0 0 1 1.414-1.414L10 8.586l2.293-2.293a1 1 0 0 1 1.414 1.414L11.414 10l2.293 2.293Z" />
            </svg>
            <span class="sr-only">Error</span>
            <div class="ml-3 text-sm font-medium">
                {{ session('error') }}
            </div>
            <button type="button"
                class="ml-auto -mx-1.5 -my-1.5 bg-red-50 text-red-500 rounded-lg focus:ring-2 focus:ring-red-400 p-1.5 hover:bg-red-200 inline-flex items-center justify-center h-8 w-8 dark:bg-gray-800 dark:text-red-400 dark:hover:bg-gray-700"
                data-dismiss-target="#alert-error" aria-label="Close">
                <span class="sr-only">Close</span>
                <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 14 14">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                </svg>
            </button>
        </div>
    @endif

    {{-- Kontainer utama untuk tabel dan aksi --}}
    <div class="relative bg-white shadow-md dark:bg-gray-800 sm:rounded-lg">
        <div class="flex flex-col items-center justify-between p-4 space-y-3 md:flex-row md:space-y-0 md:space-x-4">
            <div class="w-full md:w-1/2">
                <form class="flex items-center">
                    <label for="simple-search" class="sr-only">Cari Kurir</label>
                    <div class="relative w-full">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <svg aria-hidden="true" class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="currentColor"
                                viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd"
                                    d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                        <input type="text" id="simple-search"
                            class="block w-full p-2 pl-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                            placeholder="Cari berdasarkan nama atau email">
                    </div>
                </form>
            </div>
            <div
                class="flex flex-col items-stretch justify-end flex-shrink-0 w-full space-y-2 md:w-auto md:flex-row md:space-y-0 md:items-center md:space-x-3">
                <button type="button" data-modal-target="create-courier-modal" data-modal-toggle="create-courier-modal"
                    class="flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800">
                    <svg class="h-3.5 w-3.5 mr-2" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"
                        aria-hidden="true">
                        <path clip-rule="evenodd" fill-rule="evenodd"
                            d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" />
                    </svg>
                    Tambah Kurir
                </button>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-4 py-3">Nama Kurir</th>
                        <th scope="col" class="px-4 py-3">Email</th>
                        <th scope="col" class="px-4 py-3">Region</th>
                        <th scope="col" class="px-4 py-3">Note</th>
                        <th scope="col" class="px-4 py-3">Tanggal Bergabung</th>
                        <th scope="col" class="px-4 py-3"><span class="sr-only">Aksi</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($couriers as $courier)
                        <tr class="border-b dark:border-gray-700">
                            <th scope="row"
                                class="px-4 py-3 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                {{ $courier->name }}</th>
                            <td class="px-4 py-3">{{ $courier->email }}</td>
                            <td class="px-4 py-3">{{ $courier->region->name }}</td>
                            <td class="px-4 py-3">{{ Str::limit($courier->note, 20) }}</td>
                            <td class="px-4 py-3">{{ $courier->created_at->format('d M Y') }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="relative inline-block">
                                    <button data-dropdown-toggle="courier-actions-dropdown-{{ $courier->id }}"
                                        class="px-2 py-1 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:ring-2 focus:ring-gray-100 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <div id="courier-actions-dropdown-{{ $courier->id }}"
                                        class="z-50 hidden bg-white divide-y divide-gray-100 rounded shadow w-44 dark:bg-gray-700 dark:divide-gray-600">
                                        <ul class="py-1 text-sm text-gray-700 dark:text-gray-200"
                                            aria-labelledby="courier-actions-button-{{ $courier->id }}">
                                            <li>
                                                <button type="button"
                                                    data-modal-target="edit-courier-modal-{{ $courier->id }}"
                                                    data-modal-toggle="edit-courier-modal-{{ $courier->id }}"
                                                    class="flex items-center w-full px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-600">
                                                    <span class="inline-block w-6 mr-2 text-center"><i
                                                            class="fas fa-edit"></i></span>
                                                    <span>Edit</span>
                                                </button>
                                            </li>
                                            <li>
                                                <button type="button"
                                                    data-modal-target="note-courier-modal-{{ $courier->id }}"
                                                    data-modal-toggle="note-courier-modal-{{ $courier->id }}"
                                                    class="flex items-center w-full px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-600">
                                                    <span class="inline-block w-6 mr-2 text-center"><i
                                                            class="fas fa-sticky-note"></i></span>
                                                    <span>Note</span>
                                                </button>
                                            </li>
                                            <li>
                                                <button type="button"
                                                    class="flex items-center w-full px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-600">
                                                    <span class="inline-block w-6 mr-2 text-center">
                                                        <i class="fas fa-chart-line"></i></span>
                                                    <span>Performa</span>
                                                </button>
                                            </li>
                                        </ul>
                                        <div class="py-1">
                                            <button type="button"
                                                data-modal-target="delete-courier-modal-{{ $courier->id }}"
                                                data-modal-toggle="delete-courier-modal-{{ $courier->id }}"
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
                        <tr class="border-b dark:border-gray-700">
                            <td colspan="6" class="px-4 py-3 text-center text-gray-500">Belum ada data kurir di region
                                ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <nav class="flex justify-center md:justify-end w-full p-4" aria-label="Table navigation">
            {{ $couriers->links() }}
        </nav>
    </div>

@endsection


@push('modals')
    {{-- Memanggil modal tambah kurir --}}
    @include('dashboard.admin.couriers.create')

    {{-- Loop untuk memanggil modal lainnya untuk setiap kurir --}}
    @foreach ($couriers as $courier)
        @include('dashboard.admin.couriers.edit', ['courier' => $courier])
        @include('dashboard.admin.couriers.note', ['courier' => $courier])
        @include('dashboard.admin.couriers.delete', ['courier' => $courier])
    @endforeach
@endpush


@push('scripts')
    {{-- Skrip untuk validasi password dan fungsionalitas lainnya --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // --- Validasi untuk Form Tambah Kurir ---
            const createForm = document.forms['createCourierForm'];
            if (createForm) {
                createForm.addEventListener('submit', function(event) {
                    const password = createForm.elements['password'];
                    const passwordConfirmation = createForm.elements['password_confirmation'];
                    const errorElement = document.getElementById('create_password_error');

                    password.classList.remove('border-red-500');
                    passwordConfirmation.classList.remove('border-red-500');
                    errorElement.classList.add('hidden');

                    if (password.value !== passwordConfirmation.value) {
                        event.preventDefault();
                        errorElement.classList.remove('hidden');
                        password.classList.add('border-red-500');
                        passwordConfirmation.classList.add('border-red-500');
                    }
                });
            }

            // --- Validasi untuk Semua Form Edit Kurir ---
            document.querySelectorAll('[id^="edit-courier-modal-"]').forEach(modal => {
                const form = modal.querySelector('form');
                if (form) {
                    form.addEventListener('submit', function(event) {
                        const courierId = modal.id.split('-').pop();
                        const password = document.getElementById(`password-${courierId}`);
                        const passwordConfirmation = document.getElementById(
                            `password_confirmation-${courierId}`);
                        const errorElement = document.getElementById(
                            `edit_password_error-${courierId}`);

                        password.classList.remove('border-red-500');
                        passwordConfirmation.classList.remove('border-red-500');
                        if (errorElement) errorElement.classList.add('hidden');

                        if (password.value !== '' && password.value !== passwordConfirmation
                            .value) {
                            event.preventDefault();
                            if (errorElement) errorElement.classList.remove('hidden');
                            password.classList.add('border-red-500');
                            passwordConfirmation.classList.add('border-red-500');
                        }
                    });
                }
            });

            // --- Fungsi untuk Toggle Password Visibility ---
            document.querySelectorAll('.toggle-password').forEach(button => {
                button.addEventListener('click', function() {
                    const input = this.previousElementSibling;
                    const icon = this.querySelector('i');
                    if (input.type === 'password') {
                        input.type = 'text';
                        icon.classList.remove('fa-eye');
                        icon.classList.add('fa-eye-slash');
                    } else {
                        input.type = 'password';
                        icon.classList.remove('fa-eye-slash');
                        icon.classList.add('fa-eye');
                    }
                });
            });

            // --- Logika untuk membuka modal berdasarkan error validasi dari session ---
            const errorModalId = '{{ session('error_modal_id') }}';
            if (errorModalId) {
                const modal = document.getElementById(errorModalId);
                if (modal) {
                    const modalInstance = new Modal(modal);
                    modalInstance.show();
                    return; // Hentikan eksekusi agar tidak membuka modal create juga
                }
            }

            // Logika untuk membuka modal CREATE jika ada error validasi di form 'create'
            @if ($errors->hasBag('create'))
                const createModal = document.getElementById('create-courier-modal');
                if (createModal) {
                    const modalInstance = new Modal(createModal);
                    modalInstance.show();
                }
            @endif
        });
    </script>
@endpush
