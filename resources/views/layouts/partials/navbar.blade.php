<script>
  // Sinkronisasi toggle light/dark mode di navbar dan sidebar (SUDAH DIPERBAIKI)
  document.addEventListener('DOMContentLoaded', function() {
    var navToggle = document.getElementById('theme-toggle-checkbox-navbar');
    var sideToggle = document.getElementById('theme-toggle-checkbox-sidebar');

    if (!navToggle || !sideToggle) return;

    function setBoth(isDark) {
      navToggle.checked = isDark;
      sideToggle.checked = isDark;
      // Perubahan visual utama (menambah/menghapus kelas 'dark')
      document.documentElement.classList.toggle('dark', isDark);
    }

    // Sinkronisasi event listener
    navToggle.addEventListener('change', function() {
        setBoth(this.checked);
        // PERBAIKAN: Gunakan 'color-theme' sebagai key
        localStorage.setItem('color-theme', this.checked ? 'dark' : 'light');
    });

    sideToggle.addEventListener('change', function() {
        setBoth(this.checked);
        // PERBAIKAN: Gunakan 'color-theme' sebagai key
        localStorage.setItem('color-theme', this.checked ? 'dark' : 'light');
    });

    // Inisialisasi state toggle berdasarkan localStorage saat halaman dimuat
    // PERBAIKAN: Baca dari 'color-theme'
    var savedTheme = localStorage.getItem('color-theme');
    if (savedTheme === 'dark') {
      setBoth(true);
    } else {
      setBoth(false);
    }
  });
</script>
{{-- FIXED: Fixed navbar with proper alignment between breadcrumb and profile --}}
<nav id="navbar-main" class="fixed top-0 left-0 right-0 z-40 flex items-center justify-between px-0 py-0 transition-all duration-300 ease-in lg:flex-nowrap lg:justify-start bg-greenlight dark:bg-slate-900 dark:shadow-none" navbar-main navbar-scroll="true">
    <div class="flex items-center justify-between w-full h-20 px-6">

      {{-- Left side: Mobile toggle + Breadcrumb --}}
      <div class="flex items-center flex-grow h-full">
        {{-- Mobile Hamburger Toggle Button --}}
  <a href="javascript:;" class="flex items-center justify-center p-2 text-white transition-all ease-nav-brand lg:hidden" id="mobile-toggle" sidenav-trigger>
            <i class="text-xl fas fa-bars"></i>
        </a>
@push('page-scripts')
<script>
  // Hamburger menu auto hide saat sidebar terbuka (mobile & tablet)
  document.addEventListener('DOMContentLoaded', function() {
    var hamburger = document.getElementById('mobile-toggle');
    var sidebar = document.getElementById('sidebar');
    function updateHamburger() {
      if (!hamburger || !sidebar) return;
      // Hanya jalankan di layar < 1280px (xl)
      if (window.innerWidth < 1280) {
        var isSidebarOpen = !sidebar.classList.contains('-translate-x-full');
        if (isSidebarOpen) {
          hamburger.classList.add('hidden');
        } else {
          hamburger.classList.remove('hidden');
        }
      } else {
        // Di desktop, pastikan hamburger selalu hidden
        hamburger.classList.add('hidden');
      }
    }
    // Pantau perubahan class sidebar dan resize
    const observer = new MutationObserver(updateHamburger);
    observer.observe(sidebar, { attributes: true, attributeFilter: ['class'] });
    window.addEventListener('resize', updateHamburger);
    updateHamburger();
  });
</script>
@endpush

        {{-- Breadcrumb Navigation --}}
  <div class="flex-col justify-center flex-grow hidden h-full ml-4 xl:flex xl:ml-0">
          <ol class="flex flex-wrap bg-transparent rounded-lg">
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
       </div>
      </div>

      {{-- Toogle Lightmode - Darkmode --}}
  <!-- Toggle Lightmode/Darkmode: hanya tampil di desktop/tab -->
  <label id="theme-toggle-label-navbar" for="theme-toggle-checkbox-navbar" class="relative z-40 inline-flex items-center hidden cursor-pointer xl:inline-flex">
    <input type="checkbox" value="" id="theme-toggle-checkbox-navbar" class="sr-only peer" dark-toggle>
    <div class="h-6 bg-gray-200 rounded-full w-11 peer dark:bg-gray-700 peer-checked:bg-blue-600"></div>
    <div class="absolute top-0.5 left-[2px] bg-white border-gray-300 border rounded-full h-5 w-5 transition-all peer-checked:translate-x-full flex items-center justify-center">
      <span class="text-sm">☀️</span>
      <span class="hidden text-sm">🌙</span>
    </div>
  </label>

      {{-- Right side: Profile section - visible on all devices with proper margin --}}
      <div class="flex items-center justify-end h-full pr-4">
        <ul class="flex flex-row items-center justify-end h-full pl-0 mb-0 list-none">
          <!-- Avatar with dropdown settings -->
          <li class="relative flex items-center h-full px-3 group">
            <div class="w-8 h-8 overflow-hidden border-2 border-white rounded-full cursor-pointer">
              @php
                $avatarSrc = '/assets/icon/admin.png';
                if (Auth::user() && Auth::user()->hasRole('kurir')) {
                  $avatarSrc = '/assets/icon/kurir.png';
                }
              @endphp
              <img src="{{ asset($avatarSrc) }}" alt="User Avatar" class="object-cover w-full h-full" />
            </div>
            <div class="absolute right-0 z-[99999] w-64 mt-2 transition duration-200 ease-out origin-top-right scale-95 bg-white rounded-md shadow-lg opacity-0 pointer-events-none group-hover:opacity-100 group-hover:scale-100 group-hover:pointer-events-auto">
              <div class="px-4 py-3 text-sm text-gray-700 border-b border-gray-200">
                @php
                  use Illuminate\Support\Facades\DB;
                  $user = Auth::user();
                  $lastSession = DB::table('sessions')
                    ->where('user_id', Auth::id())
                    ->orderByDesc('last_activity')
                    ->first();
                  $lastLogin = $lastSession ? \Carbon\Carbon::createFromTimestamp($lastSession->last_activity)->setTimezone('Asia/Jakarta')->format('d M Y, H:i:s') : 'Tidak tersedia';
                @endphp
                <div class="mb-2">
                  <span class="font-semibold text-gray-900">{{ $user->name ?? 'User' }}</span>
                  <span class="block mt-1 text-xs text-gray-500">📍 Region: {{ $user->region->name ?? 'Tidak ada region' }}</span>
                  <span class="block text-xs text-gray-500">Email: {{ $user->email ?? 'Tidak ada email' }}</span>
                </div>
                <div class="pt-2 border-t border-gray-100">
                  <span class="text-sm font-semibold text-gray-900">👨🏻‍💻 Last Activity:</span>
                  <span class="block mt-1 text-sm font-medium text-gray-700">{{ $lastLogin }}</span>
                </div>
              </div>
              @php
                $profileUrl = route('profile.show');
                if (Auth::user() && Auth::user()->hasRole('admin')) {
                  $profileUrl = url('/admin/profile');
                } elseif (Auth::user() && Auth::user()->hasRole('kurir')) {
                  $profileUrl = url('/kurir/profile');
                }
              @endphp
              <a href="{{ $profileUrl }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">👤 My Profile</a>
              <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="block w-full px-4 py-2 text-sm text-left text-gray-700 hover:bg-gray-100">🏃 Logout</button>
              </form>
            </div>
          </li>
        </ul>
      </div>
    </div>
</nav>
