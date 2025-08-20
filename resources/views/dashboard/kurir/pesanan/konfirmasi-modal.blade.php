{{-- Menggunakan komponen modal-custom yang sudah ada --}}
<x-modal-custom id="konfirmasiModal" title="Konfirmasi Detail Pesanan" toggle="konfirmasiModal" size="3xl">
    <div id="detail-pesanan-modal">
        {{-- Data Customer --}}
        <div class="mb-6">
            <h4 class="pb-2 mb-3 text-lg font-semibold text-gray-800 border-b dark:text-white">👤 Data Pelanggan</h4>
            <div class="grid grid-cols-1 gap-4 text-sm md:grid-cols-2">
                <div>
                    <p class="font-bold text-gray-600 dark:text-gray-400">Nama:</p>
                    <p id="modal-customer-name" class="text-gray-900 dark:text-white">-</p>
                </div>
                <div>
                    <p class="font-bold text-gray-600 dark:text-gray-400">No. HP:</p>
                    <p id="modal-customer-phone" class="text-gray-900 dark:text-white">-</p>
                </div>
                <div class="md:col-span-2">
                    <p class="font-bold text-gray-600 dark:text-gray-400">Alamat:</p>
                    <p id="modal-customer-address" class="text-gray-900 dark:text-white">-</p>
                </div>
            </div>
        </div>

        {{-- Detail Pembayaran --}}
        <div class="mb-6">
            <h4 class="pb-2 mb-3 text-lg font-semibold text-gray-800 border-b dark:text-white">💳 Detail Pembayaran</h4>
            <div class="text-sm">
                <p class="font-bold text-gray-600 dark:text-gray-400">Metode Pembayaran:</p>
                <p id="modal-payment-method" class="text-gray-900 dark:text-white">-</p>
            </div>
        </div>

        {{-- Rincian Produk --}}
        <div>
            <h4 class="pb-2 mb-3 text-lg font-semibold text-gray-800 border-b dark:text-white">🛍️ Rincian Produk</h4>
            <div id="modal-product-list" class="pr-2 space-y-3 overflow-y-auto max-h-60">
                {{-- Daftar produk akan diisi oleh JavaScript --}}
            </div>
        </div>

        {{-- Total Keseluruhan --}}
        <div class="flex items-center justify-end pt-4 mt-6 border-t border-gray-200 dark:border-gray-600">
            <span class="text-lg font-bold text-gray-800 dark:text-white">Total Keseluruhan:</span>
            <span id="modal-total-amount" class="ml-4 text-xl font-bold text-green-700 dark:text-green-500">-</span>
        </div>
    </div>

    {{-- Tombol Aksi di Footer Modal --}}
    <div class="flex items-center justify-end pt-4 mt-4 space-x-3 border-t border-gray-200 dark:border-gray-600">
        <button type="button" data-modal-hide="konfirmasiModal" class="px-6 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 focus:outline-none dark:bg-gray-700 dark:text-gray-300 dark:border-gray-500 dark:hover:bg-gray-600">
            Batal
        </button>
        <button type="button" id="submit-order-button" onclick="submitOrder()" class="px-6 py-2 text-sm font-medium text-white bg-[#748c54] rounded-lg hover:bg-[#5a6e40] focus:outline-none focus:ring-4 focus:ring-green-300 dark:focus:ring-green-800">
            Konfirmasi & Simpan Pesanan
        </button>
    </div>
</x-modal-custom>
