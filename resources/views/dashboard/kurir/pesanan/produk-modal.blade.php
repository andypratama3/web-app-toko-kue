<x-modal-custom id="produkModal" title="Tambah Produk" toggle="produk-modal" tabindex="-1" aria-hidden="true">
    {{-- class="fixed inset-0 top-0 left-0 right-0 z-50 flex items-center justify-center hidden w-full h-full overflow-x-hidden overflow-y-auto transition-opacity duration-300 bg-black bg-opacity-50"> --}}
    {{-- <div class="relative w-full max-w-2xl p-4 transition-transform duration-300 ease-out transform scale-95">
        <div class="relative bg-white rounded-lg shadow dark:bg-gray-700">
            <!-- Modal header -->
            <div
                class="flex items-center justify-between p-4 border-b border-gray-200 rounded-t md:p-5 dark:border-gray-600">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Pilih Produk
                </h3>
                <button type="button" onclick="hideProdukModal()"
                    class="inline-flex items-center justify-center w-8 h-8 text-sm text-gray-400 bg-transparent rounded-lg hover:bg-gray-200 hover:text-gray-900 ms-auto dark:hover:bg-gray-600 dark:hover:text-white">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                    </svg>
                    <span class="sr-only">Close modal</span>
                </button>
            </div> --}}
            <!-- Modal body, scrollable -->
            <div class="p-4 md:p-5 max-h-[60vh] overflow-y-auto">
                <div id="pilihan-produk" class="space-y-2"></div>
            </div>
        {{-- </div>
    </div> --}}
</x-modal-custom>
