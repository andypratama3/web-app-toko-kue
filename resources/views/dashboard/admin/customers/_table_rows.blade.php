@forelse ($customers as $customer)
    <tr class="border-b dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700">
        <td class="px-4 py-3 font-medium text-center text-gray-900 dark:text-white">
            {{ ($customers->currentPage() - 1) * $customers->perPage() + $loop->iteration }}
        </td>
        {{-- <th scope="row" class="px-4 py-3 font-medium text-gray-900 whitespace-nowrap dark:text-white">
            {{ $customer->name }}</th> --}}
        <td class="px-4 py-3 font-medium text-gray-900 whitespace-nowrap dark:text-white">
            <div class="flex items-center justify-center space-x-2">
                <span>{{ $customer->name }}</span>

                {{-- UBAH DARI <form> MENJADI <button> --}}
                <button type="button" class="text-gray-400 hover:text-red-600 toggle-flag-btn"
                    data-url="{{ route('admin.customers.toggleFlag', $customer) }}"
                    title="{{ $customer->is_flagged ? 'Hilangkan Tanda' : 'Tandai Customer' }}">
                    {{-- Beri ID unik pada ikon untuk dimanipulasi oleh JS --}}
                    <i id="flag-icon-{{ $customer->id }}"
                        class="fas fa-flag {{ $customer->is_flagged ? 'text-red-500' : '' }}"></i>
                </button>
            </div>
        </td>
        <td class="px-4 py-3 text-center">{{ Str::limit($customer->address, 30) }}</td>
        <td class="px-4 py-3 text-center">{{ $customer->phone }}</td>
        <td class="px-4 py-3 text-center">{{ $customer->region->name ?? 'N/A' }}</td>
        <td class="px-4 py-3 text-center">{{ Str::limit($customer->note, 20) }}</td>
        <td class="px-4 py-3 text-center">{{ $customer->created_at->format('d M Y') }}</td>
        <td class="px-4 py-3 text-right">
            <div class="relative inline-block">
                <button data-dropdown-toggle="customer-actions-dropdown-{{ $customer->id }}"
                    class="px-2 py-1 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:ring-2 focus:ring-gray-100 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700">
                    <i class="fas fa-ellipsis-v"></i>
                </button>
                <div id="customer-actions-dropdown-{{ $customer->id }}"
                    class="z-50 hidden bg-white divide-y divide-gray-100 rounded shadow w-44 dark:bg-gray-700 dark:divide-gray-600">
                    <ul class="py-1 text-sm text-gray-700 dark:text-gray-200"
                        aria-labelledby="customer-actions-button-{{ $customer->id }}">
                        <li>
                            <button type="button" data-modal-target="edit-customer-modal-{{ $customer->id }}"
                                data-modal-toggle="edit-customer-modal-{{ $customer->id }}"
                                class="flex items-center w-full px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-600">
                                <span class="inline-block w-6 mr-2 text-center"><i class="fas fa-edit"></i></span>
                                <span>Edit</span>
                            </button>
                        </li>
                        <li>
                            <button type="button" data-modal-target="note-customer-modal-{{ $customer->id }}"
                                data-modal-toggle="note-customer-modal-{{ $customer->id }}"
                                class="flex items-center w-full px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-600">
                                <span class="inline-block w-6 mr-2 text-center"><i
                                        class="fas fa-sticky-note"></i></span>
                                <span>Note</span>
                            </button>
                        </li>
                    </ul>
                    <div class="py-1">
                        <button type="button" data-modal-target="delete-customer-modal-{{ $customer->id }}"
                            data-modal-toggle="delete-customer-modal-{{ $customer->id }}"
                            class="flex items-center w-full px-4 py-2 text-sm text-left text-red-600 hover:bg-gray-100 dark:hover:bg-gray-600 dark:text-red-500 dark:hover:text-white">
                            <span class="inline-block w-6 mr-2 text-center"><i class="fas fa-trash"></i></span>
                            <span>Delete</span>
                        </button>
                    </div>
                </div>
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="7" class="px-4 py-4 text-center text-gray-500">Tidak ada data customer.</td>
    </tr>
@endforelse
