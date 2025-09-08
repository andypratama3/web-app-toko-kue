@extends('layouts.argon')
@section('title', 'History Pesanan')
@section('page_title', 'History')

@section('content')
    <div class="flex-auto p-3 pt-0 -mx-3">
        <div class="p-2.5 bg-white shadow-md rounded-xl dark:bg-gray-800 dark:border-gray-700 min-h-[715px]">
            <h2 class="mb-6 text-black text-md dark:text-white">
                👤 {{ Auth::user()->name ?? 'Admin' }}
                🚩 {{ Auth::user()->region->name ?? 'N/A' }}
            </h2>
            <div class="overflow-x-auto min-h-[580px]">
                <table class="items-center w-full mb-0 align-top border-gray-200 text-slate-500">
                    <thead class="align-bottom">
                        <tr
                            class="text-xs font-bold text-left text-gray-500 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                            <th class="px-4 py-3">No.</th>
                            <th class="px-4 py-3">Invoice</th>
                            <th class="px-4 py-3">Customer</th>
                            <th class="px-4 py-3">Kurir</th>
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
                                    <p class="mb-0 text-xs font-semibold leading-tight">{{ $order->invoice_number }}</p>
                                    <p class="mb-0 text-xs leading-tight text-slate-400">
                                        {{ $order->created_at->isoFormat('D MMM YYYY, HH:mm') }}
                                    </p>
                                </td>
                                <td class="px-4 py-2">
                                    <p class="mb-0 text-xs font-semibold leading-tight">{{ $order->customer->name ?? '-' }}
                                    </p>
                                    <p class="mb-0 text-xs leading-tight text-slate-400">
                                        {{ $order->customer->company_name ?? '-' }}</p>
                                </td>
                                <td class="px-4 py-2">
                                    <p class="mb-0 text-xs leading-tight">{{ $order->createdBy->name ?? '-' }}</p>
                                </td>
                                <td class="px-4 py-2">
                                    <span
                                        class="text-xs font-medium px-2.5 py-0.5 rounded {{ $order->payment_status['class'] }}">
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
                                    @if ($order->has_return)
                                        {{-- Tampilkan total baru dan coret total lama --}}
                                        <p
                                            class="mb-0 text-xs font-semibold leading-tight text-green-600 dark:text-green-400">
                                            Rp {{ number_format($order->final_total, 0, ',', '.') }}
                                        </p>
                                        <p class="mb-0 text-xs leading-tight line-through text-slate-400">
                                            Rp {{ number_format($order->total_amount, 0, ',', '.') }}
                                        </p>
                                    @else
                                        {{-- Tampilkan total normal jika tidak ada retur --}}
                                        <p class="mb-0 text-xs font-semibold leading-tight">
                                            Rp {{ number_format($order->total_amount, 0, ',', '.') }}
                                        </p>
                                    @endif
                                </td>

                                <td class="px-4 py-2 text-center">
                                    {{-- Wrapper untuk dropdown --}}
                                    <div class="relative inline-block text-left">
                                        {{-- Tombol untuk membuka dropdown --}}
                                        <button type="button"
                                            class="flex items-center justify-center w-8 h-8 text-gray-500 rounded-full js-dropdown-toggle hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:text-gray-400 dark:hover:bg-gray-700"
                                            data-target-dropdown="actions-dropdown-{{ $order->id }}">
                                            <span class="sr-only">Buka menu aksi</span>
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>

                                        {{-- Menu dropdown, awalnya disembunyikan --}}
                                        <div id="actions-dropdown-{{ $order->id }}"
                                            class="absolute right-0 z-10 hidden w-48 mt-2 origin-top-right bg-white rounded-md shadow-lg js-dropdown-menu ring-1 ring-black ring-opacity-5 focus:outline-none dark:bg-gray-700 dark:ring-gray-600">
                                            <div class="py-1" role="menu" aria-orientation="vertical">
                                                {{-- Tombol Detail --}}
                                                <button type="button"
                                                    class="flex items-center w-full px-4 py-2 text-sm text-gray-700 js-open-modal-btn hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-600"
                                                    data-target-modal="showOrderModal" data-order-id="{{ $order->id }}"
                                                    role="menuitem">
                                                    <i class="w-5 mr-2 text-center fas fa-eye"></i>
                                                    <span>Detail</span>
                                                </button>

                                                {{-- Tombol WhatsApp --}}
                                                @php
                                                    $wa_number = $order->customer->phone ?? null;
                                                    if ($wa_number) {
                                                        $wa_number = preg_replace(
                                                            '/^0/',
                                                            '62',
                                                            preg_replace('/[^0-9]/', '', $wa_number),
                                                        );
                                                    }
                                                    $customer_name = $order->customer->name ?? '-';
                                                    $wa_message =
                                                        "Yth. Bapak/Ibu *{$customer_name}*,\n\n" .
                                                        "Kami mengonfirmasi bahwa pesanan Anda telah selesai.\n\n" .
                                                        "Sebagai referensi, transaksi ini tercatat dengan nomor invoice berikut: *{$order->invoice_number}*.\n\n" .
                                                        "Terimakasih sudah berbelanja di Toko Kami.\n\n" .
                                                        "Hormat kami.\n*Admin Kue Pandan Asli*";
                                                    $wa_message = urlencode($wa_message);
                                                @endphp
                                                @if ($wa_number)
                                                    <a href="https://wa.me/{{ $wa_number }}?text={{ $wa_message }}"
                                                        target="_blank"
                                                        class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-600"
                                                        role="menuitem">
                                                        <i class="w-5 mr-2 text-center text-green-500 fab fa-whatsapp"></i>
                                                        <span>WhatsApp</span>
                                                    </a>
                                                @endif

                                                {{-- Tombol Invoice --}}
                                                <a href="{{ route('admin.historys.invoice', $order->id) }}" target="_blank"
                                                    class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-600"
                                                    role="menuitem">
                                                    <i class="w-5 mr-2 text-center fas fa-file-invoice"></i>
                                                    <span>Invoice</span>
                                                </a>

                                                {{-- Tombol Download --}}
                                                <a href="{{ route('admin.historys.download', $order->id) }}"
                                                    class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-600"
                                                    role="menuitem">
                                                    <i class="w-5 mr-2 text-center fas fa-download"></i>
                                                    <span>Download</span>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
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
    @include('dashboard.admin.historys.show-modal')
