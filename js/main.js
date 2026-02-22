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

// Note: getLocalePrefix() is now imported from utils.js

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

// Slug map: Turkish path → English sub-path
const SLUG_MAP = {
  "/hakkimizda": "/about",
  "/etkinlikler": "/events",
  "/gelecek-etkinlikler": "/upcoming-events",
  "/iletisim": "/contact",
};

// Reverse map: English sub-path → Turkish path
const SLUG_MAP_REVERSE = {
  "/about": "/hakkimizda",
  "/events": "/etkinlikler",
  "/upcoming-events": "/gelecek-etkinlikler",
  "/contact": "/iletisim",
};

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
 * Updates menu links to be prefixed with the current locale if necessary.
 */
function updateMenuLinksForLocale() {
  const localePrefix = getLocalePrefix();
  const menuLinks = document.querySelectorAll(".menu-link");

  if (!menuLinks.length) return;

  menuLinks.forEach((link) => {
    const basePath = link.dataset.path || "/";
    if (localePrefix) {
      const enSlug = SLUG_MAP[basePath] || basePath;
      const href = joinPath(localePrefix, enSlug);
      link.href = href === "/" ? href : href + "/";
    } else {
      link.href = basePath === "/" ? basePath : basePath + "/";
    }
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
  const queryString = window.location.search;
  const langEN = document.getElementById("lang-en");
  const langTR = document.getElementById("lang-tr");

  if (!langEN || !langTR) return;

  [langEN, langTR].forEach((a) => {
    a.classList.remove("active");
    a.removeAttribute("aria-current");
  });

  if (currentPath.startsWith("/en")) {
    // Current page is EN, switch to TR
    const pathWithoutEN = currentPath.substring(3) || "/";
    const trSlug = SLUG_MAP_REVERSE[pathWithoutEN] || pathWithoutEN;
    const trHref = trSlug === "/" ? trSlug : trSlug + "/";
    const enHref = currentPath === "/" ? currentPath : currentPath + "/";
    langTR.href = trHref + queryString;
    langEN.href = enHref + queryString;

    langEN.classList.add("active");
    langEN.setAttribute("aria-current", "true");
    document.documentElement.lang = "en";
  } else {
    // Current page is TR, switch to EN
    const enSlug = SLUG_MAP[currentPath] || currentPath;
    const enPath = joinPath("/en", enSlug);
    const enHref = enPath === "/" ? enPath : enPath + "/";
    const trHref = currentPath === "/" ? currentPath : currentPath + "/";
    langEN.href = enHref + queryString;
    langTR.href = trHref + queryString;

    langTR.classList.add("active");
    langTR.setAttribute("aria-current", "true");
    document.documentElement.lang = "tr";
  }
}

// Additional enhancement: mobile/touch behavior for language switcher
// - Show only active language (handled by CSS)
// - Single tap (touch) should navigate to the other language
// - Keep keyboard accessibility and avoid interfering with existing logic
function enhanceLanguageSwitcher() {
  const switcher = document.querySelector(".lang-switcher");
  if (!switcher) return;

  const tr = document.getElementById("lang-tr");
  const en = document.getElementById("lang-en");
  if (!tr || !en) return;

  // Detect environments where hover is unreliable or absent
  const canHover =
    window.matchMedia && window.matchMedia("(hover: hover)").matches;
  const coarsePointer =
    window.matchMedia && window.matchMedia("(pointer: coarse)").matches;

  // For touch/coarse pointer devices, make a single tap on the visible (active) link
  // navigate to the other language immediately.
  if (!canHover || coarsePointer) {
    const handler = (ev) => {
      // Get the inactive link dynamically to avoid stale references
      const isENActive =
        en.classList.contains("active") ||
        en.getAttribute("aria-current") === "true";
      const inactiveLink = isENActive ? tr : en;

      // Only redirect if the tap/click target is within the switcher
      // and it's not already pointing to the inactive link directly.
      if (inactiveLink && inactiveLink.href) {
        // Don't prevent default - let the link work naturally
        // Just ensure we navigate to the inactive link
        if (ev.target !== inactiveLink) {
          ev.preventDefault();
          window.location.assign(inactiveLink.href);
        }
      }
    };

    // Use a single click handler for simplicity and reliability
    // Click events work on both touch and mouse devices
    switcher.addEventListener("click", handler);
  }

  // Ensure keyboard users can reveal and activate the hidden option using focus
  // (CSS uses :focus-within to reveal visually)
  // No JS needed for desktop hover behavior.
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

  // Install watermark on all pages for consistency
  installKodBackground();
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

// Run language switcher enhancement separately to avoid touching initializeApp
domReady(enhanceLanguageSwitcher);

// 6. Background watermark installer
// ================================================
function installKodBackground() {
  // Ensure a single instance of the background element
  let bg = document.getElementById("kod-bg");
  if (!bg) {
    bg = document.createElement("div");
    bg.id = "kod-bg";
    // Insert as first child so it paints beneath others (z-index handles rest)
    document.body.insertBefore(bg, document.body.firstChild);
  }
}
