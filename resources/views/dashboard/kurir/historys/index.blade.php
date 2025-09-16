@extends('layouts.argon')
@section('title', 'History Pesanan')
@section('page_title', 'History')

@section('content')
    <div class="flex-auto p-3 pt-0 -mx-3">
        <div class="p-2.5 bg-white shadow-md rounded-xl dark:bg-gray-800 dark:border-gray-700 min-h-[715px]">
            <div class="flex flex-col gap-4 mb-4 md:flex-row md:items-center md:justify-between">
                <div class="w-full md:w-1/2 lg:w-1/3">
                    <form onsubmit="return false;">
                        <label for="live-search-input" class="sr-only">Cari</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <svg aria-hidden="true" class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" /></svg>
                            </div>
                            <input type="text" id="live-search-input" name="search"
                                class="block w-full p-2 pl-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                                placeholder="Cari invoice atau customer...">
                        </div>
                    </form>
                </div>
                <form method="GET" class="flex flex-row flex-wrap items-center gap-2">
                    <div class="relative">
                        <select name="month" class="px-4 py-1 pr-8 text-sm border rounded appearance-none focus:ring focus:ring-blue-200">
                            @foreach($months as $num => $name)
                            <option value="{{ $num }}" @if($selectedMonth == $num) selected @endif>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="relative">
                        <select name="year" class="px-4 py-1 pr-8 text-sm border rounded appearance-none focus:ring focus:ring-blue-200">
                            @foreach($years as $year)
                            <option value="{{ $year }}" @if($selectedYear == $year) selected @endif>{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="px-3 py-1 text-sm font-semibold text-white bg-blue-600 rounded hover:bg-blue-700">Lihat</button>
                </form>
            </div>

        {{-- CARD VIEW UNTUK MOBILE (md:hidden) --}}
        <div id="history-results-container-mobile" class="space-y-4 md:hidden">
            @include('dashboard.kurir.historys._card_view', ['orders' => $orders])
        </div>

        {{-- TABLE VIEW UNTUK DESKTOP (hidden md:block) --}}
        <div class="hidden overflow-x-auto min-h-[580px] md:block">
            <table class="items-center w-full mb-0 align-top border-gray-200 text-slate-500">
                <thead class="align-bottom">
                    <tr
                        class="text-xs font-bold text-left text-gray-500 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <th class="px-4 py-3">No.</th>
                        <th class="px-4 py-3">Invoice</th>
                        <th class="px-4 py-3">Customer</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Total</th>
                        <th class="px-4 py-3 text-center">Detail</th>
                    </tr>
                </thead>
                <tbody id="history-results-container-desktop">
                    @include('dashboard.kurir.historys._table_rows', ['orders' => $orders])
                </tbody>
            </table>
        </div>
        <div class="p-4">
            {{ $orders->withQueryString()->links() }}
        </div>
    </div>
</div>
@endsection

@push('flowbite-modals')
@include('dashboard.kurir.historys.show-modal')
@endpush

@push('page-scripts')
{{-- Script JavaScript telah diperbaiki --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const modalElement = document.getElementById('showOrderModal');
        if (!modalElement) return;

        // ===== PERBAIKAN 1: Deklarasikan semua variabel elemen dengan benar =====
        const loader = document.getElementById('showOrderModalLoader');
        const content = document.getElementById('showOrderModalContent'); // <-- VARIABEL INI YANG HILANG
        const zoomWrapper = document.getElementById('showOrderModalZoomWrapper');
        const zoomImg = document.getElementById('showOrderModalZoomImg');
        const zoomCloseBtn = document.getElementById('showOrderModalZoomCloseBtn');

        // Pastikan variabel 'content' ada sebelum melanjutkan
        if (!loader || !content) {
            console.error("Modal loader or content element not found!");
            return;
        }

        // Fungsi showLoader dan hideLoader sekarang menggunakan variabel 'content' yang sudah dideklarasikan
        const showLoader = () => {
            loader.classList.remove('hidden');
            content.classList.add('hidden');
        };
        const hideLoader = () => {
            loader.classList.add('hidden');
            content.classList.remove('hidden');
        };

        const openModal = async (orderId) => {
            modalElement.classList.remove('hidden');
            modalElement.classList.add('flex');
            showLoader();

            const url = `{{ url('kurir/historys') }}/${orderId}/details`;
            try {
                const response = await fetch(url);
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                const data = await response.json();
                populateModal(data);
            } catch (error) {
                console.error('Error fetching order details:', error);
                // Menggunakan variabel 'content' yang sudah benar
                content.innerHTML =
                    `<p class="py-10 text-center text-red-500">Gagal memuat detail pesanan. Silakan coba lagi.</p>`;
            } finally {
                hideLoader();
            }
        };

        const closeModal = () => {
            modalElement.classList.add('hidden');
            modalElement.classList.remove('flex');
        };

        // Event listener tidak perlu diubah, sudah benar
        document.body.addEventListener('click', function(event) {
            const openBtn = event.target.closest(
                '.js-open-modal-btn[data-target-modal="showOrderModal"]');
            if (openBtn) {
                const orderId = openBtn.dataset.orderId;
                openModal(orderId);
                return;
            }

            const closeBtn = event.target.closest('.js-close-modal-btn');
            if (closeBtn || event.target === modalElement) {
                closeModal();
                return;
            }

            if (event.target.tagName === 'IMG' && event.target.dataset.zoomable) {
                zoomImg.src = event.target.src;
                zoomWrapper.classList.remove('hidden');
                zoomWrapper.classList.add('flex');
            }
        });

            // 1. Tutup saat tombol 'X' ditekan
            zoomCloseBtn.addEventListener('click', () => {
                zoomWrapper.classList.add('hidden');
                zoomWrapper.classList.remove('flex');
            });

            // 2. Tutup saat area luar gambar (overlay) ditekan
            zoomWrapper.addEventListener('click', (event) => {
                // Hanya tutup jika yang diklik adalah wrapper-nya, bukan gambar di dalamnya
                if (event.target === zoomWrapper) {
                    zoomWrapper.classList.add('hidden');
                    zoomWrapper.classList.remove('flex');
                }
            });

        function populateModal(data) {
            const formatRupiah = (number) => new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0
            }).format(number);

            // Elemen-elemen ini tidak perlu diubah
            const elements = {
                invoiceNumber: document.getElementById('showOrderModalInvoiceNumber'),
                customerName: document.getElementById('showOrderModalCustomerName'),
                customerPhone: document.getElementById('showOrderModalCustomerPhone'),
                customerCompany: document.getElementById('showOrderModalCustomerCompany'),
                customerAddress: document.getElementById('showOrderModalCustomerAddress'),
                paymentMethod: document.getElementById('showOrderModalPaymentMethod'),
                totalAmount: document.getElementById('showOrderModalTotalAmount'),
                createdAt: document.getElementById('showOrderModalCreatedAt'),
                paidAt: document.getElementById('showOrderModalPaidAt'),
                productDetails: document.getElementById('showOrderModalProductDetails'),
                returnedProductsSection: document.getElementById('showOrderModalReturnedProductsSection'),
                returnedProducts: document.getElementById('showOrderModalReturnedProducts'),
                paymentProof: document.getElementById('showOrderModalPaymentProof'),
                returnProof: document.getElementById('showOrderModalReturnProof'),
                totalReturned: document.getElementById('showOrderModalTotalReturned'),
                singleTotalContainer: document.getElementById('singleTotalContainer'),
                returnedTotalContainer: document.getElementById('returnedTotalContainer'),
                initialTotalAmount: document.getElementById('initialTotalAmount'),
                latestTotalAmount: document.getElementById('latestTotalAmount'),
            };
            const orderItemTemplate = document.getElementById('orderItemTemplate');
            const returnItemTemplate = document.getElementById('returnItemTemplate');

            // Reset konten modal
            elements.productDetails.innerHTML = '';
            elements.returnedProducts.innerHTML = '';
            elements.paymentProof.innerHTML = '';
            elements.returnProof.innerHTML = '';

            // Populasi data utama
            elements.invoiceNumber.textContent = data.invoice_number || '-';
            elements.customerName.textContent = data.customer_name || '-';
            elements.customerPhone.textContent = data.customer_phone || '-';
            elements.customerAddress.textContent = data.customer_address || '-';
            if (data.customer_company && data.customer_company !== 'N/A') {
                elements.customerCompany.textContent = `🏢 ${data.customer_company}`;
                elements.customerCompany.classList.remove('hidden');
            } else {
                elements.customerCompany.textContent = '';
                elements.customerCompany.classList.add('hidden');
            }
            elements.paymentMethod.textContent =
                `Metode: ${data.payment_method ? data.payment_method.charAt(0).toUpperCase() + data.payment_method.slice(1) : '-'}`;
            elements.createdAt.textContent = data.created_at || '-';
            elements.paidAt.textContent = data.paid_at || '-';

            // Populasi item produk
            if (Array.isArray(data.items) && data.items.length > 0) {
                data.items.forEach(item => {
                    const clone = orderItemTemplate.content.cloneNode(true);
                    clone.querySelector('[data-role="name"]').textContent = item.name;

                    // ===== PERBAIKAN 2: Logika untuk varian produk dipisahkan dan diperbaiki =====
                    const variantEl = clone.querySelector('[data-role="variant"]');
                    if (item.variant) {
                        variantEl.textContent = `Varian: ${item.variant}`;
                    } else {
                        variantEl.remove(); // Hapus elemen jika tidak ada varian
                    }

                    clone.querySelector('[data-role="quantity-price"]').textContent =
                        `Jumlah: ${item.quantity} x ${formatRupiah(item.price)}`;
                    clone.querySelector('[data-role="subtotal"]').textContent = formatRupiah(item
                        .subtotal);
                    elements.productDetails.appendChild(clone);
                });
            }

            // Logika untuk retur (sudah benar, tidak perlu diubah)
            if (data.return_details) {
                elements.singleTotalContainer.classList.add('hidden');
                elements.returnedTotalContainer.classList.remove('hidden');
                const finalTotal = data.total_amount - data.return_details.total_amount_returned;
                elements.initialTotalAmount.textContent = formatRupiah(data.total_amount);
                elements.latestTotalAmount.textContent = formatRupiah(finalTotal);
                elements.returnedProductsSection.classList.remove('hidden');
                data.return_details.returned_products.forEach(item => {
                    const clone = returnItemTemplate.content.cloneNode(true);
                    clone.querySelector('[data-role="name"]').textContent = item.name;
                    const variantEl = clone.querySelector('[data-role="variant"]');
                    if (item.variant) {
                        variantEl.textContent = `Varian: ${item.variant}`;
                    } else {
                        variantEl.remove();
                    }
                    clone.querySelector('[data-role="quantity"]').textContent =
                        `Jumlah Diretur: ${item.quantity}`;
                    elements.returnedProducts.appendChild(clone);
                });
                if (elements.totalReturned) {
                    elements.totalReturned.textContent = formatRupiah(data.return_details
                        .total_amount_returned);
                }
                if (data.return_details.return_proof_url) {
                    elements.returnProof.innerHTML =
                        `
                <h5 class="mt-3 mb-1 font-semibold text-red-800 dark:text-red-400">Bukti Retur:</h5>
                <img src="${data.return_details.return_proof_url}" alt="Bukti Retur" class="max-w-[200px] rounded border cursor-pointer hover:border-red-500" data-zoomable="true">`;
                }
            } else {
                elements.singleTotalContainer.classList.remove('hidden');
                elements.returnedTotalContainer.classList.add('hidden');
                elements.totalAmount.textContent = `Total: ${formatRupiah(data.total_amount || 0)}`;
                elements.returnedProductsSection.classList.add('hidden');
            }

            // Logika untuk bukti pembayaran (sudah benar, tidak perlu diubah)
            if (data.payment_proof_url) {
                elements.paymentProof.innerHTML =
                    `<h5 class="mb-1 font-semibold text-gray-800 dark:text-white">Bukti Pembayaran:</h5>
            <img src="${data.payment_proof_url}" alt="Bukti Pembayaran" class="max-w-[200px] rounded border cursor-pointer hover:border-blue-500" data-zoomable="true">`;
            } else {
                elements.paymentProof.innerHTML =
                    '<p class="text-sm text-gray-500">Tidak ada bukti pembayaran</p>';
            }
        }
    });
</script>

<script>
    // Script untuk live search tidak perlu diubah
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof initializeLiveSearch === 'function') {
            initializeLiveSearch({
                searchInputId: 'live-search-input',
                desktopContainerId: 'history-results-container-desktop',
                mobileContainerId: 'history-results-container-mobile'
            });
        }
    });
</script>
@endpush
