@extends('layouts.argon')
@section('title', 'Manajemen Kurir')
@section('page_title', 'Kurir')

@section('content')
    <div class="relative min-h-[715px] bg-white shadow-md dark:bg-gray-800 sm:rounded-lg">
        <div class="flex flex-col items-center justify-between p-4 space-y-3 md:flex-row md:space-y-0 md:space-x-4">
            <div class="w-full md:w-1/2">
                <form class="flex items-center" onsubmit="return false;">
                    <label for="courier-search-input" class="sr-only">Cari Kurir</label>
                    <div class="relative w-full">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <svg aria-hidden="true" class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <input type="text" id="courier-search-input" name="search"
                            class="block w-full p-2 pl-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                            placeholder="Cari berdasarkan nama atau email">
                    </div>
                </form>
            </div>
            <div class="flex flex-col items-stretch justify-end flex-shrink-0 w-full space-y-2 md:w-auto md:flex-row md:space-y-0 md:items-center md:space-x-3">
                <button type="button" data-target-modal="create-courier-modal"
                    class="flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-blue-700 rounded-lg js-open-modal-btn hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800">
                    <svg class="h-3.5 w-3.5 mr-2" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path clip-rule="evenodd" fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" />
                    </svg>
                    Tambah Kurir
                </button>
            </div>
        </div>
        <div class="overflow-x-auto min-h-[580px]">
            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-center">No.</th>
                        <th scope="col" class="px-4 py-3">Nama Kurir</th>
                        <th scope="col" class="px-4 py-3 text-center">Email</th>
                        <th scope="col" class="px-4 py-3 text-center">Region</th>
                        <th scope="col" class="px-4 py-3 text-center">Note</th>
                        <th scope="col" class="px-4 py-3 text-center">Tanggal Bergabung</th>
                        <th scope="col" class="px-4 py-3 text-center"><span class="sr-only">Aksi</span></th>
                    </tr>
                </thead>
                <tbody id="courier-results-container">
                    @include('dashboard.admin.couriers._table_rows', ['couriers' => $couriers])
                </tbody>
            </table>
        </div>
        <nav class="flex justify-center w-full p-4 md:justify-end" aria-label="Table navigation">
            {{ $couriers->links() }}
        </nav>
    </div>
@endsection

@push('flowbite-modals')
    @include('dashboard.admin.couriers.create')
    @foreach ($couriers as $courier)
        @include('dashboard.admin.couriers.edit', ['courier' => $courier])
        @include('dashboard.admin.couriers.note', ['courier' => $courier])
        @include('dashboard.admin.couriers.delete', ['courier' => $courier])
    @endforeach
@endpush

@push('page-scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            initializeLiveSearch({
                searchInputId: 'courier-search-input',
                desktopContainerId: 'courier-results-container',
            });

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
                        const passwordConfirmation = document.getElementById(`password_confirmation-${courierId}`);
                        const errorElement = document.getElementById(`edit_password_error-${courierId}`);

                        password.classList.remove('border-red-500');
                        passwordConfirmation.classList.remove('border-red-500');
                        if (errorElement) errorElement.classList.add('hidden');

                        if (password.value !== '' && password.value !== passwordConfirmation.value) {
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
        });
    </script>
@endpush