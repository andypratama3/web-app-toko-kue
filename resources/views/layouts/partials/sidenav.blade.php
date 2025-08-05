<aside id="sidebar"
    class="fixed inset-y-0 z-50 flex flex-col w-full p-0 my-4 overflow-y-hidden antialiased transition-all duration-200 -translate-x-full bg-white border-0 shadow-xl dark:shadow-none dark:bg-slate-850 max-w-64 ease-nav-brand xl:ml-6 rounded-2xl xl:left-0 xl:translate-x-0"
    aria-expanded="false">

    @auth
        @php
            $user = Auth::user();
            $regionName = ucwords(strtolower($user->region ?? ''));
            $dashboardUrl = url('/dashboard');

            if ($user->region) {
                $regionSlug = $user->region->slug;
                if ($user->hasRole('admin')) {
                    $dashboardUrl = route('admin.dashboard', ['region' => $regionSlug]);
                } elseif ($user->hasRole('kurir')) {
                    $dashboardUrl = route('kurir.dashboard', ['region' => $regionSlug]);
                }
            }
        @endphp

        {{-- Bagian Logo --}}
        <div class="flex-shrink-0 h-19">
            <i class="absolute top-0 right-0 p-4 opacity-50 cursor-pointer fas fa-times dark:text-white text-slate-400 xl:hidden"
                sidenav-close></i>
            <a id="sidebar-logo-link"
                class="flex items-center block px-8 py-6 m-0 text-base whitespace-nowrap dark:text-white text-slate-700"
                href="{{ $dashboardUrl }}">

                {{-- PERUBAHAN: Menambahkan id dan data-attributes untuk logo --}}
                <img id="sidebar-logo-img" src="{{ asset('assets/homepage/logo.png') }}"
                    data-logo-full="{{ asset('assets/homepage/logo.png') }}"
                    data-logo-icon="{{ asset('assets/homepage/logo.png') }}"
                    class="h-8 transition-all duration-200 ease-nav-brand" alt="main_logo" />

                <span id="sidebar-logo-text"
                    class="ml-2 text-xl font-semibold transition-all duration-200 ease-nav-brand text-greenlight sidenav-text">
                    Kue Pandan Asli
                </span>
            </a>
        </div>

        <hr
            class="flex-shrink-0 h-px mt-0 bg-transparent bg-gradient-to-r from-transparent via-black/40 to-transparent dark:bg-gradient-to-r dark:from-transparent dark:via-white dark:to-transparent" />

        {{-- Area menu yang bisa di-scroll --}}
        <div class="flex-grow w-full overflow-y-auto">
            <ul class="flex flex-col pl-0 mb-0">

                <li class="w-full mt-4 mb-4">
                    <h6 class="pl-6 ml-2 text-xs font-bold leading-tight uppercase sidenav-text dark:text-white opacity-60">
                        Dashboard Pages
                    </h6>
                </li>

                <li class="mt-0.5 w-full">
                    <a class="py-2.7 text-sm ease-nav-brand my-0 mx-2 flex items-center whitespace-nowrap rounded-lg px-4 font-semibold text-slate-700 transition-colors @if (request()->routeIs('admin.dashboard') || request()->routeIs('kurir.dashboard')) bg-blue-500/13 dark:text-white dark:opacity-80 @else dark:text-white dark:opacity-80 @endif"
                        href="{{ $dashboardUrl }}">
                        <div
                            class="mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-center text-center xl:p-2.5">
                            <i
                                class="fas fa-house-user @if (request()->routeIs('admin.dashboard') || request()->routeIs('kurir.dashboard')) text-blue-500 @else text-slate-400 @endif"></i>
                        </div>
                        <span class="ml-1 duration-300 opacity-100 pointer-events-none ease sidenav-text">Dashboard</span>
                    </a>
                </li>

                @role('admin')
                    <li class="w-full mt-4 mb-4">
                        <h6 class="pl-6 ml-2 text-xs font-bold leading-tight uppercase sidenav-text dark:text-white opacity-60">
                            Management Section
                        </h6>
                    </li>
                    <li class="mt-0.5 w-full">
                        <a class="dark:text-white dark:opacity-80 py-2.7 text-sm ease-nav-brand my-0 mx-2 flex items-center whitespace-nowrap px-4 transition-colors @if (request()->routeIs('admin.customers.*')) bg-blue-500/13 @endif"
                            href="{{ route('admin.customers.index') }}">
                            <div
                                class="mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-center text-center xl:p-2.5">
                                <i class="text-orange-500 fas fa-users"></i>
                            </div>
                            <span class="ml-1 duration-300 opacity-100 pointer-events-none ease sidenav-text">Manajemen
                                Customer</span>
                        </a>
                    </li>
                    <li class="mt-0.5 w-full">
                        <a class="dark:text-white dark:opacity-80 py-2.7 text-sm ease-nav-brand my-0 mx-2 flex items-center whitespace-nowrap px-4 transition-colors @if (request()->routeIs('admin.products.*')) bg-blue-500/13 @endif"
                            href="{{ route('admin.products.index') }}">
                            <div
                                class="mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-center text-center xl:p-2.5">
                                <i class="text-orange-500 fas fa-store"></i>
                            </div>
                            <span class="ml-1 duration-300 opacity-100 pointer-events-none ease sidenav-text">Manajemen
                                Produk</span>
                        </a>
                    </li>
                    <li class="mt-0.5 w-full">
                        <a class="dark:text-white dark:opacity-80 py-2.7 text-sm ease-nav-brand my-0 mx-2 flex items-center whitespace-nowrap px-4 transition-colors @if (request()->routeIs('admin.couriers.*')) bg-blue-500/13 @endif"
                            href="{{ route('admin.couriers.index') }}">
                            <div
                                class="mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-center text-center xl:p-2.5">
                                <i class="text-cyan-500 fas fa-truck"></i>
                            </div>
                            <span class="ml-1 duration-300 opacity-100 pointer-events-none ease sidenav-text">Manajemen
                                Kurir</span>
                        </a>
                    </li>

                    <li class="w-full mt-4 mb-4">
                        <h6 class="pl-6 ml-2 text-xs font-bold leading-tight uppercase sidenav-text dark:text-white opacity-60">
                            Order Section
                        </h6>
                    </li>

                    <li class="mt-0.5 w-full">
                        <a class="dark:text-white dark:opacity-80 py-2.7 text-sm ease-nav-brand my-0 mx-2 flex items-center whitespace-nowrap px-4 transition-colors"
                            href="#">
                            <div
                                class="mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-center text-center xl:p-2.5">
                                <i class="text-emerald-500 fas fa-cart-arrow-down"></i>
                            </div>
                            <span class="ml-1 duration-300 opacity-100 pointer-events-none ease sidenav-text">Pesanan</span>
                        </a>
                    </li>
                    <li class="mt-0.5 w-full">
                        <a class="dark:text-white dark:opacity-80 py-2.7 text-sm ease-nav-brand my-0 mx-2 flex items-center whitespace-nowrap px-4 transition-colors"
                            href="#">
                            <div
                                class="mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-center text-center xl:p-2.5">
                                <i class="text-red-500 fas fa-list"></i>
                            </div>
                            <span class="ml-1 duration-300 opacity-100 pointer-events-none ease sidenav-text">History
                                Pesanan</span>
                        </a>
                    </li>

                    <li class="w-full mt-4 mb-4">
                        <h6 class="pl-6 ml-2 text-xs font-bold leading-tight uppercase sidenav-text dark:text-white opacity-60">
                            Performa
                            Section
                        </h6>
                    </li>

                    <li class="mt-0.5 w-full">
                        <a class="dark:text-white dark:opacity-80 py-2.7 text-sm ease-nav-brand my-0 mx-2 flex items-center whitespace-nowrap px-4 transition-colors"
                            href="#">
                            <div
                                class="mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-center text-center xl:p-2.5">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <span class="ml-1 duration-300 opacity-100 pointer-events-none ease sidenav-text">Performa
                                Customer</span>
                        </a>
                    </li>

                    <li class="mt-0.5 w-full">
                        <a class="dark:text-white dark:opacity-80 py-2.7 text-sm ease-nav-brand my-0 mx-2 flex items-center whitespace-nowrap px-4 transition-colors"
                            href="#">
                            <div
                                class="mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-center text-center xl:p-2.5">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <span class="ml-1 duration-300 opacity-100 pointer-events-none ease sidenav-text">Performa
                                Kurir</span>
                        </a>
                    </li>

                    <li class="w-full mt-4 mb-4">
                        <h6 class="pl-6 ml-2 text-xs font-bold leading-tight uppercase sidenav-text dark:text-white opacity-60">
                            Account Settings
                        </h6>
                    </li>
                    <li class="mt-0.5 w-full">
                        <a class="py-2.7 text-sm ease-nav-brand my-0 mx-2 flex items-center whitespace-nowrap px-4 transition-colors rounded-lg font-semibold @if (request()->routeIs('admin.profile')) bg-blue-500/13 text-blue-700 dark:text-white dark:opacity-80 @else dark:text-white dark:opacity-80 @endif"
                            href="{{ route('admin.profile') }}">
                            <div
                                class="mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-center text-center xl:p-2.5">
                                <i
                                    class="fas fa-user @if (request()->routeIs('admin.profile')) text-blue-500 @else text-slate-700 @endif"></i>
                            </div>
                            <span class="ml-1 duration-300 opacity-100 pointer-events-none ease sidenav-text">Profil Saya</span>
                        </a>
                    </li>
                @endrole

                @role('kurir')
                    <li class="mt-0.5 w-full">
                        <a class="dark:text-white dark:opacity-80 py-2.7 text-sm ease-nav-brand my-0 mx-2 flex items-center whitespace-nowrap px-4 transition-colors @if (request()->routeIs('kurir.customers.*')) bg-blue-500/13 @endif" href="{{ route('kurir.customers.index') }}">
                            <div
                                class="mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-center text-center xl:p-2.5">
                                <i class="text-red-600 fas fa-book"></i>
                            </div>
                            <span class="ml-1 duration-300 opacity-100 pointer-events-none ease sidenav-text">Data
                                Customer</span>
                        </a>
                    </li>
                    <li class="mt-0.5 w-full">
                        <a class="dark:text-white dark:opacity-80 py-2.7 text-sm ease-nav-brand my-0 mx-2 flex items-center whitespace-nowrap px-4 transition-colors"
                            href="{{ url('/pengiriman') }}">
                            <div
                                class="mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-center text-center xl:p-2.5">
                                <i class="fas fa-folder text-greenlight"></i>
                            </div>
                            <span class="ml-1 duration-300 opacity-100 pointer-events-none ease sidenav-text">History
                                Pesanan</span>
                        </a>

                    </li>

                    <li class="w-full mt-4 mb-4">
                        <h6 class="pl-6 ml-2 text-xs font-bold leading-tight uppercase sidenav-text dark:text-white opacity-60">
                            Account Pages
                        </h6>
                    </li>
                    <li class="mt-0.5 w-full">
                        <a class="py-2.7 text-sm ease-nav-brand my-0 mx-2 flex items-center whitespace-nowrap px-4 transition-colors rounded-lg font-semibold @if (request()->routeIs('kurir.profile')) bg-blue-500/13 text-blue-700 dark:text-white dark:opacity-80 @else dark:text-white dark:opacity-80 @endif"
                            href="{{ route('kurir.profile') }}">
                            <div
                                class="mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-center text-center xl:p-2.5">
                                <i
                                    class="fas fa-user @if (request()->routeIs('kurir.profile')) text-blue-500 @else text-slate-700 @endif"></i>
                            </div>
                            <span class="ml-1 duration-300 opacity-100 pointer-events-none ease sidenav-text">Profil
                                Saya</span>
                        </a>
                    </li>
                @endrole

                <li class="mt-0.5 w-full">
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <a class="dark:text-white dark:opacity-80 py-2.7 text-sm ease-nav-brand my-0 mx-2 flex items-center whitespace-nowrap px-4 transition-colors"
                            href="{{ route('logout') }}"
                            onclick="event.preventDefault(); this.closest('form').submit();">
                            <div
                                class="mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-center text-center xl:p-2.5">
                                <i class="text-red-600 fas fa-sign-out-alt"></i>
                            </div>
                            <span class="ml-1 duration-300 opacity-100 pointer-events-none ease sidenav-text">Logout</span>
                        </a>
                    </form>
                </li>
            </ul>
        </div>

        {{-- Sidenav Toggler --}}
        <div class="flex-shrink-0 p-4 mx-4">
            {{-- PERBAIKAN: Menggunakan tag <a> dengan atribut sidenav-trigger --}}
            <a href="javascript:;"
                class="items-center justify-center hidden w-full p-3 text-white rounded-full xl:flex bg-greenlight hover:bg-black dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-black focus:outline-none"
                sidenav-trigger>
                <i class="transition-transform duration-300 fas fa-chevron-left"></i>
            </a>
        </div>
    @endauth
</aside>
