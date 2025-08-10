@extends('layouts.argon')
@section('title', 'Dashboard Kurir')
@section('content')

    <div class="flex-auto p-4">
        <p class="leading-normal uppercase dark:text-white dark:opacity-60 text-sm mb-4">Data Customer</p>

        <div class="flex flex-wrap -mx-3">
            <!-- NAMA CUSTOMER -->
            <div class="relative group w-full max-w-full px-3 shrink-0 md:w-6/12 md:flex-0 mb-4">
                <label for="search-input" class="inline-block mb-2 ml-1 font-bold text-xs text-slate-700 dark:text-white/80">Nama Customer</label>
                
                <div class="relative">
                    <button id="dropdown-button" type="button" class="border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-left">
                        <span id="selected-customer">- Pilih Customer -</span>
                    </button>
                    
                    <div id="dropdown-menu" class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg hidden dark:bg-gray-700">
                        <div class="p-2">
                            <input type="text" id="search-input" class="w-full p-2 border border-gray-300 rounded-lg focus:outline-none dark:bg-gray-600 dark:border-gray-500 dark:text-white" placeholder="Cari customer...">
                        </div>
                        
                        <ul id="customer-list" class="max-h-60 overflow-y-auto">
                            @foreach($customers as $customer)
                            <li>
                                <a href="#" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white"
                                    data-value="{{ $customer->id }}"
                                    data-phone="{{ $customer->phone }}"
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
            <div class="w-full max-w-full px-3 shrink-0 md:w-6/12 md:flex-0">
                <div class="mb-4">
                    <label for="phone" class="inline-block mb-2 ml-1 font-bold text-xs text-slate-700 dark:text-white/80">No. HP</label>
                    <input type="text" name="phone" id="phone" class="mb-6 bg-gray-100 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 cursor-not-allowed" disabled>
                </div>
            </div>

            <!-- ADDRESS -->
            <div class="w-full max-w-full px-3 shrink-0 md:w-full md:flex-0">
                <div class="mb-4">
                    <label for="address" class="inline-block mb-2 ml-1 font-bold text-xs text-slate-700 dark:text-white/80">Address</label>
                    <textarea id="address" class="mb-6 bg-gray-100 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 cursor-not-allowed" disabled></textarea>
                </div>
            </div>
        </div>

        <hr class="h-px mx-0 my-4 bg-transparent border-0 opacity-25 bg-gradient-to-r from-transparent via-black/40 to-transparent dark:bg-gradient-to-r dark:from-transparent dark:via-white dark:to-transparent " />
        <p class="leading-normal uppercase dark:text-white dark:opacity-60 text-sm mb-4">Detail Produk</p>

        <div class="flex flex-wrap -mx-3">

            <div class="w-full max-w-full px-3 shrink-0 md:w-full md:flex-0">
                <!-- Tombol Tambah Produk -->
                <div class="flex justify-end mb-4">
                    <button onclick="showProdukModal()"
                        class="bg-black text-white px-6 py-2 rounded hover:bg-gray-800 transition">
                        + Tambah Produk
                    </button>
                </div>

                <!-- Modal Pilihan Produk -->
                <div id="produkModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-4">
                        <div class="flex justify-between items-center border-b pb-2 mb-4">
                            <h2 class="text-lg font-bold">Pilih Produk</h2>
                            <button onclick="hideProdukModal()" class="text-gray-500 hover:text-black">✕</button>
                        </div>
                        <div id="pilihan-produk" class="space-y-2"></div>
                    </div>
                </div>

                <!-- Judul Kolom untuk Desktop -->
                <div class="hidden md:flex justify-between px-4 py-2 border-b border-gray-300 font-bold text-black">
                    <p class=" ml-28"></p>
                    <p class="w-1/4">Nama Produk</p>
                    <p class="w-1/6 text-center">Harga Satuan</p>
                    <p class="w-1/6 text-center -ml-4">Qty</p>
                    <p class="w-1/6 text-center">Total</p>
                    <p class="w-1/6 text-center">Aksi</p>
                </div>

                <!-- Cart -->
                <div id="cart-list"></div>
                <!-- <p id="cart-total" class="text-right mt-4 font-bold text-lg"></p> -->

            </div>

            <!-- METODE BAYAR-->
            <div class="w-full max-w-full px-3 shrink-0 md:w-full md:flex-0 mt-4 bg:white">
                <div class="mb-4">
                    <label for="lokasi" class="inline-block mb-2 ml-1 font-bold text-xs text-slate-700 dark:text-white/80">Metode Pembayaran</label>
                    <div class="relative inline-block w-full text-left">
                        <button id="payment-method-button" type="button" class="inline-flex justify-between items-center w-full rounded-lg border border-gray-300 shadow-sm px-4 py-2.5 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-100 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:bg-gray-600">
                            <span id="selected-payment-method">-Pilih metode pembayaran -</span>
                            <svg class="-mr-1 ml-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <div id="payment-method-menu" class="hidden absolute left-0 mt-1 w-full rounded-xl shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none dark:bg-gray-700" role="menu">
                            <div class="py-2" role="none">
                                <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600" data-value="cash" role="menuitem">Cash (Tunai)</a>
                                <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600" data-value="tf" role="menuitem">Transfer Bank</a>
                                <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-600" data-value="qr" role="menuitem">QRIS</a>
                            </div>
                        </div>
                        <input type="hidden" id="lokasi" name="lokasi">
                    </div>
                </div>
            </div>
        </div>

        <hr class="h-px mx-0 my-4 bg-transparent border-0 opacity-25 bg-gradient-to-r from-transparent via-black/40 to-transparent dark:bg-gradient-to-r dark:from-transparent dark:via-white dark:to-transparent " />
        <p class="leading-normal uppercase dark:text-white dark:opacity-60 text-sm mb-4">Note</p>

        <div class="flex flex-wrap -mx-3 pb-14">
            <!-- NOTE -->
            <div class="w-full max-w-full px-3 shrink-0 md:w-full md:flex-0">
                <div class="mb-4">
                    <label for="about me" class="inline-block mb-2 ml-1 font-bold text-xs text-slate-700 dark:text-white/80">Text Here!</label>
                    <textarea type="text" name="note" value="" class="focus:shadow-primary-outline dark:bg-slate-850 dark:text-white text-sm leading-5.6 ease block w-full appearance-none rounded-lg border border-solid border-gray-300 bg-white bg-clip-padding px-3 py-2 font-normal text-gray-700 outline-none transition-all placeholder:text-gray-500 focus:border-blue-500 focus:outline-none"></textarea>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Tombol Checkout Fix di Bawah -->