@endpush

@push('page-scripts')
    {{-- HANYA SATU BLOK SCRIPT YANG DIPERLUKAN --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modalElement = document.getElementById('showOrderModal');
            if (!modalElement) return;

            // Ambil semua elemen modal sekali saja
            const loader = document.getElementById('showOrderModalLoader');
            const content = document.getElementById('showOrderModalContent');
            const zoomWrapper = document.getElementById('showOrderModalZoomWrapper');
            const zoomImg = document.getElementById('showOrderModalZoomImg');

            // Cache elemen-elemen konten modal
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
                returnProof: document.getElementById('showOrderModalReturnProof')
            };

            // Helper functions
            const formatRupiah = (number) => new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0
            }).format(number);
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

                const url = `{{ url('admin/historys') }}/${orderId}/details`;
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

            // Event Delegation untuk seluruh body
            document.body.addEventListener('click', function(event) {
                // Tombol buka modal
                const openBtn = event.target.closest(
                    '.js-open-modal-btn[data-target-modal="showOrderModal"]');
                if (openBtn) {
                    const orderId = openBtn.dataset.orderId;
                    openModal(orderId);
                    return;
                }

                // Tombol tutup modal atau klik di luar area modal
                const closeBtn = event.target.closest('.js-close-modal-btn');
                if (closeBtn || event.target === modalElement) {
                    closeModal();
                    return;
                }

                // Zoom gambar
                if (event.target.tagName === 'IMG' && event.target.dataset.zoomable) {
                    zoomImg.src = event.target.src;
                    zoomWrapper.classList.remove('hidden');
                    zoomWrapper.classList.add('flex');
                }
            });

            // Tutup zoom wrapper
            zoomWrapper.addEventListener('click', () => {
                zoomWrapper.classList.add('hidden');
                zoomWrapper.classList.remove('flex');
            });

            function populateModal(data) {
                // Helper function untuk format Rupiah
                const formatRupiah = (number) => new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    minimumFractionDigits: 0
                }).format(number);

                // Ambil referensi ke semua elemen UI dan template
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

                // 1. Reset semua container sebelum mengisi data baru
                elements.productDetails.innerHTML = '';
                elements.returnedProducts.innerHTML = '';
                elements.paymentProof.innerHTML = '';
                elements.returnProof.innerHTML = '';

                // 2. Isi detail utama pesanan
                elements.invoiceNumber.textContent = data.invoice_number || '-';
                elements.customerName.textContent = data.customer_name || '-';
                elements.customerPhone.textContent = data.customer_phone || '-';
                if (data.customer_company && data.customer_company !== 'N/A') {
                    elements.customerCompany.textContent = `🏢 ${data.customer_company}`;
                    elements.customerCompany.classList.remove('hidden');
                } else {
                    elements.customerCompany.textContent = '';
                    elements.customerCompany.classList.add('hidden');
                }
                elements.customerAddress.textContent = data.customer_address || '-';
                elements.paymentMethod.textContent =
                    `Metode: ${data.payment_method ? data.payment_method.charAt(0).toUpperCase() + data.payment_method.slice(1) : '-'}`;
                elements.createdAt.textContent = data.created_at || '-';
                elements.paidAt.textContent = data.paid_at || '-';

                // 3. Isi produk yang dipesan menggunakan template
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

                // 4. Handle bagian retur dan tampilan total
                if (data.return_details) {
                    // Tampilkan kontainer total ganda, sembunyikan yang tunggal
                    elements.singleTotalContainer.classList.add('hidden');
                    elements.returnedTotalContainer.classList.remove('hidden');

                    // Hitung dan isi nominal
                    const finalTotal = data.total_amount - data.return_details.total_amount_returned;
                    elements.initialTotalAmount.textContent = formatRupiah(data.total_amount);
                    elements.latestTotalAmount.textContent = formatRupiah(finalTotal);

                    elements.returnedProductsSection.classList.remove('hidden');

                    // Isi produk yang diretur
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

                    if (elements.totalReturned) { // [!code ++]
                        elements.totalReturned.textContent = formatRupiah(data.return_details
                            .total_amount_returned); // [!code ++]
                    }

                    // Tampilkan bukti retur jika ada
                    if (data.return_details.return_proof_url) {
                        elements.returnProof.innerHTML =
                            `
                <h5 class="mt-3 mb-1 font-semibold text-red-800 dark:text-red-400">Bukti Retur:</h5>
                <img src="${data.return_details.return_proof_url}" alt="Bukti Retur" class="max-w-[200px] rounded border cursor-pointer hover:border-red-500" data-zoomable="true">`;
                    }
                } else {
                    // Tampilkan kontainer total tunggal, sembunyikan yang ganda
                    elements.singleTotalContainer.classList.remove('hidden');
                    elements.returnedTotalContainer.classList.add('hidden');
                    elements.totalAmount.textContent = `Total: ${formatRupiah(data.total_amount || 0)}`;
                    elements.returnedProductsSection.classList.add('hidden');
                }

                // 5. Handle bukti pembayaran
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
