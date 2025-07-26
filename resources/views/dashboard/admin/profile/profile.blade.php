@extends('layouts.argon')
@section('title', 'Profile Saya')
@section('page_title', 'Profile')
@section('content')
<div>
    <div class="p-0">
        <h2 class="text-2xl font-bold text-greenlight mb-6">Pengaturan Akun</h2>
        <div class="space-y-10">
            @if (Laravel\Fortify\Features::canUpdateProfileInformation())
                <div class="bg-gray-50 rounded-lg p-6 shadow-sm">
                    <h3 class="text-lg font-semibold mb-4 text-slate-700">Informasi Profil</h3>
                    @livewire('profile.update-profile-information-form')
                </div>
            @endif

            @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::updatePasswords()))
                <div class="bg-gray-50 rounded-lg p-6 shadow-sm">
                    <h3 class="text-lg font-semibold mb-4 text-slate-700">Ubah Password</h3>
                    @livewire('profile.update-password-form')
                </div>
            @endif

            @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
                <div class="bg-gray-50 rounded-lg p-6 shadow-sm">
                    <h3 class="text-lg font-semibold mb-4 text-slate-700">Keamanan 2 Faktor</h3>
                    @livewire('profile.two-factor-authentication-form')
                </div>
            @endif

            <div class="bg-gray-50 rounded-lg p-6 shadow-sm">
                <h3 class="text-lg font-semibold mb-4 text-slate-700">Logout dari Sesi Lain</h3>
                @livewire('profile.logout-other-browser-sessions-form')
            </div>

            @if (Laravel\Jetstream\Jetstream::hasAccountDeletionFeatures())
                <div class="bg-red-50 rounded-lg p-6 shadow-sm">
                    <h3 class="text-lg font-semibold mb-4 text-red-600">Hapus Akun</h3>
                    @livewire('profile.delete-user-form')
                </div>
            @endif
        </div>
    </div>
</div>
@endsection