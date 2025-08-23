@props(['product', 'categories'])

<x-modal-custom id="edit-product-modal-{{ $product->id }}" title="Edit Produk" toggle="edit-product-modal-{{ $product->id }}" size="4xl">
    <form action="{{ route('admin.products.update', $product->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="p-4 md:p-5">
            <div class="grid grid-cols-2 gap-4">
                {{-- Nama Produk --}}
                <div class="col-span-2">
                    <label for="name-{{ $product->id }}" class="block mb-2 text-sm font-medium text-gray-900">Nama Produk</label>
                    <input type="text" name="name" value="{{ old('name', $product->name) }}" id="name-{{ $product->id }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5" required>
                </div>

                {{-- Kategori --}}
                <div class="col-span-2 sm:col-span-1">
                    <label for="category-{{ $product->id }}" class="block mb-2 text-sm font-medium text-gray-900">Kategori</label>
                    <select id="category-{{ $product->id }}" name="category_id" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) == $category->id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Tag --}}
                <div class="col-span-2 sm:col-span-1">
                    <label for="tag-{{ $product->id }}" class="block mb-2 text-sm font-medium text-gray-900">Tag (Opsional)</label>
                    <input type="text" name="tag" value="{{ old('tag', $product->tag) }}" id="tag-{{ $product->id }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5" placeholder="Ala Carte">
                </div>

                {{-- Deskripsi --}}
                <div class="col-span-2">
                    <label for="description-{{ $product->id }}" class="block mb-2 text-sm font-medium text-gray-900">Deskripsi</label>
                    <textarea id="description-{{ $product->id }}" name="description" rows="4" class="block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300" required>{{ old('description', $product->description) }}</textarea>
                </div>

                {{-- Gambar --}}
                <div class="col-span-2">
                    <label class="block mb-2 text-sm font-medium text-gray-900">Gambar Produk</label>
                    <div class="flex items-center space-x-4">
                        <img src="{{ asset($product->image_path) }}" class="w-20 h-20 rounded-lg object-cover" alt="{{ $product->name }}">
                        <input type="file" name="image" class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none">
                    </div>
                    <p class="mt-1 text-xs text-gray-500">Kosongkan jika tidak ingin mengganti gambar.</p>
                </div>

                {{-- Status Aktif --}}
                <div class="col-span-2">
                    <label class="inline-flex items-center cursor-pointer">
                      <input type="checkbox" name="is_active" value="1" class="sr-only peer" @checked(old('is_active', $product->is_active))>
                      <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                      <span class="text-sm font-medium text-gray-900 ms-3 dark:text-gray-300">Produk Aktif</span>
                    </label>
                </div>

                {{-- Varian Produk --}}
                <div class="col-span-2">
                    <h4 class="mb-2 font-semibold">Varian Produk</h4>
                    <div id="edit-variants-container-{{ $product->id }}">
                        @if(old('variants')) {{-- Jika ada error validasi, gunakan data lama --}}
                             @foreach (old('variants') as $index => $variant)
                                <div class="flex items-center mb-2 space-x-2 variant-item">
                                    <input type="hidden" name="variants[{{ $index }}][id]" value="{{ $variant['id'] ?? '' }}">
                                    <input type="text" name="variants[{{ $index }}][name]" class="w-full p-2 text-sm border-gray-300 rounded-lg" placeholder="Nama Varian" value="{{ $variant['name'] }}" required>
                                    <input type="number" name="variants[{{ $index }}][price]" class="w-full p-2 text-sm border-gray-300 rounded-lg" placeholder="Harga" value="{{ $variant['price'] }}" required>
                                    <button type="button" class="px-3 py-2 text-white bg-red-600 rounded-lg remove-variant-btn">&times;</button>
                                </div>
                            @endforeach
                        @else {{-- Jika tidak ada error, gunakan data dari database --}}
                             @foreach ($product->variants->where('is_active', true) as $index => $variant)
                                <div class="flex items-center mb-2 space-x-2 variant-item">
                                    <input type="hidden" name="variants[{{ $variant->id }}][id]" value="{{ $variant->id }}">
                                    <input type="text" name="variants[{{ $variant->id }}][name]" class="w-full p-2 text-sm border-gray-300 rounded-lg" placeholder="Nama Varian" value="{{ $variant->name }}" required>
                                    <input type="number" name="variants[{{ $variant->id }}][price]" class="w-full p-2 text-sm border-gray-300 rounded-lg" placeholder="Harga" value="{{ $variant->price }}" required>
                                    <button type="button" class="px-3 py-2 text-white bg-red-600 rounded-lg remove-variant-btn">&times;</button>
                                </div>
                            @endforeach
                        @endif
                    </div>
                    <button type="button" class="text-white bg-blue-700 hover:bg-blue-800 font-medium rounded-lg text-sm px-5 py-2.5 add-edit-variant-btn" data-product-id="{{ $product->id }}">Tambah Varian</button>
                </div>
            </div>

            <div class="flex items-center justify-end pt-4 mt-4 border-t">
                <button type="submit" class="text-white bg-green-600 hover:bg-green-700 font-medium rounded-lg text-sm px-5 py-2.5 text-center">Simpan Perubahan</button>
            </div>
        </div>
    </form>
</x-modal-custom>

{{-- SCRIPT LENGKAP UNTUK MODAL EDIT --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const setupEditVariantLogic = () => {
            const modal = document.getElementById('edit-product-modal-{{ $product->id }}');
            if (!modal) return;

            const addBtn = modal.querySelector('.add-edit-variant-btn');
            const container = modal.querySelector('#edit-variants-container-{{ $product->id }}');

            if (!addBtn || !container) return;

            // Fungsi untuk menambah baris varian baru
            const addEditVariant = () => {
                const index = Date.now();
                const newVariantHtml = `
                    <div class="flex items-center mb-2 space-x-2 variant-item">
                        <input type="text" name="variants[${index}][name]" class="w-full p-2 text-sm border-gray-300 rounded-lg" placeholder="Nama Varian Baru" required>
                        <input type="number" name="variants[${index}][price]" class="w-full p-2 text-sm border-gray-300 rounded-lg" placeholder="Harga" required>
                        <button type="button" class="px-3 py-2 text-white bg-red-600 rounded-lg remove-variant-btn" title="Hapus Varian">&times;</button>
                    </div>
                `;
                container.insertAdjacentHTML('beforeend', newVariantHtml);
            };

            addBtn.addEventListener('click', addEditVariant);

            // Fungsi untuk menghapus baris varian (baik yang lama maupun yang baru)
            container.addEventListener('click', function(e) {
                if (e.target && e.target.classList.contains('remove-variant-btn')) {
                    e.target.closest('.variant-item').remove();
                }
            });
        };

        // Dijalankan setelah delay untuk memastikan modal siap
        setTimeout(setupEditVariantLogic, 100);
    });
</script>
