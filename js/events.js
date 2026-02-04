/* ================================================
   events.js - Events Page Functionality
   KOD Müzik Website
   
   Contents:
   1. State & Configuration
   2. Data Loading
   3. Language & Localization
   4. Filter Population
   5. Filter Logic & Cascading
   6. Event Display
   7. Event Listeners
   8. Initialization
   ================================================ */

// 1. State & Configuration
// ================================================
let eventsData = [];
let currentLang = "tr"; // Will be updated based on URL

// Localization dictionary
const translations = {
  tr: {
    loading: "Yükleniyor...",
    error: "Etkinlikler yüklenirken hata oluştu",
    noResults: "Sonuç bulunamadı",
    total: "Toplam",
    all: "Tümü",
    searchArtist: "Ara...",
    artist: "Sanatçı",
    genre: "Tür",
    year: "Yıl",
    venue: "Mekan",
    clearFilters: "Filtreleri Temizle",
    genreLabel: "Tür:",
    dateLabel: "Tarih:",
    venueLabel: "Mekan:",
    seriesLabel: "Seri:",
  },
  en: {
    loading: "Loading...",
    error: "Error loading events",
    noResults: "No results found",
    total: "Total",
    all: "All",
    searchArtist: "Search...",
    artist: "Artist",
    genre: "Genre",
    year: "Year",
    venue: "Venue",
    clearFilters: "Clear Filters",
    genreLabel: "Genre:",
    dateLabel: "Date:",
    venueLabel: "Venue:",
    seriesLabel: "Series:",
  },
};

// Helper Functions
// ================================================
/**
 * Parse date in DD.MM.YYYY format to JavaScript Date object
 * @param {string} dateString - Date in DD.MM.YYYY format
 * @returns {Date} JavaScript Date object
 */
function parseEventDate(dateString) {
  const parts = dateString.split('.');
  if (parts.length === 3) {
    const day = parseInt(parts[0], 10);
    const month = parseInt(parts[1], 10) - 1; // JavaScript months are 0-indexed
    const year = parseInt(parts[2], 10);
    return new Date(year, month, day);
  }
  return new Date(dateString); // Fallback to default parsing
}

/**
 * Get artist name as string from artist object (handles both string and array)
 * @param {Object} artist - Artist object with tr and en properties
 * @param {string} lang - Language code ('tr' or 'en')
 * @returns {string} Artist name(s) as string
 */
function getArtistName(artist, lang) {
  const value = artist[lang];
  if (Array.isArray(value)) {
    return value.join(', ');
  }
  return value || '';
}

/**
 * Read filter values from URL query parameters
 * @returns {Object} Filter values object with artist, genre, year, venue
 */
function readFiltersFromURL() {
  const params = new URLSearchParams(window.location.search);
  return {
    artist: params.get('artist') || '',
    genre: params.get('genre') || '',
    year: params.get('year') || '',
    venue: params.get('venue') || ''
  };
}

/**
 * Write current filter values to URL query parameters
 * Updates the URL without reloading the page
 */
function writeFiltersToURL() {
  const artistFilter = document.getElementById('artist-filter').value;
  const genreFilter = document.getElementById('genre-filter').dataset.value;
  const yearFilter = document.getElementById('year-filter').dataset.value;
  const venueFilter = document.getElementById('venue-filter').dataset.value;

  const params = new URLSearchParams();

  // Only add non-empty filters to URL
  if (artistFilter) params.set('artist', artistFilter);
  if (genreFilter) params.set('genre', genreFilter);
  if (yearFilter) params.set('year', yearFilter);
  if (venueFilter) params.set('venue', venueFilter);

  // Update URL without reloading the page
  const newURL = params.toString()
    ? `${window.location.pathname}?${params.toString()}`
    : window.location.pathname;

  window.history.replaceState({}, '', newURL);
}

/**
 * Apply filter values from URL to UI controls
 * Called after filters are populated to restore state from URL
 */
