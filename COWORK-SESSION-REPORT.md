# Cowork Session Report — Future Events Placeholder & Mobile Menu Fix

**Date:** March 23, 2026
**Scope:** Replaced the Future/Gelecek events page with an Akira/Serial Experiments Lain-inspired lightboard placeholder. Fixed mobile menu height-responsiveness.

---

## Files Modified by This Session

### 1. `css/future-events.css` — COMPLETE REWRITE

**What it was:** Timeline layout with glassmorphic event cards, action pills, newsletter section.
**What it is now:** A full-viewport Akira/Lain lightboard placeholder.

**Key design decisions:**

- **The sign is an opaque `#0a0a0a` panel** — not glass, not transparent. A physical object that pushes light outward into darkness.
- **Text glows like fluorescent tubes** — layered `text-shadow` (4-5 layers, from tight 2px core to 90px diffuse wash). Title uses Anton display font at `clamp(1.6rem, 4vw, 3.2rem)`.
- **Visible slow radiation cycle (8s)** — everything breathes between a dim state and a bright state. The ambient light pool behind the sign physically grows (`scale(1) → scale(1.35)`) using CSS transforms on `::before` and `::after` pseudo-elements. Text brightness, border glow, box-shadow layers, and the bottom reflection all animate in sync.
- **Two ambient spill layers** — `::before` on `.future-placeholder` (closer, sharper, 85% width) and `::after` (wider, softer, 120% width, offset timing with `animation-delay: -2s`).
- **Surface texture** — two SVG `feTurbulence` filter layers inside the sign panel (defined in HTML, referenced by CSS via `filter: url(#grain)` and `filter: url(#imperfections)`). Fine grain (`baseFrequency: 0.75`, 4 octaves) simulates matte surface. Coarser imperfections (`baseFrequency: 0.15 0.35`, 2 octaves, seed 3) simulates dust/scratches. Both are invisible at dim (`opacity: 0`) and fade in at peak glow (`opacity: 0.35` / `0.28`) using `overlay` and `soft-light` blend modes. Light reveals what's on the surface.
- **Internal hotspot** — `::before` on `.lightboard` is a radial gradient brighter at center, simulating uneven internal illumination like real lightboxes.
- **Bottom reflection** — `::after` on `.lightboard` casts light downward and stretches during radiation (`scaleY(1) → scaleY(1.8)`).
- **Page-level darker background** — `.future-page` and `body:has(.future-page)` set `background-color: #080808` (darker than site's `#121212`). KOD watermark dimmed to `rgba(255,255,255,0.055)`. Both have `overflow: hidden` to contain the animated pseudo-elements that scale beyond viewport.
- **Mobile responsive** — sign scales down proportionally. Padding, font sizes, letter-spacing all use `clamp()` with mobile-appropriate values.
- **`prefers-reduced-motion`** — all animations disabled, static mid-brightness state with texture visible.

**CSS class inventory:**
- `.future-page` — applied to `<main>`, triggers page-level background override
- `.future-placeholder` — full-viewport flex centering container, hosts ambient spill pseudo-elements
- `.lightboard` — the sign itself (opaque panel, box-shadow, overflow:hidden for texture)
- `.lightboard-surface` — base class for texture overlay divs (position:absolute, inset:0)
- `.lightboard-grain` — fine grain texture layer (SVG filter)
- `.lightboard-imperfections` — coarse imperfection layer (SVG filter)
- `.lightboard-title` — h1, Anton font, fluorescent glow
- `.lightboard-rule` — hr divider strip
- `.lightboard-sub` — subtitle paragraph, Inter font, dimmer glow

**Animation names:** `ambient-radiate`, `ambient-radiate-far`, `sign-radiate`, `surface-reveal`, `imperfections-reveal`, `hotspot-radiate`, `title-radiate`, `sub-radiate`, `rule-radiate`, `reflection-radiate`

---

### 2. `gelecek-etkinlikler/index.html` — MAIN CONTENT REPLACED

**What changed:**
- Added inline SVG `<defs>` block between `</nav>` and `<main>` defining two filters: `#grain` and `#imperfections` (feTurbulence-based noise).
- `<main>` now has class `future-page` (was just `main-content`).
- Entire timeline content (`.future-header`, `.timeline-section` with two `.timeline-item` articles) replaced with the lightboard structure:
  ```html
  <div class="future-placeholder">
    <div class="lightboard">
      <div class="lightboard-surface lightboard-grain"></div>
      <div class="lightboard-surface lightboard-imperfections"></div>
      <h1 class="lightboard-title">Gelecek Etkinlikler</h1>
      <hr class="lightboard-rule" />
      <p class="lightboard-sub">Yakında açıklanacak</p>
    </div>
  </div>
  ```
- Script references unchanged (`utils.js`, `main.js`, `future-events.js`).

---

### 3. `en/upcoming-events/index.html` — MAIN CONTENT REPLACED

**Identical structural changes as the Turkish version above**, with English text:
- Title: `Future Events`
- Subtitle: `will be announced soon`
- `aria-label="Future events coming soon"`
- Same SVG filters, same class structure.

---

### 4. `css/main.css` — MOBILE MENU HEIGHT-RESPONSIVENESS ADDED

**What changed:** Added ~40 lines at the end of the `@media (max-width: 768px)` block (after line 910, before the closing `}`).

**Purpose:** The mobile side menu (logo, nav items, language toggle) used fixed pixel/rem sizes that caused overlapping when viewport height got short (landscape, split-screen, short devices).

**Approach:** Every fixed-size mobile menu property now uses `clamp(minimum, vh-preferred, original-max)`. The `vh` multipliers are calibrated as `original_size / 500 * 100`, meaning:
- At **500px+ viewport height**: all values hit their max = **identical to the original fixed sizes**. Zero visual change on any normal phone.
- Below **500px**: elements scale proportionally with viewport height.
- At **~300px** (extreme): safety-floor minimums kick in.

**Properties affected:**
| Element | Property | Original | Clamp |
|---|---|---|---|
| `.side-menu` | padding-top | 2rem | `clamp(0.75rem, 6.4vh, 2rem)` |
| `.side-menu` | padding-bottom | 1.5rem | `clamp(0.5rem, 4.8vh, 1.5rem)` |
| `.menu-logo` | margin-top | 15px | `clamp(4px, 3vh, 15px)` |
| `.menu-logo-img` | width | 58px | `clamp(34px, 11.6vh, 58px)` |
| `.menu-logo-caption` | font-size | 1rem | `clamp(0.6rem, 3.2vh, 1rem)` |
| `.menu-items` | gap | 1.2rem | `clamp(0.4rem, 3.8vh, 1.2rem)` |
| `.menu-link` | font-size | 0.95rem | `clamp(0.65rem, 3vh, 0.95rem)` |
| `.lang-switcher` | padding | 16px | `clamp(6px, 3.2vh, 16px)` |
| `.lang-link` | width/height | 44px | `clamp(28px, 8.8vh, 44px)` |
| `.lang-link` | font-size | 12px | `clamp(9px, 2.4vh, 12px)` |
| `.lang-link::before` | width/height | 36px | `clamp(24px, 7.2vh, 36px)` |

---

## Files NOT Modified by This Session (Changed by User Separately)

- `kod_muzik_events.json` — User added events 376-379 (Hiromi Solo, Dean Brown, etc.)
- `en/events/index.html` — Subtitle changed from "Between 1999 and 2025" to "Since 1999..."
- `etkinlikler/index.html` — Subtitle changed from "1999 ve 2025 arası" to "1999'dan beri..."

---

## Architecture Notes

- The `future-events.js` script file still exists and is loaded but the page no longer has any dynamic content for it to act on. It can be cleaned up or repurposed when real future events are added.
- The SVG filters (`#grain`, `#imperfections`) are defined inline in each HTML file (TR and EN) because SVG filter definitions must be in the DOM to be referenced by CSS `filter: url(#id)`. They cannot live in an external CSS file.
- The `body:has(.future-page)` selector requires modern browser support (Chrome 105+, Safari 15.4+, Firefox 121+). For older browser fallback, the darker background could be applied via a body class set in JS instead.
- The `overflow: hidden` on `body:has(.future-page)` is essential — without it, the animated ambient spill pseudo-elements (which scale to 135% of their container) create scrollbars on mobile, causing a visible vertical bounce during the animation cycle.
