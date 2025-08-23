@extends('layouts.argon')
@section('title', 'Dashboard Kurir')
@section('page_title', 'Dashboard')

@section('content')
    <!-- Enhanced Dashboard Cards -->
    <div class="w-full max-w full">
        <div class="flex flex-wrap gap-6 -mx-3">
            <div class="w-full max-w-full px-3 space-y-4 lg:flex-nome">
                <!-- Enhanced Achievement Card -->
                <div class="mb-6">
                    <div
                        class="relative overflow-hidden flex flex-col flex-none max-w-full gap-4 p-6 px-6 py-6 shadow-2xl rounded-3xl xl:gap-0 bg-gradient-to-br from-amber-50 via-orange-50 to-yellow-50 dark:from-slate-800 dark:via-slate-700 dark:to-slate-800 border border-amber-100 dark:border-slate-600">
                        <!-- Background Pattern -->
                        <div class="absolute top-0 right-0 w-32 h-32 opacity-10">
                            <div
                                class="w-full h-full bg-gradient-to-br from-yellow-400 to-orange-500 rounded-full transform rotate-45 translate-x-8 -translate-y-8">
                            </div>
                        </div>

                        <div class="relative z-10 flex items-center justify-between mb-6">
                            <div class="flex items-center gap-3">
                                <div class="p-3 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-2xl shadow-lg">
                                    <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-2xl font-bold bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent dark:from-white dark:to-gray-300"
                                        id="ucapan">Selamat</div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400 font-medium">Your Achievement Today
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Stats Grid -->
                        <div class="relative z-10 grid grid-cols-4 gap-4">
                            <div
                                class="text-center p-4 bg-white/60 dark:bg-slate-700/60 backdrop-blur-sm rounded-2xl border border-white/20 dark:border-slate-600/20 shadow-lg hover:shadow-xl transition-all duration-300 hover:scale-105">
                                <div
                                    class="text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-2">
                                    TOTAL</div>
                                <div
                                    class="text-3xl font-bold bg-gradient-to-br from-blue-600 to-blue-800 bg-clip-text text-transparent">
                                    62</div>
                            </div>
                            <div
                                class="text-center p-4 bg-white/60 dark:bg-slate-700/60 backdrop-blur-sm rounded-2xl border border-white/20 dark:border-slate-600/20 shadow-lg hover:shadow-xl transition-all duration-300 hover:scale-105">
                                <div
                                    class="text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-2">
                                    WAKTU</div>
                                <div
                                    class="text-3xl font-bold bg-gradient-to-br from-orange-500 to-red-600 bg-clip-text text-transparent">
                                    23</div>
                            </div>
                            <div
                                class="text-center p-4 bg-white/60 dark:bg-slate-700/60 backdrop-blur-sm rounded-2xl border border-white/20 dark:border-slate-600/20 shadow-lg hover:shadow-xl transition-all duration-300 hover:scale-105">
                                <div
                                    class="text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-2">
                                    SELESAI</div>
                                <div
                                    class="text-3xl font-bold bg-gradient-to-br from-green-500 to-emerald-600 bg-clip-text text-transparent">
                                    45</div>
                            </div>
                            <div
                                class="text-center p-4 bg-white/60 dark:bg-slate-700/60 backdrop-blur-sm rounded-2xl border border-white/20 dark:border-slate-600/20 shadow-lg hover:shadow-xl transition-all duration-300 hover:scale-105">
                                <div
                                    class="text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-2">
                                    GAGAL</div>
                                <div
                                    class="text-3xl font-bold bg-gradient-to-br from-red-500 to-pink-600 bg-clip-text text-transparent">
                                    17</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Enhanced Action Cards -->
                <div class="grid gap-4">
                    <div class="flex flex-wrap -mx-3">
                        <!-- Enhanced Pesanan Card -->
                        <a href="{{ route('kurir.pesanan.create') }}" class="w-1/2 px-3">
                            <div
                                class="group relative overflow-hidden flex items-center p-4 transition-all duration-300 ease-out transform bg-gradient-to-br from-green-50 to-emerald-50 dark:from-slate-800 dark:to-slate-700 shadow-xl rounded-2xl hover:shadow-2xl hover:scale-105 border border-green-100 dark:border-slate-600">
                                <!-- Background Animation -->
                                <div
                                    class="absolute inset-0 bg-gradient-to-br from-green-400/10 to-emerald-500/10 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                </div>

                                <div class="relative z-10 flex items-center w-full">
                                    <div
                                        class="flex items-center justify-center w-12 h-12 mr-3 bg-gradient-to-br from-green-400 to-emerald-500 rounded-xl shadow-lg group-hover:shadow-xl transition-shadow duration-300">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-white"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M12 3v2a2 2 0 01-2 2h4a2 2 0 01-2-2V3zm-2 9h4m-4 4h4" />
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div
                                            class="text-lg font-bold text-gray-800 dark:text-white mb-1 group-hover:text-green-600 dark:group-hover:text-green-400 transition-colors duration-300 truncate">
                                            Pesanan</div>
                                        <div class="text-xs text-gray-600 dark:text-gray-400 font-medium truncate">Buat
                                            pesanan baru</div>
                                    </div>
                                    <div
                                        class="opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex-shrink-0">
                                        <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 5l7 7-7 7" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </a>

                        <!-- Enhanced Customer Card -->
                        <button type="button" id="add-customer" class="w-1/2 px-3 mb-6" data-modal-target="crud-modal"
                            data-modal-toggle="crud-modal">
                            <div
                                class="group relative overflow-hidden flex items-center p-4 transition-all duration-300 ease-out transform bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-slate-800 dark:to-slate-700 shadow-xl rounded-2xl hover:shadow-2xl hover:scale-105 border border-blue-100 dark:border-slate-600">
                                <!-- Background Animation -->
                                <div
                                    class="absolute inset-0 bg-gradient-to-br from-blue-400/10 to-indigo-500/10 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                </div>

                                <div class="relative z-10 flex items-center w-full">
                                    <div
                                        class="flex items-center justify-center w-12 h-12 mr-3 bg-gradient-to-br from-blue-400 to-indigo-500 rounded-xl shadow-lg group-hover:shadow-xl transition-shadow duration-300">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-white"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M18 9v6m3-3h-6M9 16a4 4 0 100-8 4 4 0 000 8zm0 0c-2.21 0-4 1.79-4 4v1h8v-1c0-2.21-1.79-4-4-4z" />
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div
                                            class="text-lg font-bold text-gray-800 dark:text-white mb-1 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors duration-300 truncate">
                                            Customer</div>
                                        <div class="text-xs text-gray-600 dark:text-gray-400 font-medium truncate">Tambah
                                            customer baru</div>
                                    </div>
                                    <div
                                        class="opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex-shrink-0">
                                        <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Enhanced Notes Card -->
            <div class="w-full max-w-full px-3 mt-0 mb-6 lg:mb-0 lg:flex-none">
                <div
                    class="relative overflow-hidden flex flex-col min-w-0 break-words bg-gradient-to-br from-white to-gray-50 dark:from-slate-800 dark:to-slate-900 shadow-2xl rounded-3xl bg-clip-border border border-gray-100 dark:border-slate-700">
                    <div class="p-6 pb-4 mb-0 rounded-t-4">
                        <div class="flex-1">
                            @if (Auth::user()->note)
                                <div class="flex items-center mb-4">
                                    <div class="p-2 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-lg mr-3">
                                        <i class="text-sm text-white fas fa-sticky-note"></i>
                                    </div>
                                    <h6 class="mb-0 text-lg font-bold text-gray-800 dark:text-white">Catatan dari Admin</h6>
                                </div>
                                <div
                                    class="p-4 bg-gradient-to-r from-yellow-50 to-orange-50 dark:from-slate-700 dark:to-slate-600 rounded-2xl border-l-4 border-yellow-400">
                                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed font-medium">
                                        "{{ Auth::user()->note }}"
                                    </p>
                                </div>
                            @else
                                <div class="flex items-center mb-4">
                                    <div class="p-2 bg-gradient-to-br from-green-400 to-emerald-500 rounded-lg mr-3">
                                        <i class="text-sm text-white fas fa-check-circle"></i>
                                    </div>
                                    <h6 class="mb-0 text-lg font-bold text-gray-800 dark:text-white">Tidak Ada Catatan Baru
                                    </h6>
                                </div>
                                <div
                                    class="p-4 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-slate-700 dark:to-slate-600 rounded-2xl border-l-4 border-green-400">
                                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed">
                                        Saat ini tidak ada catatan baru dari admin.
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Enhanced Income Chart -->
            <div class="w-full max-w-full px-3 mt-0 lg:flex-none">
                <div
                    class="relative overflow-hidden shadow-2xl rounded-3xl bg-gradient-to-br from-white to-blue-50 dark:from-slate-800 dark:to-slate-900 border border-blue-100 dark:border-slate-700">
                    <div class="p-6 pt-4 pb-0 rounded-t-3xl">
                        <div class="flex items-center justify-between">
                            <div>
                                <h6 class="text-lg font-bold text-gray-800 dark:text-white mb-2">Income</h6>
                                <div class="flex items-center">
                                    <div class="p-2 bg-gradient-to-br from-green-400 to-emerald-500 rounded-lg mr-3">
                                        <i class="fa fa-arrow-up text-white text-sm"></i>
                                    </div>
                                    <span
                                        class="text-2xl font-bold bg-gradient-to-r from-green-600 to-emerald-600 bg-clip-text text-transparent">Rp
                                        550.000</span>
                                </div>
                            </div>
                            <div class="p-2 bg-gradient-to-br from-blue-400 to-indigo-500 rounded-xl">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div class="flex-auto p-4">
                        <div class="bg-white/50 dark:bg-slate-700/50 rounded-2xl p-4">
                            <canvas id="savingsChart" class="h-[200px] lg:h-[300px]"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enhanced Latest Orders -->
        <div class="flex flex-wrap mt-6 -mx-3">
            <div class="w-full max-w-full px-3 mt-0 lg:flex-none">
                <div
                    class="relative overflow-hidden flex flex-col min-w-0 mb-4 break-words bg-gradient-to-br from-white to-gray-50 dark:from-slate-800 dark:to-slate-900 shadow-2xl rounded-3xl bg-clip-border border border-gray-100 dark:border-slate-700">
                    <div class="p-6 pb-0 mb-0 rounded-t-3xl">
                        <div class="flex items-center justify-between">
                            <h5 class="text-xl font-bold text-gray-800 dark:text-white">Latest Orders</h5>
                            <div class="p-2 bg-gradient-to-br from-purple-400 to-pink-500 rounded-lg">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div class="flex-auto px-0 pt-0 pb-2">
                        <div class="p-0 overflow-x-auto">
                            <table class="items-center w-full mb-0 align-top border-collapse text-slate-500">
                                <thead class="align-bottom">
                                    <tr>
                                        <th
                                            class="px-6 py-4 font-bold text-left text-gray-700 dark:text-gray-300 uppercase align-middle bg-gray-50 dark:bg-slate-700 border-b border-gray-200 dark:border-slate-600 text-xs tracking-wider">
                                            Nama</th>
                                        <th
                                            class="px-6 py-4 font-bold text-left text-gray-700 dark:text-gray-300 uppercase align-middle bg-gray-50 dark:bg-slate-700 border-b border-gray-200 dark:border-slate-600 text-xs tracking-wider">
                                            Alamat</th>
                                        <th
                                            class="px-6 py-4 font-bold text-center text-gray-700 dark:text-gray-300 uppercase align-middle bg-gray-50 dark:bg-slate-700 border-b border-gray-200 dark:border-slate-600 text-xs tracking-wider">
                                            Pesanan</th>
                                        <th
                                            class="px-6 py-4 font-bold text-center text-gray-700 dark:text-gray-300 uppercase align-middle bg-gray-50 dark:bg-slate-700 border-b border-gray-200 dark:border-slate-600 text-xs tracking-wider">
                                            Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors duration-200">
                                        <td
                                            class="p-4 align-middle bg-transparent border-b dark:border-slate-600 whitespace-nowrap">
                                            <div class="flex px-2 py-1">
                                                <div class="flex flex-col justify-center">
                                                    <h6
                                                        class="mb-0 text-sm font-semibold leading-normal text-gray-800 dark:text-white">
                                                        John Michael</h6>
                                                    <p class="mb-0 text-xs leading-tight text-gray-500 dark:text-gray-400">
                                                        john@creative-tim.com</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td
                                            class="p-4 align-middle bg-transparent border-b dark:border-slate-600 whitespace-nowrap">
                                            <p class="mb-0 text-sm font-medium text-gray-700 dark:text-gray-300">Gampeng
                                            </p>
                                        </td>
                                        <td class="p-4 bg-transparent border-b dark:border-slate-600 whitespace-nowrap">
                                            <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Tumpeng Kue
                                                Pandan</span>
                                        </td>
                                        <td
                                            class="p-4 text-sm leading-normal text-center align-middle bg-transparent border-b dark:border-slate-600 whitespace-nowrap">
                                            <span
                                                class="bg-gradient-to-r from-green-500 to-emerald-500 px-3 py-2 text-xs rounded-full font-bold uppercase text-white shadow-lg">TERKIRIM</span>
                                        </td>
                                    </tr>
                                    <tr class="hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors duration-200">
                                        <td
                                            class="p-4 align-middle bg-transparent border-b dark:border-slate-600 whitespace-nowrap">
                                            <div class="flex px-2 py-1">
                                                <div class="flex flex-col justify-center">
                                                    <h6
                                                        class="mb-0 text-sm font-semibold leading-normal text-gray-800 dark:text-white">
                                                        John Michael</h6>
                                                    <p class="mb-0 text-xs leading-tight text-gray-500 dark:text-gray-400">
                                                        john@creative-tim.com</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td
                                            class="p-4 align-middle bg-transparent border-b dark:border-slate-600 whitespace-nowrap">
                                            <p class="mb-0 text-sm font-medium text-gray-700 dark:text-gray-300">Gampeng
                                            </p>
                                        </td>
                                        <td class="p-4 bg-transparent border-b dark:border-slate-600 whitespace-nowrap">
                                            <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Kue
                                                Manis</span>
                                        </td>
                                        <td
                                            class="p-4 text-sm leading-normal text-center align-middle bg-transparent border-b dark:border-slate-600 whitespace-nowrap">
                                            <span
                                                class="bg-gradient-to-r from-red-500 to-pink-500 px-3 py-2 text-xs rounded-full font-bold uppercase text-white shadow-lg">GAGAL</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
    <!-- end cards -->

    @include('dashboard.kurir.customers.create')

    <!-- Enhanced Scripts -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Modal functionality
            const modalToggle = document.querySelector('[data-modal-toggle="crud-modal"]');
            const modal = document.getElementById('crud-modal');

            if (modalToggle && modal) {
                modalToggle.addEventListener('click', function() {
                    modal.classList.remove('hidden');
                });

                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        modal.classList.add('hidden');
                    }
                });

                const closeButton = modal.querySelector('[data-modal-toggle="crud-modal"]');
                if (closeButton) {
                    closeButton.addEventListener('click', function() {
                        modal.classList.add('hidden');
                    });
                }
            }

            // Enhanced Chart
            var ctx = document.getElementById('savingsChart');
            if (ctx && window.Chart) {
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May'],
                        datasets: [{
                            label: 'Income',
                            data: [20, 110, 55, 100, 30],
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: '#3b82f6',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 3,
                            pointRadius: 6,
                            pointHoverRadius: 8,
                            pointHoverBackgroundColor: '#1d4ed8',
                            pointHoverBorderColor: '#fff',
                            pointHoverBorderWidth: 3
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
                                        return 'Rp ' + context.parsed.y + 'K';
                                    }
                                },
                                backgroundColor: 'rgba(255, 255, 255, 0.95)',
                                titleColor: '#1f2937',
                                bodyColor: '#374151',
                                borderColor: '#3b82f6',
                                borderWidth: 2,
                                displayColors: false,
                                padding: 12,
                                cornerRadius: 8
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    color: '#6b7280',
                                    callback: function(value) {
                                        return value + 'K';
                                    }
                                },
                                grid: {
                                    color: 'rgba(107, 114, 128, 0.1)',
                                    drawBorder: false
                                }
                            },
                            x: {
                                ticks: {
                                    color: '#6b7280'
                                },
                                grid: {
                                    display: false
                                }
                            }
                        }
                    }
                });
            }

            // Time-based greeting
            function getGreeting() {
                const hour = new Date().getHours();
                if (hour >= 4 && hour < 11) return "Selamat Pagi 🌤️";
                else if (hour >= 11 && hour < 15) return "Selamat Siang ☀️";
                else if (hour >= 15 && hour < 18) return "Selamat Sore 🌇";
                else return "Selamat Malam 🌙";
            }

            document.getElementById("ucapan").textContent = getGreeting();
        });
    </script>

    <script src="/assets/argon/js/plugins/chartjs.min.js"></script>
    <script src="/assets/argon/js/plugins/perfect-scrollbar.min.js" async></script>
    <script src="/assets/argon/js/assets/argon-dashboard-tailwind.js?v=1.0.1" async></script>

@endsection
