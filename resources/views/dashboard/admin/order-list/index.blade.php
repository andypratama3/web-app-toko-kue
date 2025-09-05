@extends('layouts.argon')
@section('title', 'Manajemen Pesanan')
@section('page_title', 'Pesanan')

{{-- Tambahkan CSRF Token untuk request AJAX --}}
@section('meta')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('content')
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
                            <th class="px-4 py-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($orders as $order)
                            <tr class="text-sm font-normal text-gray-700 border-b dark:text-gray-400 dark:border-gray-700">
                                <td class="px-4 py-2">{{ $loop->iteration }}</td>
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
                                        {{-- {{ ucfirst(str_replace('_', ' ', $order->status)) }} --}}
                                        {{ $labelStatus($order->status) }}
                                    </span>
                                </td>
                                    <td class="px-4 py-2">
                                        @php
                                            // Cek retur aktif (tidak ditolak)
                                            $activeReturn = $order->returns->where('status', '!=', 'ditolak')->sortByDesc('id')->first();
                                            $returnedAmount = $activeReturn ? $activeReturn->total_amount_returned : 0;
                                            $afterReturn = $order->total_amount - $returnedAmount;
                                        @endphp
                                        @if ($activeReturn && $returnedAmount > 0)
                                            <span class="block text-xs text-gray-500 line-through">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                                            <span class="block text-green-600 font-bold">Rp {{ number_format($afterReturn, 0, ',', '.') }}</span>
                                        @else
                                            Rp {{ number_format($order->total_amount, 0, ',', '.') }}
                                        @endif
                                    </td>
                                <td class="px-4 py-2">
                                    {{-- Tombol ini akan membuka modal verifikasi --}}
                                    {{-- DIUBAH: Menambahkan kondisi || $order->status == 'menunggu_verifikasi_admin' --}}
                                    @if ($order->status == 'selesai' || $order->status == 'menunggu_verifikasi_admin')
                                        <button
                                            class="px-3 py-1 text-xs font-bold text-white bg-blue-600 rounded js-open-modal-btn hover:bg-blue-700"
                                            data-target-modal="verifyOrderModal" data-order-id="{{ $order->id }}">
                                            Verifikasi
                                        </button>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-6 text-center text-gray-500">Tidak ada pesanan ditemukan.</td>
                            </tr>
                        @endforelse {{-- <<< KESALAHAN ADA DI SINI, SEKARANG SUDAH BENAR --}}
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    {{-- @include('dashboard.admin.order-list.verify-modal') --}}
@endsection
{{-- Sertakan file modal baru di sini --}}
@push('flowbite-modals')
    @include('dashboard.admin.order-list.verify-modal')
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

        // Fungsi untuk menampilkan gambar zoom
        function showVerifyModalZoom(imgSrc) {
            document.getElementById('verifyModalZoomImg').src = imgSrc;
            document.getElementById('verifyModalZoomWrapper').classList.remove('hidden');
            document.getElementById('verifyModalZoomWrapper').classList.add('flex');
        }

        // --- FUNGSI UTAMA MODAL ---
        document.addEventListener('DOMContentLoaded', function() {
            // Event listener untuk tombol buka modal verifikasi
            document.body.addEventListener('click', function(event) {
                const openBtn = event.target.closest(
                    '.js-open-modal-btn[data-target-modal="verifyOrderModal"]');
                if (openBtn) {
                    const orderId = openBtn.dataset.orderId;
                    openModal('verifyOrderModal');
                    openVerifyModal(orderId);
                }
            });

            // Event listener untuk menutup gambar zoom
            const zoomWrapper = document.getElementById('verifyModalZoomWrapper');
            if (zoomWrapper) {
                zoomWrapper.addEventListener('click', () => {
                    zoomWrapper.classList.add('hidden');
                    zoomWrapper.classList.remove('flex');
                });
            }
        });

        // Mengambil data dan mengisi modal verifikasi
        async function openVerifyModal(orderId) {
            const loader = document.getElementById('verifyModalLoader');
            const content = document.getElementById('verifyModalContent');
            const modalTitle = document.querySelector('#verifyOrderModal h3'); // Menargetkan judul modal

            const returnedProductsSection = document.getElementById('returnedProductsSection');
            const returnedProductsList = document.getElementById('verifyModalReturnedProducts');

            loader.classList.remove('hidden');
            content.classList.add('hidden');
            modalTitle.textContent = "Verifikasi Rincian Pesanan"; // Reset judul
            returnedProductsSection.classList.add('hidden');

            try {
                const response = await fetch(`/admin/orders/${orderId}/details`);
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Gagal memuat data.');

                // Validasi minimal data
                if (!data || typeof data !== 'object') throw new Error('Data tidak valid.');

                // Mengisi konten modal umum
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
                    `${data.paid_at}${data.paid_at_label}` : 'Belum Lunas';
                document.getElementById('verifyModalCourierName').textContent = data.kurir_name || '-';

                // Mengisi rincian produk pesanan
                const productDetailsDiv = document.getElementById('verifyModalProductDetails');
                productDetailsDiv.innerHTML = '';
                if (Array.isArray(data.items) && data.items.length > 0) {
                    data.items.forEach(item => {
                        const productItem = document.createElement('div');
                        productItem.className = 'p-2 border rounded-lg dark:border-gray-700';
                        productItem.innerHTML =
                            `<p class="font-semibold text-gray-900 dark:text-white">${item.name} ${item.variant_name ? `(${item.variant_name})` : ''}</p><p class="text-sm text-gray-700 dark:text-gray-300">Jumlah: ${item.quantity} x Rp ${new Intl.NumberFormat('id-ID').format(item.price)}</p>`;
                        productDetailsDiv.appendChild(productItem);
                    });
                } else {
                    productDetailsDiv.innerHTML = '<p>Tidak ada produk.</p>';
                }

                const paymentProofContainer = document.getElementById('verifyModalPaymentProof').parentElement;
                const paymentProofDiv = document.getElementById('verifyModalPaymentProof');
                const proofTitle = paymentProofContainer.querySelector('h4');

                if (data.return_details) {
                    // --- TAMPILAN UNTUK PESANAN RETUR ---
                    modalTitle.textContent = "Verifikasi Pesanan dengan Retur";
                    proofTitle.textContent = "✅ Bukti Retur";

                    // Tampilkan bukti retur
                    if (data.return_details.return_proof) {
                        const imageUrl = `/storage/${data.return_details.return_proof.replace('public/', '')}`;
                        paymentProofDiv.innerHTML =
                            `<img src="${imageUrl}" class="object-cover w-32 h-32 border-2 border-gray-300 rounded shadow cursor-zoom-in" alt="Bukti Retur" onclick="showVerifyModalZoom('${imageUrl}')">`;
                    } else {
                        paymentProofDiv.innerHTML = '<span class="text-red-500">Bukti retur belum diupload.</span>';
                    }

                    // Perbarui total tagihan
                    const originalTotal = data.total_amount || 0;
                    const returnedAmount = data.return_details.total_amount_returned || 0;
                    const newTotal = originalTotal - returnedAmount;
                    document.getElementById('verifyModalTotalAmount').innerHTML =
                        `<span class="block text-sm font-normal text-gray-500 line-through">Rp ${Number(originalTotal).toLocaleString('id-ID')}</span>` +
                        `<span class="block text-green-600 dark:text-green-500">Rp ${Number(newTotal).toLocaleString('id-ID')} (Setelah Retur)</span>`;

                    // **LOGIKA BARU: Tampilkan daftar produk yang diretur**
                    returnedProductsSection.classList.remove('hidden');
                    returnedProductsList.innerHTML = ''; // Kosongkan daftar
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
                    // --- TAMPILAN UNTUK PESANAN NORMAL ---
                    proofTitle.textContent = "✅ Bukti Pembayaran";
                    document.getElementById('verifyModalTotalAmount').textContent = 'Rp ' + (data.total_amount ? Number(
                        data.total_amount).toLocaleString('id-ID') : '0');
                    if (data.payment_proof) {
                        const imageUrl = `/storage/${data.payment_proof.replace('public/', '')}`;
                        paymentProofDiv.innerHTML =
                            `<img src="${imageUrl}" class="object-cover w-32 h-32 border-2 border-gray-300 rounded shadow cursor-zoom-in" alt="Bukti Pembayaran" onclick="showVerifyModalZoom('${imageUrl}')">`;
                    } else {
                        paymentProofDiv.innerHTML = '<span class="text-red-500">Belum diupload oleh kurir</span>';
                    }
                }

                // Mengatur event listener untuk tombol aksi
                document.getElementById('btnVerifyOrder').onclick = () => verifyOrder(orderId);
                document.getElementById('btnRejectOrder').onclick = () => rejectOrder(orderId);

                loader.classList.add('hidden');
                content.classList.remove('hidden');

            } catch (error) {
                console.error('Error openVerifyModal:', error);
                loader.innerHTML = `<div class='text-center text-red-600'>Error: ${error.message}</div>`;
                content.classList.add('hidden');
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

        // Fungsi untuk mengirim request penolakan
        async function rejectOrder(orderId) {
            const modalElement = document.getElementById('verifyOrderModal');
            try {
                const response = await fetch(`/admin/orders/${orderId}/reject`, {
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
                dispatchToast('Verifikasi pesanan ditolak.', 'success');
                setTimeout(() => window.location.reload(), 1500);

            } catch (error) {
                dispatchToast(`Gagal menolak: ${error.message}`, 'error');
            }
        }
    </script>
@endpush
