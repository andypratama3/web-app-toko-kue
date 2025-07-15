      window.addEventListener('load', function() {
    const preloader = document.getElementById('preloader');
    preloader.style.display = 'none';
  });

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
    
    // Auto-scroll testimonial carousel
    const track = document.getElementById('testimonial-track');
    const total = track.children.length;
    let idx = 0;
    setInterval(() => {
      idx = (idx + 1) % total;
      track.style.transform = `translateX(-${idx * 100}%)`;
    }, 4000);

    // Data outlet
    const outlets = {
      surabaya: {
        map: 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d27407.361567370317!2d112.667856172412!3d-7.30505313126888!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2dd7fc3a2785bf49%3A0x86974c8847438953!2sAzko%20-%20Lenmarc%20Mall%20Surabaya!5e0!3m2!1sid!2sid!4v1752289089994!5m2!1sid!2sid',
        img: 'https://images.unsplash.com/photo-1519125323398-675f0ddb6308?auto=format&fit=crop&w=400&q=80',
        title: 'Outlet Surabaya',
        address: 'Jl. Raya Darmo No. 99, Surabaya',
        hours: 'Senin - Minggu, 08.00 - 20.00',
        contact: 'Telp: 031-1234567',
        directions: 'https://maps.app.goo.gl/451F7tNyDLVXx3pt6'
      },
      malang: {
        map: 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3952.934234!2d112.6304!3d-7.9775!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2dd6281b1b1b1b1b%3A0x1b1b1b1b1b1b1b1b!2sMalang!5e0!3m2!1sen!2sid!4v1680000000001!5m2!1sen!2sid',
        img: 'https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=400&q=80',
        title: 'Outlet Malang',
        address: 'Jl. Ijen No. 88, Malang',
        hours: 'Senin - Minggu, 08.00 - 21.00',
        contact: 'Telp: 0341-7654321',
        directions: 'https://goo.gl/maps/efgh5678'
      },
      denpasar: {
        map: 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3944.934234!2d115.2204!3d-8.6575!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2dd2471b1b1b1b1b%3A0x1b1b1b1b1b1b1b1b!2sDenpasar!5e0!3m2!1sen!2sid!4v1680000000002!5m2!1sen!2sid',
        img: 'https://images.unsplash.com/photo-1464983953574-0892a716854b?auto=format&fit=crop&w=400&q=80',
        title: 'Outlet Denpasar',
        address: 'Jl. Teuku Umar No. 123, Denpasar',
        hours: 'Senin - Minggu, 09.00 - 21.00',
        contact: 'Telp: 0361-9876543',
        directions: 'https://goo.gl/maps/ijkl9101'
      }
    };
    // Button event
    document.getElementById('btn-surabaya').onclick = function() {
      setOutlet('surabaya');
      setActive(this);
    };
    document.getElementById('btn-malang').onclick = function() {
      setOutlet('malang');
      setActive(this);
    };
    document.getElementById('btn-denpasar').onclick = function() {
      setOutlet('denpasar');
      setActive(this);
    };
    function setOutlet(key) {
      const o = outlets[key];
      document.getElementById('outlet-map').src = o.map;
      document.getElementById('outlet-img').src = o.img;
      document.getElementById('outlet-title').textContent = o.title;
      document.getElementById('outlet-address').textContent = o.address;
      document.getElementById('outlet-hours').textContent = o.hours;
      document.getElementById('outlet-contact').textContent = o.contact;
      document.getElementById('outlet-directions').href = o.directions;
    }
    function setActive(btn) {
      document.querySelectorAll('.outlet-btn').forEach(b => {
        b.classList.remove('bg-[#8BA870]','text-white');
        b.classList.add('bg-white','text-[#8BA870]','border','border-[#8BA870]');
      });
      btn.classList.add('bg-[#8BA870]','text-white');
      btn.classList.remove('bg-white','text-[#8BA870]','border','border-[#8BA870]');
    }

    AOS.init({
        duration: 800, // durasi animasi dalam ms
        once: false, // apakah animasi hanya terjadi sekali
    });