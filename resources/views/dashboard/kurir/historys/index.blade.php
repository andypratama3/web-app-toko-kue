@extends('layouts.argon')
@section('title', 'History Pesanan')
@section('page_title', 'History')

@section('content')
<div class="flex-auto p-3 pt-0 -mx-3">
    <div class="p-2.5 bg-white shadow-md rounded-xl dark:bg-gray-800 dark:border-gray-700 min-h-[715px]">
        <h2 class="mb-6 text-black text-md dark:text-white">
            👤 {{ Auth::user()->name ?? 'Kurir' }}
            🚩 {{ Auth::user()->region->name ?? 'N/A' }}
        </h2>

        {{-- CARD VIEW UNTUK MOBILE (md:hidden) --}}
        <div class="space-y-4 md:hidden">
            @forelse ($orders as $order)
            <div class="py-2 px-3 rounded-lg bg-gray-50 dark:bg-gray-700 border-l-8 border-green-500 rounded-lg shadow-md">
                {{-- BAGIAN ATAS: INVOICE & TOMBOL DETAIL ICON --}}
                <div class="flex items-start justify-between pb-1 border-b dark:border-gray-600">
                    <div>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $order->invoice_number }}</p>

                    </div>
                    <button type="button"
                        class="text-xl text-blue-500 js-open-modal-btn hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
                        data-target-modal="showOrderModal" data-order-id="{{ $order->id }}">
                        <i class="fas fa-info-circle"></i>
                    </button>
                </div>

                {{-- BAGIAN TENGAH: CUSTOMER (KIRI) & TOTAL (KANAN) --}}
                <div class="flex items-start justify-between mt-1">
                    {{-- SISI KIRI: DATA CUSTOMER --}}
                    <div class="pr-4">
                        <!-- <p class="text-xs text-gray-500 dark:text-gray-400">Customer:</p> -->
                        <p class="text-md font-bold text-gray-800 dark:text-gray-200 mb-2">
                            {{ $order->customer->name ?? '-' }}
                        </p>
                        <p class="text-xs text-gray-600 dark:text-gray-300">
                            <i class="fas fa-store mr-1"></i> {{ $order->customer->company_name ?? '' }}
                        </p>
                        <div class="flex items-center mt-1 text-sm text-gray-500 dark:text-gray-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $order->updated_at->isoFormat('D MMM YYYY, HH:mm') }}
                                </p>
                            </span>
                        </div>
                        <!-- <p class="text-xs text-gray-600 dark:text-gray-300">{{ $order->customer->phone ?? '' }}</p> -->
                        <!-- <p class="text-xs text-gray-600 dark:text-gray-300">{{ $order->customer->address ?? '' }}</p> -->
                    </div>

                    {{-- SISI KANAN: TOTAL HARGA & STATUS --}}
                    <div class="text-right shrink-0">
                        {{-- Status Lunas & Retur --}}
                        <div class="flex flex-row items-end mt-2 space-x-1 justify-end mb-2">
                            <span class="text-xs font-medium px-2.5 py-0.5 rounded {{ $order->payment_status['class'] }}">
                                {{ $order->payment_status['text'] }}
                            </span>
                            @if ($order->has_return)
                            <span class="text-xs font-medium px-2.5 py-0.5 rounded bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300">
                                Retur
                            </span>
                            @endif
                        </div>
                        {{-- Total Harga --}}
                        <div>
                            <!-- <p class="mb-1 text-xs text-gray-500 dark:text-gray-400">Total:</p> -->
                            @if ($order->has_return)
                            {{-- Jika ada retur, tampilkan total baru dan coret total lama --}}
                            <p class="text-xs leading-tight line-through text-slate-400">
                                Rp {{ number_format($order->total_amount, 0, ',', '.') }}
                            </p>
                            <p class="text-xl font-bold text-green-600 dark:text-green-400">
                                Rp {{ number_format($order->final_total, 0, ',', '.') }}
                            </p>
                            
                            @else
                            {{-- Tampilkan total normal jika tidak ada retur --}}
                            <p class="text-lg font-bold text-green-800 dark:text-green-200">
                                Rp {{ number_format($order->total_amount, 0, ',', '.') }}
                            </p>
                            @endif
                        </div>
                    </div>
                </div>
                {{-- [!code focus:end] --}}
            </div>
            @empty
            <div class="py-10 text-center">
                <p class="text-sm text-gray-500">Tidak ada data history pesanan</p>
            </div>
            @endforelse
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
                        <th class="px-4 py-3 text-center"><span class="sr-only">Aksi</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                    <tr class="border-b dark:border-gray-700">
                        {{-- NO --}}
                        <td class="px-4 py-3 font-medium text-center text-gray-900 dark:text-white">
                            {{ ($orders->currentPage() - 1) * $orders->perPage() + $loop->iteration }}
                        </td>
                        <td class="px-4 py-2">
                            <p class="mb-0.5 text-md text-gray-900 dark:text-white font-semibold leading-tight">{{ $order->invoice_number }}</p>
                            <p class="mb-0 text-xs leading-tight text-slate-400">
                                {{ $order->created_at->isoFormat('D MMM YYYY, HH:mm') }}
                            </p>
                        </td>
                        <td class="px-4 py-2">
                            <p class="mb-0 text-md text-gray-900 dark:text-white font-semibold leading-tight">{{ $order->customer->name ?? '-' }}
                            </p>
                            <p class="mb-0 text-xs leading-tight text-slate-400">
                                {{ $order->customer->company_name ?? '-' }}
                            </p>
                        </td>
                        <td class="px-4 py-2">
                            <span
                                class="text-xs mr-1 font-medium px-2.5 py-0.5 rounded {{ $order->payment_status['class'] }}">
                                {{ $order->payment_status['text'] }}
                            </span>
                            @if ($order->has_return)
                            <span
                                class="text-xs font-medium px-2.5 py-0.5 rounded bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300">
                                Retur
                            </span>
                            @endif
                        </td>
                        <td class="px-4 py-2">
                            @if ($order->has_return) <p
                                class="mb-0 text-md font-semibold leading-tight text-green-600 dark:text-green-400">
                                Rp {{ number_format($order->final_total, 0, ',', '.') }}
                            </p>
                            <p class="mb-0 text-xs leading-tight line-through text-slate-400">
                                Rp {{ number_format($order->total_amount, 0, ',', '.') }}
                            </p>
                            @else
                            <p class="mb-0 text-md font-semibold leading-tight">
                                Rp {{ number_format($order->total_amount, 0, ',', '.') }}
                            </p>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-center">
                            <button type="button"
                                class="js-open-modal-btn text-xs px-3 py-1.5 font-semibold text-white bg-blue-500 rounded-lg shadow-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-opacity-75"
                                data-target-modal="showOrderModal" data-order-id="{{ $order->id }}">
                                Detail
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-2 text-center">
                            <p class="mb-0 text-sm text-gray-500">Tidak ada data history pesanan</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">
            {{ $orders->links() }}
        </div>
    </div>
