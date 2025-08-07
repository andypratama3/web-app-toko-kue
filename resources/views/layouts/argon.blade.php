<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    @section('title', 'Dashboard')
    @include('layouts.headicon')
    {{-- Vite build assets --}}
    @vite(['resources/css/app.css', 'resources/css/argon-dashboard-tailwind.css', 'resources/js/app.js', 'resources/js/custom.js','resources/js/argon-dashboard-tailwind.js', 'resources/js/sidenav-burger.js', 'resources/js/navbar-scroll-fix.js'])

    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" rel="stylesheet" />
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>
    {{-- <link href="resources/css/nucleo-icons.css" rel="stylesheet" />
    <link href="resources/css/nucleo-svg.css" rel="stylesheet" /> --}}
    <script src="https://unpkg.com/@popperjs/core@2"></script>

    {{--! Script untuk Alpine.js --}}
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    {{-- Script for Modal --}}
    <script src="https://unpkg.com/flowbite@1.6.5/dist/flowbite.min.js"></script>

    <!-- dropdown search -->
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <!-- Livewire Styles -->
    @livewireStyles
</head>

<body class="m-0 overflow-x-hidden font-sans text-base antialiased font-normal dark:bg-slate-900 leading-default bg-gray-50 text-slate-500">
    <!-- PRELOADER -->
    <div id="preloader" class="fixed top-0 left-0 w-full h-full bg-white flex justify-center items-center z-50">
        <div class="animate-spin rounded-full h-16 w-16 border-t-4 border-b-4 border-green-500"></div>
    </div>

    {{-- Top color bar --}}
    <div class="fixed w-full bg-greenlight dark:hidden min-h-75"></div>

    {{-- Sidebar Overlay --}}
    <div id="sidebar-overlay" class="fixed inset-0 z-30 hidden transition-opacity duration-200 bg-black bg-opacity-40"></div>

    @include('layouts.partials.sidenav')

    {{-- PERBAIKAN: Menambahkan id="main-content" --}}
    <main id="main-content" class="main-content relative h-full max-h-screen transition-all duration-200 ease-in-out rounded-xl">
        @include('layouts.partials.navbar')
        @include('layouts.partials.content')
        @include('layouts.partials.footer')
    </main>

    @stack('modals')

    <!-- Livewire Scripts -->
    @livewireScripts
</body>


</html>
