<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Homepage - Kue Pandan Asli Malang</title>
  <style>[x-cloak] { display: none !important; }</style>
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>

<body>
  <nav id="navbar" class="w-full bg-white shadow-sm px-6 py-3 flex items-center justify-between fixed top-0 left-0 z-50 transition-all duration-300">
    <!-- Logo Kiri -->
    <div class="flex items-center gap-2 pl-2 md:pl-6">
      <img src="https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=facearea&w=64&h=64&facepad=2" alt="Logo" class="h-10 w-10 rounded-full object-cover">
      <span class="font-semibold text-[#8BA870] text-xl tracking-tight">Kue Pandan Asli Malang</span>
    </div>
    <!-- Menu Tengah -->
    <ul class="hidden md:flex items-center gap-8 text-sm font-medium text-gray-700">
      <li>
        <a href="#" class="hover:text-green-600 transition">Tentang Kami</a>
      </li>
      <li>
        <a href="#" class="hover:text-green-600 transition">Produk Kami</a>
      </li>
      <li>
        <a href="#" class="hover:text-green-600 transition">Minuman</a>
      </li>
    </ul>
    <!-- Search & Order Kanan -->
    <div class="flex items-center gap-3 pr-2 md:pr-6">
      <button class="text-gray-500 hover:text-green-600 focus:outline-none">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2"/>
          <line x1="21" y1="21" x2="16.65" y2="16.65" stroke="currentColor" stroke-width="2"/>
        </svg>
      </button>
      <button class="bg-[#8BA870] text-white rounded-full px-5 py-1.5 font-medium text-sm hover:bg-[#7a965e] transition">
        Order
      </button>
    </div>
    <!-- Hamburger Menu (Mobile) -->
    <div class="md:hidden flex items-center ml-2">
      <button id="hamburger-btn" class="text-gray-600 focus:outline-none">
        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
      </button>
    </div>
  </nav>

  <!-- Mobile Menu -->
  <div id="mobile-menu" class="fixed top-[68px] left-0 w-full h-[calc(100vh-68px)] bg-gray-900/60 z-40 opacity-0 pointer-events-none transition-all duration-300">
    <div class="bg-white rounded-r-2xl shadow-xl w-4/5 max-w-xs h-full p-8 flex flex-col gap-6 text-lg font-medium text-gray-700 animate-slidein" style="animation: slidein 0.3s cubic-bezier(.4,0,.2,1)">
      <a href="#" class="hover:text-green-600 transition text-left">NasiKotak</a>
      <a href="#" class="hover:text-green-600 transition text-left">PaketKueBasah</a>
      <a href="#" class="hover:text-green-600 transition text-left">Minuman</a>
    </div>
  </div>

  <style>
    @keyframes slidein {
      from { transform: translateX(-100%); opacity: 0; }
      to { transform: translateX(0); opacity: 1; }
    }
  </style>

  <script>
    // Hamburger menu
    const hamburgerBtn = document.getElementById('hamburger-btn');
    const mobileMenu = document.getElementById('mobile-menu');
    let menuOpen = false;
    hamburgerBtn.addEventListener('click', () => {
      menuOpen = !menuOpen;
      if(menuOpen) {
        mobileMenu.classList.remove('opacity-0','pointer-events-none');
        mobileMenu.classList.add('opacity-100');
      } else {
        mobileMenu.classList.add('opacity-0','pointer-events-none');
        mobileMenu.classList.remove('opacity-100');
      }
    });
    mobileMenu.addEventListener('click', (e) => {
      if(e.target === mobileMenu) {
        menuOpen = false;
        mobileMenu.classList.add('opacity-0','pointer-events-none');
        mobileMenu.classList.remove('opacity-100');
      }
    });
    // Navbar transparan saat discroll
    const navbar = document.getElementById('navbar');
    window.addEventListener('scroll', () => {
      if(window.scrollY > 10) {
        navbar.classList.add('bg-white/80','backdrop-blur','shadow-md');
        navbar.classList.remove('bg-white');
      } else {
        navbar.classList.remove('bg-white/80','backdrop-blur');
        navbar.classList.add('bg-white');
      }
    });
  </script>

  <!-- Spacer agar konten tidak tertutup navbar -->
  <div class="h-[68px] md:h-[68px]"></div>

  <!-- Banner Section -->
  <section class="relative w-full h-[400px] md:h-[520px] flex items-center justify-center overflow-hidden">
    <img src="https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=1200&q=80" alt="Banner Dummy" class="absolute inset-0 w-full h-full object-cover object-center brightness-95">
    <div class="absolute inset-0 flex flex-col items-center justify-center z-10">
      <h1 class="text-white text-4xl md:text-6xl font-extrabold mb-4 drop-shadow-lg">Pandan Chiffon</h1>
      <p class="text-white text-lg md:text-2xl font-medium drop-shadow mb-8 max-w-2xl text-center">Kelembutan dan aroma pandan asli, cocok untuk semua acara spesial Anda.</p>
      <a href="#" class="bg-white text-[#7a965e] font-semibold px-8 py-3 rounded-full shadow hover:bg-[#8BA870] hover:text-white transition text-lg border border-[#8BA870]">Pesan Sekarang</a>
    </div>
    <div class="absolute inset-0 bg-gradient-to-t from-[#8BA870]/40 to-transparent"></div>
  </section>

