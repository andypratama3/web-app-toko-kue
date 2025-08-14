@extends('layouts.argon')
@section('title', 'Dashboard Kurir')
@section('content')

    <div class="flex-auto p-4">
        {{-- FORM UTAMA UNTUK DATA PESANAN --}}
        {{-- Action diset ke endpoint API. Submission dihandle oleh JavaScript (Fetch API). --}}
        {{-- Pastikan route 'orders.checkout' ada di backend Laravel Anda. --}}
        <form class="p-0 m-0" action="{{ route('kurir.orders.checkout') }}" method="POST" id="order-form">
            @csrf {{-- Tambahkan token CSRF untuk keamanan Laravel --}}

            <!-- MAIN LAYOUT: TWO COLUMNS FOR XL SCREENS, STACKED FOR SMALLER -->
            <div class="flex flex-col xl:flex-row -mx-7">
                {{-- KOLOM KIRI: DETAIL PRODUK --}}
                {{-- Di mobile: order-2 (tengah), Di desktop: order-1 (kiri) --}}
                <div class="order-2 w-full max-w-full px-3 mt-4 mb-12 shrink-0 xl:w-7/12 xl:flex-0 xl:order-1 xl:mb-0">
                    <div
                        class="p-3 bg-white border border-gray-200 shadow-md rounded-xl dark:bg-gray-800 dark:border-gray-700">
                        <p class="mb-4 text-sm leading-normal uppercase dark:text-white dark:opacity-60">Detail Produk</p>
                        <div class="flex justify-end mb-4">
                            <button type="button" onclick="showProdukModal()" toggle="produk-modal"
                                class="bg-[#345c7c] text-white px-6 py-2 rounded hover:bg-[#2a4964] transition">
                                + Tambah Produk
                            </button>
                        </div>

                        <!-- Modal Pilihan Produk (mengikuti komponen modal-custom) -->
                        {{-- <div id="produkModal" tabindex="-1" aria-hidden="true"
                            class="fixed inset-0 top-0 left-0 right-0 z-50 flex items-center justify-center hidden w-full h-full overflow-x-hidden overflow-y-auto transition-opacity duration-300 bg-black bg-opacity-50">
                            <div
                                class="relative w-full max-w-2xl p-4 transition-transform duration-300 ease-out transform scale-95">
                                <div class="relative bg-white rounded-lg shadow dark:bg-gray-700">
                                    <!-- Modal header -->
                                    <div
                                        class="flex items-center justify-between p-4 border-b border-gray-200 rounded-t md:p-5 dark:border-gray-600">
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                            Pilih Produk
                                        </h3>
                                        <button type="button" onclick="hideProdukModal()"
                                            class="inline-flex items-center justify-center w-8 h-8 text-sm text-gray-400 bg-transparent rounded-lg hover:bg-gray-200 hover:text-gray-900 ms-auto dark:hover:bg-gray-600 dark:hover:text-white">
                                            <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                                fill="none" viewBox="0 0 14 14">
                                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                                    stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                                            </svg>
                                            <span class="sr-only">Close modal</span>
                                        </button>
                                    </div>
                                    <!-- Modal body, scrollable -->
                                    <div class="p-4 md:p-5 max-h-[60vh] overflow-y-auto">
                                        <div id="pilihan-produk" class="space-y-2"></div>
                                    </div>
                                </div>
                            </div>
                        </div> --}}

                        <!-- Judul Kolom untuk Desktop -->
                        <div class="justify-between hidden px-4 py-2 font-bold text-black border-b border-gray-300 md:flex">
                            <p class=" ml-28"></p>
                            <p class="w-1/4">Nama Produk</p>
                            <p class="w-1/6 -ml-4 text-center">Qty</p>
                            <p class="w-1/6 text-center">Total</p>
                            <p class="w-1/6 text-center">Aksi</p>
                        </div>

                        <!-- Cart -->
                        <div id="cart-list" class="min-h-[200px]"></div> {{-- Tambahkan min-height agar tidak kosong --}}
                    </div>
                </div>

                {{-- KOLOM KANAN --}}
                {{-- Di mobile: order-1 untuk Data Customer, order-3 untuk Pembayaran/Catatan --}}
                {{-- Di desktop: order-2 untuk kedua box (berada di kolom kanan) --}}
                <div class="order-1 w-full max-w-full px-3 shrink-0 xl:w-5/12 xl:flex-0 xl:order-2 xl:mt-4 xl:mt-0 xl:mr-4">
                    {{-- Box 1 Kanan: DATA CUSTOMER (Nama, No HP, Alamat) --}}
                    <div
                        class="p-3 mb-4 bg-white border border-gray-200 shadow-md rounded-xl dark:bg-gray-800 dark:border-gray-700">
                        <p class="mb-4 text-sm leading-normal uppercase dark:text-white dark:opacity-60">Data Customer</p>

                        <!-- NAMA CUSTOMER (CUSTOM DROPDOWN WITH SEARCH) -->
                        <div class="relative w-full mb-4 group">
                            <label for="search-input"
                                class="inline-block mb-2 ml-1 text-xs font-bold text-slate-700 dark:text-white/80">Nama
                                Customer</label>
                            <div class="relative">
                                <button id="dropdown-button" type="button"
                                    class="border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-left">
                                    <span id="selected-customer">- Pilih Customer -</span>
                                    <svg class="absolute w-5 h-5 ml-2 -mr-1 -translate-y-1/2 right-3 top-1/2"
                                        xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                                        aria-hidden="true">
                                        <path fill-rule="evenodd"
                                            d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </button>

                                <div id="dropdown-menu"
                                    class="absolute z-10 hidden w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg dark:bg-gray-700">
                                    <div class="p-2">
                                        <input type="text" id="search-input"
                                            class="w-full p-2 border border-gray-300 rounded-lg focus:outline-none dark:bg-gray-600 dark:border-gray-500 dark:text-white"
                                            placeholder="Cari customer...">
                                    </div>

                                    <ul id="customer-list" class="overflow-y-auto max-h-60">
                                        @foreach ($customers as $customer)
                                            <li>
                                                <a href="#"
                                                    class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white"
                                                    data-value="{{ $customer->id }}" data-phone="{{ $customer->phone }}"
                                                    data-address="{{ $customer->address }}">
                                                    {{ $customer->name }}
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                            <input type="hidden" name="customer_id" id="customer-id-input">
                        </div>

                        <!-- NO HP -->
                        <div class="mb-4">
                            <label for="phone"
                                class="inline-block mb-2 ml-1 text-xs font-bold text-slate-700 dark:text-white/80">No.
                                HP</label>
                            <input type="text" name="phone" id="phone"
                                class="mb-6 bg-gray-100 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 cursor-not-allowed"
                                disabled>
                        </div>

                        <!-- ADDRESS -->
                        <div class="mb-4">
                            <label for="address"
                                class="inline-block mb-2 ml-1 text-xs font-bold text-slate-700 dark:text-white/80">Alamat</label>
                            <textarea id="address" name="address"
                                class="mb-6 bg-gray-100 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 cursor-not-allowed"
                                disabled></textarea>
                        </div>
                    </div>

                    {{-- Box 2 Kanan: METODE PEMBAYARAN & CATATAN --}}
                    <div
                        class="p-3 mt-4 bg-white border border-gray-200 shadow-md rounded-xl dark:bg-gray-800 dark:border-gray-700">
                        <p class="mb-4 text-sm leading-normal uppercase dark:text-white dark:opacity-60">Detail Pembayaran &
                            Catatan</p>

                        <!-- METODE BAYAR (CUSTOM DROPDOWN) -->
                        <div class="mb-4">
                            <label for="payment-method-input"
                                class="inline-block mb-2 ml-1 text-xs font-bold text-slate-700 dark:text-white/80">Metode
                                Pembayaran</label>
                            <div class="relative inline-block w-full text-left">
                                <button id="payment-method-button" type="button"
                                    class="inline-flex justify-between items-center w-full rounded-lg border border-gray-300 shadow-sm px-4 py-2.5 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-100 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:bg-gray-600">
                                    <span id="selected-payment-method">-Pilih metode pembayaran -</span>
                                    <svg class="w-5 h-5 ml-2 -mr-1" xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd"
                                            d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </button>
                                <div id="payment-method-menu"
                                    class="absolute left-0 hidden w-full mt-1 bg-white shadow-lg rounded-xl ring-1 ring-black ring-opacity-5 focus:outline-none dark:bg-gray-700"
                                    role="menu">
                                    <div class="py-2" role="none">
                                        <a href="#"
                                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600"
                                            data-value="cash" role="menuitem">Cash (Tunai)</a>
                                        <a href="#"
                                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600"
                                            data-value="tf" role="menuitem">Transfer Bank</a>
                                        <a href="#"
                                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600"
                                            data-value="qr" role="menuitem">QRIS</a>
                                    </div>
                                </div>
                                <input type="hidden" id="payment-method-input" name="payment_method_selected">
                                {{-- Ini akan digunakan oleh JS --}}
                            </div>
                        </div>

                        <!-- Form Upload Bukti Pembayaran (Kondisional) -->
                        <div id="payment-proof-upload" class="hidden mb-4">
                            <label for="payment-proof"
                                class="inline-block mb-2 ml-1 text-xs font-bold text-slate-700 dark:text-white/80">Bukti
                                Pembayaran</label>
                            <input type="file" name="payment_proof" id="payment-proof" accept="image/*"
                                class="focus:shadow-primary-outline dark:bg-slate-850 dark:text-white text-sm leading-5.6 ease block w-full appearance-none rounded-lg border border-solid border-gray-300 bg-white bg-clip-padding px-3 py-2 font-normal text-gray-700 outline-none transition-all placeholder:text-gray-500 focus:border-blue-500 focus:outline-none">
                            <p class="mt-1 text-xs text-gray-500">Format: JPG, PNG. Ukuran maksimal: 2MB.</p>
                        </div>

                        <!-- NOTE -->
                        <div class="mb-4">
                            <label for="note"
                                class="inline-block mb-2 ml-1 text-xs font-bold text-slate-700 dark:text-white/80">Catatan</label>
                            <textarea type="text" name="note" id="note"
                                class="focus:shadow-primary-outline dark:bg-slate-850 dark:text-white text-sm leading-5.6 ease block w-full appearance-none rounded-lg border border-solid border-gray-300 bg-white bg-clip-padding px-3 py-2 font-normal text-gray-700 outline-none transition-all placeholder:text-gray-500 focus:border-blue-500 focus:outline-none"></textarea>
                        </div>

                        <!-- BUTTON CHECKOUT UNTUK WEBSITE (TAMBAHAN) -->
                        <div class="hidden w-full max-w-full mt-4 shrink-0 md:w-full md:flex-0 xl:block">
                            <div class="flex justify-end">
                                <button type="button" onclick="checkout()"
                                    class="w-full max-w-full bg-[#748c54] text-white px-6 py-2 rounded-xl hover:bg-[#5a6e40] transition shadow-md">
                                    Checkout
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </div> {{-- Penutup flex kolom kiri dan kanan --}}


        </form> {{-- PENUTUP FORM --}}
    </div>
    </div>

    <!-- Tombol Checkout Fix di Bawah (UNTUK MOBILE) -->
    <div
        class="fixed bottom-0 left-0 z-50 flex items-center justify-between w-full p-4 bg-white border-t border-gray-300 xl:hidden">
        <p id="cart-total" class="text-lg font-bold">Total: Rp 0</p>
        <button type="button" onclick="checkout()"
            class="bg-[#748c54] text-white px-6 py-2 rounded-xl hover:bg-[#5a6e40] transition shadow-md">
            Checkout
        </button>
    </div>

    <!-- Modal Sukses Tersimpan -->
    <div id="successModal" class="fixed inset-0 z-50 flex items-center justify-center hidden bg-black bg-opacity-50">
        <div class="w-full max-w-sm p-6 text-center bg-white rounded-lg shadow-lg">
            <div class="mb-4 text-5xl text-green-500">✔</div>
            <h2 class="mb-2 text-xl font-bold">Sukses!</h2>
            <p id="success-message" class="mb-4 text-gray-700">Pesanan berhasil disimpan.</p>
            <button onclick="hideSuccessModal()"
                class="px-4 py-2 text-white transition bg-green-500 rounded hover:bg-green-600">Tutup</button>
        </div>
    </div>

    <!-- Elemen Toast Notifikasi -->
    <div id="toast-notification"
        class="hidden fixed top-8 right-4 bg-white text-gray-800 px-4 py-3 rounded-lg shadow-lg z-[60] text-sm transform transition-all duration-300 ease-out flex items-center space-x-3 min-w-[250px]">
        <div id="toast-icon-wrapper" class="flex-shrink-0">
            <!-- Ikon akan disuntikkan di sini oleh JavaScript -->
        </div>
        <span id="toast-message" class="flex-grow"></span>
        <button onclick="hideToast()" class="flex-shrink-0 ml-auto text-gray-500 hover:text-gray-700">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>

    @push('flowbite-modals')
    {{-- Memanggil modal tambah kurir --}}
    @include('dashboard.kurir.pesanan.produk-modal')
    @endpush
    {{-- Semua JavaScript terkait fungsionalitas aplikasi berada di sini --}}
    <script>
        // --- Data dan State Global ---
        let produkList = []; // Daftar semua produk yang tersedia
        let cart = []; // Keranjang belanja

        // --- Fungsi Toast Notifikasi ---
        let toastTimeout; // Untuk menyimpan ID timeout agar bisa dibersihkan

        function showToast(message, type = 'info', duration = 3000) {
            const toast = document.getElementById('toast-notification');
            const toastMessage = document.getElementById('toast-message');
            const toastIconWrapper = document.getElementById('toast-icon-wrapper');

            // Reset kelas background dan ikon
            toast.classList.remove('bg-white', 'border-green-400', 'border-red-400', 'border-orange-400',
            'border-blue-400'); // Tambahkan border-blue-400
            toastIconWrapper.innerHTML = ''; // Kosongkan ikon sebelumnya

            let iconSvg = '';
            let borderColorClass = '';

            if (type === 'success') {
                borderColorClass = 'border-green-400';
                iconSvg =
                    `<svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>`;
            } else if (type === 'error') {
                borderColorClass = 'border-red-400';
                iconSvg =
                    `<svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>`;
            } else if (type === 'warning') {
                borderColorClass = 'border-orange-400';
                iconSvg =
                    `<svg class="w-6 h-6 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>`;
            } else { // Default to info
                borderColorClass = 'border-blue-400';
                iconSvg =
                    `<svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>`;
            }

            toastIconWrapper.innerHTML = iconSvg;
            toast.classList.add('bg-white', borderColorClass, 'border'); // Tambahkan border sebagai pemisah visual
            toastMessage.textContent = message;

            // Atur posisi agar sedikit lebih ke bawah
            toast.style.right = '1rem';
            toast.style.top = '4rem';

            toast.classList.remove('hidden'); // Tampilkan toast

            // Bersihkan timeout sebelumnya jika ada
            if (toastTimeout) {
                clearTimeout(toastTimeout);
            }

            // Sembunyikan toast setelah durasi tertentu
            toastTimeout = setTimeout(() => {
                hideToast();
            }, duration);
        }

        function hideToast() {
            const toast = document.getElementById('toast-notification');
            toast.classList.add('hidden'); // Sembunyikan toast
        }

        // --- Inisialisasi DOM dan Event Listeners ---
        document.addEventListener('DOMContentLoaded', function() {
            // --- Logic Dropdown Customer (Searchable) ---
            const customerDropdownButton = document.getElementById('dropdown-button');
            const customerDropdownMenu = document.getElementById('dropdown-menu');
            const searchInput = document.getElementById('search-input');
            const selectedCustomerSpan = document.getElementById('selected-customer');
            const hiddenCustomerIdInput = document.getElementById('customer-id-input');
            const phoneInput = document.getElementById('phone');
            const addressInput = document.getElementById('address');
            const customerList = document.getElementById('customer-list');

            // Toggle visibilitas dropdown pelanggan
            customerDropdownButton.addEventListener('click', function(e) {
                e.stopPropagation();
                customerDropdownMenu.classList.toggle('hidden');
                searchInput.focus();
            });

            // Filter daftar pelanggan berdasarkan input pencarian
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                Array.from(customerList.children).forEach(li => {
                    const customerName = li.textContent.toLowerCase();
                    if (customerName.includes(searchTerm)) {
                        li.style.display = 'block';
                    } else {
                        li.style.display = 'none';
                    }
                });
            });

            // Tangani pemilihan pelanggan dari daftar
            customerList.addEventListener('click', function(e) {
                if (e.target.tagName === 'A') {
                    e.preventDefault();
                    const selectedLink = e.target;

                    // Ambil data pelanggan dari atribut 'data-' pada link
                    const customerId = selectedLink.getAttribute('data-value');
                    const customerName = selectedLink.textContent.trim();
                    const phone = selectedLink.getAttribute('data-phone');
                    const address = selectedLink.getAttribute('data-address');

                    // Perbarui teks yang terlihat pada tombol dropdown dan nilai input tersembunyi
                    selectedCustomerSpan.textContent = customerName;
                    hiddenCustomerIdInput.value = customerId;

                    // Isi kolom nomor telepon dan alamat
                    phoneInput.value = phone;
                    addressInput.value = address;

                    // Sembunyikan menu dropdown setelah pemilihan
                    customerDropdownMenu.classList.add('hidden');
                }
            });

            // --- Logic Dropdown Metode Pembayaran (Custom) ---
            const paymentButton = document.getElementById('payment-method-button');
            const paymentMenu = document.getElementById('payment-method-menu');
            const selectedPaymentText = document.getElementById('selected-payment-method');
            const hiddenPaymentInput = document.getElementById('payment-method-input');
            const paymentProofUploadDiv = document.getElementById('payment-proof-upload');

            // Toggle visibilitas dropdown metode pembayaran
            paymentButton.addEventListener('click', function(e) {
                e.stopPropagation();
                paymentMenu.classList.toggle('hidden');
            });

            // Tangani pemilihan metode pembayaran dari daftar
            paymentMenu.addEventListener('click', function(e) {
                if (e.target.tagName === 'A') {
                    e.preventDefault();
                    const value = e.target.getAttribute('data-value');
                    const text = e.target.textContent;

                    // Perbarui teks yang terlihat pada tombol dropdown dan nilai input tersembunyi
                    selectedPaymentText.textContent = text;
                    hiddenPaymentInput.value = value;

                    // Tampilkan/sembunyikan form upload bukti pembayaran
                    if (value === 'tf' || value === 'qr') {
                        paymentProofUploadDiv.classList.remove('hidden');
                    } else {
                        paymentProofUploadDiv.classList.add('hidden');
                        document.getElementById('payment-proof').value =
                        ''; // Kosongkan input file jika disembunyikan
                    }

                    // Sembunyikan menu dropdown setelah pemilihan
                    paymentMenu.classList.add('hidden');
                }
            });

            // --- Menutup semua dropdown saat mengklik di luar area dropdown ---
            document.addEventListener('click', function(e) {
                // Untuk dropdown pelanggan
                if (!customerDropdownButton.contains(e.target) && !customerDropdownMenu.contains(e
                    .target)) {
                    customerDropdownMenu.classList.add('hidden');
                }
                // Untuk dropdown metode pembayaran
                if (!paymentButton.contains(e.target) && !paymentMenu.contains(e.target)) {
                    paymentMenu.classList.add('hidden');
                }
            });

            // --- Inisialisasi Produk dan Keranjang ---
            getProduk();
        });

        // --- Fungsi Checkout ---
        async function checkout() {
            // Mendapatkan data dari form
            const customerId = document.getElementById('customer-id-input').value;
            const paymentMethod = document.getElementById('payment-method-input').value;
            const note = document.getElementById('note').value;
            const phone = document.getElementById('phone').value; // Hanya ada satu input phone sekarang
            const address = document.getElementById('address').value;
            const paymentProofFile = document.getElementById('payment-proof').files[0];

            // Validasi data sebelum checkout
            if (!customerId) {
                showToast('Silakan pilih customer terlebih dahulu.', 'error');
                return;
            }

            if (cart.length === 0) {
                showToast('Keranjang belanja kosong. Tambahkan produk terlebih dahulu.', 'error');
                return;
            }

            if (!paymentMethod || paymentMethod === "-Pilih metode pembayaran -") {
                showToast('Silakan pilih metode pembayaran.', 'error');
                return;
            }

            // Validasi bukti pembayaran jika metode transfer/QRIS
            if ((paymentMethod === 'tf' || paymentMethod === 'qr') && !paymentProofFile) {
                showToast('Silakan unggah bukti pembayaran.', 'error');
                return;
            }

            // Siapkan data pesanan
            let requestBody;
            let headers = {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            };

            if (paymentProofFile) {
                // Gunakan FormData jika ada file yang diupload
                const formData = new FormData();
                formData.append('customer_id', customerId);
                formData.append('phone', phone);
                formData.append('address', address);
                formData.append('payment_method', paymentMethod);
                formData.append('note', note);
                formData.append('payment_proof', paymentProofFile);
                formData.append('products', JSON.stringify(cart.map(item => ({ // Stringify products array
                    product_id: item.id,
                    product_name: item.nama,
                    quantity: item.qty,
                    price: item.harga,
                }))));
                requestBody = formData;
                // Hapus Content-Type header karena FormData akan mengaturnya ke multipart/form-data
                // delete headers['Content-Type']; // Tidak perlu dihapus secara eksplisit, browser akan mengaturnya
            } else {
                // Gunakan JSON jika tidak ada file yang diupload
                requestBody = JSON.stringify({
                    customer_id: customerId,
                    phone: phone,
                    address: address,
                    payment_method: paymentMethod,
                    note: note,
                    products: cart.map(item => ({
                        product_id: item.id,
                        product_name: item.nama,
                        quantity: item.qty,
                        price: item.harga,
                    }))
                });
                headers['Content-Type'] = 'application/json';
            }

            try {
                // Mengirim data ke API
                const response = await fetch('/api/orders/checkout', {
                    method: 'POST',
                    headers: headers, // Gunakan headers yang sudah disiapkan
                    body: requestBody
                });

                const result = await response.json();

                if (response.ok) {
                    showSuccessModal(result.message);
                    // Reset form setelah berhasil checkout
                    cart = [];
                    renderCart();
                    // Reset tampilan dropdown pelanggan
                    document.getElementById('selected-customer').textContent = '- Pilih Customer -';
                    document.getElementById('customer-id-input').value = '';
                    document.getElementById('phone').value = '';
                    document.getElementById('address').value = '';
                    // Reset tampilan dropdown metode pembayaran
                    document.getElementById('selected-payment-method').textContent = '-Pilih metode pembayaran -';
                    document.getElementById('payment-method-input').value = '';
                    document.getElementById('payment-proof').value = ''; // Kosongkan input file
                    document.getElementById('payment-proof-upload').classList.add('hidden'); // Sembunyikan lagi
                    document.getElementById('note').value = '';
                } else {
                    showToast('Gagal menyimpan pesanan: ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Terjadi kesalahan saat checkout. Mohon coba lagi.', 'error');
            }
        }

        // --- Logic Produk dan Keranjang ---

        // Mengambil daftar produk dari API backend
        async function getProduk() {
            try {
                const res = await fetch("{{ url('kurir/produk/json') }}");
                produkList = await res.json();
                tampilkanPilihanProduk();
            } catch (err) {
                console.error("Gagal ambil produk:", err);
                showToast('Gagal memuat daftar produk.', 'error');
            }
        }

        // Menampilkan modal pilihan produk
        function showProdukModal() {
            tampilkanPilihanProduk();
            document.getElementById('produkModal').classList.remove('hidden');
        }

        // Menyembunyikan modal pilihan produk
        function hideProdukModal() {
            document.getElementById('produkModal').classList.add('hidden');
        }

        // Menampilkan pilihan produk di dalam modal
        function tampilkanPilihanProduk() {
            const pilihDiv = document.getElementById('pilihan-produk');
            pilihDiv.innerHTML = '';
            produkList.forEach(p => {
                // Ambil URL gambar produk jika ada (misal: p.image atau p.foto)
                let imageUrl = p.image || p.foto || null;
                if (p.variants && p.variants.length > 0) {
                    p.variants.forEach(v => {
                        const sudahDipilih = cart.some(c => c.variant_id === v.id);
                        pilihDiv.innerHTML += `
                        <div class="flex flex-row items-center gap-3 p-3 border rounded bg-gray-50">
                            ${imageUrl ? `<img src="${imageUrl}" alt="${p.name}" class="object-cover w-16 h-16 mr-2 border rounded" />` : ''}
                            <div class="flex-1">
                                <div class="font-semibold">${p.name} <span class="text-xs text-gray-500">${v.name ? ' - ' + v.name : ''}</span></div>
                                <div class="font-bold text-green-700">Rp ${v.price.toLocaleString()}</div>
                            </div>
                            <button type="button" class="px-2 py-1 text-xs text-white bg-blue-600 rounded hover:bg-blue-700" onclick="tambahKeCart(${p.id}, ${v.id})" ${sudahDipilih ? 'disabled' : ''}>${sudahDipilih ? 'Sudah di keranjang' : 'Tambah'}</button>
                        </div>
                    `;
                    });
                } else {
                    const sudahDipilih = cart.some(c => c.product_id === p.id);
                    pilihDiv.innerHTML += `
                    <div class="flex flex-row items-center gap-3 p-3 border rounded bg-gray-50">
                        ${imageUrl ? `<img src="${imageUrl}" alt="${p.name}" class="object-cover w-16 h-16 mr-2 border rounded" />` : ''}
                        <div class="flex-1">
                            <div class="font-semibold">${p.name}</div>
                            <div class="font-bold text-green-700">Rp ${p.price ? p.price.toLocaleString() : ''}</div>
                        </div>
                        <button type="button" class="px-2 py-1 text-xs text-white bg-blue-600 rounded hover:bg-blue-700" onclick="tambahKeCart(${p.id}, null)" ${sudahDipilih ? 'disabled' : ''}>${sudahDipilih ? 'Sudah di keranjang' : 'Tambah'}</button>
                    </div>
                `;
                }
            });
        }

        // Menambahkan produk ke keranjang atau menambah kuantitasnya
        function tambahKeCart(productId, variantId) {
            let product = produkList.find(p => p.id === productId);
            let variant = null;
            let cartItem = null;
            if (variantId) {
                variant = product.variants.find(v => v.id === variantId);
                cartItem = cart.find(c => c.variant_id === variantId);
            } else {
                cartItem = cart.find(c => c.product_id === productId && !c.variant_id);
            }
            if (cartItem) {
                cartItem.qty++;
                showToast(
                    `Kuantitas ${product.name}${variant ? ' - ' + variant.name : ''} ditambahkan menjadi ${cartItem.qty}.`,
                    'info');
            } else {
                cart.push({
                    product_id: productId,
                    product_name: product.name,
                    variant_id: variantId || null,
                    variant_name: variant ? variant.name : null,
                    price: variant ? variant.price : (product.price || product.harga),
                    qty: 1
                });
                showToast(`${product.name}${variant ? ' - ' + variant.name : ''} ditambahkan ke keranjang.`, 'success');
            }
            renderCart();
            tampilkanPilihanProduk();
            hideProdukModal();
        }

        // Mengubah kuantitas produk di keranjang
        function ubahQty(id, change) {
            const item = cart.find(p => p.id === id);
            item.qty += change;
            if (item.qty < 1) {
                item.qty = 1;
                showToast('Kuantitas tidak bisa kurang dari 1.', 'warning');
            }
            renderCart();
        }

        // Menghapus produk dari keranjang
        function hapusProduk(id) {
            const itemToRemove = cart.find(p => p.id === id);
            cart = cart.filter(p => p.id !== id);
            renderCart();
            tampilkanPilihanProduk();
            showToast(`${itemToRemove.nama} dihapus dari keranjang.`, 'info');
        }

        // Merender (menampilkan) isi keranjang
        function renderCart() {
            const cartDiv = document.getElementById('cart-list');
            cartDiv.innerHTML = '';
            let total = 0;
            cart.forEach((item, idx) => {
                const subtotal = item.qty * item.price;
                total += subtotal;
                cartDiv.innerHTML += `
                <div class="relative flex flex-row items-start gap-4 p-4 mb-2 border rounded">
                    <div class="flex-1">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                            <div>
                                <p class="font-bold text-black">${item.product_name}${item.variant_name ? ' - ' + item.variant_name : ''}</p>
                                <p class="text-gray-500">Rp ${item.price.toLocaleString()}</p>
                            </div>
                            <div class="flex items-center gap-2 mt-2 md:mt-0">
                                <button type="button" onclick="ubahQtyCart(${idx}, -1)" class="px-2 text-black bg-gray-200 rounded">–</button>
                                <span class="text-black">${item.qty}</span>
                                <button type="button" onclick="ubahQtyCart(${idx}, 1)" class="px-2 text-black bg-gray-200 rounded">+</button>
                                <button type="button" onclick="hapusProdukCart(${idx})" class="ml-4 text-xl text-red-500">🗑</button>
                            </div>
                            <div class="mt-2 font-medium text-black md:mt-0">Total Rp ${subtotal.toLocaleString()}</div>
                        </div>
                    </div>
                </div>
            `;
            });
            document.getElementById('cart-total').textContent = `Total: Rp ${total.toLocaleString()}`;
        }

        function ubahQtyCart(idx, change) {
            cart[idx].qty += change;
            if (cart[idx].qty < 1) cart[idx].qty = 1;
            renderCart();
        }

        function hapusProdukCart(idx) {
            cart.splice(idx, 1);
            renderCart();
            tampilkanPilihanProduk();
        }

        // --- Fungsi Modal Sukses ---
        function showSuccessModal(message) {
            document.getElementById('success-message').textContent = message;
            document.getElementById('successModal').classList.remove('hidden');
        }

        function hideSuccessModal() {
            document.getElementById('successModal').classList.add('hidden');
        }
    </script>

    {{-- Script eksternal yang mungkin dibutuhkan oleh layout atau fitur dashboard lainnya --}}
    {{-- <script src="/assets/argon/js/plugins/chartjs.min.js"></script>
<script src="/assets/argon/js/plugins/perfect-scrollbar.min.js" async></script>
<script src="/assets/argon/js/assets/argon-dashboard-tailwind.js?v=1.0.1" async></script> --}}

@endsection
