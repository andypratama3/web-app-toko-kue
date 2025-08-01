@extends('layouts.argon')
@section('title', 'Tambah Kurir')
@section('page_title', 'Tambah Kurir')

@section('breadcrumbs')
    <li class="text-sm pl-2 capitalize leading-normal text-white before:float-left before:pr-2 before:text-white before:content-['/']">
        <a href="{{ route('admin.couriers.index') }}" class="text-white opacity-50">Manajemen Kurir</a>
    </li>
    <li class="text-sm pl-2 capitalize leading-normal text-white before:float-left before:pr-2 before:text-white before:content-['/']" aria-current="page">
        Tambah
    </li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="mb-0">Form Tambah Kurir Baru</h3>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.couriers.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="name" class="form-control-label">Nama Lengkap</label>
                <input class="form-control @error('name') is-invalid @enderror" type="text" id="name" name="name" value="{{ old('name') }}" required>
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label for="email" class="form-control-label">Alamat Email</label>
                <input class="form-control @error('email') is-invalid @enderror" type="email" id="email" name="email" value="{{ old('email') }}" required>
                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label for="password" class="form-control-label">Password</label>
                <input class="form-control @error('password') is-invalid @enderror" type="password" id="password" name="password" required>
                @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label for="password_confirmation" class="form-control-label">Konfirmasi Password</label>
                <input class="form-control" type="password" id="password_confirmation" name="password_confirmation" required>
            </div>
            <div class="text-right">
                <a href="{{ route('admin.couriers.index') }}" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan Kurir</button>
            </div>
        </form>
    </div>
</div>
@endsection
