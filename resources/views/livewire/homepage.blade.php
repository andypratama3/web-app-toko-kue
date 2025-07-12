<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Kue Pandan Asli Malang</title>
  <style>[x-cloak] { display: none !important; }</style>
  @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/script_homepage.js'])
  <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>

<body>

<!-- Floating WhatsApp Button -->
<a 
  class="fixed bottom-6 right-6 z-50"
  href="https://wa.me/6282131338971?text=Hai%20admin%20*Pandan%20Asli%20Malang*%2C%20perkenalan%20nama%20saya%20(isi%20nama%20anda)%20.%20Saya%20ingin%20.."
  target="_blank"
  aria-label="Chat via WhatsApp"
  style="
    background-color: #212121;
    border-radius: 40px;
    padding: 12px;
    box-shadow: 0 6px 32px 0 rgba(44,62,80,0.16);
    width: 52px;
    height: 52px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: 0.3s;
  "
>
  <!-- WhatsApp White Icon -->
  <svg width="26" height="26" viewBox="0 0 26 26" fill="white" xmlns="http://www.w3.org/2000/svg">
    <path fill-rule="evenodd" clip-rule="evenodd"
      d="M18.8663 15.5805C18.5434 15.4191 16.9607 14.6413 16.666 14.533C16.3713 14.4257 16.1568 14.3726 15.9413 14.6955C15.7268 15.0161 15.1103 15.742 14.9229 15.9565C14.7344 16.172 14.547 16.198 14.2253 16.0377C13.9035 15.8752 12.8657 15.5361 11.6361 14.4398C10.6795 13.5861 10.0328 12.532 9.84533 12.2092C9.65792 11.8875 9.82475 11.713 9.98617 11.5527C10.1313 11.4086 10.3079 11.1768 10.4693 10.9894C10.6307 10.8009 10.6838 10.6665 10.7911 10.451C10.8994 10.2365 10.8453 10.049 10.764 9.88763C10.6838 9.72621 10.0403 8.14129 9.77167 7.49671C9.51058 6.86946 9.24517 6.95504 9.048 6.94421C8.8595 6.93554 8.645 6.93338 8.4305 6.93338C8.216 6.93338 7.86717 7.01354 7.5725 7.33638C7.27675 7.65813 6.44583 8.43705 6.44583 10.022C6.44583 11.6058 7.5985 13.1365 7.75992 13.3521C7.92133 13.5666 10.0295 16.8188 13.2589 18.213C14.0281 18.5445 14.6272 18.7428 15.0941 18.8901C15.8654 19.136 16.5674 19.1014 17.1221 19.018C17.7396 18.9259 19.0266 18.239 19.2953 17.4872C19.5628 16.7354 19.5628 16.0908 19.4827 15.9565C19.4025 15.8221 19.188 15.742 18.8652 15.5805H18.8663ZM12.9924 23.6005H12.9881C11.07 23.6008 9.18707 23.0852 7.53675 22.1076L7.14675 21.8758L3.09292 22.9396L4.17517 18.9876L3.92058 18.5825C2.84823 16.8755 2.28074 14.9 2.28367 12.8841C2.28583 6.97996 7.08933 2.17646 12.9968 2.17646C15.8568 2.17646 18.5456 3.29229 20.5671 5.31596C21.5642 6.30892 22.3544 7.48974 22.8922 8.7901C23.43 10.0905 23.7046 11.4845 23.7001 12.8917C23.6979 18.7959 18.8944 23.6005 12.9924 23.6005ZM22.1054 3.77871C20.9118 2.57721 19.4916 1.62454 17.9271 0.975912C16.3626 0.32728 14.6849 -0.00441593 12.9913 4.43923e-05C5.89117 4.43923e-05 0.1105 5.77963 0.108333 12.883C0.105043 15.1437 0.698072 17.3652 1.82758 19.3235L0 26L6.82933 24.2082C8.71853 25.2375 10.8356 25.7768 12.987 25.7769H12.9924C20.0926 25.7769 25.8733 19.9973 25.8754 12.8928C25.8807 11.1998 25.5502 9.52265 24.903 7.95825C24.2559 6.39384 23.3051 4.97327 22.1054 3.77871Z"
      fill="white"/>
  </svg>
