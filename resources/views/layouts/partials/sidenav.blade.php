<aside
    class="fixed inset-y-0 z-50 flex-wrap items-center justify-between block w-full p-0 my-4 overflow-y-auto antialiased transition-transform duration-200 -translate-x-full bg-white border-0 shadow-xl dark:shadow-none dark:bg-slate-850 max-w-64 ease-nav-brand xl:ml-6 rounded-2xl xl:left-0 xl:translate-x-0"
    aria-expanded="false">
    <div class="h-19">
        <i class="absolute top-0 right-0 p-4 opacity-50 cursor-pointer fas fa-times dark:text-white text-slate-400 xl:hidden"
            sidenav-close></i>
        <a class="block px-8 py-6 m-0 text-sm whitespace-nowrap dark:text-white text-slate-700"
            href="{{ url('/dashboard') }}">
            <img src="{{ asset('assets/homepage/logo.png') }}"
                class="inline h-full max-w-full transition-all duration-200 ease-nav-brand max-h-8"
                alt="main_logo" />
            <span class="ml-1 font-semibold transition-all duration-200 ease-nav-brand text-greenlight">Kue Pandan Asli Malang</span>
        </a>
    </div>

    <hr class="h-px mt-0 bg-transparent bg-gradient-to-r from-transparent via-black/40 to-transparent dark:bg-gradient-to-r dark:from-transparent dark:via-white dark:to-transparent" />

    <div class="items-center block w-auto max-h-screen overflow-auto h-sidenav grow basis-full">
        <ul class="flex flex-col pl-0 mb-0">

            {{-- Menu Umum --}}
            <li class="mt-0.5 w-full">
                <a class="py-2.7 bg-blue-500/13 dark:text-white dark:opacity-80 text-sm ease-nav-brand my-0 mx-2 flex items-center whitespace-nowrap rounded-lg px-4 font-semibold text-slate-700 transition-colors"
                    href="{{ url('/dashboard') }}">
                    <div class="mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-center text-center xl:p-2.5">
                        <i class="text-blue-500 fas fa-home"></i>
                    </div>
                    <span class="ml-1 duration-300 opacity-100 pointer-events-none ease">Dashboard</span>
                </a>
            </li>

            {{-- Menu untuk Admin --}}
            @role('admin')
                <li class="mt-0.5 w-full">
                    <a class="dark:text-white dark:opacity-80 py-2.7 text-sm ease-nav-brand my-0 mx-2 flex items-center whitespace-nowrap px-4 transition-colors"
                        href="{{ url('/data-kurir') }}">
                        <div class="mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-center text-center xl:p-2.5">
                            <i class="text-orange-500 fas fa-truck"></i>
                        </div>
                        <span class="ml-1 duration-300 opacity-100 pointer-events-none ease">Manajemen Kurir</span>
                    </a>
                </li>
            @endrole

            {{-- Menu untuk Kurir --}}
            @role('kurir')
                <li class="mt-0.5 w-full">
                    <a class="dark:text-white dark:opacity-80 py-2.7 text-sm ease-nav-brand my-0 mx-2 flex items-center whitespace-nowrap px-4 transition-colors"
                        href="{{ url('/pengiriman') }}">
                        <div class="mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-center text-center xl:p-2.5">
                            <i class="fas fa-box text-emerald-500"></i>
                        </div>
                        <span class="ml-1 duration-300 opacity-100 pointer-events-none ease">Data Pengiriman</span>
                    </a>
                </li>
            @endrole

            <li class="w-full mt-4">
                <h6 class="pl-6 ml-2 text-xs font-bold leading-tight uppercase dark:text-white opacity-60">Account Pages</h6>
            </li>

            @auth
            <li class="mt-0.5 w-full">
                <a class="dark:text-white dark:opacity-80 py-2.7 text-sm ease-nav-brand my-0 mx-2 flex items-center whitespace-nowrap px-4 transition-colors"
                    href="{{ route('profile.show') }}">
                    <div class="mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-center text-center xl:p-2.5">
                        <i class="fas fa-user text-slate-700"></i>
                    </div>
                    <span class="ml-1 duration-300 opacity-100 pointer-events-none ease">Profil Saya</span>
                </a>
            </li>
            <li class="mt-0.5 w-full">
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <a class="dark:text-white dark:opacity-80 py-2.7 text-sm ease-nav-brand my-0 mx-2 flex items-center whitespace-nowrap px-4 transition-colors"
                        href="{{ route('logout') }}"
                        onclick="event.preventDefault(); this.closest('form').submit();">
                        <div class="mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-center text-center xl:p-2.5">
                            <i class="text-red-600 fas fa-sign-out-alt"></i>
                        </div>
                        <span class="ml-1 duration-300 opacity-100 pointer-events-none ease">Logout</span>
                    </a>
                </form>
            </li>
            @endauth
        </ul>
    </div>
</aside>
