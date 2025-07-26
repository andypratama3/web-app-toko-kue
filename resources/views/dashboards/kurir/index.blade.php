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
    <div class="fixed top-0 w-full z-0 dark:hidden min-h-75" style="background-color: #888f7a;"></div>

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
        <nav id="navbar" class="sticky top-0 pt-4 pb-2 z-50 px-6 transition-shadow duration-300 shadow-xl rounded-b-2xl" style="background-color: #888f7a;">
            <div class="flex items-center justify-between bg-[#888f7a] p-5">
                <div class="flex items-center">
                    <div>
                        <h4 class="mb-0 font-bold text-white">Hai! {{ $kurir->name }}</h4>
                        <div class="text-sm text-white">Semangat bekerja!</div>
                    </div>
                </div>
                <div>
                    <a href="javascript:;" class="block p-0 text-sm text-white xl:hidden" sidenav-trigger>
                        <button class="bg-white/10 p-2 rounded-lg">
                            <svg width="24" height="24" fill="none" stroke="currentColor" class="text-white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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

        <!-- cards -->
        <div class="w-full px-6 py-6 mx-auto">
            <div class="flex flex-wrap gap-6 -mx-3">
                <div class="w-full max-w-full lg:flex-nome lg:w-1/2 space-y-4 px-3">
                    <div class=" mb-6">
                        <div class=" max-w-full rounded-2xl p-5 shadow flex flex-col flex-none gap-4 xl:gap-0 px-4 py-4 " style="background-color: #efece8ff; ">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-2">
                                    <div>
                                        <div class="font-bold text-black text-base" id="ucapan">Selamat</div>
                                        <div class="text-xs text-black">Your Achivement Today</div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-between text-xs text-gray-700 opacity-80 mb-1">
                                <div class="w-1/4 text-center">TOTAL</div>
                                <div class="w-1/4 text-center">WAKTU</div>
                                <div class="w-1/4 text-center">SELESAI</div>
                                <div class="w-1/4 text-center">GAGAL</div>
                            </div>
                            <div class="flex justify-between font-bold text-gray-800 text-lg mb-4">
                                <div class="w-1/4 text-center">62</div>
                                <div class="w-1/4 text-center">23</div>
                                <div class="w-1/4 text-center">45</div>
                                <div class="w-1/4 text-center">17</div>
                            </div>
                            <!-- <div class="flex justify-between items-center text-xs text-gray-700 opacity-80">
                                <span class="font-semibold">Today <span class="ml-1">▼</span></span>
                                <span class="underline">See Progress</span>
                            </div> -->
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="flex flex-wrap -mx-3">
                            <!-- Card 1: Pesanan -->
                            <a href="/tambah-pesanan" class="w-1/2 px-3 mb-6">
                                <div class="flex items-center rounded-2xl p-4 bg-white shadow-lg hover:shadow-xl transition ease-in-out duration-150 transform active:scale-95">
                                    <div class="flex items-center justify-center w-12 h-6 rounded-full bg-green-100 mr-2 lg:mr-4">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10" viewBox="0 0 24 24" fill="#fde047" stroke="#000">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M12 3v2a2 2 0 01-2 2h4a2 2 0 01-2-2V3zm-2 9h4m-4 4h4" />
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="text-lg font-bold text-black sm:text-xl">Pesanan</div>
                                        <div class="text-xs text-black sm:text-sm">Input here!</div>
                                    </div>
                                </div>
                            </a>

                            <!-- Card 2: Seller -->
                            <a href="{{ route('kurir.tambahSeller', ['region' => $kurir->region]) }}" class="w-1/2 px-3 mb-6">
                                <div class="flex items-center rounded-2xl p-4 bg-white shadow-lg hover:shadow-xl transition ease-in-out duration-150 transform active:scale-95">
                                    <div class="flex items-center justify-center w-12 h-6 rounded-full bg-yellow-100 mr-2 lg:mr-4">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10" viewBox="0 0 24 24" fill="#fde047" stroke="#000">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M18 9v6m3-3h-6M9 16a4 4 0 100-8 4 4 0 000 8zm0 0c-2.21 0-4 1.79-4 4v1h8v-1c0-2.21-1.79-4-4-4z" />
                                        </svg>

                                    </div>
                                    <div>
                                        <div class="text-lg font-bold text-black sm:text-2xl">Seller</div>
                                        <div class="text-xs text-black sm:text-sm">Input here!</div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>

                </div>

                <div class="w-full max-w-full px-3 mt-0 lg:w-1/2 lg:flex-none">
                    <div class="border-black/12.5 dark:bg-slate-850 dark:shadow-dark-xl shadow-xl relative z-20 flex min-w-0 flex-col break-words rounded-2xl border-0 border-solid bg-white bg-clip-border">
                        <div class="border-black/12.5 mb-0 rounded-t-2xl border-b-0 border-solid p-6 pt-4 pb-0">
                            <h6 class="capitalize dark:text-white">Income</h6>
                            <p class="mb-0 text-sm text-black leading-normal dark:text-white dark:opacity-60">
                                <i class="fa fa-arrow-up text-emerald-500"></i>
                                <span class="text-xl text-black font-semibold">Rp 550.000</span>
                            </p>
                        </div>
                        <div class="flex-auto p-4">
                            <div>
                                <canvas id="savingsChart" class="h-[200px] lg:h-[85px]"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lastes Order -->
            <div class="flex flex-wrap mt-6 -mx-3">
                <div class="w-full max-w-full px-3 mt-0 lg:w-7/12 lg:flex-none">
                    <div class="relative flex flex-col min-w-0 mb-4 break-words bg-white border-0 border-transparent border-solid shadow-xl dark:bg-slate-850 dark:shadow-dark-xl rounded-2xl bg-clip-border">
                        <div class="p-6 pb-0 mb-0 border-b-0 border-b-solid rounded-t-2xl border-b-transparent">
                            <h5 class="dark:text-white text-black">Lastes Order</h5>
                        </div>
                        <div class="flex-auto px-0 pt-0 pb-2">
                            <div class="p-0 overflow-x-auto">
                                <table class="items-center w-full mb-0 align-top border-collapse dark:border-white/40 text-slate-500">
                                    <thead class="align-bottom text-black">
                                        <tr>
                                            <th class="px-6 py-3 font-bold text-left text-black uppercase align-middle bg-transparent border-b border-collapse shadow-none dark:border-white/40 dark:text-white text-xxs border-b-solid tracking-none whitespace-nowrap text-slate-400 opacity-70">
                                                Nama</th>
                                            <th class="px-6 py-3 pl-2 font-bold text-left text-black uppercase align-middle bg-transparent border-b border-collapse shadow-none dark:border-white/40 dark:text-white text-xxs border-b-solid tracking-none whitespace-nowrap text-slate-400 opacity-70">
                                                Alamat</th>
                                            <th class="px-6 py-3 font-bold text-center text-black uppercase align-middle bg-transparent border-b border-collapse shadow-none dark:border-white/40 dark:text-white text-xxs border-b-solid tracking-none whitespace-nowrap text-slate-400 opacity-70">
                                                Pesanan</th>
                                            <th class="px-6 py-3 font-bold text-center text-black uppercase align-middle bg-transparent border-b border-collapse shadow-none dark:border-white/40 dark:text-white text-xxs border-b-solid tracking-none whitespace-nowrap text-slate-400 opacity-70">
                                                Status</th>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="p-2 align-middle bg-transparent border-b dark:border-white/40 whitespace-nowrap shadow-transparent">
                                                <div class="flex px-2 py-1">
                                                    <div class="flex flex-col justify-center">
                                                        <h6 class="mb-0 text-sm leading-normal dark:text-white">John Michael</h6>
                                                        <p class="mb-0 text-xs leading-tight dark:text-white dark:opacity-80 text-slate-400">
                                                            john@creative-tim.com</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="p-2 align-middle bg-transparent border-b dark:border-white/40 whitespace-nowrap shadow-transparent">
                                                <p class="mb-0 text-xs font-semibold leading-tight dark:text-white dark:opacity-80">Gampeng</p>
                                            </td>
                                            <td class="p-2 bg-transparent border-b dark:border-white/40 whitespace-nowrap shadow-transparent">
                                                <span class="text-xs font-semibold leading-tight dark:text-white dark:opacity-80 text-slate-400">Tumpeng Kue Pandan</span>
                                            </td>
                                            <td class="p-2 text-sm leading-normal text-center align-middle bg-transparent border-b dark:border-white/40 whitespace-nowrap shadow-transparent">
                                                <span class="bg-gradient-to-tl from-emerald-500 to-teal-400 px-2.5 text-xs rounded-1.8 py-1.4 inline-block whitespace-nowrap text-center align-baseline font-bold uppercase leading-none text-white">TERKIRIM</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="p-2 align-middle bg-transparent border-b dark:border-white/40 whitespace-nowrap shadow-transparent">
                                                <div class="flex px-2 py-1">
                                                    <div class="flex flex-col justify-center">
                                                        <h6 class="mb-0 text-sm leading-normal dark:text-white">John Michael</h6>
                                                        <p class="mb-0 text-xs leading-tight dark:text-white dark:opacity-80 text-slate-400">
                                                            john@creative-tim.com</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="p-2 align-middle bg-transparent border-b dark:border-white/40 whitespace-nowrap shadow-transparent">
                                                <p class="mb-0 text-xs font-semibold leading-tight dark:text-white dark:opacity-80">Gampeng</p>
                                            </td>
                                            <td class="p-2r bg-transparent border-b dark:border-white/40 whitespace-nowrap shadow-transparent">
                                                <span class="text-xs font-semibold leading-tight dark:text-white dark:opacity-80 text-slate-400">Kue Manis</span>
                                            </td>
                                            <td class="p-2 text-sm leading-normal text-center align-middle bg-transparent border-b dark:border-white/40 whitespace-nowrap shadow-transparent">
                                                <span class="bg-gradient-to-tl from-red-600 to-red-400 px-2.5 text-xs rounded-1.8 py-1.4 inline-block whitespace-nowrap text-center align-baseline font-bold uppercase leading-none text-white">Gagal</span>
                                            </td>

                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="w-full max-w-full px-3 mt-0 mb-6 lg:mb-0 lg:w-5/12 lg:flex-none">
                    <div class="relative flex flex-col min-w-0 break-words bg-white border-0 border-solid shadow-xl dark:bg-slate-850 dark:shadow-dark-xl dark:bg-gray-950 border-black-125 rounded-2xl bg-clip-border">
                        <div class="p-4 pb-0 mb-0 rounded-t-4">
                            <!-- Total Income -->
                            <div class="flex-1">
                                <h4 class=" text-blackfont-semibold text-xl mb-1">APAYAAA</h4>
                                <div class="text-3xl md:text-4xl font-bold  text-black mb-4">????? </div>
                            </div>
                            <!-- Savings Card -->
                            <!-- <div class="flex-1">
                                <div class="rounded-2xl p-5 bg-white shadow flex flex-col" style="min-width:280px;">
                                    <div class="w-full h-36 flex items-center justify-center">
                                        <canvas id="savingsChart" height="100"></canvas>
                                    </div>
                                </div>
                            </div> -->
                        </div>
                    </div>
                </div>
            </div>

            <footer class="">
                <div class="w-full px-6 mx-auto">
                    <div class="flex flex-wrap items-center -mx-3 lg:justify-between">
                        <div class="w-full max-w-full px-3 mt-0 mb-4 shrink-0 lg:mb-0 lg:w-1/2 lg:flex-none">
                            <div class="text-sm leading-normal text-center text-slate-500 lg:text-left">
                                ©
                                <script>
                                    document.write(new Date().getFullYear() + ",");
                                </script>
                                made with <i class="fa fa-heart"></i> by
                                <a href="https://www.creative-tim.com" class="font-semibold text-slate-700 dark:text-white" target="_blank">Creative Tim L0v3</a>

                            </div>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
        <!-- end cards -->
    </main>
