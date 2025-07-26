<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="apple-touch-icon" sizes="76x76" href="/assets/argon/img/apple-icon.png" />
    <link rel="icon" type="image/png" href="/assets/argon/img/favicon.png" />
    <title>Argon Dashboard 2 Tailwind by Creative Tim</title>
    <!--     Fonts and icons     -->
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" rel="stylesheet" />
    <!-- Font Awesome Icons -->
    <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>
    <!-- Nucleo Icons -->
    <link href="/assets/argon/css/nucleo-icons.css" rel="stylesheet" />
    <link href="/assets/argon/css/nucleo-svg.css" rel="stylesheet" />
    <!-- Popper -->
    <script src="https://unpkg.com/@popperjs/core@2"></script>
    <!-- Main Styling -->
    <link href="/assets/argon/css/argon-dashboard-tailwind.css?v=1.0.1" rel="stylesheet" />
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-KoD9y+QZ0cqGyhAVM1RxBexqKZ9AcHAb8Pqwd6WsvUYoRA1c6O6g6PKzWj13JD8SH2EGrKZyQjNVt2C2Ex+ARA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://kit.fontawesome.com/7ae6395865.js" crossorigin="anonymous"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var aside = document.querySelector('aside');
            var overlay = document.getElementById('sidebar-overlay');
            // Toggle open
            document.querySelectorAll('[sidenav-trigger]').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    aside.classList.remove('-translate-x-full');
                    aside.classList.add('translate-x-0');
                    overlay.classList.remove('hidden');
                    overlay.classList.add('block');
                });
            });
            // Toggle close
            document.querySelectorAll('[sidenav-close]').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    aside.classList.add('-translate-x-full');
                    aside.classList.remove('translate-x-0');
                    overlay.classList.add('hidden');
                    overlay.classList.remove('block');
                });
            });
            // Click overlay to close
            overlay.addEventListener('click', function() {
                aside.classList.add('-translate-x-full');
                aside.classList.remove('translate-x-0');
                overlay.classList.add('hidden');
                overlay.classList.remove('block');
            });
        });
    </script>

</head>