<div class="fixed bottom-0 left-0 w-full bg-white border-t border-gray-300 p-4 flex justify-between items-center z-50 xl:hidden">
    <p id="cart-total" class="font-bold text-lg">Total: Rp 0</p> 
    <button onclick="checkout()" 
        class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition">
        Checkout
    </button>
</div>

<!-- SCRIPT JS -->
<!-- tambah produk -->
<script>
    function addProduct() {
        const container = document.getElementById('order-items');
        const newRow = document.createElement('div');
        newRow.className = 'grid grid-cols-3 gap-4 items-center group';
        newRow.innerHTML = `
            <input type="text" name="product_name[]" placeholder="Nama Produk"
                class="block w-full border border-gray-300 rounded-md py-2 text-sm focus:ring focus:border-blue-300" />
            <input type="number" name="quantity[]" placeholder="Jumlah"
                class="block w-full border border-gray-300 rounded-md py-2 text-sm focus:ring focus:border-blue-300" />
            <div class="relative">
                <input type="tel" name="price[]" placeholder="Harga Satuan"
                    class="block w-full border border-gray-300 rounded-md py-2 text-sm focus:ring focus:border-blue-300" />
                <button type="button" onclick="removeProduct(this)"
                    class="absolute top-1/2 right-2 -translate-y-1/2 text-red-600 hover:text-red-800 text-xs">Hapus</button>
            </div>
        `;
        container.appendChild(newRow);
    }

    function removeProduct(button) {
        button.closest('.grid').remove();
    }
</script>

<!-- dropdown search -->
 <script>
// --- Inisialisasi Dropdown Customer ---
    document.addEventListener('DOMContentLoaded', function() {
        const dropdownButton = document.getElementById('dropdown-button');
        const dropdownMenu = document.getElementById('dropdown-menu');
        const searchInput = document.getElementById('search-input');
        const selectedCustomerSpan = document.getElementById('selected-customer');
        const hiddenCustomerIdInput = document.getElementById('customer-id-input');
        const phoneInput = document.getElementById('phone');
        const addressInput = document.getElementById('address');
        const customerList = document.getElementById('customer-list');

        // Mengatur visibilitas dropdown saat tombol diklik
        dropdownButton.addEventListener('click', function(e) {
            e.stopPropagation();
            dropdownMenu.classList.toggle('hidden');
            searchInput.focus();
        });

        // Menyembunyikan dropdown saat klik di luar area dropdown
        document.addEventListener('click', function(e) {
            if (!dropdownMenu.contains(e.target) && !dropdownButton.contains(e.target)) {
                dropdownMenu.classList.add('hidden');
            }
        });

        // Memfilter daftar pelanggan berdasarkan input pencarian
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

        // Menangani pemilihan pelanggan dari daftar
        customerList.addEventListener('click', function(e) {
            if (e.target.tagName === 'A') {
                e.preventDefault();
                const selectedLink = e.target;
                
                // Mengambil data pelanggan dari atribut 'data-'
                const customerId = selectedLink.getAttribute('data-value');
                const customerName = selectedLink.textContent.trim();
                const phone = selectedLink.getAttribute('data-phone');
                const address = selectedLink.getAttribute('data-address');

                // Memperbarui teks yang terlihat dan input tersembunyi
                selectedCustomerSpan.textContent = customerName;
                hiddenCustomerIdInput.value = customerId;

                // Mengisi kolom nomor telepon dan alamat
                phoneInput.value = phone;
                addressInput.value = address;
                
                // Menutup menu dropdown
                dropdownMenu.classList.add('hidden');
            }
        });
    });