<!-- About Us Section -->
<section class="w-full py-8" style="background-color: #f2f6f5;">
  <div class="w-[90%] max-w-6xl mx-auto flex flex-col md:flex-row items-center gap-12">
    <!-- Left: Image -->
    <div class="w-full md:w-1/2">
      <img 
        src="https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=600&q=80" 
        alt="About Us" 
        class="w-full h-[400px] object-cover rounded-2xl shadow-lg"
      >
    </div>
    <!-- Right: Content -->
    <div class="w-full md:w-1/2 flex flex-col items-start">
      <span class="text-[#8BA870] font-medium tracking-wider text-sm mb-2">TENTANG KAMI</span>
      <h2 class="text-3xl md:text-4xl font-bold text-[#2C3E50] mb-6">Kue Pandan Asli Malang</h2>
      <p class="text-gray-600 leading-relaxed mb-8">
        Kami adalah pembuat kue pandan artisanal yang berbasis di Malang. Setiap produk kami dibuat dengan bahan-bahan berkualitas tinggi dan menggunakan daun pandan asli untuk memberikan aroma dan rasa yang autentik. Dengan pengalaman lebih dari 10 tahun, kami berkomitmen untuk menghadirkan kelezatan dan kehangatan dalam setiap gigitan.
      </p>
      <div class="flex gap-4">
        <button class="bg-[#8BA870] text-white rounded-full px-8 py-3 font-medium hover:bg-[#7a965e] transition shadow-md">
          Hubungi Kami
        </button>
        <button class="bg-white text-[#8BA870] rounded-full px-8 py-3 font-medium hover:bg-gray-50 transition shadow-md border border-[#8BA870]">
          Lihat Menu
        </button>
      </div>
    </div>
  </div>
</section>

