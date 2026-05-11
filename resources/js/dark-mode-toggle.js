// Dedicated Dark Mode Toggle Script
// This script handles the dark/light mode toggle functionality

document.addEventListener('DOMContentLoaded', function() {
    // Get elements
    const toggle = document.getElementById('theme-toggle-checkbox');
    const html = document.documentElement;
    const lightIcon = document.getElementById('theme-toggle-light-icon');
    const darkIcon = document.getElementById('theme-toggle-dark-icon');
    
    if (!toggle) {
        console.error('Dark mode toggle element not found!');
        return;
    }
    
    // Function to update theme
    function updateTheme(isDark) {
        if (isDark) {
            html.classList.add('dark');
            localStorage.setItem('color-theme', 'dark');
            if (lightIcon) lightIcon.classList.add('hidden');
            if (darkIcon) darkIcon.classList.remove('hidden');
        } else {
            html.classList.remove('dark');
            localStorage.setItem('color-theme', 'light');
            if (lightIcon) lightIcon.classList.remove('hidden');
            if (darkIcon) darkIcon.classList.add('hidden');
        }
    }
    
    // Initialize theme
    function initTheme() {
        const savedTheme = localStorage.getItem('color-theme');
        const currentlyDark = html.classList.contains('dark');
        
        let isDark = savedTheme ? savedTheme === 'dark' : currentlyDark;
        
        // Set toggle state
        toggle.checked = isDark;
        
        // Update icons
        if (isDark) {
            if (lightIcon) lightIcon.classList.add('hidden');
            if (darkIcon) darkIcon.classList.remove('hidden');
        } else {
            if (lightIcon) lightIcon.classList.remove('hidden');
            if (darkIcon) darkIcon.classList.add('hidden');
        }
        
        // Save current state
        localStorage.setItem('color-theme', isDark ? 'dark' : 'light');
    }
    
    // Initialize
    initTheme();
    
    // Add event listeners
    toggle.addEventListener('change', function() {
        updateTheme(this.checked);
    });
    
    // Backup click listener for better compatibility
    toggle.addEventListener('click', function() {
        setTimeout(() => {
            updateTheme(this.checked);
        }, 10);
    });
});
