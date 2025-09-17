@extends('layouts.argon')
@section('title', 'Manajemen Pesanan')
@section('page_title', 'Pesanan')

{{-- Tambahkan CSRF Token untuk request AJAX --}}
@section('meta')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('content')
    @if (session('success'))
        <div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400"
            role="alert">
            <span class="font-medium">Sukses!</span> {{ session('success') }}
        </div>
    @endif
    <div class="flex-auto p-3 pt-0 -mx-3">
        <div class="p-2.5 bg-white shadow-md rounded-xl dark:bg-gray-800 dark:border-gray-700 min-h-[715px]">
            <h2 class="mb-6 text-black text-md dark:text-white">
                👤 {{ Auth::user()->name ?? 'Admin' }}
                🚩 {{ Auth::user()->region->name ?? 'N/A' }}
            </h2>
            <div class="overflow-x-auto">
                @php
                    // Peta status → label tampilan (samakan dengan yang dipakai role kurir)
                    $statusLabelMap = [
                        'pending' => 'Baru',
                        'diterima_pembeli' => 'Diterima',
                        'selesai' => 'Selesai',
                        'menunggu_verifikasi_admin' => 'Menunggu Verifikasi',
                        'diverifikasi_admin' => 'Valid',
                        'dikembalikan' => 'Retur',
                        'dibatalkan' => 'Dibatalkan',
                    ];

                    // helper kecil agar tetap aman kalau ada status baru yang belum dipetakan
                    $labelStatus = function ($status) use ($statusLabelMap) {
                        return $statusLabelMap[$status] ?? ucwords(str_replace('_', ' ', $status));
                    };
                @endphp
                <table class="items-center w-full mb-0 align-top border-gray-200 text-slate-500">
                    <thead class="align-bottom">
                        <tr
                            class="text-xs font-bold text-left text-gray-500 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                            <th class="px-4 py-3">No</th>
                            <th class="px-4 py-3">Invoice</th>
                            <th class="px-4 py-3">Customer</th>
                            <th class="px-4 py-3">Kurir</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Total</th>
                            <th class="px-4 py-3 text-center">Catatan</th>
                            <th class="px-4 py-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($orders as $order)
                            <tr class="text-sm font-normal text-gray-700 border-b dark:text-gray-400 dark:border-gray-700">
                                <td class="px-4 py-2 text-center">{{ $loop->iteration }}</td>
                                <td class="px-4 py-2 font-mono">{{ $order->invoice_number }}</td>
                                <td class="px-4 py-2">{{ $order->customer->name ?? '-' }}</td>
                                <td class="px-4 py-2">{{ $order->createdBy->name ?? '-' }}</td>
                                <td class="px-4 py-2">
                                    <span
                                        class="inline-block px-2 py-1 text-xs font-semibold rounded-full
                                        @switch($order->status)
                                            @case('selesai') bg-blue-100 text-blue-800 @break
                                            @case('menunggu_verifikasi_admin') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300 @break
                                            @case('diverifikasi_admin') bg-green-100 text-green-800 @break
                                            @default bg-gray-100 text-gray-800
                                        @endswitch">
                                        {{ $labelStatus($order->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-2">
                                    @php
                                        // Cek retur aktif (tidak ditolak)
                                        $activeReturn = $order->returns
                                            ->where('status', '!=', 'ditolak')
                                            ->sortByDesc('id')
                                            ->first();
                                        $returnedAmount = $activeReturn ? $activeReturn->total_amount_returned : 0;
                                        $afterReturn = $order->total_amount - $returnedAmount;
                                    @endphp
                                    @if ($activeReturn && $returnedAmount > 0)
                                        <span class="block text-xs text-gray-500 line-through">Rp
                                            {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                                        <span class="block font-bold text-green-600">Rp
                                            {{ number_format($afterReturn, 0, ',', '.') }}</span>
                                    @else
                                        Rp {{ number_format($order->total_amount, 0, ',', '.') }}
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-center">
                                    @if ($order->note)
                                        <button type="button"
                                            class="text-gray-500 js-open-modal-btn hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-400"
                                            data-target-modal="viewNoteModal" data-note="{{ $order->note }}"
                                            title="Lihat Catatan">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 20 20"
                                                fill="currentColor">
                                                <path fill-rule="evenodd"
                                                    d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2">
                                    {{-- Tombol ini akan membuka modal verifikasi --}}
                                    @if ($order->status == 'selesai' || $order->status == 'menunggu_verifikasi_admin')
                                        <button
                                            class="px-3 py-1 text-xs font-bold text-white bg-blue-600 rounded js-open-modal-btn hover:bg-blue-700"
                                            data-target-modal="verifyOrderModal" data-order-id="{{ $order->id }}">
                                            Verifikasi
                                        </button>
                                    @else
                                        <button
                                            class="px-3 py-1 text-xs font-bold text-white bg-gray-400 rounded cursor-not-allowed"
                                            disabled>
                                            Verifikasi
                                        </button>
                                    @endif

                                    <button
                                        class="px-3 py-1 text-xs font-bold text-white bg-red-600 rounded js-open-delete-modal hover:bg-red-700"
                                        data-order-id="{{ $order->id }}"
                                        data-invoice-number="{{ $order->invoice_number }}">
                                        Hapus
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-6 text-center text-gray-500">Tidak ada pesanan ditemukan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('flowbite-modals')
    @include('dashboard.admin.order-list.verify-modal')
    @include('dashboard.admin.order-list.delete')
    @include('dashboard.admin.order-list.note-modal')
    @include('dashboard.admin.order-list.rejection-modal')
@endpush

@push('page-scripts')
    <script>
        // --- FUNGSI PEMBANTU ---
        function dispatchToast(message, type = 'success') {
            window.dispatchEvent(new CustomEvent('show-toast', {
                detail: {
                    type: type,
                    message: message
                }
            }));
        }

        const zoomWrapper = document.getElementById('verifyModalZoomWrapper');
        const zoomImg = document.getElementById('verifyModalZoomImg');
        const zoomCloseBtn = document.getElementById('verifyModalZoomCloseBtn');

        // Fungsi untuk menampilkan gambar zoom
        function showVerifyModalZoom(imgSrc) {
            zoomImg.src = imgSrc;
            zoomWrapper.classList.remove('hidden');
            zoomWrapper.classList.add('flex');
        }

        // Fungsi untuk menyembunyikan gambar zoom
        function hideVerifyModalZoom() {
            zoomWrapper.classList.add('hidden');
            zoomWrapper.classList.remove('flex');
        }

        // Event listener untuk tombol close '×'
        zoomCloseBtn.addEventListener('click', hideVerifyModalZoom);

        // Event listener untuk klik di luar gambar (area overlay)
        zoomWrapper.addEventListener('click', function(event) {
            if (event.target === zoomWrapper) {
                hideVerifyModalZoom();
            }
        });

        // --- FUNGSI UTAMA MODAL ---
        let currentOrderId = null;

        document.addEventListener('DOMContentLoaded', function() {
            document.body.addEventListener('click', function(event) {
                const target = event.target.closest('button');
                if (!target) return;

                if (target.matches('.js-open-modal-btn')) {
                    const modalId = target.dataset.targetModal;
                    if (modalId === 'verifyOrderModal') {
                        currentOrderId = target.dataset.orderId;
                        openVerifyModal(currentOrderId);
                    }
                    if (modalId === 'viewNoteModal') {
                        const noteContent = target.dataset.note;
                        document.getElementById('fullOrderNote').textContent = noteContent ||
                            'Tidak ada catatan.';
                    }
                    // This will handle opening all modals including the ones above
                    openModal(modalId);
                    return;
                }

                if (target.matches('.js-open-delete-modal')) {
                    currentOrderId = target.dataset.orderId;
                    const invoiceNumber = target.dataset.invoiceNumber;
                    document.getElementById('deleteInvoiceNumber').textContent = invoiceNumber;
                    openModal('deleteConfirmModal');
                    return;
                }

                if (target.matches('#btnVerifyOrder')) {
                    if (currentOrderId) verifyOrder(currentOrderId);
                    return;
                }

                if (target.matches('#btnOpenRejectModal')) {
                    if (currentOrderId) {
                        // Close verify modal first, then open rejection modal
                        closeModal(document.getElementById('verifyOrderModal'));
                        openModal('rejectionNoteModal');
                    }
                    return;
                }

                // Event listener for the actual form submission button
                if (target.matches('#btnConfirmRejection')) {
                    submitRejectionForm();
                    return;
                }

                if (target.matches('#btnConfirmDelete')) {
                    if (currentOrderId) deleteOrder(currentOrderId);
                    return;
                }
            });
        });

        function submitRejectionForm() {
            const rejectionForm = document.getElementById('rejectionForm');
            const noteTextarea = document.getElementById('rejection_note');
            const noteValue = noteTextarea.value.trim();

            if (noteValue.length < 10) {
                dispatchToast('Alasan penolakan harus diisi minimal 10 karakter.', 'error');
                noteTextarea.focus();
                return;
            }

            if (!currentOrderId) {
                dispatchToast('Error: Order ID tidak ditemukan. Silakan coba lagi.', 'error');
                return;
            }

            rejectionForm.action = `/admin/orders/${currentOrderId}/reject`;
            rejectionForm.submit();
        }

        // [!code block:start]
        // --- FUNGSI openVerifyModal YANG TELAH DIPERBAIKI ---
        async function openVerifyModal(orderId) {
            const loader = document.getElementById('verifyModalLoader');
            const content = document.getElementById('verifyModalContent');
            const modalTitle = document.querySelector('#verifyOrderModal h3');

            // Elemen-elemen spesifik di modal verifikasi
            const returnedProductsSection = document.getElementById('returnedProductsSection');
            const returnedProductsList = document.getElementById('verifyModalReturnedProducts');
            const proofTitle = document.getElementById('verifyModalProofTitle');
            const proofImageContainer = document.getElementById('verifyModalProofImageContainer');
            const proofImage = document.getElementById('verifyModalProofImage');
            const noProofContainer = document.getElementById('verifyModalNoProof');

            // Reset state
            loader.classList.remove('hidden');
            content.classList.add('hidden');
            modalTitle.textContent = "Verifikasi Rincian Pesanan";
            returnedProductsSection.classList.add('hidden');
            proofImageContainer.classList.add('hidden');
            noProofContainer.classList.add('hidden');

            try {
                const response = await fetch(`/admin/orders/${orderId}/details`);
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Gagal memuat data.');

                // Populate data umum (Nama customer, invoice, dll)
                document.getElementById('verifyModalInvoiceNumber').textContent = data.invoice_number || '-';
                document.getElementById('verifyModalCustomerName').textContent = data.customer?.name || '-';
                const companyNameEl = document.getElementById('verifyModalCompanyName');
                if (data.customer?.company_name) {
                    companyNameEl.textContent = `🏢 ${data.customer.company_name}`;
                    companyNameEl.classList.remove('hidden');
                } else {
                    companyNameEl.classList.add('hidden');
                }
                document.getElementById('verifyModalCustomerPhone').textContent = data.customer?.phone || '';
                document.getElementById('verifyModalCustomerAddress').textContent = data.customer?.address || '';
                document.getElementById('verifyModalPaymentMethod').textContent = data.payment_method || '-';
                document.getElementById('verifyModalOrderCreatedAt').textContent = data.created_at || '-';
                document.getElementById('verifyModalOrderPaidAt').textContent = data.paid_at ? `${data.paid_at}${data.paid_at_label || ''}` : 'Belum Lunas';
                document.getElementById('verifyModalCourierName').textContent = data.kurir_name || '-';
                document.getElementById('verifyModalOrderNote').textContent = data.note || 'Tidak ada catatan dari kurir.';

                // Populate produk yang dipesan
                const productDetailsDiv = document.getElementById('verifyModalProductDetails');
                productDetailsDiv.innerHTML = '';
                if (Array.isArray(data.items) && data.items.length > 0) {
                    data.items.forEach(item => {
                        const productItem = document.createElement('div');
                        productItem.className = 'p-2 border rounded-lg dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50';
                        productItem.innerHTML = `<p class="font-semibold text-gray-900 dark:text-white">${item.name} ${item.variant_name ? `(${item.variant_name})` : ''}</p><p class="text-sm text-gray-700 dark:text-gray-300">Jumlah: ${item.quantity} x Rp ${new Intl.NumberFormat('id-ID').format(item.price)}</p>`;
                        productDetailsDiv.appendChild(productItem);
                    });
                } else {
                    productDetailsDiv.innerHTML = '<p>Tidak ada produk.</p>';
                }

                // Logika utama untuk menampilkan Total dan Bukti (Pembayaran vs Retur)
                let proofUrl = null;

                if (data.return_details) {
                    // --- TAMPILAN JIKA ADA RETUR ---
                    modalTitle.textContent = "Verifikasi Pesanan dengan Retur";
                    proofTitle.textContent = "✅ Bukti Retur";
                    proofUrl = data.return_details.return_proof;

                    const originalTotal = data.total_amount || 0;
                    const returnedAmount = data.return_details.total_amount_returned || 0;
                    const newTotal = originalTotal - returnedAmount;

                    document.getElementById('verifyModalTotalAmount').innerHTML =
                        `<span class="block text-sm font-normal text-gray-500 line-through">Rp ${Number(originalTotal).toLocaleString('id-ID')}</span>` +
                        `<span class="block text-green-600 dark:text-green-500">Rp ${Number(newTotal).toLocaleString('id-ID')} (Setelah Retur)</span>`;

                    // Tampilkan detail produk retur
                    returnedProductsSection.classList.remove('hidden');
                    returnedProductsList.innerHTML = '';
                    if (Array.isArray(data.return_details.returned_products) && data.return_details.returned_products.length > 0) {
                        data.return_details.returned_products.forEach(item => {
                            const returnedItem = document.createElement('div');
                            returnedItem.className = 'text-sm';
                            returnedItem.innerHTML = `<p class="font-semibold text-gray-800 dark:text-gray-200">${item.name} ${item.variant_name ? `(${item.variant_name})` : ''}</p><p class="text-gray-600 dark:text-gray-400">Jumlah Diretur: ${item.quantity} x Rp ${new Intl.NumberFormat('id-ID').format(item.price)}</p>`;
                            returnedProductsList.appendChild(returnedItem);
                        });
                    } else {
                        returnedProductsList.innerHTML = '<p>Tidak ada detail produk retur.</p>';
                    }

                } else {
                    // --- TAMPILAN NORMAL (TANPA RETUR) ---
                    modalTitle.textContent = "Verifikasi Rincian Pesanan";
                    proofTitle.textContent = "✅ Bukti Pembayaran";
                    proofUrl = data.payment_proof;
                    document.getElementById('verifyModalTotalAmount').innerHTML = `Rp ${data.total_amount ? Number(data.total_amount).toLocaleString('id-ID') : '0'}`;
                }

                // Tampilkan gambar jika URL ada, jika tidak, tampilkan pesan "Tidak ada bukti"
                if (proofUrl) {
                    proofImage.src = proofUrl; // Langsung gunakan URL dari backend
                    proofImage.alt = data.return_details ? 'Bukti Retur' : 'Bukti Pembayaran';
                    proofImageContainer.classList.remove('hidden');
                    proofImage.onclick = () => showVerifyModalZoom(proofUrl);
                } else {
                    noProofContainer.classList.remove('hidden');
                }

                loader.classList.add('hidden');
                content.classList.remove('hidden');

            } catch (error) {
                console.error('Error openVerifyModal:', error);
                loader.innerHTML = `<div class='text-center text-red-600'>Error: ${error.message}</div>`;
                content.classList.add('hidden');
            }
        }
        // [!code block:end]

        // Fungsi untuk mengirim request verifikasi
        async function verifyOrder(orderId) {
            const modalElement = document.getElementById('verifyOrderModal');
            try {
                const response = await fetch(`/admin/orders/${orderId}/verify`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                            'content'),
                        'Content-Type': 'application/json'
                    }
                });
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Terjadi kesalahan');

                closeModal(modalElement);
                dispatchToast('Pesanan berhasil diverifikasi!', 'success');
                setTimeout(() => window.location.reload(), 1500);

            } catch (error) {
                dispatchToast(`Gagal verifikasi: ${error.message}`, 'error');
            }
        }

        // Fungsi untuk menghapus pesanan
        async function deleteOrder(orderId) {
            const modalElement = document.getElementById('deleteConfirmModal');
            try {
                const response = await fetch(`/admin/orders/${orderId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                            'content'),
                        'Content-Type': 'application/json'
                    }
                });
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Terjadi kesalahan');

                closeModal(modalElement);
                dispatchToast('Pesanan berhasil dihapus!', 'success');
                setTimeout(() => window.location.reload(), 1500);

            } catch (error) {
                dispatchToast(`Gagal menghapus: ${error.message}`, 'error');
            }
        }
    </script>
@endpush
