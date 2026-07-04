# KOD Müzik — GSC Round 3 Status

**Property:** https://www.kodmuzik.com/ (URL-prefix)
**Last updated:** 2026-07-04
**Next scheduled check:** 2026-07-14
**Status:** Fixes deployed & verified live. GSC validation in progress.

---

## Summary
Two recurring GSC issues (Soft 404 + Crawled-currently-not-indexed) were traced to root cause: the Round 3 fixes documented in `GSC_FIX_PROMPT_ROUND3.md` had never been applied to the code. They are now applied, committed, deployed to production, and verified. GSC validation was started on 2026-07-04.

## Root causes & fixes
1. **Soft 404 on `/en/events/`** — `js/events.js` `loadEvents()` catch block wiped the server-rendered event cards and showed an error div whenever the `/api/events.php` fetch failed during Googlebot's render. **Fix:** guard on `has-ssr` before overwriting the grid, so SSR cards are preserved when the API is unreachable.
2. **Crawled – currently not indexed (6 URLs)** — non-slash directory URLs (`/etkinlikler`, `/hakkimizda`, `/iletisim`, `/gelecek-etkinlikler`) plus `/index/` and `/en/index/`. `.htaccess` Rule 4 excluded physical directories (`!-d`). **Fix:** new Rule 3a forces a trailing-slash 301 on physical directories; Rule 1c converted from mixed `RedirectMatch` to pure `mod_rewrite`. (Live redirects were already firing via Apache `DirectorySlash` — these changes are hardening / regression-proofing.)

## Deployment
- **Commit:** `6cef2e9` — "GSC Round 3: preserve SSR on API failure (soft 404) + directory trailing-slash & /index/ 301s"
- **Files:** `.htaccess` (web root), `js/events.js`
- Pushed to `origin/main` and FTP-deployed to production on **2026-07-04**.

## Verification — 2026-07-04 (Phase C)
- Guard live on server: **confirmed** (cache-busted fetch of `/js/events.js`).
- Behavioral test on the actual deployed bytes: **PASS** — 383 SSR event cards preserved when the API was forced to fail; no error div.
- Redirects: `/etkinlikler`, `/hakkimizda`, `/iletisim`, `/gelecek-etkinlikler`, `/index/`, `/en/index/` → **3xx**; canonical `/etkinlikler/` → **200**.

## GSC actions taken — 2026-07-04
- Validate Fix started: **Soft 404** (1 page) and **Crawled – currently not indexed** (6 pages).
- Requested indexing: `/en/events/` (added to priority crawl queue).

## Baseline at deploy (for comparison on next check)
- Indexed: **10** pages · Not indexed: **12** (Page with redirect 5, Crawled-not-indexed 6, Soft 404 1).
- Performance (3 mo): 71 clicks · 441 impressions · 16.1% CTR · avg position 5.1.

## To verify on 2026-07-14
- [ ] Soft 404 validation → target **Passed**; `/en/events/` indexed.
- [ ] Crawled-not-indexed validation → target **Passed**; the 6 URLs reclassified as redirects / dropped.
- [ ] Pages report: indexed count up, not-indexed count down.
- [ ] Re-run redirect checks (regression): the 6 URLs still 3xx, canonicals 200.
- [ ] Confirm live `/js/events.js` still contains the `has-ssr` guard (no regression / re-deploy overwrite).
- [ ] Update this file with results + date.

## Do NOT
- Re-run validation on "Page with redirect" — those redirects are intentional (index.html, /?f, .html variants).
- Revert or re-apply the Round 3 changes — they are already live.
