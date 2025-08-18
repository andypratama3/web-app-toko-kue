@forelse ($couriers as $courier)
    <tr class="border-b dark:border-gray-700">
        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white text-center">
            {{ ($couriers->currentPage() - 1) * $couriers->perPage() + $loop->iteration }}
        </td>
        <th scope="row" class="px-4 py-3 font-medium text-gray-900 whitespace-nowrap dark:text-white">
            {{ $courier->name }}</th>
        <td class="px-4 py-3 text-center">{{ $courier->email }}</td>
        <td class="px-4 py-3 text-center">{{ $courier->region->name }}</td>
        <td class="px-4 py-3 text-center">{{ Str::limit($courier->note, 20) }}</td>
        <td class="px-4 py-3 text-center">{{ $courier->created_at->format('d M Y') }}</td>
        <td class="px-4 py-3 text-right">
            <div class="relative inline-block">
                <button data-dropdown-toggle="courier-actions-dropdown-{{ $courier->id }}"
                    class="px-2 py-1 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:ring-2 focus:ring-gray-100 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700">
                    <i class="fas fa-ellipsis-v"></i>
                </button>
                <div id="courier-actions-dropdown-{{ $courier->id }}"
                    class="z-50 hidden bg-white divide-y divide-gray-100 rounded shadow w-44 dark:bg-gray-700 dark:divide-gray-600">
                    <ul class="py-1 text-sm text-gray-700 dark:text-gray-200"
                        aria-labelledby="courier-actions-button-{{ $courier->id }}">
                        <li>
                            <button type="button" data-modal-target="edit-courier-modal-{{ $courier->id }}"
                                data-modal-toggle="edit-courier-modal-{{ $courier->id }}"
                                class="flex items-center w-full px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-600">
                                <span class="inline-block w-6 mr-2 text-center"><i class="fas fa-edit"></i></span>
                                <span>Edit</span>
                            </button>
                        </li>
                        <li>
                            <button type="button" data-modal-target="note-courier-modal-{{ $courier->id }}"
                                data-modal-toggle="note-courier-modal-{{ $courier->id }}"
                                class="flex items-center w-full px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-600">
                                <span class="inline-block w-6 mr-2 text-center"><i
                                        class="fas fa-sticky-note"></i></span>
                                <span>Note</span>
                            </button>
                        </li>
                        <li>
                            <button type="button"
                                class="flex items-center w-full px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-600">
                                <span class="inline-block w-6 mr-2 text-center">
                                    <i class="fas fa-chart-line"></i></span>
                                <span>Performa</span>
                            </button>
                        </li>
                    </ul>
                    <div class="py-1">
                        <button type="button" data-modal-target="delete-courier-modal-{{ $courier->id }}"
                            data-modal-toggle="delete-courier-modal-{{ $courier->id }}"
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
    <tr class="border-b dark:border-gray-700">
        <td colspan="6" class="px-4 py-3 text-center text-gray-500">Belum ada data kurir di region
            ini.</td>
    </tr>
@endforelse