</body>
<!-- plugin for charts  -->

<script src="/assets/argon/js/plugins/chartjs.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var ctx = document.getElementById('savingsChart');
        if (ctx && window.Chart) {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May'],
                    datasets: [{
                        label: '',
                        data: [20, 110, 55, 100, 30],
                        borderColor: '#b7d6d1',
                        backgroundColor: 'rgba(183,214,209,0.15)',
                        fill: true,
                        tension: 0.5,
                        pointBackgroundColor: '#b7d6d1',
                        pointBorderColor: '#fff',
                        pointRadius: 5,
                        pointHoverRadius: 7
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            enabled: true,
                            callbacks: {
                                label: function(context) {
                                    return '$' + context.parsed.y;
                                }
                            },
                            backgroundColor: '#fff',
                            titleColor: '#333',
                            bodyColor: '#4b5563',
                            borderColor: '#b7d6d1',
                            borderWidth: 1,
                            displayColors: false,
                            padding: 10
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                color: '#b0b0b0',
                                font: {
                                    size: 12
                                }
                            },
                            grid: {
                                color: 'rgba(0,0,0,0.04)'
                            }
                        },
                        x: {
                            ticks: {
                                color: '#b0b0b0',
                                font: {
                                    size: 12
                                }
                            },
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }
    });
</script>

<script>
    function getGreeting() {
        const now = new Date();
        const hour = now.getHours();

        if (hour >= 4 && hour < 11) {
            return "Selamat Pagi 🌤️";
        } else if (hour >= 11 && hour < 15) {
            return "Selamat Siang ☀️";
        } else if (hour >= 15 && hour < 18) {
            return "Selamat Sore 🌇";
        } else {
            return "Selamat Malam 🌙";
        }

    }

    // Set ke elemen dengan id ucapan
    document.addEventListener("DOMContentLoaded", function() {
        document.getElementById("ucapan").textContent = getGreeting();
    });
</script>

<script>
    window.addEventListener('scroll', function() {
        const navbar = document.getElementById('navbar');
        if (window.scrollY > 10) {
            navbar.classList.add('shadow-xl');
        } else {
            navbar.classList.remove('shadow-xl');
        }
    });
</script>


<script src="/assets/argon/js/plugins/perfect-scrollbar.min.js" async></script>
<script src="/assets/argon/js/assets/argon-dashboard-tailwind.js?v=1.0.1" async></script>

</html>