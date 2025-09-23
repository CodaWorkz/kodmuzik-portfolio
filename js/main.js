/* ================================================
   main.js - Global Scripts
   KOD Müzik Website
   
   Contents:
   1. Helper Functions
   2. Path Management & Routing
   3. Menu Active State
   4. Language Toggle
   5. Initialization
   ================================================ */

// 1. Helper Functions
// ================================================

/**
 * Joins path segments without creating double slashes.
 * @param {...string} segments - The path segments to join.
 * @returns {string} The combined path.
 */
function joinPath(...segments) {
  return segments.join("/").replace(/\/+/g, "/");
}

/**
 * Gets the current URL pathname, normalized without a trailing slash.
 * @returns {string} The normalized pathname.
 */
function getCurrentPath() {
  const { pathname } = window.location;
  // Return '/' for the root, otherwise remove trailing slash
  return pathname.length > 1 ? pathname.replace(/\/$/, "") : "/";
}

/**
 * Checks if the current path matches a link's base path.
 * Handles exact matches and parent path matches (e.g., /events matches /events/some-event).
 * @param {string} currentPath - The current page's path.
 * @param {string} linkPath - The base path of the link to check.
 * @returns {boolean} True if it's a match.
 */
function isMatch(currentPath, linkPath) {
  if (linkPath === "/") {
    return currentPath === "/";
  }
  return currentPath === linkPath || currentPath.startsWith(linkPath + "/");
}

// 2. Path Management & Routing (TR-first logic)
// ================================================

/**
 * Gets the locale prefix from the current path.
 * English is at /en, Turkish (default) is at the root.
 * @returns {string} '/en' for English pages, '' for Turkish pages.
 */
function getLocalePrefix() {
  return getCurrentPath().startsWith("/en") ? "/en" : "";
}

/**
 * Updates menu links to be prefixed with the current locale if necessary.
 */
function updateMenuLinksForLocale() {
  const localePrefix = getLocalePrefix();
  const menuLinks = document.querySelectorAll(".menu-link");

  if (!menuLinks.length) return; // Defensive check

  menuLinks.forEach((link) => {
    const basePath = link.dataset.path || "/";
    // Only prefix with /en if on an English page
    link.href = localePrefix ? joinPath(localePrefix, basePath) : basePath;
  });
}

// 3. Menu Active State
// ================================================

/**
 * Sets the 'active' class on the correct menu link based on the current path.
 */
function setActiveLink() {
  const currentPath = getCurrentPath();
  const localePrefix = getLocalePrefix();

  // Get path without locale for comparison (e.g., /en/hizmetler -> /hizmetler)
  const pathWithoutLocale = localePrefix
    ? currentPath.substring(localePrefix.length) || "/"
    : currentPath;

  const menuLinks = document.querySelectorAll(".menu-link");
  if (!menuLinks.length) return; // Defensive check

  menuLinks.forEach((link) => {
    link.classList.remove("active");
    link.removeAttribute("aria-current");

    const linkBasePath = link.dataset.path || "/";

    if (isMatch(pathWithoutLocale, linkBasePath)) {
      link.classList.add("active");
      link.setAttribute("aria-current", "page");
    }
  });
}

// 4. Language Toggle
// ================================================

/**
 * Updates language toggle links to switch to the equivalent page in the other language.
 */
function updateLanguageLinks() {
  const currentPath = getCurrentPath();
  const langEN = document.getElementById("lang-en");
  const langTR = document.getElementById("lang-tr");

  if (!langEN || !langTR) return; // Defensive check

  // Reset states
  [langEN, langTR].forEach((a) => {
    a.classList.remove("active");
    a.removeAttribute("aria-current");
  });

  if (currentPath.startsWith("/en")) {
    // Current page is EN, switch to TR
    const pathWithoutEN = currentPath.substring(3) || "/";
    langTR.href = pathWithoutEN;
    langEN.href = currentPath; // Link to self

    langEN.classList.add("active");
    langEN.setAttribute("aria-current", "true");
    document.documentElement.lang = "en";
  } else {
    // Current page is TR, switch to EN
    const enPath = joinPath("/en", currentPath);
    langEN.href = enPath;
    langTR.href = currentPath; // Link to self

    langTR.classList.add("active");
    langTR.setAttribute("aria-current", "true");
    document.documentElement.lang = "tr";
  }
}

// 5. Initialization
// ================================================

/**
 * Initializes all dynamic menu and language features.
 */
function initializeApp() {
  updateMenuLinksForLocale();
  setActiveLink();
  updateLanguageLinks();

  // Only install watermark if NOT on about pages
  const currentPath = getCurrentPath();
  if (
    !currentPath.includes("/hakkimizda") &&
    !currentPath.includes("/en/hakkimizda")
  ) {
    installKodBackground();
  }
}

/**
 * DOM Ready handler to ensure script runs after the DOM is loaded.
 * @param {function} fn - The function to execute when the DOM is ready.
 */
function domReady(fn) {
  if (document.readyState !== "loading") {
    fn();
  } else {
    document.addEventListener("DOMContentLoaded", fn);
  }
}

// Run initialization
domReady(initializeApp);

// 6. Background watermark installer
// ================================================
function installKodBackground() {
  const doc = document;
  // Ensure a single instance
  let bg = doc.getElementById("kod-bg");
  if (!bg) {
    bg = doc.createElement("div");
    bg.id = "kod-bg";
    // Insert as first child so it paints beneath others (z-index handles rest)
    doc.body.insertBefore(bg, doc.body.firstChild);
  }

  const pr = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  function tune() {
    const w = window.innerWidth;
    const h = window.innerHeight;
    const ar = w / h;
    // Base values — keep in sync with CSS defaults
    let scale = 0.6;
    let rotate = -14; // degrees
    let shiftX = 0; // percentage offset

    // Aspect-ratio driven framing to keep <O> with chevrons peeking
    if (ar >= 2.0) {
      // ultra-wide → a touch more scale to avoid gaps, slight left nudge
      scale = 0.65;
      shiftX = -1; // show a bit more of the right chevron
    } else if (ar <= 0.7) {
      // tall phones → slightly more scale and small right nudge
      scale = 0.7;
      shiftX = 1;
    }

    // Fine tuning for small widths
    if (w <= 600) scale = Math.max(scale, 0.75);

    // Apply via CSS variables so CSS does the heavy lifting
    const rs = doc.documentElement.style;
    rs.setProperty("--kod-bg-scale", String(scale));
    rs.setProperty("--kod-bg-rotate", rotate + "deg");
    rs.setProperty("--kod-watermark-shift-x", shiftX + "%");
  }

  tune();
  window.addEventListener("resize", tune, { passive: true });
  window.addEventListener("orientationchange", tune, { passive: true });
  if (!pr) {
    // No animation now, but hook is here if a micro parallax is desired later
  }
}
