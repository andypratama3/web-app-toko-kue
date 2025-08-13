@extends('layouts.argon')
@section('title', 'Manajemen Produk')
@section('page_title', 'Produk')

@section('content')
    {{-- Kontainer utama dengan padding untuk memberikan ruang --}}
    <div class="relative bg-white shadow-md dark:bg-gray-800 sm:rounded-lg p-6">
        @forelse ($categories as $category)
            <div class="mb-10">
                {{-- Judul kategori dengan garis bawah untuk pemisah visual --}}
                <h2 class="mb-4 pb-4 text-2xl font-bold text-gray-800 dark:text-white border-b border-gray-200 dark:border-gray-700">{{ $category->name }}</h2>

                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    @forelse ($category->products as $product)
                        {{-- Menggunakan flexbox untuk perataan vertikal di dalam kartu --}}
                        <div class="flex flex-col overflow-hidden transition-all duration-300 bg-white shadow-lg rounded-2xl hover:shadow-xl hover:-translate-y-1 dark:bg-gray-700">
                            <div class="relative">
                                {{-- Memperbaiki path gambar agar sesuai dengan storage link --}}
                                <img src="{{ asset($product->image_path) }}" alt="{{ $product->name }}" loading="lazy" class="object-cover w-full h-48">
                                @if ($product->tag)
                                    <div class="absolute top-3 right-3 bg-orange-500 text-white px-2 py-1 rounded-full text-xs font-medium">
                                        {{ $product->tag }}
                                    </div>
                                @endif
                            </div>
                            {{-- Konten kartu dibuat flex-grow agar varian harga selalu di bawah --}}
                            <div class="flex flex-col flex-grow p-5">
                                <h3 class="font-bold text-lg text-[#2C3E50] dark:text-white mb-2">{{ $product->name }}</h3>

                                {{-- Deskripsi dibuat flex-grow agar mendorong varian ke bawah --}}
                                <div x-data="{ open: false }" class="flex-grow">
                                    <p class="mb-3 text-sm text-gray-600 dark:text-gray-300" :class="open ? '' : 'line-clamp-3'">
                                        {{ $product->description }}</p>
                                    @if (strlen($product->description) > 120)
                                        <button @click="open = !open"
                                            class="mb-2 text-xs font-semibold text-blue-600 focus:outline-none hover:underline">
                                            <span x-show="!open">Selengkapnya</span>
                                            <span x-show="open">Tutup</span>
                                        </button>
                                    @endif
                                </div>

                                <div class="mt-4">
                                    <h4 class="text-sm font-semibold text-gray-500 dark:text-gray-400">Varian Harga:</h4>
                                    <ul class="mt-1 space-y-1 text-sm text-gray-700 dark:text-gray-200">
                                        @foreach ($product->variants as $variant)
                                            <li class="flex justify-between">
                                                <span>{{ $variant->name }}</span>
                                                <span class="font-semibold text-[#8BA870]">Rp
                                                    {{ number_format($variant->price, 0, ',', '.') }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="col-span-full text-gray-500 dark:text-gray-300">Tidak ada produk dalam kategori ini.</p>
                    @endforelse
                </div>
            </div>
        @empty
            <div class="p-6 text-center text-gray-500 col-span-full dark:text-gray-400">
                <p>Belum ada kategori atau produk yang ditambahkan.</p>
            </div>
        @endforelse
    </div>
@endsection
