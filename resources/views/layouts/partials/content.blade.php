<div class="container mx-auto px-4 mt-4">
    <div id="tab-content" class="bg-white rounded-xl shadow p-8 min-h-[400px] transition-transform duration-500 ease-in-out transform translate-x-full opacity-0">
        <h2 class="text-2xl font-bold text-greenlight mb-6">@yield('title')</h2>
        <div class="border-b border-gray-200 my-2 mb-6"></div>
        @yield('content')
    </div>
</div>