document.addEventListener("DOMContentLoaded", function () {
    // --- Bagian 1: Animasi Konten (Opsional) ---
    const tabContent = document.getElementById("tab-content");
    if (tabContent) {
        setTimeout(() => {
            tabContent.classList.remove("translate-x-full", "opacity-0");
            tabContent.classList.add("translate-x-0", "opacity-100");
        }, 100);
    }

    // --- Bagian 2: Logika Sidebar (Mobile & Desktop) ---

    // A. PEMILIHAN SEMUA ELEMEN
    const sidebar = document.getElementById("sidebar");
    const mainContent = document.getElementById("main-content");
    
    // Elemen untuk Mobile
    const overlay = document.getElementById('sidebar-overlay');
    const openButtons = document.querySelectorAll('[sidenav-open]');
    const closeButtons = document.querySelectorAll('[sidenav-close]');

    // Elemen untuk Desktop
    const SIDEBAR_STATE_KEY = "sidebarCollapsed";
    const toggleButton = document.querySelector("a[sidenav-trigger]");
    const logoLink = document.getElementById("sidebar-logo-link");

    // B. FUNGSI-FUNGSI
    const openSidebarMobile = () => {
        if (sidebar && overlay) {
            sidebar.classList.add('translate-x-0');
            sidebar.classList.remove('-translate-x-full');
            overlay.classList.remove('hidden');
        }
    };

    const closeSidebarMobile = () => {
        if (sidebar && overlay) {
            sidebar.classList.remove('translate-x-0');
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
        }
    };

    const applySidebarState = (isCollapsed) => {
        if (!sidebar || !mainContent || !logoLink) return;

        sidebar.classList.toggle("max-w-64", !isCollapsed);
        sidebar.classList.toggle("max-w-20", isCollapsed);

        mainContent.classList.toggle("xl:ml-68", !isCollapsed);
        mainContent.classList.toggle("xl:ml-24", isCollapsed);

        logoLink.classList.toggle("px-8", !isCollapsed);
        logoLink.classList.toggle("px-4", isCollapsed);
        logoLink.classList.toggle("justify-center", isCollapsed);

        document.querySelectorAll(".sidenav-text").forEach((text) => {
            text.classList.toggle("hidden", isCollapsed);
            text.classList.toggle("opacity-0", isCollapsed);
        });

        const logoImg = document.getElementById("sidebar-logo-img");
        if (logoImg) {
            if (logoImg.dataset.logoFull && logoImg.dataset.logoIcon) {
                logoImg.src = isCollapsed ? logoImg.dataset.logoIcon : logoImg.dataset.logoFull;
            }
            logoImg.classList.toggle('w-8', isCollapsed);
            logoImg.classList.toggle('flex-shrink-0', isCollapsed);
            logoImg.classList.toggle('w-auto', !isCollapsed);
        }
        
        const icon = toggleButton.querySelector("i");
        if (icon) {
            icon.classList.toggle("rotate-180", isCollapsed);
        }
    };

    // C. PEMASANGAN EVENT LISTENERS
    
    // Listeners untuk Mobile
    openButtons.forEach(btn => btn.addEventListener('click', openSidebarMobile));
    closeButtons.forEach(btn => btn.addEventListener('click', closeSidebarMobile));
    if (overlay) {
        overlay.addEventListener('click', closeSidebarMobile);
    }
    
    // Listeners untuk Desktop
    if (toggleButton) {
        toggleButton.addEventListener("click", () => {
            const isCurrentlyCollapsed = sidebar.classList.contains("max-w-20");
            const newState = !isCurrentlyCollapsed;
            applySidebarState(newState);
            localStorage.setItem(SIDEBAR_STATE_KEY, newState);
        });

        // --- PERUBAHAN DI SINI ---
        // Terapkan state awal HANYA JIKA di layar desktop (xl breakpoint = 1280px)
        if (window.innerWidth >= 1280) {
            const initialCollapsedState = localStorage.getItem(SIDEBAR_STATE_KEY) === "true";
            applySidebarState(initialCollapsedState);
        }
    }
});