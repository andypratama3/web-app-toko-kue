<x-modal-custom id="orderDetailsModal" title="Rincian Pesanan" size="4xl">
    {{-- Loader saat data dimuat --}}
    <div id="modalLoader" class="text-center p-8">
        {{-- Loader akan diinjeksi oleh JavaScript --}}
    </div>

    {{-- Konten utama modal, awalnya disembunyikan --}}
    <div id="modalContent" class="hidden">
        <!-- Layout utama menggunakan grid, 1 kolom di mobile, 3 kolom di desktop -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 p-1">

            <!-- Kolom Kiri (Detail Pesanan & Daftar Produk) -->
            <div class="lg:col-span-2 space-y-6">

                <!-- Panel Info Pesanan & Pelanggan -->
                <div class="bg-white dark:bg-gray-800 p-5 rounded-xl shadow-md border border-gray-200 dark:border-gray-700">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-5">Detail Pesanan</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-5">
                        <!-- Nomor Invoice -->
                        <div>
                            <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Nomor Invoice</label>
                            <p id="modalInvoiceNumber" class="text-lg font-semibold font-mono text-gray-800 dark:text-gray-200"></p>
                        </div>
                        <!-- Detail Pelanggan -->
                        <div>
                            <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Pelanggan</label>
                            <p id="customerName" class="text-lg font-semibold text-gray-800 dark:text-gray-200"></p>
                            <p id="customerCompanyName" class="text-sm text-gray-600 dark:text-gray-300"></p>
                            <p id="customerPhone" class="text-sm text-gray-600 dark:text-gray-300"></p>
                            <p id="customerAddress" class="text-sm text-gray-600 dark:text-gray-300"></p>
                        </div>
                        <!-- Metode Pembayaran -->
                        <div>
                            <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Metode Pembayaran</label>
                            <p class="text-md font-semibold uppercase text-gray-800 dark:text-gray-200" id="paymentMethod"></p>
                        </div>
                        <!-- Tanggal Penting -->
                        <div>
                            <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Tanggal Penting</label>
                            <p class="text-sm text-gray-600 dark:text-gray-300">Tgl. Pesan: <span id="orderCreatedAt" class="font-semibold"></span></p>
                            <p class="text-sm text-gray-600 dark:text-gray-300">Tgl. Lunas: <span id="orderPaidAt" class="font-semibold"></span></p>
                        </div>
                    </div>
                    <!-- Catatan Pesanan Section -->                    
                    <div  class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700 ">
                        <label class="text-md font-medium text-gray-900 dark:text-gray-400 mb-1">Catatan Pesanan</label>
                        <div id="orderNotesContainer" class="p-2 text-center bg-yellow-100 dark:bg-yellow-900/20 rounded-lg font-semibold text-gray-800 dark:text-gray-50">
                            <p id="orderNotes"></p>
                        </div>
                    </div>
                </div>

                <!-- Panel Daftar Produk -->
                <div class="bg-white dark:bg-gray-800 p-5 rounded-xl shadow-md border border-gray-200 dark:border-gray-700">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">🛒 Produk Dipesan</h3>
                    <div id="productDetailsWrapper" class="overflow-x-auto">
                        <div id="productDetails" class="space-y-3">
                            <!-- Item produk akan diinjeksi di sini oleh JavaScript -->
                        </div>
                    </div>
                </div>

            </div>

            <!-- Kolom Kanan (Status, Total, dan Aksi) -->
            <div class="lg:col-span-1 space-y-6">

                <!-- Panel Status & Total Tagihan -->
                <div class="bg-white dark:bg-gray-800 p-5 rounded-xl shadow-md border border-gray-200 dark:border-gray-700">
                    <!-- Status Pesanan -->
                    <div class="mb-5">
                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2 block">Status Pesanan</label>
                        <div id="modalOrderStatusSection" class="flex items-center">
                            <span id="modalOrderStatusBadge" class="px-4 py-1.5 text-sm font-bold rounded-full w-full text-center"></span>
                        </div>
                    </div>
                    <!-- Total Tagihan -->
                    <div id="totalAmountSection" class="border-t border-gray-200 dark:border-gray-700 pt-4">
                        <div id="singleTotalAmountContainer">
                            <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Tagihan</label>
                            <p id="modalTotalAmount" class="text-xl font-extrabold text-blue-600 dark:text-blue-500"></p>
                        </div>
                        <div id="returnedTotalAmountContainer" class="hidden">
                            <label class="text-md font-medium text-gray-500 dark:text-gray-400">Total Tagihan</label>
                            <p id="modalInitialTotalAmount" class="text-sm font-bold text-gray-400 dark:text-gray-500 line-through"></p>
                            <!-- <label class="mt-2 text-sm font-medium text-gray-500 dark:text-gray-400">Tagihan Terbaru</label> -->
                            <p id="modalLatestTotalAmount" class="text-2xl font-extrabold text-green-600 dark:text-green-500"></p>
                        </div>
                    </div>
                </div>

                <!-- Panel Bukti Pembayaran / Retur -->
                <div class="bg-white dark:bg-gray-800 p-5 rounded-xl shadow-md border border-gray-200 dark:border-gray-700">
                    <h3 id="paymentProofTitle" class="text-xl font-bold text-gray-900 dark:text-white mb-4">Bukti Pembayaran</h3>

                    <form id="paymentUploadForm" class="hidden space-y-3" enctype="multipart/form-data">
                        <input type="file" name="payment_proof" id="payment_proof_file" class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400" required>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-300">Format: JPG, PNG, JPEG. Maks 2MB.</p>
                        <a id="compress-link" href="https://gentle-druid-f8b395.netlify.app/" target="_blank" rel="noopener noreferrer" class="hidden items-center gap-2 text-sm text-blue-600 hover:underline dark:text-blue-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5v-4m0 0h-4m4 0l-5-5" />
                            </svg>
                            <span>Kompres gambar</span>
                        </a>
                        <button type="submit" class="inline-flex items-center justify-center w-full px-5 py-2.5 text-sm font-medium text-center text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 disabled:opacity-50 transition-colors">
                            <span id="uploadButtonText">Unggah Bukti</span>
                            <svg id="uploadButtonSpinner" aria-hidden="true" class="hidden w-4 h-4 ml-2 text-white animate-spin" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor" />
                                <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="currentFill" />
                            </svg>
                        </button>
                    </form>

                    <div id="paymentProofUploaded" class="hidden text-center">
                        <p class="mb-2 font-medium text-gray-800 dark:text-white" id="proofUploadedTitle"></p>
                        <img id="proofImage" src="" alt="Bukti Unggahan" class="object-cover w-full mx-auto border-2 border-gray-200 dark:border-gray-600 rounded-lg shadow-md cursor-pointer h-auto max-h-48" onclick="openImageViewer(this.src)">
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Klik gambar untuk memperbesar.</p>
                    </div>

                    <div id="paymentUploadBlocker" class="hidden p-4 text-center bg-yellow-100 dark:bg-yellow-900/20 rounded-lg">
                        <p class="font-medium text-yellow-800 dark:text-yellow-300 text-sm">Status pesanan tidak memungkinkan untuk unggah bukti saat ini.</p>
                    </div>
                </div>

                <!-- Panel Aksi -->
                <div id="returnRequestButtonContainer" class="hidden">
                    <button type="button" id="requestReturnButton" class="js-open-modal-btn group inline-flex items-center justify-center w-full px-5 py-3 text-sm font-medium text-center text-white bg-red-600 rounded-lg hover:bg-red-700 focus:ring-4 focus:outline-none focus:ring-red-300 transition-colors" data-target-modal="returnProductModal">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-2 transform group-hover:-translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        Ajukan Pengembalian Produk
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-modal-custom>

{{-- Modal untuk Zoom Gambar --}}
<div id="imageViewerModal"
    class="fixed inset-0 z-[9999] flex items-center justify-center w-full h-full bg-black bg-opacity-80 hidden transition-opacity duration-300"
    onclick="closeImageViewer()">
    <img id="fullSizeImage" src="" class="max-w-[90vw] max-h-[90vh] object-contain rounded-lg shadow-2xl">
    <button class="absolute text-4xl font-light text-white top-4 right-6 hover:text-gray-300 transition-colors"
        aria-label="Close image viewer">&times;</button>
</div>