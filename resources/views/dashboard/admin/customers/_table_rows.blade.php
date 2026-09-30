{{--
    Baris tabel customer.
    data-label dipakai CSS .admin-table untuk mengubah <tr> menjadi kartu
    di layar < 768px (label kiri, nilai kanan). Karena live-search me-render
    ulang partial ini lewat AJAX, label harus ada di file ini — bukan di CSS.
--}}
@forelse ($customers as $customer)
    <tr class="border-b border-gray-100 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-700/40">
        {{-- NO --}}
        <td data-label="No."
            class="px-4 py-3 font-medium text-center text-gray-900 dark:text-white">
            {{ ($customers->currentPage() - 1) * $customers->perPage() + $loop->iteration }}
        </td>

        {{-- NAMA PERUSAHAAN --}}
        <td data-label="Perusahaan"
            class="px-4 py-3 font-medium text-gray-900 dark:text-white">
            {{ $customer->company_name ?? '-' }}
        </td>

        {{-- NAMA CUSTOMER (+ FLAG) --}}
        {{-- whitespace-nowrap DIHAPUS: memaksa kolom melebar dan FLAG terpotong --}}
        <td data-label="Customer"
            class="px-4 py-3 font-medium text-gray-900 dark:text-white">
            <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                <span class="break-words">{{ $customer->name }}</span>

                {{-- inline-flex + flex-shrink-0 menjaga flag tetap di baris
                     yang sama dan tidak "menggantung" di luar kotak --}}
                <button type="button" class="toggle-flag-btn inline-flex shrink-0 items-center justify-center rounded p-1 align-middle"
                    aria-label="{{ $customer->is_flagged ? 'Hapus tanda customer bermasalah' : 'Tandai sebagai customer bermasalah' }}"
                    data-url="{{ route('admin.customers.toggleFlag', $customer) }}">
                    @if ($customer->is_flagged)
                        <i class="text-red-500 fas fa-flag" title="Customer Bermasalah. Klik untuk menghapus tanda."></i>
                    @else
                        <i class="text-gray-400 fas fa-flag hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300"
                            title="Tandai sebagai customer bermasalah."></i>
                    @endif
                </button>
            </div>
        </td>

        {{-- ALAMAT (+ LANDMARK) --}}
        <td data-label="Alamat" class="px-4 py-3 text-gray-900 dark:text-white">
            <span class="break-words">{{ Str::limit($customer->address, 50, '...') }}</span>

            @if ($customer->landmark)
                <span class="block mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Patokan: {{ $customer->landmark }}
                </span>
            @endif
        </td>

        {{-- NOMOR TELEPON --}}
        <td data-label="Telepon" class="px-4 py-3 text-center text-gray-900 dark:text-white">
            <span class="break-all">+{{ $customer->phone }}</span>
        </td>

        {{-- NOTE --}}
        <td data-label="Note" class="px-4 py-3 text-center">
            <span class="break-words">{{ Str::limit($customer->note, 20) }}</span>
        </td>

        {{-- KOLOM AKSI --}}
        <td data-label="Aksi" class="px-4 py-3 text-right">
            <div class="relative inline-block text-left">
                {{-- Tombol Dropdown Aksi --}}
                <button data-target-dropdown="customer-actions-dropdown-{{ $customer->id }}"
                    aria-label="Aksi untuk {{ $customer->name }}"
                    class="inline-flex items-center justify-center px-2 py-1 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg js-dropdown-toggle hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:ring-2 focus:ring-gray-100 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700">
                    <i class="fas fa-ellipsis-v"></i>
                </button>
                {{-- Konten Dropdown --}}
                <div id="customer-actions-dropdown-{{ $customer->id }}"
                    class="absolute right-0 z-50 hidden mt-2 bg-white divide-y divide-gray-100 rounded shadow js-dropdown-menu w-44 dark:bg-gray-700 dark:divide-gray-600">
                    <ul class="py-1 text-sm text-gray-700 dark:text-gray-200">
                        {{-- Detail/Show --}}
                        <li>
                            <button type="button" data-target-modal="show-customer-modal-{{ $customer->id }}"
                                class="flex items-center w-full px-4 py-2 text-left js-open-modal-btn hover:bg-gray-100 dark:hover:bg-gray-600">
                                <span class="inline-block w-6 mr-2 text-center"><i class="fas fa-eye"></i></span>
                                <span>Detail</span>
                            </button>
                        </li>
                        {{-- Edit --}}
                        <li>
                            <button type="button" data-target-modal="edit-customer-modal-{{ $customer->id }}"
                                class="flex items-center w-full px-4 py-2 text-left js-open-modal-btn hover:bg-gray-100 dark:hover:bg-gray-600">
                                <span class="inline-block w-6 mr-2 text-center"><i class="fas fa-edit"></i></span>
                                <span>Edit</span>
                            </button>
                        </li>
                        {{-- Note --}}
                        <li>
                            <button type="button" data-target-modal="note-customer-modal-{{ $customer->id }}"
                                class="flex items-center w-full px-4 py-2 text-left js-open-modal-btn hover:bg-gray-100 dark:hover:bg-gray-600">
                                <span class="inline-block w-6 mr-2 text-center"><i
                                        class="fas fa-sticky-note"></i></span>
                                <span>Note</span>
                            </button>
                        </li>
                        {{-- Rekap --}}
                        <li>
                            <button type="button" data-target-modal="rekap-customer-modal-{{ $customer->id }}"
                                class="flex items-center w-full px-4 py-2 text-left js-open-modal-btn hover:bg-gray-100 dark:hover:bg-gray-600">
                                <span class="inline-block w-6 mr-2 text-center"><i class="fas fa-file-download"></i></span>
                                <span>Rekap</span>
                            </button>
                        </li>
                    </ul>
                    <div class="py-1">
                        {{-- Delete --}}
                        <button type="button" data-target-modal="delete-customer-modal-{{ $customer->id }}"
                            class="flex items-center w-full px-4 py-2 text-sm text-left text-red-600 js-open-modal-btn hover:bg-gray-100 dark:hover:bg-gray-600 dark:text-red-500 dark:hover:text-white">
                            <span class="inline-block w-6 mr-2 text-center"><i class="fas fa-trash"></i></span>
                            <span>Delete</span>
                        </button>
                    </div>
                </div>
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="7" class="px-4 py-4 text-center text-gray-500 dark:text-gray-400">Tidak ada data customer.</td>
    </tr>
@endforelse
