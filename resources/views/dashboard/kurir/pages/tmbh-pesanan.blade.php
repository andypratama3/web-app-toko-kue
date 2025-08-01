@extends('layouts.argon')
@section('title', 'Dashboard Kurir')
@section('content')



<div class="flex-auto p-4">
    <p class="leading-normal uppercase dark:text-white dark:opacity-60 text-sm mb-4">Data Customer</p>

    <div class="flex flex-wrap -mx-3">
        <!-- NAMA CUSTOMER -->
        <div class="relative group w-full max-w-full px-3 shrink-0 md:w-6/12 md:flex-0 mb-4">
            <label for="dropdown-button" class="inline-block mb-2 ml-1 font-bold text-xs text-slate-700 dark:text-white/80">Nama Customer</label>
            <button id="dropdown-button" class=" border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 text-left flex justify-between items-center dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-gray-400 dark:focus:ring-blue-500 dark:focus:border-blue-500">
                <span id="selected-item" class="text-gray-700 dark:text-white">- Pilih Customer -</span>
                <svg class="w-4 h-4 ml-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>
            <div id="dropdown-menu" class="hidden absolute z-10 w-full mt-2 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 p-1 space-y-1 dark:bg-gray-700">
                <input id="search-input" class="block w-full px-4 py-2 text-sm text-gray-800 border border-gray-300 rounded-md focus:outline-none focus:ring focus:border-blue-500 dark:bg-gray-600 dark:text-white" type="text" placeholder="cari customer" autocomplete="off">
                <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-600 rounded-md cursor-pointer">Salis Nilam</a>
                <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-600 rounded-md cursor-pointer">Kansaa Zafarani</a>
                <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-600 rounded-md cursor-pointer">Chalya kirana</a>
                <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-600 rounded-md cursor-pointer">Tung tung sahuur</a>
            </div>
        </div>

        <!-- NO HP -->
        <div class="w-full max-w-full px-3 shrink-0 md:w-6/12 md:flex-0">
            <div class="mb-4">
                <label for="nohp" class="inline-block mb-2 ml-1 font-bold text-xs text-slate-700 dark:text-white/80">No. HP</label>
                <input type="text" name="nohp" id="disabled-input" aria-label="disabled input" class="mb-6 bg-gray-100 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 cursor-not-allowed dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-gray-400 dark:focus:ring-blue-500 dark:focus:border-blue-500" disabled>
            </div>
        </div>
        <div class="w-full max-w-full px-3 shrink-0 md:w-full md:flex-0">
            <div class="mb-4">
                <label for="address" class="inline-block mb-2 ml-1 font-bold text-xs text-slate-700 dark:text-white/80">Address</label>
                <textarea type="text" id="disabled-input" aria-label="disabled input" class="mb-6 bg-gray-100 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 cursor-not-allowed dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-gray-400 dark:focus:ring-blue-500 dark:focus:border-blue-500" disabled></textarea>
            </div>
        </div>
    </div>

    <hr class="h-px mx-0 my-4 bg-transparent border-0 opacity-25 bg-gradient-to-r from-transparent via-black/40 to-transparent dark:bg-gradient-to-r dark:from-transparent dark:via-white dark:to-transparent " />
    <p class="leading-normal uppercase dark:text-white dark:opacity-60 text-sm mb-4">Detail Produk</p>

    <div class="flex flex-wrap -mx-3">
        <div class="w-full max-w-full px-3 shrink-0 md:w-full md:flex-0 space-y-4" id="order-items">
            <div class="grid grid-cols-3 gap-4 text-sm font-semibold text-gray-700">
                <div>Nama Produk</div>
                <div>Jumlah</div>
                <div>Harga Satuan</div>
            </div>
            <div class="grid grid-cols-3 gap-4 items-center group">
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
            </div>
        </div>

        <!-- Tombol Tambah Produk -->
        <div class="w-full px-3 mt-3 mb-6 text-right relative z-0">
            <button type="button" onclick="addProduct()" class="px-3 py-2 text-xs font-medium text-center text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                Tambah Produk
            </button>

        </div>


        <!-- METODE BAYAR-->
        <div class="w-full max-w-full px-3 shrink-0 md:w-full md:flex-0">
            <div class="mb-4">
                <label for="payment_method" class="inline-block mb-2 ml-1 font-bold text-xs text-slate-700 dark:text-white/80">Metode Pembayaran</label>
                <select id="lokasi" name="lokasi" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                    <option selected disabled>-Pilih metode-</option>
                    <option value="cash">Cash (Tunai)</option>
                    <option value="tf">Transfer Bank</option>
                    <option value="qr">QRIS</option>
                </select>
            </div>
        </div>
    </div>

    <hr class="h-px mx-0 my-4 bg-transparent border-0 opacity-25 bg-gradient-to-r from-transparent via-black/40 to-transparent dark:bg-gradient-to-r dark:from-transparent dark:via-white dark:to-transparent " />
    <p class="leading-normal uppercase dark:text-white dark:opacity-60 text-sm mb-4">Note</p>

    <div class="flex flex-wrap -mx-3">
        <!-- NOTE -->
        <div class="w-full max-w-full px-3 shrink-0 md:w-full md:flex-0">
            <div class="mb-4">
                <label for="about me" class="inline-block mb-2 ml-1 font-bold text-xs text-slate-700 dark:text-white/80">Text Here!</label>
                <textarea type="text" name="note" value="" class="focus:shadow-primary-outline dark:bg-slate-850 dark:text-white text-sm leading-5.6 ease block w-full appearance-none rounded-lg border border-solid border-gray-300 bg-white bg-clip-padding px-3 py-2 font-normal text-gray-700 outline-none transition-all placeholder:text-gray-500 focus:border-blue-500 focus:outline-none"></textarea>
            </div>
        </div>
    </div>

</div>

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
    const dropdownButton = document.getElementById('dropdown-button');
    const dropdownMenu = document.getElementById('dropdown-menu');
    const searchInput = document.getElementById('search-input');
    const selectedItem = document.getElementById('selected-item');
    const items = dropdownMenu.querySelectorAll('a');

    let isOpen = false;

    dropdownButton.addEventListener('click', (e) => {
        e.stopPropagation();
        isOpen = !isOpen;
        dropdownMenu.classList.toggle('hidden', !isOpen);
        searchInput.value = '';
        items.forEach(item => item.style.display = 'block');
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', () => {
        isOpen = false;
        dropdownMenu.classList.add('hidden');
    });

    // Prevent dropdown from closing when clicking inside
    dropdownMenu.addEventListener('click', (e) => {
        e.stopPropagation();
    });

    // Filter dropdown items
    searchInput.addEventListener('input', () => {
        const searchTerm = searchInput.value.toLowerCase();
        items.forEach((item) => {
            const text = item.textContent.toLowerCase();
            item.style.display = text.includes(searchTerm) ? 'block' : 'none';
        });
    });

    // Optional: Update selected item text on click
    items.forEach(item => {
        item.addEventListener('click', () => {
            selectedItem.textContent = item.textContent;
            dropdownMenu.classList.add('hidden');
            isOpen = false;
        });
    });
</script>

<script src="/assets/argon/js/plugins/chartjs.min.js"></script>
<script src="/assets/argon/js/plugins/perfect-scrollbar.min.js" async></script>
<script src="/assets/argon/js/assets/argon-dashboard-tailwind.js?v=1.0.1" async></script>

@endsection