</div>
@endsection

@push('flowbite-modals')
@include('dashboard.kurir.historys.show-modal')
@endpush

@push('page-scripts')
{{-- Script JavaScript tidak perlu diubah sama sekali --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const modalElement = document.getElementById('showOrderModal');
        if (!modalElement) return;

        const loader = document.getElementById('showOrderModalLoader');
        const content = document.getElementById('showOrderModalContent');
        const zoomWrapper = document.getElementById('showOrderModalZoomWrapper');
        const zoomImg = document.getElementById('showOrderModalZoomImg');

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
                content.innerHTML =
                    `<p class="text-center text-red-500">Gagal memuat detail pesanan. Silakan coba lagi.</p>`;
            } finally {
                hideLoader();
            }
        };

        const closeModal = () => {
            modalElement.classList.add('hidden');
            modalElement.classList.remove('flex');
        };

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

        zoomWrapper.addEventListener('click', () => {
            zoomWrapper.classList.add('hidden');
            zoomWrapper.classList.remove('flex');
        });

        function populateModal(data) {
            const formatRupiah = (number) => new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0
            }).format(number);

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

            elements.productDetails.innerHTML = '';
            elements.returnedProducts.innerHTML = '';
            elements.paymentProof.innerHTML = '';
            elements.returnProof.innerHTML = '';

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

            if (Array.isArray(data.items) && data.items.length > 0) {
                data.items.forEach(item => {
                    const clone = orderItemTemplate.content.cloneNode(true);
                    clone.querySelector('[data-role="name"]').textContent = item.name;
                    const variantEl = clone.querySelector('[data-role="variant"]');

                    if (item.variant) {
                        variantEl.textContent = `Varian: ${item.variant}`;
                    } else {
                        variantEl.remove();
                    }

                    clone.querySelector('[data-role="quantity-price"]').textContent =
                        `Jumlah: ${item.quantity} x ${formatRupiah(item.price)}`;
                    clone.querySelector('[data-role="subtotal"]').textContent = formatRupiah(item
                        .subtotal);
                    elements.productDetails.appendChild(clone);
                });
            }

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

            if (data.payment_proof_url) {
                elements.paymentProof.innerHTML =
                    `
            <img src="${data.payment_proof_url}" alt="Bukti Pembayaran" class="max-w-[300px] rounded border cursor-pointer hover:border-blue-500" data-zoomable="true">`;
            } else {
                elements.paymentProof.innerHTML =
                    '<p class="text-sm text-gray-500">Tidak ada bukti pembayaran</p>';
            }
        }
    });
</script>
@endpush