function applyURLFiltersToUI() {
  const urlFilters = readFiltersFromURL();

  // Apply artist filter
  const artistInput = document.getElementById('artist-filter');
  if (artistInput && urlFilters.artist) {
    artistInput.value = urlFilters.artist;
  }

  // Apply custom select filters
  applyCustomSelectValue('genre-filter', urlFilters.genre);
  applyCustomSelectValue('year-filter', urlFilters.year);
  applyCustomSelectValue('venue-filter', urlFilters.venue);
}

/**
 * Helper function to set custom select value and label
 * @param {string} selectId - The ID of the custom select element
 * @param {string} value - The value to set
 */
function applyCustomSelectValue(selectId, value) {
  if (!value) return;

  const customSelect = document.getElementById(selectId);
  if (!customSelect) return;

  // Find the option with the matching value
  const option = customSelect.querySelector(
    `.custom-select-option[data-value="${value}"]`
  );

  if (option) {
    // Set the value in dataset
    customSelect.dataset.value = value;

    // Update the label text
    const label = customSelect.querySelector('.custom-select-label');
    if (label) {
      label.textContent = option.textContent;
    }
  }
}

// 2. Data Loading
// ================================================
async function loadEvents() {
  try {
    const response = await fetch("/kod_muzik_events.json");
    if (!response.ok) throw new Error("Failed to load events");

    const data = await response.json();
    eventsData = data.events;

    populateFilters(data.meta);
    applyURLFiltersToUI(); // Restore filters from URL
    applyFilters();
  } catch (error) {
    console.error("Error loading events:", error);
    document.getElementById(
      "events-grid"
    ).innerHTML = `<div class="error">${translations[currentLang].error}</div>`;
  }
}

// 3. Language & Localization
// ================================================
function detectLanguage() {
  // Use the same logic as main.js
  currentLang = window.location.pathname.startsWith("/en") ? "en" : "tr";
  updateEventPageLanguage();
}

function updateEventPageLanguage() {
  // Update filter labels
  document.querySelectorAll("[data-label-key]").forEach((el) => {
    const key = el.dataset.labelKey;
    el.textContent = translations[currentLang][key];
  });

  // Update placeholders
  const artistFilter = document.getElementById("artist-filter");
  if (artistFilter) {
    artistFilter.placeholder = translations[currentLang].searchArtist;
  }

  // Update button text
  const clearBtn = document.querySelector(".clear-filters");
  if (clearBtn) {
    clearBtn.textContent = translations[currentLang].clearFilters;
  }

  // Update filter options
  updateFilterOptionsLanguage();
}

function updateFilterOptionsLanguage() {
  // Update custom select options
  document.querySelectorAll(".custom-select").forEach((select) => {
    const optionsContainer = select.querySelector(".custom-select-options");
    if (optionsContainer) {
      optionsContainer
        .querySelectorAll(".custom-select-option")
        .forEach((option) => {
          option.textContent = option.dataset[currentLang];
        });
    }
  });

  // Update native venue select
  const venueSelect = document.getElementById("venue-filter");
  if (venueSelect && window.allVenues) {
    venueSelect.querySelectorAll('option[value!=""]').forEach((option) => {
      const venueData = window.allVenues.find((v) => v.en === option.value);
      if (venueData) {
        option.textContent = venueData[currentLang];
      }
    });
  }
}

// 4. Filter Population
// ================================================
function populateFilters(meta) {
  // Store all options data for cascading
  window.allGenres = meta.genres;
  window.allVenues = meta.venues;

  // Populate custom genre filter
  const genreOptions = document.querySelector(
    "#genre-filter .custom-select-options"
  );
  genreOptions.innerHTML = createCustomOption(
    "",
    translations[currentLang].all,
    translations[currentLang].all
  );
  meta.genres.forEach((g) => {
    genreOptions.innerHTML += createCustomOption(g.en, g.tr, g.en);
  });

  // Populate custom year filter
  const years = [
    ...new Set(eventsData.map((event) => parseEventDate(event.date).getFullYear())),
  ].sort((a, b) => b - a);
  window.allYears = years;
  const yearOptions = document.querySelector(
    "#year-filter .custom-select-options"
  );
  yearOptions.innerHTML = createCustomOption(
    "",
    translations[currentLang].all,
    translations[currentLang].all
  );
  years.forEach((year) => {
    yearOptions.innerHTML += createCustomOption(year, year, year);
  });

  // Populate custom venue filter
  const venueOptions = document.querySelector(
    "#venue-filter .custom-select-options"
  );
  if (venueOptions) {
    venueOptions.innerHTML = createCustomOption(
      "",
      translations[currentLang].all,
      translations[currentLang].all
    );
    meta.venues.forEach((v) => {
      venueOptions.innerHTML += createCustomOption(v.en, v.tr, v.en);
    });
  }

  // Initial cascade update
  updateCascadingFilters();
}

