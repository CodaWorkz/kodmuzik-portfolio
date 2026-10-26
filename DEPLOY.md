# Deploying to kodmuzik.com

**Live commit: `83ace16`** (fix/audit-safe-batch + new mobile menu), deployed 2026-10-26.

The site is deployed by uploading files over FTP / cPanel File Manager.
A partial upload is what left `css/events.css` out of date from July to October 2026,
so every deploy follows the same three steps.

When any CSS or JS file changes, bump ?v= in the 10 page files to the deploy date (YYYYMMDD) and upload them too.

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

## Release: fix/audit-safe-batch + new mobile menu (October 2026)

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
iletisim/index.html
en/contact/index.html
js/events.js
js/future-events.js
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

### Before upload

- Open the server's `api/config.php` and confirm it defines `UPLOAD_DIR`
  (the template does; the new gallery delete check uses it).
- Download the live `.htaccess` as `.htaccess.bak` so it can be put back in one step.

### Checks after upload

0. Right after uploading `.htaccess`, load the home page. If it shows a 500 error,
   put `.htaccess.bak` back and tell me: the host then limits `.htaccess` overrides.
1. `bash scripts/verify-deploy.sh` → 0 problems.
2. https://www.kodmuzik.com/etkinlikler/ loads, cards show; pick a year filter, clear it,
   scroll straight to the bottom: cards down to 1996 are visible within about a second.
3. English pages: the current page is highlighted in the menu.
3b. On a phone (or a narrow browser window): the top bar shows the logo and a
   two-line menu icon; tapping it opens the full-screen menu, which turns from dark
   to white; TR | EN switches language; × closes it. Desktop looks as before.
4. Contact form: send one test message from `/iletisim/`; it arrives at iletisim@kodmuzik.com
   with **From: KOD Müzik Web Sitesi** and the visitor's address as Reply-To.
5. Admin: log in, log out (logout is now a button), log in again; add or edit one event;
   upload one image in the admin gallery section.
6. https://www.kodmuzik.com/db/schema.sql and https://www.kodmuzik.com/README.md return 403.
7. https://www.kodmuzik.com/robots.txt shows the `Allow: /api/events.php` line.
8. In Search Console, request indexing for `/gelecek-etkinlikler/` and
   `/en/upcoming-events/`, and resubmit `sitemap.xml`.
