@extends('layouts.argon')
@section('title', 'Dashboard Admin')

@section('content')
  <div class="row">
    <div class="col-12">
      <div class="mb-4 card">
        <div class="pb-0 card-header">
          <h6>Monitoring Kurir di Region {{ Auth::user()->region }}</h6>
        </div>
        <div class="px-0 pt-0 pb-2 card-body">
          <div class="p-0 table-responsive">
            <table class="table mb-0 align-items-center">
              <thead>
                <tr>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Nama Kurir</th>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Email</th>
                </tr>
              </thead>
              <tbody>
                @forelse($couriers as $kurir)
                <tr>
                  <td>
                    <div class="px-2 py-1 d-flex">
                      <div class="d-flex flex-column justify-content-center">
                        <h6 class="mb-0 text-sm">{{ $kurir->name }}</h6>
                      </div>
                    </div>
                  </td>
                  <td>
                    <p class="mb-0 text-xs font-weight-bold">{{ $kurir->email }}</p>
                  </td>
                </tr>
                @empty
                <tr>
                  <td colspan="2" class="py-4 text-center">Tidak ada data kurir di region ini.</td>
                </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>


  {{-- Dynamic Product Section --}}
    {{-- <div class="max-w-screen-xl px-4 mx-auto 2xl:px-0"> --}}
      <div class="items-end justify-between mb-4 space-y-4 sm:flex sm:space-y-0 md:mb-8">
        <div>
          <h2 class="mt-3 text-xl font-semibold text-gray-900 dark:text-white sm:text-2xl">Manajemen Produk</h2>
        </div>
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
        <div class="p-6 bg-white border border-gray-200 shadow-sm rounded-2xl dark:border-gray-700 dark:bg-gray-800">
          <div class="w-full h-56">
            <a href="#">
              <img class="h-full mx-auto" src="{{ asset('public/image/' . $product->image) }}" alt="{{ $product->name }}" />
            </a>
          </div>
          <div class="pt-6">
            <span class="text-lg font-semibold leading-tight text-gray-900 dark:text-white">{{ $product->name }}</span>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ Str::limit($product->description, 100) }}</p>
            <div class="flex items-center justify-between gap-4 mt-4">
              <p class="text-2xl font-extrabold leading-tight text-gray-900 dark:text-white">Rp{{ number_format($product->price, 0, ',', '.') }}</p>
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