<!-- Products Section -->
<section class="w-full bg-white py-8">
  <div class="w-[90%] max-w-7xl mx-auto">
    <!-- Section Header -->
    <div class="text-center mb-16">
      <span class="text-[#8BA870] font-medium tracking-wider text-sm mb-3 block">PRODUK UNGGULAN</span>
      <h2 class="text-4xl md:text-5xl font-bold text-[#2C3E50] mb-6">Produk Kami</h2>
      <p class="text-gray-600 max-w-3xl mx-auto text-lg leading-relaxed">
        Nikmati berbagai varian kue pandan berkualitas tinggi yang dibuat dengan resep tradisional dan bahan-bahan pilihan
      </p>
    </div>

    <!-- Products Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
      
      <!-- Product 1: Pandan Chiffon Cake -->
      <div class="bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
        <div class="relative overflow-hidden">
          <img 
            src="https://images.unsplash.com/photo-1578985545062-69928b1d9587?auto=format&fit=crop&w=400&q=80" 
            alt="Pandan Chiffon Cake" 
            class="w-full h-48 object-cover hover:scale-105 transition-transform duration-300"
          >
          <div class="absolute top-3 right-3 bg-[#8BA870] text-white px-2 py-1 rounded-full text-xs font-medium">
            Best Seller
          </div>
        </div>
        <div class="p-5">
          <h3 class="font-bold text-lg text-[#2C3E50] mb-2">Pandan Chiffon Cake</h3>
          <p class="text-gray-600 text-sm mb-3 line-clamp-2">Kue chiffon lembut dengan aroma pandan asli yang menggugah selera</p>
          <div class="flex items-center justify-between">
            <span class="text-[#8BA870] font-bold text-lg">Rp 85.000</span>
            <button class="bg-[#8BA870] text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-[#7a965e] transition">
              Pesan
            </button>
          </div>
        </div>
      </div>

      <!-- Product 2: Pandan Layer Cake -->
      <div class="bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
        <div class="relative overflow-hidden">
          <img 
            src="https://images.unsplash.com/photo-1565958011703-44f9829ba187?auto=format&fit=crop&w=400&q=80" 
            alt="Pandan Layer Cake" 
            class="w-full h-48 object-cover hover:scale-105 transition-transform duration-300"
          >
        </div>
        <div class="p-5">
          <h3 class="font-bold text-lg text-[#2C3E50] mb-2">Pandan Layer Cake</h3>
          <p class="text-gray-600 text-sm mb-3 line-clamp-2">Kue berlapis dengan cream pandan yang lezat dan tekstur yang sempurna</p>
          <div class="flex items-center justify-between">
            <span class="text-[#8BA870] font-bold text-lg">Rp 120.000</span>
            <button class="bg-[#8BA870] text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-[#7a965e] transition">
              Pesan
            </button>
          </div>
        </div>
      </div>

      <!-- Product 3: Pandan Roll Cake -->
      <div class="bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
        <div class="relative overflow-hidden">
          <img 
            src="https://images.unsplash.com/photo-1586985289688-ca3cf47d3e6e?auto=format&fit=crop&w=400&q=80" 
            alt="Pandan Roll Cake" 
            class="w-full h-48 object-cover hover:scale-105 transition-transform duration-300"
          >
        </div>
        <div class="p-5">
          <h3 class="font-bold text-lg text-[#2C3E50] mb-2">Pandan Roll Cake</h3>
          <p class="text-gray-600 text-sm mb-3 line-clamp-2">Roll cake lembut dengan isian cream pandan yang creamy dan segar</p>
          <div class="flex items-center justify-between">
            <span class="text-[#8BA870] font-bold text-lg">Rp 65.000</span>
            <button class="bg-[#8BA870] text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-[#7a965e] transition">
              Pesan
            </button>
          </div>
        </div>
      </div>

      <!-- Product 4: Pandan Cupcakes -->
      <div class="bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
        <div class="relative overflow-hidden">
          <img 
            src="https://images.unsplash.com/photo-1576618148400-f54bed99fcfd?auto=format&fit=crop&w=400&q=80" 
            alt="Pandan Cupcakes" 
            class="w-full h-48 object-cover hover:scale-105 transition-transform duration-300"
          >
          <div class="absolute top-3 right-3 bg-orange-500 text-white px-2 py-1 rounded-full text-xs font-medium">
            New
          </div>
        </div>
        <div class="p-5">
          <h3 class="font-bold text-lg text-[#2C3E50] mb-2">Pandan Cupcakes</h3>
          <p class="text-gray-600 text-sm mb-3 line-clamp-2">Cupcake mini dengan topping cream cheese pandan yang manis</p>
          <div class="flex items-center justify-between">
            <span class="text-[#8BA870] font-bold text-lg">Rp 45.000</span>
            <button class="bg-[#8BA870] text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-[#7a965e] transition">
              Pesan
            </button>
          </div>
        </div>
      </div>

      <!-- Product 5: Pandan Brownies -->
      <div class="bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
        <div class="relative overflow-hidden">
          <img 
            src="https://images.unsplash.com/photo-1606313564200-e75d5e30476c?auto=format&fit=crop&w=400&q=80" 
            alt="Pandan Brownies" 
            class="w-full h-48 object-cover hover:scale-105 transition-transform duration-300"
          >
        </div>
        <div class="p-5">
          <h3 class="font-bold text-lg text-[#2C3E50] mb-2">Pandan Brownies</h3>
          <p class="text-gray-600 text-sm mb-3 line-clamp-2">Brownies fudgy dengan perpaduan rasa pandan dan cokelat yang unik</p>
          <div class="flex items-center justify-between">
            <span class="text-[#8BA870] font-bold text-lg">Rp 55.000</span>
            <button class="bg-[#8BA870] text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-[#7a965e] transition">
              Pesan
            </button>
          </div>
        </div>
      </div>

      <!-- Product 6: Pandan Cookies -->
      <div class="bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
        <div class="relative overflow-hidden">
          <img 
            src="https://images.unsplash.com/photo-1499636136210-6f4ee915583e?auto=format&fit=crop&w=400&q=80" 
            alt="Pandan Cookies" 
            class="w-full h-48 object-cover hover:scale-105 transition-transform duration-300"
          >
        </div>
        <div class="p-5">
          <h3 class="font-bold text-lg text-[#2C3E50] mb-2">Pandan Cookies</h3>
          <p class="text-gray-600 text-sm mb-3 line-clamp-2">Cookies renyah dengan aroma pandan yang cocok untuk cemilan sehari-hari</p>
          <div class="flex items-center justify-between">
            <span class="text-[#8BA870] font-bold text-lg">Rp 35.000</span>
            <button class="bg-[#8BA870] text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-[#7a965e] transition">
              Pesan
            </button>
          </div>
        </div>
      </div>

      <!-- Product 7: Pandan Pudding -->
      <div class="bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
        <div class="relative overflow-hidden">
          <img 
            src="https://images.unsplash.com/photo-1551024506-0bccd828d307?auto=format&fit=crop&w=400&q=80" 
            alt="Pandan Pudding" 
            class="w-full h-48 object-cover hover:scale-105 transition-transform duration-300"
          >
        </div>
        <div class="p-5">
          <h3 class="font-bold text-lg text-[#2C3E50] mb-2">Pandan Pudding</h3>
          <p class="text-gray-600 text-sm mb-3 line-clamp-2">Pudding silky dengan rasa pandan yang menyegarkan dan tekstur lembut</p>
          <div class="flex items-center justify-between">
            <span class="text-[#8BA870] font-bold text-lg">Rp 25.000</span>
            <button class="bg-[#8BA870] text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-[#7a965e] transition">
              Pesan
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

</body>
</html>
