(function() {
  'use strict';

  function initNavbarScroll() {
    setTimeout(() => {
      const navbar = document.getElementById('navbar-main');

      if (!navbar) {
        console.log('Navbar not found');
        return;
      }

      console.log('Green navbar scroll initialized');

      // Add CSS styles for smooth green navbar transition
      const style = document.createElement('style');
      style.id = 'navbar-green-scroll-styles';
      style.textContent = `
        #navbar-main {
          transition: all 0.3s ease-out !important;
          /* background-color dihapus agar tidak override kelas Tailwind */
        }

        #navbar-main.scrolled {
          box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15) !important;
          backdrop-filter: blur(8px) !important;
          -webkit-backdrop-filter: blur(8px) !important;
          background-color: #97b67d !important; /* bg-greenlight solid saat scroll */
        }

        /* Tetap hijau tapi dengan shadow yang lebih dalam saat scroll */
        @media (max-width: 768px) {
          #navbar-main.scrolled {
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.2) !important;
            background-color: #97b67d !important; /* bg-greenlight solid saat scroll */
          }
        }
      `;

      // Remove existing style if any
      const existingStyle = document.getElementById('navbar-green-scroll-styles');
      if (existingStyle) {
        existingStyle.remove();
      }

      document.head.appendChild(style);

      function handleScroll() {
        const scrollY = window.scrollY || window.pageYOffset;

        // Tambahkan class scrolled untuk efek shadow yang lebih dalam
        if (scrollY > 0) {
          navbar.classList.add('scrolled');
        } else {
          navbar.classList.remove('scrolled');
        }
      }

      // Add scroll event listener
      window.addEventListener('scroll', handleScroll, { passive: true });

      // Initial check
      handleScroll();

    }, 100);
  }

  // Initialize
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initNavbarScroll);
  } else {
    initNavbarScroll();
  }

  window.addEventListener('load', initNavbarScroll);

})();
