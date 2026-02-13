/* ================================================
   utils.js - Shared Utility Functions
   KOD Müzik Website
   
   Contents:
   1. Language Detection
   ================================================ */

// 1. Language Detection
// ================================================

/**
 * Detects the current language from the URL path.
 * English pages are at /en/*, Turkish (default) is at the root.
 * @returns {string} 'en' for English pages, 'tr' for Turkish pages.
 */
function detectCurrentLanguage() {
  return window.location.pathname.startsWith("/en") ? "en" : "tr";
}

/**
 * Gets the locale prefix from the current path.
 * English is at /en, Turkish (default) is at the root.
 * @returns {string} '/en' for English pages, '' for Turkish pages.
 */
function getLocalePrefix() {
  return window.location.pathname.startsWith("/en") ? "/en" : "";
}
