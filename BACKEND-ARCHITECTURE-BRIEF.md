# KOD Müzik — Backend Architecture Brief
## For Claude Sonnet (Executor) in VS Code

**Architect:** Claude Opus (Cowork session, March 24, 2026)
**Project:** kodmuzik.com — PHP + MySQL backend for event management and image gallery
**Hosting:** Medium Linux Hosting at cp25.hosting.sh.com.tr (cPanel)

---

## HOSTING ENVIRONMENT (VERIFIED)

| Item | Value |
|---|---|
| Server | cp25.hosting.sh.com.tr |
| OS | Linux x86_64 |
| Web Server | Apache 2.4.66 + LiteSpeed Cache |
| PHP (system) | 8.3 (ea-php83) |
| PHP (kodmuzik.com currently) | 8.3 (ea-php83) — **UPGRADED from 7.3 on March 24, 2026** |
| PHP-FPM | Available but currently disabled |
| MySQL | 8.0.37 — running, healthy |
| Database quota | 1 database, 2.72 GB storage (0% used) |
| Database prefix | `kodmuzik_` (enforced by cPanel) |
| Disk space | 7 GB total, 4.28 GB used, ~2.7 GB free |
| Bandwidth | 30 GB/month (1.19% used) |
| Cron Jobs | Available |
| Backups | JetBackup 5 available |
| SSL | Active |
| phpMyAdmin | Available |
| Home directory | /home/kodmuzik |
| Primary domain | kodmuzik.com |

---

## EXISTING CODEBASE (DO NOT MODIFY UNLESS STATED)

### File Structure
```
/                           ← Site root (public_html)
├── index.html              ← TR homepage
├── en/                     ← EN mirror (all pages)
│   ├── index.html
│   ├── upcoming-events/index.html
│   └── ...
├── etkinlikler/            ← TR past events page
├── gelecek-etkinlikler/    ← TR future events page (currently lightboard placeholder)
├── hakkimizda/             ← TR about page
├── iletisim/               ← TR contact page
├── css/
│   ├── main.css
│   └── future-events.css
├── js/
│   ├── events.js           ← CRITICAL: fetches from /kod_muzik_events.json
│   └── ...
├── kod_muzik_events.json   ← 379 past events (bilingual TR/EN)
├── future_events.json      ← Future events placeholder data
├── contact-handler.php     ← ONLY existing PHP file — needs FILTER_SANITIZE_STRING fix
└── KodLogo.svg
```

### Current Data Fetch (events.js, line 213)
```javascript
const response = await fetch("/kod_muzik_events.json");
const data = await response.json();
eventsData = data.events;
```

### Past Events JSON Schema (kod_muzik_events.json — 379 records)
```json
{
  "events": [
    {
      "id": "event_001",
      "artist": { "tr": ["Artist Name"], "en": ["Artist Name"] },
      "genre": { "tr": "Metal", "en": "Metal" },
      "date": "09.03.1996",
      "venue": { "tr": "Mekan Adı", "en": "Venue Name" },
      "city": { "tr": "İstanbul", "en": "Istanbul" },
      "series": { "tr": "Seri Adı", "en": "Series Name" },
      "description": { "tr": "", "en": "" }
    }
  ]
}
```
Key notes:
- `artist` is ARRAY (can have multiple artists per event)
- `genre` can be string or array
- `date` format is DD.MM.YYYY
- `series` is optional (empty string when unused)
- `description` is optional (mostly empty in current data)

### Future Events JSON Schema (future_events.json)
```json
{
  "meta": { "schema": "kodmuzik.future.v1", "generated": "2025-01-01" },
  "events": [
    {
      "id": "fe-2025-002",
      "date": "15-10-2025",
      "title": { "tr": "Yeni Konser", "en": "New Concert" },
      "venue": { "tr": "CRR", "en": "CRR" },
      "city": { "tr": "İstanbul", "en": "Istanbul" },
      "ticketUrl": null,
      "infoUrl": null
    }
  ]
}
```
Key notes:
- Future events use `title` (not `artist`) — different from past events
- Date format is DD-MM-YYYY (dashes, not dots — inconsistent with past events)
- Has `ticketUrl` and `infoUrl` fields

