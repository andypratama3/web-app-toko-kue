@extends('layouts.argon')
@section('title', 'Manajemen Pesanan')
@section('page_title', 'Pesanan')

@section('content')
    <div class="flex-auto p-3 pt-0 -mx-3">
        <div class="p-2.5 bg-white shadow-md rounded-xl dark:bg-gray-800 dark:border-gray-700 min-h-[715px]">
            <h2 class="mb-6 text-black text-md dark:text-white">
                👤 {{ Auth::user()->name ?? 'Admin' }}
                🚩 {{ Auth::user()->region->name ?? 'N/A' }}
            </h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead>
                        <tr>
                            <th class="px-4 py-2">No</th>
                            <th class="px-4 py-2">Invoice</th>
                            <th class="px-4 py-2">Customer</th>
                            <th class="px-4 py-2">Kurir</th>
                            <th class="px-4 py-2">Status</th>
                            <th class="px-4 py-2">Total</th>
                            <th class="px-4 py-2">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($orders as $order)
                            <tr>
                                <td class="px-4 py-2">{{ $loop->iteration }}</td>
                                <td class="px-4 py-2">{{ $order->invoice_number }}</td>
                                <td class="px-4 py-2">{{ $order->customer->name ?? '-' }}</td>
                                <td class="px-4 py-2">{{ $order->createdBy->name ?? '-' }}</td>
                                <td class="px-4 py-2">
                                    <span class="inline-block px-2 py-1 text-xs font-semibold rounded
                                        @switch($order->status)
                                            @case('selesai')
                                                bg-blue-100 text-blue-800
                                                @break
                                            @case('diverifikasi_admin')
                                                bg-green-100 text-green-800
                                                @break
                                            @default
                                                bg-gray-100 text-gray-800
                                        @endswitch">
                                        {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                                    </span>
                                </td>
                                <td class="px-4 py-2">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                                <td class="px-4 py-2">
                                    {{-- Logika ini sudah benar, saat status 'selesai', tombol akan muncul --}}
                                    @if ($order->status == 'selesai')
                                        <button
                                            class="px-3 py-1 text-xs font-bold text-white bg-blue-600 rounded hover:bg-blue-700"
                                            onclick="openVerifyModal({{ $order->id }})">Verifikasi</button>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-6 text-center text-gray-500">Tidak ada pesanan ditemukan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @include('dashboard.admin.order-list.verify-modal')

    <script>
        // --- Modal Zoom Image ---
        function showVerifyModalZoom(imgSrc) {
            const zoomWrapper = document.getElementById('verifyModalZoomWrapper');
            const zoomImg = document.getElementById('verifyModalZoomImg');
            zoomImg.src = imgSrc;
            zoomWrapper.classList.remove('hidden');
        }
        document.addEventListener('DOMContentLoaded', function() {
            const zoomWrapper = document.getElementById('verifyModalZoomWrapper');
            if (zoomWrapper) {
                zoomWrapper.onclick = function(e) {
                    if (e.target === zoomWrapper) zoomWrapper.classList.add('hidden');
                };
            }
        });

        async function openVerifyModal(orderId) {
            const modal = document.getElementById('verifyOrderModal');
            const loader = document.getElementById('verifyModalLoader');
            const content = document.getElementById('verifyModalContent');
            loader.classList.remove('hidden');
            content.classList.add('hidden');
            modal.classList.remove('hidden');
            try {
                const res = await fetch(`/admin/orders/${orderId}/details`);
                const data = await res.json();
                if (!res.ok) throw new Error(data.message);
                // Populate modal content
                document.getElementById('verifyModalInvoice').textContent = data.invoice_number || '-';
                document.getElementById('verifyModalCustomer').textContent = data.customer?.name || '-';
                document.getElementById('verifyModalKurir').textContent = data.kurir_name || '-';
                document.getElementById('verifyModalStatus').textContent = data.status || '-';
                document.getElementById('verifyModalTotal').textContent = 'Rp ' + (data.total_amount ? Number(data
                    .total_amount).toLocaleString('id-ID') : '0');
                // Always show payment proof image (square, zoomable)
                let paymentProofHtml = '';
                if (data.payment_proof) {
                    paymentProofHtml =
                        `<img src="/storage/${data.payment_proof}" class="object-cover w-32 h-32 border-2 border-gray-300 rounded shadow cursor-zoom-in" alt="Bukti Pembayaran" onclick="showVerifyModalZoom('/storage/${data.payment_proof}')">`;
                } else {
                    paymentProofHtml = '<span class="text-red-500">Belum diupload</span>';
                }
                document.getElementById('verifyModalPaymentProof').innerHTML = paymentProofHtml;
                loader.classList.add('hidden');
                content.classList.remove('hidden');
                // Set button actions
                document.getElementById('btnVerifyOrder').onclick = function() {
                    verifyOrder(orderId);
                };
                document.getElementById('btnRejectOrder').onclick = function() {
                    rejectOrder(orderId);
                };
            } catch (err) {
                loader.innerHTML = `<div class='text-center text-red-600'>${err.message}</div>`;
            }
        }

        function closeVerifyModal() {
            document.getElementById('verifyOrderModal').classList.add('hidden');
        }
        async function verifyOrder(orderId) {
            if (!confirm('Verifikasi pesanan ini?')) return;
            try {
                const res = await fetch(`/admin/orders/${orderId}/verify`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                            'content')
                    }
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message);
                alert('Pesanan berhasil diverifikasi!');
                window.location.reload();
            } catch (err) {
                alert('Gagal verifikasi: ' + err.message);
            }
        }
        async function rejectOrder(orderId) {
            if (!confirm('Tolak verifikasi pesanan ini?')) return;
            try {
                // POST to /admin/orders/{orderId}/reject, expect backend to set status to 'diterima_pembeli' and clear payment_proof
                const res = await fetch(`/admin/orders/${orderId}/reject`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                            'content')
                    }
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message);
                alert(
                    'Verifikasi pesanan ditolak. Status dikembalikan ke "Diterima Pembeli". Silakan upload ulang bukti pembayaran.'
                    );
                window.location.reload();
            } catch (err) {
                alert('Gagal menolak: ' + err.message);
            }
        }
    </script>
@endsection
