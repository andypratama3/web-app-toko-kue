@extends('layouts.argon')
@section('title', 'Daftar Pesanan Saya')
@section('page_title', 'Pesanan Kurir')

@section('content')
<div class="flex-auto p-3 pt-0 -mx-3">
    <div class="p-2.5 bg-white shadow-md rounded-xl dark:bg-gray-800 dark:border-gray-700 min-h-[715px]">
        <h2 class="mb-6 text-md text-black dark:text-white">
            🙍🏻‍♂️ {{ Auth::user()->name ?? 'Pengguna' }}
            🚩 {{ Auth::user()->region->name ?? 'N/A' }}
        </h2>

        <div class="flex justify-end mb-4">
            <a href="{{ route('kurir.pesanan.create') }}"
                class="flex items-center justify-center w-full px-4 py-2 text-sm font-medium text-white bg-blue-700 rounded-lg md:w-auto hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-800">
                <i class="fas fa-plus me-2"></i>
                Tambah Pesanan
            </a>
        </div>

        @if (isset($error))
        <div class="p-4 mb-4 text-red-700 bg-red-100 border-l-4 border-red-500 rounded-md" role="alert">
            <p class="font-bold">Error:</p>
            <p>{{ $error }}</p>
        </div>
        @endif

        @if ($orders->isEmpty())
        <div class="p-4 text-blue-700 bg-blue-100 border-l-4 border-blue-500 rounded-md" role="alert">
            <p class="font-bold">Info:</p>
            <p>Tidak ada pesanan yang ditugaskan untuk Anda saat ini.</p>
        </div>
        @else
        {{-- Tampilan Desktop (Tabel) --}}
        <div class="hidden overflow-x-auto md:block">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase dark:text-gray-300">No</th>
                        <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase dark:text-gray-300">Nomor Invoice</th>
                        <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase dark:text-gray-300">Nama Pelanggan</th>
                        <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase dark:text-gray-300">Status</th>
                        <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase dark:text-gray-300">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                    @foreach ($orders as $order)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700" data-order-id="{{ $order->id }}">
                        <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap dark:text-gray-300">
                            {{ $loop->iteration }}
                            @if($order->show_warning)
                            <span title="Pembayaran melewati 5 hari">⚠️</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 font-mono text-sm text-gray-900 whitespace-nowrap dark:text-white">
                            {{ $order->invoice_number }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap dark:text-gray-300">{{ $order->customer->name ?? 'Pelanggan Dihapus' }}</td>
                        <td class="px-6 py-4 text-sm whitespace-nowrap">
                            {{-- Status akan diperbarui oleh JS --}}
                            <span class="px-2.5 py-1 text-xs font-semibold rounded-full 
                                @switch($order->status ?? 'dikemas')
                                    @case('diambil') bg-blue-100 text-blue-800 @break
                                    @case('diantar') bg-yellow-100 text-yellow-800 @break
                                    @case('diterima_pembeli') bg-purple-100 text-purple-800 @break
                                    @case('selesai') bg-green-100 text-green-800 @break
                                    @default bg-gray-100 text-gray-800
                                @endswitch">
                                {{ ucfirst(str_replace('_', ' ', $order->status ?? 'Dikemas')) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm font-medium text-center whitespace-nowrap">
                            <div class="flex items-center justify-center space-x-2">
                                {{-- Tombol Ubah Status --}}
                                <button type="button"
                                    class="px-3 py-1.5 text-xs font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors"
                                    data-modal-target="statusStepperModal"
                                    data-modal-toggle="statusStepperModal"
                                    onclick="openStatusStepperModal({{ $order->id }})">
                                    Ubah Status
                                </button>
                                {{-- Tombol Rincian (tetap sama) --}}
                                <button type="button"
                                    class="px-3 py-1.5 text-xs font-medium text-gray-900 bg-gray-200 rounded-lg hover:bg-gray-300 transition-colors"
                                    data-modal-toggle="orderDetailsModal"
                                    onclick="fetchOrderDetails({{ $order->id }})">
                                    Rincian
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Tampilan Mobile (Cards) --}}
        <div class="space-y-3 md:hidden">
            @foreach ($orders as $order)
            <div class="p-2 bg-white shadow-md rounded-xl dark:bg-gray-700" data-order-id="{{ $order->id }}">
                <div class="flex justify-end mb-1.5">
                    <span class="px-1 py-0.5 text-xs font-semibold rounded-lg 
                    @switch($order->status ?? 'dikemas')
                        @case('diambil') bg-blue-100 text-blue-800 @break
                        @case('diantar') bg-yellow-100 text-yellow-800 @break
                        @case('diterima_pembeli') bg-purple-100 text-purple-800 @break
                        @case('selesai') bg-green-100 text-green-800 @break
                        @default bg-gray-100 text-gray-800
                    @endswitch">
                        {{ ucfirst(str_replace('_', ' ', $order->status ?? 'Dikemas')) }}
                    </span>
                </div>
                <div class="flex items-center justify-between mb-1">
                    <div class="flex items-center space-x-2">
                        <p class="font-mono text-sm font-bold text-black dark:text-white">
                            🧾 {{ $order->invoice_number ?? 'N/A' }}
                        </p>
                        @if($order->show_warning)
                        <span title="Pembayaran melewati 5 hari" class="text-xs">⚠️</span>
                        @endif
                    </div>
                </div>
                <div class="mb-3">
                    <p class="text-sm text-gray-800 dark:text-gray-200">👤  {{ $order->customer->name ?? 'Pelanggan Dihapus' }}</p>
                </div>
                
                <div class="flex justify-end pt-1.5 space-x-2 border-t border-gray-200 dark:border-gray-600">
                    {{-- Tombol Ubah Status --}}
                    <button type="button"
                        class="px-2 py-1 text-xs font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors"
                        data-modal-target="statusStepperModal"
                        data-modal-toggle="statusStepperModal"
                        onclick="openStatusStepperModal({{ $order->id }})">
                        Ubah Status
                    </button>
                    {{-- Tombol Rincian (tetap sama) --}}
                    <button type="button"
                        class="px-2 py-1 text-xs font-medium text-gray-900 bg-gray-200 rounded-lg hover:bg-gray-300 transition-colors"
                        data-modal-toggle="orderDetailsModal"
                        onclick="fetchOrderDetails({{ $order->id }})">
                        Rincian
                    </button>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>

@push('flowbite-modals')
@include('dashboard.kurir.pesanan.rincian-modal')
@include('dashboard.kurir.pesanan.status-modal')
@endpush

<script>
    // ... (fungsi-fungsi lain tetap sama) ...
    function getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }

    async function fetchOrderDetails(orderId) {
        const modalLoader = document.getElementById('modalLoader');
        const modalContent = document.getElementById('modalContent');

        modalContent.classList.add('hidden');
        modalLoader.classList.remove('hidden');
        modalLoader.innerHTML = `
                <svg class="w-8 h-8 text-blue-600 animate-spin mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="mt-4 text-lg font-medium text-gray-700 dark:text-gray-300">Memuat Detail Pesanan...</p>`;

        try {
            const response = await fetch(`/kurir/pesanan/${orderId}/details`, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken()
                }
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.message);

            populateOrderDetailsModal(data);
        } catch (error) {
            console.error('Error fetching order details:', error);
            modalLoader.innerHTML = `<div class="text-center"><p class="font-bold text-red-600">Gagal Memuat Data</p><p class="mt-2 text-sm text-gray-500">${error.message}</p></div>`;
        }
    }

    function populateOrderDetailsModal(order) {
        // Mengisi data umum (invoice, customer, dll)
        document.getElementById('modalInvoiceNumber').textContent = order.invoice_number || 'N/A';
        document.getElementById('customerName').textContent = order.customer.name || 'N/A';
        document.getElementById('customerPhone').textContent = order.customer.phone || 'N/A';
        document.getElementById('customerAddress').textContent = order.customer.address || 'N/A';
        document.getElementById('paymentMethod').textContent = order.payment_method || 'N/A';
        document.getElementById('orderCreatedAt').textContent = order.created_at || 'Tidak tersedia';

        const orderPaidAtEl = document.getElementById('orderPaidAt');
        if (order.paid_at) {
            orderPaidAtEl.textContent = order.paid_at + (order.paid_at_label || ' ✅');
        } else {
            orderPaidAtEl.textContent = 'Belum Dibayar ❌';
        }

        const productDetailsDiv = document.getElementById('productDetails');
        productDetailsDiv.innerHTML = '';
        if (order.products && order.products.length > 0) {
            order.products.forEach(product => {
                const productItem = document.createElement('div');
                productItem.className = 'p-3 border rounded-lg dark:border-gray-700';
                productItem.innerHTML = `<p class="font-semibold text-gray-900 dark:text-white">${product.name} ${product.variant_name ? `(${product.variant_name})` : ''}</p><p class="text-sm text-gray-700 dark:text-gray-300">Jumlah: ${product.quantity}</p><p class="text-sm text-gray-700 dark:text-gray-300">Harga: Rp ${new Intl.NumberFormat('id-ID').format(product.price)}</p>`;
                productDetailsDiv.appendChild(productItem);
            });
        } else {
            productDetailsDiv.innerHTML = '<p class="text-gray-700 dark:text-gray-300">Tidak ada produk.</p>';
        }

        document.getElementById('modalTotalAmount').textContent = `Rp ${new Intl.NumberFormat('id-ID').format(order.total_amount || 0)}`;

        const paymentUploadForm = document.getElementById('paymentUploadForm');
        const paymentProofUploaded = document.getElementById('paymentProofUploaded');
        const paymentUploadBlocker = document.getElementById('paymentUploadBlocker'); // Ambil div blocker
        const fileInput = document.getElementById('payment_proof_file');
        const submitButton = paymentUploadForm.querySelector('button[type="submit"]');

        // Sembunyikan semua elemen terkait pembayaran terlebih dahulu
        paymentUploadForm.classList.add('hidden');
        paymentProofUploaded.classList.add('hidden');
        paymentUploadBlocker.classList.add('hidden');

        // Skenario 1: Bukti sudah diunggah (status 'selesai')
        if (order.payment_proof) {
            paymentProofUploaded.classList.remove('hidden');
        }
        // Skenario 2: Pesanan sudah diterima pembeli, siap untuk unggah bukti
        else if (order.status === 'diterima_pembeli') {
            paymentUploadForm.classList.remove('hidden');

            // Atur form
            fileInput.value = '';
            submitButton.disabled = true;
            fileInput.onchange = () => {
                submitButton.disabled = fileInput.files.length === 0;
            };
            paymentUploadForm.onsubmit = (e) => {
                e.preventDefault();
                handlePaymentUpload(order.id);
            };
        }
        // Skenario 3: Pesanan belum diterima pembeli, belum bisa unggah bukti
        else {
            paymentUploadBlocker.classList.remove('hidden');
        }

        document.getElementById('modalLoader').classList.add('hidden');
        document.getElementById('modalContent').classList.remove('hidden');
    }

    async function handlePaymentUpload(orderId) {
        const form = document.getElementById('paymentUploadForm');
        const submitButton = form.querySelector('button[type="submit"]');
        const buttonText = document.getElementById('uploadButtonText');
        const buttonSpinner = document.getElementById('uploadButtonSpinner');
        const buttonStatus = document.getElementById('uploadButtonStatus');
        const formData = new FormData(form);

        submitButton.disabled = true;
        buttonText.classList.add('hidden');
        buttonSpinner.classList.remove('hidden');
        buttonStatus.classList.remove('hidden');

        try {
            const response = await fetch(`/kurir/pesanan/${orderId}/upload-proof`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken()
                },
                body: formData
            });
            const result = await response.json();
            if (!response.ok) {
                let errorMessage = result.message;
                if (result.errors?.payment_proof) {
                    errorMessage += `\n- ${result.errors.payment_proof.join('\n- ')}`;
                }
                throw new Error(errorMessage);
            }
            // Setelah unggahan berhasil, ambil kembali detail dan perbarui status tabel
            fetchOrderDetails(orderId);
            // Asumsikan 'selesai' adalah status setelah unggahan bukti pembayaran
            updateTableRowStatus(orderId, 'selesai');
            showCustomAlert(result.message, 'success');
        } catch (error) {
            // Menggunakan kotak pesan kustom sebagai ganti alert
            showCustomAlert(`Upload Gagal: ${error.message}`);
            submitButton.disabled = false;
            buttonText.classList.remove('hidden');
            buttonSpinner.classList.add('hidden');
            buttonStatus.classList.add('hidden');
        }
    }


    // --- Logika Modal Stepper Status ---
    let currentOrderIdForStatus = null;

    async function openStatusStepperModal(orderId) {
        currentOrderIdForStatus = orderId;
        const modalLoader = document.getElementById('statusStepperModalLoader');
        const modalContent = document.getElementById('statusStepperModalContent');
        const updateButton = document.getElementById('updateStatusButton');
        const updateButtonText = document.getElementById('updateStatusButtonText');
        const updateButtonSpinner = document.getElementById('updateStatusButtonSpinner');

        // Reset button state
        updateButtonText.classList.remove('hidden');
        updateButtonSpinner.classList.add('hidden');
        updateButton.disabled = false;
        updateButton.classList.remove('opacity-50', 'cursor-not-allowed');
        updateButtonText.textContent = 'Memuat...';


        modalContent.classList.add('hidden');
        modalLoader.classList.remove('hidden');
        modalLoader.innerHTML = `
                <svg class="w-8 h-8 text-blue-600 animate-spin mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="mt-4 text-lg font-medium text-gray-700 dark:text-gray-300">Memuat Status Pesanan...</p>`;

        try {
            const response = await fetch(`/kurir/pesanan/${orderId}/details`, { // Reuse details endpoint for all status info
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken()
                }
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.message);

            populateStatusStepperModal(data);
        } catch (error) {
            console.error('Error fetching order status details:', error);
            modalLoader.innerHTML = `<div class="text-center"><p class="font-bold text-red-600">Gagal Memuat Data</p><p class="mt-2 text-sm text-gray-500">${error.message}</p></div>`;
            updateButton.disabled = true;
            updateButton.classList.add('opacity-50', 'cursor-not-allowed');
            updateButtonText.textContent = 'Error Memuat';
            updateButtonText.classList.remove('hidden');
            updateButtonSpinner.classList.add('hidden');
        }
    }


    function populateStatusStepperModal(order) {
        const statusMap = {
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
                buttonText: 'Menunggu Pembayaran'
            },
            'selesai': {
                label: 'Selesai (Lunas)',
                nextStatus: null,
                buttonText: 'Pesanan Selesai'
            },
        };

        document.getElementById('modalStatusInvoiceNumber').textContent = order.invoice_number || 'N/A';
        document.getElementById('modalStatusCustomerName').textContent = order.customer.name || 'N/A';

        const currentStatus = order.status;
        const effectiveStatus = currentStatus && statusMap[currentStatus] ? currentStatus : 'dikemas';

        const updateButton = document.getElementById('updateStatusButton');
        const updateButtonText = document.getElementById('updateStatusButtonText');
        const currentOrderStatusText = document.getElementById('currentOrderStatusText');

        // Reset semua langkah ke default
        const steps = ['diambil', 'diantar', 'diterima_pembeli'];
        steps.forEach(step => {
            const iconEl = document.getElementById(`step-${step}-icon`);
            // Reset Ikon
            iconEl.classList.remove('bg-blue-600', 'text-white', 'border-blue-600', 'bg-green-600', 'border-green-600');
            iconEl.classList.add('border-gray-300', 'text-gray-500', 'dark:border-gray-600', 'dark:text-gray-400', 'bg-white', 'dark:bg-gray-700');

            // Reset Garis (Desktop & Mobile)
            const lineDesktop = document.getElementById(`line-${step === 'diambil' ? 'diantar' : step}`);
            const lineMobile = document.getElementById(`line-${step}-mobile`);
            if (lineDesktop) {
                lineDesktop.classList.remove('bg-blue-600', 'bg-green-600');
                lineDesktop.classList.add('bg-gray-200', 'dark:bg-gray-600');
            }
            if (lineMobile) {
                lineMobile.classList.remove('bg-blue-600', 'bg-green-600');
                lineMobile.classList.add('bg-gray-200', 'dark:bg-gray-600');
            }
        });
        document.getElementById('step-diambil-icon').innerHTML = '<i class="fas fa-box"></i>';
        document.getElementById('step-diantar-icon').innerHTML = '<i class="fas fa-truck-moving"></i>';
        document.getElementById('step-diterima_pembeli-icon').innerHTML = '<i class="fas fa-home"></i>';
        document.getElementById('pickedUpAt').textContent = '';
        document.getElementById('deliveredAt').textContent = '';
        document.getElementById('receivedByBuyerAt').textContent = '';

        // currentOrderStatusText.textContent = `Status saat ini: ${statusMap[effectiveStatus]?.label || 'Tidak Diketahui'}`;

        // Perbarui stepper berdasarkan data timestamp
        if (order.picked_up_at) {
            const icon = document.getElementById('step-diambil-icon');
            const lineMobile = document.getElementById('line-diambil-mobile');

            icon.classList.remove('border-gray-300', 'text-gray-500', 'dark:border-gray-600', 'dark:text-gray-400', 'bg-white', 'dark:bg-gray-700');
            icon.classList.add('bg-green-600', 'text-white', 'border-green-600');
            lineMobile.classList.remove('bg-gray-200', 'dark:bg-gray-600');
            lineMobile.classList.add('bg-green-600');
            icon.innerHTML = '<i class="fas fa-check-circle"></i>';
            document.getElementById('pickedUpAt').textContent = order.picked_up_at;
        }

        if (order.delivered_at) {
            const icon = document.getElementById('step-diantar-icon');
            const lineDesktop = document.getElementById('line-diantar');
            const lineMobile = document.getElementById('line-diantar-mobile');

            icon.classList.remove('border-gray-300', 'text-gray-500', 'dark:border-gray-600', 'dark:text-gray-400', 'bg-white', 'dark:bg-gray-700');
            icon.classList.add('bg-green-600', 'text-white', 'border-green-600');
            lineDesktop.classList.remove('bg-gray-200', 'dark:bg-gray-600');
            lineDesktop.classList.add('bg-green-600');
            lineMobile.classList.remove('bg-gray-200', 'dark:bg-gray-600');
            lineMobile.classList.add('bg-green-600');
            icon.innerHTML = '<i class="fas fa-check-circle"></i>';
            document.getElementById('deliveredAt').textContent = order.delivered_at;
        }

        if (order.received_by_buyer_at) {
            const icon = document.getElementById('step-diterima_pembeli-icon');
            const lineDesktop = document.getElementById('line-diterima_pembeli');

            icon.classList.remove('border-gray-300', 'text-gray-500', 'dark:border-gray-600', 'dark:text-gray-400', 'bg-white', 'dark:bg-gray-700');
            icon.classList.add('bg-green-600', 'text-white', 'border-green-600');
            if (lineDesktop) { // Check if lineDesktop exists before adding classes
                lineDesktop.classList.remove('bg-gray-200', 'dark:bg-gray-600');
                lineDesktop.classList.add('bg-green-600');
            }

            icon.innerHTML = '<i class="fas fa-check-circle"></i>';
            document.getElementById('receivedByBuyerAt').textContent = order.received_by_buyer_at;
        }

        const nextStatusInfo = statusMap[effectiveStatus];
        if (nextStatusInfo && nextStatusInfo.nextStatus) {
            updateButtonText.textContent = nextStatusInfo.buttonText;
            updateButton.setAttribute('data-next-status', nextStatusInfo.nextStatus);
            updateButton.disabled = false;
            updateButton.classList.remove('opacity-50', 'cursor-not-allowed');
        } else {
            updateButtonText.textContent = statusMap[effectiveStatus]?.buttonText || 'Tidak ada tindakan';
            updateButton.setAttribute('data-next-status', '');
            updateButton.disabled = true;
            updateButton.classList.add('opacity-50', 'cursor-not-allowed');
        }

        updateButton.setAttribute('data-order-id', order.id);

        document.getElementById('statusStepperModalLoader').classList.add('hidden');
        document.getElementById('statusStepperModalContent').classList.remove('hidden');
    }

    // Fungsi handleStatusUpdate, updateTableRowStatus, getCsrfToken, fetchOrderDetails, populateOrderDetailsModal, handlePaymentUpload, showCustomAlert, dan event listener lainnya tetap sama.
    // ... (fungsi-fungsi lain tetap sama) ...
    function getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }

    async function fetchOrderDetails(orderId) {
        const modalLoader = document.getElementById('modalLoader');
        const modalContent = document.getElementById('modalContent');

        modalContent.classList.add('hidden');
        modalLoader.classList.remove('hidden');
        modalLoader.innerHTML = `
                <svg class="w-8 h-8 text-blue-600 animate-spin mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="mt-4 text-lg font-medium text-gray-700 dark:text-gray-300">Memuat Detail Pesanan...</p>`;

        try {
            const response = await fetch(`/kurir/pesanan/${orderId}/details`, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken()
                }
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.message);

            populateOrderDetailsModal(data);
        } catch (error) {
            console.error('Error fetching order details:', error);
            modalLoader.innerHTML = `<div class="text-center"><p class="font-bold text-red-600">Gagal Memuat Data</p><p class="mt-2 text-sm text-gray-500">${error.message}</p></div>`;
        }
    }

    function populateOrderDetailsModal(order) {
        // Mengisi data umum (invoice, customer, dll)
        document.getElementById('modalInvoiceNumber').textContent = order.invoice_number || 'N/A';
        document.getElementById('customerName').textContent = order.customer.name || 'N/A';
        document.getElementById('customerPhone').textContent = order.customer.phone || 'N/A';
        document.getElementById('customerAddress').textContent = order.customer.address || 'N/A';
        document.getElementById('paymentMethod').textContent = order.payment_method || 'N/A';
        document.getElementById('orderCreatedAt').textContent = order.created_at || 'Tidak tersedia';

        const orderPaidAtEl = document.getElementById('orderPaidAt');
        if (order.paid_at) {
            orderPaidAtEl.textContent = order.paid_at + (order.paid_at_label || ' ✅');
        } else {
            orderPaidAtEl.textContent = 'Belum Dibayar ❌';
        }

        const productDetailsDiv = document.getElementById('productDetails');
        productDetailsDiv.innerHTML = '';
        if (order.products && order.products.length > 0) {
            order.products.forEach(product => {
                const productItem = document.createElement('div');
                productItem.className = 'p-3 border rounded-lg dark:border-gray-700';
                productItem.innerHTML = `<p class="font-semibold text-gray-900 dark:text-white">${product.name} ${product.variant_name ? `(${product.variant_name})` : ''}</p><p class="text-sm text-gray-700 dark:text-gray-300">Jumlah: ${product.quantity}</p><p class="text-sm text-gray-700 dark:text-gray-300">Harga: Rp ${new Intl.NumberFormat('id-ID').format(product.price)}</p>`;
                productDetailsDiv.appendChild(productItem);
            });
        } else {
            productDetailsDiv.innerHTML = '<p class="text-gray-700 dark:text-gray-300">Tidak ada produk.</p>';
        }

        document.getElementById('modalTotalAmount').textContent = `Rp ${new Intl.NumberFormat('id-ID').format(order.total_amount || 0)}`;

        const paymentUploadForm = document.getElementById('paymentUploadForm');
        const paymentProofUploaded = document.getElementById('paymentProofUploaded');
        const paymentUploadBlocker = document.getElementById('paymentUploadBlocker'); // Ambil div blocker
        const fileInput = document.getElementById('payment_proof_file');
        const submitButton = paymentUploadForm.querySelector('button[type="submit"]');

        // Sembunyikan semua elemen terkait pembayaran terlebih dahulu
        paymentUploadForm.classList.add('hidden');
        paymentProofUploaded.classList.add('hidden');
        paymentUploadBlocker.classList.add('hidden');

        // Skenario 1: Bukti sudah diunggah (status 'selesai')
        if (order.payment_proof) {
            paymentProofUploaded.classList.remove('hidden');
        }
        // Skenario 2: Pesanan sudah diterima pembeli, siap untuk unggah bukti
        else if (order.status === 'diterima_pembeli') {
            paymentUploadForm.classList.remove('hidden');

            // Atur form
            fileInput.value = '';
            submitButton.disabled = true;
            fileInput.onchange = () => {
                submitButton.disabled = fileInput.files.length === 0;
            };
            paymentUploadForm.onsubmit = (e) => {
                e.preventDefault();
                handlePaymentUpload(order.id);
            };
        }
        // Skenario 3: Pesanan belum diterima pembeli, belum bisa unggah bukti
        else {
            paymentUploadBlocker.classList.remove('hidden');
        }

        document.getElementById('modalLoader').classList.add('hidden');
        document.getElementById('modalContent').classList.remove('hidden');
    }

    async function handlePaymentUpload(orderId) {
        const form = document.getElementById('paymentUploadForm');
        const submitButton = form.querySelector('button[type="submit"]');
        const buttonText = document.getElementById('uploadButtonText');
        const buttonSpinner = document.getElementById('uploadButtonSpinner');
        const buttonStatus = document.getElementById('uploadButtonStatus');
        const formData = new FormData(form);

        submitButton.disabled = true;
        buttonText.classList.add('hidden');
        buttonSpinner.classList.remove('hidden');
        buttonStatus.classList.remove('hidden');

        try {
            const response = await fetch(`/kurir/pesanan/${orderId}/upload-proof`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken()
                },
                body: formData
            });
            const result = await response.json();
            if (!response.ok) {
                let errorMessage = result.message;
                if (result.errors?.payment_proof) {
                    errorMessage += `\n- ${result.errors.payment_proof.join('\n- ')}`;
                }
                throw new Error(errorMessage);
            }
            // Setelah unggahan berhasil, ambil kembali detail dan perbarui status tabel
            fetchOrderDetails(orderId);
            // Asumsikan 'selesai' adalah status setelah unggahan bukti pembayaran
            updateTableRowStatus(orderId, 'selesai');
            showCustomAlert(result.message, 'success');
        } catch (error) {
            // Menggunakan kotak pesan kustom sebagai ganti alert
            showCustomAlert(`Upload Gagal: ${error.message}`);
            submitButton.disabled = false;
            buttonText.classList.remove('hidden');
            buttonSpinner.classList.add('hidden');
            buttonStatus.classList.add('hidden');
        }
    }

    async function handleStatusUpdate() {
        const updateButton = document.getElementById('updateStatusButton');
        const orderId = updateButton.getAttribute('data-order-id');
        const newStatus = updateButton.getAttribute('data-next-status');

        if (!orderId || !newStatus) {
            showCustomAlert('Error: Status atau Order ID tidak ditemukan.');
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
            if (!response.ok) throw new Error(result.message);

            await openStatusStepperModal(orderId);
            updateTableRowStatus(orderId, result.order.status);
            showCustomAlert(result.message, 'success');

        } catch (error) {
            console.error('Error updating order status:', error);
            showCustomAlert(`Gagal memperbarui status: ${error.message}`);
        } finally {
            buttonText.classList.remove('hidden');
            buttonSpinner.classList.add('hidden');
        }
    }

    // Fungsi untuk memperbarui status di baris/kartu tabel utama
    function updateTableRowStatus(orderId, newStatus) {
        const tableRows = document.querySelectorAll(`[data-order-id="${orderId}"]`);

        tableRows.forEach(row => {
            const statusSpan = row.querySelector('.px-2\\.5.py-1.text-xs.font-semibold.rounded-full');
            if (statusSpan) {
                statusSpan.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1).replace('_', ' ');
                statusSpan.className = 'px-2.5 py-1 text-xs font-semibold rounded-full '; // Reset kelas
                switch (newStatus) {
                    case 'dikemas':
                    case 'diambil':
                        statusSpan.classList.add('bg-blue-100', 'text-blue-800');
                        break;
                    case 'diantar':
                        statusSpan.classList.add('bg-yellow-100', 'text-yellow-800');
                        break;
                    case 'diterima_pembeli':
                        statusSpan.classList.add('bg-purple-100', 'text-purple-800');
                        break;
                    case 'selesai':
                        statusSpan.classList.add('bg-green-100', 'text-green-800');
                        break;
                    default:
                        statusSpan.classList.add('bg-gray-100', 'text-gray-800');
                        break;
                }
            }
        });
    }

    // Lampirkan pendengar event ke tombol perbarui status di modal
    document.addEventListener('DOMContentLoaded', () => {
        const updateButton = document.getElementById('updateStatusButton');
        if (updateButton) {
            updateButton.addEventListener('click', handleStatusUpdate);
        }
    });

    // Kotak Peringatan Kustom (mengganti alert default)
    function showCustomAlert(message, type = 'error') {
        const alertContainer = document.getElementById('customAlertContainer') || document.createElement('div');
        alertContainer.id = 'customAlertContainer';
        alertContainer.className = 'fixed top-4 right-4 z-[9999] flex flex-col space-y-2';
        document.body.appendChild(alertContainer);

        const alertDiv = document.createElement('div');
        alertDiv.className = `p-4 rounded-md shadow-lg flex items-center space-x-3 transition-all duration-300 transform translate-x-full opacity-0 ${
            type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
        }`;
        alertDiv.innerHTML = `
            <svg class="w-6 h-6 ${type === 'success' ? 'text-green-600' : 'text-red-600'}" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                ${type === 'success' ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>' : '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>'}
            </svg>
            <p class="font-medium">${message}</p>
            <button class="ml-auto focus:outline-none" onclick="this.closest('div').remove()">
                <svg class="w-4 h-4 ${type === 'success' ? 'text-green-600' : 'text-red-600'}" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        `;
        alertContainer.appendChild(alertDiv); // Perbaikan di sini, sebelumnya alertContainer.appendChild(alertContainer);

        setTimeout(() => {
            alertDiv.classList.remove('translate-x-full', 'opacity-0');
            alertDiv.classList.add('translate-x-0', 'opacity-100');
        }, 100);

        setTimeout(() => {
            alertDiv.classList.remove('translate-x-0', 'opacity-100');
            alertDiv.classList.add('translate-x-full', 'opacity-0');
            alertDiv.addEventListener('transitionend', () => alertDiv.remove());
        }, 5000);
    }
</script>
@endsection