---

## PHASE 0 — PRE-FLIGHT ✅ COMPLETED (March 24, 2026)

All pre-flight steps have been completed:
1. ✅ JetBackup snapshot confirmed (daily backup from 24 Mar 2026 02:37)
2. ✅ PHP upgraded from 7.3 → 8.3 via cPanel MultiPHP Manager
3. ✅ `contact-handler.php` fixed: `FILTER_SANITIZE_STRING` replaced with `FILTER_DEFAULT` + `htmlspecialchars()`
4. ✅ Site verified: homepage, events page (JSON fetch + filters), lightboard placeholder — all working perfectly

---

## PHASE 1 — DATABASE SCHEMA

Database name: `kodmuzik_events`
Charset: `utf8mb4` (mandatory — Turkish characters + emoji support)
Collation: `utf8mb4_unicode_ci`

### Table: `events`
```sql
CREATE TABLE events (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_type ENUM('past', 'future') NOT NULL DEFAULT 'past',
    title_tr VARCHAR(255) DEFAULT NULL,
    title_en VARCHAR(255) DEFAULT NULL,
    artists_tr JSON DEFAULT NULL,       -- Array: ["Artist 1", "Artist 2"]
    artists_en JSON DEFAULT NULL,       -- Array: ["Artist 1", "Artist 2"]
    genre_tr VARCHAR(100) DEFAULT NULL,
    genre_en VARCHAR(100) DEFAULT NULL,
    event_date DATE NOT NULL,
    venue_tr VARCHAR(255) NOT NULL,
    venue_en VARCHAR(255) NOT NULL,
    city_tr VARCHAR(100) NOT NULL,
    city_en VARCHAR(100) NOT NULL,
    series_tr VARCHAR(255) DEFAULT '',
    series_en VARCHAR(255) DEFAULT '',
    description_tr TEXT DEFAULT NULL,
    description_en TEXT DEFAULT NULL,
    ticket_url VARCHAR(500) DEFAULT NULL,
    info_url VARCHAR(500) DEFAULT NULL,
    status ENUM('draft', 'published') NOT NULL DEFAULT 'published',
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_event_type (event_type),
    INDEX idx_event_date (event_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table: `gallery`
```sql
CREATE TABLE gallery (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id INT UNSIGNED DEFAULT NULL,  -- NULL = standalone image
    image_path VARCHAR(500) NOT NULL,    -- Relative path: /uploads/gallery/filename.jpg
    thumbnail_path VARCHAR(500) DEFAULT NULL,
    caption_tr VARCHAR(500) DEFAULT NULL,
    caption_en VARCHAR(500) DEFAULT NULL,
    category ENUM('poster', 'photo', 'flyer', 'other') DEFAULT 'photo',
    year SMALLINT UNSIGNED DEFAULT NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE SET NULL,
    INDEX idx_category (category),
    INDEX idx_year (year),
    INDEX idx_event (event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table: `admin_users`
```sql
CREATE TABLE admin_users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,  -- password_hash() with PASSWORD_DEFAULT
    display_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Data Migration Script
Write a PHP script (`migrate_json_to_db.php`) that:
1. Reads `kod_muzik_events.json` (379 past events)
2. Reads `future_events.json` (future events)
3. Parses each record and INSERTs into the `events` table
4. Converts DD.MM.YYYY and DD-MM-YYYY date formats to MySQL DATE (YYYY-MM-DD)
5. Stores artist arrays as JSON
6. Sets `event_type = 'past'` or `'future'` accordingly
7. Creates a default admin user (username: admin, temporary password to be changed)
8. Outputs a summary of migrated records
9. **This script runs once and should be deleted from server afterward**

---

## PHASE 2 — CONFIG & DATABASE CONNECTION

### File: `/api/config.php`
```php
<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'kodmuzik_events');    // Actual name will be kodmuzik_[suffix]
define('DB_USER', 'kodmuzik_[user]');    // Created in cPanel
define('DB_PASS', '[password]');          // Created in cPanel
define('DB_CHARSET', 'utf8mb4');

// Paths
define('UPLOAD_DIR', $_SERVER['DOCUMENT_ROOT'] . '/uploads/');
define('GALLERY_DIR', UPLOAD_DIR . 'gallery/');
define('THUMB_DIR', UPLOAD_DIR . 'thumbnails/');

// Gallery settings
define('MAX_IMAGE_SIZE', 2 * 1024 * 1024);  // 2MB
define('THUMB_WIDTH', 400);
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'webp']);

