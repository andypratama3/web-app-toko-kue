{{-- resources/views/dashboard/admin/couriers/modals/note.blade.php --}}
@props(['courier'])

<x-modal id="note-courier-modal-{{ $courier->id }}" title="Catatan untuk {{ $courier->name }}" toggle="note-courier-modal-{{ $courier->id }}" size="xl">
    <form action="{{ route('admin.couriers.updateNote', $courier) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="p-6 space-y-6">
            <div>
                <label for="note-{{ $courier->id }}"
                    class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Catatan</label>
                <textarea id="note-{{ $courier->id }}" name="note" rows="4"
                    class="block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                    placeholder="Tambahkan catatan untuk kurir ini...">{{ $courier->note }}</textarea>
            </div>
        </div>
        <div class="flex items-center p-6 space-x-2 border-t border-gray-200 rounded-b dark:border-gray-600">
            <button type="submit"
                class="text-white inline-flex items-center bg-green-600 hover:bg-green-700 focus:ring-4 focus:outline-none focus:ring-green-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-green-500 dark:hover:bg-green-600 dark:focus:ring-green-800">
                <i class="fas fa-save me-1"></i>
                Simpan Catatan
            </button>
            <button type="button"
                class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-gray-200 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-500 dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-600"
                data-modal-hide="note-courier-modal-{{ $courier->id }}">
                Batal
            </button>
        </div>
    </form>
</x-modal>
