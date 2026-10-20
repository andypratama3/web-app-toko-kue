@extends('layouts.argon')
@section('title', 'Edit Zona Pengiriman')
@section('page_title', 'Edit Zona Pengiriman')

@section('content')
<div class="max-w-2xl mx-auto bg-white shadow-md dark:bg-gray-800 sm:rounded-lg p-6">
    <form method="POST" action="{{ route('admin.delivery-zones.update', $deliveryZone) }}">
        @csrf @method('PUT')
        <div class="mb-4">
            <label class="block mb-2 text-sm font-medium">Cabang</label>
            <select name="region_id" class="w-full border rounded p-2.5" required>
                @foreach($regions as $region)
                    <option value="{{ $region->id }}" {{ $deliveryZone->region_id == $region->id ? 'selected' : '' }}>{{ $region->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-4">
            <label class="block mb-2 text-sm font-medium">Nama Area</label>
            <input name="area_name" value="{{ $deliveryZone->area_name }}" class="w-full border rounded p-2.5" required>
        </div>
        <div class="mb-4">
            <label class="block mb-2 text-sm font-medium">Keyword/Landmark (pisahkan koma)</label>
            <input name="landmark_keyword" value="{{ $deliveryZone->landmark_keyword }}" class="w-full border rounded p-2.5">
        </div>
        <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block mb-2 text-sm font-medium">Jarak (km)</label>
                <input type="number" step="0.01" name="distance_km" value="{{ $deliveryZone->distance_km }}" class="w-full border rounded p-2.5" required>
            </div>
            <div>
                <label class="block mb-2 text-sm font-medium">Ongkir (Rp)</label>
                <input type="number" name="ongkir" value="{{ $deliveryZone->ongkir }}" class="w-full border rounded p-2.5" required>
            </div>
        </div>
        <div class="mb-4">
            <label class="block mb-2 text-sm font-medium">Catatan</label>
            <input name="notes" value="{{ $deliveryZone->notes }}" class="w-full border rounded p-2.5">
        </div>
        <div class="mb-4 flex items-center">
            <input type="checkbox" name="is_active" value="1" {{ $deliveryZone->is_active ? 'checked' : '' }} class="mr-2">
            <label class="text-sm font-medium">Aktif</label>
        </div>
        <div class="flex gap-2">
            <button class="text-white bg-green-600 px-5 py-2.5 rounded-lg">Simpan</button>
            <a href="{{ route('admin.delivery-zones.index') }}" class="bg-gray-100 px-5 py-2.5 rounded-lg">Batal</a>
        </div>
    </form>
</div>
@endsection
