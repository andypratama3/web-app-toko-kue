@extends('layouts.argon')
@section('title', 'Tambah Produk Baru')
@section('page_title', 'Tambah Produk')

@section('content')
{{-- Fitur tambah produk dinonaktifkan untuk admin. --}}
<div class="alert alert-warning mt-8 text-center">
    Fitur tambah produk tidak tersedia. Semua admin hanya dapat melihat produk yang sudah ada.
</div>
@endsection
