@extends('layouts.argon')
@section('title', 'Daftar Pesanan Saya')
@section('page_title', 'Pesanan Kurir')

@section('content')
    <div class="flex-auto p-3 pt-0 -mx-3">
        <div class="p-2.5 bg-white shadow-md rounded-xl dark:bg-gray-800 dark:border-gray-700 min-h-[715px]">
            {{-- Bagian Header (Nama Kurir & Region) --}}
            <h2 class="mb-6 text-black text-md dark:text-white">
                🙍🏻‍♂️ {{ Auth::user()->name ?? 'Pengguna' }}
                🚩 {{ optional(Auth::user()->region)->name ?? 'N/A' }}
            </h2>

            {{-- Tombol Tambah Pesanan --}}
            <div class="flex justify-end mb-4">
                <a href="{{ route('kurir.pesanan.create') }}"
                    class="flex items-center justify-center w-full px-4 py-2 text-sm font-medium text-white bg-blue-700 rounded-lg md:w-auto hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-800">
                    <i class="fas fa-plus me-2"></i>
                    Tambah Pesanan
                </a>
            </div>

            {{-- Blok Notifikasi Error atau Info --}}
            @if (isset($error))
                <div class="p-4 mb-4 text-red-700 bg-red-100 border-l-4 border-red-500 rounded-md" role="alert">
                    <p class="font-bold">Error:</p>
                    <p>{{ $error }}</p>
                </div>
            @endif

            @php
                $statusLabelMap = [
                    'baru' => 'Baru',
                    'dikemas' => 'Dikemas',
                    'diambil' => 'Diambil',
                    'diantar' => 'Diantar',
                    'diterima_pembeli' => 'Diterima',
                    'selesai' => 'Selesai',
                    'menunggu_retur' => 'Menunggu Retur',
                    'menunggu_verifikasi_admin' => 'Menunggu Verifikasi',
                    'diverifikasi_admin' => 'Valid',
                    'dikembalikan' => 'Retur',
                    'dibatalkan' => 'Dibatalkan',
                ];

                $labelStatus = function ($status) use ($statusLabelMap) {
                    return $statusLabelMap[$status] ?? ucwords(str_replace('_', ' ', $status));
                };
            @endphp

            @if ($orders->isEmpty())
                <div class="p-4 text-blue-700 bg-blue-100 border-l-4 border-blue-500 rounded-md" role="alert">
                    <p class="font-bold">Info:</p>
                    <p>Tidak ada pesanan aktif untuk Anda saat ini.</p>
                </div>
            @else
                {{-- Tampilan Desktop (Tabel) - DARI FILE 1 --}}
                <div class="hidden overflow-x-auto md:block">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th scope="col"
                                    class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase dark:text-gray-300">
                                    No</th>
                                <th scope="col"
                                    class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase dark:text-gray-300">
                                    Nomor Invoice</th>
                                <th scope="col"
                                    class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase dark:text-gray-300">
                                    Nama Pelanggan</th>
                                <th scope="col"
                                    class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase dark:text-gray-300">
                                    Status</th>
                                <th scope="col"
                                    class="px-6 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase dark:text-gray-300">
                                    Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                            @foreach ($orders as $order)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700" data-order-id="{{ $order->id }}">
                                    <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap dark:text-gray-300">
                                        {{ $loop->iteration }}
                                        @if ($order->show_warning)
                                            <span title="Pembayaran melewati 5 hari">⚠️</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 font-mono text-sm text-gray-900 whitespace-nowrap dark:text-white">
                                        {{ $order->invoice_number }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap dark:text-gray-300">
                                        {{ optional($order->customer)->name ?? 'Pelanggan Dihapus' }}</td>

                                    <td class="px-6 py-4 text-sm whitespace-nowrap">
                                        <span {{-- KELAS DASAR UNTUK BENTUK & UKURAN SERAGAM --}}
                                            class="status-badge px-2.5 py-1 text-xs font-semibold rounded-full

                                                {{-- KELAS WARNA DINAMIS --}}
                                                @switch($order->status ?? 'baru')
                                                    @case('diambil') bg-blue-100 text-blue-800 @break
                                                    @case('diantar') bg-yellow-100 text-yellow-800 @break
                                                    @case('diterima_pembeli') bg-purple-100 text-purple-800 @break
                                                    @case('menunggu_retur') bg-red-100 text-red-800 @break
                                                    @case('menunggu_verifikasi_admin') bg-orange-100 text-orange-800 @break
                                                    @case('selesai') bg-green-100 text-green-800 @break
                                                    @default bg-gray-100 text-gray-800
                                                @endswitch
                                            ">
                                            {{ $labelStatus($order->status) }}
                                            {{-- TULISAN STATUS (SATU KATA) --}}
                                            {{-- @switch($order->status)
                                                @case('diterima_pembeli')
                                                    Diterima
                                                @break

                                                @case('menunggu_retur')
                                                    Retur
                                                @break

                                                @case('menunggu_verifikasi_admin')
                                                    Verifikasi
                                                @break

                                                @case('selesai')
                                                    Selesai
                                                @break

                                                @default
                                                    {{ ucfirst($order->status) }}
                                            @endswitch --}}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium text-center whitespace-nowrap">
                                        <div class="flex items-center justify-center space-x-2">
                                            <button type="button"
                                                class="js-open-status-modal px-3 py-1.5 text-xs font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors"
                                                data-order-id="{{ $order->id }}">
                                                Ubah Status
                                            </button>
                                            <button type="button"
                                                class="js-open-details-modal px-3 py-1.5 text-xs font-medium text-gray-900 bg-gray-200 rounded-lg hover:bg-gray-300 transition-colors"
                                                data-order-id="{{ $order->id }}">
                                                Rincian
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Tampilan Mobile (Cards) - DARI FILE 2 (UI LEBIH BAIK) --}}
                <div class="space-y-4 md:hidden">
                    @foreach ($orders as $order)
                        <div class="relative p-3 overflow-hidden bg-white shadow-lg rounded-xl dark:bg-gray-700"
                            data-order-id="{{ $order->id }}">
                            {{-- Color Bar --}}
                            <div
                                class="absolute top-0 left-0 h-full w-2.5
                            @switch($order->status ?? 'dikemas')
                                @case('diambil') bg-blue-500 @break
                                @case('diantar') bg-yellow-500 @break
                                @case('diterima_pembeli') bg-purple-500 @break
                                @case('menunggu_retur') bg-red-500 @break
                                @case('menunggu_verifikasi_admin') bg-orange-500 @break
                                @case('selesai') bg-green-500 @break
                                @default bg-gray-400
                            @endswitch">
                            </div>

                            <div class="pl-2">
                                <div class="flex items-start justify-between mb-1">
                                    <div class="flex-grow min-w-0">
                                        <p class="font-mono text-sm font-bold text-black truncate dark:text-white">
                                            {{ $order->invoice_number ?? 'N/A' }}
                                            @if ($order->show_warning)
                                                <span title="Pembayaran melewati 5 hari" class="text-xs">⚠️</span>
                                            @endif
                                        </p>
                                        <p class="text-sm text-gray-800 truncate dark:text-gray-200">
                                            {{ optional($order->customer)->name ?? 'Pelanggan Dihapus' }}</p>
                                    </div>

                                    <span {{-- KELAS DASAR UNTUK BENTUK & UKURAN SERAGAM --}}
                                        class="status-badge flex-shrink-0 px-2 py-0.5 text-xs font-semibold rounded-full whitespace-nowrap ml-2

                                        {{-- KELAS WARNA DINAMIS --}}
                                        @switch($order->status ?? 'baru')
                                            @case('diambil') bg-blue-100 text-blue-800 @break
                                            @case('diantar') bg-yellow-100 text-yellow-800 @break
                                            @case('diterima_pembeli') bg-purple-100 text-purple-800 @break
                                            @case('menunggu_retur') bg-red-100 text-red-800 @break
                                            @case('menunggu_verifikasi_admin') bg-orange-100 text-orange-800 @break
                                            @case('selesai') bg-green-100 text-green-800 @break
                                            @default bg-gray-100 text-gray-800
                                        @endswitch
                                        ">

                                        {{ $labelStatus($order->status) }}

                                        {{-- TULISAN STATUS (SATU KATA) --}}
                                        {{-- @switch($order->status)
                                            @case('diterima_pembeli')
                                                Diterima
                                            @break

                                            @case('menunggu_retur')
                                                Retur
                                            @break

                                            @case('menunggu_verifikasi_admin')
                                                Verifikasi
                                            @break

                                            @case('selesai')
                                                Selesai
                                            @break

                                            @default
                                                {{ ucfirst($order->status) }}
                                        @endswitch --}}
                                    </span>
                                </div>

                                <div class="flex items-center mb-3 text-sm text-gray-500 dark:text-gray-400">
                                    <span>🗓️
                                        {{ \Carbon\Carbon::parse($order->created_at)->translatedFormat('d M Y, H:i') }}</span>
                                </div>

                                <div class="flex justify-end pt-2 space-x-2 border-t border-gray-200 dark:border-gray-600">
                                    <button type="button"
                                        class="js-open-status-modal px-2 py-1 text-xs font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors"
                                        data-order-id="{{ $order->id }}">
                                        Ubah Status
                                    </button>
                                    <button type="button"
                                        class="js-open-details-modal px-2 py-1 text-xs font-medium text-gray-900 bg-gray-200 rounded-lg hover:bg-gray-300 transition-colors"
                                        data-order-id="{{ $order->id }}">
                                        Rincian
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection

@push('flowbite-modals')
    @include('dashboard.kurir.pesanan.rincian-modal')
    @include('dashboard.kurir.pesanan.status-modal')
    @include('dashboard.kurir.pesanan.return-modal')
@endpush

@push('page-scripts')
    <script>
        // Ganti dengan URL aplikasi Anda yang sebenarnya di production
        const APP_URL = "{{ url('/') }}";
        const STATUS_LABEL_MAP = @json($statusLabelMap);

        // --- Helper ---
        function getCsrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        }

        function dispatchToast(message, type = 'success') {
            window.dispatchEvent(new CustomEvent('show-toast', {
                detail: {
                    type: type,
                    message: message
                }
            }));
        }

        function openImageViewer(src) { // [!code ++]
            const imageViewer = document.getElementById('imageViewerModal'); // [!code ++]
            const fullSizeImage = document.getElementById('fullSizeImage'); // [!code ++]
            if (imageViewer && fullSizeImage) { // [!code ++]
                fullSizeImage.src = src; // [!code ++]
                imageViewer.classList.remove('hidden'); // [!code ++]
                document.body.classList.add('overflow-hidden'); // Mencegah scroll di belakang modal [!code ++]
            } // [!code ++]
        } // [!code ++]

        /**
         * Menutup modal image viewer.
         */
        function closeImageViewer() { // [!code ++]
            const imageViewer = document.getElementById('imageViewerModal'); // [!code ++]
            if (imageViewer) { // [!code ++]
                imageViewer.classList.add('hidden'); // [!code ++]
                document.body.classList.remove('overflow-hidden'); // Mengembalikan kemampuan scroll [!code ++]
            } // [!code ++]
        } // [!code ++]

        // --- Logika Modal Rincian ---
        async function fetchOrderDetails(orderId) {
            openModal('orderDetailsModal');
            const modalLoader = document.getElementById('modalLoader');
            const modalContent = document.getElementById('modalContent');
            modalContent.classList.add('hidden');
            modalLoader.classList.remove('hidden');
            modalLoader.innerHTML =
                `<svg class="w-8 h-8 mx-auto text-blue-600 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg><p class="mt-4 text-lg font-medium text-gray-700 dark:text-gray-300">Memuat Detail Pesanan...</p>`;
            try {
                const response = await fetch(`/kurir/pesanan/${orderId}/details`);
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Gagal mengambil data.');
                populateOrderDetailsModal(data);
            } catch (error) {
                modalLoader.innerHTML =
                    `<div class="text-center"><p class="font-bold text-red-600">Gagal Memuat Data</p><p class="mt-2 text-sm text-gray-500">${error.message}</p></div>`;
            }
        }

        function populateOrderDetailsModal(order) {
            // Populate data umum
            document.getElementById('modalInvoiceNumber').textContent = order.invoice_number || 'N/A';
            document.getElementById('customerName').textContent = order.customer.name || 'N/A';
            document.getElementById('customerPhone').textContent = order.customer.phone || 'N/A';
            document.getElementById('customerAddress').textContent = order.customer.address || 'N/A';
            // document.getElementById('customerCompanyName').textContent = order.customer.company_name ?
            //     `🏢 Toko: ${order.customer.company_name}` : '';
            const companyNameEl = document.getElementById('customerCompanyName');
            if (order.customer.company_name && order.customer.company_name !== 'N/A') {
                companyNameEl.textContent = `🏢 ${order.customer.company_name}`;
                companyNameEl.classList.remove('hidden');
            } else {
                companyNameEl.textContent = '';
                companyNameEl.classList.add('hidden');
            }
            document.getElementById('paymentMethod').textContent = order.payment_method || 'N/A';
            document.getElementById('orderCreatedAt').textContent = order.created_at || 'Tidak Tersedia';
            document.getElementById('orderPaidAt').textContent = order.paid_at ? (order.paid_at + (order.paid_at_label ||
                '')) : 'Belum Lunas';
            const statusBadge = document.getElementById('modalOrderStatusBadge');
            if (statusBadge) {
                // 1. Ambil teks status dari map yang sudah ada
                const statusText = STATUS_LABEL_MAP[order.status] || (order.status.charAt(0).toUpperCase() + order.status
                    .slice(1).replace(/_/g, ' '));
                statusBadge.textContent = statusText;

                // 2. Tentukan kelas warna berdasarkan status
                let badgeColorClasses = 'bg-gray-100 text-gray-800'; // Default
                switch (order.status) {
                    case 'diambil':
                        badgeColorClasses = 'bg-blue-100 text-blue-800';
                        break;
                    case 'diantar':
                        badgeColorClasses = 'bg-yellow-100 text-yellow-800';
                        break;
                    case 'diterima_pembeli':
                        badgeColorClasses = 'bg-purple-100 text-purple-800';
                        break;
                    case 'menunggu_retur':
                        badgeColorClasses = 'bg-red-100 text-red-800';
                        break;
                    case 'menunggu_verifikasi_admin':
                        badgeColorClasses = 'bg-orange-100 text-orange-800';
                        break;
                    case 'selesai':
                        badgeColorClasses = 'bg-green-100 text-green-800';
                        break;
                }

                // 3. Gabungkan kelas dasar dengan kelas warna baru
                const baseClasses = 'flex-shrink-0 px-3 py-1 text-sm font-semibold rounded-full whitespace-nowrap';
                statusBadge.className = `${baseClasses} ${badgeColorClasses}`;
            }

            // Logika Perhitungan Total Tagihan untuk handle retur
            let calculatedInitialTotal = 0;
            let calculatedLatestTotal = 0;
            let isReturned = false;
            if (order.products && order.products.length > 0) {
                order.products.forEach(p => {
                    const initialQuantity = p.quantity || 0;
                    const returnedQuantity = p.returned_quantity || 0;
                    const price = p.price || 0;
                    calculatedInitialTotal += initialQuantity * price;
                    const latestQuantity = initialQuantity - returnedQuantity;
                    calculatedLatestTotal += latestQuantity * price;
                    if (returnedQuantity > 0) isReturned = true;
                });
            }

            const singleTotalContainer = document.getElementById('singleTotalAmountContainer');
            const returnedTotalContainer = document.getElementById('returnedTotalAmountContainer');
            if (isReturned && calculatedInitialTotal !== calculatedLatestTotal) {
                document.getElementById('modalInitialTotalAmount').textContent =
                    `Rp ${new Intl.NumberFormat('id-ID').format(calculatedInitialTotal)}`;
                document.getElementById('modalLatestTotalAmount').textContent =
                    `Rp ${new Intl.NumberFormat('id-ID').format(calculatedLatestTotal)}`;
                singleTotalContainer.classList.add('hidden');
                returnedTotalContainer.classList.remove('hidden');
            } else {
                document.getElementById('modalTotalAmount').textContent =
                    `Rp ${new Intl.NumberFormat('id-ID').format(order.total_amount || 0)}`;
                singleTotalContainer.classList.remove('hidden');
                returnedTotalContainer.classList.add('hidden');
            }

            // Populate Product List
            const productDetailsDiv = document.getElementById('productDetails');
            productDetailsDiv.innerHTML = '';
            if (order.products && order.products.length > 0) {
                order.products.forEach(product => {
                    const productItem = document.createElement('div');
                    productItem.className = 'p-3 border rounded-lg dark:border-gray-700';
                    const returnedQty = product.returned_quantity || 0;
                    const currentStock = product.quantity - returnedQty;
                    let quantityInfo =
                        `<p class="text-sm text-gray-700 dark:text-gray-300">Jumlah: ${product.quantity}</p>`;
                    if (returnedQty > 0) {
                        quantityInfo =
                            `<div class="text-sm text-gray-700 dark:text-gray-300"><span>Jumlah Awal: ${product.quantity}</span><span class="text-red-500 ml-2">(Diretur: ${returnedQty})</span><span class="font-bold text-green-600 block">Stok Terbaru: ${currentStock}</span></div>`;
                    }
                    productItem.innerHTML =
                        `<p class="font-semibold text-gray-900 dark:text-white">${product.name} ${product.variant_name ? `(${product.variant_name})` : ''}</p>${quantityInfo}<p class="text-sm">Harga: Rp ${new Intl.NumberFormat('id-ID').format(product.price)}</p>`;
                    productDetailsDiv.appendChild(productItem);
                });
            }

            // Logika Proof Upload
            const paymentUploadForm = document.getElementById('paymentUploadForm');
            const paymentProofUploaded = document.getElementById('paymentProofUploaded');
            const paymentUploadBlocker = document.getElementById('paymentUploadBlocker');
            const compressLink = document.getElementById('compress-link');
            paymentUploadForm.classList.add('hidden');
            paymentProofUploaded.classList.add('hidden');
            paymentUploadBlocker.classList.add('hidden');
            compressLink.classList.add('hidden');

            const getImageUrl = (path) => path ? `${APP_URL}/storage/${path.replace(/^public\//, '')}` : '';
            let proofPath = order.payment_proof || (order.order_return ? order.order_return.return_proof : null);

            if (proofPath) {
                document.getElementById('proofImage').src = getImageUrl(proofPath);
                document.getElementById('proofUploadedTitle').textContent = order.payment_proof ? 'Bukti Pembayaran' :
                    'Bukti Retur';
                paymentProofUploaded.classList.remove('hidden');
            } else if (order.status === 'diterima_pembeli' || order.status === 'menunggu_retur') {
                paymentUploadForm.classList.remove('hidden');
                compressLink.classList.remove('hidden');
                const isReturn = order.status === 'menunggu_retur';
                document.getElementById('paymentProofTitle').textContent = isReturn ? 'Unggah Bukti Retur' :
                    'Unggah Bukti Pembayaran';
                document.getElementById('uploadButtonText').textContent = isReturn ? 'Unggah Bukti Retur' :
                    'Unggah Bukti Pembayaran';
                paymentUploadForm.onsubmit = (e) => {
                    e.preventDefault();
                    handleProofUpload(order.id, order.status);
                };
            } else {
                paymentUploadBlocker.classList.remove('hidden');
            }

            // Tombol Retur
            const returnRequestButtonContainer = document.getElementById('returnRequestButtonContainer');
            if (order.status === 'diterima_pembeli') {
                returnRequestButtonContainer.classList.remove('hidden');
                document.getElementById('requestReturnButton').onclick = () => openReturnProductModal(order);
            } else {
                returnRequestButtonContainer.classList.add('hidden');
            }

            document.getElementById('modalLoader').classList.add('hidden');
            document.getElementById('modalContent').classList.remove('hidden');
        }

        async function handleProofUpload(orderId, status) {
            const form = document.getElementById('paymentUploadForm');
            const submitButton = form.querySelector('button[type="submit"]');
            let url, nextStatus;

            if (status === 'diterima_pembeli') {
                url = `/kurir/pesanan/${orderId}/upload-proof`;
                nextStatus = 'selesai';
            } else if (status === 'menunggu_retur') {
                url = `/kurir/pesanan/${orderId}/upload-return-proof`;
                nextStatus = 'menunggu_verifikasi_admin';
            } else return;

            submitButton.disabled = true;
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {
                        'X-CSRF-TOKEN': getCsrfToken(),
                        'Accept': 'application/json'
                    }
                });
                const result = await response.json();
                if (!response.ok) throw new Error(result.message);
                dispatchToast(result.message, 'success');
                fetchOrderDetails(orderId);
                updateTableRowStatus(orderId, nextStatus);
            } catch (error) {
                dispatchToast(error.message, 'error');
            } finally {
                submitButton.disabled = false;
            }
        }

        // --- Logika Modal Status & Stepper ---
        async function openStatusStepperModal(orderId) {
            openModal('statusStepperModal');
            const modalLoader = document.getElementById('statusStepperModalLoader');
            const modalContent = document.getElementById('statusStepperModalContent');
            modalContent.classList.add('hidden');
            modalLoader.classList.remove('hidden');
            modalLoader.innerHTML =
                `<svg class="w-8 h-8 mx-auto text-blue-600 animate-spin" ...></svg><p class="mt-4 ...">Memuat Status...</p>`;
            try {
                const response = await fetch(`/kurir/pesanan/${orderId}/details`);
                const data = await response.json();
                if (!response.ok) throw new Error(data.message);
                populateStatusStepperModal(data);
            } catch (error) {
                modalLoader.innerHTML =
                    `<div class="text-center"><p class="font-bold text-red-600">Gagal Memuat</p><p class="mt-2 text-sm">${error.message}</p></div>`;
            }
        }

        function populateStatusStepperModal(order) {
            // 1. Peta status yang sudah dilengkapi semua kemungkinan
            const statusMap = {
                'baru': {
                    label: 'Baru',
                    nextStatus: 'diambil',
                    buttonText: 'Ubah Status ke Diambil'
                },
                'dikemas': {
                    label: 'Dikemas',
                    nextStatus: 'diambil',
                    buttonText: 'Ubah Status ke Diambil'
                },
                'diambil': {
                    label: 'Diambil',
                    nextStatus: 'diantar',
                    buttonText: 'Ubah Status ke Diantar'
                },
                'diantar': {
                    label: 'Diantar',
                    nextStatus: 'diterima_pembeli',
                    buttonText: 'Ubah Status ke Diterima Pembeli'
                },
                'diterima_pembeli': {
                    label: 'Diterima Pembeli',
                    nextStatus: null,
                    buttonText: 'Menunggu Bukti Pembayaran'
                },
                'menunggu_retur': {
                    label: 'Menunggu Retur',
                    nextStatus: null,
                    buttonText: 'Menunggu Proses Retur'
                },
                'menunggu_verifikasi_admin': {
                    label: 'Menunggu Verifikasi Admin',
                    nextStatus: null,
                    buttonText: 'Menunggu Verifikasi Admin'
                },
                'selesai': {
                    label: 'Selesai (Lunas)',
                    nextStatus: null,
                    buttonText: 'Pesanan Selesai'
                },
                'diverifikasi_admin': {
                    label: 'Telah Diverifikasi Admin',
                    nextStatus: null,
                    buttonText: 'Telah Diverifikasi Admin'
                }
            };

            // 2. Mengisi info dasar modal
            document.getElementById('modalStatusInvoiceNumber').textContent = order.invoice_number || 'N/A';
            document.getElementById('modalStatusCustomerName').textContent = order.customer.name || 'N/A';

            // 3. Memperbarui UI Stepper dengan memanggil fungsi terpisah
            updateStepperUI(order);

            // 4. Mengatur tombol aksi utama
            const updateButton = document.getElementById('updateStatusButton');
            const updateButtonText = document.getElementById('updateStatusButtonText');
            const currentStatus = order.status || 'baru';
            const currentStatusInfo = statusMap[currentStatus];

            // 5. Pengecekan pengaman untuk menghindari error
            if (currentStatusInfo) {
                updateButtonText.textContent = currentStatusInfo.buttonText;

                // Menonaktifkan tombol jika status sudah final
                if (!currentStatusInfo.nextStatus || ['selesai', 'diverifikasi_admin', 'menunggu_verifikasi_admin',
                        'menunggu_retur'
                    ].includes(currentStatus)) {
                    updateButton.disabled = true;
                    updateButton.classList.add('opacity-50', 'cursor-not-allowed');
                    if (currentStatus === 'diverifikasi_admin') {
                        updateButton.classList.remove('bg-blue-700', 'hover:bg-blue-800');
                        updateButton.classList.add('bg-teal-600', 'hover:bg-teal-700');
                    }
                } else {
                    updateButton.disabled = false;
                    updateButton.classList.remove('opacity-50', 'cursor-not-allowed', 'bg-teal-600', 'hover:bg-teal-700');
                    updateButton.classList.add('bg-blue-700', 'hover:bg-blue-800');
                    updateButton.setAttribute('data-next-status', currentStatusInfo.nextStatus);
                }
            } else {
                // Fallback jika status tidak dikenal
                updateButtonText.textContent = `Status Tidak Dikenal: ${currentStatus}`;
                updateButton.disabled = true;
                updateButton.classList.add('opacity-50', 'cursor-not-allowed');
            }

            updateButton.setAttribute('data-order-id', order.id);

            // 6. Menampilkan konten modal
            document.getElementById('statusStepperModalLoader').classList.add('hidden');
            document.getElementById('statusStepperModalContent').classList.remove('hidden');
        }

        // Fungsi pembantu untuk memperbarui UI Stepper secara spesifik
        function updateStepperUI(order) {
            const steps = ['diambil', 'diantar', 'diterima_pembeli'];
            const timestamps = {
                diambil: order.picked_up_at,
                diantar: order.delivered_at,
                diterima_pembeli: order.received_by_buyer_at
            };
            const icons = {
                diambil: 'fa-box',
                diantar: 'fa-truck-moving',
                diterima_pembeli: 'fa-home'
            };
            const timeSpans = {
                diambil: 'pickedUpAt',
                diantar: 'deliveredAt',
                diterima_pembeli: 'receivedByBuyerAt'
            };

            // Loop tunggal untuk mengatur setiap langkah
            steps.forEach(step => {
                const iconEl = document.getElementById(`step-${step}-icon`);
                const timeSpanEl = document.getElementById(timeSpans[step]);
                const mobileLineEl = document.getElementById(`line-${step}-mobile`);
                const desktopLineEl = document.getElementById(step === 'diambil' ? 'line-diantar' : `line-${step}`);

                // Reset warna
                iconEl.classList.remove('bg-green-600', 'text-green-600', 'border-green-600');
                if (mobileLineEl) mobileLineEl.classList.remove('bg-green-600');
                if (desktopLineEl) desktopLineEl.classList.remove('bg-green-600');

                // Cek apakah langkah sudah selesai
                if (timestamps[step]) {
                    iconEl.innerHTML = '<i class="fas fa-check-circle text-green-600"></i>';
                    iconEl.classList.add('bg-green-600', 'text-green-600', 'border-green-600');
                    timeSpanEl.textContent = timestamps[step];
                    if (mobileLineEl) mobileLineEl.classList.add('bg-green-600');
                    if (desktopLineEl) desktopLineEl.classList.add('bg-green-600');
                } else {
                    iconEl.innerHTML = `<i class="fas ${icons[step]} text-green-600"></i>`;
                    timeSpanEl.textContent = '';
                }
            });
        }

        async function handleStatusUpdate() {
            const updateButton = document.getElementById('updateStatusButton');
            const orderId = updateButton.getAttribute('data-order-id');
            const newStatus = updateButton.getAttribute('data-next-status');

            if (!orderId || !newStatus) {
                dispatchToast('Error: Status atau Order ID tidak ditemukan.', 'error');
                return;
            }

            const buttonText = document.getElementById('updateStatusButtonText');
            const buttonSpinner = document.getElementById('updateStatusButtonSpinner');

            buttonText.classList.add('hidden');
            buttonSpinner.classList.remove('hidden');
            updateButton.disabled = true;
            updateButton.classList.add('opacity-50', 'cursor-not-allowed');

            try {
                const response = await fetch(`/kurir/pesanan/${orderId}/update-status`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken()
                    },
                    body: JSON.stringify({
                        new_status: newStatus
                    })
                });

                const result = await response.json();
                if (!response.ok) throw new Error(result.message || 'Gagal memperbarui status.');

                dispatchToast(result.message, 'success');

                // --- PERBAIKAN UTAMA: Perbarui UI secara langsung dengan waktu lokal ---
                // 1. Dapatkan waktu saat ini dari perangkat pengguna (Date.now())
                const now = new Date();
                const localTimestamp = now.toLocaleDateString('id-ID', {
                    day: 'numeric',
                    month: 'long',
                    year: 'numeric'
                }) + ', ' + now.toLocaleTimeString('id-ID', {
                    hour: '2-digit',
                    minute: '2-digit'
                });

                // 2. Perbarui teks timestamp di UI stepper secara langsung
                const timeSpanId = {
                    'diambil': 'pickedUpAt',
                    'diantar': 'deliveredAt',
                    'diterima_pembeli': 'receivedByBuyerAt'
                } [newStatus];

                if (timeSpanId) {
                    document.getElementById(timeSpanId).textContent = localTimestamp;
                }

                // 3. Muat ulang konten modal untuk mendapatkan data server terbaru di latar belakang
                // Ini memastikan tombol dan status berikutnya sudah benar tanpa harus menampilkan timestamp server.
                openStatusStepperModal(orderId);

                // 4. Perbarui status pada baris tabel di halaman utama
                updateTableRowStatus(orderId, result.order.status);

            } catch (error) {
                console.error('Error updating order status:', error);
                dispatchToast(`Gagal: ${error.message}`, 'error');
            } finally {
                buttonText.classList.remove('hidden');
                buttonSpinner.classList.add('hidden');
            }
        }

        function updateTableRowStatus(orderId, newStatus) {
            const rows = document.querySelectorAll(`[data-order-id="${orderId}"]`);
            const statusText = STATUS_LABEL_MAP[newStatus] || (newStatus.charAt(0).toUpperCase() + newStatus.slice(1)
                .replace(/_/g, ' '));

            // Definisikan kelas warna dinamis
            let newClasses = 'bg-gray-100 text-gray-800';
            let newColorBarClass = 'bg-gray-400';
            // ... (switch case untuk newClasses dan newColorBarClass tetap sama)
            switch (newStatus) {
                case 'diambil':
                    newClasses = 'bg-blue-100 text-blue-800';
                    newColorBarClass = 'bg-blue-500';
                    break;
                case 'diantar':
                    newClasses = 'bg-yellow-100 text-yellow-800';
                    newColorBarClass = 'bg-yellow-500';
                    break;
                case 'diterima_pembeli':
                    newClasses = 'bg-purple-100 text-purple-800';
                    newColorBarClass = 'bg-purple-500';
                    break;
                case 'menunggu_retur':
                    newClasses = 'bg-red-100 text-red-800';
                    newColorBarClass = 'bg-red-500';
                    break;
                case 'menunggu_verifikasi_admin':
                    newClasses = 'bg-orange-100 text-orange-800';
                    newColorBarClass = 'bg-orange-500';
                    break;
                case 'selesai':
                    newClasses = 'bg-green-100 text-green-800';
                    newColorBarClass = 'bg-green-500';
                    break;
            }


            // Definisikan semua kelas dasar yang statis
            const baseClasses = 'status-badge px-2.5 py-1 text-xs font-semibold rounded-full'; // [!code ++]

            rows.forEach(row => {
                const statusSpan = row.querySelector('.status-badge');
                if (statusSpan) {
                    statusSpan.textContent = statusText;
                    // Gabungkan kelas dasar dengan kelas warna yang baru
                    statusSpan.className = `${baseClasses} ${newClasses}`; // [!code ++]
                }
                const colorBar = row.querySelector('.absolute.top-0.left-0');
                if (colorBar) {
                    colorBar.className = colorBar.className.replace(/bg-\w+-\d+/g, '') + ` ${newColorBarClass}`;
                }
            });
        }

        function openReturnProductModal(order) {
            const returnModalLoader = document.getElementById('returnModalLoader');
            const returnModalContent = document.getElementById('returnModalContent');
            const returnProductList = document.getElementById('returnProductList');
            const returnOrderIdInput = document.getElementById('returnOrderId');

            returnModalContent.classList.add('hidden');
            returnModalLoader.classList.remove('hidden');

            returnOrderIdInput.value = order.id;
            returnProductList.innerHTML = '';

            if (!order.products || order.products.length === 0) {
                returnProductList.innerHTML =
                    '<p class="text-center text-gray-500 dark:text-gray-400">Tidak ada produk dalam pesanan ini untuk diretur.</p>';
                returnModalLoader.classList.add('hidden');
                returnModalContent.classList.remove('hidden');
                return;
            }

            order.products.forEach(product => {
                // Accommodate both `product.product_id` and the older `product.id`
                const productId = product.product_id || product.id;
                // Handle cases where variant_id might be null or 0. Default to 0 if not present.
                const variantId = product.variant_id !== undefined && product.variant_id !== null ? product
                    .variant_id : 0;

                if (typeof productId === 'undefined') {
                    console.error('Product data is missing a valid product ID:', product);
                    return; // Skip this product if it has no identifier
                }

                const productItem = document.createElement('div');
                productItem.className =
                    'flex items-center space-x-4 p-3 border border-gray-200 rounded-lg dark:border-gray-700';
                const productImage = product.image_url ||
                    `https://placehold.co/64x64/E0F2F7/000000?text=${product.name.charAt(0)}`;

                const returnKey = `${productId}-${variantId}`;

                productItem.innerHTML = `
            <img src="${productImage}" alt="${product.name}" class="w-16 h-16 rounded-md object-cover">
            <div class="flex-grow">
                <p class="font-semibold text-gray-900 dark:text-white">${product.name}</p>
                <p class="text-sm text-gray-600 dark:text-gray-400">${product.variant_name || ''}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">Jumlah Awal: ${product.quantity}</p>
            </div>
            <div class="flex items-center space-x-2">
                <button type="button" class="quantity-minus flex items-center justify-center w-8 h-8 text-gray-600 dark:text-gray-400 border border-gray-300 dark:border-gray-600 rounded-full hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                    <i class="fas fa-minus"></i>
                </button>
                <input type="number" name="return_qty[${returnKey}]" min="0" max="${product.quantity}" value="0" class="w-16 p-2 text-center border border-gray-300 rounded-md text-sm text-gray-900 dark:bg-gray-700 dark:border-gray-600 dark:text-white quantity-input">
                <button type="button" class="quantity-plus flex items-center justify-center w-8 h-8 text-gray-600 dark:text-gray-400 border border-gray-300 dark:border-gray-600 rounded-full hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                    <i class="fas fa-plus"></i>
                </button>
            </div>
            <button type="button" class="remove-product text-gray-400 hover:text-red-600 dark:hover:text-red-500 transition-colors">
                <i class="fas fa-trash-alt"></i>
            </button>`;
                returnProductList.appendChild(productItem);

                const qtyInput = productItem.querySelector('.quantity-input');

                productItem.querySelector('.quantity-minus').addEventListener('click', () => {
                    let currentVal = parseInt(qtyInput.value, 10);
                    if (currentVal > 0) qtyInput.value = currentVal - 1;
                });
                productItem.querySelector('.quantity-plus').addEventListener('click', () => {
                    let currentVal = parseInt(qtyInput.value, 10);
                    if (currentVal < product.quantity) qtyInput.value = currentVal + 1;
                });
                qtyInput.addEventListener('change', () => {
                    let currentVal = parseInt(qtyInput.value, 10);
                    if (isNaN(currentVal) || currentVal < 0) qtyInput.value = 0;
                    if (currentVal > product.quantity) qtyInput.value = product.quantity;
                });
                productItem.querySelector('.remove-product').addEventListener('click', () => {
                    productItem.remove();
                });
            });

            returnModalLoader.classList.add('hidden');
            returnModalContent.classList.remove('hidden');

            // document.getElementById('returnProductForm').onsubmit = (e) => {
            //     e.preventDefault();
            //     handleReturnRequestSubmit(order.id);
            // };

            document.getElementById('returnProductForm').onsubmit = (e) => {
                e.preventDefault();
                handleReturnRequestSubmit(order.id);
            };
        }

        async function handleReturnRequestSubmit(orderId) {
            const form = document.getElementById('returnProductForm');
            const submitButton = document.getElementById('submitReturnRequestButton');
            const buttonText = document.getElementById('submitReturnRequestButtonText');
            const buttonSpinner = document.getElementById('submitReturnRequestButtonSpinner');

            // Beri umpan balik ke pengguna (UI loading)
            submitButton.disabled = true;
            buttonText.classList.add('hidden');
            buttonSpinner.classList.remove('hidden');

            // Ambil semua input jumlah yang nilainya lebih dari 0
            const returnQuantities = {};
            let hasValidReturn = false;
            form.querySelectorAll('.quantity-input').forEach(input => {
                const key = input.name.match(/\[(.*?)\]/)[1];
                const quantity = parseInt(input.value, 10);
                if (quantity > 0) {
                    returnQuantities[key] = quantity;
                    hasValidReturn = true;
                }
            });

            // Validasi frontend: pastikan ada produk yang diretur
            if (!hasValidReturn) {
                dispatchToast('Anda harus memasukkan jumlah minimal 1 untuk satu produk.', 'error');
                submitButton.disabled = false;
                buttonText.classList.remove('hidden');
                buttonSpinner.classList.add('hidden');
                return;
            }

            try {
                // Kirim data ke server
                const response = await fetch(`/kurir/pesanan/${orderId}/request-return`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken()
                    },
                    body: JSON.stringify({
                        return_quantities: returnQuantities
                    })
                });

                const result = await response.json();
                if (!response.ok) {
                    const errorMsg = result.errors ? Object.values(result.errors).flat().join(' ') : result.message;
                    throw new Error(errorMsg || 'Gagal mengajukan pengembalian.');
                }

                // Jika berhasil:
                dispatchToast(result.message, 'success');

                // Tutup modal retur
                closeModal(document.getElementById('returnProductModal'));

                // Perbarui UI di latar belakang
                updateTableRowStatus(orderId, result.order.status);
                fetchOrderDetails(orderId); // Refresh rincian untuk menampilkan info retur

            } catch (error) {
                dispatchToast(`Gagal: ${error.message}`, 'error');
            } finally {
                // Selalu kembalikan tombol ke keadaan normal
                submitButton.disabled = false;
                buttonText.classList.remove('hidden');
                buttonSpinner.classList.add('hidden');
            }
        }

        // --- Event Delegation ---
        document.addEventListener('DOMContentLoaded', function() {
            document.body.addEventListener('click', function(event) {
                const openStatusBtn = event.target.closest('.js-open-status-modal');
                if (openStatusBtn) {
                    const orderId = openStatusBtn.getAttribute('data-order-id');
                    openStatusStepperModal(orderId);
                    return;
                }

                const openDetailsBtn = event.target.closest('.js-open-details-modal');
                if (openDetailsBtn) {
                    const orderId = openDetailsBtn.getAttribute('data-order-id');
                    fetchOrderDetails(orderId);
                    return;
                }
            });

            const updateButton = document.getElementById('updateStatusButton');
            if (updateButton) {
                updateButton.addEventListener('click', handleStatusUpdate);
            }
        });
    </script>
@endpush
