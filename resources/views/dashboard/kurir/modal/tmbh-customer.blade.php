{{-- Modal tambah Cust --}}
<div id="crud-modal" tabindex="-1" aria-hidden="true"
    class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 flex justify-center items-center w-full inset-0 h-full bg-black bg-opacity-50 transition-opacity duration-300">

    <div class="relative p-4 w-full max-w-md lg:max-w-2xl max-h-full transition-transform duration-300 ease-out transform scale-95">
        <div class="relative bg-white rounded-lg shadow dark:bg-gray-700">
            <!-- Modal header -->
            <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t dark:border-gray-600 border-gray-200 bg-gray">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Tambah Data Customer
                </h3>
                <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white" data-modal-toggle="crud-modal">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                    </svg>
                    <span class="sr-only">Close modal</span>
                </button>
            </div>
            <!-- Modal body -->
            <form class="p-4 md:p-5" action="{{ route('customer.store') }}" method="POST">
                @csrf
                <div class="grid gap-4 mb-4 grid-cols-2">
                    <div class="col-span-2">
                        <label for="nama" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Nama</label>
                        <input type="text" name="nama" id="nama" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" required="">
                    </div>
                    <div class="col-span-2 ">
                        <label for="alamat" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Alamat</label>
                        <textarea type="description" name="alamat" id="alamat" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" required=""></textarea>
                    </div>
                    <div class="col-span-2">
                        <label for="input-group-1" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">No. HP</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 start-0 flex items-center px-2 pointer-events-none">
                                +62
                            </div>
                            <input type="tel" name="nohp" id="nohp" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                        </div>
                    </div>
                    <div class="col-span-2">
                        <label for="region" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Region</label>
                        <select id="region" name="region" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                            <option selected disabled>-Pilih lokasi-</option>
                            <option value="sby">Surabaya</option>
                            <option value="mlg">Malang</option>
                            <option value="bali">Bali</option>
                        </select>                                   
                    </div>

                    <div class="col-span-2">
                        <label for="note" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Note</label>
                        <textarea id="note" name="note" rows="4" class="block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="No more than 2000 words"></textarea>
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-4">
                    <button type="submit" class="text-white inline-flex items-center bg-blue-700 hover:bg-green focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>