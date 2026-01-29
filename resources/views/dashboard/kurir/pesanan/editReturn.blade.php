@extends('layouts.argon')
@section('title', 'Edit Retur')
@section('page_title', 'Order')
@section('content')

    <div id="error" class="mb-4"></div>

    <form onsubmit="event.preventDefault(); submitReturn();">

        <h3>Data Customer</h3>

        <label>Nama Customer</label>
        <input type="hidden" id="id" disabled>
        <input type="text" id="name" disabled>

        <label>No. Hp</label>
        <input type="text" id="phone" disabled>

        <label>Alamat</label>
        <input type="text" id="address" disabled>

        <h3 class="mt-4">Produk</h3>
        <div id="product"></div>

        <h3 class="mt-4">Metode Pembayaran</h3>
        <select id="paymentMethod" disabled>
            <option value="">- Pilih Metode Pembayaran -</option>
            <option value="cash">Cash</option>
            <option value="tf">Transfer</option>
        </select>

        <label class="mt-2">Catatan</label>
        <textarea id="note" rows="4" disabled></textarea>

        <button type="submit" class="mt-4 px-4 py-2 bg-orange-500 text-white rounded hover:bg-orange-600">
            Simpan Retur
        </button>
    </form>

    <script>
        /* ================= CSRF ================= */
        function getCsrfToken() {
            return document.querySelector('meta[name="csrf-token"]').content;
        }

        /* ================= LOAD DATA ================= */
        async function loadContent() {
            try {
                const STORAGE_URL = "{{ Storage::url('') }}";
                const params = new URLSearchParams(window.location.search);
                const orderId = params.get('id');

                if (!orderId) throw new Error('Order ID tidak ditemukan');

                const response = await fetch(`/kurir/pesanan/${orderId}/details`, {
                    headers: {
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) throw new Error('Gagal memuat data');

                const data = await response.json();
                console.log(data);

                document.getElementById('id').value = data.order_return.id
                document.getElementById('name').value =
                    `${data.customer.name} ${data.customer.company_name ?? ''}`;
                document.getElementById('phone').value = data.customer.phone ?? '-';
                document.getElementById('address').value = data.customer.address ?? '-';
                document.getElementById('paymentMethod').value = data.payment_method ?? '';
                document.getElementById('note').value = data.note ?? '';

                let productHtml = '';

                data.products.forEach(product => {
                    const key = `${product.product_id}-${product.variant_id}`;
                    const returnedQty = product.returned_quantity ?? 0;

                    productHtml += `
                <div class="mb-4 p-3 border rounded">
                    <img src="${STORAGE_URL}${product.image_url}"
                         class="w-24 mb-2">

                    <p class="font-semibold">
                        ${product.name} (${product.variant_name})
                    </p>

                    <p class="mt-1">
                        Awal: ${product.quantity} |
                        Retur:
                        <input
                            type="number"
                            min="0"
                            max="${product.quantity}"
                            value="${returnedQty}"
                            class="return-qty border px-2 w-20"
                            data-product-key="${key}"
                        >
                        | Sisa: ${product.quantity - returnedQty}
                    </p>
                </div>
            `;
                });

                document.getElementById('product').innerHTML = productHtml;

            } catch (error) {
                console.error(error);
                document.getElementById('error').innerHTML = `
            <p class="text-red-600 font-bold">${error.message}</p>
        `;
            }
        }

        /* ================= SUBMIT RETURN ================= */
        async function submitReturn() {
            try {
                const params = new URLSearchParams(window.location.search);
                const orderId = params.get('id');

                if (!orderId) throw new Error('Order ID tidak ditemukan');

                const orderReturnId = document.getElementById('id').value;
                if (!orderReturnId) throw new Error('Order Return ID tidak ditemukan');

                const inputs = document.querySelectorAll('.return-qty');
                const returnQuantities = {};

                inputs.forEach(input => {
                    const key = input.dataset.productKey;
                    const qty = Number(input.value || 0);

                    if (!key) return;
                    if (qty > 0) {
                        returnQuantities[key] = qty;
                    }
                });

                if (Object.keys(returnQuantities).length === 0) {
                    throw new Error('Tidak ada produk yang diretur');
                }

                const reason = document.getElementById('note').value;

                const response = await fetch(
                    `/kurir/pesanan/${orderId}/request-return/edit`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': getCsrfToken()
                        },
                        body: JSON.stringify({
                            order_return_id: orderReturnId,
                            return_quantities: returnQuantities,
                            reason: reason
                        })
                    }
                );

                const result = await response.json();

                if (!response.ok) {
                    throw new Error(result.message ?? 'Gagal menyimpan retur');
                }

                alert(result.message);

            } catch (error) {
                alert(`Gagal: ${error.message}`);
            }
        }


        /* ================= INIT ================= */
        loadContent();
    </script>

@endsection
