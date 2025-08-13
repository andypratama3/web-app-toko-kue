{{-- resources/views/dashboard/admin/customers/modals/edit.blade.php --}}
@props(['customer'])

<x-modal id="edit-customer-modal-{{ $customer->id }}" title="Edit Customer" toggle="edit-customer-modal-{{ $customer->id }}" size="2xl">
    <form class="p-4 md:p-5" action="{{ route('admin.customers.update', $customer->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="grid grid-cols-2 gap-4 mb-4">
            <div class="col-span-2">
                <label for="name-{{ $customer->id }}"
                    class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Nama</label>
                <input type="text" name="name" id="name-{{ $customer->id }}"
                    value="{{ old('name', $customer->name) }}"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                    required>
            </div>
            <div class="col-span-2">
                <label for="address-{{ $customer->id }}"
                    class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Alamat</label>
                <textarea name="address" id="address-{{ $customer->id }}" rows="3"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                    required>{{ old('address', $customer->address) }}</textarea>
            </div>
            <div class="col-span-2">
                <label for="phone-{{ $customer->id }}"
                    class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">No. HP</label>
                <input type="tel" name="phone" id="phone-{{ $customer->id }}"
                    value="{{ old('phone', $customer->phone) }}"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                    required>
            </div>
            <div class="col-span-2">
                <label for="note-{{ $customer->id }}"
                    class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Note (Opsional)</label>
                <textarea name="note" id="note-{{ $customer->id }}" rows="3"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">{{ old('note', $customer->note) }}</textarea>
            </div>
        </div>
        <button type="submit"
            class="text-white inline-flex items-center bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
            <i class="w-4 h-4 mr-2 fas fa-save"></i>
            Simpan Perubahan
        </button>
    </form>
</x-modal>
