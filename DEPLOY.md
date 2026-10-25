# Deploying to kodmuzik.com

The site is deployed by uploading files over FTP / cPanel File Manager.
A partial upload is what left `css/events.css` out of date from July to October 2026,
so every deploy follows the same three steps.

## 1. Get the list of changed files

From the repo root, against the commit that is currently live:

```
git diff --name-status <live-commit> main
```

`A` and `M` files are uploaded. `D` files are deleted from the server.
Never upload: `.git/`, `.vscode/`, `scripts/`, `*.md`, `*.pptx`, `package.json`,
`api/config.php` (the server keeps its own copy).

## 2. Upload

Upload each `A` / `M` file to the same path under `public_html/`, and delete each `D` file.

## 3. Verify

```
bash scripts/verify-deploy.sh
```

It prints `Checked N files, 0 problem(s).` when every static file on the live site
matches the repo. PHP files and `.htaccess` cannot be checked this way; test them
with the checks listed for each release below.

Then record the deployed commit at the top of this file.

---

## Release: fix/audit-safe-batch (October 2026)

Live before this release: `3d60a79` for most files, but `css/events.css` was still
the `1ba5648` version (April 2026).

### Upload (new or changed)

```
.htaccess
db/.htaccess
admin/event-form.php
admin/gallery-delete.php
admin/gallery-upload.php
admin/includes/auth.php
admin/includes/functions.php
admin/includes/header.php
admin/index.php
admin/logout.php
admin/settings.php
api/events.php
api/gallery.php
contact-handler.php
css/events.css
css/main.css
index.html
en/index.html
hakkimizda/index.html
en/about/index.html
etkinlikler/index.php
en/events/index.php
gelecek-etkinlikler/index.html
en/upcoming-events/index.html
galeri/index.html
en/gallery/index.html
iletisim/index.html
en/contact/index.html
js/events.js
js/future-events.js
js/gallery.js
js/main.js
robots.txt
sitemap.xml
```

### Delete from the server (if present)

```
css/future-events.css
css/home.css
js/home.js
legacy-events-app/
```

### Checks after upload

1. `bash scripts/verify-deploy.sh` → 0 problems.
2. https://www.kodmuzik.com/etkinlikler/ loads, cards show; pick a year filter, clear it,
   scroll straight to the bottom: cards down to 1996 are visible within about a second.
3. Menu shows GALERİ / GALLERY on every page; the gallery page loads images.
4. Contact form: send one test message from `/iletisim/`; it arrives at iletisim@kodmuzik.com
   with **From: KOD Müzik Web Sitesi** and the visitor's address as Reply-To.
5. Admin: log in, log out (logout is now a button), log in again; add or edit one event;
   upload one gallery image.
6. https://www.kodmuzik.com/db/schema.sql and https://www.kodmuzik.com/README.md return 403.
7. https://www.kodmuzik.com/robots.txt shows the two `Allow: /api/...` lines.
8. In Search Console, request indexing for `/galeri/`, `/en/gallery/`,
   `/gelecek-etkinlikler/` and `/en/upcoming-events/`, and resubmit `sitemap.xml`.
