@extends('layouts.argon')
@section('title', 'Detail Produk')
@section('page_title', 'Detail Produk')

@section('content')
    <div class="p-4 bg-white border border-gray-200 rounded-xl dark:bg-gray-800 dark:border-gray-700">
        <h3 class="mb-0 text-xl font-semibold text-gray-900 dark:text-white">{{ $product->name }}</h3>

        <div class="grid grid-cols-1 gap-6 mt-6 md:grid-cols-4">
            <div class="md:col-span-1">
                <img src="{{ Storage::url($product->image_path) }}" alt="{{ $product->name }}"
                    class="w-full rounded-lg shadow-sm">
            </div>

            <div class="md:col-span-3">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Deskripsi</h3>
                <p class="text-gray-700 dark:text-gray-300">{{ $product->description }}</p>

                <hr class="my-4 border-gray-200 dark:border-gray-700">

                <h4 class="font-semibold text-gray-900 dark:text-white">Harga</h4>
                <p class="text-2xl font-bold text-green-600 dark:text-green-400">
                    Rp{{ number_format($product->price, 0, ',', '.') }}
                </p>

                <hr class="my-4 border-gray-200 dark:border-gray-700">

                <div class="flex justify-start">
                    <a href="{{ route('admin.dashboard') }}"
                        class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white rounded-lg bg-greenlight hover:bg-greendark focus:outline-none focus:ring-4 focus:ring-greenlight/30">
                        Kembali ke Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
