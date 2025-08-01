@extends('layouts.argon')
@section('title', 'Edit Kurir')
@section('page_title', 'Edit Kurir')

@section('breadcrumbs')
    <li class="text-sm pl-2 capitalize leading-normal text-white before:float-left before:pr-2 before:text-white before:content-['/']">
        <a href="{{ route('admin.couriers.index') }}" class="text-white opacity-50">Manajemen Kurir</a>
    </li>
    <li class="text-sm pl-2 capitalize leading-normal text-white before:float-left before:pr-2 before:text-white before:content-['/']" aria-current="page">
        Edit
    </li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="mb-0">Form Edit Data Kurir</h3>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.couriers.update', $courier->id) }}" method="POST">
            @csrf
            @method('PATCH')
            <div class="form-group">
                <label for="name" class="form-control-label">Nama Lengkap</label>
                <input class="form-control @error('name') is-invalid @enderror" type="text" id="name" name="name" value="{{ old('name', $courier->name) }}" required>
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label for="email" class="form-control-label">Alamat Email</label>
                <input class="form-control @error('email') is-invalid @enderror" type="email" id="email" name="email" value="{{ old('email', $courier->email) }}" required>
                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <hr class="my-4">
            <p class="text-muted">Kosongkan password jika tidak ingin mengubahnya.</p>
            <div class="form-group">
                <label for="password" class="form-control-label">Password Baru</label>
                <input class="form-control @error('password') is-invalid @enderror" type="password" id="password" name="password">
                @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label for="password_confirmation" class="form-control-label">Konfirmasi Password Baru</label>
                <input class="form-control" type="password" id="password_confirmation" name="password_confirmation">
            </div>
            <div class="text-right">
                <a href="{{ route('admin.couriers.index') }}" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">Update Data</button>
            </div>
        </form>
    </div>
</div>
@endsection
