{{-- resources/views/dashboard/admin/customers/modals/create.blade.php --}}

<x-modal id="create-customer-modal" title="Tambah Customer Baru" toggle="create-customer-modal" size="2xl">
    <form class="p-4 md:p-5" action="{{ route('admin.customers.store') }}" method="POST">
        @csrf
        <div class="grid grid-cols-2 gap-4 mb-4">
            <div class="col-span-2">
                <label for="create-name"
                    class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Nama</label>
                <input type="text" name="name" id="create-name"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                    placeholder="Masukkan nama customer" required>
            </div>
            <div class="col-span-2">
                <label for="create-address"
                    class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Alamat</label>
                <textarea name="address" id="create-address" rows="3"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                    placeholder="Masukkan alamat lengkap" required></textarea>
            </div>
            <div class="col-span-2">
                <label for="create-phone" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">No. HP</label>
                <div class="flex">
                    <span
                        class="inline-flex items-center px-3 text-sm text-gray-900 bg-gray-200 border border-r-0 border-gray-300 rounded-l-md dark:bg-gray-600 dark:text-gray-400 dark:border-gray-600">
                        +62
                    </span>
                    <input type="tel" name="phone" id="create-phone"
                        class="rounded-none rounded-r-lg bg-gray-50 border text-gray-900 focus:ring-blue-500 focus:border-blue-500 block flex-1 min-w-0 w-full text-sm border-gray-300 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                        placeholder="8123456789" required>
                </div>
            </div>
            <div class="col-span-2">
                <label for="create-note" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Note
                    (Opsional)</label>
                <textarea name="note" id="create-note" rows="3"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                    placeholder="Catatan tambahan..."></textarea>
            </div>
        </div>
        <button type="submit"
            class="text-white inline-flex items-center bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
            <i class="w-4 h-4 mr-2 fas fa-save"></i>
            Simpan Customer
        </button>
    </form>
</x-modal>
