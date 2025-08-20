@forelse ($customers as $customer)
    <tr
        class="bg-white border-b border-gray-200 dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
        <td class="px-4 py-3 font-medium text-center text-gray-900 dark:text-white">
            {{ ($customers->currentPage() - 1) * $customers->perPage() + $loop->iteration }}
        </td>
        <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
            {{ $customer->name }}
        </th>
        <td class="px-6 py-4">{{ Str::limit($customer->address, 30) }}</td>
        <td class="px-6 py-4">
            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $customer->phone) }}" target="_blank"
                class="flex items-center space-x-2 text-green-600 hover:text-green-700">
                <i class="fab fa-whatsapp"></i>
                <span>{{ $customer->phone }}</span>
            </a>
        </td>
        <td class="px-6 py-4">{{ $customer->region->name }}</td>
        <td class="px-6 py-4">{{ Str::limit($customer->note, 20) }}</td>
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
                            <button type="button" data-modal-target="edit-modal-{{ $customer->id }}"
                                data-modal-toggle="edit-modal-{{ $customer->id }}"
                                class="flex items-center w-full px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-600">
                                <span class="inline-block w-6 mr-2 text-center"><i class="fas fa-edit"></i></span>
                                <span>Edit</span>
                            </button>
                        </li>
                        <li>
                            <button type="button" data-modal-target="note-modal-{{ $customer->id }}"
                                data-modal-toggle="note-modal-{{ $customer->id }}"
                                class="flex items-center w-full px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-600">
                                <span class="inline-block w-6 mr-2 text-center"><i
                                        class="fas fa-sticky-note"></i></span>
                                <span>Note</span>
                            </button>
                        </li>
                    </ul>
                    <div class="py-1">
                        <button type="button" data-modal-target="delete-modal-{{ $customer->id }}"
                            data-modal-toggle="delete-modal-{{ $customer->id }}"
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
        <td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
            Tidak ada data customer ditemukan.
        </td>
    </tr>
@endforelse
