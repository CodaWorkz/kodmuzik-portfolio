<?php
$ssr_events = [];
$config = $_SERVER['DOCUMENT_ROOT'] . '/api/config.php';
if (file_exists($config)) {
    require_once $config;
    try {
        $pdo = getDB();
        $stmt = $pdo->query("
            SELECT artists_en, genre_en, event_date, venue_en
            FROM events
            WHERE event_type = 'past' AND status = 'published'
            ORDER BY event_date DESC
            LIMIT 20
        ");
        $ssr_events = $stmt->fetchAll();
    } catch (Exception $e) {
        // Silently fail — JS will load events
    }
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="icon" type="image/x-icon" href="/favicon.ico" />
    <title>Events - Kodmüzik</title>
    <meta
      name="description"
      content="Kodmüzik events - Selective music events organized since 1999"
    />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Anton&family=Inter:wght@300;500&family=TASA+Orbiter:wght@400;500;600;700;800&display=swap"
      rel="stylesheet"
    />

    <!-- Global Styles -->
    <link rel="stylesheet" href="/css/main.css" />
    <!-- Events Page Styles -->
    <link rel="stylesheet" href="/css/events.css" />
    <!-- SSR → JS seamless handoff: hide loading spinner when SSR provides content -->
    <style>
      .has-ssr .loading { display: none; }
    </style>
    <!-- Canonical URL -->
    <link rel="canonical" href="https://www.kodmuzik.com/en/events/" />
    <!-- Hreflang -->
    <link
      rel="alternate"
      hreflang="x-default"
      href="https://www.kodmuzik.com/etkinlikler/"
    />
    <link
      rel="alternate"
      hreflang="tr"
      href="https://www.kodmuzik.com/etkinlikler/"
    />
    <link
      rel="alternate"
      hreflang="en"
      href="https://www.kodmuzik.com/en/events/"
    />
  </head>
  <body>
    <!-- Skip to content link for screen readers -->
    <a href="#main-content" class="skip-to-content">Skip to content</a>

    <!-- Side Menu -->
    <nav class="side-menu" aria-label="Main menu">
      <!-- Logo/Home -->
      <a href="/en/" class="menu-logo" aria-label="Kodmüzik home">
        <img
          src="/KodLogo.svg"
          alt="Kodmüzik logo"
          class="menu-logo-img"
          loading="eager"
          decoding="async"
        />
        <span class="menu-logo-caption" aria-hidden="true"
          ><span class="menu-logo-letter">K</span
          ><span class="menu-logo-letter">O</span
          ><span class="menu-logo-letter">D</span
          ><span class="menu-logo-letter">M</span
          ><span class="menu-logo-letter">Ü</span
          ><span class="menu-logo-letter">Z</span
          ><span class="menu-logo-letter">İ</span
          ><span class="menu-logo-letter">K</span></span
        >
      </a>

      <!-- Navigation -->
      <div class="menu-nav">
        <ul class="menu-items">
          <li class="menu-item">
            <a href="/en/about/" class="menu-link" data-path="/hakkimizda"
              >ABOUT</a
            >
          </li>
          <li class="menu-item">
            <a href="/en/events/" class="menu-link" data-path="/etkinlikler"
              >PAST</a
            >
          </li>
          <li class="menu-item">
            <a
              href="/en/upcoming-events/"
              class="menu-link"
              data-path="/gelecek-etkinlikler"
              >FUTURE</a
            >
          </li>
          <li class="menu-item">
            <a href="/en/contact/" class="menu-link" data-path="/iletisim"
              >CONTACT</a
            >
          </li>
        </ul>
      </div>

      <!-- Language Toggle -->
      <div class="lang-toggle-menu" aria-label="Language selection">
        <div class="lang-switcher" role="group" aria-label="Language selection">
          <a
            href="/etkinlikler/"
            class="lang-link"
            id="lang-tr"
            hreflang="tr"
            lang="tr"
            >TR</a
          >
          <a
            href="/en/events/"
            class="lang-link"
            id="lang-en"
            hreflang="en"
            lang="en"
            >EN</a
          >
        </div>
      </div>
    </nav>
    <!-- Main Content -->
    <main id="main-content" class="main-content">
      <!-- Events Header -->
      <section class="events-header">
        <h1 class="events-title">Past</h1>
        <p class="events-subtitle">Since 1999...</p>
      </section>

      <!-- Filters Section -->
      <section class="filters-section">
        <div class="filters-container">
          <div class="filter-group">
            <label
              for="genre-filter-button"
              class="filter-label"
              data-label-key="genre"
              >Genre</label
            >
            <div id="genre-filter" class="custom-select" data-value="">
              <button
                id="genre-filter-button"
                type="button"
                class="custom-select-trigger"
                aria-haspopup="listbox"
                aria-expanded="false"
              >
                <span class="custom-select-label">All</span>
                <svg
                  class="custom-select-arrow"
                  viewBox="0 0 10 6"
                  fill="none"
                  xmlns="http://www.w3.org/2000/svg"
                >
                  <path
                    d="M1 1L5 5L9 1"
                    stroke="currentColor"
                    stroke-width="1.5"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  />
                </svg>
              </button>
              <div class="custom-select-options" role="listbox" hidden></div>
            </div>
          </div>
          <div class="filter-group">
            <label
              for="year-filter-button"
              class="filter-label"
              data-label-key="year"
              >Year</label
            >
            <div
              id="year-filter"
              class="year-filter custom-select"
              data-value=""
            >
              <button
                id="year-filter-button"
                type="button"
                class="custom-select-trigger"
                aria-haspopup="listbox"
                aria-expanded="false"
              >
                <span class="custom-select-label">All</span>
                <svg
                  class="custom-select-arrow"
                  viewBox="0 0 10 6"
                  fill="none"
                  xmlns="http://www.w3.org/2000/svg"
                >
                  <path
                    d="M1 1L5 5L9 1"
                    stroke="currentColor"
                    stroke-width="1.5"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  />
                </svg>
              </button>
              <div class="custom-select-options" role="listbox" hidden></div>
            </div>
          </div>
          <div class="filter-group">
            <label
              for="venue-filter-button"
              class="filter-label"
              data-label-key="venue"
              >Venue</label
            >
            <div id="venue-filter" class="custom-select" data-value="">
              <button
                id="venue-filter-button"
                type="button"
                class="custom-select-trigger"
                aria-haspopup="listbox"
                aria-expanded="false"
              >
                <span class="custom-select-label">All</span>
                <svg
                  class="custom-select-arrow"
                  viewBox="0 0 10 6"
                  fill="none"
                  xmlns="http://www.w3.org/2000/svg"
                >
                  <path
                    d="M1 1L5 5L9 1"
                    stroke="currentColor"
                    stroke-width="1.5"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  />
                </svg>
              </button>
              <div class="custom-select-options" role="listbox" hidden></div>
            </div>
          </div>
          <div class="filter-group">
            <label
              for="artist-filter"
              class="filter-label"
              data-label-key="artist"
              >Artist</label
            >
            <input
              type="text"
              id="artist-filter"
              class="artist-filter filter-input"
              placeholder="Search..."
              aria-label="Search"
            />
          </div>
          <div class="filter-actions">
            <button class="clear-filters" type="button">Clear Filters</button>
            <span class="results-count">
              <span data-label-key="total">Total</span>:
              <span id="results-number">0</span>
            </span>
          </div>
        </div>
      </section>

      <!-- Events Grid — SSR content rendered directly, JS replaces in-place -->
      <section class="events-section">
        <div id="events-grid" class="events-grid<?php echo !empty($ssr_events) ? ' has-ssr' : ''; ?>">
          <?php if (!empty($ssr_events)): ?>
          <?php foreach ($ssr_events as $ev):
            $artists = json_decode($ev['artists_en'], true) ?: [];
            $artistName = implode(' & ', array_filter($artists)) ?: '—';
            $genre = $ev['genre_en'] ?? '';
            $date = date('F j, Y', strtotime($ev['event_date']));
            $venue = $ev['venue_en'] ?? '';
          ?>
          <div class="event-card">
            <h3 class="event-artist"><span class="event-artist-name"><?= htmlspecialchars($artistName, ENT_QUOTES, 'UTF-8') ?></span></h3>
            <div class="event-details">
              <div class="event-detail"><span class="event-detail-label">Date:</span> <span><?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8') ?></span></div>
              <div class="event-detail"><span class="event-detail-label">Venue:</span> <span><?= htmlspecialchars($venue, ENT_QUOTES, 'UTF-8') ?></span></div>
              <?php if ($genre): ?><div class="event-detail"><span class="event-detail-label">Genre:</span> <span><?= htmlspecialchars($genre, ENT_QUOTES, 'UTF-8') ?></span></div><?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
          <?php endif; ?>
          <div class="loading">Loading...</div>
        </div>
      </section>
    </main>

    <!-- Global Scripts -->
    <script src="/js/utils.js" defer></script>
    <script src="/js/main.js" defer></script>
    <!-- Events Page Scripts -->
    <script src="/js/events.js" defer></script>
  </body>
</html>