<body class="m-0 font-sans text-base antialiased font-normal dark:bg-slate-900 leading-default bg-blue-50 text-slate-500">
    <div class="fixed top-0 w-full z-0 dark:hidden min-h-75"></div>

    <!-- sidenav  -->
    <div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-40 z-980 hidden transition-opacity duration-200"></div>
    <aside class="fixed inset-y-0 flex-wrap items-center justify-between block w-full p-0 my-4 overflow-y-auto antialiased transition-transform duration-200 -translate-x-full bg-white border-0 shadow-xl dark:shadow-none dark:bg-slate-850 max-w-64 ease-nav-brand z-990 xl:ml-6 rounded-2xl xl:left-0 xl:translate-x-0" aria-expanded="false">
        <div class="h-19">
            <i class="absolute top-0 right-0 p-4 opacity-50 cursor-pointer fas fa-times dark:text-white text-slate-400 xl:hidden" sidenav-close></i>
            <a class="block px-8 py-6 m-0 text-sm whitespace-nowrap dark:text-white text-slate-700">
                <!-- <img src="/assets/assets/img/logo-ct-dark.png" class="inline h-full max-w-full transition-all duration-200 dark:hidden ease-nav-brand max-h-8" alt="main_logo" />
                <img src="/assets/assets/img/logo-ct.png" class="hidden h-full max-w-full transition-all duration-200 dark:inline ease-nav-brand max-h-8" alt="main_logo" /> -->
                <span class="ml-1 font-semibold transition-all duration-200 ease-nav-brand">Hai <strong>{{ $kurir->name }}</strong>!</span>
            </a>
        </div>

        <hr class="h-px mt-0 bg-transparent bg-gradient-to-r from-transparent via-black/40 to-transparent dark:bg-gradient-to-r dark:from-transparent dark:via-white dark:to-transparent" />

        <!-- <div class="items-center block w-auto max-h-screen overflow-auto h-sidenav grow basis-full"> -->
        <ul class="flex flex-col pl-0 mb-0">
            <li class="mt-0.5 w-full">
                <a class="py-2.7 bg-blue-500/13 dark:text-white dark:opacity-80 text-sm ease-nav-brand my-0 mx-2 flex items-center whitespace-nowrap rounded-lg px-4 font-semibold text-slate-700 transition-colors" href="./pages/dashboard.html">
                    <div class="mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-center stroke-0 text-center xl:p-2.5">
                        <i class="relative top-0 text-sm leading-normal text-cyan-500 ni ni-shop"></i>
                    </div>
                    <span class="ml-1 duration-300 opacity-100 pointer-events-none ease">Home</span>
                </a>
            </li>

            <li class="mt-0.5 w-full">
                <a class=" dark:text-white dark:opacity-80 py-2.7 text-sm ease-nav-brand my-0 mx-2 flex items-center whitespace-nowrap px-4 transition-colors" href="./pages/tables.html">
                    <div class="mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-center stroke-0 text-center xl:p-2.5">
                        <i class="relative top-0 text-sm leading-normal text-cyan-500 ni ni-briefcase-24"></i>
                    </div>
                    <span class="ml-1 duration-300 opacity-100 pointer-events-none ease">Data Seller</span>
                </a>
            </li>

            <li class="mt-0.5 w-full">
                <a class=" dark:text-white dark:opacity-80 py-2.7 text-sm ease-nav-brand my-0 mx-2 flex items-center whitespace-nowrap px-4 transition-colors" href="./pages/billing.html">
                    <div class="mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-center fill-current stroke-0 text-center xl:p-2.5">
                        <i class="relative top-0 text-sm leading-normal text-cyan-500 ni ni-time-alarm"></i>
                    </div>
                    <span class="ml-1 duration-300 opacity-100 pointer-events-none ease">History</span>
                </a>
            </li>

            <li class="w-full mt-4">
                <h6 class="pl-6 ml-2 text-xs font-bold leading-tight uppercase dark:text-white opacity-60">Account</h6>
            </li>

            <li class="mt-0.5 w-full">
                <a class=" dark:text-white dark:opacity-80 py-2.7 text-sm ease-nav-brand my-0 mx-2 flex items-center whitespace-nowrap px-4 transition-colors" href="./pages/profile.html">
                    <div class="mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-center stroke-0 text-center xl:p-2.5">
                        <i class="relative top-0 text-sm leading-normal text-cyan-500 ni ni-circle-08"></i>
                    </div>
                    <span class="ml-1 duration-300 opacity-100 pointer-events-none ease">Profile</span>
                </a>
            </li>

            <li class="mt-0.5 w-full">
                <a class=" dark:text-white dark:opacity-80 py-2.7 text-sm ease-nav-brand my-0 mx-2 flex items-center whitespace-nowrap px-4 transition-colors" href="./pages/sign-up.html">
                    <div class="mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-center stroke-0 text-center xl:p-2.5">
                        <i class="relative top-0 text-sm leading-normal text-cyan-500 ni ni-user-run"></i>
                    </div>
                    <span class="ml-1 duration-300 opacity-100 pointer-events-none ease">Log Out</span>
                </a>
            </li>
        </ul>
        <!-- </div> -->
    </aside>
    <!-- end sidenav -->

    <main class="relative h-full max-h-screen transition-all duration-200 ease-in-out xl:ml-68 rounded-xl">
        <!-- Navbar -->
        <nav id="navbar" class="sticky top-0 py-2 z-50 px-6 transition-shadow duration-300 shadow-xl rounded-b-2xl" >
            <div class="flex items-center justify-between bg-[#888f7a] p-5">
                <!-- Kiri: Icon panah -->
                <div class="flex items-center gap-4">
                    <a href="javascript:history.back()">
                        <svg xmlns="http://www.w3.org/2000/svg" class="text-black" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                        </svg>
                    </a>
                </div>

                <!-- Tengah: Teks -->
                <div class="text-black text-lg font-bold text-center flex-1">
                    Order's
                </div>

                <!-- Kanan: Icon menu (tetap seperti sebelumnya) -->
                <div class="flex items-center justify-end w-6">
                    <a href="javascript:;" class="block p-0 text-sm text-black xl:hidden" sidenav-trigger>
                        <button class="bg-white/10 p-2 rounded-lg">
                            <svg width="24" height="24" fill="none" stroke="currentColor" class="text-black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="7" height="7" rx="1.5" />
                                <rect x="14" y="3" width="7" height="7" rx="1.5" />
                                <rect x="14" y="14" width="7" height="7" rx="1.5" />
                                <rect x="3" y="14" width="7" height="7" rx="1.5" />
                            </svg>
                        </button>
                    </a>
                </div>
            </div>
        </nav>

        <!-- end Navbar -->

    </main>
</body>
<!-- plugin for charts  -->

<script src="/assets/argon/js/plugins/chartjs.min.js"></script>
<script src="/assets/argon/js/plugins/perfect-scrollbar.min.js" async></script>
<script src="/assets/argon/js/argon-dashboard-tailwind.js?v=1.0.1" async></script>

</html>