<!DOCTYPE html>
<html lang="id" style="scroll-behavior: smooth;">

<head>
  <!-- Google Tag Manager -->
  <script>
    (function(w, d, s, l, i) {
      w[l] = w[l] || [];
      w[l].push({
        'gtm.start': new Date().getTime(),
        event: 'gtm.js'
      });
      var f = d.getElementsByTagName(s)[0],
        j = d.createElement(s),
        dl = l != 'dataLayer' ? '&l=' + l : '';
      j.async = true;
      j.src =
        'https://www.googletagmanager.com/gtm.js?id=' + i + dl;
      f.parentNode.insertBefore(j, f);
    })(window, document, 'script', 'dataLayer', 'GTM-PRXFHTTN');
  </script>
  <!-- End Google Tag Manager -->

  <!-- Google tag (gtag.js) -->
  <script async src="https://www.googletagmanager.com/gtag/js?id=G-6GHRM0X2ZS"></script>
  <script>
    window.dataLayer = window.dataLayer || [];

    function gtag() {
      dataLayer.push(arguments);
    }
    gtag('js', new Date());
    gtag('config', 'G-6GHRM0X2ZS');
  </script>

  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="keywords" content="Kue, Kue Pandan, Kue Pandan Asli, Kue Pandan Ijo, Kue Pandan Tradisional, Kue Tradisional, Kue Ijo, Malang, Kue Malang, Oleh oleh Malang, Kue Ijo Alami, Kue Natural, Kue Asli Pandan, Kue Ketan, Kue Pulut, Kue Singkong, Kelapa, Kelapa parut, Kue Ongol, Kue Aren, Kue Talang, Kue Estetik, Kue Srikaya, Kue Coklat, Kue Manis, Kue Ubi Nanas, Kue Ubi, Kue Nanas, Lumpur Surga, Tumpeng Kue, Tumpeng Kue Tradisional, Paket Hampers, Paket Tumpeng, Kue Enak, Kue Lezat, Kue Kekinian, Kue Instagram, Kue Estetik, Camilan Kekinian, Camilan Instgramable, Kue Photogenic, Kue Lembut, Kue Pandan Wangi, Kue Pandan Lembut, Kue Homemade, Buah Tangan Malang, Kue Kuno, Kue 100% Pandan, Kue Delicious, Kue Santan, Kue Gurih, Kue Asin, Kue Tanpa Pengawet, Bahan Baku Alami, Makanan sehat, Saus Srikaya, Kue Srikaya, Kue Basah, Kue Kenyal, Kue Gula Jawa, Saus Gula Jawa, Kue Hijau, Asli Pandan, Kue Ubi Madu, Pandan Homemade">
  <meta name="description" content="Kue Pandan Asli, kami adalah perusahaan kuliner yang berfokus pada produksi dan pengembangan kue tradisional berbahan alami tanpa campuran pengawet dan pewarna. Kami berfokus pada bahan bahan alami mulai dari pewarna kami menggunakan 100% pandan pada seluruh produk kami. Kami berkomitmen menghadirkan kue tradisional dengan bahan baku premium, alami dan kekinian.">

  @section('title', 'Homepage')
  @include('layouts.headicon')
  @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/script_homepage.js'])

  <!-- External Scripts -->
  <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
  <script src="https://cdn.jsdelivr.net/npm/medium-zoom@1.1.0/dist/medium-zoom.min.js"></script>
  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

  <!-- External Styles -->
  <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<!-- PRELOADER -->
<div id="preloader" class="fixed top-0 left-0 w-full h-full bg-white flex justify-center items-center z-50">
  <div class="animate-spin rounded-full h-16 w-16 border-t-4 border-b-4 border-green-500"></div>
</div>

