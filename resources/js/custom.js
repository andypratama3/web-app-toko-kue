document.addEventListener("DOMContentLoaded", function () {
    const tabContent = document.getElementById("tab-content");
    setTimeout(function () {
        tabContent.classList.remove("translate-x-full", "opacity-0");
        tabContent.classList.add("translate-x-0", "opacity-100");
    }, 100);
});

document.addEventListener("DOMContentLoaded", function () {
    const SIDEBAR_STATE_KEY = "sidebarCollapsed";
    const sidebar = document.getElementById("sidebar");
    const toggleButton = document.getElementById("sidebar-toggle-button");
    const mainContent = document.getElementById("main-content");
    const logoLink = document.getElementById("sidebar-logo-link");
    const icon = toggleButton.querySelector("i");

    // Fungsi untuk menerapkan state (buka/tutup)
    const applySidebarState = (isCollapsed) => {
        if (isCollapsed) {
            sidebar.classList.remove("max-w-64");
            sidebar.classList.add("max-w-20");
            mainContent.classList.remove("xl:ml-68");
            mainContent.classList.add("xl:ml-20");
            logoLink.classList.remove("px-8");
            logoLink.classList.add("px-4", "justify-center");
            document.querySelectorAll(".sidenav-text").forEach((text) => {
                text.classList.add("hidden", "opacity-0");
            });
            icon.classList.add("rotate-180");
        } else {
            sidebar.classList.add("max-w-64");
            sidebar.classList.remove("max-w-20");
            mainContent.classList.add("xl:ml-68");
            mainContent.classList.remove("xl:ml-20");
            logoLink.classList.add("px-8");
            logoLink.classList.remove("px-4", "justify-center");
            document.querySelectorAll(".sidenav-text").forEach((text) => {
                text.classList.remove("hidden", "opacity-0");
            });
            icon.classList.remove("rotate-180");
        }
    };

    // Cek state dari localStorage saat halaman dimuat
    const isCollapsed = localStorage.getItem(SIDEBAR_STATE_KEY) === "true";
    if (sidebar && mainContent && logoLink && icon) {
        applySidebarState(isCollapsed);
    }

    // Event listener untuk tombol toggle
    if (toggleButton) {
        toggleButton.addEventListener("click", () => {
            const currentState = sidebar.classList.contains("max-w-20");
            applySidebarState(!currentState);
            localStorage.setItem(SIDEBAR_STATE_KEY, !currentState);
        });
    }
});
