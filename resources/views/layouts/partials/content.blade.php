{{--
    Container konten admin.
    Padding mobile-first: p-3 → sm:p-4 → md:p-6 → lg:p-8
    min-w-0 + overflow guard mencegah anak yang lebar (tabel, flex)
    memaksa seluruh halaman scroll horizontal.
--}}
<div class="container px-3 mx-auto mt-3 sm:px-4 sm:mt-4 md:px-6 md:mt-6 lg:px-8">
    <div id="tab-content"
        class="admin-shell bg-white rounded-xl shadow p-3 min-h-[833px] transition-transform duration-500 ease-in-out transform translate-x-full opacity-0 sm:p-4 md:p-6 lg:p-8 dark:bg-gray-900">
        <h2 class="mb-4 text-lg font-bold text-greenlight sm:mb-6 sm:text-xl md:text-2xl dark:text-white">
            @yield('title')
        </h2>
        <div class="my-2 mb-4 border-b border-gray-200 sm:mb-6 dark:border-gray-800"></div>
        @yield('content')
    </div>
</div>
