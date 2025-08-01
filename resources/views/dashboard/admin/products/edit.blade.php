@extends('layouts.argon')
@section('title', 'Edit Produk')
@section('page_title', 'Edit Produk')

@section('content')
{{-- Fitur edit produk dinonaktifkan untuk admin. --}}
<div class="alert alert-warning mt-8 text-center">
    Fitur edit produk tidak tersedia. Semua admin hanya dapat melihat produk yang sudah ada.
</div>
@endsection
