document.addEventListener("DOMContentLoaded", function () {
    // --- Content Animation (Optional) ---
    const tabContent = document.getElementById("tab-content");
    if (tabContent) {
        setTimeout(() => {
            tabContent.classList.remove("translate-x-full", "opacity-0");
            tabContent.classList.add("translate-x-0", "opacity-100");
        }, 100);
    }

    // Note: Sidebar functionality is now handled by sidenav-burger.js
    // This file is kept for other custom functionality that doesn't conflict with sidebar
});

window.addEventListener("load", function () {
    const preloader = document.getElementById("preloader");
    preloader.style.display = "none";
});
