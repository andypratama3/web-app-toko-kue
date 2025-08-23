<!-- Status Stepper Modal -->
<x-modal-custom id="statusStepperModal" title="Ubah Status Pesanan" toggle="statusStepperModal" size="2xl">
    <!-- Modal body -->
    <div class="p-6 space-y-4">
        <div id="statusStepperModalLoader" class="text-center">
            {{-- Loader --}}
        </div>

        <div id="statusStepperModalContent" class="hidden">
            <!-- Menampilkan Nomor Invoice dan Nama Pelanggan di atas stepper -->
            <div class="mb-4 text-center">
                <p class="text-sm text-gray-500 dark:text-gray-400">Nomor Invoice: <span id="modalStatusInvoiceNumber" class="font-semibold text-gray-800 dark:text-gray-200"></span></p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Pelanggan: <span id="modalStatusCustomerName" class="font-semibold text-gray-800 dark:text-gray-200"></span></p>
            </div>

            <div class="md:flex md:items-center md:justify-between md:text-center md:mb-6">

                <!-- Step Diambil -->
                <div class="relative flex items-start pb-8 md:flex-1 md:flex-col md:items-center md:pb-0">
                    <!-- Garis Vertikal (Hanya Mobile) -->
                    <div class="absolute top-5 left-5 -ml-px h-full w-0.5 bg-gray-200 dark:bg-gray-600 md:hidden" id="line-diambil-mobile"></div>
                    <!-- Ikon -->
                    <div class="relative z-10 w-10 h-10 flex items-center justify-center rounded-full border-2 border-gray-300 text-gray-500 dark:border-gray-600 dark:text-gray-400 transition-all duration-300 bg-white dark:bg-gray-700" id="step-diambil-icon">
                        <i class="fas fa-box"></i>
                    </div>
                    <!-- Detail Teks -->
                    <div class="ml-4 md:ml-0">
                        <p class="mt-0 md:mt-2 text-sm font-medium text-gray-700 dark:text-gray-300">Diambil</p>
                        <p id="pickedUpAt" class="text-xs text-gray-500 dark:text-gray-400"></p>
                    </div>
                </div>

                <!-- Garis Horizontal (Hanya Desktop) -->
                <div id="line-diantar" class="flex-1 h-0.5 bg-gray-200 dark:bg-gray-600 transition-all duration-300 hidden md:block"></div>

                <!-- Step Diantar -->
                <div class="relative flex items-start pb-8 md:flex-1 md:flex-col md:items-center md:pb-0">
                    <!-- Garis Vertikal (Hanya Mobile) -->
                    <div class="absolute top-5 left-5 -ml-px h-full w-0.5 bg-gray-200 dark:bg-gray-600 md:hidden" id="line-diantar-mobile"></div>
                    <!-- Ikon -->
                    <div class="relative z-10 w-10 h-10 flex items-center justify-center rounded-full border-2 border-gray-300 text-gray-500 dark:border-gray-600 dark:text-gray-400 transition-all duration-300 bg-white dark:bg-gray-700" id="step-diantar-icon">
                        <i class="fas fa-truck-moving"></i>
                    </div>
                    <!-- Detail Teks -->
                    <div class="ml-4 md:ml-0">
                        <p class="mt-0 md:mt-2 text-sm font-medium text-gray-700 dark:text-gray-300">Diantar</p>
                        <p id="deliveredAt" class="text-xs text-gray-500 dark:text-gray-400"></p>
                    </div>
                </div>

                <!-- Garis Horizontal (Hanya Desktop) -->
                <div id="line-diterima_pembeli" class="flex-1 h-0.5 bg-gray-200 dark:bg-gray-600 transition-all duration-300 hidden md:block"></div>

                <!-- Step Diterima Pembeli -->
                <div class="relative flex items-start md:flex-1 md:flex-col md:items-center">
                    <!-- Ikon -->
                    <div class="relative z-10 w-10 h-10 flex items-center justify-center rounded-full border-2 border-gray-300 text-gray-500 dark:border-gray-600 dark:text-gray-400 transition-all duration-300 bg-white dark:bg-gray-700" id="step-diterima_pembeli-icon">
                        <i class="fas fa-home"></i>
                    </div>
                    <!-- Detail Teks -->
                    <div class="ml-4 md:ml-0">
                        <p class="mt-0 md:mt-2 text-sm font-medium text-gray-700 dark:text-gray-300">Diterima Pembeli</p>
                        <p id="receivedByBuyerAt" class="text-xs text-gray-500 dark:text-gray-400"></p>
                    </div>
                </div>
            </div>

            <p class="pt-4 text-center text-gray-600 dark:text-gray-400" id="currentOrderStatusText"></p>

            <button id="updateStatusButton" type="button"
                class="w-full px-5 py-2.5 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800 transition-colors duration-200"
                data-order-id="" data-next-status="">
                <span id="updateStatusButtonText">Memuat...</span>
                <svg id="updateStatusButtonSpinner" class="hidden w-4 h-4 text-white animate-spin" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <!-- Spinner Path -->
                </svg>
            </button>
        </div>
    </div>
</x-modal-custom>
