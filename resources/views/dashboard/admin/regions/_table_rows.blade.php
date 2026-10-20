@forelse ($regions as $region)
    @php($isOwnRegion = $region->id === ($ownRegionId ?? null))
    <tr class="border-b dark:border-gray-700">
        <td data-label="No." class="px-4 py-3 font-medium text-center text-gray-900 dark:text-white">
            {{ $loop->iteration }}
        </td>
        <td data-label="Cabang" class="px-4 py-3 font-medium text-gray-900 dark:text-white">
            {{ $region->name }}
            @if ($isOwnRegion)
                <span
                    class="ml-1 inline-flex items-center px-2 py-0.5 text-xs font-medium rounded bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                    Cabang Anda
                </span>
            @endif
        </td>
        <td data-label="Nomor Owner WhatsApp" class="px-4 py-3">
            @if ($region->owner_phone)
                <a href="https://wa.me/{{ $region->owner_phone }}" target="_blank" rel="noopener"
                    class="inline-flex items-center font-mono text-green-700 hover:underline dark:text-green-400">
                    <i class="w-5 mr-1 text-green-600 fab fa-whatsapp"></i>+{{ $region->owner_phone }}
                </a>
            @else
                <span
                    class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                    <i class="mr-1 fas fa-triangle-exclamation text-amber-500"></i> Belum diisi
                </span>
            @endif
        </td>
        <td data-label="Status Cabang" class="px-4 py-3 text-center">
            @if ($region->is_active)
                <span
                    class="inline-flex items-center px-2.5 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                    Aktif
                </span>
            @else
                <span
                    class="inline-flex items-center px-2.5 py-0.5 text-xs font-medium rounded-full bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                    Nonaktif
                </span>
            @endif
        </td>
        <td data-label="Aksi" class="px-4 py-3 text-right">
            @if ($isOwnRegion)
                <div class="flex items-center justify-end gap-2">
                    <button type="button" data-target-modal="edit-region-modal-{{ $region->id }}"
                        class="admin-touch-target inline-flex items-center px-3 py-2 text-xs font-medium text-white bg-blue-700 rounded-lg js-open-modal-btn hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800">
                        <i class="mr-1 fas fa-pen"></i> Atur Nomor
                    </button>
                    <form method="POST" action="{{ route('admin.regions.sendTest', $region->id) }}"
                        onsubmit="return confirm('Kirim pesan tes ke nomor owner cabang {{ $region->name }}?');">
                        @csrf
                        <button type="submit"
                            class="admin-touch-target inline-flex items-center px-3 py-2 text-xs font-medium text-gray-900 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
                            <i class="mr-1 fas fa-paper-plane"></i> Kirim Tes
                        </button>
                    </form>
                </div>
            @else
                <span class="text-xs text-gray-400 dark:text-gray-500">
                    <i class="mr-1 fas fa-lock"></i> Hanya cabang Anda
                </span>
            @endif
        </td>
    </tr>
@empty
    <tr class="border-b dark:border-gray-700">
        <td colspan="5" class="px-4 py-3 text-center text-gray-500 dark:text-gray-400">
            Belum ada data cabang.
        </td>
    </tr>
@endforelse