// Security
define('ADMIN_SESSION_LIFETIME', 3600);  // 1 hour
define('CSRF_TOKEN_NAME', 'kod_csrf');

// PDO connection helper
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    return $pdo;
}
```

---

## PHASE 3 — PUBLIC API

### File: `/api/events.php`
Serves JSON to the frontend. Must output **exactly** the same structure the current JS expects.

**Endpoint:** `GET /api/events.php?type=past`
**Output:** Must match current `kod_muzik_events.json` structure:
```json
{
  "events": [
    {
      "id": "event_1",
      "artist": { "tr": ["Name"], "en": ["Name"] },
      "genre": { "tr": "Metal", "en": "Metal" },
      "date": "09.03.1996",
      "venue": { "tr": "...", "en": "..." },
      "city": { "tr": "...", "en": "..." },
      "series": { "tr": "...", "en": "..." },
      "description": { "tr": "...", "en": "..." }
    }
  ]
}
```

Key requirements:
- MUST convert MySQL DATE back to DD.MM.YYYY for past events
- MUST convert `artists_tr`/`artists_en` JSON columns back to `artist: { tr: [...], en: [...] }` format
- MUST output `"id": "event_[n]"` string format (prefix + number)
- Set `Content-Type: application/json; charset=utf-8`
- Set `Access-Control-Allow-Origin: *` for local dev
- Add `Cache-Control: public, max-age=300` (5 min cache)

**Endpoint:** `GET /api/events.php?type=future`
**Output:** Must match current `future_events.json` structure

### File: `/api/gallery.php`
**Endpoint:** `GET /api/gallery.php` — returns all published gallery images
**Endpoint:** `GET /api/gallery.php?event_id=5` — returns images for specific event
**Endpoint:** `GET /api/gallery.php?category=poster&year=1996` — filtered query

---

## PHASE 4 — FRONTEND SWITCHOVER

### Single change in `js/events.js` (line 213):
**Before:**
```javascript
const response = await fetch("/kod_muzik_events.json");
```
**After:**
```javascript
const response = await fetch("/api/events.php?type=past");
```

That's it. The API output matches the JSON structure exactly. Nothing else changes.

The future events page (`gelecek-etkinlikler/` and `en/upcoming-events/`) currently shows a lightboard placeholder (built in previous Cowork session — see `css/future-events.css`). When future events exist in the database, a new JS file or modification will be needed to fetch and display them. This is deferred until the admin panel allows adding future events.

---

## PHASE 5 — ADMIN PANEL

### Location: `/admin/`
Completely separate from the public site. Own CSS, own layout.

### File Structure
```
/admin/
├── index.php               ← Login page
├── dashboard.php           ← Overview: counts, recent activity
├── events.php              ← List all events (filterable, sortable)
├── event-form.php          ← Add/Edit event form (both past & future)
├── event-delete.php        ← Delete handler (POST only, with confirmation)
├── gallery.php             ← Gallery management
├── gallery-upload.php      ← Image upload handler
├── gallery-delete.php      ← Image delete handler
├── settings.php            ← Change password, basic settings
├── logout.php              ← Session destroy + redirect
├── includes/
│   ├── auth.php            ← Session check, login/logout functions
│   ├── header.php          ← Admin HTML header + nav
│   ├── footer.php          ← Admin HTML footer
│   └── functions.php       ← Shared helpers (CSRF, pagination, image resize)
├── css/
│   └── admin.css           ← Admin-only styles
└── .htaccess               ← Force HTTPS, deny direct access to includes/
```

### Authentication
- PHP sessions with `session_regenerate_id()` on login
- `password_hash()` / `password_verify()` (bcrypt)
- CSRF tokens on all forms
- Rate limiting on login (simple: track attempts in session or DB)
- Auto-logout after 1 hour of inactivity
- `.htaccess` protection on `/admin/includes/`

### Event Form Fields
- Event Type: radio — Past / Future
- Title TR / EN: text (shown for future events, optional for past)
- Artists TR / EN: text with comma separation (shown for past events)
- Genre TR / EN: text with datalist suggestions from existing genres
- Date: date picker
- Venue TR / EN: text with datalist suggestions
- City TR / EN: text with datalist suggestions
- Series TR / EN: text (optional)
- Description TR / EN: textarea (optional)
- Ticket URL: url (future events only)
- Info URL: url (future events only)
- Status: radio — Published / Draft

### Gallery Upload
- Accepts JPG, PNG, WebP up to 2MB
- Auto-generates thumbnail (400px width)
- Optional: link to existing event
- Fields: caption TR/EN, category (poster/photo/flyer/other), year
- Uses PHP GD library for image processing (available on most cPanel hosts)

### Admin UI Requirements
- Clean, minimal design — no framework needed (simple CSS)
- Mobile-friendly (the client may add events from phone)
- Turkish language interface (primary) with field labels in both languages
- Confirmation dialogs before delete operations
- Success/error flash messages after operations
- Pagination for event lists (20 per page)

---

## PHASE 6 — GALLERY PUBLIC PAGE (NEW)

This is a new page to be added to the site. It does not exist yet.

### Turkish: `/galeri/index.html`
### English: `/en/gallery/index.html`

Design should match the existing site aesthetic (dark background #121212, Anton display font, Inter body font). Filterable by year, category. Lightbox for full-size viewing. Lazy loading for images.

The JS fetches from `/api/gallery.php` and renders the grid.

---

## DIRECTORY STRUCTURE TO CREATE ON SERVER

```
/api/
    config.php
    events.php
    gallery.php
