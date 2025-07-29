{{-- FIXED: Added flex-wrap, responsive widths, and updated breakpoints --}}
<nav class="relative flex flex-wrap items-center justify-between px-0 py-2 mx-6 transition-all ease-in shadow-none duration-250 rounded-2xl lg:flex-nowrap lg:justify-start" navbar-main navbar-scroll="false">
  <div class="flex flex-wrap items-center justify-between w-full px-4 py-1 mx-auto">    <nav>
      <ol class="flex flex-wrap pt-1 mr-12 bg-transparent rounded-lg sm:mr-16">
        <li class="text-sm leading-normal">
          <a class="text-white opacity-50" href="javascript:;">Pages</a>
        </li>
        <li class="text-sm pl-2 capitalize leading-normal text-white before:float-left before:pr-2 before:text-white before:content-['/']" aria-current="page">
          @yield('page_title', 'Dashboard')
        </li>
      </ol>
      <h6 class="mb-0 font-bold text-white capitalize">@yield('page_title', 'Dashboard')</h6>
    </nav>
    {{-- <nav>
      <ol class="flex flex-wrap pt-1 mr-12 bg-transparent rounded-lg sm:mr-16">
        <li class="text-sm leading-normal">
          <a class="text-white opacity-50" href="javascript:;">Pages</a>
        </li>
        <li class="text-sm pl-2 capitalize leading-normal text-white before:float-left before:pr-2 before:text-white before:content-['/']" aria-current="page">Dashboard</li>
      </ol>
      <h6 class="mb-0 font-bold text-white capitalize">Dashboard</h6>
    </nav> --}}

    <div class="flex items-center w-full mt-2 grow sm:mt-0 sm:mr-6 md:mr-0 lg:w-auto lg:flex lg:basis-auto">
    {{-- <div class="flex items-center w-full mt-2 grow sm:mt-0 sm:mr-6 md:mr-0 lg:w-auto lg:flex lg:basis-auto"> --}}
      <div class="flex items-center md:ml-auto md:pr-4">
        <div class="relative flex flex-wrap items-stretch w-full transition-all rounded-lg ease">
          <span class="text-sm ease leading-5.6 absolute z-50 -ml-px flex h-full items-center whitespace-nowrap rounded-lg rounded-tr-none rounded-br-none border border-r-0 border-transparent bg-transparent py-2 px-2.5 text-center font-normal text-slate-500 transition-all">
            <i class="fas fa-search"></i>
          </span>
          <input type="text" class="pl-9 text-sm focus:shadow-primary-outline ease w-full leading-5.6 relative -ml-px block min-w-0 flex-auto rounded-lg border border-solid border-gray-300 dark:bg-slate-850 dark:text-white bg-white bg-clip-padding py-2 pr-3 text-gray-700 transition-all placeholder:text-gray-500 focus:border-blue-500 focus:outline-none focus:transition-shadow" placeholder="Type here..." />
        </div>
      </div>
      <ul class="flex flex-row justify-end pl-0 mb-0 list-none md-max:w-full">
        {{-- @auth
        <li class="relative flex items-center">
          <div class="relative inline-block text-left group">
            <button type="button" class="inline-flex items-center text-sm font-semibold text-white focus:outline-none" id="menu-button" aria-expanded="true" aria-haspopup="true">
              <i class="fa fa-user sm:mr-1"></i>
              <span class="hidden sm:inline">{{ Auth::user()->name }}</span>
              <svg class="w-4 h-4 ml-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>
            <div class="absolute right-0 z-50 w-48 mt-2 transition duration-200 ease-out origin-top-right transform scale-95 bg-white rounded-md shadow-lg opacity-0 ring-1 ring-black ring-opacity-5 group-hover:opacity-100 group-hover:scale-100">
              <div class="py-1" role="none">
                <a href="{{ route('profile.show') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">Profil</a>
                <form method="POST" action="{{ route('logout') }}">
                  @csrf
                  <button type="submit" class="block w-full px-4 py-2 text-sm text-left text-gray-700 hover:bg-gray-100" role="menuitem">
                    Logout
                  </button>
                </form>
              </div>
            </div>
          </div>
        </li>
        @endauth --}}

        <li class="flex items-center pl-4 lg:hidden">
          <a href="javascript:;" class="block p-0 text-sm text-white transition-all ease-nav-brand" sidenav-trigger>
            <div class="w-4.5 overflow-hidden">
              <i class="ease mb-0.75 relative block h-0.5 rounded-sm bg-white transition-all"></i>
              <i class="ease mb-0.75 relative block h-0.5 rounded-sm bg-white transition-all"></i>
              <i class="ease relative block h-0.5 rounded-sm bg-white transition-all"></i>
            </div>
          </a>
        </li>
        <li class="flex items-center px-4">
          <a href="javascript:;" class="p-0 text-sm text-white transition-all ease-nav-brand">
            <i fixed-plugin-button-nav class="cursor-pointer fa fa-cog"></i>
          </a>
        </li>
        <li class="relative flex items-center pr-2">
          <a href="javascript:;" class="block p-0 text-sm text-white transition-all ease-nav-brand" dropdown-trigger aria-expanded="false">
            <i class="cursor-pointer fa fa-bell"></i>
          </a>
        </li>
      </ul>
    </div>
  </div>
</nav>
