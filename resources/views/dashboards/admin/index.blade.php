<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Admin Dashboard - Region {{ $admin->region }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                <h3 class="text-lg font-medium">Monitoring Kurir di Region {{ $admin->region }}</h3>
                <ul class="mt-4 list-disc list-inside">
                    @forelse($couriers as $kurir)
                        <li>{{ $kurir->name }} ({{ $kurir->email }})</li>
                    @empty
                        <li>Tidak ada data kurir di region ini.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>