<body class="flex flex-col min-h-screen" style="overflow-x:hidden;">
  <!-- Google Tag Manager (noscript) -->
  <noscript>
    <iframe src="https://www.googletagmanager.com/ns.html?id=GTM-PRXFHTTN"
      height="0" width="0" style="display:none;visibility:hidden"></iframe>
  </noscript>
  <!-- End Google Tag Manager (noscript) -->

  <!-- Floating WhatsApp Button -->
  <a class="fixed bottom-6 right-6 z-50"
    href="https://wa.me/6282131338971?text=Hai%20admin%20*Pandan%20Asli%20Malang*%2C%20perkenalan%20nama%20saya%20(isi%20nama%20anda)%20.%20Saya%20ingin%20.."
    target="_blank" aria-label="Chat via WhatsApp" style="
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
  ">
    <!-- WhatsApp Icon -->
    <svg width="26" height="26" viewBox="0 0 26 26" fill="white" xmlns="http://www.w3.org/2000/svg">
      <path fill-rule="evenodd" clip-rule="evenodd"
        d="M18.8663 15.5805C18.5434 15.4191 16.9607 14.6413 16.666 14.533C16.3713 14.4257 16.1568 14.3726 15.9413 14.6955C15.7268 15.0161 15.1103 15.742 14.9229 15.9565C14.7344 16.172 14.547 16.198 14.2253 16.0377C13.9035 15.8752 12.8657 15.5361 11.6361 14.4398C10.6795 13.5861 10.0328 12.532 9.84533 12.2092C9.65792 11.8875 9.82475 11.713 9.98617 11.5527C10.1313 11.4086 10.3079 11.1768 10.4693 10.9894C10.6307 10.8009 10.6838 10.6665 10.7911 10.451C10.8994 10.2365 10.8453 10.049 10.764 9.88763C10.6838 9.72621 10.0403 8.14129 9.77167 7.49671C9.51058 6.86946 9.24517 6.95504 9.048 6.94421C8.8595 6.93554 8.645 6.93338 8.4305 6.93338C8.216 6.93338 7.86717 7.01354 7.5725 7.33638C7.27675 7.65813 6.44583 8.43705 6.44583 10.022C6.44583 11.6058 7.5985 13.1365 7.75992 13.3521C7.92133 13.5666 10.0295 16.8188 13.2589 18.213C14.0281 18.5445 14.6272 18.7428 15.0941 18.8901C15.8654 19.136 16.5674 19.1014 17.1221 19.018C17.7396 18.9259 19.0266 18.239 19.2953 17.4872C19.5628 16.7354 19.5628 16.0908 19.4827 15.9565C19.4025 15.8221 19.188 15.742 18.8652 15.5805H18.8663ZM12.9924 23.6005H12.9881C11.07 23.6008 9.18707 23.0852 7.53675 22.1076L7.14675 21.8758L3.09292 22.9396L4.17517 18.9876L3.92058 18.5825C2.84823 16.8755 2.28074 14.9 2.28367 12.8841C2.28583 6.97996 7.08933 2.17646 12.9968 2.17646C15.8568 2.17646 18.5456 3.29229 20.5671 5.31596C21.5642 6.30892 22.3544 7.48974 22.8922 8.7901C23.43 10.0905 23.7046 11.4845 23.7001 12.8917C23.6979 18.7959 18.8944 23.6005 12.9924 23.6005ZM22.1054 3.77871C20.9118 2.57721 19.4916 1.62454 17.9271 0.975912C16.3626 0.32728 14.6849 -0.00441593 12.9913 4.43923e-05C5.89117 4.43923e-05 0.1105 5.77963 0.108333 12.883C0.105043 15.1437 0.698072 17.3652 1.82758 19.3235L0 26L6.82933 24.2082C8.71853 25.2375 10.8356 25.7768 12.987 25.7769H12.9924C20.0926 25.7769 25.8733 19.9973 25.8754 12.8928C25.8807 11.1998 25.5502 9.52265 24.903 7.95825C24.2559 6.39384 23.3051 4.97327 22.1054 3.77871Z"
        fill="white" />
    </svg>
  </a>

  <!-- Navbar -->
  <nav id="navbar"
    class="w-full bg-white/80 shadow-sm py-3 flex items-center justify-between fixed top-0 left-0 z-50 transition-all duration-300">
    <!-- Logo Kiri -->
    <div class="flex items-center gap-2 pl-2 md:pl-6 flex-shrink-0">
      <img src="{{ asset('assets/homepage/logo.png') }}"
        alt="Logo" class="h-10 w-10 rounded-full object-cover">
      <a href="/" class="font-semibold text-[#8BA870] text-xl tracking-tight hover:text-black active:text-black transition-colors duration-200 md:hidden lg:inline">Kue Pandan Asli</a>
    </div>
    <!-- Menu Tengah Absolute Center Boxed -->
    <div class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] max-w-full">
      <ul class="hidden md:flex items-center justify-center gap-8 text-sm font-medium text-gray-700 w-full">
        <li>
          <a href="#tentang-kami" class="hover:text-green-600 transition">Tentang Kami</a>
        </li>
        <li>
          <a href="#produk-kami" class="hover:text-green-600 transition">Produk Kami</a>
        </li>
        <li>
          <a href="#testimoni" class="hover:text-green-600 transition">Testimoni</a>
        </li>
        <li>
          <a href="#outlet-location" class="hover:text-green-600 transition">Outlet Location</a>
        </li>
      </ul>
    </div>
    <!-- Search & Order Kanan -->
    <div class="flex items-center gap-3 pr-2 md:pr-6 flex-shrink-0">
      <!-- Form wrapper untuk tombol Login -->
      <form action="{{ route('login') }}" method="GET" class="hidden md:block">
        <button type="submit" class="bg-[#8BA870] text-white rounded-full px-5 py-1.5 font-medium text-sm hover:bg-[#7a965e] transition">
          Login
        </button>
      </form>
    </div>

    <!-- Hamburger Menu (Mobile) -->
    <div class="md:hidden flex items-center ml-2 mr-4">
      <button id="hamburger-btn" class="text-white bg-[#8BA870]/80 hover:bg-[#8BA870] focus:outline-none rounded-lg p-2 transition-all duration-200 active:scale-95 active:bg-[#7a965e] transform hover:scale-105">
        <svg class="h-6 w-6 transition-transform duration-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
      </button>
    </div>
  </nav>

  <!-- Mobile Menu -->
  <div id="mobile-menu"
    class="fixed top-[68px] left-0 w-full h-[calc(100vh-68px)] bg-gray-900/60 z-40 opacity-0 pointer-events-none transition-all duration-300">
    <div class="bg-white rounded-r-2xl shadow-xl w-4/5 max-w-xs h-full p-8 flex flex-col gap-6 text-lg font-medium text-gray-700 animate-slidein"
      style="animation: slidein 0.3s cubic-bezier(.4,0,.2,1)">
      <a href="#tentang-kami" class="hover:text-green-600 transition text-left">Tentang Kami</a>
      <a href="#produk-kami" class="hover:text-green-600 transition text-left">Produk Kami</a>
      <a href="#testimoni" class="hover:text-green-600 transition text-left">Testimoni</a>
      <a href="#outlet-location" class="hover:text-green-600 transition text-left">Outlet Location</a>

      <!-- Separator -->
      <div class="border-t border-gray-200 my-4"></div>

      <!-- Tombol/Login Link -->
      <a href="{{ route('login') }}"
        class="block w-full text-center bg-[#8BA870] text-white rounded-full px-5 py-2 font-medium text-sm hover:bg-[#7a965e] transition">
        Login
      </a>
    </div>
  </div>

  <!-- Spacer agar konten tidak tertutup navbar -->
  <div class="h-[68px] md:h-[68px]"></div>

  <!-- Banner Section -->
  <section class="relative w-full h-[400px] md:h-[520px] flex items-center justify-center overflow-hidden">
    <img src="{{ asset('assets/homepage/hero-image.jpg') }}"
      alt="Hero Image" loading="lazy" class="absolute inset-0 w-full h-full object-cover object-center brightness-90">
    <div class="absolute inset-0 flex flex-col items-center justify-center z-10" data-aos="fade-down">
      <h1 class="text-white text-4xl md:text-6xl font-extrabold mb-4 drop-shadow-lg">Kue Pandan Asli</h1>
      <p class="text-white text-lg md:text-2xl font-medium drop-shadow mb-8 max-w-2xl text-center">Kue Ijo Adalah Kue tradisional Indonesia, cocok untuk semua acara spesial Anda.</p>
      <a href="https://api.whatsapp.com/send/?phone=6282131338971&text=Hai+admin+%2APandan+Asli+Malang%2A%2C+perkenalan+nama+saya+%28isi+nama+anda%29+.+Saya+ingin+..&type=phone_number&app_absent=0"
        class="bg-white text-[#7a965e] font-semibold px-8 py-3 rounded-full shadow hover:bg-[#8BA870] hover:text-white transition-all duration-300 text-lg border border-[#8BA870] transform hover:scale-105 active:scale-95 hover:shadow-xl active:shadow-md">Pesan
        Sekarang</a>
    </div>
    <div class="absolute inset-0 bg-gradient-to-t from-[#8BA870]/40 to-transparent"></div>
  </section>

  <!-- About Us Section -->
  <section id="tentang-kami" class="w-full py-8" style="background-color: #f2f6f5;">
    <div class="w-[90%] max-w-6xl mx-auto flex flex-col md:flex-row items-center gap-12" data-aos="fade-up">
      <!-- Left: Image -->
      <div class="w-full md:w-1/2">
        <img src="{{ asset('assets/homepage/about-us.jpg') }}"
          alt="About Us" loading="lazy" class="w-full h-[400px] object-cover rounded-2xl shadow-lg">
      </div>
      <!-- Right: Content (tab only on this side) -->
      <div class="w-full md:w-1/2 flex flex-col items-start">
        <div x-data="{ tab: 'tentang' }" class="w-full">
          <div class="flex gap-8 mb-4 border-b border-gray-200 relative">
            <button @click="tab = 'tentang'"
              class="pb-2 px-1 text-lg font-semibold focus:outline-none transition-colors duration-200 border-b-2"
              :class="tab === 'tentang' ? 'text-[#8BA870] border-[#8BA870]' : 'text-gray-500 hover:text-[#8BA870] border-b-2 border-transparent'">
              Tentang
            </button>
            <button @click="tab = 'visi'"
              class="pb-2 px-1 text-lg font-semibold focus:outline-none transition-colors duration-200 border-b-2"
              :class="tab === 'visi' ? 'text-[#8BA870] border-[#8BA870]' : 'text-gray-500 hover:text-[#8BA870] border-b-2 border-transparent'">
              Visi
            </button>
            <button @click="tab = 'misi'"
              class="pb-2 px-1 text-lg font-semibold focus:outline-none transition-colors duration-200 border-b-2"
              :class="tab === 'misi' ? 'text-[#8BA870] border-[#8BA870]' : 'text-gray-500 hover:text-[#8BA870] border-b-2 border-transparent'">
              Misi
            </button>
          </div>
          <h2 class="text-3xl md:text-4xl font-bold text-[#2C3E50] mb-6" x-show="tab === 'tentang'">Kue Pandan Asli</h2>
          <div x-show="tab === 'tentang'" x-transition:enter="transition ease-out duration-800" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-400" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <div x-data="{ open: false }">
              <p class="text-gray-600 leading-relaxed mb-8">
                <span x-show="!open">
                  Kue Pandan Asli, kami adalah perusahaan kuliner yang berfokus pada produksi dan pengembangan kue tradisional berbahan alami tanpa campuran pengawet dan pewarna. Kami berfokus pada bahan bahan alami mulai dari pewarna kami menggunakan 100% pandan pada seluruh produk kami. Kami berkomitmen menghadirkan kue tradisional dengan bahan baku premium, alami dan kekinian.<br>
                  <button @click="open = true" class="inline-flex items-center gap-2 bg-[#8BA870] text-white px-4 py-1.5 rounded-full font-semibold shadow hover:bg-[#7a965e] transition mt-8 group">
                    <svg class="w-4 h-4 text-white group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                    </svg>
                    <span>Selengkapnya</span>
                  </button>
                </span>
                <span x-show="open">
                  Kue Pandan Asli, kami adalah perusahaan kuliner yang berfokus pada produksi dan pengembangan kue tradisional berbahan alami tanpa campuran pengawet dan pewarna. Kami berfokus pada bahan bahan alami mulai dari pewarna kami menggunakan 100% pandan pada seluruh produk kami. Kami berkomitmen menghadirkan kue tradisional dengan bahan baku premium, alami dan kekinian.<br><br>
                  Kue Pandan Asli mengelakan kembali warisan kuliner nusantara melalui produk-produk unggulan seperti Kue Ijo Pandan, Kue Pulut Srikaya, Kue Lumpur Surga, Kue Ongol, Kue Ubi Nanas dengan mengedepankan 100% bahan alami yang telah melalui quality control tim kami mulai dari pemilihan daun pandan yang hijau tua segar, gula jawa murni dari suplier sampai dengan pemilihan nanas langsung dari petani lokal. Mengangkat Kue Tradisional yang dikemas secara premium, cantik dan estetik agar sesuai dengan perkembangan zaman.<br><br>
                  Kami percaya bahwasanya makanan tidak hanya soal rasa, tetapi juga pengalaman dan nilai budaya. Oleh karena itu, setiap produk kami dikemas dengan visual yang photogenic dan instagramable, menjadikannya pilihan utama sebagai oleh-oleh yang tersedia mulai dari Paket Hampers A (Anggun), Hampers B (Bagus) dan Hampers C (Cantik) dan Paket Tumpeng kue tradisional mulai dari Tumpeng Mini dan Tumpeng Besar yang cocok untuk berbagai acara.<br>
                  <button @click="open = false" class="inline-flex items-center gap-2 bg-gray-200 text-[#8BA870] px-4 py-1.5 rounded-full font-semibold shadow hover:bg-gray-300 transition mt-8 group">
                    <svg class="w-4 h-4 text-[#8BA870] group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M7 16l-4-4m0 0l4-4m-4 4h18" />
                    </svg>
                    <span>Tutup Selengkapnya</span>
                  </button>
                </span>
              </p>
            </div>
          </div>
          <div x-show="tab === 'visi'" x-transition:enter="transition ease-out duration-800" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-400" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <h2 class="text-3xl md:text-4xl font-bold text-[#2C3E50] mb-6">Visi Kami</h2>
            <div class="text-gray-600 leading-relaxed mb-8">
              <p class="mb-4">Menjadi pelopor dalam pelestarian dan pengembangan kue tradisional Indonesia berbahan alami, dengan menghadirkan produk yang tidak hanya lezat dan sehat, tetapi juga dikemas secara modern dan menarik.</p>
              <p>Kami ingin membawa warisan kuliner nusantara ke generasi masa kini dan mendatang, sehingga kue tradisional tetap relevan, dicintai, dan menjadi kebanggaan bangsa.</p>
            </div>
          </div>
          <div x-show="tab === 'misi'" x-transition:enter="transition ease-out duration-800" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-400" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <h2 class="text-3xl md:text-4xl font-bold text-[#2C3E50] mb-6">Misi Kami</h2>
            <ul class="text-gray-600 leading-relaxed mb-8 list-disc pl-6 space-y-2">
              <li>
                <span class="font-semibold text-[#8BA870]">Mengutamakan bahan alami:</span> Seluruh produk dibuat dari bahan baku alami, tanpa pengawet dan pewarna buatan, demi kesehatan dan kualitas terbaik.
              </li>
              <li>
                <span class="font-semibold text-[#8BA870]">Inovasi berkelanjutan:</span> Terus mengembangkan varian kue tradisional dengan sentuhan modern, baik dari segi rasa maupun tampilan.
              </li>
              <li>
                <span class="font-semibold text-[#8BA870]">Tanggung jawab produk:</span> Menjaga kualitas mulai dari pemilihan bahan, proses produksi, hingga pengemasan dan pengiriman ke konsumen.
              </li>
              <li>
                <span class="font-semibold text-[#8BA870]">Pelayanan prima:</span> Memberikan pengalaman terbaik bagi pelanggan melalui produk berkualitas, pelayanan ramah, dan kemasan eksklusif.
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>
    </div>
  </section>

  <!-- Products Section -->
  <section id="produk-kami" class="w-full bg-white pt-4 pb-8">
    <div class="w-[90%] max-w-7xl mx-auto" x-data="{ kategori: 'produk' }">
      <!-- Section Header -->
      <div class="text-center mb-6">
        <span class="text-[#8BA870] font-medium tracking-wider text-sm mb-3 block">PRODUK UNGGULAN</span>
        <h2 class="text-4xl md:text-5xl font-bold text-[#2C3E50] mb-6">Produk Kami</h2>
        <p class="text-gray-600 max-w-3xl mx-auto text-lg leading-relaxed">
          Nikmati berbagai varian kue pandan berkualitas tinggi yang dibuat dengan resep tradisional dan
          bahan-bahan pilihan
        </p>
      </div>
      <!-- Tombol Filter Kategori (pindah ke bawah judul) -->
      <div class="flex justify-center gap-4 mb-4">
        <button @click="kategori = 'produk'" :class="kategori === 'produk' ? 'bg-[#8BA870] text-white' : 'bg-gray-100 text-[#2C3E50]'" class="px-5 py-2 rounded-full font-semibold shadow transition">Produk</button>
        <button @click="kategori = 'hampers'" :class="kategori === 'hampers' ? 'bg-[#8BA870] text-white' : 'bg-gray-100 text-[#2C3E50]'" class="px-5 py-2 rounded-full font-semibold shadow transition">Hampers</button>
        <button @click="kategori = 'tumpeng'" :class="kategori === 'tumpeng' ? 'bg-[#8BA870] text-white' : 'bg-gray-100 text-[#2C3E50]'" class="px-5 py-2 rounded-full font-semibold shadow transition">Tumpeng</button>
      </div>

      <!-- Produk: Kategori Produk -->
      <div x-show="kategori === 'produk'"
        x-transition:enter="transition ease-out duration-500"
        x-transition:enter-start="opacity-0 transform scale-95"
        x-transition:enter-end="opacity-100 transform scale-100"
        x-transition:leave="transition ease-in duration-300"
        x-transition:leave-start="opacity-100 transform scale-100"
        x-transition:leave-end="opacity-0 transform scale-95"
        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-3 gap-8 py-8 md:py-12" data-aos="zoom-in">
        <!-- Product 1: Kue Ijo -->
        <div
          class="bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
          <div class="relative overflow-hidden">
            <img src="{{ asset('assets/homepage/product/kue-ijo.jpg') }}"
              alt="Kue Ijo"
              loading="lazy"
              class="w-full h-48 object-cover hover:scale-105 transition-transform duration-300 cursor-zoom-in zoomable">
            <div
              class="absolute top-3 right-3 bg-[#8BA870] text-white px-2 py-1 rounded-full text-xs font-medium">
              Best Seller
            </div>
          </div>
          <div class="p-5">
            <h3 class="font-bold text-lg text-[#2C3E50] mb-2">Kue Ijo</h3>
            <div x-data="{ open: false }">
              <p class="text-gray-600 text-sm mb-3" :class="open ? '' : 'line-clamp-2'">
                Kue Ijo terbuat dari tepung beras yang telah diayak lalu dicampur dengan campuran santan kelapa fresh dan air pandan asli. Kue ini memiliki tekstur yang kenyal dan lembut bersamaan ketika dikunyah didalam mulut. Dibalut taburan dengan kelapa parut segar menambah cita rasa gurih dan manis yang menjadikan kue Ijo cocok dinikmati pada suasana apapun.
              </p>
              <button @click="open = !open" class="text-[#8BA870] text-xs font-semibold focus:outline-none hover:underline mb-2">
                <span x-show="!open">Selengkapnya</span>
                <span x-show="open">Tutup</span>
              </button>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-[#8BA870] font-bold text-lg">Rp 85.000</span>
              <button
                class="bg-[#8BA870] text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-[#7a965e] transition">
                Pesan
              </button>
            </div>
          </div>
        </div>

        <!-- Product 2: Kue Lumpur Surga -->
        <div
          class="bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
          <div class="relative overflow-hidden">
            <img src="{{ asset('assets/homepage/product/kue-lumpur-surga.jpg') }}"
              alt="Kue Lumpur Surga"
              loading="lazy"
              class="w-full h-48 object-cover hover:scale-105 transition-transform duration-300 cursor-zoom-in zoomable">
          </div>
          <div class="p-5">
            <h3 class="font-bold text-lg text-[#2C3E50] mb-2">Kue Lumpur Surga</h3>
            <div x-data="{ open: false }">
              <p class="text-gray-600 text-sm mb-3" :class="open ? '' : 'line-clamp-2'">
                Kue yang memiliki dua lapisan yaitu lapisan bawah bewarna hijau yang dihasilkan dari air pandan asli dan lapisan atas mirip dengan vla yang terbuat dari santan. Kue yang memiliki tekstur lembut dan lumer dimulut sangat nikmat jika disantap dalam keadaan dingin. Memiliki Cita rasa manis dari kue nya dan gurih asin dari vla nya berpadu menghasilkan rasa nikmat yang nyaman ketika masuk dimulut.
              </p>
              <button @click="open = !open" class="text-[#8BA870] text-xs font-semibold focus:outline-none hover:underline mb-2">
                <span x-show="!open">Selengkapnya</span>
                <span x-show="open">Tutup</span>
              </button>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-[#8BA870] font-bold text-lg">Rp 120.000</span>
              <button
                class="bg-[#8BA870] text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-[#7a965e] transition">
                Pesan
              </button>
            </div>
          </div>
        </div>

        <!-- Product 3: Kue Ongol -->
        <div
          class="bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
          <div class="relative overflow-hidden">
            <img src="{{ asset('assets/homepage/product/kue-ongol.jpg') }}"
              alt="Kue Ongol"
              loading="lazy"
              class="w-full h-48 object-cover hover:scale-105 transition-transform duration-300 cursor-zoom-in zoomable">
          </div>
          <div class="p-5">
            <h3 class="font-bold text-lg text-[#2C3E50] mb-2">Kue Ongol</h3>
            <div x-data="{ open: false }">
              <p class="text-gray-600 text-sm mb-3" :class="open ? '' : 'line-clamp-2'">
                Kue Ongol kami terbuat dari tepung tapioka yang ditambahkan gula merah jawa dan sedikit tambahan air pandan asli membuat warna kue ini bewarna coklat cantik. Kue yang memiliki tekstur kenyal dan legit dan terasa manis dimulut. Kue Ongol yang memiliki wangi khas gula jawa merah sangat cocok dipadukan dengan parutan kelapa segar yang akan memadukan antara gurih dan manis pada kue ini.
              </p>
              <button @click="open = !open" class="text-[#8BA870] text-xs font-semibold focus:outline-none hover:underline mb-2">
                <span x-show="!open">Selengkapnya</span>
                <span x-show="open">Tutup</span>
              </button>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-[#8BA870] font-bold text-lg">Rp 65.000</span>
              <button
                class="bg-[#8BA870] text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-[#7a965e] transition">
                Pesan
              </button>
            </div>
          </div>
        </div>

        <!-- Product 4: Kue Pulut -->
        <div
          class="bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
          <div class="relative overflow-hidden">
            <img src="{{ asset('assets/homepage/product/kue-pulut.jpg') }}"
              alt="Kue Pulut Srikaya"
              class="w-full h-48 object-cover hover:scale-105 transition-transform duration-300 cursor-zoom-in zoomable">
            <div
              class="absolute top-3 right-3 bg-orange-500 text-white px-2 py-1 rounded-full text-xs font-medium">
              New
            </div>
          </div>
          <div class="p-5">
            <h3 class="font-bold text-lg text-[#2C3E50] mb-2">Kue Pulut Srikaya</h3>
            <div x-data="{ open: false }">
              <p class="text-gray-600 text-sm mb-3" :class="open ? '' : 'line-clamp-2'">
                Kue Pulut yang dibuat dari beras ketan utuh yang akan menciptakan tekstur punel dan sedikit legit. Perpaduan warna Putih dari beras ketan dan Ungu alami dari bunga lawing (butterfly pea tea) menambah keindahan visual pada kue ini. Kue Pulut ini dilengkapi dengan saus srikaya yang membuat perpaduan antara manis dan sedikit gurih dari saus dan gurih dari kue pulut itu sendiri menjadikan perpaduan rasa yang lengkap dan lezat.
              </p>
              <button @click="open = !open" class="text-[#8BA870] text-xs font-semibold focus:outline-none hover:underline mb-2">
                <span x-show="!open">Selengkapnya</span>
                <span x-show="open">Tutup</span>
              </button>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-[#8BA870] font-bold text-lg">Rp 45.000</span>
              <button
                class="bg-[#8BA870] text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-[#7a965e] transition">
                Pesan
              </button>
            </div>
          </div>
        </div>

        <!-- Product 5: Kue Ubi Nanas -->
        <div
          class="bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
          <div class="relative overflow-hidden">
            <img src="{{ asset('assets/homepage/product/kue-ubi-nanas.jpeg') }}"
              alt="Kue Ubi Nanas"
              class="w-full h-48 object-cover hover:scale-105 transition-transform duration-300 cursor-zoom-in zoomable">
          </div>
          <div class="p-5">
            <h3 class="font-bold text-lg text-[#2C3E50] mb-2">Kue Ubi Nanas</h3>
            <div x-data="{ open: false }">
              <p class="text-gray-600 text-sm mb-3" :class="open ? '' : 'line-clamp-2'">
                Kue yang terbuat dari perpaduan ubi singkong yang memiliki rasa netral dan tekstur padat dan buah nanas yang memiliki rasa manis, segar dan masam dipadukan. Kue ini memiliki tekrstur sedikit chewy dan padat hampir seperti kue talam pada umumnya. Kue ini memiliki rasa manis dan sedikit rasa segar dari buah nanas dan rasa gurih dari bahan bahan kue. Terbuat dari bahan alami sehingga aman dikonsumsi dan sehat.
              </p>
              <button @click="open = !open" class="text-[#8BA870] text-xs font-semibold focus:outline-none hover:underline mb-2">
                <span x-show="!open">Selengkapnya</span>
                <span x-show="open">Tutup</span>
              </button>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-[#8BA870] font-bold text-lg">Rp 55.000</span>
              <button
                class="bg-[#8BA870] text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-[#7a965e] transition">
                Pesan
              </button>
            </div>
          </div>
        </div>

        <!-- Product 6: Selai Srikaya -->
        <div
          class="bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
          <div class="relative overflow-hidden">
            <img src="{{ asset('assets/homepage/product/selai-srikaya.jpg') }}"
              alt="Selai Srikaya"
              class="w-full h-48 object-cover hover:scale-105 transition-transform duration-300 cursor-zoom-in zoomable">
          </div>
          <div class="p-5">
            <h3 class="font-bold text-lg text-[#2C3E50] mb-2">Selai Srikaya</h3>
            <div x-data="{ open: false }">
              <p class="text-gray-600 text-sm mb-3" :class="open ? '' : 'line-clamp-2'">
                Selai yang terbuat dari perpaduan santan kelapa, telur, air pandan dan gula merah jawa menghasilkan rasa yang gurih dan manis pada selai ini. Selai yang memiliki tekstur kental dan memiliki warna oren pekat dihasilkan dari telur dan gula merah. Selai ini cocok dipadukan dengan kue yang memiliki cita rasa netral maupun gurih untuk menambahkan cita rasa manis pada rasa kue tersebut.
              </p>
              <button @click="open = !open" class="text-[#8BA870] text-xs font-semibold focus:outline-none hover:underline mb-2">
                <span x-show="!open">Selengkapnya</span>
                <span x-show="open">Tutup</span>
              </button>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-[#8BA870] font-bold text-lg">Rp 35.000</span>
              <button
                class="bg-[#8BA870] text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-[#7a965e] transition">
                Pesan
              </button>
            </div>
          </div>
        </div>

      </div>

      <!-- Produk: Kategori Hampers -->
      <div x-show="kategori === 'hampers'"
        x-transition:enter="transition ease-out duration-500"
        x-transition:enter-start="opacity-0 transform translate-y-4"
        x-transition:enter-end="opacity-100 transform translate-y-0"
        x-transition:leave="transition ease-in duration-300"
        x-transition:leave-start="opacity-100 transform translate-y-0"
        x-transition:leave-end="opacity-0 transform translate-y-4"
        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-3 gap-8 py-8 md:py-12">

        <div class="bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
          <div class="relative overflow-hidden">
            <img src="{{ asset('assets/homepage/product/hampers-a.jpg') }}" alt="Hampers A (Anggun)" loading="lazy" class="w-full h-48 object-cover hover:scale-105 transition-transform duration-300 cursor-zoom-in zoomable">
            <div class="absolute top-3 right-3 bg-[#8BA870] text-white px-2 py-1 rounded-full text-xs font-medium">Hampers</div>
          </div>
          <div class="p-5" data-aos="fade-left">
            <h3 class="font-bold text-lg text-[#2C3E50] mb-2">Hampers A (Anggun)</h3>
            <p class="text-gray-600 text-sm mb-3 line-clamp-2">Paket kue pandan spesial untuk hadiah Lebaran, lengkap dengan kemasan eksklusif dan kartu ucapan.</p>
            <div class="flex items-center justify-between">
              <span class="text-[#8BA870] font-bold text-lg">Rp 250.000</span>
              <button class="bg-[#8BA870] text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-[#7a965e] transition">Pesan</button>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
          <div class="relative overflow-hidden">
            <img src="{{ asset('assets/homepage/product/hampers-b.jpg') }}" alt="Hampers B (Bagus)" loading="lazy" class="w-full h-48 object-cover hover:scale-105 transition-transform duration-300 cursor-zoom-in zoomable">
            <div class="absolute top-3 right-3 bg-[#8BA870] text-white px-2 py-1 rounded-full text-xs font-medium">Hampers</div>
          </div>
          <div class="p-5">
            <h3 class="font-bold text-lg text-[#2C3E50] mb-2">Hampers B (Bagus)</h3>
            <p class="text-gray-600 text-sm mb-3 line-clamp-2">Hampers kue pandan untuk momen Natal, cocok untuk keluarga dan kolega, isi 3 varian kue.</p>
            <div class="flex items-center justify-between">
              <span class="text-[#8BA870] font-bold text-lg">Rp 275.000</span>
              <button class="bg-[#8BA870] text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-[#7a965e] transition">Pesan</button>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
          <div class="relative overflow-hidden">
            <img src="{{ asset('assets/homepage/product/hampers-c.png') }}" alt="Hampers C (Cantik)" loading="lazy" class="w-full h-48 object-cover hover:scale-105 transition-transform duration-300 cursor-zoom-in zoomable">
            <div class="absolute top-3 right-3 bg-[#8BA870] text-white px-2 py-1 rounded-full text-xs font-medium">Hampers</div>
          </div>
          <div class="p-5">
            <h3 class="font-bold text-lg text-[#2C3E50] mb-2">Hampers C (Cantik)</h3>
            <p class="text-gray-600 text-sm mb-3 line-clamp-2">Paket hampers custom, bisa pilih isi kue sesuai keinginan dan desain kemasan sendiri.</p>
            <div class="flex items-center justify-between">
              <span class="text-[#8BA870] font-bold text-lg">Rp 300.000</span>
              <button class="bg-[#8BA870] text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-[#7a965e] transition">Pesan</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Produk: Kategori Tumpeng -->
      <div x-show="kategori === 'tumpeng'"
        x-transition:enter="transition ease-out duration-500"
        x-transition:enter-start="opacity-0 transform -translate-x-4"
        x-transition:enter-end="opacity-100 transform translate-x-0"
        x-transition:leave="transition ease-in duration-300"
        x-transition:leave-start="opacity-100 transform translate-x-0"
        x-transition:leave-end="opacity-0 transform -translate-x-4"
        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-3 gap-8 py-8 md:py-12" data-aos="zoom-in">

        <div class="bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
          <div class="relative overflow-hidden">
            <img src="{{ asset('assets/homepage/product/tumpeng-mini.jpg') }}" alt="Tumpeng Mini" loading="lazy" class="w-full h-48 object-cover hover:scale-105 transition-transform duration-300 cursor-zoom-in zoomable">
            <div class="absolute top-3 right-3 bg-yellow-500 text-white px-2 py-1 rounded-full text-xs font-medium">Tumpeng</div>
          </div>
          <div class="p-5">
            <h3 class="font-bold text-lg text-[#2C3E50] mb-2">Tumpeng Mini</h3>
            <p class="text-gray-600 text-sm mb-3 line-clamp-2">Tumpeng mini dengan lauk lengkap, cocok untuk syukuran, ulang tahun, atau acara spesial lainnya.</p>
            <div class="flex items-center justify-between">
              <span class="text-[#8BA870] font-bold text-lg">Rp 150.000</span>
              <button class="bg-[#8BA870] text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-[#7a965e] transition">Pesan</button>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
          <div class="relative overflow-hidden">
            <img src="{{ asset('assets/homepage/product/tumpeng-besar.jpg') }}" alt="Tumpeng Besar" loading="lazy" class="w-full h-48 object-cover hover:scale-105 transition-transform duration-300 cursor-zoom-in zoomable">
            <div class="absolute top-3 right-3 bg-yellow-500 text-white px-2 py-1 rounded-full text-xs font-medium">Tumpeng</div>
          </div>
          <div class="p-5">
            <h3 class="font-bold text-lg text-[#2C3E50] mb-2">Tumpeng Besar</h3>
            <p class="text-gray-600 text-sm mb-3 line-clamp-2">Tumpeng besar untuk acara keluarga, kantor, arisan, atau perayaan penting lainnya. Bisa request lauk.</p>
            <div class="flex items-center justify-between">
              <span class="text-[#8BA870] font-bold text-lg">Rp 350.000</span>
              <button class="bg-[#8BA870] text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-[#7a965e] transition">Pesan</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Testimonials Section -->
  <section id="testimoni" class="w-full bg-white py-8" style="background-color: #f2f6f5;">
    <div class="w-[90%] max-w-7xl mx-auto">
      <div class="text-center mb-10">
        <span class="text-[#8BA870] font-medium tracking-wider text-sm mb-3 block">TESTIMONI</span>
        <h2 class="text-3xl md:text-4xl font-bold text-[#2C3E50] mb-4">Apa Kata Pelanggan Kami?</h2>
        <p class="text-gray-600 max-w-2xl mx-auto text-lg leading-relaxed">
          Kepuasan pelanggan adalah prioritas kami. Berikut beberapa testimoni dari pelanggan setia Kue Pandan
          Asli Malang.
        </p>
      </div>
      <div class="relative" data-aos="zoom-out-down">
        <div id="testimonial-carousel" class="overflow-hidden">
          <div id="testimonial-track" class="flex transition-transform duration-700">
            <!-- Testimonial 1 -->
            <div class="min-w-full px-4">
              <div class="bg-white rounded-2xl shadow-lg p-8 flex flex-col items-center">
                <img src="{{ asset('assets/homepage/testimonial/testimoni-3.png') }}" alt="Testimoni 1" loading="lazy"
                  class="w-20 h-20 rounded-full object-cover mb-4 border-4 border-[#8BA870]">
                <div class="flex mb-2">
                  <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                    <polygon
                      points="9.9,1.1 7.6,6.6 1.6,7.6 6,11.9 4.8,17.8 9.9,14.8 15,17.8 13.8,11.9 18.2,7.6 12.2,6.6 " />
                  </svg>
                  <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                    <polygon
                      points="9.9,1.1 7.6,6.6 1.6,7.6 6,11.9 4.8,17.8 9.9,14.8 15,17.8 13.8,11.9 18.2,7.6 12.2,6.6 " />
                  </svg>
                  <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                    <polygon
                      points="9.9,1.1 7.6,6.6 1.6,7.6 6,11.9 4.8,17.8 9.9,14.8 15,17.8 13.8,11.9 18.2,7.6 12.2,6.6 " />
                  </svg>
                  <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                    <polygon
                      points="9.9,1.1 7.6,6.6 1.6,7.6 6,11.9 4.8,17.8 9.9,14.8 15,17.8 13.8,11.9 18.2,7.6 12.2,6.6 " />
                  </svg>
                  <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                    <polygon
                      points="9.9,1.1 7.6,6.6 1.6,7.6 6,11.9 4.8,17.8 9.9,14.8 15,17.8 13.8,11.9 18.2,7.6 12.2,6.6 " />
                  </svg>
                </div>
                <p class="text-gray-700 text-center mb-4">"Sebenarnya saya tau ini dari temen yang kasih rekomendasi, baru beberapa kali coba cocok dan kedepan akan jadi langganan terus sih sepertinya."</p>
                <span class="font-semibold text-[#2C3E50]">Mbak Muanansa, Malang</span>
              </div>
            </div>
            <!-- Testimonial 2 -->
            <div class="min-w-full px-4">
              <div class="bg-white rounded-2xl shadow-lg p-8 flex flex-col items-center">
                <img src="{{ asset('assets/homepage/testimonial/testimoni-2.png') }}" alt="Testimoni 2" loading="lazy"
                  class="w-20 h-20 rounded-full object-cover mb-4 border-4 border-[#8BA870]">
                <div class="flex mb-2">
                  <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                    <polygon
                      points="9.9,1.1 7.6,6.6 1.6,7.6 6,11.9 4.8,17.8 9.9,14.8 15,17.8 13.8,11.9 18.2,7.6 12.2,6.6 " />
                  </svg>
                  <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                    <polygon
                      points="9.9,1.1 7.6,6.6 1.6,7.6 6,11.9 4.8,17.8 9.9,14.8 15,17.8 13.8,11.9 18.2,7.6 12.2,6.6 " />
                  </svg>
                  <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                    <polygon
                      points="9.9,1.1 7.6,6.6 1.6,7.6 6,11.9 4.8,17.8 9.9,14.8 15,17.8 13.8,11.9 18.2,7.6 12.2,6.6 " />
                  </svg>
                  <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                    <polygon
                      points="9.9,1.1 7.6,6.6 1.6,7.6 6,11.9 4.8,17.8 9.9,14.8 15,17.8 13.8,11.9 18.2,7.6 12.2,6.6 " />
                  </svg>
                  <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                    <polygon
                      points="9.9,1.1 7.6,6.6 1.6,7.6 6,11.9 4.8,17.8 9.9,14.8 15,17.8 13.8,11.9 18.2,7.6 12.2,6.6 " />
                  </svg>
                </div>
                <p class="text-gray-700 text-center mb-4">"Saya sekeluarga cocok, kalau ada acara kantor langsung pesen kesini, orang orang kantor juga pada nanyain beli dimana? Saya beli di kue pandan asli di sby tambaksari"</p>
                <span class="font-semibold text-[#2C3E50]">Pak Handoko, Surabaya</span>
              </div>
            </div>
            <!-- Testimonial 3 -->
            <div class="min-w-full px-4">
              <div class="bg-white rounded-2xl shadow-lg p-8 flex flex-col items-center">
                <img src="{{ asset('assets/homepage/testimonial/testimoni-1.png') }}" alt="Testimoni 3" loading="lazy"
                  class="w-20 h-20 rounded-full object-cover mb-4 border-4 border-[#8BA870]">
                <div class="flex mb-2">
                  <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                    <polygon
                      points="9.9,1.1 7.6,6.6 1.6,7.6 6,11.9 4.8,17.8 9.9,14.8 15,17.8 13.8,11.9 18.2,7.6 12.2,6.6 " />
                  </svg>
                  <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                    <polygon
                      points="9.9,1.1 7.6,6.6 1.6,7.6 6,11.9 4.8,17.8 9.9,14.8 15,17.8 13.8,11.9 18.2,7.6 12.2,6.6 " />
                  </svg>
                  <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                    <polygon
                      points="9.9,1.1 7.6,6.6 1.6,7.6 6,11.9 4.8,17.8 9.9,14.8 15,17.8 13.8,11.9 18.2,7.6 12.2,6.6 " />
                  </svg>
                  <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                    <polygon
                      points="9.9,1.1 7.6,6.6 1.6,7.6 6,11.9 4.8,17.8 9.9,14.8 15,17.8 13.8,11.9 18.2,7.6 12.2,6.6 " />
                  </svg>
                  <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                    <polygon
                      points="9.9,1.1 7.6,6.6 1.6,7.6 6,11.9 4.8,17.8 9.9,14.8 15,17.8 13.8,11.9 18.2,7.6 12.2,6.6 " />
                  </svg>
                </div>
                <p class="text-gray-700 text-center mb-4">"Kue langganan keluarga , dari dulu sering beli sampe kalau mau kasih hampers mau ngasih oleh oleh pasti langsung beli di kue pandan asli, semua orang dikeluarga suka dan cocok sama rasanya."</p>
                <span class="font-semibold text-[#2C3E50]">Adhitya, Denpasar</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Outlet Location Section -->
  <section id="outlet-location" class="w-full py-8" style="background-color: bg-whhite;">
    <div class="w-[90%] max-w-5xl mx-auto">
      <div class="text-center mb-10">
        <span class="text-[#8BA870] font-medium tracking-wider text-sm mb-3 block">Outlet Location</span>
        <h2 class="text-3xl md:text-4xl font-bold text-[#2C3E50] mb-2">Lokasi Outlet Kami</h2>
        <p class="text-gray-600 max-w-2xl mx-auto text-lg leading-relaxed">
          Temukan outlet Kue Pandan Asli terdekat di kota Anda.
        </p>
      </div>
      <div class="flex justify-center mb-8 gap-4" data-aos="fade-down">
        <button id="btn-surabaya"
          class="outlet-btn bg-[#8BA870] text-white px-5 py-2 rounded-lg font-medium shadow hover:bg-[#7a965e] transition">Surabaya</button>
        <button id="btn-malang"
          class="outlet-btn bg-[#8BA870] text-white px-5 py-2 rounded-lg font-medium shadow hover:bg-[#7a965e] transition">Malang</button>
        <button id="btn-denpasar"
          class="outlet-btn bg-[#8BA870] text-white px-5 py-2 rounded-lg font-medium shadow hover:bg-[#7a965e] transition">Denpasar</button>

      </div>
      <div id="outlet-content" data-aos="fade-up"
        class="bg-soft-green rounded-2xl shadow-2xl p-6 flex flex-col md:flex-row gap-6 items-center">
        <!-- Google Maps -->
        <div class="w-full md:w-1/2 flex justify-center items-center">
          <iframe id="outlet-map"
            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3957.9681779681105!2d112.775769691843!3d-7.244461222219543!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2dd7f9b0b298d195%3A0x1b301a8958c157c6!2sKue%20Ijo%20Pandan%20Asli!5e0!3m2!1sid!2sid!4v1753154879994!5m2!1sid!2sid"
            width="100%" height="500"
            style="border:0; border-radius:1rem; box-shadow:0 2px 16px 0 rgba(44,62,80,0.08); max-width: 480px; min-width: 320px; display: block; margin: 0 auto; background: #eee;"
            allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
        </div>
        <!-- Outlet Info -->
        <div class="w-full md:w-1/2 flex flex-col gap-4">
          <img id="outlet-img" loading="lazy"
            src="{{ asset('assets/homepage/b1.jpg') }}"
            alt="Outlet Surabaya" class="w-full h-36 object-cover rounded-xl mb-2 shadow">
          <h3 id="outlet-title" class="text-2xl font-bold text-[#2C3E50] mb-1">Pusat Surabaya</h3>
          <p id="outlet-address" class="text-gray-700 mb-1">Jalan Lebak Jaya II no. 26A Gading, Tambaksari, Surabaya, Jawa Timur 60134 (Rumah pagar hitam)</p>
          <!-- Jam Buka -->
          <p id="outlet-hours" class="flex items-center gap-2 text-gray-500 text-sm mb-1">
            <i class="far fa-clock text-lg"></i>
            Senin - Minggu, 08.00 - 20.00
          </p>

          <!-- WhatsApp -->
          <a id="outlet-contact" href="#" target="_blank"
            class="flex items-center gap-2 text-gray-500 text-sm mb-2 hover:text-green-600 transition">
            <i class="fab fa-whatsapp text-lg"></i>
            <span id="outlet-contact-text">Telp: -</span>
          </a>

          <!-- Email -->
          <a id="outlet-email"
            href="#"
            class="flex items-center gap-2 text-gray-500 text-sm hover:text-blue-600 transition">
            <i class="far fa-envelope text-lg"></i>
            <span id="outlet-email-text">pandanaslisbyadm@gmail.com</span>
          </a>

          <!-- Sosial Media -->
          <div class="flex items-center gap-4 text-2xl text-gray-600">
            <a id="social-tiktok" href="#" target="_blank" class="hover:text-black transition" aria-label="TikTok">
              <i class="fab fa-tiktok"></i>
            </a>
            <a id="social-instagram" href="#" target="_blank" class="hover:text-pink-500 transition" aria-label="Instagram">
              <i class="fab fa-instagram"></i>
            </a>
            <a id="social-facebook" href="#" target="_blank" class="hover:text-blue-600 transition" aria-label="Facebook">
              <i class="fab fa-facebook"></i>
            </a>
          </div>

          <div class="w-full">
            <a
              id="outlet-directions"
              href="https://maps.app.goo.gl/FBLH5zD3sq1wBYit8"
              target="_blank"
              class="w-full flex justify-center items-center bg-[#8BA870] text-white px-5 py-2 rounded-lg font-medium shadow hover:bg-[#7a965e] transition">
              Get Directions
            </a>
          </div>
        </div>

      </div>
  </section>

  <!-- Footer -->
  <footer class="bg-black text-white mt-auto">
    <div class="mx-auto w-full max-w-screen-xl p-4 py-6 lg:py-8">
      <div class="md:flex md:justify-between">
        <div class="mb-6 md:mb-0">
          <a href="/" class="flex flex-col items-start">
            <img src="{{ asset('assets/homepage/logo.png') }}" alt="Logo" class="h-8 w-8 mb-2 rounded-full object-cover">
            <span class="text-left text-2xl font-semibold whitespace-nowrap">Kue Pandan Asli</span>
          </a>
          <p class="mt-2 text-gray-400 max-w-xs text-sm">Kue Pandan Asli, kami adalah perusahaan kuliner yang berfokus pada produksi dan pengembangan kue tradisional berbahan alami tanpa campuran pengawet dan pewarna.</p>
        </div>
        <div class="grid grid-cols-2 gap-8 sm:gap-6 sm:grid-cols-3">
          <div>
            <h2 class="mb-6 text-sm font-semibold uppercase">Social Media</h2>
            <ul class="text-gray-400 font-medium">
              <li class="mb-4">
                <a href="https://www.instagram.com/pandanaslimalang/" class="hover:underline">Instagram</a>
              </li>
              <li class="mb-4">
                <a href="https://www.tiktok.com/@pandanasli_malang" class="hover:underline">Tiktok</a>
              </li>
              <li>
                <a href="https://www.facebook.com/profile.php?id=61557399493559&ref=pro_upsell_xav_ig_profile_page_web#" class="hover:underline">Facebook</a>
              </li>
            </ul>
          </div>
          <div>
            <h2 class="mb-6 text-sm font-semibold uppercase">Useful Link</h2>
            <ul class="text-gray-400 font-medium">
              <li class="mb-4">
                <a href="#tentang-kami" class="hover:underline">Tentang Kami</a>
              </li>
              <li class="mb-4">
                <a href="#testimoni" class="hover:underline">Testimoni</a>
              </li>
              <li class="mb-4">
                <a href="#outlet-location" class="hover:underline">Outlet Location</a>
              </li>
              <li>
                <a href="#produk-kami" class="hover:underline">Produk Kami</a>
              </li>
            </ul>
          </div>
          <div>
            <h2 class="mb-6 text-sm font-semibold uppercase">Katalog</h2>
            <ul class="text-gray-400 font-medium">
              <li class="mb-4">
                <a href="#" class="hover:underline">Download Katalog</a>
              </li>
            </ul>
          </div>
        </div>
      </div>
      <hr class="my-6 border-gray-700 sm:mx-auto lg:my-8" />
      <div class="sm:flex sm:items-center sm:justify-center p-4">
        <span class="text-sm text-gray-400 sm:text-center">© Copyright 2010 - 2025 <a href="#" class="hover:underline">Kue Pandan
            Asli</a><br>All Rights Reserved.
        </span>
      </div>
    </div>
  </footer>

  <!-- Pass asset URLs to JavaScript -->
  <script>
    window.assetUrls = {
      outletImages: {
        surabaya: "{{ asset('assets/homepage/b1.jpg') }}",
        malang: "{{ asset('assets/homepage/b2.jpg') }}",
        denpasar: "{{ asset('assets/homepage/b3.jpg') }}"
      }
    };
  </script>
</body>

</html>