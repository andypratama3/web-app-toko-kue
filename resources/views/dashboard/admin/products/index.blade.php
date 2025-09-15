@extends('layouts.argon')
@section('title', 'Manajemen Produk')
@section('page_title', 'Produk')

@section('content')
    <div class="relative p-6 bg-white shadow-md dark:bg-gray-800 sm:rounded-lg">
        {{-- Tombol Tambah Produk Baru --}}
        <div class="flex items-center justify-between pb-4 mb-4 border-b">
            <div>
                <h2 class="text-xl font-semibold text-gray-800 dark:text-white">Daftar Produk</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">Kelola produk dan varian untuk region
                    {{ $regionName }}.</p>
            </div>
            {{-- ========================= PERBAIKAN TOMBOL TAMBAH ========================= --}}
            <button type="button" data-target-modal="create-product-modal"
                class="flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-blue-700 rounded-lg js-open-modal-btn hover:bg-blue-800 focus:ring-4 focus:ring-blue-300">
                <i class="mr-2 fas fa-plus"></i>
                Tambah Produk
            </button>
        </div>

        {{-- Daftar Produk Berdasarkan Kategori --}}
        @forelse ($categories as $category)
            @if ($category->products->isNotEmpty())
                <div class="mb-10">
                    <h3 class="pb-4 mb-4 text-2xl font-bold text-gray-800 border-b dark:text-white dark:border-gray-700">
                        {{ $category->name }}</h3>
                    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        @foreach ($category->products as $product)
                            <div
                                class="flex flex-col overflow-hidden transition-all duration-300 bg-white border border-gray-200 shadow-lg rounded-2xl hover:shadow-xl hover:-translate-y-1 dark:bg-gray-700 dark:border-gray-600">
                                <div class="relative">
                                    <img src="{{ asset($product->image_path) }}" alt="{{ $product->name }}" loading="lazy"
                                        class="object-cover w-full h-48">
                                    @if (!$product->is_active)
                                        <div
                                            class="absolute top-0 left-0 flex items-center justify-center w-full h-full bg-black bg-opacity-50">
                                            <span
                                                class="px-3 py-1 text-sm font-bold text-white bg-gray-700 rounded-full">NONAKTIF</span>
                                        </div>
                                    @endif
                                    @if ($product->tag)
                                        <div class="absolute px-2 py-1 text-xs ... top-3 right-3">{{ $product->tag }}</div>
                                    @endif
                                </div>
                                <div class="flex flex-col flex-grow p-5">
                                    <h4 class="mb-2 text-lg font-bold text-[#2C3E50] dark:text-white">{{ $product->name }}
                                    </h4>
                                    <div x-data="{ open: false }" class="flex-grow">
                                        <p class="mb-3 text-sm text-gray-600 dark:text-gray-300"
                                            :class="open ? '' : 'line-clamp-3'">{{ $product->description }}</p>
                                        @if (strlen($product->description) > 100)
                                            <button @click="open = !open"
                                                class="mb-2 text-xs font-semibold text-blue-600 focus:outline-none hover:underline">
                                                <span x-show="!open">Selengkapnya</span><span x-show="open">Tutup</span>
                                            </button>
                                        @endif
                                    </div>
                                    <div class="mt-4">
                                        <h5 class="text-sm font-semibold text-gray-500 dark:text-gray-400">Varian Harga:
                                        </h5>
                                        <ul class="mt-1 space-y-1 text-sm text-gray-700 dark:text-gray-200">
                                            @forelse ($product->variants->where('is_active', true) as $variant)
                                                <li class="flex justify-between"><span>{{ $variant->name }}</span><span
                                                        class="font-semibold text-[#8BA870]">Rp
                                                        {{ number_format($variant->price, 0, ',', '.') }}</span></li>
                                            @empty
                                                <li class="text-gray-400">Belum ada varian.</li>
                                            @endforelse
                                        </ul>
                                    </div>
                                    {{-- ========================= PERBAIKAN TOMBOL AKSI ========================= --}}
                                    <div
                                        class="flex items-center justify-end pt-4 mt-4 space-x-2 border-t border-gray-200 dark:border-gray-600">
                                        <button type="button" data-target-modal="edit-product-modal-{{ $product->id }}"
                                            class="js-open-modal-btn px-3 py-1.5 text-xs font-medium text-white bg-yellow-500 rounded-md hover:bg-yellow-600">Edit</button>
                                        <button type="button" data-target-modal="delete-product-modal-{{ $product->id }}"
                                            class="js-open-modal-btn px-3 py-1.5 text-xs font-medium text-white bg-red-600 rounded-md hover:bg-red-700">Hapus</button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @empty
            <div class="p-6 text-center text-gray-500 col-span-full dark:text-gray-400">
                <p>Belum ada kategori atau produk yang ditambahkan di region Anda.</p>
            </div>
        @endforelse
    </div>
@endsection

@push('flowbite-modals')
    @include('dashboard.admin.products.create', ['categories' => $all_categories])
    @foreach ($categories as $category)
        @foreach ($category->products as $product)
            @include('dashboard.admin.products.edit', [
                'product' => $product,
                'categories' => $all_categories,
            ])
            @include('dashboard.admin.products.delete', ['product' => $product])
        @endforeach
    @endforeach
@endpush
