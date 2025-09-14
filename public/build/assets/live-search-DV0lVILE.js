window.initializeLiveSearch=function(n){const i=document.getElementById(n.searchInputId),e=document.getElementById(n.desktopContainerId),t=document.getElementById(n.mobileContainerId);if(!i||!e&&!t){console.error("LiveSearch Error: Search input or at least one result container not found.");return}const s=`
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
        </tr>`;let l;const d=n.debounceTime||300;i.addEventListener("input",c=>{clearTimeout(l),l=setTimeout(()=>{m(c.target.value)},d)});async function m(c){e&&(e.innerHTML=s),t&&(t.innerHTML=`<div class="text-center p-4">${s}</div>`);const o=new URL(window.location.href);o.searchParams.set("search",c),o.searchParams.set("page",1);try{const a=await fetch(o.toString(),{method:"GET",headers:{"X-Requested-With":"XMLHttpRequest",Accept:"application/json"}});if(!a.ok)throw new Error("Network response was not ok");const r=await a.json();e&&r.desktop_html&&(e.innerHTML=r.desktop_html),t&&r.mobile_html&&(t.innerHTML=r.mobile_html),history.pushState({},"",o.toString())}catch(a){console.error("LiveSearch Fetch Error:",a);const r='<tr><td colspan="100%" class="text-center text-red-500 p-4">Gagal memuat data.</td></tr>';e&&(e.innerHTML=r),t&&(t.innerHTML='<div class="text-center p-4 text-red-500">Gagal memuat data.</div>')}}};
