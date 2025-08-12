@extends('layouts.argon')
@section('title', 'Dashboard Kurir')
@section('content')

<div class="flex flex-wrap -mx-3">
    <div class="flex-none w-full max-w-full px-3">
        <div class="p-3 pb-0 mb-0 border-b-0 border-b-solid rounded-t-2xl border-b-transparent">
            <h6 class="dark:text-white mb-4">Data Pesanan</h6>
            
            <!-- Mobile Search Bar (always visible at the top on mobile, hidden on md and up) -->
            <div class="flex justify-end mb-4 md:hidden">
                <form id="mobile-search-form" class="flex w-full items-center justify-end">
                    <label for="simple-search-mobile" class="sr-only">Search</label>
                    <div class="relative w-full"> 
                        <input type="text" id="simple-search-mobile" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-3 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                            placeholder="Search here..." required />
                    </div>
                    <button type="submit" class="px-4 py-2.5 ms-2 text-sm font-medium text-white bg-blue-700 rounded-lg border border-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                        <svg class="w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z" />
                        </svg>
                        <span class="sr-only">Search</span>
                    </button>
                </form>
            </div>

            <!-- Tab Menu and Desktop Search Section -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
                <!-- Tab Navigation -->
                <div class="flex-grow flex border-b border-gray-200 dark:border-gray-700">
                    <button id="all-orders-tab" class="py-2 px-4 text-sm font-medium text-center rounded-t-lg text-gray-900 dark:text-white border-b-2 border-transparent hover:text-[#3c4c34] hover:border-[#3c4c34] active-tab">
                        All Orders
                    </button>
                    <button id="shipped-tab" class="py-2 px-4 text-sm font-medium text-center rounded-t-lg text-gray-500 dark:text-gray-400 border-b-2 border-transparent hover:text-[#3c4c34] hover:border-[#3c4c34]">
                        Dikirim
                    </button>
                    <button id="received-tab" class="py-2 px-4 text-sm font-medium text-center rounded-t-lg text-gray-500 dark:text-gray-400 border-b-2 border-transparent hover:text-[#3c4c34] hover:border-[#3c4c34]">
                        Diterima
                    </button>
                    <button id="failed-tab" class="py-2 px-4 text-sm font-medium text-center rounded-t-lg text-gray-500 dark:text-gray-400 border-b-2 border-transparent hover:text-[#3c4c34] hover:border-[#3c4c34]">
                        Gagal
                    </button>
                </div>
                
                <!-- Desktop Search Form (hidden on sm) -->
                <form class="hidden md:flex w-full md:w-80"> {{-- Hidden on mobile, flex on md and up --}}
                    <label for="simple-search-desktop" class="sr-only">Search</label>
                    <div class="relative w-full">
                        <input type="text" id="simple-search-desktop" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-3 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                            placeholder="Search here..." required />
                    </div>
                    <button type="submit" class="px-4 py-2.5 ms-2 text-sm font-medium text-white bg-blue-700 rounded-lg border border-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                        <svg class="w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z" />
                        </svg>
                        <span class="sr-only">Search</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Original scripts commented out as they are not directly related to the new features --}}
{{-- <script src="/assets/argon/js/plugins/chartjs.min.js"></script>
<script src="/assets/argon/js/plugins/perfect-scrollbar.min.js" async></script>
<script src="/assets/argon/js/assets/argon-dashboard-tailwind.js?v=1.0.1" async></script> --}}

<style>
    /* Styling for the active tab to give it a background and underline */
    .active-tab {
        border-bottom-color: #3c4c34; /* Custom dark green color */
        color: #3c4c34; /* Active text color */
        background-color: rgba(60, 76, 52, 0.1); /* Subtle background for active state using RGB of #3c4c34 */
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const tabs = document.querySelectorAll('button[id$="-tab"]'); // Selects all buttons whose ID ends with "-tab"

        // Set the "All Orders" tab as active by default on page load
        const defaultActiveTab = document.getElementById('all-orders-tab');
        if (defaultActiveTab) {
            defaultActiveTab.classList.add('active-tab');
            defaultActiveTab.classList.remove('text-gray-500', 'dark:text-gray-400');
            defaultActiveTab.classList.add('text-gray-900', 'dark:text-white');
        }

        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                // Remove active class from all tabs
                tabs.forEach(t => {
                    t.classList.remove('active-tab');
                    t.classList.remove('text-gray-900', 'dark:text-white');
                    t.classList.add('text-gray-500', 'dark:text-gray-400'); // Revert to inactive text color
                });
                // Add active class to the clicked tab
                this.classList.add('active-tab');
                this.classList.remove('text-gray-500', 'dark:text-gray-400'); // Remove inactive text color
                this.classList.add('text-gray-900', 'dark:text-white'); // Apply active text color
            });
        });

        // The mobile search toggle logic has been removed as per the request
    });
</script>

@endsection
