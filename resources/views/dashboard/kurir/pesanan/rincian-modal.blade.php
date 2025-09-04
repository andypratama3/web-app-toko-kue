<x-modal-custom id="orderDetailsModal" title="Rincian Pesanan" toggle="orderDetailsModal" size="3xl">
    {{-- Loader saat data dimuat --}}
    <div id="modalLoader" class="text-center">
    </div>

    {{-- Konten utama modal, awalnya disembunyikan --}}
    <div id="modalContent" class="hidden space-y-4 max-h-[70vh] overflow-y-auto pr-4">

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <h4 class="mb-1 font-semibold text-gray-800 dark:text-white">🧾 Nomor Invoice</h4>
                <p id="modalInvoiceNumber" class="font-mono text-gray-700 dark:text-gray-300"></p>
            </div>
            <div>
                <h4 class="mb-1 font-semibold text-gray-800 dark:text-white">👤 Pelanggan</h4>
                <p id="customerName" class="text-gray-700 dark:text-gray-300"></p>
                <p id="customerPhone" class="text-sm text-gray-500 dark:text-gray-400"></p>
                <p id="customerAddress" class="text-sm text-gray-500 dark:text-gray-400"></p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 pt-4 border-t sm:grid-cols-2 dark:border-gray-600">
            <div>
                <h4 class="mb-1 font-semibold text-gray-800 dark:text-white">💳 Detail Pembayaran</h4>
                <p class="text-sm text-gray-700 dark:text-gray-300">Metode: <span id="paymentMethod"></span></p>
                <p class="text-sm text-gray-700 dark:text-gray-300">Tanggal Pesan: <span id="orderCreatedAt"></span></p>
                <p class="text-sm text-gray-700 dark:text-gray-300">Tanggal Lunas: <span id="orderPaidAt"></span></p>
            </div>
            <div>
                <h4 class="mb-1 font-semibold text-gray-800 dark:text-white">💰 Total Tagihan</h4>
                <p id="modalTotalAmount" class="text-2xl font-bold text-blue-600 dark:text-blue-500"></p>
            </div>
        </div>

        <div class="pt-4 border-t dark:border-gray-600">
            <h4 class="mb-2 font-semibold text-gray-800 dark:text-white">📦 Produk Dipesan</h4>
            <div id="productDetails" class="space-y-2">
            </div>
        </div>

        <div class="pt-4 border-t dark:border-gray-600">
            <h4 class="mb-2 font-semibold text-gray-800 dark:text-white">✅ Bukti Pembayaran</h4>

            <div id="rejectionNotice" class="hidden p-3 mb-3 text-center bg-red-100 rounded-lg">
                <p class="font-semibold text-red-800">Verifikasi ditolak oleh admin. Silakan periksa ulang dengan benar.</p>
            </div>

            {{-- Wrapper untuk gambar bukti pembayaran yang sudah ada --}}
            <div id="paymentProofImageWrapper" class="mb-2">
            </div>

            {{-- Form untuk upload (awalnya disembunyikan) --}}
            <form id="paymentUploadForm" class="hidden" enctype="multipart/form-data">
                <input type="file" name="payment_proof" id="payment_proof_file"
                    class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400"
                    required>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-300">Format: JPG, PNG, JPEG. Maks 2MB.</p>
                <button type="submit"
                    class="inline-flex items-center justify-center w-full px-4 py-2 mt-3 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 disabled:opacity-50">
                    <span id="uploadButtonText">Unggah Bukti</span>
                    <svg id="uploadButtonSpinner" class="hidden w-4 h-4 mr-2 text-white animate-spin" fill="none"
                        viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                            stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                </button>
            </form>

            {{-- Tampilan setelah diunggah (awalnya disembunyikan) --}}
            <div id="paymentProofUploaded" class="hidden p-3 text-center bg-green-100 rounded-lg">
                <p class="font-semibold text-green-800">Bukti pembayaran sudah diunggah.</p>
            </div>

            {{-- Pesan jika upload belum diizinkan --}}
            <div id="paymentUploadBlocker" class="hidden p-3 text-center bg-yellow-100 rounded-lg">
                <p class="font-semibold text-yellow-800">Harap ubah status menjadi "Diterima Pembeli" terlebih dahulu
                    sebelum mengunggah bukti pembayaran.</p>
            </div>
        </div>
    </div>
</x-modal-custom>

<div id="rincianModalZoomWrapper"
    class="fixed inset-0 flex items-center justify-center hidden bg-black z-[9999] bg-opacity-80"
    onclick="this.classList.add('hidden')">
    <img id="rincianModalZoomImg" src="" alt="Bukti Pembayaran"
        class="max-w-[90%] max-h-[90%] border-4 border-white rounded shadow-lg">
</div>

<script>
    // Fungsi untuk menampilkan gambar bukti pembayaran yang di-zoom
    function showRincianModalZoom(imgSrc) {
        const zoomWrapper = document.getElementById('rincianModalZoomWrapper');
        const zoomImg = document.getElementById('rincianModalZoomImg');
        zoomImg.src = imgSrc;
        zoomWrapper.classList.remove('hidden');
    }

    function populateOrderDetails(order) {
        try {
            const paymentProofImageWrapper = document.getElementById('paymentProofImageWrapper');
            paymentProofImageWrapper.innerHTML = '';
            if (order.payment_proof) {
                const imgSrc = `/storage/${order.payment_proof}`;
                paymentProofImageWrapper.innerHTML =
                    `<img src="${imgSrc}" class="object-cover w-32 h-32 border-2 border-gray-300 rounded shadow cursor-zoom-in" alt="Bukti Pembayaran" onclick="showRincianModalZoom('${imgSrc}')">`;
            }
        } catch (err) {
            document.getElementById('modalLoader').innerHTML =
                `<div class='text-center text-red-600'>${err.message}</div>`;
            document.getElementById('modalLoader').classList.remove('hidden');
            document.getElementById('modalContent').classList.add('hidden');
        }
    }
</script>
