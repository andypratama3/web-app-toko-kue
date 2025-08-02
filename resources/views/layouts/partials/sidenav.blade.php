<aside
    class="fixed inset-y-0 z-50 flex-wrap items-center justify-between block w-full p-0 my-4 overflow-y-auto antialiased transition-transform duration-200 -translate-x-full bg-white border-0 shadow-xl dark:shadow-none dark:bg-slate-850 max-w-64 ease-nav-brand xl:ml-6 rounded-2xl xl:left-0 xl:translate-x-0"
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

<div class="h-19">
  <i class="absolute top-0 right-0 p-4 opacity-50 cursor-pointer fas fa-times dark:text-white text-slate-400 xl:hidden" sidenav-close></i>
  
  <a class="block px-8 py-6 m-0 text-base whitespace-nowrap dark:text-white text-slate-700 flex items-center" href="{{ $dashboardUrl }}">
    <img src="{{ asset('assets/homepage/logo.png') }}" class="h-8 w-auto transition-all duration-200 ease-nav-brand" alt="main_logo" />
    <span class="ml-2 font-semibold text-xl transition-all duration-200 ease-nav-brand text-greenlight">
      Kue Pandan Asli
    </span>
  </a>
</div>


<hr class="h-px mt-0 bg-transparent bg-gradient-to-r from-transparent via-black/40 to-transparent dark:bg-gradient-to-r dark:from-transparent dark:via-white dark:to-transparent" />

<div class="items-center block w-auto max-h-screen overflow-auto h-sidenav grow basis-full">
<ul class="flex flex-col pl-0 mb-0">
<li class="mt-0.5 w-full">
<a class="py-2.7 text-sm ease-nav-brand my-0 mx-2 flex items-center whitespace-nowrap rounded-lg px-4 font-semibold text-slate-700 transition-colors @if (request()->routeIs('admin.dashboard') || request()->routeIs('kurir.dashboard')) bg-blue-500/13 dark:text-white dark:opacity-80 @else dark:text-white dark:opacity-80 @endif" href="{{ $dashboardUrl }}">
<div class="mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-center text-center xl:p-2.5">
<i class="fas fa-house-user @if (request()->routeIs('admin.dashboard') || request()->routeIs('kurir.dashboard')) text-blue-500 @else text-slate-400 @endif"></i>
</div>
<span class="ml-1 duration-300 opacity-100 pointer-events-none ease">Dashboard</span>
</a>
</li>

@role('admin')
{{-- PERBAIKAN: Setiap link <a> kini berada dalam <li> nya sendiri --}}
<li class="mt-0.5 w-full">
{{-- <a class="dark:text-white dark:opacity-80 py-2.7 text-sm ease-nav-brand my-0 mx-2 flex items-center whitespace-nowrap px-4 transition-colors @if(request()->routeIs('admin.customers.*')) bg-blue-500/13 @endif" href="{{ route('admin.customers.index') }}"> --}}
<a class="dark:text-white dark:opacity-80 py-2.7 text-sm ease-nav-brand my-0 mx-2 flex items-center whitespace-nowrap px-4 transition-colors @if(request()->routeIs('admin.customers.*')) bg-blue-500/13 @endif" href="#">
<div class="mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-center text-center xl:p-2.5">
<i class="text-orange-500 fas fa-users"></i>
</div>
<span class="ml-1 duration-300 opacity-100 pointer-events-none ease">Manajemen Customer</span>
</a>
</li>
<li class="mt-0.5 w-full">
<a class="dark:text-white dark:opacity-80 py-2.7 text-sm ease-nav-brand my-0 mx-2 flex items-center whitespace-nowrap px-4 transition-colors @if(request()->routeIs('admin.products.*')) bg-blue-500/13 @endif" href="{{ route('admin.products.index') }}">
<div class="mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-center text-center xl:p-2.5">
<i class="text-orange-500 fas fa-store"></i>
</div>
<span class="ml-1 duration-300 opacity-100 pointer-events-none ease">Manajemen Produk</span>
</a>
</li>
<li class="mt-0.5 w-full">
<a class="dark:text-white dark:opacity-80 py-2.7 text-sm ease-nav-brand my-0 mx-2 flex items-center whitespace-nowrap px-4 transition-colors @if(request()->routeIs('admin.couriers.*')) bg-blue-500/13 @endif" href="{{ route('admin.couriers.index') }}">
<div class="mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-center text-center xl:p-2.5">
<i class="text-cyan-500 fas fa-truck"></i>
</div>
<span class="ml-1 duration-300 opacity-100 pointer-events-none ease">Manajemen Kurir</span>
</a>
</li>
<li class="mt-0.5 w-full">
<a class="dark:text-white dark:opacity-80 py-2.7 text-sm ease-nav-brand my-0 mx-2 flex items-center whitespace-nowrap px-4 transition-colors" href="#">
<div class="mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-center text-center xl:p-2.5">
<i class="text-emerald-500 fas fa-cart-arrow-down"></i>
</div>
<span class="ml-1 duration-300 opacity-100 pointer-events-none ease">Manajemen Pesanan</span>
</a>
</li>
<li class="mt-0.5 w-full">
<a class="dark:text-white dark:opacity-80 py-2.7 text-sm ease-nav-brand my-0 mx-2 flex items-center whitespace-nowrap px-4 transition-colors" href="#">
<div class="mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-center text-center xl:p-2.5">
<i class="text-red-500 fas fa-list"></i>
</div>
<span class="ml-1 duration-300 opacity-100 pointer-events-none ease">History Pesanan</span>
</a>
</li>
<li class="w-full mt-4 mb-4">
<h6 class="pl-6 ml-2 text-xs font-bold leading-tight uppercase dark:text-white opacity-60">Account Pages</h6>
</li>
<li class="mt-0.5 w-full">
<a class="py-2.7 text-sm ease-nav-brand my-0 mx-2 flex items-center whitespace-nowrap px-4 transition-colors rounded-lg font-semibold
@if (request()->routeIs('admin.profile')) bg-blue-500/13 text-blue-700 dark:text-white dark:opacity-80 @else dark:text-white dark:opacity-80 @endif"
href="{{ route('admin.profile') }}">
<div class="mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-center text-center xl:p-2.5">
<i class="fas fa-user @if (request()->routeIs('admin.profile')) text-blue-500 @else text-slate-700 @endif"></i>
</div>
<span class="ml-1 duration-300 opacity-100 pointer-events-none ease">Profil Saya</span>
</a>
</li>
@endrole

