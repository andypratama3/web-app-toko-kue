{{-- resources/views/dashboard/admin/regions/edit.blade.php --}}

<x-modal-custom id="edit-region-modal-{{ $region->id }}"
    title="Atur Nomor Owner — {{ $region->name }}" size="lg">
    <form class="p-4 md:p-5" method="POST" action="{{ route('admin.regions.update', $region->id) }}">
        @csrf
        @method('PUT')

        <div class="mb-4">
            <label for="owner_phone-{{ $region->id }}"
                class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                Nomor WhatsApp Owner / Pemilik Cabang
            </label>
            <input type="text" name="owner_phone" id="owner_phone-{{ $region->id }}"
                value="{{ old('owner_phone', $region->owner_phone ? '+' . $region->owner_phone : '') }}"
                placeholder="contoh: 0812 3456 7890" inputmode="tel" autocomplete="off"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                Format bebas: <span class="font-mono">0812…</span>, <span class="font-mono">62812…</span>, atau
                <span class="font-mono">+62 812…</span>. Disimpan otomatis sebagai <span class="font-mono">62…</span>.
                Kosongkan untuk menonaktifkan penerusan ke owner.
            </p>
            @error('owner_phone')
                <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                    <i class="mr-1 fas fa-circle-exclamation"></i>{{ $message }}
                </p>
            @enderror
        </div>

        <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
                <label for="latitude-{{ $region->id }}" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Latitude</label>
                <input type="number" step="0.0000001" name="latitude" id="latitude-{{ $region->id }}" value="{{ old('latitude', $region->latitude) }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white">
            </div>
            <div>
                <label for="longitude-{{ $region->id }}" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Longitude</label>
                <input type="number" step="0.0000001" name="longitude" id="longitude-{{ $region->id }}" value="{{ old('longitude', $region->longitude) }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white">
            </div>
        </div>

        <div class="mb-4 flex items-center">
            <input type="checkbox" name="is_active" value="1" id="is_active-{{ $region->id }}" {{ old('is_active', $region->is_active) ? 'checked' : '' }} class="w-4 h-4 text-green-600 bg-gray-100 border-gray-300 rounded focus:ring-green-500">
            <label for="is_active-{{ $region->id }}" class="ml-2 text-sm font-medium text-gray-900 dark:text-white">Cabang Aktif</label>
        </div>

        <div class="flex items-center gap-2">
            <button type="submit"
                class="text-white inline-flex items-center bg-green-600 hover:bg-green-700 focus:ring-4 focus:outline-none focus:ring-green-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-green-500 dark:hover:bg-green-600 dark:focus:ring-green-800">
                <i class="fas fa-save me-1"></i>
                Simpan Nomor
            </button>
            <button type="button"
                class="js-close-modal-btn text-gray-700 inline-flex items-center bg-gray-100 hover:bg-gray-200 focus:ring-4 focus:outline-none focus:ring-gray-200 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-gray-600 dark:text-white dark:hover:bg-gray-500">
                Batal
            </button>
        </div>
    </form>
</x-modal-custom>
