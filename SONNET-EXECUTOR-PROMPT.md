# EXECUTOR PROMPT — KOD Müzik Backend Build

## ROLE
You are the **executor**. The architecture has been designed and finalized by Claude Opus (architect). Your job is to implement it precisely as specified. Do not deviate from the architecture brief unless you encounter a technical impossibility — in which case, explain the issue and propose the minimal change needed.

## ARCHITECTURE BRIEF
Read `BACKEND-ARCHITECTURE-BRIEF.md` in this project root **before writing any code**. It contains the complete specification: hosting environment, database schema, API contracts, admin panel structure, and constraints. Every decision has already been made.

## STATUS
**Phase 0 is COMPLETE.** PHP is upgraded to 8.3, contact-handler.php is fixed, JetBackup snapshot exists. You are starting from Phase 1.

---

## STEP 0 — GIT BACKUP (DO THIS FIRST, BEFORE ANYTHING ELSE)

Before writing a single line of code, create a backup branch of the current state:

```bash
git checkout -b backup/pre-backend-$(date +%Y%m%d)
git add -A
git commit -m "Backup: pre-backend state — Phase 0 complete, PHP 8.3, contact-handler fixed"
git push origin backup/pre-backend-$(date +%Y%m%d)
git checkout main
```

This preserves the exact current state. If anything goes wrong during the build, we can restore from this branch.

---

## EXECUTION ORDER

Follow this exact sequence. Complete and test each phase before starting the next. Commit after each phase.

### Phase 1 — Database Schema + Migration Script
- Read the `BACKEND-ARCHITECTURE-BRIEF.md` for the exact SQL `CREATE TABLE` statements (events, gallery, admin_users)
- Create `db/schema.sql` with all three table definitions
- Create `db/migrate_json_to_db.php` that reads `kod_muzik_events.json` (379 past events) and `future_events.json` (2 future events), converts them, and inserts into the events table
- The migration script must also create a default admin user (username: `admin`, password: `kodmuzik2026!` — to be changed on first login)
- Date conversion: `DD.MM.YYYY` → `YYYY-MM-DD` for past events, `DD-MM-YYYY` → `YYYY-MM-DD` for future events
- Artist arrays must be stored as JSON in `artists_tr` / `artists_en` columns
- Genre can be string or array in the JSON — normalize to string in DB
- **Commit:** `"Phase 1: Database schema and migration script"`

### Phase 2 — Config & DB Connection
- Create `/api/config.php` as specified in the brief (PDO connection helper, path constants, gallery settings)
- Use placeholder credentials with clear `[CHANGE_ME]` markers
- Create `/api/.htaccess` to deny direct access to config.php
- **Commit:** `"Phase 2: API config and database connection layer"`

### Phase 3 — Public API
- Create `/api/events.php` — serves JSON in **exactly** the same format the frontend currently expects
- The output for `?type=past` must be field-for-field identical to `kod_muzik_events.json` structure
- The output for `?type=future` must be field-for-field identical to `future_events.json` structure
- Create `/api/gallery.php` — serves gallery images with filtering by event_id, category, year
- Set proper headers: `Content-Type: application/json; charset=utf-8`, `Cache-Control`, CORS
- **Commit:** `"Phase 3: Public API endpoints for events and gallery"`

### Phase 4 — Frontend Switchover (DO NOT APPLY YET)
- Create a commented-out version of the switchover in `js/events.js`
- Add a comment block showing exactly what to change (line 213: JSON path → API path)
- Do NOT actually change the fetch URL yet — we will do this manually after testing the API on the live server
- **Commit:** `"Phase 4: Frontend switchover prepared (not activated)"`

### Phase 5 — Admin Panel
- Build the complete `/admin/` directory as specified in the brief
- Authentication: PHP sessions, bcrypt, CSRF tokens, rate limiting
- CRUD for events (past + future, with appropriate field visibility)
- Gallery management: upload, thumbnail generation (GD library), caption, category, year, link to event
- Settings: password change
- Admin UI: clean, minimal, mobile-friendly, Turkish primary language
- **Commit:** `"Phase 5: Admin panel with event CRUD and gallery management"`

### Phase 6 — Gallery Public Page
- Create `/galeri/index.html` (TR) and `/en/gallery/index.html` (EN)
- Design must match existing site aesthetic: dark #121212 bg, Anton display font, Inter body font
- JS fetches from `/api/gallery.php`
- Filterable by year, category
- Lightbox for full-size images
- Lazy loading
- Add gallery links to the site navigation (both TR and EN menus)
- **Commit:** `"Phase 6: Public gallery page with filtering and lightbox"`

---

## CONSTRAINTS (NON-NEGOTIABLE)

1. **No frameworks.** Vanilla PHP 8.3, vanilla JS, vanilla CSS. No Laravel, no React, no jQuery, no Tailwind, no Bootstrap.
2. **UTF-8 everywhere.** `utf8mb4` charset, `mb_string` functions for Turkish characters.
3. **Bilingual.** Every user-facing text field has `_tr` and `_en` variants.
4. **Keep JSON files.** Do not delete `kod_muzik_events.json` or `future_events.json`. They are the fallback.
5. **One database.** All tables in one database. The prefix `kodmuzik_` is enforced by cPanel.
6. **Security.** Prepared statements everywhere. No raw SQL concatenation. CSRF on all forms. XSS-safe output with `htmlspecialchars()`.
7. **PHP 8.3 compatible.** No deprecated functions. No `FILTER_SANITIZE_STRING`. Use typed parameters, null coalescing, match expressions where appropriate.
8. **Add to .gitignore:** `/api/config.php` (contains credentials), `/uploads/` (user content)

---

## GIT DISCIPLINE

- Commit after each phase with the exact message specified above
- Each commit should be a working, non-breaking state
- Push all commits to origin when complete
- Final state should have these branches:
  - `backup/pre-backend-[date]` — pristine pre-build state
  - `main` — with all 6 phases committed

---

## TESTING NOTES

- Test locally with `php -S localhost:8000` before deploying
- The migration script needs a local MySQL database to test against
- API endpoints can be tested with `curl` or browser
- Admin panel should be tested for: login, CRUD operations, image upload, password change
- Gallery page should be tested for: image grid rendering, filtering, lightbox, lazy loading

---

## FINAL CHECKLIST

Before declaring done, verify:
- [ ] `backup/pre-backend-[date]` branch exists and is pushed
- [ ] Schema SQL creates all 3 tables without errors
- [ ] Migration script handles all 379 past events + 2 future events
- [ ] API `?type=past` output matches `kod_muzik_events.json` structure exactly
- [ ] API `?type=future` output matches `future_events.json` structure exactly
- [ ] Admin login works with bcrypt
- [ ] Admin CRUD: create, edit, delete events
- [ ] Admin gallery: upload, thumbnail generation, delete
- [ ] Gallery public page renders and filters work
- [ ] All navigation links updated (TR + EN)
- [ ] `.gitignore` includes `api/config.php` and `uploads/`
- [ ] No deprecated PHP functions used
- [ ] All SQL uses prepared statements
