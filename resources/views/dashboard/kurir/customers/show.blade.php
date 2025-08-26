@props(['customer'])

<x-modal-custom id="show-customer-modal-{{ $customer->id }}" title="Detail Customer" size="4xl">
    {{-- Hapus padding default agar konten bisa menempel ke tepi modal --}}
    <div class="p-0">
        {{--
            PERBAIKAN UTAMA:
            - Tambahkan 'flex flex-col' untuk membuat layout kolom vertikal.
            - 'overflow-hidden' memastikan konten tidak keluar dari batas rounded.
        --}}
        <div class="flex flex-col overflow-hidden">

            {{--
                Area konten yang bisa di-scroll.
                - 'overflow-y-auto' akan memunculkan scrollbar jika konten lebih tinggi dari 'max-h-[70vh]'.
                - 'max-h-[70vh]' membatasi tinggi area ini maksimal 70% dari tinggi viewport/layar.
            --}}
            <div class="overflow-y-auto max-h-[70vh]">
                {{-- Gunakan flexbox untuk layout yang lebih fleksibel --}}
                <div class="flex flex-col md:flex-row">
                    {{-- Kolom Kiri - Informasi Utama --}}
                    <div class="w-full p-5 md:w-2/3">
                        <h3 class="mb-1 text-xl font-bold text-gray-900 dark:text-white">{{ $customer->name }}</h3>
                        <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">{{ $customer->company_name ?? 'Customer Personal' }}</p>

                        <dl class="space-y-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Nomor Telepon</dt>
                                <dd class="mt-1 font-semibold text-gray-900 dark:text-white">{{ $customer->phone ? '+' . $customer->phone : '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Alamat Lengkap</dt>
                                <dd class="mt-1 text-gray-900 dark:text-white">{{ $customer->address ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Patokan Tempat</dt>
                                <dd class="mt-1 text-gray-900 dark:text-white">{{ $customer->landmark ?? '-' }}</dd>
                            </div>
                            <div class="pt-4 mt-4 border-t border-gray-200 dark:border-gray-600">
                                <dt class="mb-1 text-sm font-medium text-gray-500 dark:text-gray-400">Catatan</dt>
                                <dd class="prose-sm prose text-gray-900 max-w-none dark:text-gray-300">
                                    {!! $customer->note ? nl2br(e($customer->note)) : '<span class="italic text-gray-400">Tidak ada catatan.</span>' !!}
                                </dd>
                            </div>
                        </dl>
                    </div>

                    {{-- Kolom Kanan - Informasi Tambahan --}}
                    <div class="w-full p-5 border-t border-gray-200 md:w-1/3 bg-gray-50 dark:bg-gray-800 md:border-t-0 md:border-l dark:border-gray-600">
                        <h3 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white">Informasi Lainnya</h3>
                        <dl class="space-y-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</dt>
                                <dd class="mt-1">
                                    @if ($customer->is_flagged)
                                        <span class="px-2 py-1 text-xs font-medium text-red-800 bg-red-100 rounded-full dark:bg-red-900 dark:text-red-300">Bermasalah</span>
                                    @else
                                        <span class="px-2 py-1 text-xs font-medium text-green-800 bg-green-100 rounded-full dark:bg-green-900 dark:text-green-300">Normal</span>
                                    @endif
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Kategori Customer</dt>
                                <dd class="mt-1 text-gray-900 dark:text-white">{{ optional($customer->category)->name ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Jam Buka</dt>
                                <dd class="mt-1 text-gray-900 dark:text-white">{{ $customer->opening_hours ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Jenis Pembayaran</dt>
                                <dd class="mt-1 text-gray-900 dark:text-white">{{ $customer->payment_type ?? '-' }}</dd>
                            </div>
                            <div class="pt-4 mt-4 border-t border-gray-200 dark:border-gray-600">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Region</dt>
                                <dd class="mt-1 text-gray-900 dark:text-white">{{ optional($customer->region)->name ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Ditambahkan Oleh</dt>
                                <dd class="mt-1 text-gray-900 dark:text-white">{{ optional($customer->addedBy)->name ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Tanggal Bergabung</dt>
                                <dd class="mt-1 text-xs text-gray-900 dark:text-white">{{ $customer->created_at->format('d F Y, H:i') }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

            {{-- Bagian Footer Modal (Tombol Tutup) --}}
            <div class="flex items-center justify-end p-4 border-t border-gray-200 dark:border-gray-600">
                <button type="button"
                    class="js-close-modal-btn py-2.5 px-5 text-sm font-medium text-gray-900 focus:outline-none bg-white rounded-lg border border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-4 focus:ring-gray-100 dark:focus:ring-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</x-modal-custom>
