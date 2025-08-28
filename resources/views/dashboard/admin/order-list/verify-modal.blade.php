<div id="verifyOrderModal" class="fixed inset-0 z-50 flex items-center justify-center hidden bg-black bg-opacity-40">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-2xl p-6 relative">
        <div id="verifyModalLoader" class="text-center py-10">
            <svg class="w-8 h-8 mx-auto text-blue-600 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
            </svg>
            <p class="mt-4 text-lg font-medium text-gray-700">Memuat detail pesanan...</p>
        </div>
        <div id="verifyModalContent" class="hidden">
            <h3 class="text-xl font-bold mb-4">Verifikasi Pesanan</h3>
            <div class="mb-2"><b>Invoice:</b> <span id="verifyModalInvoice">-</span></div>
            <div class="mb-2"><b>Customer:</b> <span id="verifyModalCustomer">-</span></div>
            <div class="mb-2"><b>Kurir:</b> <span id="verifyModalKurir">-</span></div>
            <div class="mb-2"><b>Status:</b> <span id="verifyModalStatus">-</span></div>
            <div class="mb-2"><b>Total:</b> <span id="verifyModalTotal">-</span></div>
            <div class="mb-2"><b>Bukti Pembayaran:</b> <span id="verifyModalPaymentProof" class="inline-block"></span></div>
            <div class="flex justify-end mt-6 space-x-2">
                <button id="btnRejectOrder" class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600">Tolak</button>
        <!-- Modal Zoom Image -->
        <div id="verifyModalZoomWrapper" class="fixed inset-0 z-60 flex items-center justify-center hidden bg-black bg-opacity-80">
            <img id="verifyModalZoomImg" src="" alt="Bukti Pembayaran" class="max-w-full max-h-full rounded shadow-lg border-4 border-white">
        </div>
                <button id="btnVerifyOrder" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Verifikasi</button>
                <button onclick="closeVerifyModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">Tutup</button>
            </div>
        </div>
    </div>
</div>
