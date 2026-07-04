# GSC Fix Round 3 — Sonnet Coder Prompt

## Context
Google Search Console is flagging 2 active issues for kodmuzik.com that need code fixes. After the fixes, we'll validate in GSC.

## Fix 1: events.js — Preserve SSR Content When API Fails (SOFT 404 FIX)

**Problem:** When Google renders `/en/events/` (and `/etkinlikler/`), the JavaScript `fetch('/api/events.php?type=past')` fails because Google's rendering servers can't reach the API. The catch block at line 237-239 then REPLACES the perfectly good PHP SSR event cards with `<div class="error">Error loading events</div>`. Google sees: filters + error message = Soft 404.

**File:** `js/events.js`

**Current code (lines 223-241):**
```javascript
async function loadEvents() {
  try {
    const response = await fetch("/api/events.php?type=past");
    if (!response.ok) throw new Error("Failed to load events");

    const data = await response.json();
    eventsData = data.events;

    populateFilters(data.meta);
    applyURLFiltersToUI();
    applyFilters();
  } catch (error) {
    console.error("Error loading events:", error);
    document.getElementById("events-grid").innerHTML =
      `<div class="error">${translations[currentLang].error}</div>`;
  }
}
```

**Replace with:**
```javascript
async function loadEvents() {
  try {
    const response = await fetch("/api/events.php?type=past");
    if (!response.ok) throw new Error("Failed to load events");

    const data = await response.json();
    eventsData = data.events;

    populateFilters(data.meta);
    applyURLFiltersToUI();
    applyFilters();
  } catch (error) {
    console.error("Error loading events:", error);
    const grid = document.getElementById("events-grid");
    // Preserve SSR content if present — don't replace server-rendered
    // event cards with an error message. This prevents Google from
    // classifying the page as a Soft 404 when the API is unreachable.
    if (!grid.classList.contains("has-ssr")) {
      grid.innerHTML =
        `<div class="error">${translations[currentLang].error}</div>`;
    }
  }
}
```

**Why:** If SSR content already exists (`has-ssr` class on grid), the page is perfectly functional — keep it. Only show the error when there's no SSR fallback (e.g., user navigated in-app and the grid was already replaced by JS).

---

## Fix 2: .htaccess — Directory Trailing Slash + Ghost /index/ Fixes (CRAWLED NOT INDEXED FIX)

**Problem A:** Rule 4 (Force Trailing Slash) has `!-d` condition, which means physical directories like `/etkinlikler` skip the redirect and serve 200 at both `/etkinlikler` and `/etkinlikler/`. Google crawls the non-trailing-slash version and marks it "Crawled - currently not indexed" because the canonical is the trailing-slash version.

**Problem B:** Rule 1c uses `RedirectMatch` (mod_alias) mixed with `RewriteRule` (mod_rewrite). These modules process at different phases, causing unpredictable behavior for `/index/` and `/en/index/` ghost URLs.

**File:** `.htaccess`

### Change 1: Convert Rule 1c from RedirectMatch to RewriteRule

**Current (lines 16-18):**
```apache
# 1c. Redirect /index/ and /en/index/ to clean roots
RedirectMatch 301 ^/index/?$ /
RewriteRule ^en/index/?$ /en/ [L,R=301,NC]
```

**Replace with:**
```apache
# 1c. Redirect /index/ and /en/index/ to clean roots (pure mod_rewrite)
RewriteRule ^index/?$ / [L,R=301,NC]
RewriteRule ^en/index/?$ /en/ [L,R=301,NC]
```

### Change 2: Add directory trailing slash rule BEFORE Rule 4

**Current Rule 4 (lines 43-47):**
```apache
# 4. Force Trailing Slash (Relative Path)
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_URI} !/$
RewriteRule ^(.+)$ /$1/ [L,R=301]
```

**Replace with (add 3a before existing Rule 4):**
```apache
# 3a. Force Trailing Slash on Directories
# Physical directories (etkinlikler, en/events, etc.) need explicit
# trailing-slash redirect — the generic Rule 4 skips them via !-d.
RewriteCond %{REQUEST_FILENAME} -d
RewriteCond %{REQUEST_URI} !/$
RewriteRule ^(.+)$ /$1/ [L,R=301]

# 4. Force Trailing Slash (Non-Directory Paths)
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_URI} !/$
RewriteRule ^(.+)$ /$1/ [L,R=301]
```

---

## Summary of All Changes

| File | What | Why |
|------|------|-----|
| `js/events.js` | Preserve SSR content in catch block | Fixes Soft 404 on events pages |
| `.htaccess` line 17 | `RedirectMatch` → `RewriteRule` | Fixes `/index/` ghost URL processing |
| `.htaccess` before Rule 4 | Add directory trailing slash rule | Fixes 4 URLs missing trailing slash |

## No Other Files Changed
- `en/events/index.php` — no changes needed (SSR works correctly)
- `etkinlikler/index.php` — no changes needed
- `sitemap.xml` — no changes needed
- `robots.txt` — no changes needed
