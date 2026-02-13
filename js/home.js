/* ================================================
   home.js - Home Page Hero Slider Functionality
   KOD Müzik Website
   
   Contents:
   1. Configuration
   2. Slider State
   3. DOM Building
   4. Image Loading
   5. Slider Controls
   6. Event Handlers
   7. Auto-advance
   8. Initialization
   ================================================ */

// 1. Configuration
// ================================================
const slideData = [
  {
    src: "/img/hero/hero-hiromis-sonicwonder-2025-06-15-istanbul-is-sanat-16x9.webp",
    artist: "Hiromi's Sonicwonder",
    city: "İstanbul",
    venue: "İş Sanat",
    date: "2025",
    objectPosition: "center 35%", // Optional focal point
  },
  {
    src: "/img/hero/hero-alex-skolnick-trio-2025-03-28-istanbul-crr-konser-salonu-16x9.webp",
    artist: "Alex Skolnick Trio",
    city: "İstanbul",
    venue: "CRR Konser Salonu",
    date: "2025",
  },
  {
    src: "/img/hero/hero-nova-muzak-40-hackedepicciotto-rafael-toral-2025-06-10-istanbul-borusan-muzik-evi-16x9.webp",
    artist: "Nova Muzak 40: Hackedepicciotto & Rafael Toral",
    city: "İstanbul",
    venue: "Borusan Müzik Evi",
    date: "2025",
  },
];

const ctaConfig = {
  tr: { text: "Geçmiş Etkinlikler", href: "/etkinlikler" },
  en: { text: "Past Events", href: "/en/etkinlikler" },
};

// 2. Slider State
// ================================================
let currentSlide = 0;
let autoAdvanceTimer = null;
let resumeTimeout = null;
let isTransitioning = false;
let touchStartX = 0;
let touchStartY = 0;
let sliderWrapper = null;
const currentLocale = detectCurrentLanguage();
const prefersReducedMotion = window.matchMedia(
  "(prefers-reduced-motion: reduce)",
).matches;

// 3. DOM Building
// ================================================
function buildSlider() {
  const sliderContainer = document.getElementById("hero-slider");
  if (!sliderContainer) return;

  sliderWrapper = document.createElement("div");
  sliderWrapper.className = "slider-wrapper";
  sliderWrapper.setAttribute("role", "region");
  sliderWrapper.setAttribute("aria-roledescription", "carousel");
  sliderWrapper.setAttribute("tabindex", "0");

  slideData.forEach((slide, index) => {
    const slideEl = createSlide(slide, index);
    sliderWrapper.appendChild(slideEl);
  });

  sliderContainer.appendChild(sliderWrapper);

  addEventListeners();
}

function createSlide(data, index) {
  const slide = document.createElement("article");
  slide.className = "slide";
  slide.setAttribute(
    "aria-label",
    `Slide ${index + 1} of ${slideData.length}: ${data.artist}`,
  );
  slide.setAttribute("aria-hidden", index !== 0 ? "true" : "false");

  const imageContainer = document.createElement("div");
  imageContainer.className = "slide-image";

  const img = document.createElement("img");
  if (index === 0) {
    img.src = data.src; // Load first image immediately
  } else {
    img.dataset.src = data.src; // Defer others
  }
  img.alt = `${data.artist} - ${data.venue}`;
  img.style.objectPosition = data.objectPosition || "center center";
  img.loading = index === 0 ? "eager" : "lazy";

  imageContainer.appendChild(img);

  const content = document.createElement("div");
  content.className = "slide-content";
  content.innerHTML = `
    <h2 class="slide-artist">${data.artist}</h2>
    <div class="slide-details">
      <p class="slide-location">${data.city} &bull; ${data.venue}</p>
      <p class="slide-date">${data.date}</p>
    </div>
  `;

  const cta = document.createElement("div");
  cta.className = "slide-cta";
  const ctaLocale = ctaConfig[currentLocale];
  cta.innerHTML = `
    <a href="${ctaLocale.href}" class="cta-button">
      <span>${ctaLocale.text}</span>
      <div class="cta-arrow-wrapper" aria-hidden="true">
        <svg width="12" height="12" viewBox="0 0 12 12" fill="none" class="cta-arrow-icon">
          <path d="M1 6H11M11 6L6 1M11 6L6 11" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </div>
    </a>
  `;

  slide.appendChild(imageContainer);
  slide.appendChild(content);
  slide.appendChild(cta);

  return slide;
}