/admin/
    (all admin files as listed above)
/uploads/
    gallery/        ← Full-size images
    thumbnails/     ← Auto-generated thumbs
```

Add to `.gitignore` (if using git): `/api/config.php` (contains credentials)

---

## IMPORTANT CONSTRAINTS

1. **One database only.** All tables live in `kodmuzik_events` (or whatever suffix cPanel assigns). No room for a second database.
2. **~2.7 GB free disk space.** 1000 images at ~300KB = ~300MB. Plenty of room.
3. **PHP 8.3 target.** Do not use deprecated functions. The existing `contact-handler.php` has `FILTER_SANITIZE_STRING` which must be replaced (see Phase 0).
4. **Bilingual everything.** Every text field has `_tr` and `_en` variants. The public API must serve both.
5. **Keep JSON files as fallback.** After switchover, keep `kod_muzik_events.json` and `future_events.json` on server as static backups. The fetch URL change in events.js is the only switch — reverting that one line restores the old system.
6. **No frameworks.** Vanilla PHP, vanilla JS. Matches the existing site philosophy. No Laravel, no React, no jQuery.
7. **Test locally first.** Use `php -S localhost:8000` for local dev. Only the config.php credentials differ between local and production.
8. **UTF-8 everywhere.** Database charset `utf8mb4`, PHP `mb_string` functions for Turkish characters (İ, ı, Ş, ş, Ğ, ğ, Ü, ü, Ö, ö, Ç, ç).
9. **Date format conversion.** Database stores YYYY-MM-DD. API outputs DD.MM.YYYY for past events (to match existing frontend expectations). Admin forms use HTML5 date input (YYYY-MM-DD natively).

---

## CASCADE SUMMARY

| Phase | Deliverable | Risk | Rollback |
|---|---|---|---|
| 0 | PHP upgrade + backup | Low (static site unaffected) | JetBackup restore |
| 1 | Database schema + migration | Low (no public impact) | Drop tables |
| 2 | Config + DB connection | None (not linked yet) | Delete /api/ folder |
| 3 | Public API endpoints | None (not consumed yet) | Delete /api/ folder |
| 4 | Frontend switchover (1 line) | Minimal | Revert 1 line in events.js |
| 5 | Admin panel | None (separate /admin/) | Delete /admin/ folder |
| 6 | Gallery public page | None (new page) | Delete /galeri/ folder |

Each phase is independently deployable and reversible.
