# KOD Müzik website

Bilingual (Turkish / English) portfolio site for KOD Müzik: past events archive,
upcoming events timeline, gallery, about and contact pages, plus a small admin
panel to manage events and gallery images.

Live: https://www.kodmuzik.com/

## Stack

- Apache shared hosting (cPanel), PHP 8.x, MySQL (PDO)
- Static HTML pages + vanilla JavaScript, no build step
- Events and gallery data live in MySQL and are served by `api/events.php` and `api/gallery.php`
- The past events pages (`etkinlikler/index.php`, `en/events/index.php`) render the
  latest 20 events on the server, then `js/events.js` loads the full list from the API

## Structure

```
.htaccess                  Canonical redirects, clean URLs, headers, caching
index.html, en/index.html  Home pages
hakkimizda/, en/about/     About (content from kod_about_tr.json / kod_about_en.json)
etkinlikler/, en/events/   Past events (PHP: server-rendered first cards)
gelecek-etkinlikler/,
en/upcoming-events/        Upcoming events timeline (loads /api/events.php?type=future)
galeri/, en/gallery/       Gallery (loads /api/gallery.php)
iletisim/, en/contact/     Contact form -> contact-handler.php
api/                       Public JSON endpoints; api/config.php holds DB credentials (not in git)
admin/                     Admin panel (events, gallery, settings)
db/                        Schema, migration, nightly cron, local sync (blocked from the web)
css/, js/                  Styles and scripts; js/utils.js and js/main.js load on every page
scripts/verify-deploy.sh   Compares local files with what the live site serves
```

## Setup

1. Copy `api/config.example.php` to `api/config.php` and fill in the database values.
2. Create the tables with `db/schema.sql`.
3. Cron (cPanel → Cron Jobs), moves finished upcoming events to the past list:
   `0 3 * * * /usr/bin/php /home/USERNAME/public_html/db/transition-future-to-past.php`

## Local development

PHP's built-in server runs the pages and the API, but it ignores `.htaccess`
(no clean-URL rewrites or redirects):

```
npm run serve        # php -S localhost:8000
```

Open `http://localhost:8000/etkinlikler/index.php` etc. The API needs a local
MySQL with `api/config.php` pointing at it; `db/sync-from-live.php` copies the
live data down.

## Deploying

See `DEPLOY.md`. Upload only the files that changed, then run
`scripts/verify-deploy.sh` to confirm the live site serves the same files.

## SEO guardrails

Read `CLAUDE.md` before touching `js/events.js` (`loadEvents()` / `has-ssr`) or the
`.htaccess` redirect rules. GSC status is tracked in `GSC_ROUND3_STATUS.md`.

## Filter URLs

The past events page keeps its filters in the query string, so filtered views can be shared:

```
/etkinlikler/?genre=Jazz&year=2020
/en/events/?venue=CRR
```

Parameters: `artist`, `genre`, `year`, `venue` (genre and venue use the English value).