function createCustomOption(value, textTr, textEn) {
  const text = currentLang === "tr" ? textTr : textEn;
  return `<div class="custom-select-option" role="option" tabindex="-1" data-value="${value}" data-tr="${textTr}" data-en="${textEn}">${text}</div>`;
}

// 5. Filter Logic & Cascading
// ================================================
function getFilteredEvents() {
  const artistFilter = document
    .getElementById("artist-filter")
    .value.toLowerCase();
  const genreFilter = document.getElementById("genre-filter").dataset.value;
  const yearFilter = document.getElementById("year-filter").dataset.value;
  const venueFilter = document.getElementById("venue-filter").dataset.value;

  return eventsData.filter((event) => {
    // Artist filter - search in both languages
    if (
      artistFilter &&
      !getArtistName(event.artist, 'tr').toLowerCase().includes(artistFilter) &&
      !getArtistName(event.artist, 'en').toLowerCase().includes(artistFilter)
    ) {
      return false;
    }

    // Genre filter
    if (genreFilter && event.genre.en !== genreFilter) {
      return false;
    }

    // Year filter
    if (
      yearFilter &&
      parseEventDate(event.date).getFullYear() !== parseInt(yearFilter)
    ) {
      return false;
    }

    // Venue filter
    if (venueFilter && event.venue.en !== venueFilter) {
      return false;
    }

    return true;
  });
}

function applyFilters() {
  const filteredEvents = getFilteredEvents();
  displayEvents(filteredEvents);
  updateResultsCount(filteredEvents.length);
  updateCascadingFilters();
  writeFiltersToURL();
}

function updateCascadingFilters() {
  const artistFilter = document
    .getElementById("artist-filter")
    .value.toLowerCase();
  const genreFilter = document.getElementById("genre-filter").dataset.value;
  const yearFilter = document.getElementById("year-filter").dataset.value;
  const venueFilter = document.getElementById("venue-filter").dataset.value;

  // Get available options based on current filters
  const availableGenres = new Set();
  const availableYears = new Set();
  const availableVenues = new Set();

  eventsData.forEach((event) => {
    let matchesFilters = true;

    // Check artist filter
    if (
      artistFilter &&
      !getArtistName(event.artist, 'tr').toLowerCase().includes(artistFilter) &&
      !getArtistName(event.artist, 'en').toLowerCase().includes(artistFilter)
    ) {
      matchesFilters = false;
    }

    // Check year filter
    if (
      yearFilter &&
      parseEventDate(event.date).getFullYear() !== parseInt(yearFilter)
    ) {
      matchesFilters = false;
    }

    // Check genre filter
    if (genreFilter && event.genre.en !== genreFilter) {
      matchesFilters = false;
    }

    // Check venue filter
    if (venueFilter && event.venue.en !== venueFilter) {
      matchesFilters = false;
    }

    if (matchesFilters) {
      // Add available options for other filters
      if (!genreFilter || event.genre.en === genreFilter) {
        availableGenres.add(event.genre.en);
      }
      if (
        !yearFilter ||
        parseEventDate(event.date).getFullYear() === parseInt(yearFilter)
      ) {
        availableYears.add(parseEventDate(event.date).getFullYear());
      }
      if (!venueFilter || event.venue.en === venueFilter) {
        availableVenues.add(event.venue.en);
      }
    }
  });

  // Update filter options visibility
  updateFilterOptions("genre-filter", availableGenres);
  updateFilterOptions("year-filter", availableYears, true);
  updateFilterOptions("venue-filter", availableVenues);
}

