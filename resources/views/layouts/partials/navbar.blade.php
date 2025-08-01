{{-- FIXED: Added flex-wrap, responsive widths, and updated breakpoints --}}
<nav class="relative flex flex-wrap items-center justify-between px-0 py-2 mx-6 transition-all ease-in shadow-none duration-250 rounded-2xl lg:flex-nowrap lg:justify-start" navbar-main navbar-scroll="false">
  <div class="flex flex-wrap items-center justify-between w-full px-4 py-1 mx-auto">
{{-- <nav class="relative flex flex-wrap items-center justify-between px-0 py-2 mx-6 transition-all ease-in shadow-none duration-250 rounded-2xl lg:flex-nowrap lg:justify-start" navbar-main navbar-scroll="false">
  <div class="flex flex-wrap items-center justify-between w-full px-4 py-1 mx-auto"> --}}
 
<nav>
      <ol class="flex flex-wrap pt-1 mr-12 bg-transparent rounded-lg sm:mr-16">
        <li class="text-sm leading-normal">
          @php
            $user = Auth::user();
            $region = $user->region ?? null;
            $homeUrl = url('/dashboard');
            if ($user && $region) {
              if ($user->hasRole('admin')) {
                $homeUrl = route('admin.dashboard', ['region' => $region]);
              } elseif ($user->hasRole('kurir')) {
                $homeUrl = route('kurir.dashboard', ['region' => $region]);
              }
            }
          @endphp
          <a class="text-white opacity-50" href="{{ $homeUrl }}">Dashboard</a>
        </li>
        <li class="text-sm pl-2 capitalize leading-normal text-white before:float-left before:pr-2 before:text-white before:content-['/']" aria-current="page">
          @yield('page_title', 'Dashboard')
        </li>
      </ol>
      <h6 class="mb-0 font-bold text-white capitalize">@yield('page_title', 'Dashboard')</h6>
    </nav>

    <div class="flex items-center justify-end mt-2 sm:mt-0 sm:mr-6 md:mr-0 lg:w-auto lg:flex lg:basis-auto">
    {{-- <div class="flex items-center w-full mt-2 grow sm:mt-0 sm:mr-6 md:mr-0 lg:w-auto lg:flex lg:basis-auto"> --}}
      <ul class="flex flex-row justify-end pl-0 mb-0 list-none md-max:w-full items-center">
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
        <!-- Avatar with dropdown settings -->
        <li class="flex items-center px-2 relative group">
          <div class="w-8 h-8 rounded-full overflow-hidden border-2 border-white cursor-pointer">
            @php
              $avatarSrc = '/assets/icon/admin.png';
              if (Auth::user() && Auth::user()->hasRole('kurir')) {
                $avatarSrc = '/assets/icon/kurir.png';
              }
            @endphp
            <img src="{{ asset($avatarSrc) }}" alt="User Avatar" class="w-full h-full object-cover" />
          </div>
          <div class="absolute right-0 mt-40 w-64 bg-white rounded-md shadow-lg z-50 opacity-0 group-hover:opacity-100 group-hover:scale-100 scale-95 transition duration-200 ease-out origin-top-right pointer-events-none group-hover:pointer-events-auto">
            <div class="py-2 px-4 border-b text-gray-700 text-sm">
              @php
                use Illuminate\Support\Facades\DB;
                $user = Auth::user();
                $lastSession = DB::table('sessions')
                  ->where('user_id', Auth::id())
                  ->orderByDesc('last_activity')
                  ->first();
                $lastLogin = $lastSession ? \Carbon\Carbon::createFromTimestamp($lastSession->last_activity)->setTimezone('Asia/Jakarta')->format('d-m-Y H:i:s') : '-';
              @endphp
              <span class="font-semibold">Name - {{ $user->name ?? '-' }}</span>
              <span class="block text-xs text-gray-500">Region : {{ $user->region ?? '-' }}</span>
              <span class="block text-xs text-gray-500 mb-1">Email : {{ $user->email ?? '-' }}</span>
              <div class="border-b border-gray-200 my-2"></div>
              <span class="font-semibold">Last Activity:</span>
              <span class="block mt-1">{{ $lastLogin }}</span>
            </div>
            @php
              $profileUrl = route('profile.show');
              if (Auth::user() && Auth::user()->hasRole('admin')) {
                $profileUrl = url('/admin/profile');
              } elseif (Auth::user() && Auth::user()->hasRole('kurir')) {
                $profileUrl = url('/kurir/profile');
              }
            @endphp
            <a href="{{ $profileUrl }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">My Profile</a>
            <form method="POST" action="{{ route('logout') }}">
              @csrf
              <button type="submit" class="block w-full px-4 py-2 text-sm text-left text-gray-700 hover:bg-gray-100">Logout</button>
            </form>
          </div>
        </li>
      </ul>
    </div>
  </div>
</nav>
