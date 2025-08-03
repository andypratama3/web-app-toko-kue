@extends('layouts.argon')
@section('title', 'Dashboard Kurir')
@section('page_title', 'Dashboard')

@section('content')
@include('dashboard.kurir.modal.tmbh-customer')

<!-- cards -->
<div class="w-full max-w full">
    <div class="flex flex-wrap gap-6 -mx-3">
        <div class="w-full max-w-full px-3 space-y-4 lg:flex-nome">
            <div class="mb-6 ">
                <div class="flex flex-col flex-none max-w-full gap-4 p-5 px-4 py-4 shadow rounded-2xl xl:gap-0" style="background-color: #efece8ff; ">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <div>
                                <div class="text-base font-bold text-black" id="ucapan">Selamat</div>
                                <div class="text-xs text-black">Your Achivement Today</div>
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-between mb-1 text-xs text-gray-700 opacity-80">
                        <div class="w-1/4 text-center">TOTAL</div>
                        <div class="w-1/4 text-center">WAKTU</div>
                        <div class="w-1/4 text-center">SELESAI</div>
                        <div class="w-1/4 text-center">GAGAL</div>
                    </div>
                    <div class="flex justify-between mb-4 text-lg font-bold text-gray-800">
                        <div class="w-1/4 text-center">62</div>
                        <div class="w-1/4 text-center">23</div>
                        <div class="w-1/4 text-center">45</div>
                        <div class="w-1/4 text-center">17</div>
                    </div>
                </div>
            </div>

            <div class="grid gap-4 g">
                <div class="flex flex-wrap -mx-3">
                    <!-- Card 1: Pesanan -->
                    {{-- <a href="{{ route('kurir.pages.tmbh-pesanan', ['region' => $kurir->region]) }}" class="w-1/2 px-3 "> --}}
                    <a href="#" class="w-1/2 px-3 ">
                        <div class="flex items-center p-4 transition duration-150 ease-in-out transform bg-white shadow-lg rounded-2xl hover:shadow-xl active:scale-95">
                            <div class="flex items-center justify-center w-12 h-6 mr-2 bg-green-100 rounded-full lg:mr-4">
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

                    <!-- Card 2: Cust -->
                    <button type="button"
                        class="w-1/2 px-3 mb-6"
                        data-modal-toggle="crud-modal">
                        <div class="flex items-center p-4 transition duration-150 ease-in-out transform bg-white shadow-lg rounded-2xl hover:shadow-xl active:scale-95">
                            <div class="flex items-center justify-center w-12 h-6 mr-2 bg-yellow-100 rounded-full lg:mr-4">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10" viewBox="0 0 24 24" fill="#fde047" stroke="#000">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M18 9v6m3-3h-6M9 16a4 4 0 100-8 4 4 0 000 8zm0 0c-2.21 0-4 1.79-4 4v1h8v-1c0-2.21-1.79-4-4-4z" />
                                </svg>
                            </div>
                            <div>
                                <div class="text-lg font-bold text-black sm:text-2xl">Customer</div>
                                <div class="text-xs text-left text-black sm:text-sm">Input here!</div>
                            </div>
                        </div>
                    </button>
                </div>
            </div>
        </div>

        <div class="w-full max-w-full px-3 mt-0 lg:flex-none">
            <div class="border-black/12.5 dark:bg-slate-850 dark:shadow-dark-xl shadow-xl relative z-20 flex min-w-0 flex-col break-words rounded-2xl border-0 border-solid bg-white bg-clip-border">
                <div class="border-black/12.5 mb-0 rounded-t-2xl border-b-0 border-solid p-6 pt-4 pb-0">
                    <h6 class="capitalize dark:text-white">Income</h6>
                    <p class="mb-0 text-sm leading-normal text-black dark:text-white dark:opacity-60">
                        <i class="fa fa-arrow-up text-emerald-500"></i>
                        <span class="text-xl font-semibold text-black">Rp 550.000</span>
                    </p>
                </div>
                <div class="flex-auto p-4">
                    <div>
                        <canvas id="savingsChart" class="h-[200px] lg:h-[300px]"></canvas>
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
                    <h5 class="text-black dark:text-white">Lastes Order</h5>
                </div>
                <div class="flex-auto px-0 pt-0 pb-2">
                    <div class="p-0 overflow-x-auto">
                        <table class="items-center w-full mb-0 align-top border-collapse dark:border-white/40 text-slate-500">
                            <thead class="text-black align-bottom">
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
                                    <td class="bg-transparent border-b p-2r dark:border-white/40 whitespace-nowrap shadow-transparent">
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
                    <div class="flex-1">
                        <h4 class="mb-1 text-xl text-blackfont-semibold">APAYAAA</h4>
                        <div class="mb-4 text-3xl font-bold text-black md:text-4xl">????? </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- end cards -->
<!-- </body> -->

<!-- plugin for charts  -->
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

<!-- untuk ucapan waktu -->
<script>
    function getGreeting() {
        const now = new Date();
        const hour = now.getHours();

        if (hour >= 4 && hour < 11) {
            return "Selamat Pagi 🌤";
        } else if (hour >= 11 && hour < 15) {
            return "Selamat Siang ☀";
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
<!--
<script>
    window.addEventListener('scroll', function() {
        const navbar = document.getElementById('navbar');
        if (window.scrollY > 10) {
            navbar.classList.add('shadow-xl');
        } else {
            navbar.classList.remove('shadow-xl');
        }
    });
</script> -->

<script src="/assets/argon/js/plugins/chartjs.min.js"></script>
<script src="/assets/argon/js/plugins/perfect-scrollbar.min.js" async></script>
<script src="/assets/argon/js/assets/argon-dashboard-tailwind.js?v=1.0.1" async></script>

</html>

@endsection