</a>

<!-- Navbar -->
  <nav id="navbar" class="w-full bg-white shadow-sm px-6 py-3 flex items-center justify-between fixed top-0 left-0 z-50 transition-all duration-300">
    <!-- Logo Kiri -->
    <div class="flex items-center gap-2 pl-2 md:pl-6">
      <img src="https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=facearea&w=64&h=64&facepad=2" alt="Logo" class="h-10 w-10 rounded-full object-cover">
      <span class="font-semibold text-[#8BA870] text-xl tracking-tight">Kue Pandan Asli Malang</span>
    </div>
    <!-- Menu Tengah -->
    <ul class="hidden md:flex items-center gap-8 text-sm font-medium text-gray-700 mx-auto">
      <li>
        <a href="#" class="hover:text-green-600 transition">Tentang Kami</a>
      </li>
      <li>
        <a href="#" class="hover:text-green-600 transition">Produk Kami</a>
      </li>
      <li>
        <a href="#" class="hover:text-green-600 transition">Testimoni</a>
      </li>
      <li>
        <a href="#" class="hover:text-green-600 transition">Outlet Location</a>
      </li>
    </ul>
    <!-- Search & Order Kanan -->
    <div class="flex items-center gap-3 pr-2 md:pr-6">
      <button class="text-gray-500 hover:text-green-600 focus:outline-none">

      </button>
      <button class="hidden md:block bg-[#8BA870] text-white rounded-full px-5 py-1.5 font-medium text-sm hover:bg-[#7a965e] transition">
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
      <a href="#" class="hover:text-green-600 transition text-left">Tentang Kami</a>
      <a href="#" class="hover:text-green-600 transition text-left">Produk Kami</a>
      <a href="#" class="hover:text-green-600 transition text-left">Testimoni</a>
      <a href="#" class="hover:text-green-600 transition text-left">Outlet Location</a>
    </div>
  </div>

  <style>
    @keyframes slidein {
      from { transform: translateX(-100%); opacity: 0; }
      to { transform: translateX(0); opacity: 1; }
    }
  </style>

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

