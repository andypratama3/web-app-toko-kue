<x-modal-custom id="orderDetailsModal" title="Rincian Pesanan" toggle="orderDetailsModal" size="3xl">
    <div id="modalLoader" class="text-center">
        <!-- Loader SVG and text will be injected here by JS -->
    </div>
    
    <!-- PERUBAHAN DI SINI: Ditambahkan max-h-[70vh] overflow-y-auto pr-4 -->
    <div id="modalContent" class="hidden space-y-4 max-h-[70vh] overflow-y-auto pr-4">
        <!-- Invoice and Customer Details -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <h4 class="mb-1 font-semibold text-gray-800 dark:text-white">Nomor Invoice</h4>
                <p id="modalInvoiceNumber" class="font-mono text-gray-700 dark:text-gray-300"></p>
            </div>
            <div>
                <h4 class="mb-1 font-semibold text-gray-800 dark:text-white">Pelanggan</h4>
                <p id="customerName" class="text-gray-700 dark:text-gray-300"></p>
                <p id="customerPhone" class="text-sm text-gray-500 dark:text-gray-400"></p>
                <p id="customerAddress" class="text-sm text-gray-500 dark:text-gray-400"></p>
            </div>
        </div>
        <!-- Order and Payment Details -->
        <div class="grid grid-cols-1 gap-4 pt-4 border-t sm:grid-cols-2 dark:border-gray-600">
            <div>
                <h4 class="mb-1 font-semibold text-gray-800 dark:text-white">Detail Pembayaran</h4>
                <p class="text-sm text-gray-700 dark:text-gray-300">Metode: <span id="paymentMethod"></span></p>
                <p class="text-sm text-gray-700 dark:text-gray-300">Tanggal Pesan: <span id="orderCreatedAt"></span></p>
                <p class="text-sm text-gray-700 dark:text-gray-300">Tanggal Lunas: <span id="orderPaidAt"></span></p>
            </div>
            <div>
                <h4 class="mb-1 font-semibold text-gray-800 dark:text-white">Total Tagihan</h4>
                <p id="modalTotalAmount" class="text-2xl font-bold text-blue-600 dark:text-blue-500"></p>
            </div>
        </div>
        <!-- Product Details -->
        <div class="pt-4 border-t dark:border-gray-600">
            <h4 class="mb-2 font-semibold text-gray-800 dark:text-white">Produk Dipesan</h4>
            <div id="productDetails" class="space-y-2">
                <!-- Product items will be injected here by JS -->
            </div>
        </div>
        <!-- Payment Proof Section -->
        <div class="pt-4 border-t dark:border-gray-600">
            <h4 class="mb-2 font-semibold text-gray-800 dark:text-white">Bukti Pembayaran</h4>
            
            <!-- Form untuk upload (awalnya hidden) -->
            <form id="paymentUploadForm" class="hidden" enctype="multipart/form-data">
                <input type="file" name="payment_proof" id="payment_proof_file" class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400" required>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-300">Format: JPG, PNG, JPEG. Maks 2MB.</p>
                <button type="submit" class="inline-flex items-center justify-center w-full px-4 py-2 mt-3 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 disabled:opacity-50" disabled>
                    <span id="uploadButtonText">Unggah Bukti</span>
                    <svg id="uploadButtonSpinner" class="hidden w-4 h-4 text-white animate-spin" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <!-- Spinner path -->
                    </svg>
                </button>
                <p id="uploadButtonStatus" class="hidden mt-2 text-sm text-center text-blue-600">Mengunggah...</p>
            </form>

            <!-- Tampilan setelah diunggah (awalnya hidden) -->
            <div id="paymentProofUploaded" class="hidden p-3 text-center bg-green-100 rounded-lg">
                <p class="font-semibold text-green-800">Bukti pembayaran sudah diunggah.</p>
            </div>

            <!-- Pesan jika upload belum diizinkan -->
            <div id="paymentUploadBlocker" class="hidden p-3 text-center bg-yellow-100 rounded-lg">
                <p class="font-semibold text-yellow-800">Harap ubah status menjadi "Diterima Pembeli" terlebih dahulu sebelum mengunggah bukti pembayaran.</p>
            </div>

        </div>
    </div>
</x-modal-custom>
