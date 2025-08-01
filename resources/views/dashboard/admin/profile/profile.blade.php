@extends('layouts.argon')
@section('title', 'Profile Saya')
@section('page_title', 'Profile')
@section('content')
<div>
    <div class="p-0">
        <div class="space-y-10">
            @if (Laravel\Fortify\Features::canUpdateProfileInformation())
                <div class="p-6 rounded-lg shadow-sm bg-gray-50">
                    <h3 class="mb-4 text-lg font-semibold text-slate-700">Informasi Profil</h3>
                    @livewire('profile.update-profile-information-form')
                </div>
            @endif

            @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::updatePasswords()))
                <div class="p-6 rounded-lg shadow-sm bg-gray-50">
                    <h3 class="mb-4 text-lg font-semibold text-slate-700">Ubah Password</h3>
                    @livewire('profile.update-password-form')
                </div>
            @endif

            @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
                <div class="p-6 rounded-lg shadow-sm bg-gray-50">
                    <h3 class="mb-4 text-lg font-semibold text-slate-700">Keamanan 2 Faktor</h3>
                    @livewire('profile.two-factor-authentication-form')
                </div>
            @endif

            <div class="p-6 rounded-lg shadow-sm bg-gray-50">
                <h3 class="mb-4 text-lg font-semibold text-slate-700">Logout dari Sesi Lain</h3>
                @livewire('profile.logout-other-browser-sessions-form')
            </div>

            @if (Laravel\Jetstream\Jetstream::hasAccountDeletionFeatures())
                <div class="p-6 rounded-lg shadow-sm bg-red-50">
                    <h3 class="mb-4 text-lg font-semibold text-red-600">Hapus Akun</h3>
                    @livewire('profile.delete-user-form')
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