@role('kurir')
<li class="mt-0.5 w-full">
<a class="dark:text-white dark:opacity-80 py-2.7 text-sm ease-nav-brand my-0 mx-2 flex items-center whitespace-nowrap px-4 transition-colors"
href="#">
{{-- href="{{ route('kurir.pages.data-customer', ['region' => $kurir->region]) }}"> --}}
<div class="mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-center text-center xl:p-2.5">
<i class="text-red-600 fas fa-book"></i>
</div>
<span class="ml-1 duration-300 opacity-100 pointer-events-none ease">Data Customer</span>
</a>
</li>
<li class="mt-0.5 w-full">
<a class="dark:text-white dark:opacity-80 py-2.7 text-sm ease-nav-brand my-0 mx-2 flex items-center whitespace-nowrap px-4 transition-colors"
href="{{ url('/pengiriman') }}">
<div class="mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-center text-center xl:p-2.5">
<i class="fas fa-folder text-greenlight"></i>
</div>
<span class="ml-1 duration-300 opacity-100 pointer-events-none ease">History Pesanan</span>
</a>

</li>

<!-- kurir ke profile -->
<li class="w-full mt-4 mb-4">
<h6 class="pl-6 ml-2 text-xs font-bold leading-tight uppercase dark:text-white opacity-60">Account Pages</h6>
</li>
<li class="mt-0.5 w-full">
<a class="py-2.7 text-sm ease-nav-brand my-0 mx-2 flex items-center whitespace-nowrap px-4 transition-colors rounded-lg font-semibold
@if (request()->routeIs('kurir.profile')) bg-blue-500/13 text-blue-700 dark:text-white dark:opacity-80 @else dark:text-white dark:opacity-80 @endif"
href="{{ route('kurir.profile') }}">
<div class="mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-center text-center xl:p-2.5">
{{-- PERBAIKAN: Menggunakan ikon Nucleo --}}
<i class="ni ni-single-02 @if (request()->routeIs('kurir.profile')) text-blue-500 @else text-slate-700 @endif"></i>
</div>
<span class="ml-1 duration-300 opacity-100 pointer-events-none ease">Profil Saya</span>
</a>
</li>
@endrole

<li class="mt-0.5 w-full">
<form method="POST" action="{{ route('logout') }}" class="inline">
@csrf
<a class="dark:text-white dark:opacity-80 py-2.7 text-sm ease-nav-brand my-0 mx-2 flex items-center whitespace-nowrap px-4 transition-colors" href="{{ route('logout') }}" onclick="event.preventDefault(); this.closest('form').submit();">
<div class="mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-center text-center xl:p-2.5">
<i class="text-red-600 fas fa-sign-out-alt"></i>
</div>
<span class="ml-1 duration-300 opacity-100 pointer-events-none ease">Logout</span>
</a>
</form>
</li>
</ul>
</div>
@endauth
</aside>