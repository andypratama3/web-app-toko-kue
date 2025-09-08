<x-modal-custom id="showOrderModal" title="Detail Pesanan & Retur" size="3xl">
    {{-- Loader saat data dimuat --}}
    <div id="showOrderModalLoader" class="py-10 text-center">
        {{-- SVG Loader tidak berubah --}}
        <svg class="inline w-8 h-8 mr-2 text-gray-200 animate-spin dark:text-gray-600 fill-blue-600" viewBox="0 0 100 101"
            fill="none" xmlns="http://www.w3.org/2000/svg">
            <path
                d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z"
                fill="currentColor" />
            <path
                d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5424 39.6781 93.9676 39.0409Z" />
            ...
        </svg>
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
                <p id="showOrderModalCustomerCompany" class="text-sm text-gray-500 dark:text-gray-400"></p>
                <p id="showOrderModalCustomerAddress" class="text-sm text-gray-500 dark:text-gray-400"></p>
                <p id="showOrderModalCustomerPhone" class="text-sm text-gray-500 dark:text-gray-400"></p>
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
                    <p class="text-sm text-gray-500 line-through dark:text-gray-400">
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
            <div id="showOrderModalReturnedProducts" class="p-3 space-y-2 rounded-lg bg-red-50 dark:bg-gray-700"></div>
            <div class="flex justify-end pt-2 mt-2 border-t border-red-200 dark:border-red-700">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Total Nilai Retur:</p>
                    <p id="showOrderModalTotalReturned"
                        class="font-bold text-right text-red-800 text-md dark:text-red-400"></p>
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
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded js-close-modal-btn hover:bg-gray-300">Tutup</button>
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
    <div class="flex items-center justify-between p-2 rounded-lg bg-gray-50 dark:bg-gray-700">
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
    <div class="flex items-center justify-between">
        <div>
            <p class="font-medium text-gray-800 dark:text-white" data-role="name"></p>
            <p class="text-sm text-gray-500 dark:text-gray-400" data-role="variant"></p>
            <p class="text-sm text-gray-500 dark:text-gray-400" data-role="quantity"></p>
        </div>
    </div>
</template>
