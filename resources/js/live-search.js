/**
 * Initializes a live search component on a page.
 *
 * @param {object} options - The configuration options.
 * @param {string} options.searchInputId - The ID of the search input element.
 * @param {string} options.resultsContainerId - The ID of the container where results will be rendered.
 * @param {string} options.loadingStateHtml - HTML to display while loading results.
 * @param {number} [options.debounceTime=300] - Time in ms to wait after user stops typing.
 */
window.initializeLiveSearch = function (options) {
    const searchInput = document.getElementById(options.searchInputId);
    const desktopContainer = document.getElementById(
        options.desktopContainerId
    );
    const mobileContainer = document.getElementById(options.mobileContainerId);

    if (!searchInput || (!desktopContainer && !mobileContainer)) {
        console.error(
            "LiveSearch Error: Search input or at least one result container not found."
        );
        return;
    }

    // Default loading state
    const loadingHtml =
        options.loadingStateHtml ||
        `
        <tr>
            <td colspan="100%" class="text-center p-4">
                <div class="flex justify-center items-center">
                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-gray-700" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Mencari...</span>
                </div>
            </td>
        </tr>`;

    let debounceTimer;

    searchInput.addEventListener("input", (e) => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            performSearch(e.target.value);
        }, 300);
    });

    async function performSearch(query) {
        // Tampilkan loading state jika ada (opsional)
        if (desktopContainer)
            desktopContainer.innerHTML = `<tr><td colspan="100%" class="text-center p-4">Mencari...</td></tr>`;
        if (mobileContainer)
            mobileContainer.innerHTML = `<div class="text-center p-4">Mencari...</div>`;

        const url = new URL(window.location.href);
        url.searchParams.set("search", query);

        try {
            const response = await fetch(url.toString(), {
                method: "GET",
                headers: { "X-Requested-With": "XMLHttpRequest" },
            });

            if (!response.ok) throw new Error("Network response was not ok");

            // Ubah dari .text() menjadi .json()
            const data = await response.json();

            // Perbarui kedua kontainer dengan HTML yang sesuai
            if (desktopContainer) {
                desktopContainer.innerHTML = data.desktop_html;
            }
            if (mobileContainer) {
                mobileContainer.innerHTML = data.mobile_html;
            }

            history.pushState({}, "", url.toString());
        } catch (error) {
            console.error("LiveSearch Fetch Error:", error);
            // Handle error state
        }
    }
};
