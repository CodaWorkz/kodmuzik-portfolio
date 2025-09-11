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
    searchArtist: "Sanatçı ara...",
    artist: "Sanatçı",
    genre: "Tür",
    year: "Yıl",
    venue: "Mekan",
    clearFilters: "Filtreleri Temizle",
    genreLabel: "Tür:",
    dateLabel: "Tarih:",
    venueLabel: "Mekan:",
  },
  en: {
    loading: "Loading...",
    error: "Error loading events",
    noResults: "No results found",
    total: "Total",
    all: "All",
    searchArtist: "Search artist...",
    artist: "Artist",
    genre: "Genre",
    year: "Year",
    venue: "Venue",
    clearFilters: "Clear Filters",
    genreLabel: "Genre:",
    dateLabel: "Date:",
    venueLabel: "Venue:",
  },
};

// 2. Data Loading
// ================================================
async function loadEvents() {
  try {
    const response = await fetch("/kod_events.json");
    if (!response.ok) throw new Error("Failed to load events");

    const data = await response.json();
    eventsData = data.events;

    populateFilters(data.meta);
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
  // Update "All" option in all selects
  document.querySelectorAll(".filter-select").forEach((select) => {
    const allOption = select.querySelector('option[value=""]');
    if (allOption) {
      allOption.textContent = translations[currentLang].all;
    }
  });

  // Update genre options
  const genreSelect = document.getElementById("genre-filter");
  if (genreSelect && window.allGenres) {
    genreSelect.querySelectorAll('option[value!=""]').forEach((option) => {
      const genreData = window.allGenres.find((g) => g.en === option.value);
      if (genreData) {
        option.textContent = genreData[currentLang];
      }
    });
  }

  // Update venue options
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

  // Populate genre filter
  const genreFilter = document.getElementById("genre-filter");
  genreFilter.innerHTML = `<option value="">${translations[currentLang].all}</option>`;
  meta.genres.forEach((genre) => {
    const option = document.createElement("option");
    option.value = genre.en;
    option.textContent = genre[currentLang];
    genreFilter.appendChild(option);
  });

  // Extract and populate years
  const years = [
    ...new Set(eventsData.map((event) => new Date(event.date).getFullYear())),
  ].sort((a, b) => b - a);

  window.allYears = years;

  const yearFilter = document.getElementById("year-filter");
  yearFilter.innerHTML = `<option value="">${translations[currentLang].all}</option>`;
  years.forEach((year) => {
    const option = document.createElement("option");
    option.value = year;
    option.textContent = year;
    yearFilter.appendChild(option);
  });

  // Populate venue filter
  const venueFilter = document.getElementById("venue-filter");
  venueFilter.innerHTML = `<option value="">${translations[currentLang].all}</option>`;
  meta.venues.forEach((venue) => {
    const option = document.createElement("option");
    option.value = venue.en;
    option.textContent = venue[currentLang];
    venueFilter.appendChild(option);
  });

  // Initial cascade update
  updateCascadingFilters();
}

// 5. Filter Logic & Cascading
// ================================================
function getFilteredEvents() {
  const artistFilter = document
    .getElementById("artist-filter")
    .value.toLowerCase();
  const genreFilter = document.getElementById("genre-filter").value;
  const yearFilter = document.getElementById("year-filter").value;
  const venueFilter = document.getElementById("venue-filter").value;

  return eventsData.filter((event) => {
    // Artist filter - search in both languages
    if (
      artistFilter &&
      !event.artist.tr.toLowerCase().includes(artistFilter) &&
      !event.artist.en.toLowerCase().includes(artistFilter)
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
      new Date(event.date).getFullYear() !== parseInt(yearFilter)
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
}

function updateCascadingFilters() {
  const artistFilter = document
    .getElementById("artist-filter")
    .value.toLowerCase();
  const genreFilter = document.getElementById("genre-filter").value;
  const yearFilter = document.getElementById("year-filter").value;
  const venueFilter = document.getElementById("venue-filter").value;

  // Get available options based on current filters
  const availableGenres = new Set();
  const availableYears = new Set();
  const availableVenues = new Set();

  eventsData.forEach((event) => {
    let matchesFilters = true;

    // Check artist filter
    if (
      artistFilter &&
      !event.artist.tr.toLowerCase().includes(artistFilter) &&
      !event.artist.en.toLowerCase().includes(artistFilter)
    ) {
      matchesFilters = false;
    }

    // Check year filter
    if (
      yearFilter &&
      new Date(event.date).getFullYear() !== parseInt(yearFilter)
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
        new Date(event.date).getFullYear() === parseInt(yearFilter)
      ) {
        availableYears.add(new Date(event.date).getFullYear());
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
  const select = document.getElementById(filterId);
  Array.from(select.options).forEach((option) => {
    if (option.value === "") return; // Keep "All" option

    const value = isNumeric ? parseInt(option.value) : option.value;
    const isAvailable = availableValues.has(value);

    option.style.display = isAvailable ? "" : "none";
    option.disabled = !isAvailable;
  });
}

// 6. Event Display
// ================================================
function displayEvents(events) {
  const grid = document.getElementById("events-grid");

  if (events.length === 0) {
    grid.innerHTML = `<div class="no-results">${translations[currentLang].noResults}</div>`;
    return;
  }

  grid.innerHTML = events
    .map((event, index) => {
      const date = new Date(event.date);
      const formattedDate = date.toLocaleDateString(
        currentLang === "tr" ? "tr-TR" : "en-US",
        {
          year: "numeric",
          month: "long",
          day: "numeric",
        }
      );

      return `
      <div class="event-card">
        <h3 class="event-artist">${event.artist[currentLang]}</h3>
        <div class="event-details">
          <div class="event-detail">
            <span class="event-detail-label">${translations[currentLang].genreLabel}</span>
            <span>${event.genre[currentLang]}</span>
          </div>
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
}

function updateResultsCount(count) {
  document.getElementById("results-number").textContent = count;
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

  // Other filters
  document
    .getElementById("genre-filter")
    .addEventListener("change", applyFilters);
  document
    .getElementById("year-filter")
    .addEventListener("change", applyFilters);
  document
    .getElementById("venue-filter")
    .addEventListener("change", applyFilters);

  // Clear filters
  document.querySelector(".clear-filters").addEventListener("click", () => {
    document.getElementById("artist-filter").value = "";
    document.getElementById("genre-filter").value = "";
    document.getElementById("year-filter").value = "";
    document.getElementById("venue-filter").value = "";
    applyFilters();
  });
}

// 8. Initialization
// ================================================
function initializeEventsPage() {
  detectLanguage();
  loadEvents();
  initializeEventListeners();
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
