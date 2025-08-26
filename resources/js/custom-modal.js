function initializeCustomUI() {
    // --- FUNGSI-FUNGSI UTAMA ---
    const openModal = (modalId) => {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.classList.add('overflow-hidden');
        }
    };

    const closeModal = (modalElement) => {
        if (modalElement) {
            modalElement.classList.add('hidden');
            modalElement.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
        }
    };

    const toggleDropdown = (dropdownId) => {
        const dropdown = document.getElementById(dropdownId);
        if (dropdown) {
            // Tutup semua dropdown lain terlebih dahulu
            document.querySelectorAll('.js-dropdown-menu').forEach(otherDropdown => {
                if (otherDropdown.id !== dropdownId) {
                    otherDropdown.classList.add('hidden');
                }
            });
            // Buka/tutup dropdown yang ditargetkan
            dropdown.classList.toggle('hidden');
        }
    };

    // --- EVENT LISTENER UTAMA (EVENT DELEGATION) ---
    document.addEventListener('click', function (event) {
        const target = event.target;

        // --- Logika untuk Modal ---
        const openModalBtn = target.closest('.js-open-modal-btn');
        if (openModalBtn) {
            event.preventDefault();
            const modalId = openModalBtn.getAttribute('data-target-modal');
            openModal(modalId);
            return;
        }

        const closeModalBtn = target.closest('.js-close-modal-btn');
        if (closeModalBtn) {
            event.preventDefault();
            const modal = closeModalBtn.closest('[role="dialog"]');
            closeModal(modal);
            return;
        }

        const modalOverlay = target.closest('[role="dialog"]');
        if (modalOverlay && target === modalOverlay) {
            closeModal(modalOverlay);
            return;
        }

        // --- Logika untuk Dropdown ---
        const dropdownToggleBtn = target.closest('.js-dropdown-toggle');
        if (dropdownToggleBtn) {
            event.preventDefault();
            const dropdownId = dropdownToggleBtn.getAttribute('data-target-dropdown');
            toggleDropdown(dropdownId);
            return;
        }

        // Tutup semua dropdown jika klik di luar area dropdown
        if (!target.closest('.js-dropdown-toggle') && !target.closest('.js-dropdown-menu')) {
            document.querySelectorAll('.js-dropdown-menu').forEach(dropdown => {
                dropdown.classList.add('hidden');
            });
        }
    });
}

document.addEventListener('DOMContentLoaded', initializeCustomUI);