<!-- Testimonials Section -->
<section class="w-full bg-white py-8" style="background-color: #f2f6f5;">
  <div class="w-[90%] max-w-7xl mx-auto">
    <div class="text-center mb-10">
      <span class="text-[#8BA870] font-medium tracking-wider text-sm mb-3 block">TESTIMONI</span>
      <h2 class="text-3xl md:text-4xl font-bold text-[#2C3E50] mb-4">Apa Kata Pelanggan Kami?</h2>
      <p class="text-gray-600 max-w-2xl mx-auto text-lg leading-relaxed">
        Kepuasan pelanggan adalah prioritas kami. Berikut beberapa testimoni dari pelanggan setia Kue Pandan Asli Malang.
      </p>
    </div>
    <div class="relative">
      <div id="testimonial-carousel" class="overflow-hidden">
        <div id="testimonial-track" class="flex transition-transform duration-700">
          <!-- Testimonial 1 -->
          <div class="min-w-full px-4">
            <div class="bg-white rounded-2xl shadow-lg p-8 flex flex-col items-center">
              <img src="https://randomuser.me/api/portraits/women/44.jpg" alt="Testimoni 1" class="w-20 h-20 rounded-full object-cover mb-4 border-4 border-[#8BA870]">
              <div class="flex mb-2">
                <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><polygon points="9.9,1.1 7.6,6.6 1.6,7.6 6,11.9 4.8,17.8 9.9,14.8 15,17.8 13.8,11.9 18.2,7.6 12.2,6.6 "/></svg>
                <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><polygon points="9.9,1.1 7.6,6.6 1.6,7.6 6,11.9 4.8,17.8 9.9,14.8 15,17.8 13.8,11.9 18.2,7.6 12.2,6.6 "/></svg>
                <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><polygon points="9.9,1.1 7.6,6.6 1.6,7.6 6,11.9 4.8,17.8 9.9,14.8 15,17.8 13.8,11.9 18.2,7.6 12.2,6.6 "/></svg>
                <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><polygon points="9.9,1.1 7.6,6.6 1.6,7.6 6,11.9 4.8,17.8 9.9,14.8 15,17.8 13.8,11.9 18.2,7.6 12.2,6.6 "/></svg>
                <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><polygon points="9.9,1.1 7.6,6.6 1.6,7.6 6,11.9 4.8,17.8 9.9,14.8 15,17.8 13.8,11.9 18.2,7.6 12.2,6.6 "/></svg>
              </div>
              <p class="text-gray-700 text-center mb-4">"Kuenya lembut banget, aroma pandannya terasa alami. Anak-anak dan keluarga saya suka sekali! Pelayanan juga ramah dan cepat."</p>
              <span class="font-semibold text-[#2C3E50]">Rina, Malang</span>
            </div>
          </div>
          <!-- Testimonial 2 -->
          <div class="min-w-full px-4">
            <div class="bg-white rounded-2xl shadow-lg p-8 flex flex-col items-center">
              <img src="https://randomuser.me/api/portraits/men/32.jpg" alt="Testimoni 2" class="w-20 h-20 rounded-full object-cover mb-4 border-4 border-[#8BA870]">
              <div class="flex mb-2">
                <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><polygon points="9.9,1.1 7.6,6.6 1.6,7.6 6,11.9 4.8,17.8 9.9,14.8 15,17.8 13.8,11.9 18.2,7.6 12.2,6.6 "/></svg>
                <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><polygon points="9.9,1.1 7.6,6.6 1.6,7.6 6,11.9 4.8,17.8 9.9,14.8 15,17.8 13.8,11.9 18.2,7.6 12.2,6.6 "/></svg>
                <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><polygon points="9.9,1.1 7.6,6.6 1.6,7.6 6,11.9 4.8,17.8 9.9,14.8 15,17.8 13.8,11.9 18.2,7.6 12.2,6.6 "/></svg>
                <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><polygon points="9.9,1.1 7.6,6.6 1.6,7.6 6,11.9 4.8,17.8 9.9,14.8 15,17.8 13.8,11.9 18.2,7.6 12.2,6.6 "/></svg>
                <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><polygon points="9.9,1.1 7.6,6.6 1.6,7.6 6,11.9 4.8,17.8 9.9,14.8 15,17.8 13.8,11.9 18.2,7.6 12.2,6.6 "/></svg>
              </div>
              <p class="text-gray-700 text-center mb-4">"Saya sudah langganan di sini. Pandan roll cake-nya favorit keluarga! Harga terjangkau, kualitas premium."</p>
              <span class="font-semibold text-[#2C3E50]">Budi, Surabaya</span>
            </div>
          </div>
          <!-- Testimonial 3 -->
          <div class="min-w-full px-4">
            <div class="bg-white rounded-2xl shadow-lg p-8 flex flex-col items-center">
              <img src="https://randomuser.me/api/portraits/women/65.jpg" alt="Testimoni 3" class="w-20 h-20 rounded-full object-cover mb-4 border-4 border-[#8BA870]">
              <div class="flex mb-2">
                <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><polygon points="9.9,1.1 7.6,6.6 1.6,7.6 6,11.9 4.8,17.8 9.9,14.8 15,17.8 13.8,11.9 18.2,7.6 12.2,6.6 "/></svg>
                <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><polygon points="9.9,1.1 7.6,6.6 1.6,7.6 6,11.9 4.8,17.8 9.9,14.8 15,17.8 13.8,11.9 18.2,7.6 12.2,6.6 "/></svg>
                <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><polygon points="9.9,1.1 7.6,6.6 1.6,7.6 6,11.9 4.8,17.8 9.9,14.8 15,17.8 13.8,11.9 18.2,7.6 12.2,6.6 "/></svg>
                <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><polygon points="9.9,1.1 7.6,6.6 1.6,7.6 6,11.9 4.8,17.8 9.9,14.8 15,17.8 13.8,11.9 18.2,7.6 12.2,6.6 "/></svg>
                <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><polygon points="9.9,1.1 7.6,6.6 1.6,7.6 6,11.9 4.8,17.8 9.9,14.8 15,17.8 13.8,11.9 18.2,7.6 12.2,6.6 "/></svg>
              </div>
              <p class="text-gray-700 text-center mb-4">"Pandan brownies-nya unik, rasa pandan dan cokelatnya pas banget! Pengiriman juga cepat dan aman."</p>
              <span class="font-semibold text-[#2C3E50]">Sari, Jakarta</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Outlet Location Section -->
