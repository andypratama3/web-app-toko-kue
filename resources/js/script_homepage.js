// ===== Utilities =====
function normalizePhoneTo62(raw) {
  if (!raw) return "";
  let d = String(raw).replace(/\D+/g, "");
  if (d.startsWith("+62")) d = "62" + d.slice(3);
  else if (d.startsWith("0")) d = "62" + d.slice(1);
  else if (d.startsWith("8")) d = "62" + d;
  return d;
}
function buildWaLink(raw) {
  const n = normalizePhoneTo62(raw);
  return n ? `https://wa.me/${n}` : "#";
}
function buildMailto(email, { subject = "", body = "" } = {}) {
  if (!email) return "#";
  const q = new URLSearchParams();
  if (subject) q.set("subject", subject);
  if (body) q.set("body", body);
  return `mailto:${email}${q.toString() ? "?" + q.toString() : ""}`;
}
function setHrefOrDisable(el, href) {
  if (!el) return;
  const disabled = !href || href === "#";
  el.href = disabled ? "#" : href;
  el.target = disabled ? "_self" : "_blank";
  el.classList.toggle("pointer-events-none", disabled);
  el.classList.toggle("opacity-60", disabled);
}

// ===== On load: preloader =====
window.addEventListener("load", function () {
  const preloader = document.getElementById("preloader");
  if (preloader) preloader.style.display = "none";
});

// ===== AOS & Zoom =====
if (window.AOS) AOS.init({ duration: 700, once: true });
if (window.mediumZoom) mediumZoom(".zoomable", { background: "rgba(0,0,0,0.7)", margin: 24, scrollOffset: 40 });

// ===== Hamburger =====
const hamburgerBtn = document.getElementById("hamburger-btn");
const mobileMenu = document.getElementById("mobile-menu");
let menuOpen = false;
if (hamburgerBtn && mobileMenu) {
  hamburgerBtn.addEventListener("click", () => {
    menuOpen = !menuOpen;
    mobileMenu.classList.toggle("opacity-0", !menuOpen);
    mobileMenu.classList.toggle("pointer-events-none", !menuOpen);
    mobileMenu.classList.toggle("opacity-100", menuOpen);
  });
  mobileMenu.addEventListener("click", (e) => {
    if (e.target === mobileMenu) {
      menuOpen = false;
      mobileMenu.classList.add("opacity-0", "pointer-events-none");
      mobileMenu.classList.remove("opacity-100");
    }
  });
}

// ===== Navbar scroll =====
const navbar = document.getElementById("navbar");
window.addEventListener("scroll", () => {
  if (!navbar) return;
  if (window.scrollY > 10) {
    navbar.classList.add("bg-white/80", "backdrop-blur", "shadow-md");
    navbar.classList.remove("bg-white");
  } else {
    navbar.classList.remove("bg-white/80", "backdrop-blur");
    navbar.classList.add("bg-white");
  }
});

// ===== Testimonial carousel =====
const track = document.getElementById("testimonial-track");
if (track && track.children.length) {
  const total = track.children.length;
  let idx = 0;
  setInterval(() => {
    idx = (idx + 1) % total;
    track.style.transform = `translateX(-${idx * 100}%)`;
  }, 4000);
}

// ===== Data outlet (tambahan: wa & social) =====
const outlets = {
  surabaya: {
    map: "https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3957.9681779681105!2d112.775769691843!3d-7.244461222219543!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2dd7f9b0b298d195%3A0x1b301a8958c157c6!2sKue%20Ijo%20Pandan%20Asli!5e0!3m2!1sid!2sid!4v1753154879994!5m2!1sid!2sid",
    img: window.assetUrls?.outletImages?.surabaya || "/assets/homepage/b1.jpg",
    title: "Pusat Surabaya",
    address: "Jl. Lebak Jaya II No.26, RT.005/RW.04, Gading, Kec. Tambaksari, Surabaya, Jawa Timur 60134",
    hours: "Senin - Minggu, 08.00 - 20.00",
    contact: "Telp: 082144834303",
    wa: "082144834303",
    email: "pandanaslisbyadm@gmail.com",
    directions: "https://maps.app.goo.gl/FBLH5zD3sq1wBYit8",
    social: {
      tiktok: "#",
      instagram: "https://www.instagram.com/pandanasli",
      facebook: "#",
    },
  },
  malang: {
    map: "https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3951.4563986431112!2d112.6579965!3d-7.951697299999999!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2dd629afaf867ba3%3A0x9aa041e45fac81a8!2sJl.%20Graha%20Pelita%20Asri%20No.b29%2C%20Pandanwangi%2C%20Kec.%20Blimbing%2C%20Kota%20Malang%2C%20Jawa%20Timur%2065124!5e0!3m2!1sid!2sid!4v1753156426758!5m2!1sid!2sid",
    img: window.assetUrls?.outletImages?.malang || "/assets/homepage/b2.jpg",
    title: "Outlet Malang",
    address: "Jl. Graha Pelita Asri No.b29, Pandanwangi, Kec. Blimbing, Kota Malang, Jawa Timur 65124",
    hours: "Senin - Minggu, 08.00 - 21.00",
    contact: "Telp: 082131338971",
    wa: "082131338971", // pilih Malang -> direct ke WA Malang
    email: "pandanaslimalangadm@gmail.com",
    directions: "https://maps.app.goo.gl/fyw33cyHhwBuAyQE8",
    social: {
      tiktok: "https://www.tiktok.com/@pandanasli_malang",
      instagram: "https://www.instagram.com/pandanaslimalang",
      facebook: "https://www.facebook.com/pandanaslimalang",
    },
  },
  denpasar: {
    map: "https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3943.832310607487!2d115.22523580000001!3d-8.7074693!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2dd2410d9d4cfba5%3A0xf2eb89848c9d9c5a!2sGg.%20Ikan%20Arwana%20No.6%2C%20Sesetan%2C%20Denpasar%20Selatan%2C%20Kota%20Denpasar%2C%20Bali%2080224!5e0!3m2!1sid!2sid!4v1753156639022!5m2!1sid!2sid",
    img: window.assetUrls?.outletImages?.denpasar || "/assets/homepage/b3.jpg",
    title: "Outlet Denpasar",
    address: "Gg. Ikan Arwana No.6, Sesetan, Denpasar Selatan, Kota Denpasar, Bali 80224",
    hours: "Senin - Minggu, 09.00 - 21.00",
    contact: "Telp: 082338901223",
    wa: "082338901223", // tidak ada WA? nanti auto nonaktif
    email: "pandanaslibaliadm@gmail.com",
    directions: "https://maps.app.goo.gl/YA8hKxqigziBTiXf7",
    social: {
      tiktok: "https://www.tiktok.com/@pandanasli_bali",
      instagram: "https://www.instagram.com/pandan_baliasli",
      facebook: "#",
    },
  },
};

