window.initializeLiveSearch=function(r){const s=document.getElementById(r.searchInputId),t=document.getElementById(r.desktopContainerId),n=document.getElementById(r.mobileContainerId);if(!s||!t&&!n){console.error("LiveSearch Error: Search input or at least one result container not found.");return}const l=`
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
        </tr>`;let d;const m=r.debounceTime||300;s.addEventListener("input",c=>{clearTimeout(d),d=setTimeout(()=>{h(c.target.value)},m)});async function h(c){t&&(t.innerHTML=l),n&&(n.innerHTML=`<div class="text-center p-4">${l}</div>`);const o=new URL(window.location.href);o.searchParams.set("search",c),o.searchParams.set("page",1);try{const a=await fetch(o.toString(),{method:"GET",headers:{"X-Requested-With":"XMLHttpRequest",Accept:"application/json"}});if(!a.ok)throw new Error("Network response was not ok");const e=await a.json();t&&e.desktop_html&&(t.innerHTML=e.desktop_html),n&&e.mobile_html&&(n.innerHTML=e.mobile_html);let i=document.getElementById("customer-modals-container");i||(i=document.getElementById("courier-modals-container")),i&&e.modals_html&&(i.innerHTML=e.modals_html),history.pushState({},"",o.toString())}catch(a){console.error("LiveSearch Fetch Error:",a);const e='<tr><td colspan="100%" class="text-center text-red-500 p-4">Gagal memuat data.</td></tr>';t&&(t.innerHTML=e),n&&(n.innerHTML='<div class="text-center p-4 text-red-500">Gagal memuat data.</div>')}}};
