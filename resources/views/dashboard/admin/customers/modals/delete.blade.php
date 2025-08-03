@props(['customer'])

{{-- Modal Hapus Customer --}}
<div id="delete-customer-modal-{{ $customer->id }}" tabindex="-1" class="fixed top-0 left-0 right-0 z-50 items-center justify-center hidden w-full h-full overflow-x-hidden overflow-y-auto md:inset-0 bg-gray-900/50">
    <div class="relative w-full h-auto max-w-md p-4">
        <div class="relative bg-white rounded-lg shadow dark:bg-gray-700">
            <button type="button" class="absolute top-3 right-2.5 text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ml-auto inline-flex justify-center items-center" data-modal-hide="delete-customer-modal-{{ $customer->id }}">
                <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/></svg>
                <span class="sr-only">Close modal</span>
            </button>
            <div class="p-6 text-center">
                <svg class="w-12 h-12 mx-auto mb-4 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 11V6m0 8h.01M19 10a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                </svg>
                <h3 class="mb-5 text-lg font-normal text-gray-500 dark:text-gray-400">Anda yakin ingin menghapus customer <br><span class="font-bold">{{ $customer->name }}</span>?</h3>
                <form action="{{ route('admin.customers.destroy', $customer->id) }}" method="POST" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-white bg-red-600 hover:bg-red-800 focus:ring-4 focus:outline-none focus:ring-red-300 font-medium rounded-lg text-sm inline-flex items-center px-5 py-2.5 text-center">
                        Ya, saya yakin
                    </button>
                </form>
                <button data-modal-hide="delete-customer-modal-{{ $customer->id }}" type="button" class="py-2.5 px-5 ml-3 text-sm font-medium text-gray-900 focus:outline-none bg-white rounded-lg border border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-4 focus:ring-gray-200">
                    Tidak, batal
                </button>
            </div>
        </div>
    </div>
</div>
