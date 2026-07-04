# KOD Müzik — Project Instructions

Parent protocol: `~/.claude/CLAUDE.md` (Architect & Executor principles). This file
holds project-specific addenda.

## SEO / GSC

**Round 3 fixes are LIVE as of 2026-07-04 (commit `6cef2e9`). Do not undo them.**

- **`js/events.js` — `loadEvents()` catch block guards on `has-ssr`** before
  overwriting `#events-grid`. This is the **Soft 404 fix**: it preserves the
  server-rendered event cards when `/api/events.php` is unreachable during
  Googlebot's render. **Do not remove the guard.**
- **`.htaccess` Rule 3a** = trailing-slash 301 on physical directories.
  **Rule 1c** = pure `mod_rewrite` for `/index/` and `/en/index/`.

**Source of truth for GSC status:** `GSC_ROUND3_STATUS.md`.

**"Page with redirect" URLs** (`index.html`, `/?f`, `.html` variants) are
**intentional** — never "fix" or re-validate them.

**Regression guardrail:** if you edit `loadEvents()` or the `.htaccess` redirect
rules, keep the `has-ssr` guard and the trailing-slash / `/index/` 301s intact —
otherwise the Soft 404 / Crawled-currently-not-indexed issues will re-open in
Search Console.
