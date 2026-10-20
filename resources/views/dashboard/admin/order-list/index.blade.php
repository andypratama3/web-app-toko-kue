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

            <form class="flex flex-col gap-2 mb-4 md:flex-row md:items-center md:w-1/2" method="GET">

                <label for="live-search-input" class="sr-only">Cari</label>

                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <svg aria-hidden="true" class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="currentColor"
                            viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd"
                                d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>

                    <input type="text" id="live-search-input" name="search" value="{{ request('search') }}"
                        class="block w-full p-2 pl-10 text-sm text-gray-900 border border-gray-300 rounded-lg
                   bg-gray-50 focus:ring-primary-500 focus:border-primary-500
                   dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                        placeholder="Cari Pesanan...">
                </div>

                <button type="submit"
                    class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium
           text-white bg-blue-600 rounded-lg hover:bg-blue-700
           focus:ring-4 focus:ring-blue-300
           dark:bg-blue-500 dark:hover:bg-blue-600 dark:focus:ring-blue-800">
                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                            clip-rule="evenodd" />
                    </svg>
                    Cari
                </button>
            </form>
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

                <x-admin.responsive-table>
                    <table
                        class="admin-table items-center w-full mb-0 align-top border-gray-200 text-slate-500 dark:text-slate-300">
                        <thead class="align-bottom">
                            <tr
                            class="text-xs font-bold text-left text-gray-500 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                            <th class="px-4 py-3 text-center">No</th>
                            <th class="px-4 py-3">Invoice</th>
                            <th class="px-4 py-3">Customer</th>
                            <th class="px-4 py-3">Kurir</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Total</th>
                            <th class="px-4 py-3 text-center">Catatan</th>
                            <th class="px-4 py-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="order-result-container">
                        @include('dashboard.admin.order-list._table_rows', ['orders' => $orders])
                    </tbody>
                </table>
            </x-admin.responsive-table>
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
                        window.verifyReadOnly = target.dataset.readonly === '1';
                        document.getElementById('btnVerifyOrder').classList.toggle('hidden', window.verifyReadOnly);
                        document.getElementById('btnOpenRejectModal').classList.toggle('hidden', window.verifyReadOnly);
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

                if (target.matches('#btnUploadProof')) {
                    if (currentOrderId) uploadProof();
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
            const uploadProofContainer = document.getElementById('verifyModalUploadProof');
            if (uploadProofContainer) {
                uploadProofContainer.classList.toggle('hidden', window.verifyReadOnly);
            }
            const proofInput = document.getElementById('verifyModalProofInput');
            if (proofInput) proofInput.value = '';
            const uploadMsg = document.getElementById('verifyModalProofUploadMsg');
            if (uploadMsg) uploadMsg.textContent = '';

            // Reset state
            loader.classList.remove('hidden');
            content.classList.add('hidden');
            modalTitle.textContent = window.verifyReadOnly ? "Rincian Pesanan" : "Verifikasi Rincian Pesanan";
            returnedProductsSection.classList.add('hidden');
            proofImageContainer.classList.add('hidden');
            noProofContainer.classList.add('hidden');
            const btnVerifyReset = document.getElementById('btnVerifyOrder');
            btnVerifyReset.disabled = false;
            btnVerifyReset.classList.remove('opacity-50', 'cursor-not-allowed');
            btnVerifyReset.title = '';

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
                document.getElementById('verifyModalOrderPaidAt').textContent = data.paid_at ?
                    `${data.paid_at}${data.paid_at_label || ''}` : 'Belum Lunas';
                document.getElementById('verifyModalCourierName').textContent = data.kurir_name || '-';
                document.getElementById('verifyModalOrderNote').textContent = data.note ||
                    'Tidak ada catatan dari kurir.';

                // Populate produk yang dipesan
                const productDetailsDiv = document.getElementById('verifyModalProductDetails');
                productDetailsDiv.innerHTML = '';
                if (Array.isArray(data.items) && data.items.length > 0) {
                    data.items.forEach(item => {
                        const productItem = document.createElement('div');
                        productItem.className =
                            'p-2 border rounded-lg dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50';
                        productItem.innerHTML =
                            `<p class="font-semibold text-gray-900 dark:text-white">${item.name} ${item.variant_name ? `(${item.variant_name})` : ''}</p><p class="text-sm text-gray-700 dark:text-gray-300">Jumlah: ${item.quantity} x Rp ${new Intl.NumberFormat('id-ID').format(item.price)}</p>`;
                        productDetailsDiv.appendChild(productItem);
                    });
                } else {
                    productDetailsDiv.innerHTML = '<p>Tidak ada produk.</p>';
                }

                // Logika utama untuk menampilkan Total dan Bukti (Pembayaran vs Retur)
                let proofUrl = null;

                if (data.return_details) {
                    // --- TAMPILAN JIKA ADA RETUR ---
                    modalTitle.textContent = window.verifyReadOnly ? "Rincian Pesanan (Retur)" : "Verifikasi Pesanan dengan Retur";
                    proofTitle.textContent = "✅ Bukti Retur";
                    proofUrl = data.return_details.return_proof;

                    const originalTotal = data.total_amount || 0;
                    const returnedAmount = data.return_details.total_amount_returned || 0;
                    const newTotal = originalTotal - returnedAmount;

                    document.getElementById('verifyModalTotalAmount').innerHTML =
                        `<span class="block text-sm font-normal text-gray-500 line-through dark:text-gray-400">Rp ${Number(originalTotal).toLocaleString('id-ID')}</span>` +
                        `<span class="block text-green-600 dark:text-green-500">Rp ${Number(newTotal).toLocaleString('id-ID')} (Setelah Retur)</span>`;

                    // Tampilkan detail produk retur
                    returnedProductsSection.classList.remove('hidden');
                    returnedProductsList.innerHTML = '';
                    if (Array.isArray(data.return_details.returned_products) && data.return_details.returned_products
                        .length > 0) {
                        data.return_details.returned_products.forEach(item => {
                            const returnedItem = document.createElement('div');
                            returnedItem.className = 'text-sm';
                            returnedItem.innerHTML =
                                `<p class="font-semibold text-gray-800 dark:text-gray-200">${item.name} ${item.variant_name ? `(${item.variant_name})` : ''}</p><p class="text-gray-600 dark:text-gray-400">Jumlah Diretur: ${item.quantity} x Rp ${new Intl.NumberFormat('id-ID').format(item.price)}</p>`;
                            returnedProductsList.appendChild(returnedItem);
                        });
                    } else {
                        returnedProductsList.innerHTML = '<p>Tidak ada detail produk retur.</p>';
                    }

                } else {
                    // --- TAMPILAN NORMAL (TANPA RETUR) ---
                    modalTitle.textContent = window.verifyReadOnly ? "Rincian Pesanan" : "Verifikasi Rincian Pesanan";
                    proofTitle.textContent = "✅ Bukti Pembayaran";
                    proofUrl = data.payment_proof;
                    document.getElementById('verifyModalTotalAmount').innerHTML =
                        `Rp ${data.total_amount ? Number(data.total_amount).toLocaleString('id-ID') : '0'}`;
                }

                // Tampilkan gambar jika URL ada, jika tidak, tampilkan pesan "Tidak ada bukti"
                // + kunci tombol verifikasi: tanpa bukti, tombol DISABLED (server juga menolak).
                const btnVerify = document.getElementById('btnVerifyOrder');
                if (proofUrl) {
                    proofImage.src = proofUrl; // Langsung gunakan URL dari backend
                    proofImage.alt = data.return_details ? 'Bukti Retur' : 'Bukti Pembayaran';
                    proofImageContainer.classList.remove('hidden');
                    proofImage.onclick = () => showVerifyModalZoom(proofUrl);
                    btnVerify.disabled = false;
                    btnVerify.classList.remove('opacity-50', 'cursor-not-allowed');
                    btnVerify.title = '';
                } else {
                    noProofContainer.classList.remove('hidden');
                    btnVerify.disabled = true;
                    btnVerify.classList.add('opacity-50', 'cursor-not-allowed');
                    btnVerify.title = 'Belum ada bukti — minta kurir mengunggah dulu';
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

        // Fungsi untuk mengunggah bukti (pembayaran / pengiriman) dari modal verifikasi
        async function uploadProof() {
            const input = document.getElementById('verifyModalProofInput');
            const msg = document.getElementById('verifyModalProofUploadMsg');
            const btn = document.getElementById('btnUploadProof');

            if (!currentOrderId) {
                dispatchToast('Error: Order ID tidak ditemukan.', 'error');
                return;
            }
            if (!input || !input.files || input.files.length === 0) {
                if (msg) {
                    msg.textContent = 'Pilih file gambar dulu.';
                    msg.className = 'mt-2 text-sm text-red-600 dark:text-red-400';
                }
                return;
            }

            const formData = new FormData();
            formData.append('payment_proof', input.files[0]);

            if (btn) {
                btn.disabled = true;
                btn.textContent = 'Mengunggah...';
            }
            if (msg) msg.textContent = '';

            try {
                const response = await fetch(`/admin/orders/${currentOrderId}/upload-proof`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                            'content'),
                        'Accept': 'application/json'
                    },
                    body: formData
                });
                const data = await response.json().catch(() => ({}));
                if (!response.ok) throw new Error(data.message || 'Gagal mengunggah bukti.');

                const proofImage = document.getElementById('verifyModalProofImage');
                proofImage.src = data.payment_proof;
                document.getElementById('verifyModalProofImageContainer').classList.remove('hidden');
                document.getElementById('verifyModalNoProof').classList.add('hidden');
                proofImage.onclick = () => showVerifyModalZoom(data.payment_proof);

                const btnVerify = document.getElementById('btnVerifyOrder');
                btnVerify.disabled = false;
                btnVerify.classList.remove('opacity-50', 'cursor-not-allowed');
                btnVerify.title = '';

                if (msg) {
                    msg.textContent = '✅ Bukti berhasil diunggah.';
                    msg.className = 'mt-2 text-sm text-green-600 dark:text-green-400';
                }
                dispatchToast('Bukti berhasil diunggah!', 'success');
            } catch (error) {
                if (msg) {
                    msg.textContent = `Gagal: ${error.message}`;
                    msg.className = 'mt-2 text-sm text-red-600 dark:text-red-400';
                }
                dispatchToast(`Gagal unggah bukti: ${error.message}`, 'error');
            } finally {
                if (input) input.value = '';
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = 'Unggah Bukti';
                }
            }
        }

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

        // Tugaskan order bot (baru, tanpa kurir) ke kurir se-region.
        async function assignOrder(orderId) {
            const select = document.getElementById(`assign-courier-${orderId}`);
            const courierId = select ? select.value : '';
            if (!courierId) {
                dispatchToast('Pilih kurir dulu sebelum menugaskan.', 'error');
                return;
            }
            try {
                const response = await fetch(`/admin/orders/${orderId}/assign`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                            'content'),
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ courier_id: courierId })
                });
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Terjadi kesalahan');

                dispatchToast(data.message || 'Pesanan ditugaskan.', 'success');
                setTimeout(() => window.location.reload(), 1200);
            } catch (error) {
                dispatchToast(`Gagal menugaskan: ${error.message}`, 'error');
            }
        }

        // Verifikasi pembayaran di depan (tandai lunas, alur kirim tetap jalan).
        async function verifyPayment(orderId) {
            try {
                const response = await fetch(`/admin/orders/${orderId}/verify-payment`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                            'content'),
                        'Content-Type': 'application/json'
                    }
                });
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Terjadi kesalahan');

                dispatchToast(data.message || 'Pembayaran terverifikasi.', 'success');
                setTimeout(() => window.location.reload(), 1200);
            } catch (error) {
                dispatchToast(`Gagal: ${error.message}`, 'error');
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
