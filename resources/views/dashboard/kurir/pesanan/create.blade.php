@extends('layouts.argon')
@section('title', 'Tambah Pesanan')
@section('page_title', 'Order')
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
                        <p class="mb-4 font-bold tracking-wide text-black uppercase text-md dark:text-white dark:opacity-60">
                            📦 Detail Produk
                        </p>
                        <div class="flex justify-end mb-4">
                            <button type="button" data-modal-target="produkModal" data-modal-toggle="produkModal"
                                class="bg-[#345c7c] text-white px-6 py-1 rounded hover:bg-[#2a4964] transition">
                                + Tambah Produk
                            </button>
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
                        <p
                            class="mb-4 font-bold tracking-wide text-black uppercase text-md dark:text-white dark:opacity-60">
                            👤 Data Customer
                        </p>

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
                                                    class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:text-white active:scale-90"
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
                                disabled>
                            </textarea>
                        </div>
                    </div>

                    {{-- Box 2 Kanan: METODE PEMBAYARAN & CATATAN --}}
                    <div
                        class="p-3 mt-4 bg-white border border-gray-200 shadow-md rounded-xl dark:bg-gray-800 dark:border-gray-700">
                        <p
                            class="mb-4 font-bold tracking-wide text-black uppercase text-md dark:text-white dark:opacity-60">
                            💳 METODE PEMBAYARAN
                        </p>

                        <!-- METODE BAYAR (CUSTOM DROPDOWN) -->
                        <div class="mb-4">
                            <label for="payment-method-input"
                                class="inline-block mb-2 ml-1 text-xs font-bold text-slate-700 dark:text-white/80">Metode
                                Pembayaran</label>
                            <div class="relative inline-block w-full text-left">
                                <button id="payment-method-button" type="button"
                                    class="inline-flex justify-between items-center w-full rounded-lg border border-gray-300 shadow-sm px-4 py-2.5 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-100 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:bg-gray-600">
                                    <span id="selected-payment-method">-Pilih metode pembayaran -</span>
                                    <svg class="w-5 h-5 ml-2 -mr-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                        fill="currentColor" aria-hidden="true">
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
                                            class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600"
                                            data-value="cash" role="menuitem"><i class="fas fa-money-bill-wave"></i> Cash
                                            (Tunai)</a>
                                        <a href="#"
                                            class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600"
                                            data-value="tf" role="menuitem"><i class="fas fa-money-check"></i> Transfer
                                            Bank</a>
                                        <a href="#"
                                            class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600"
                                            data-value="qr" role="menuitem"><i class="fas fa-qrcode"></i>QRIS</a>
                                    </div>
                                </div>
                                <input type="hidden" id="payment-method-input" name="payment_method">
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
                                class="inline-block mb-2 ml-1 text-xs font-bold text-slate-700 dark:text-white/80">📝
                                Catatan</label>
                            <textarea type="text" name="note" id="note"
                                class="focus:shadow-primary-outline dark:bg-slate-850 dark:text-white text-sm leading-5.6 ease block w-full appearance-none rounded-lg border border-solid border-gray-300 bg-white bg-clip-padding px-3 py-2 font-normal text-gray-700 outline-none transition-all placeholder:text-gray-500 focus:border-blue-500 focus:outline-none"></textarea>
                        </div>

                        <div class="hidden pt-4 mt-4 border-t border-gray-200 dark:border-gray-600 xl:block">
                            <div class="flex items-center justify-between">
                                <span class="text-base font-medium text-gray-900 dark:text-white">Total Pesanan:</span>
                                {{-- Kita beri class 'cart-total-display' untuk target JavaScript --}}
                                <span class="text-xl font-bold text-gray-900 dark:text-white cart-total-display">Rp
                                    0</span>
                            </div>
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
        class="fixed bottom-0 left-0 z-50 flex items-center justify-between w-full p-4 bg-white border-t border-gray-300 dark:bg-gray-800 xl:hidden">
        {{-- Tambahkan class "cart-total-display" di sini --}}
        <p id="cart-total" class="text-lg font-bold cart-total-display dark:text-white">Total: Rp 0</p>
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
            <p id="success-message" class="mb-2 text-gray-700">Pesanan berhasil disimpan.</p>
            <p id="success-invoice" class="p-2 mb-4 text-sm font-semibold text-gray-800 bg-gray-100 rounded"></p>
            <button onclick="hideSuccessModal()"
                class="px-4 py-2 text-white transition bg-green-500 rounded hover:bg-green-600">Tutup</button>
        </div>
    </div>

    @push('flowbite-modals')
        {{-- Memanggil modal tambah produk --}}
        @include('dashboard.kurir.pesanan.produk-modal')

        {{-- BARU: Memanggil modal konfirmasi pesanan --}}
        @include('dashboard.kurir.pesanan.konfirmasi-modal')
    @endpush

    {{-- Semua JavaScript terkait fungsionalitas aplikasi berada di sini --}}
    <script>
        // --- Data dan State Global ---
        let produkList = []; // Daftar semua produk yang tersedia
        let cart = []; // Keranjang belanja

        function showToast(message, type = 'success', duration = 5000) {
            // 1. Hapus toast yang mungkin sudah ada sebelumnya
            const existingToast = document.getElementById('toast-notification-dynamic');
            if (existingToast) {
                existingToast.remove();
            }

            // 2. Tentukan ikon dan style berdasarkan tipe
            let iconSvg, iconBgColor, iconTextColor;
            if (type === 'success') {
                iconBgColor = 'bg-green-100 dark:bg-green-800';
                iconTextColor = 'text-green-500 dark:text-green-200';
                iconSvg =
                    `<svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20"><path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 8.207-4 4a1 1 0 0 1-1.414 0l-2-2a1 1 0 0 1 1.414-1.414L9 10.586l3.293-3.293a1 1 0 0 1 1.414 1.414Z" /></svg>`;
            } else { // 'error'
                iconBgColor = 'bg-red-100 dark:bg-red-800';
                iconTextColor = 'text-red-500 dark:text-red-200';
                iconSvg =
                    `<svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20"><path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 11.793a1 1 0 1 1-1.414 1.414L10 11.414l-2.293 2.293a1 1 0 0 1-1.414-1.414L8.586 10 6.293 7.707a1 1 0 0 1 1.414-1.414L10 8.586l2.293-2.293a1 1 0 0 1 1.414 1.414L11.414 10l2.293 2.293Z" /></svg>`;
            }

            // 3. Buat elemen toast baru dari string HTML
            const toastElement = document.createElement('div');
            toastElement.id = 'toast-notification-dynamic';
            // Style dan class diambil dari toast.blade.php
            toastElement.className =
                'fixed top-5 right-5 w-full max-w-xs p-4 text-gray-900 bg-white rounded-lg shadow-lg dark:bg-gray-800 dark:text-gray-300 z-[100] transition-transform duration-300 ease-out';
            toastElement.setAttribute('role', 'alert');
            toastElement.style.transform = 'translateY(-20px) translateX(20px)'; // Posisi awal untuk animasi
            toastElement.style.opacity = '0';

            toastElement.innerHTML = `
        <div class="flex items-center">
            <div class="inline-flex items-center justify-center flex-shrink-0 w-8 h-8 ${iconTextColor} ${iconBgColor} rounded-lg">
                ${iconSvg}
            </div>
            <div class="text-sm font-normal ms-3">${message}</div>
            <button type="button" onclick="this.parentElement.parentElement.remove()" class="ms-auto -mx-1.5 -my-1.5 bg-white text-gray-400 hover:text-gray-900 rounded-lg focus:ring-2 focus:ring-gray-300 p-1.5 hover:bg-gray-100 inline-flex items-center justify-center h-8 w-8 dark:text-gray-500 dark:hover:text-white dark:bg-gray-800 dark:hover:bg-gray-700" aria-label="Close">
                <span class="sr-only">Close</span>
                <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" /></svg>
            </button>
        </div>
    `;

            // 4. Tambahkan ke body dan animasikan
            document.body.appendChild(toastElement);

            // Animasikan masuk (mirip x-transition)
            setTimeout(() => {
                toastElement.style.transform = 'translateY(0) translateX(0)';
                toastElement.style.opacity = '1';
            }, 10); // Delay kecil agar transisi berjalan

            // 5. Atur timer untuk menghapus toast
            setTimeout(() => {
                // Animasikan keluar
                toastElement.style.transform = 'translateY(-20px) translateX(20px)';
                toastElement.style.opacity = '0';
                // Hapus elemen dari DOM setelah animasi selesai
                setTimeout(() => toastElement.remove(), 300);
            }, duration);
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

        // FUNGSI BARU: Untuk membuka dan mengisi modal konfirmasi
        function showConfirmationModal() {
            // 1. Ambil semua data dari form
            const customerName = document.getElementById('selected-customer').textContent.trim();
            const customerPhone = document.getElementById('phone').value;
            const customerAddress = document.getElementById('address').value;
            const paymentMethodText = document.getElementById('selected-payment-method').textContent.trim();

            // 2. Isi data customer dan pembayaran ke dalam modal
            document.getElementById('modal-customer-name').textContent = customerName;
            document.getElementById('modal-customer-phone').textContent = customerPhone || '-';
            document.getElementById('modal-customer-address').textContent = customerAddress || '-';
            document.getElementById('modal-payment-method').textContent = paymentMethodText;

            // 3. Bangun dan isi daftar produk di dalam modal
            const productListDiv = document.getElementById('modal-product-list');
            productListDiv.innerHTML = ''; // Kosongkan dulu
            let total = 0;

            cart.forEach(item => {
                const subtotal = item.qty * item.price;
                total += subtotal;

                const productHtml = `
                <div class="flex items-start justify-between text-sm">
                    <div class="flex-grow">
                        <p class="font-semibold text-gray-800 dark:text-gray-200">${item.product_name}</p>
                        ${item.variant_name ? `<p class="text-xs text-gray-500">${item.variant_name}</p>` : ''}
                        <p class="text-xs text-gray-600 dark:text-gray-400">${item.qty} x Rp ${item.price.toLocaleString('id-ID')}</p>
                    </div>
                    <p class="font-semibold text-gray-800 dark:text-gray-200">Rp ${subtotal.toLocaleString('id-ID')}</p>
                </div>
            `;
                productListDiv.innerHTML += productHtml;
            });

            // 4. Isi total amount di modal
            document.getElementById('modal-total-amount').textContent = `Rp ${total.toLocaleString('id-ID')}`;

            // 5. Tampilkan modal menggunakan Flowbite instance
            const modalElement = document.getElementById('konfirmasiModal');
            // Pastikan Anda memiliki instance Modal dari Flowbite
            const modal = new Modal(modalElement);
            modal.show();
        }

        // FUNGSI DIUBAH: checkout() sekarang hanya untuk validasi dan memanggil modal
        function checkout() {
            // Validasi data sebelum menampilkan modal
            const customerId = document.getElementById('customer-id-input').value;
            const paymentMethod = document.getElementById('payment-method-input').value;
            const paymentProofFile = document.getElementById('payment-proof').files[0];

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
            if ((paymentMethod === 'tf' || paymentMethod === 'qr') && !paymentProofFile) {
                showToast('Silakan unggah bukti pembayaran.', 'error');
                return;
            }

            // Jika semua validasi lolos, panggil fungsi untuk menampilkan modal
            showConfirmationModal();
        }

        // FUNGSI BARU: untuk mengirim data ke server (dipanggil dari modal)
        async function submitOrder() {
            // Mendapatkan data dari form lagi untuk dikirim
            const customerId = document.getElementById('customer-id-input').value;
            const paymentMethod = document.getElementById('payment-method-input').value;
            const note = document.getElementById('note').value;
            const phone = document.getElementById('phone').value;
            const address = document.getElementById('address').value;
            const paymentProofFile = document.getElementById('payment-proof').files[0];

            // Siapkan FormData
            const formData = new FormData();
            formData.append('customer_id', customerId);
            formData.append('phone', phone);
            formData.append('address', address);
            formData.append('payment_method', paymentMethod);
            formData.append('note', note);
            formData.append('products', JSON.stringify(cart.map(item => ({
                product_id: item.product_id,
                product_name: item.product_name,
                variant_id: item.variant_id,
                variant_name: item.variant_name,
                quantity: item.qty,
                price: item.price,
            }))));

            if (paymentProofFile) {
                formData.append('payment_proof', paymentProofFile);
            }

            // Tampilkan loading state di tombol submit
            const submitButton = document.getElementById('submit-order-button');
            submitButton.disabled = true;
            submitButton.innerHTML = 'Menyimpan...';

            try {
                const response = await fetch("{{ route('kurir.orders.checkout') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                            'content')
                    },
                    body: formData
                });

                const result = await response.json();

                // Sembunyikan modal konfirmasi
                const modalElement = document.getElementById('konfirmasiModal');
                const modal = new Modal(modalElement);
                modal.hide();

                if (response.ok) {
                    showSuccessModal(result.message, result.invoice_number);
                    // Reset form setelah berhasil
                    cart = [];
                    renderCart();
                    document.getElementById('selected-customer').textContent = '- Pilih Customer -';
                    document.getElementById('customer-id-input').value = '';
                    document.getElementById('phone').value = '';
                    document.getElementById('address').value = '';
                    document.getElementById('selected-payment-method').textContent = '-Pilih metode pembayaran -';
                    document.getElementById('payment-method-input').value = '';
                    document.getElementById('payment-proof').value = '';
                    document.getElementById('payment-proof-upload').classList.add('hidden');
                    document.getElementById('note').value = '';

                } else {
                    let errorMessage = result.message || 'Terjadi kesalahan.';
                    if (result.errors) {
                        errorMessage += '\n<ul>';
                        for (const key in result.errors) {
                            errorMessage += `<li>${result.errors[key].join(', ')}</li>`;
                        }
                        errorMessage += '</ul>';
                    }
                    showToast('Gagal: ' + errorMessage, 'error', 7000);
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Terjadi kesalahan saat mengirim pesanan. Mohon coba lagi.', 'error');
            } finally {
                // Kembalikan tombol ke state normal
                submitButton.disabled = false;
                submitButton.innerHTML = 'Konfirmasi & Simpan Pesanan';
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
                            ${sudahDipilih ?
                                `<button type="button" class="px-2 py-1 text-xs text-white bg-red-500 rounded hover:bg-red-600" onclick="hapusDariCart(${p.id}, ${v.id})"><i class="fas fa-trash"></i></button>` :
                                `<button type="button" class="px-2 py-1 text-xs text-white bg-green-500 rounded hover:bg-green-600" onclick="tambahKeCart(${p.id}, ${v.id})"><i class="fas fa-cart-plus"></i></button>`
                            }
                        </div>
                    `;
                    });
                } else {
                    const sudahDipilih = cart.some(c => c.product_id === p.id && !c
                    .variant_id); // Perbaikan: Tambahkan !c.variant_id
                    pilihDiv.innerHTML += `
                    <div class="flex flex-row items-center gap-3 p-3 border rounded bg-gray-50">
                        ${imageUrl ? `<img src="${imageUrl}" alt="${p.name}" class="object-cover w-16 h-16 mr-2 border rounded" />` : ''}
                        <div class="flex-1">
                            <div class="font-semibold">${p.name}</div>
                            <div class="font-bold text-green-700">Rp ${p.price ? p.price.toLocaleString() : ''}</div>
                        </div>
                        ${sudahDipilih ?
                            `<button type="button" class="px-2 py-1 text-xs text-white bg-red-500 rounded hover:bg-red-600" onclick="hapusDariCart(${p.id}, null)"><i class="fas fa-trash"></i></button>` :
                            `<button type="button" class="px-2 py-1 text-xs text-white bg-green-500 rounded hover:bg-green-600" onclick="tambahKeCart(${p.id}, null)"><i class="fas fa-cart-plus"></i></button>`
                        }
                    </div>
                `;
                }
            });
        }

        // Menambahkan produk ke keranjang atau menambah kuantitasnya
        function tambahKeCart(productId, variantId) {
            let product = produkList.find(p => p.id === productId);
            let cartItem = null;
            let variant = null;

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

        // Menghapus produk dari keranjang melalui modal produk
        function hapusDariCart(productId, variantId) {
            let product = produkList.find(p => p.id === productId);
            let variant = null;
            let cartItemIndex = -1;

            if (variantId) {
                variant = product.variants.find(v => v.id === variantId);
                cartItemIndex = cart.findIndex(c => c.variant_id === variantId);
            } else {
                cartItemIndex = cart.findIndex(c => c.product_id === productId && !c.variant_id);
            }

            if (cartItemIndex !== -1) {
                const removedItem = cart[cartItemIndex];
                cart.splice(cartItemIndex, 1);
                showToast(`${product.name}${variant ? ' - ' + variant.name : ''} dihapus dari keranjang.`, 'info');
                renderCart();
                tampilkanPilihanProduk();
            }
        }

        // Mengubah kuantitas produk di keranjang (via index)
        function ubahQtyCart(idx, change) {
            cart[idx].qty += change;
            if (cart[idx].qty < 1) cart[idx].qty = 1;
            renderCart();
        }

        // Menghapus produk dari keranjang (via index)
        function hapusProdukCart(idx) {
            const itemToRemove = cart[idx];
            cart.splice(idx, 1);
            renderCart();
            tampilkanPilihanProduk();
            showToast(
                `${itemToRemove.product_name}${itemToRemove.variant_name ? ' - ' + itemToRemove.variant_name : ''} dihapus dari keranjang.`,
                'info');
        }

        // Merender (menampilkan) isi keranjang
        function renderCart() {
            const cartDiv = document.getElementById('cart-list');
            // Kosongkan isi sebelumnya
            cartDiv.innerHTML = '';
            let total = 0;

            // Jika keranjang kosong, tampilkan pesan
            if (cart.length === 0) {
                cartDiv.innerHTML =
                    `<div class="p-8 text-center text-gray-500 dark:text-gray-400">Keranjang masih kosong. Tambahkan produk!</div>`;
            } else {
                // Buat container untuk tabel (desktop) dan div untuk card (mobile)
                const desktopTableContainer = document.createElement('div');
                desktopTableContainer.className = 'overflow-x-auto hidden md:block'; // Sembunyikan di mobile

                let tableHTML = `
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th scope="col" class="w-[5%] px-4 py-3 text-xs font-bold tracking-wider text-left text-gray-500 uppercase dark:text-white">
                                No
                            </th>
                            <th scope="col" class="w-[45%] px-6 py-3 text-xs font-bold tracking-wider text-left text-gray-500 uppercase dark:text-white">
                               🛒 Produk
                            </th>
                            <th scope="col" class="w-[15%] px-6 py-3 text-xs font-bold tracking-wider text-center text-gray-500 uppercase dark:text-white">
                                Qty
                            </th>
                            <th scope="col" class="w-[25%] px-6 py-3 text-xs font-bold tracking-wider text-left text-gray-500 uppercase dark:text-white" style="padding-left:35px";>
                                Subtotal
                            </th>
                            <th scope="col" class="w-[10%] px-6 py-3 text-xs font-bold tracking-wider text-center text-gray-500 uppercase dark:text-gray-400">

                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
            `;

                let mobileCardsHTML = `<div class="md:hidden">`; // Container untuk tampilan mobile

                cart.forEach((item, idx) => {
                    const subtotal = item.qty * item.price;
                    total += subtotal;

                    // Ambil URL gambar dari data produk yang ada di `produkList`
                    const productData = produkList.find(p => p.id === item.product_id);
                    // Fallback placeholder image if no image/foto is found
                    const imageUrl = productData ? (productData.image || productData.foto ||
                            'https://placehold.co/64x64/E2E8F0/64748B?text=No+Img') :
                        'https://placehold.co/64x64/E2E8F0/64748B?text=No+Img';

                    // Tampilan Desktop (baris tabel)
                    tableHTML += `
                    <tr>
                        <td class="px-4 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900 dark:text-white">${idx + 1}</div>
                        </td>
                        <td class="px-2 py-4">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 w-16 h-16">
                                    <img class="object-cover w-16 h-16 rounded-md" src="${imageUrl}" alt="${item.product_name}">
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">${item.product_name}</div>
                                    ${item.variant_name ? `<div class="text-xs text-gray-400 dark:text-gray-500">${item.variant_name}</div>` : ''}
                                    <div class="text-sm text-gray-500 dark:text-gray-400">Rp ${item.price.toLocaleString('id-ID')}</div>
                                </div>
                            </div>
                        </td>
                        <td class="py-4 whitespace-nowrap">
                            <div class="flex items-center justify-center gap-2">
                                <button type="button" onclick="ubahQtyCart(${idx}, -1)" class="px-2 text-black transition rounded hover:bg-gray-300 dark:text-white dark:hover:bg-gray-700 hover:scale-110 active:scale-90">–</button>
                                <span class="px-2 text-black bg-gray-200 rounded dark:text-white dark:bg-gray-700">${item.qty}</span>
                                <button type="button" onclick="ubahQtyCart(${idx}, 1)" class="px-2 text-black transition rounded hover:bg-gray-300 dark:text-white dark:hover:bg-gray-700 hover:scale-110 active:scale-90">+</button>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-left text-gray-900 whitespace-nowrap dark:text-white" style="padding-left:35px";>
                            Rp ${subtotal.toLocaleString('id-ID')}
                        </td>
                        <td class="px-4 py-4 text-sm font-medium text-center whitespace-nowrap">
                            <button type="button" onclick="hapusProdukCart(${idx})" class="text-red-600 hover:text-red-900 dark:hover:text-red-500 hover:scale-110 active:scale-90">
                                🗑
                            </button>
                        </td>
                    </tr>
                `;

                    // Tampilan Mobile (card) - Posisi sudah ditukar
                    mobileCardsHTML += `
                    <div class="flex items-start gap-4 p-4 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex-shrink-0 w-24 h-24">
                            <img class="object-cover w-24 h-24 rounded-md" src="${imageUrl}" alt="${item.product_name}">
                        </div>
                        <div class="flex flex-col flex-1">
                            <div class="flex items-center justify-between mb-1"> {{-- Added items-center for vertical alignment --}}
                                <p class="font-bold text-black dark:text-white">${item.product_name}</p>
                                {{-- Tombol hapus dipindahkan ke sini --}}
                                <button type="button" onclick="hapusProdukCart(${idx})" class="text-sm text-red-600 hover:text-red-900 dark:hover:text-red-500 hover:scale-110 active:scale-90">🗑</button>
                            </div>
                            ${item.variant_name ? `<p class="mb-1 text-xs text-gray-500 dark:text-gray-400">${item.variant_name}</p>` : ''}
                            <p class="text-black dark:text-white">Rp ${item.price.toLocaleString('id-ID')}</p>
                            <div class="flex items-center justify-between mt-3"> {{-- Changed to justify-between and added items-center --}}
                                {{-- Kontrol kuantitas --}}
                                <div class="flex items-center gap-2">
                                    <button type="button" onclick="ubahQtyCart(${idx}, -1)" class="px-2 text-black rounded dark:text-white hover:scale-110 active:scale-90">–</button>
                                    <span class="px-2 text-black bg-gray-200 rounded dark:text-white dark:bg-gray-700">${item.qty}</span>
                                    <button type="button" onclick="ubahQtyCart(${idx}, 1)" class="px-2 text-black rounded dark:text-white hover:scale-110 active:scale-90">+</button>
                                </div>
                                {{-- Subtotal dipindahkan ke sini --}}
                                <div class="ml-auto"> {{-- Keeping ml-auto for pushing to right, but flex takes care of the spacing --}}
                                    <span class="font-medium text-black dark:text-white">Rp ${subtotal.toLocaleString('id-ID')}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                });

                tableHTML += `
                    </tbody>
                </table>
            `;
                mobileCardsHTML += `</div>`; // Tutup container mobile

                // Masukkan tabel desktop dan card mobile ke dalam div cartDiv
                desktopTableContainer.innerHTML = tableHTML;
                cartDiv.appendChild(desktopTableContainer);
                cartDiv.innerHTML += mobileCardsHTML; // Tambahkan setelah tabel desktop
            }

            // Memperbarui semua elemen display total harga
            const totalDisplayElements = document.querySelectorAll('.cart-total-display');
            totalDisplayElements.forEach(el => {
                if (el.id === 'cart-total') {
                    el.textContent = `Total: Rp ${total.toLocaleString('id-ID')}`;
                } else {
                    el.textContent = `Rp ${total.toLocaleString('id-ID')}`;
                }
            });
        }

        // --- Fungsi Modal Sukses ---
        function showSuccessModal(message, invoiceNumber) { // Tambahkan parameter invoiceNumber
            document.getElementById('success-message').textContent = message;

            // Tampilkan nomor invoice jika ada
            const invoiceEl = document.getElementById('success-invoice');
            if (invoiceNumber) {
                invoiceEl.textContent = 'No. Invoice: ' + invoiceNumber;
                invoiceEl.classList.remove('hidden');
            } else {
                invoiceEl.classList.add('hidden');
            }

            document.getElementById('successModal').classList.remove('hidden');
        }

        function hideSuccessModal() {
            document.getElementById('successModal').classList.add('hidden');
        }
    </script>
@endsection