</script>

<!-- untuk menambahkan produk -->
<script>
    let produkList = [];
    let cart = [];

    // Ambil data produk dari backend
    async function getProduk() {
        try {
            const res = await fetch('/api/products');
            produkList = await res.json();
            tampilkanPilihanProduk();
        } catch (err) {
            console.error("Gagal ambil produk:", err);
        }
    }

    function showProdukModal() {
        tampilkanPilihanProduk();
        document.getElementById('produkModal').classList.remove('hidden');
    }

    function hideProdukModal() {
        document.getElementById('produkModal').classList.add('hidden');
    }

    function tampilkanPilihanProduk() {
        const pilihDiv = document.getElementById('pilihan-produk');
        pilihDiv.innerHTML = '';

        produkList.forEach(p => {
            const sudahDipilih = cart.some(c => c.id === p.id);

            pilihDiv.innerHTML += `
        <button onclick="tambahKeCart(${p.id})" 
            class="w-full flex items-center gap-3 p-3 border rounded-lg hover:bg-gray-100 transition relative"
            ${sudahDipilih ? 'disabled' : ''}>
            
            <img src="${p.gambar}" alt="${p.nama}" class="w-12 h-12 object-cover rounded">

            <div class="flex flex-col text-left">
                <span class="font-medium text-gray-800">${p.nama}</span>
                <span class="text-gray-500">Rp ${p.harga.toLocaleString()}</span>
            </div>

            ${sudahDipilih ? `
                <span class="absolute top-2 right-2 bg-green-500 text-white text-xs px-2 py-1 rounded">
                    ✔
                </span>` : ``}
        </button>
    `;
        });
    }

    // Tambah ke cart
    function tambahKeCart(id) {
        const item = produkList.find(p => p.id === id);
        const existing = cart.find(c => c.id === id);
        if (existing) {
            existing.qty++;
        } else {
            cart.push({
                ...item,
                qty: 1
            });
        }
        renderCart();
        tampilkanPilihanProduk();
        hideProdukModal();
    }

    function ubahQty(id, change) {
        const item = cart.find(p => p.id === id);
        item.qty += change;
        if (item.qty < 1) item.qty = 1;
        renderCart();
    }

    function hapusProduk(id) {
        cart = cart.filter(p => p.id !== id);
        renderCart();
        tampilkanPilihanProduk();
    }

    function renderCart() {
        const cartDiv = document.getElementById('cart-list');
        cartDiv.innerHTML = '';
        let total = 0;

        cart.forEach(item => {
            const subtotal = item.qty * item.harga;
            total += subtotal;

            cartDiv.innerHTML += `
                <div class="border p-4 rounded mb-2 relative flex flex-row items-start gap-4">
                    <img src="${item.gambar}" class="w-24 h-24 rounded object-cover" />
                    <div class="flex-1">
                        <div class="flex justify-between md:hidden">
                            <div>
                                <p class="font-bold text-black">${item.nama}</p>
                                <p class="text-black">Rp ${item.harga.toLocaleString()}</p>
                            </div>
                        </div>
                        <div class="hidden md:flex justify-between items-center px-4 py-2 border-b border-gray-100">
                            <p class="w-1/4 text-black">${item.nama}</p>
                            <p class="w-1/6 text-center text-black">Rp ${item.harga.toLocaleString()}</p>
                            <div class="w-1/6 flex justify-center items-center gap-2">
                                <button onclick="ubahQty(${item.id}, -1)" class="px-2 bg-gray-200 rounded text-black">–</button>
                                <span class="text-black">${item.qty}</span>
                                <button onclick="ubahQty(${item.id}, 1)" class="px-2 bg-gray-200 rounded text-black">+</button>
                            </div>
                            <p class="w-1/6 text-center text-black font-medium">Rp ${subtotal.toLocaleString()}</p>
                            <div class="w-1/6 flex justify-center">
                                <button onclick="hapusProduk(${item.id})" class="text-red-500 text-xl">🗑</button>
                            </div>
                        </div>
                        <div class="md:hidden mt-3">
                            <div class="flex items-center gap-2">
                                <div class="flex items-center gap-2">
                                    <button onclick="ubahQty(${item.id}, -1)" class="px-2 bg-gray-200 rounded text-black">–</button>
                                    <span class="text-black">${item.qty}</span>
                                    <button onclick="ubahQty(${item.id}, 1)" class="px-2 bg-gray-200 rounded text-black">+</button>
                                </div>
                                <div class="ml-auto">
                                    <button onclick="hapusProduk(${item.id})" class="text-red-500 text-xl">🗑</button>
                                </div>
                            </div>
                            <div class="mt-2 flex justify-between">
                                <span class="text-black font-medium"></span>
                                <span class="text-black font-medium mt-2">Total Rp ${subtotal.toLocaleString()}</span>
                            </div>
                        </div>
                    </div>
                </div>
    `;
        });

        document.getElementById('cart-total').textContent = `Total: Rp ${total.toLocaleString()}`;
    }

    document.addEventListener("DOMContentLoaded", getProduk);