function updateFilterOptions(filterId, availableValues, isNumeric = false) {
  const customSelect = document.getElementById(filterId);
  if (customSelect.classList.contains("custom-select")) {
    const options = customSelect.querySelectorAll(".custom-select-option");
    options.forEach((option) => {
      if (option.dataset.value === "") return;
      const value = isNumeric
        ? parseInt(option.dataset.value)
        : option.dataset.value;
      const isAvailable = availableValues.has(value);
      option.classList.toggle("is-disabled", !isAvailable);
      option.setAttribute("aria-disabled", !isAvailable);
    });
  }
}

// 6. Event Display
// ================================================
function displayEvents(events) {
  const grid = document.getElementById("events-grid");

  if (events.length === 0) {
    grid.innerHTML = `<div class="no-results">${translations[currentLang].noResults}</div>`;
    return;
  }

  // Sort events by date in descending order (newest first)
  const sortedEvents = [...events].sort((a, b) => {
    const dateA = parseEventDate(a.date);
    const dateB = parseEventDate(b.date);
    return dateB - dateA; // Descending order (newest first)
  });

  grid.innerHTML = sortedEvents
    .map((event, index) => {
      const date = parseEventDate(event.date);
      const formattedDate = date.toLocaleDateString(
        currentLang === "tr" ? "tr-TR" : "en-US",
        {
          year: "numeric",
          month: "short",
          day: "numeric",
        }
      );

      const seriesValue = (event.series && event.series[currentLang]
        ? String(event.series[currentLang]).trim()
        : "");

      return `
      <div class="event-card">
        <h3 class="event-artist">${seriesValue ? `<span class=\"event-series-inline\">${seriesValue}</span> ` : ""}<span class=\"event-artist-name\">${getArtistName(event.artist, currentLang)}</span></h3>
        <div class="event-details">
          <div class="event-detail">
            <span class="event-detail-label">${translations[currentLang].dateLabel}</span>
            <span>${formattedDate}</span>
          </div>
          <div class="event-detail">
            <span class="event-detail-label">${translations[currentLang].venueLabel}</span>
            <span>${event.venue[currentLang]}</span>
          </div>
        </div>
      </div>
    `;
    })
    .join("");

  // Fit series text to one line and apply long-title sizing
  if (typeof requestAnimationFrame === "function") {
    requestAnimationFrame(() => {
      applyLongTitleSizing();
      fitSeriesOneLineAll();
    });
  } else {
    setTimeout(() => {
      applyLongTitleSizing();
      fitSeriesOneLineAll();
    }, 0);
  }
}

function updateResultsCount(count) {
  document.getElementById("results-number").textContent = count;
}

function setupCustomSelects() {
  document.querySelectorAll(".custom-select").forEach((customSelect) => {
    const trigger = customSelect.querySelector(".custom-select-trigger");
    const optionsContainer = customSelect.querySelector(
      ".custom-select-options"
    );
    const label = trigger.querySelector(".custom-select-label");

    trigger.addEventListener("click", (e) => {
      e.stopPropagation();
      // Close other open selects
      document.querySelectorAll(".custom-select.is-open").forEach((open) => {
        if (open !== customSelect) {
          open.classList.remove("is-open");
          open
            .querySelector(".custom-select-trigger")
            .setAttribute("aria-expanded", "false");
          open.querySelector(".custom-select-options").hidden = true;
        }
      });

      const isOpen = customSelect.classList.toggle("is-open");
      trigger.setAttribute("aria-expanded", isOpen);
      optionsContainer.hidden = !isOpen;
    });

    optionsContainer.addEventListener("click", (e) => {
      const option = e.target.closest(".custom-select-option");
      if (option && !option.classList.contains("is-disabled")) {
        const value = option.dataset.value;
        const text = option.textContent;

        label.textContent = text;
        customSelect.dataset.value = value;

        // Close dropdown
        customSelect.classList.remove("is-open");
        trigger.setAttribute("aria-expanded", "false");
        optionsContainer.hidden = true;

        // Apply filters
        applyFilters();
      }
    });

    customSelect.addEventListener("keydown", (e) => {
      if (e.key === "Escape") {
        customSelect.classList.remove("is-open");
        trigger.setAttribute("aria-expanded", "false");
        optionsContainer.hidden = true;
      }
    });
  });

  // Close when clicking outside
  document.addEventListener("click", () => {
    document.querySelectorAll(".custom-select.is-open").forEach((open) => {
      open.classList.remove("is-open");
      open
        .querySelector(".custom-select-trigger")
        .setAttribute("aria-expanded", "false");
      open.querySelector(".custom-select-options").hidden = true;
    });
  });
}

// 7. Event Listeners
// ================================================
function initializeEventListeners() {
  // Debounce function for text input
  let debounceTimer;
  function debounce(func, delay) {
    return function () {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(func, delay);
    };
  }

  // Artist filter with debounce
  document
    .getElementById("artist-filter")
    .addEventListener("input", debounce(applyFilters, 300));

  // Venue filter (still a native select)
  // Switched to custom select; handled in setupCustomSelects()

  // Clear filters
  document.querySelector(".clear-filters").addEventListener("click", () => {
    document.getElementById("artist-filter").value = "";
    document.getElementById("venue-filter").value = "";

    // Reset custom selects
    document.querySelectorAll(".custom-select").forEach((select) => {
      select.dataset.value = "";
      const allText = select.querySelector(
        '.custom-select-option[data-value=""]'
      ).dataset[currentLang];
      select.querySelector(".custom-select-label").textContent = allText;
    });

    applyFilters();
  });
}

// 8. Initialization
// ================================================
function initializeEventsPage() {
  detectLanguage();
  loadEvents();
  initializeEventListeners();
  setupCustomSelects();

  // Re-fit series line on resize/orientation changes (debounced)
  let resizeSeriesTimer;
  window.addEventListener("resize", () => {
    clearTimeout(resizeSeriesTimer);
    resizeSeriesTimer = setTimeout(fitSeriesOneLineAll, 150);
  });
}

// Start when DOM is ready
if (document.readyState !== "loading") {
  initializeEventsPage();
} else {
  document.addEventListener("DOMContentLoaded", initializeEventsPage);
}

// Re-detect language on navigation
window.addEventListener("popstate", () => {
  detectLanguage();
  applyFilters();
});

// ================================================
// Series one-line fitting (per-card, subtle scaling)
// ================================================
function fitSeriesOneLineAll() {
  const seriesNodes = document.querySelectorAll(".event-series-inline");
  if (!seriesNodes.length) return;
  seriesNodes.forEach((node) => fitSeriesOneLine(node));
}

function fitSeriesOneLine(node) {
  // Force series on its own line and attempt single-line fit
  node.style.whiteSpace = "nowrap";

  // Reset any previous inline sizing to the CSS-defined base
  node.style.fontSize = "";

  const baseSize = parseFloat(getComputedStyle(node).fontSize);
  if (!baseSize) return;

  let scale = 1.0;
  const minPx = 10; // minimum readable size
  const step = 0.96; // ~4% per iteration for smooth changes
  const maxIterations = 16;
  let iterations = 0;

  // Apply base first
  node.style.fontSize = baseSize + "px";

  // Reduce font-size until content fits on one line
  while (node.scrollWidth > node.clientWidth + 0.5 && iterations < maxIterations) {
    scale *= step;
    const next = Math.max(minPx, baseSize * scale);
    node.style.fontSize = next + "px";
    iterations++;
    if (next <= minPx) break;
  }
}

// ================================================
// Long title sizing (shrink by 20% if >30 chars)
// ================================================
function applyLongTitleSizing() {
  const titles = document.querySelectorAll('.event-artist-name');
  titles.forEach((node) => {
    const text = (node.textContent || '').trim();
    if (text.length > 30) {
      node.classList.add('is-long');
    } else {
      node.classList.remove('is-long');
    }
  });
}
