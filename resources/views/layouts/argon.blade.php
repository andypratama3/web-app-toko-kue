<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>@yield('title', 'Dashboard')</title>
    @include('layouts.headicon')
    {{-- Vite build assets --}}
    @vite(['resources/css/app.css', 'resources/css/argon-dashboard-tailwind.css', 'resources/js/app.js', 'resources/js/argon-dashboard-tailwind.js'])

    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" rel="stylesheet" />
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>
    {{-- <link href="resources/css/nucleo-icons.css" rel="stylesheet" />
    <link href="resources/css/nucleo-svg.css" rel="stylesheet" /> --}}
    <script src="https://unpkg.com/@popperjs/core@2"></script>

    {{-- Script for Sidenav --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var aside = document.querySelector('aside');
            var overlay = document.getElementById('sidebar-overlay');
            // Toggle open
            document.querySelectorAll('[sidenav-trigger]').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    aside.classList.remove('-translate-x-full');
                    aside.classList.add('translate-x-0');
                    if (overlay) {
                        overlay.classList.remove('hidden');
                        overlay.classList.add('block');
                    }
                });
            });
            // Toggle close
            document.querySelectorAll('[sidenav-close]').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    aside.classList.add('-translate-x-full');
                    aside.classList.remove('translate-x-0');
                    if (overlay) {
                        overlay.classList.add('hidden');
                        overlay.classList.remove('block');
                    }
                });
            });
            // Click overlay to close
            if (overlay) {
                overlay.addEventListener('click', function() {
                    aside.classList.add('-translate-x-full');
                    aside.classList.remove('translate-x-0');
                    overlay.classList.add('hidden');
                    overlay.classList.remove('block');
                });
            }
        });
    </script>
</head>

<body class="m-0 font-sans text-base antialiased font-normal dark:bg-slate-900 leading-default bg-gray-50 text-slate-500">
    {{-- Top color bar --}}
    <div class="absolute w-full bg-greenlight dark:hidden min-h-75"></div>

    {{-- Sidebar Overlay --}}
    <div id="sidebar-overlay" class="fixed inset-0 z-40 hidden transition-opacity duration-200 bg-black bg-opacity-40"></div>

    @include('layouts.partials.sidenav')

    <main class="relative h-full max-h-screen transition-all duration-200 ease-in-out xl:ml-68 rounded-xl">
        @include('layouts.partials.navbar')

        @include('layouts.partials.content')
        @include('layouts.partials.footer')
    </main>
</body>

</html>