// 4. Image Loading
// ================================================
function loadSlideImage(index) {
  const slide = sliderWrapper.children[index];
  if (!slide) return;
  const img = slide.querySelector("img");
  if (img && img.dataset.src) {
    img.src = img.dataset.src;
    delete img.dataset.src;
  }
}

// 5. Slider Controls
// ================================================
function goToSlide(index) {
  if (isTransitioning) return;

  const newIndex = (index + slideData.length) % slideData.length;
  if (newIndex === currentSlide) return;

  isTransitioning = !prefersReducedMotion;
  currentSlide = newIndex;

  sliderWrapper.style.transform = `translateX(-${currentSlide * 100}%)`;

  Array.from(sliderWrapper.children).forEach((slide, i) => {
    slide.setAttribute("aria-hidden", i !== currentSlide);
  });

  loadSlideImage(currentSlide);
  loadSlideImage((currentSlide + 1) % slideData.length); // Preload next
}

// 6. Event Handlers
// ================================================
function addEventListeners() {
  sliderWrapper.addEventListener("transitionend", () => {
    isTransitioning = false;
  });

  // Touch Swipe
  sliderWrapper.addEventListener("touchstart", handleTouchStart, {
    passive: true,
  });
  sliderWrapper.addEventListener("touchmove", handleTouchMove, {
    passive: true,
  });
  sliderWrapper.addEventListener("touchend", handleTouchEnd, { passive: true });

  // Keyboard
  sliderWrapper.addEventListener("keydown", handleKeyDown);

  // Auto-advance pause/resume
  const sliderContainer = document.getElementById("hero-slider");
  sliderContainer.addEventListener("mouseenter", pauseAutoAdvance);
  sliderContainer.addEventListener("mouseleave", resumeAutoAdvance);
  sliderContainer.addEventListener("focusin", pauseAutoAdvance);
  sliderContainer.addEventListener("focusout", resumeAutoAdvance);
  sliderContainer.addEventListener("touchstart", pauseAutoAdvance, {
    passive: true,
  });
  sliderContainer.addEventListener("touchend", resumeAutoAdvance, {
    passive: true,
  });
}

function handleTouchStart(e) {
  touchStartX = e.touches[0].clientX;
  touchStartY = e.touches[0].clientY;
}

function handleTouchMove(e) {
  // We only need start and end points, but this is here for potential future use
}

function handleTouchEnd(e) {
  const touchEndX = e.changedTouches[0].clientX;
  const touchEndY = e.changedTouches[0].clientY;
  const diffX = touchEndX - touchStartX;
  const diffY = touchEndY - touchStartY;

  if (Math.abs(diffX) > 70 && Math.abs(diffY) < 30) {
    if (diffX < 0) {
      goToSlide(currentSlide + 1);
    } else {
      goToSlide(currentSlide - 1);
    }
  }
}

function handleKeyDown(e) {
  pauseAutoAdvance();
  if (e.key === "ArrowLeft") {
    e.preventDefault();
    goToSlide(currentSlide - 1);
  } else if (e.key === "ArrowRight") {
    e.preventDefault();
    goToSlide(currentSlide + 1);
  }
  resumeAutoAdvance();
}

// 7. Auto-advance
// ================================================
function startAutoAdvance() {
  if (prefersReducedMotion) return;
  pauseAutoAdvance(); // Clear any existing timers
  autoAdvanceTimer = setInterval(() => goToSlide(currentSlide + 1), 6000);
}

function pauseAutoAdvance() {
  clearInterval(autoAdvanceTimer);
  clearTimeout(resumeTimeout);
}

function resumeAutoAdvance() {
  if (prefersReducedMotion) return;
  pauseAutoAdvance();
  resumeTimeout = setTimeout(startAutoAdvance, 5000);
}

// 8. Initialization
// ================================================
function initHeroSlider() {
  buildSlider();
  loadSlideImage(0);
  loadSlideImage(1);
  resumeAutoAdvance(); // Start the auto-advance timer after initial 5s delay
}

if (document.readyState !== "loading") {
  initHeroSlider();
} else {
  document.addEventListener("DOMContentLoaded", initHeroSlider);
}