<section class="w-full py-8" style="background-color: bg-whhite;">
  <div class="w-[90%] max-w-5xl mx-auto">
    <div class="text-center mb-10">
            <span class="text-[#8BA870] font-medium tracking-wider text-sm mb-3 block">Outlet Location</span>
      <h2 class="text-3xl md:text-4xl font-bold text-[#2C3E50] mb-2">Lokasi Outlet Kami</h2>
      <p class="text-gray-600 max-w-2xl mx-auto text-lg leading-relaxed">
        Temukan outlet Kue Pandan Asli Malang terdekat di kota Anda.
      </p>
    </div>
    <div class="flex justify-center mb-8 gap-4">
    <button id="btn-surabaya" class="outlet-btn bg-[#8BA870] text-white px-5 py-2 rounded-lg font-medium shadow hover:bg-[#7a965e] transition">Surabaya</button>
    <button id="btn-malang" class="outlet-btn bg-[#8BA870] text-white px-5 py-2 rounded-lg font-medium shadow hover:bg-[#7a965e] transition">Malang</button>
    <button id="btn-denpasar" class="outlet-btn bg-[#8BA870] text-white px-5 py-2 rounded-lg font-medium shadow hover:bg-[#7a965e] transition">Denpasar</button>

    </div>
    <div id="outlet-content" class="bg-soft-green rounded-2xl shadow-lg p-6 flex flex-col md:flex-row gap-6 items-center">
      <!-- Google Maps -->
      <div class="w-full md:w-1/2 flex justify-center items-center">
        <iframe id="outlet-map" src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3956.934234!2d112.7304!3d-7.2575!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2dd7fbf8b1b1b1b1%3A0x1b1b1b1b1b1b1b1b!2sSurabaya!5e0!3m2!1sen!2sid!4v1680000000000!5m2!1sen!2sid" 
          width="100%" 
          height="320" 
          style="border:0; border-radius:1rem; box-shadow:0 2px 16px 0 rgba(44,62,80,0.08); max-width: 370px; min-width: 220px; display: block; margin: 0 auto; background: #eee;" 
          allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
      </div>
      <!-- Outlet Info -->
      <div class="w-full md:w-1/2 flex flex-col gap-4">
        <img id="outlet-img" src="https://images.unsplash.com/photo-1519125323398-675f0ddb6308?auto=format&fit=crop&w=400&q=80" alt="Outlet Surabaya" class="w-full h-36 object-cover rounded-xl mb-2 shadow">
        <h3 id="outlet-title" class="text-2xl font-bold text-[#2C3E50] mb-1">Outlet Surabaya</h3>
        <p id="outlet-address" class="text-gray-700 mb-1">Jl. Raya Darmo No. 99, Surabaya</p>
        <p id="outlet-hours" class="text-gray-500 text-sm mb-1">Senin - Minggu, 08.00 - 20.00</p>
        <p id="outlet-contact" class="text-gray-500 text-sm mb-2">Telp: 031-1234567</p>
        <a id="outlet-directions" href="https://maps.app.goo.gl/451F7tNyDLVXx3pt6" target="_blank" class="inline-block bg-[#8BA870] text-white px-5 py-2 rounded-lg font-medium shadow hover:bg-[#7a965e] transition">Get Directions</a>
      </div>
    </div>
  </div>
</section>

</body>
</html>
