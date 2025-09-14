(function(){function t(){setTimeout(()=>{const e=document.getElementById("navbar-main");if(!e){console.log("Navbar not found");return}console.log("Green navbar scroll initialized");const o=document.createElement("style");o.id="navbar-green-scroll-styles",o.textContent=`
        #navbar-main {
          transition: all 0.3s ease-out !important;
          /* background-color dihapus agar tidak override kelas Tailwind */
        }

        /* Light mode scrolled navbar */
        #navbar-main.scrolled:not(.dark) {
          box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15) !important;
          background-color: #97b67d !important; /* bg-greenlight solid saat scroll */
          /* Removed backdrop-filter to prevent stacking context issues */
        }

        /* Dark mode scrolled navbar */
        #navbar-main.scrolled.dark,
        .dark #navbar-main.scrolled {
          box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3) !important;
          background-color: #0f172a !important; /* dark:bg-slate-900 */
        }

        /* Mobile styles */
        @media (max-width: 768px) {
          /* Light mode mobile */
          #navbar-main.scrolled:not(.dark) {
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.2) !important;
            background-color: #97b67d !important; /* bg-greenlight solid saat scroll */
          }
          
          /* Dark mode mobile */
          #navbar-main.scrolled.dark,
          .dark #navbar-main.scrolled {
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.4) !important;
            background-color: #0f172a !important; /* dark:bg-slate-900 */
          }
        }

        /* Ensure profile dropdown works in scrolled state */
        #navbar-main.scrolled .group:hover .absolute {
          opacity: 1 !important;
          transform: scale(1) !important;
          pointer-events: auto !important;
          z-index: 99999 !important;
        }
      `;const s=document.getElementById("navbar-green-scroll-styles");s&&s.remove(),document.head.appendChild(o);function r(){const a=window.scrollY||window.pageYOffset,n=document.documentElement.classList.contains("dark")||document.body.classList.contains("dark");a>0?(e.classList.add("scrolled"),n?e.classList.add("dark"):e.classList.remove("dark")):(e.classList.remove("scrolled"),e.classList.remove("dark"))}function i(){const a=new MutationObserver(function(n){n.forEach(function(l){l.type==="attributes"&&l.attributeName==="class"&&r()})});a.observe(document.documentElement,{attributes:!0,attributeFilter:["class"]}),a.observe(document.body,{attributes:!0,attributeFilter:["class"]})}window.addEventListener("scroll",r,{passive:!0}),i(),r()},100)}document.readyState==="loading"?document.addEventListener("DOMContentLoaded",t):t(),window.addEventListener("load",t)})();
