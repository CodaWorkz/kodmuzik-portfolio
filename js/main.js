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