</script>


<!-- fungsi button checkout -->
 <script>
    async function checkout() {
        // Mengambil ID pelanggan dari input tersembunyi
        const customerId = document.getElementById('customer-id-input').value;
        const paymentMethod = document.getElementById('lokasi').value;
        const note = document.querySelector('textarea[name="note"]').value;

         // Validasi
        if (!customerId) {
            alert('Silakan pilih customer terlebih dahulu.');
            return;
        }

        if (cart.length === 0) {
            alert('Keranjang belanja kosong. Tambahkan produk terlebih dahulu.');
            return;
        }

        if (!paymentMethod || paymentMethod === "-Pilih metode-") {
            alert('Silakan pilih metode pembayaran.');
            return;
        }

        const orderData = {
            customer_id: customerId,
            payment_method: paymentMethod,
            note: note,
            products: cart.map(item => ({
                product_id: item.id,
                product_name: item.nama,
                quantity: item.qty,
                price: item.harga,
            }))
        };

        try {
            // Mengirim data ke API
            const response = await fetch('/api/orders/checkout', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(orderData)
            });

            const result = await response.json();

            if (response.ok) {
                showSuccessModal(result.message);
                // Mereset form setelah berhasil
                cart = [];
                renderCart();
                document.getElementById('dropdown-button').value = '';
                document.getElementById('phone').value = '';
                document.getElementById('address').value = '';
                document.getElementById('lokasi').value = '';
                document.querySelector('textarea[name="note"]').value = '';
            } else {
                alert('Gagal menyimpan pesanan: ' + result.message);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat checkout. Mohon coba lagi.');
        }
    }
</script>

<!--  JavaScript untuk dropdown Metode Pembayaran -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const button = document.getElementById('payment-method-button');
    const menu = document.getElementById('payment-method-menu');
    const selectedText = document.getElementById('selected-payment-method');
    const hiddenInput = document.getElementById('lokasi');

    button.addEventListener('click', function(e) {
        e.stopPropagation();
        menu.classList.toggle('hidden');
    });

    menu.addEventListener('click', function(e) {
        if (e.target.tagName === 'A') {
            e.preventDefault();
            const value = e.target.getAttribute('data-value');
            const text = e.target.textContent;

            // Perbarui teks yang terlihat dan nilai input tersembunyi
            selectedText.textContent = text;
            hiddenInput.value = value;
            
            // Sembunyikan menu
            menu.classList.add('hidden');
        }
    });

    // Sembunyikan menu saat mengklik di luar
    document.addEventListener('click', function(e) {
        if (!button.contains(e.target) && !menu.contains(e.target)) {
            menu.classList.add('hidden');
        }
    });
});
</script>


<script src="/assets/argon/js/plugins/chartjs.min.js"></script>
<script src="/assets/argon/js/plugins/perfect-scrollbar.min.js" async></script>
<script src="/assets/argon/js/assets/argon-dashboard-tailwind.js?v=1.0.1" async></script>

<!-- script modal -->
 <script>
    function showSuccessModal(message) {
        document.getElementById('success-message').textContent = message;
        document.getElementById('successModal').classList.remove('hidden');
    }

    function hideSuccessModal() {
        document.getElementById('successModal').classList.add('hidden');
    }
</script>

<!-- modal sukses tersimpan -->
<div id="successModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-sm p-6 text-center">
        <div class="text-green-500 text-5xl mb-4">✔</div>
        <h2 class="text-xl font-bold mb-2">Sukses!</h2>
        <p id="success-message" class="text-gray-700 mb-4">Pesanan berhasil disimpan.</p>
        <button onclick="hideSuccessModal()" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 transition">Tutup</button>
    </div>
</div>


@endsection