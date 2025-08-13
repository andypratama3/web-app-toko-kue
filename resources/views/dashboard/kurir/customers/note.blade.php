{{-- resources/views/dashboard/kurir/customers/modals/note.blade.php --}}
@props(['customer'])

<x-modal id="note-modal-{{ $customer->id }}" title="Update Catatan untuk {{ $customer->name }}" toggle="note-modal-{{ $customer->id }}" size="xl">
    <form class="p-4 md:p-5" action="{{ route('kurir.customers.update-note', $customer->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="space-y-4">
            <div>
                <label for="note-area-{{ $customer->id }}" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Catatan</label>
                <textarea id="note-area-{{ $customer->id }}" name="note" rows="4" class="block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-primary-600 focus:border-primary-600 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white">{{ $customer->note }}</textarea>
            </div>
        </div>
        <div class="flex justify-end pt-4 mt-4 border-t border-gray-200 dark:border-gray-600">
             <button type="submit"
                class="text-white inline-flex items-center bg-green-600 hover:bg-green-700 focus:ring-4 focus:outline-none focus:ring-green-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-green-500 dark:hover:bg-green-600 dark:focus:ring-green-800">
                <i class="fas fa-save me-1"></i>
                Update Catatan
            </button>
        </div>
    </form>
</x-modal>
