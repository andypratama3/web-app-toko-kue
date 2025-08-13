{{-- resources/views/dashboard/admin/customers/modals/edit.blade.php --}}
@props(['customer'])

<x-modal id="edit-customer-modal-{{ $customer->id }}" title="Edit Customer"
    toggle="edit-customer-modal-{{ $customer->id }}" size="2xl">
    <form class="p-4 md:p-5" action="{{ route('admin.customers.update', $customer->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="grid grid-cols-2 gap-4 mb-4">
            <div class="col-span-2">
                <label for="name-{{ $customer->id }}"
                    class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Nama</label>
                <input type="text" name="name" id="name-{{ $customer->id }}"
                    value="{{ old('name', $customer->name) }}"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                    required>
            </div>
            <div class="col-span-2">
                <label for="address-{{ $customer->id }}"
                    class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Alamat</label>
                <textarea name="address" id="address-{{ $customer->id }}" rows="3"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                    required>{{ old('address', $customer->address) }}</textarea>
            </div>
            <div class="col-span-2">
                <label for="phone-{{ $customer->id }}"
                    class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">No. HP</label>
                <div class="flex">
                    <span
                        class="inline-flex items-center px-3 text-sm text-gray-900 bg-gray-200 border border-r-0 border-gray-300 rounded-l-md dark:bg-gray-600 dark:text-gray-400 dark:border-gray-500">
                        +62
                    </span>
                    <input type="tel" name="phone" id="phone-{{ $customer->id }}"
                        value="{{ old('phone', substr($customer->phone, 2)) }}"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                        required>
                </div>

            </div>
            <div class="col-span-2">
                <label for="note-{{ $customer->id }}"
                    class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Note (Opsional)</label>
                <textarea name="note" id="note-{{ $customer->id }}" rows="3"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">{{ old('note', $customer->note) }}</textarea>
            </div>
        </div>
        <button type="submit"
            class="text-white inline-flex items-center bg-green-600 hover:bg-green-700 focus:ring-4 focus:outline-none focus:ring-green-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-green-500 dark:hover:bg-green-600 dark:focus:ring-green-800">
            <i class="fas fa-save me-1"></i>
            Simpan Perubahan
        </button>
    </form>
</x-modal>
