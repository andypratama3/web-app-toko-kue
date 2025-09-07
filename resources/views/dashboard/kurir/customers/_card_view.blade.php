@forelse ($customers as $customer)
    <div class="flex flex-col justify-between w-full max-w-sm p-4 bg-white border border-gray-200 rounded-lg shadow-md dark:bg-gray-800 dark:border-gray-700">
        {{-- Bagian Konten Utama --}}
        <div>
            {{-- Header Kartu --}}
            <div class="flex items-start justify-between mb-3">
                {{-- Nama Toko dan Nama Customer --}}
                <div class="flex-1 min-w-0">
                    <h4 class="text-lg font-semibold text-gray-900 truncate dark:text-white" title="{{ $customer->company_name ?? $customer->name }}">
                        {{-- Tampilkan Nama Toko, atau Nama Customer jika Toko kosong --}}
                        🏪{{ $customer->company_name ?? $customer->name }}
                    </h4>
                    {{-- Tampilkan Nama Customer sebagai subjudul jika Nama Toko ada --}}
                    @if ($customer->company_name)
                    <p class="flex items-center text-md text-gray-500 dark:text-gray-400">
                        {{ $customer->name }}
                        @if ($customer->is_flagged)
                            <i class="ml-2 text-red-500 fas fa-flag" title="Customer ditandai"></i>
                        @endif
                    </p>
                    @endif
                </div>
                {{-- Tombol Detail --}}
                <button type="button" data-target-modal="show-customer-modal-{{ $customer->id }}"
                    class="flex-shrink-0 ml-2 text-lg text-gray-500 transition transform js-open-modal-btn dark:text-gray-400 hover:scale-125 active:scale-90">
                    <i class="fas fa-info-circle"></i>
                </button>
            </div>

            {{-- Detail Alamat dan Catatan --}}
            <div class="space-y-3">
                {{-- Alamat & Patokan --}}
                <div>
                    <p class="text-sm text-gray-700 dark:text-gray-300">
                        📍 <span class="font-semibold">Alamat:</span> {{ $customer->address }}
                    </p>
                    @if ($customer->landmark)
                        <p class="pl-4 text-xs italic text-gray-500 dark:text-gray-400">
                            (Patokan: {{ $customer->landmark }})
                        </p>
                    @endif
                </div>

                {{-- Catatan --}}
                @if ($customer->note)
                    <div>
                        <p class="text-sm text-gray-700 dark:text-gray-300">
                           📌 <span class="font-semibold">Catatan:</span> <span class="italic">"{{ $customer->note }}"</span>
                        </p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Bagian Tombol Aksi --}}
        <div class="flex justify-end pt-3 mt-3 space-x-4 border-t border-gray-200 dark:border-gray-700">
            <a href="https://wa.me/{{ $customer->phone }}" target="_blank"
                class="text-lg text-green-600 transition transform dark:text-green-500 hover:scale-125 active:scale-90" title="Hubungi via WhatsApp">
                <i class="fab fa-whatsapp"></i>
            </a>
            <button type="button" data-target-modal="note-customer-modal-{{ $customer->id }}"
                class="text-lg text-yellow-600 transition transform js-open-modal-btn dark:text-yellow-500 hover:scale-125 active:scale-90" title="Edit Catatan">
                📋{{-- <i class="fas fa-sticky-note"></i> --}}
            </button>
            <button type="button" data-target-modal="edit-customer-modal-{{ $customer->id }}"
                class="text-lg text-blue-600 transition transform js-open-modal-btn dark:text-blue-500 hover:scale-125 active:scale-90" title="Edit Customer">
                ✍🏻{{-- <i class="fas fa-edit"></i> --}}
            </button>
            <button type="button" data-target-modal="delete-customer-modal-{{ $customer->id }}"
                class="text-lg text-red-600 transition transform js-open-modal-btn dark:text-red-500 hover:scale-125 active:scale-90" title="Hapus Customer">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    </div>
@empty
    <div class="text-center text-gray-500 dark:text-gray-400">
        Tidak ada data customer ditemukan.
    </div>
@endforelse
