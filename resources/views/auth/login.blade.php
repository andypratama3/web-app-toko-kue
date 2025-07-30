@section('title', 'Login')
@include('layouts.headicon')
<x-guest-layout>
    <div class="min-h-screen flex items-center justify-center relative overflow-hidden">
        {{-- Background --}}
        <img
            src="{{ asset('assets/homepage/bg-login.jpg') }}"
            alt="Background Login"
            class="absolute inset-0 w-full h-full object-cover" />
        <div class="absolute inset-0 bg-black/20"></div>

        {{-- Parent container: centered & limited width --}}
        <div
            class="relative z-10
             w-full
             max-w-md md:max-w-4xl
             mx-auto
             bg-white rounded-2xl shadow-lg
             flex overflow-hidden">
            {{-- KIRI: SIGN IN --}}
            <div class="w-full md:w-1/2 px-4 py-8 sm:px-8 sm:py-12 flex flex-col justify-center bg-white max-w-xs mx-auto md:mx-0 md:max-w-full">
                <x-slot name="logo">
                    <div class="flex justify-center mb-6">
                        <img src="{{ asset('logo.png') }}" alt="Logo" class="w-14 h-14">
                    </div>
                </x-slot>

                <h2 class="text-2xl font-bold text-greendark mb-2 text-center">Login Area</h2>
                <p class="text-gray-400 text-sm text-center mb-2">
                    Please use your email password for login!
                </p>
                <hr class="border-gray-200 mb-4">

                <x-validation-errors class="mb-4" />

                @if(session('status'))
                <div class="mb-4 font-medium text-sm text-green-600">
                    {{ session('status') }}
                </div>
                @endif

                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    {{-- email --}}
                    <div>
                        <x-label for="email" value="{{ __('Email') }}" class="text-greendark" />
                        <x-input
                            id="email"
                            class="block mt-1 w-full border-greendark rounded-full px-4 py-2 focus:ring-greenlight focus:border-greenlight"
                            type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
                    </div>
                    {{-- password --}}
                    <div class="mt-4">
                        <x-label for="password" value="{{ __('Password') }}" class="text-greendark" />
                        <x-input
                            id="password"
                            class="block mt-1 w-full border-greendark rounded-full px-4 py-2 focus:ring-greenlight focus:border-greenlight"
                            type="password" name="password" required autocomplete="current-password" />
                    </div>
                    {{-- remember --}}
                    <div class="block mt-4">
                        <label for="remember_me" class="flex items-center">
                            <x-checkbox id="remember_me" name="remember" class="text-greenlight focus:ring-greendark" />
                            <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
                        </label>
                    </div>
                    {{-- actions --}}
                    <div class="flex items-center justify-between mt-4">
                       <a href="/" class="text-green-600 hover:text-green-800 underline">
                        ← Back
                        </a>
                        <x-button class="bg-greenlight hover:bg-greendark text-white px-6 ml-4 rounded-full">
                            {{ __('Sign In') }}
                        </x-button>
                    </div>
                </form>
            </div>

            {{-- KANAN: greeting (hide on mobile) --}}
            <div class="hidden md:flex w-1/2 relative overflow-hidden rounded-tl-2xl rounded-bl-2xl">
                <img
                    src="{{ asset('assets/homepage/login-view.png') }}"
                    alt="Background"
                    class="absolute inset-0 w-full h-full object-cover z-0"
                    style="filter: brightness(0.55);" />
                <div class="absolute inset-0 bg-gradient-to-br from-greenlight/20 to-greendark/50 z-10"></div>

                <div class="relative z-20 flex flex-col items-center justify-center w-full h-full px-4 text-center">
                    <h2 class="text-white text-3xl font-bold mb-2 drop-shadow-lg">Selamat Datang!</h2>
                    <p class="text-white drop-shadow">
                        Sistem Order & Delivery <br>Kue Pandan Asli Malang<br><br>
                        <b>App_Version [1.0.0]</b>
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>