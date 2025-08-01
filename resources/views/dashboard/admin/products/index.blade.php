@extends('layouts.argon')
@section('title', 'Manajemen Produk')

@section('content')
    <div class="items-end justify-between mb-4 space-y-4 sm:flex sm:space-y-0 md:mb-8">
        {{-- <div>
            <h2 class="text-2xl font-bold text-greenlight mb-6">Manajemen Produk</h2>
        </div> --}}
        {{-- <div class="flex items-center space-x-4">
            <a href="{{ route('admin.products.create') }}" class="flex items-center justify-center w-full px-3 py-2 text-sm font-medium text-white bg-green-700 rounded-lg hover:bg-green-800 focus:ring-4 focus:ring-green-300 sm:w-auto dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-800">
                <svg class="w-5 h-5 mr-2 -ml-1" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd"></path></svg>
                Tambah Produk
            </a>
        </div> --}}
    </div>

    {{-- Product Grid --}}
    <div class="grid gap-4 mb-4 sm:grid-cols-2 md:mb-8 lg:grid-cols-3 xl:grid-cols-4">
        @forelse ($products as $product)
            <div
                class="overflow-hidden transition-all duration-300 bg-white shadow-lg rounded-2xl hover:shadow-xl hover:-translate-y-1">
                <div class="relative overflow-hidden">
                    <img src="{{ asset($product->image) }}" alt="{{ $product->name }}" loading="lazy"
                        class="object-cover w-full h-48 transition-transform duration-300 hover:scale-105 cursor-zoom-in zoomable">
                </div>
                <div class="p-5">
                    <h3 class="font-bold text-lg text-[#2C3E50] mb-2">{{ $product->name }}</h3>
                    {{-- Deskripsi dengan toggle --}}
                    <div x-data="{ open: false }">
                        <p class="mb-3 text-sm text-gray-600" :class="open ? '' : 'line-clamp-2'">
                            {{ $product->description }}
                        </p>
                        {{-- Tombol hanya muncul jika teksnya panjang --}}
                        @if (strlen($product->description) > 100)
                            {{-- Sesuaikan panjang karakter jika perlu --}}
                            <button @click="open = !open"
                                class="mb-2 text-xs font-semibold text-blue-600 focus:outline-none hover:underline">
                                <span x-show="!open">Selengkapnya</span>
                                <span x-show="open">Tutup</span>
                            </button>
                        @endif
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-[#8BA870] font-bold text-lg">Rp
                            {{ number_format($product->price, 0, ',', '.') }}</span>
                        <button
                            class="bg-[#8BA870] text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-[#7a965e] transition">
                            Detail
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="p-6 text-center text-gray-500 col-span-full dark:text-gray-400">
                <p>Belum ada produk yang ditambahkan.</p>
            </div>
        @endforelse
    </div>
    <div class="w-full text-center">
        {{ $products->links() }}
    </div>
    {{-- </div> --}}
@endsection
