<x-modal-custom id="showOrderModal" title="Detail Pesanan & Retur" size="3xl">
    {{-- Loader saat data dimuat --}}
    <div id="showOrderModalLoader" class="py-10 text-center">
        {{-- SVG Loader tidak berubah --}}
        <svg class="inline w-8 h-8 mr-2 text-gray-200 animate-spin dark:text-gray-600 fill-blue-600" viewBox="0 0 100 101"
            fill="none" xmlns="http://www.w3.org/2000/svg">...</svg>
    </div>

    {{-- Konten utama modal, awalnya disembunyikan --}}
    <div id="showOrderModalContent" class="hidden space-y-4 max-h-[70vh] overflow-y-auto pr-4">
        {{-- Rincian Invoice & Pelanggan --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <h4 class="mb-1 font-semibold text-gray-800 dark:text-white">🧾 Nomor Invoice</h4>
                <p id="showOrderModalInvoiceNumber" class="font-mono text-gray-700 dark:text-gray-300"></p>
            </div>
            <div>
                <h4 class="mb-1 font-semibold text-gray-800 dark:text-white">👤 Pelanggan</h4>
                <p id="showOrderModalCustomerName" class="font-bold text-gray-700 dark:text-gray-300"></p>
                <p id="showOrderModalCustomerAddress" class="text-sm text-gray-500 dark:text-gray-400"></p>
            </div>
        </div>

        {{-- Detail Pembayaran & Pengiriman --}}
        <div class="grid grid-cols-1 gap-4 pt-4 border-t sm:grid-cols-2 dark:border-gray-600">
            <div>
                <h4 class="mb-1 font-semibold text-gray-800 dark:text-white">💰 Detail Pembayaran</h4>
                <p id="showOrderModalPaymentMethod" class="text-sm text-gray-700 dark:text-gray-300"></p>

                {{-- Kontainer untuk total tunggal (tanpa retur) --}}
                <div id="singleTotalContainer">
                    <p id="showOrderModalTotalAmount" class="text-sm text-gray-700 dark:text-gray-300"></p>
                </div>

                {{-- Kontainer BARU untuk total ganda (dengan retur) --}}
                <div id="returnedTotalContainer" class="hidden mt-2">
                    <p class="text-sm text-gray-500 dark:text-gray-400 line-through">
                        Total Awal: <span id="initialTotalAmount"></span>
                    </p>
                    <p class="text-sm font-bold text-green-600 dark:text-green-400">
                        Total Terbaru: <span id="latestTotalAmount"></span>
                    </p>
                </div>
            </div>
            <div>
                <h4 class="mb-1 font-semibold text-gray-800 dark:text-white">🚚 Detail Pengiriman</h4>
                <p class="text-sm text-gray-700 dark:text-gray-300">Tgl. Pesan : <span
                        id="showOrderModalCreatedAt"></span></p>
                <p class="text-sm text-gray-700 dark:text-gray-300">Tgl. Lunas : <span id="showOrderModalPaidAt"></span>
                </p>
            </div>
        </div>

        {{-- Produk Dipesan --}}
        <div class="pt-4 border-t dark:border-gray-600">
            <h4 class="mb-2 font-semibold text-gray-800 dark:text-white">📦 Produk Dipesan</h4>
            <div id="showOrderModalProductDetails" class="space-y-2"></div>
        </div>

        {{-- Bagian Produk Retur --}}
        <div id="showOrderModalReturnedProductsSection" class="hidden pt-4 border-t border-red-300 dark:border-red-700">
            <h4 class="mb-2 font-semibold text-red-800 dark:text-red-400">♻️ Produk yang Diretur</h4>
            <div id="showOrderModalReturnedProducts" class="p-3 space-y-2 bg-red-50 rounded-lg dark:bg-gray-700"></div>
            <div class="flex justify-end pt-2 mt-2 border-t border-red-200 dark:border-red-700">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Total Nilai Retur:</p>
                    <p id="showOrderModalTotalReturned"
                        class="text-md font-bold text-red-800 dark:text-red-400 text-right"></p>
                </div>
            </div>
            <div id="showOrderModalReturnProof" class="mt-3"></div>
        </div>

        {{-- Bukti Pembayaran --}}
        <div class="pt-4 border-t dark:border-gray-600">
            <h4 class="mb-2 font-semibold text-gray-800 dark:text-white">✅ Bukti Pembayaran</h4>
            <div id="showOrderModalPaymentProof" class="mb-2"></div>
        </div>
    </div>

    <x-slot name="footer">
        <div
            class="flex items-center justify-end p-4 space-x-3 border-t border-gray-200 rounded-b md:p-5 dark:border-gray-600">
            <button type="button"
                class="js-close-modal-btn px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded hover:bg-gray-300">Tutup</button>
        </div>
    </x-slot>
</x-modal-custom>

{{-- Wrapper untuk zoom gambar --}}
<div id="showOrderModalZoomWrapper"
    class="fixed inset-0 z-[9999] items-center justify-center hidden bg-black bg-opacity-80">
    <img id="showOrderModalZoomImg" src="" alt="Bukti Pembayaran"
        class="max-w-[90%] max-h-[90%] border-4 border-white rounded shadow-lg">
</div>

{{-- TEMPLATE UNTUK ITEM PRODUK (agar tidak membuat string HTML di JS) --}}
<template id="orderItemTemplate">
    <div class="flex justify-between items-center p-2 bg-gray-50 rounded-lg dark:bg-gray-700">
        <div>
            <p class="font-medium text-gray-800 dark:text-white" data-role="name"></p>
            <p class="text-sm text-gray-500 dark:text-gray-400" data-role="variant"></p>
            <p class="text-sm text-gray-500 dark:text-gray-400" data-role="quantity-price"></p>
        </div>
        <div class="text-right">
            <p class="font-medium text-gray-800 dark:text-white" data-role="subtotal"></p>
        </div>
    </div>
</template>

<template id="returnItemTemplate">
    <div class="flex justify-between items-center">
        <div>
            <p class="font-medium text-gray-800 dark:text-white" data-role="name"></p>
            <p class="text-sm text-gray-500 dark:text-gray-400" data-role="variant"></p>
            <p class="text-sm text-gray-500 dark:text-gray-400" data-role="quantity"></p>
        </div>
    </div>
</template>