// ===== Buttons =====
const btnSby = document.getElementById("btn-surabaya");
const btnMlg = document.getElementById("btn-malang");
const btnDps = document.getElementById("btn-denpasar");
if (btnSby) btnSby.onclick = function () { setOutlet("surabaya"); setActive(this); };
if (btnMlg) btnMlg.onclick = function () { setOutlet("malang"); setActive(this); };
if (btnDps) btnDps.onclick = function () { setOutlet("denpasar"); setActive(this); };

// ===== Core: setOutlet =====
function setOutlet(key) {
  const o = outlets[key];
  const outletContent = document.getElementById("outlet-content");
  if (!o || !outletContent) return;

  // Fade out
  outletContent.style.opacity = "0";
  outletContent.style.transform = "translateY(10px)";
  outletContent.style.transition = "all 0.3s ease-in-out";

  setTimeout(() => {
    // Map & gambar
    const mapEl = document.getElementById("outlet-map");
    const imgEl = document.getElementById("outlet-img");
    if (mapEl) mapEl.src = o.map;
    if (imgEl) imgEl.src = o.img;

    // Teks umum
    const titleEl = document.getElementById("outlet-title");
    const addrEl  = document.getElementById("outlet-address");
    const hoursEl = document.getElementById("outlet-hours");
    if (titleEl) titleEl.textContent = o.title;
    if (addrEl)  addrEl.textContent  = o.address;
    if (hoursEl) hoursEl.textContent = o.hours;

    // WhatsApp (anchor dengan id="outlet-contact")
    const contactA = document.getElementById("outlet-contact");
    if (contactA) {
      const telText = (o.contact && o.contact.trim()) ? o.contact : "Telp: -";
      const telNo   = o.wa || telText.replace(/\D+/g, "");
      const waHref  = buildWaLink(telNo);

      // jika ada span text di dalam <a>, update; kalau tidak, set text langsung
      const span = document.getElementById("outlet-contact-text");
      if (span) span.textContent = telText;
      else contactA.textContent = telText;

      setHrefOrDisable(contactA, waHref);
    }

    // Email (anchor dengan id="outlet-email")
    const emailA = document.getElementById("outlet-email");
    const emailTextSpan = document.getElementById("outlet-email-text");
    const mailHref = buildMailto(o.email, {
      subject: `Pertanyaan Outlet ${o.title}`,
      body: `Halo ${o.title},%0D%0A%0D%0ASaya ingin menanyakan ...`
    });
    if (emailA) {
      setHrefOrDisable(emailA, o.email ? mailHref : "#");
      if (emailTextSpan) emailTextSpan.textContent = o.email || "Email: -";
      else emailA.textContent = o.email || "Email: -";
    }

    // Directions
    const dirA = document.getElementById("outlet-directions");
    if (dirA) setHrefOrDisable(dirA, o.directions);

    // Sosial media (anchor id: social-tiktok/instagram/facebook)
    setHrefOrDisable(document.getElementById("social-tiktok"),    o.social?.tiktok    || "#");
    setHrefOrDisable(document.getElementById("social-instagram"), o.social?.instagram || "#");
    setHrefOrDisable(document.getElementById("social-facebook"),  o.social?.facebook  || "#");

    // Fade in
    outletContent.style.opacity = "1";
    outletContent.style.transform = "translateY(0)";
  }, 300);
}

// ===== Active button state =====
function setActive(btn) {
  document.querySelectorAll(".outlet-btn").forEach((b) => {
    b.classList.remove("bg-[#8BA870]", "text-white");
    b.classList.add("bg-white", "text-[#8BA870]", "border", "border-[#8BA870]");
    b.style.transform = "scale(1)";
    b.style.transition = "all 0.3s ease-in-out";
  });
  btn.classList.add("bg-[#8BA870]", "text-white");
  btn.classList.remove("bg-white", "text-[#8BA870]", "border", "border-[#8BA870]");
  btn.style.transform = "scale(1.05)";
  btn.style.transition = "all 0.3s ease-in-out";
  setTimeout(() => { btn.style.transform = "scale(1)"; }, 150);
}

// Set default outlet saat halaman siap
document.addEventListener("DOMContentLoaded", () => setOutlet("malang"));
