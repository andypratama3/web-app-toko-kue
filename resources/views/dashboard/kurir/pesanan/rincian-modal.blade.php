<x-modal-custom id="orderDetailsModal" title="Rincian Pesanan" size="3xl">
    {{-- Loader saat data dimuat --}}
    <div id="modalLoader" class="text-center">
        {{-- Loader akan diinjeksi oleh JavaScript --}}
    </div>

    {{-- Konten utama modal, awalnya disembunyikan --}}
    <div id="modalContent" class="hidden space-y-3">
        <script>
            // Script untuk membuat seksi di mobile collapsible (satu terbuka, yang lain tertutup)
            document.addEventListener('DOMContentLoaded', function() {
                if (window.innerWidth < 640) {
                    const sections = ['productSection', 'paymentProofSection'];
                    sections.forEach(id => {
                        const summary = document.querySelector(`#${id} summary`);
                        if (summary) {
                            summary.addEventListener('click', function(e) {
                                setTimeout(() => {
                                    sections.forEach(otherId => {
                                        if (otherId !== id) {
                                            const el = document.getElementById(otherId);
                                            if (el && el.open) el.open = false;
                                        }
                                    });
                                }, 10);
                            });
                        }
                    });
                }
            });
        </script>

        <div class="grid grid-cols-1 gap-2 sm:gap-4 sm:grid-cols-2">
            <div>
                <h4 class="mb-1 text-base sm:text-md font-semibold text-gray-800 dark:text-white">🧾 Nomor Invoice</h4>
                <p id="modalInvoiceNumber" class="font-mono text-gray-700 dark:text-gray-300 text-sm sm:text-base"></p>
            </div>
            <div>
                <h4 class="mb-1 text-base sm:text-md font-semibold text-gray-800 dark:text-white">👤 Pelanggan</h4>
                <p id="customerName" class="text-gray-700 dark:text-gray-300 font-bold text-sm sm:text-base"></p>
                <p id="customerPhone" class="text-sm text-gray-500 dark:text-gray-400"></p>
                <p id="customerAddress" class="text-sm text-gray-500 dark:text-gray-400"></p>
            </div>
        </div>
        <div class="grid grid-cols-1 gap-2 sm:gap-4 pt-2 sm:pt-4 border-t sm:grid-cols-2 dark:border-gray-600">
            <div>
                <h4 class="mb-1 text-base sm:text-md font-semibold text-gray-800 dark:text-white">💰 Detail Pembayaran</h4>
                <p class="text-sm text-gray-700 dark:text-gray-300">Metode : <span class="uppercase font-bold" id="paymentMethod"></span></p>
            </div>
            <div>
                <h4 class="mb-1 text-base sm:text-md font-semibold text-gray-800 dark:text-white">🚚 Detail Pengiriman</h4>
                <p class="text-sm text-gray-700 dark:text-gray-300">Tgl. Pesan : <span id="orderCreatedAt"></span></p>
                <p class="text-sm text-gray-700 dark:text-gray-300">Tgl. Lunas : <span id="orderPaidAt"></span></p>
            </div>
            <div id="totalAmountSection">
                <div id="singleTotalAmountContainer">
                    <h4 class="mb-1 text-base sm:text-md font-semibold text-gray-800 dark:text-white">📜 Total Tagihan</h4>
                    <p id="modalTotalAmount" class="text-xl sm:text-2xl font-bold text-blue-600 dark:text-blue-500"></p>
                </div>
                <div id="returnedTotalAmountContainer" class="hidden">
                    <h4 class="mb-1 text-base sm:text-md font-semibold text-gray-800 dark:text-white">📜 Total Tagihan Awal</h4>
                    <p id="modalInitialTotalAmount" class="text-lg sm:text-xl font-bold text-gray-500 dark:text-gray-400 line-through"></p>
                    <h4 class="mt-2 mb-1 text-base sm:text-md font-semibold text-gray-800 dark:text-white">🧾 Total Tagihan Terbaru</h4>
                    <p id="modalLatestTotalAmount" class="text-xl sm:text-2xl font-bold text-green-600 dark:text-green-500"></p>
                </div>
            </div>
            <div>
                <h4 class="mb-1 text-base sm:text-md font-semibold text-gray-800 dark:text-white ">Status Pesanan</h4>
                <div id="modalOrderStatusSection" class="flex items-center space-x-2 mt-2">
                    <span id="modalOrderStatusIcon" class="hidden flex items-center justify-center"></span>
                    <span id="modalOrderStatusBadge" class="flex-shrink-0 px-3 py-1 text-sm font-semibold rounded-full whitespace-nowrap"></span>
                </div>
            </div>
        </div>

        {{-- Tombol untuk Ajukan Retur --}}
        <div id="returnRequestButtonContainer" class="hidden pt-4 border-t dark:border-gray-600">
            <button type="button" id="requestReturnButton" class="js-open-modal-btn inline-flex items-center justify-center w-full px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 focus:ring-4 focus:outline-none focus:ring-red-300" data-target-modal="returnProductModal">
                <i class="fas fa-undo mr-2"></i> Ajukan Pengembalian Produk
            </button>
        </div>

        <div class="pt-2 sm:pt-4 border-t dark:border-gray-600">
            <details id="productSection" class="block sm:open" open>
                <summary class="sm:hidden font-semibold text-gray-800 dark:text-white cursor-pointer py-2">🛒 Produk Dipesan</summary>
                <div class="px-2 py-1 sm:px-0">
                    <h4 class="mb-2 text-base sm:text-md font-semibold text-gray-800 dark:text-white hidden sm:block">🛒 Produk Dipesan</h4>
                    <div id="productDetailsWrapper" class="overflow-x-auto">
                        <div id="productDetails" class="space-y-2 min-w-[260px]">
                            </div>
                    </div>
                </div>
            </details>
        </div>
        <div class="pt-2 sm:pt-4 border-t dark:border-gray-600">
            <details id="paymentProofSection" class="block sm:open" open>
                <summary class="sm:hidden font-semibold text-gray-800 dark:text-white cursor-pointer py-2">Bukti Pembayaran/Retur</summary>
                <div class="px-2 py-1 sm:px-0">
                    <h4 id="paymentProofTitle" class="mb-2 text-base sm:text-md font-semibold text-gray-800 dark:text-white hidden sm:block">Bukti Pembayaran</h4>

                    <form id="paymentUploadForm" class="hidden" enctype="multipart/form-data">
                        <input type="file" name="payment_proof" id="payment_proof_file" class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400" required>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-300">Format: JPG, PNG, JPEG. Maks 2MB.</p>
                        <button type="submit" class="inline-flex items-center justify-center w-full px-4 py-2 mt-3 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 disabled:opacity-50">
                            <span id="uploadButtonText">Unggah Bukti</span>
                            <svg id="uploadButtonSpinner" class="hidden w-4 h-4 text-white animate-spin" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                                {{-- Path Spinner --}}
                            </svg>
                        </button>
                    </form>

                    <div id="paymentProofUploaded" class="hidden text-center">
                        <p class="mb-2 font-semibold text-gray-800 dark:text-white" id="proofUploadedTitle"></p>
                        <img id="proofImage" src="" alt="Bukti Unggahan" class="object-contain w-full max-w-xs mx-auto border rounded-lg shadow-md cursor-pointer h-auto max-h-48 dark:border-gray-600" onclick="openImageViewer(this.src)">
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Klik gambar untuk memperbesar.</p>
                    </div>

                    <div id="paymentUploadBlocker" class="hidden p-3 text-center bg-yellow-100 rounded-lg">
                        <p class="font-semibold text-yellow-800 text-base">Status pesanan tidak memungkinkan untuk unggah bukti saat ini.</p>
                    </div>
                </div>
            </details>
        </div>
    </div>
</x-modal-custom>

{{-- Modal untuk Zoom Gambar --}}
<div id="imageViewerModal" class="fixed inset-0 z-[9999] flex items-center justify-center w-full h-full bg-black bg-opacity-80 hidden" onclick="closeImageViewer()">
    <img id="fullSizeImage" src="" class="max-w-[90vw] max-h-[90vh] object-contain">
    <button class="absolute text-3xl font-bold text-white top-4 right-6" aria-label="Close image viewer">&times;</button>
</div>
