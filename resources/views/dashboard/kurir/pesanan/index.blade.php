@extends('layouts.argon')
@section('title', 'Daftar Pesanan Saya')
@section('page_title', 'Pesanan Kurir')

@section('content')
    <div class="flex-auto p-3 pt-0 -mx-3">
        <div class="p-2.5 bg-white shadow-md rounded-xl dark:bg-gray-800 dark:border-gray-700 min-h-[715px]">
            <h2 class="mb-6 text-black text-md dark:text-white">
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
                                        {{ $order->customer->name ?? 'Pelanggan Dihapus' }}</td>
                                    <td class="px-6 py-4 text-sm whitespace-nowrap">
                                        <span
                                            class="status-badge px-2.5 py-1 text-xs font-semibold rounded-full
                                @switch($order->status ?? 'dikemas')
                                    @case('diambil') bg-blue-100 text-blue-800 @break
                                    @case('diantar') bg-yellow-100 text-yellow-800 @break
                                    @case('diterima_pembeli') bg-purple-100 text-purple-800 @break
                                    @case('selesai') bg-green-100 text-green-800 @break
                                    @case('diverifikasi_admin') bg-green-200 text-green-900 @break
                                    @default bg-gray-100 text-gray-800
                                @endswitch">
                                            {{ ucfirst(str_replace('_', ' ', $order->status ?? 'Dikemas')) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium text-center whitespace-nowrap">
                                        <div class="flex items-center justify-center space-x-2">
                                            {{-- HAPUS onclick, TAMBAHKAN class & data-order-id --}}
                                            <button type="button"
                                                class="js-open-status-modal px-3 py-1.5 text-xs font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors"
                                                data-order-id="{{ $order->id }}">
                                                Ubah Status
                                            </button>
                                            {{-- HAPUS onclick, TAMBAHKAN class & data-order-id --}}
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

                {{-- Tampilan Mobile (Cards) --}}
                <div class="space-y-3 md:hidden">
                    @foreach ($orders as $order)
                        <div class="p-2 bg-white shadow-md rounded-xl dark:bg-gray-700"
                            data-order-id="{{ $order->id }}">
                            <div class="flex justify-end mb-1.5">
                                <span
                                    class="status-badge px-2.5 py-1 text-xs font-semibold rounded-full
                                    @switch($order->status ?? 'dikemas')
                                        @case('diambil') bg-blue-100 text-blue-800 @break
                                        @case('diantar') bg-yellow-100 text-yellow-800 @break
                                        @case('diterima_pembeli') bg-purple-100 text-purple-800 @break
                                        @case('selesai') bg-green-100 text-green-800 @break
                                        @case('diverifikasi_admin') bg-teal-100 text-teal-800 font-bold @break {{-- STYLE BARU --}}
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
                                    @if ($order->show_warning)
                                        <span title="Pembayaran melewati 5 hari" class="text-xs">⚠️</span>
                                    @endif
                                </div>
                            </div>
                            <div class="mb-3">
                                <p class="text-sm text-gray-800 dark:text-gray-200">👤
                                    {{ $order->customer->name ?? 'Pelanggan Dihapus' }}</p>
                            </div>

                            <div class="flex justify-end pt-1.5 space-x-2 border-t border-gray-200 dark:border-gray-600">
                                {{-- HAPUS onclick, TAMBAHKAN class & data-order-id --}}
                                <button type="button"
                                    class="px-2 py-1 text-xs font-medium text-white transition-colors bg-blue-600 rounded-lg js-open-status-modal hover:bg-blue-700"
                                    data-order-id="{{ $order->id }}">
                                    Ubah Status
                                </button>
                                {{-- HAPUS onclick, TAMBAHKAN class & data-order-id --}}
                                <button type="button"
                                    class="px-2 py-1 text-xs font-medium text-gray-900 transition-colors bg-gray-200 rounded-lg js-open-details-modal hover:bg-gray-300"
                                    data-order-id="{{ $order->id }}">
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
        // --- Helper ---
        function getCsrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        }

        /**
         * Fungsi BARU untuk memicu toast Alpine.js.
         * Menggantikan showCustomAlert()
         */
        function dispatchToast(message, type = 'success') {
            window.dispatchEvent(new CustomEvent('show-toast', {
                detail: {
                    type: type,
                    message: message
                }
            }));
        }

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
            document.getElementById('modalInvoiceNumber').textContent = order.invoice_number || 'N/A';
            document.getElementById('customerName').textContent = order.customer.name || 'N/A';
            document.getElementById('customerPhone').textContent = order.customer.phone || '';
            document.getElementById('customerAddress').textContent = order.customer.address || '';
            document.getElementById('paymentMethod').textContent = order.payment_method || 'N/A';
            document.getElementById('orderCreatedAt').textContent = order.created_at || 'Tidak tersedia';
            document.getElementById('orderPaidAt').textContent = order.paid_at ? (order.paid_at + (order.paid_at_label ||
                '')) : 'Belum Lunas';
            document.getElementById('modalTotalAmount').textContent =
                `Rp ${new Intl.NumberFormat('id-ID').format(order.total_amount || 0)}`;
            const productDetailsDiv = document.getElementById('productDetails');
            productDetailsDiv.innerHTML = '';
            if (order.products && order.products.length > 0) {
                order.products.forEach(product => {
                    const productItem = document.createElement('div');
                    productItem.className = 'p-3 border rounded-lg dark:border-gray-700';
                    productItem.innerHTML =
                        `<p class="font-semibold text-gray-900 dark:text-white">${product.name} ${product.variant_name ? `(${product.variant_name})` : ''}</p><p class="text-sm text-gray-700 dark:text-gray-300">Jumlah: ${product.quantity} x Rp ${new Intl.NumberFormat('id-ID').format(product.price)}</p>`;
                    productDetailsDiv.appendChild(productItem);
                });
            } else {
                productDetailsDiv.innerHTML = '<p>Tidak ada produk.</p>';
            }
            const paymentProofImageWrapper = document.getElementById('paymentProofImageWrapper');
            const paymentUploadForm = document.getElementById('paymentUploadForm');
            const paymentProofUploaded = document.getElementById('paymentProofUploaded');
            const paymentUploadBlocker = document.getElementById('paymentUploadBlocker');
            const rejectionNotice = document.getElementById('rejectionNotice');
            paymentProofImageWrapper.innerHTML = '';
            paymentUploadForm.classList.add('hidden');
            paymentProofUploaded.classList.add('hidden');
            paymentUploadBlocker.classList.add('hidden');
            rejectionNotice.classList.add('hidden');
            if (order.payment_proof) {
                const imgSrc = `/storage/${order.payment_proof}`;
                paymentProofImageWrapper.innerHTML =
                    `<img src="${imgSrc}" class="object-cover w-32 h-32 border-2 border-gray-300 rounded shadow cursor-zoom-in" alt="Bukti Pembayaran" onclick="showRincianModalZoom('${imgSrc}')">`;
                paymentProofUploaded.classList.remove('hidden');
            } else if (order.status === 'diterima_pembeli') {
                paymentUploadForm.classList.remove('hidden');
                paymentUploadForm.onsubmit = (e) => {
                    e.preventDefault();
                    handlePaymentUpload(order.id);
                };
                if (order.paid_at) {
                    rejectionNotice.classList.remove('hidden');
                }
            } else {
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
            submitButton.disabled = true;
            buttonText.textContent = 'Mengunggah...';
            buttonSpinner.classList.remove('hidden');
            try {
                const response = await fetch(`/kurir/pesanan/${orderId}/upload-proof`, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {
                        'X-CSRF-TOKEN': getCsrfToken(),
                        'Accept': 'application/json'
                    }
                });
                const result = await response.json();
                if (!response.ok) throw new Error(result.message || 'Gagal mengunggah.');
                dispatchToast(result.message, 'success');
                fetchOrderDetails(orderId);
                updateTableRowStatus(orderId, 'selesai');
            } catch (error) {
                dispatchToast(error.message, 'error');
            } finally {
                submitButton.disabled = false;
                buttonText.textContent = 'Unggah Bukti';
                buttonSpinner.classList.add('hidden');
            }
        }

        async function openStatusStepperModal(orderId) {
            openModal('statusStepperModal');
            const modalLoader = document.getElementById('statusStepperModalLoader');
            const modalContent = document.getElementById('statusStepperModalContent');
            modalContent.classList.add('hidden');
            modalLoader.classList.remove('hidden');
            modalLoader.innerHTML =
                `<svg class="w-8 h-8 mx-auto text-blue-600 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg><p class="mt-4">Memuat Status...</p>`;
            try {
                const response = await fetch(`/kurir/pesanan/${orderId}/details`);
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Gagal mengambil data.');
                populateStatusStepperModal(data);
            } catch (error) {
                modalLoader.innerHTML =
                    `<div class="text-center"><p class="font-bold text-red-600">Gagal Memuat</p><p class="mt-2 text-sm">${error.message}</p></div>`;
            }
        }

        function populateStatusStepperModal(order) {
            const statusMap = {
                'dikemas': {
                    next: 'diambil',
                    text: 'Ubah ke Diambil'
                },
                'diambil': {
                    next: 'diantar',
                    text: 'Ubah ke Diantar'
                },
                'diantar': {
                    next: 'diterima_pembeli',
                    text: 'Ubah ke Diterima Pembeli'
                },
                'diterima_pembeli': {
                    next: null,
                    text: 'Menunggu Bukti Pembayaran'
                },
                'selesai': {
                    next: null,
                    text: 'Menunggu Verifikasi Admin'
                },
                'diverifikasi_admin': {
                    next: null,
                    text: 'Telah Diverifikasi Admin'
                }
            };
            document.getElementById('modalStatusInvoiceNumber').textContent = order.invoice_number || 'N/A';
            document.getElementById('modalStatusCustomerName').textContent = order.customer.name || 'N/A';
            updateStepperUI(order);
            const updateButton = document.getElementById('updateStatusButton');
            const updateButtonText = document.getElementById('updateStatusButtonText');
            const currentStatusInfo = statusMap[order.status] || statusMap['dikemas'];
            updateButtonText.textContent = currentStatusInfo.text;
            if (!currentStatusInfo.next || order.status === 'selesai' || order.status === 'diverifikasi_admin') {
                updateButton.disabled = true;
                updateButton.classList.add('opacity-50', 'cursor-not-allowed');
                if (order.status === 'diverifikasi_admin') {
                    updateButton.classList.remove('bg-blue-700', 'hover:bg-blue-800');
                    updateButton.classList.add('bg-teal-600', 'hover:bg-teal-700');
                }
            } else {
                updateButton.disabled = false;
                updateButton.classList.remove('opacity-50', 'cursor-not-allowed', 'bg-teal-600', 'hover:bg-teal-700');
                updateButton.classList.add('bg-blue-700', 'hover:bg-blue-800');
                updateButton.setAttribute('data-order-id', order.id);
                updateButton.setAttribute('data-next-status', currentStatusInfo.next);
            }
            document.getElementById('statusStepperModalLoader').classList.add('hidden');
            document.getElementById('statusStepperModalContent').classList.remove('hidden');
        }

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
            steps.forEach(step => {
                const iconEl = document.getElementById(`step-${step}-icon`);
                iconEl.classList.remove('bg-green-600', 'text-white', 'border-green-600');
                iconEl.innerHTML = `<i class="fas ${icons[step]}"></i>`;
                document.getElementById(timeSpans[step]).textContent = '';
            });
            steps.forEach(step => {
                if (timestamps[step]) {
                    const icon = document.getElementById(`step-${step}-icon`);
                    icon.classList.add('bg-green-600', 'text-white', 'border-green-600');
                    icon.innerHTML = '<i class="fas fa-check-circle"></i>';
                    document.getElementById(timeSpans[step]).textContent = timestamps[step];
                }
            });
        }

        async function handleStatusUpdate() {
            const updateButton = document.getElementById('updateStatusButton');
            const orderId = updateButton.getAttribute('data-order-id');
            const newStatus = updateButton.getAttribute('data-next-status');
            if (!orderId || !newStatus) return;
            const buttonText = document.getElementById('updateStatusButtonText');
            const buttonSpinner = document.getElementById('updateStatusButtonSpinner');
            buttonText.classList.add('hidden');
            buttonSpinner.classList.remove('hidden');
            updateButton.disabled = true;
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

                // Panggil fungsi toast baru
                dispatchToast(result.message, 'success');

                openStatusStepperModal(orderId);
                updateTableRowStatus(orderId, result.order.status);
            } catch (error) {
                // Panggil fungsi toast baru
                dispatchToast(`Gagal: ${error.message}`, 'error');
            } finally {
                buttonText.classList.remove('hidden');
                buttonSpinner.classList.add('hidden');
            }
        }

        function updateTableRowStatus(orderId, newStatus) {
            const rows = document.querySelectorAll(`[data-order-id="${orderId}"]`);
            const statusText = newStatus.charAt(0).toUpperCase() + newStatus.slice(1).replace(/_/g, ' ');
            let newClasses = 'bg-gray-100 text-gray-800';
            switch (newStatus) {
                case 'diambil':
                    newClasses = 'bg-blue-100 text-blue-800';
                    break;
                case 'diantar':
                    newClasses = 'bg-yellow-100 text-yellow-800';
                    break;
                case 'diterima_pembeli':
                    newClasses = 'bg-purple-100 text-purple-800';
                    break;
                case 'selesai':
                    newClasses = 'bg-green-100 text-green-800';
                    break;
                case 'diverifikasi_admin':
                    newClasses = 'bg-teal-100 text-teal-800 font-bold';
                    break;
            }
            rows.forEach(row => {
                const statusSpan = row.querySelector('.status-badge');
                if (statusSpan) {
                    statusSpan.textContent = statusText;
                    statusSpan.className =
                        `status-badge ${statusSpan.className.split(' ').slice(0, 4).join(' ')} ${newClasses}`;
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.js-open-status-modal').forEach(button => {
                button.addEventListener('click', function() {
                    const orderId = this.getAttribute('data-order-id');
                    openStatusStepperModal(orderId);
                });
            });
            document.querySelectorAll('.js-open-details-modal').forEach(button => {
                button.addEventListener('click', function() {
                    const orderId = this.getAttribute('data-order-id');
                    fetchOrderDetails(orderId);
                });
            });
            const updateButton = document.getElementById('updateStatusButton');
            if (updateButton) {
                updateButton.addEventListener('click', handleStatusUpdate);
            }
        });
    </script>
@endsection
