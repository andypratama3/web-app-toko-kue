@extends('layouts.argon')
@section('title', 'Zona Pengiriman')
@section('page_title', 'Zona Pengiriman')

@section('content')
<div class="relative min-h-[715px] bg-white shadow-md dark:bg-gray-800 sm:rounded-lg">
    <div class="p-4 flex justify-between items-center">
        <p class="text-sm text-gray-600 dark:text-gray-400">Kelola tarif ongkir per zona per cabang.</p>
        <a href="{{ route('admin.delivery-zones.create') }}" class="text-white bg-green-600 hover:bg-green-700 px-4 py-2 rounded-lg text-sm">Tambah Zona</a>
    </div>
    <x-admin.responsive-table min-height="540">
        <table class="admin-table w-full text-sm text-left text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th class="px-4 py-3">Cabang</th>
                    <th class="px-4 py-3">Nama Area</th>
                    <th class="px-4 py-3">Keyword/Landmark</th>
                    <th class="px-4 py-3">Jarak (km)</th>
                    <th class="px-4 py-3">Ongkir</th>
                    <th class="px-4 py-3">Aktif</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($zones as $zone)
                <tr class="border-b dark:border-gray-700">
                    <td class="px-4 py-3">{{ $zone->region->name ?? '-' }}</td>
                    <td class="px-4 py-3">{{ $zone->area_name }}</td>
                    <td class="px-4 py-3">{{ $zone->landmark_keyword }}</td>
                    <td class="px-4 py-3">{{ $zone->distance_km }}</td>
                    <td class="px-4 py-3">Rp {{ number_format($zone->ongkir,0,',','.') }}</td>
                    <td class="px-4 py-3">{{ $zone->is_active ? 'Ya' : 'Tidak' }}</td>
                    <td class="px-4 py-3 text-right space-x-2">
                        <a href="{{ route('admin.delivery-zones.edit', $zone) }}" class="text-blue-600">Edit</a>
                        <form action="{{ route('admin.delivery-zones.destroy', $zone) }}" method="POST" class="inline" onsubmit="return confirm('Hapus zona ini?');">
                            @csrf @method('DELETE')
                            <button class="text-red-600">Hapus</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </x-admin.responsive-table>
</div>
@endsection
