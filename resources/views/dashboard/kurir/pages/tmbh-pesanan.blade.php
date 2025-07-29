@extends('layouts.argon')
@section('title', 'Dashboard Kurir')
@section('content')



<div class="flex-auto p-4">
    <p class="leading-normal uppercase dark:text-white dark:opacity-60 text-sm">Data Customer</p>
    <div class="flex flex-wrap -mx-3">
        <div class="w-full max-w-full px-3 shrink-0 md:w-6/12 md:flex-0">
            <div class="mb-4">
                <label for="nama" class="block mb-2 ml-1 font-bold text-xs text-slate-700 dark:text-white/80">Nama</label>
                <!-- <select id="product-select" name="product_id[]" class="select2 w-full" > -->
                <select id="product-select" name="product_id[]" class=" select2 w-full">
                    <option value="">Cari Nama Cust</option>
                    <option value="1">Salis</option>
                    <option value="2">Nilam</option>
                    <option value="3">Amartama</option>
                    <option value="3">Chalya Kirana</option>
                    <option value="3">Dewi</option>
                    <option value="3">Dimas</option>
                    <option value="3">Dimas putra abu</option>                 
                </select>
            </div>
        </div>
        <div class="w-full max-w-full px-3 shrink-0 md:w-6/12 md:flex-0">
            <div class="mb-4">
                <label for="nohp" class="inline-block mb-2 ml-1 font-bold text-xs text-slate-700 dark:text-white/80">No. HP</label>
                <input type="tel" name="nohp" value="" class="focus:shadow-primary-outline dark:bg-slate-850 dark:text-white text-sm leading-5.6 ease block w-full appearance-none rounded-lg border border-solid border-gray-300 bg-white bg-clip-padding px-3 py-2 font-normal text-gray-700 outline-none transition-all placeholder:text-gray-500 focus:border-blue-500 focus:outline-none" />
            </div>
        </div>
        <div class="w-full max-w-full px-3 shrink-0 md:w-full md:flex-0">
            <div class="mb-4">
                <label for="address" class="inline-block mb-2 ml-1 font-bold text-xs text-slate-700 dark:text-white/80">Address</label>
                <textarea type="text" name="address" value="" class="focus:shadow-primary-outline dark:bg-slate-850 dark:text-white text-sm leading-5.6 ease block w-full appearance-none rounded-lg border border-solid border-gray-300 bg-white bg-clip-padding px-3 py-2 font-normal text-gray-700 outline-none transition-all placeholder:text-gray-500 focus:border-blue-500 focus:outline-none"></textarea>
            </div>
        </div>
    </div>

    <hr class="h-px mx-0 my-4 bg-transparent border-0 opacity-25 bg-gradient-to-r from-transparent via-black/40 to-transparent dark:bg-gradient-to-r dark:from-transparent dark:via-white dark:to-transparent " />

    <p class="leading-normal uppercase dark:text-white dark:opacity-60 text-sm mb-3">Detail Produk</p>

    <div class="flex flex-wrap -mx-3">
        <div class="w-full max-w-full px-3 shrink-0 md:w-full md:flex-0 space-y-4" id="order-items">
            <!-- Judul Kolom -->
            <div class="grid grid-cols-3 gap-4 text-sm font-semibold text-gray-700">
                <div>Nama Produk</div>
                <div>Jumlah</div>
                <div>Harga Satuan</div>
            </div>

            <div class="grid grid-cols-3 gap-4">
                <input type="text" name="product_name[]" placeholder="Nama Produk" class="block w-full border border-gray-300 rounded-md py-2 text-sm focus:ring focus:border-blue-300" />
                <input type="number" name="quantity[]" placeholder="Jumlah" class="block w-full border border-gray-300 rounded-md py-2 text-sm focus:ring focus:border-blue-300" />
                <input type="tel" name="price[]" placeholder="Harga Satuan" class="block w-full border border-gray-300 rounded-md py-2 text-sm focus:ring focus:border-blue-300" />
            </div>
        </div>

        <div class="w-full px-3 mt-3">
            <button type="button" onclick="addProduct()" class="text-blue-600 text-sm hover:underline">
                + Tambah Produk
            </button>
        </div>
    </div>

    <hr class="h-px mx-0 my-4 bg-transparent border-0 opacity-25 bg-gradient-to-r from-transparent via-black/40 to-transparent dark:bg-gradient-to-r dark:from-transparent dark:via-white dark:to-transparent " />

    <p class="leading-normal uppercase dark:text-white dark:opacity-60 text-sm">Detail Pesanan</p>
    <div class="flex flex-wrap -mx-3">
        <div class="w-full max-w-full px-3 shrink-0 md:w-full md:flex-0">
            <div class="mb-4">
                <label for="address" class="inline-block mb-2 ml-1 font-bold text-xs text-slate-700 dark:text-white/80">Metode Pembayaran</label>
                <select name="payment_method" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    <option value="COD">COD (Bayar di Tempat)</option>
                    <option value="Transfer Bank">Transfer Bank</option>
                    <option value="OVO">OVO</option>
                    <option value="DANA">DANA</option>
                    <option value="GoPay">GoPay</option>
                    <option value="ShopeePay">ShopeePay</option>
                    <option value="QRIS">QRIS</option>
                    <option value="Tunai">Tunai (Ambil Sendiri)</option>
                </select>
            </div>
        </div>
        <div class="w-full max-w-full px-3 shrink-0 md:flex-0">
            <div class="mb-4">
                <label for="city" class="inline-block mb-2 ml-1 font-bold text-xs text-slate-700 dark:text-white/80">APAYAAAAA</label>
                <input type="text" name="city" value="" class="focus:shadow-primary-outline dark:bg-slate-850 dark:text-white text-sm leading-5.6 ease block w-full appearance-none rounded-lg border border-solid border-gray-300 bg-white bg-clip-padding px-3 py-2 font-normal text-gray-700 outline-none transition-all placeholder:text-gray-500 focus:border-blue-500 focus:outline-none" />
            </div>
        </div>

    </div>

    <hr class="h-px mx-0 my-4 bg-transparent border-0 opacity-25 bg-gradient-to-r from-transparent via-black/40 to-transparent dark:bg-gradient-to-r dark:from-transparent dark:via-white dark:to-transparent " />

    <p class="leading-normal uppercase dark:text-white dark:opacity-60 text-sm">Note</p>
    <div class="flex flex-wrap -mx-3">
        <div class="w-full max-w-full px-3 shrink-0 md:w-full md:flex-0">
            <div class="mb-4">
                <label for="about me" class="inline-block mb-2 ml-1 font-bold text-xs text-slate-700 dark:text-white/80">Text Here!</label>
                <textarea type="text" name="note" value="" class="focus:shadow-primary-outline dark:bg-slate-850 dark:text-white text-sm leading-5.6 ease block w-full appearance-none rounded-lg border border-solid border-gray-300 bg-white bg-clip-padding px-3 py-2 font-normal text-gray-700 outline-none transition-all placeholder:text-gray-500 focus:border-blue-500 focus:outline-none"></textarea>
            </div>
        </div>
    </div>
</div>

<!-- untuk tambah produk di detail pesanan -->
<script>
    function addProduct() {
        const container = document.getElementById('order-items');
        const newRow = document.createElement('div');
        newRow.className = "grid grid-cols-3 gap-4";
        newRow.innerHTML = `
            <input type="text" name="product_name[]" placeholder="Nama Produk" class="block w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring focus:border-blue-300" />
            <input type="number" name="quantity[]" placeholder="Jumlah" class="block w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring focus:border-blue-300" />
            <input type="tel" name="price[]" placeholder="Harga Satuan" class="block w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring focus:border-blue-300" />
        `;
        container.appendChild(newRow);
    }
</script>

<script>
    $(document).ready(function() {
        $('#product-select').select2({
            placeholder: "Cari disini...",
            allowClear: true
        });
    });
</script>


<script src="/assets/argon/js/plugins/chartjs.min.js"></script>
<script src="/assets/argon/js/plugins/perfect-scrollbar.min.js" async></script>
<script src="/assets/argon/js/assets/argon-dashboard-tailwind.js?v=1.0.1" async></script>

@endsection