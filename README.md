# KOD Müzik Portfolio Website

A bilingual (Turkish/English) portfolio website for KOD Müzik, showcasing past and future music events with advanced filtering capabilities.

## 🎵 Overview

KOD Müzik is a professional portfolio site featuring a comprehensive events database with 375+ past events and upcoming performances. The site offers an intuitive interface with smart filtering, language switching, and shareable URL support for client presentations.

## ✨ Features

### Core Functionality
- **Bilingual Support**: Full Turkish and English translations with seamless language switching
- **Events Database**: 375+ past events with comprehensive metadata (artist, genre, venue, date, series)
- **Advanced Filtering**:
  - Artist search (fuzzy search across both languages)
  - Genre filter (11 genres including Classical, Jazz, Alternative, Electronic, etc.)
  - Year filter (dynamic based on available events)
  - Venue filter (51+ venues across 7 cities)
- **Smart Cascading Filters**: Available options update based on selected filters
- **Shareable URLs**: Filter state persists in URL query parameters for easy sharing

### Technical Features
- **URL Query Parameters**: Share pre-filtered event results with clients
  - Example: `/etkinlikler?genre=Jazz&year=2020&venue=CRR`
  - Filter state preserved when switching languages
- **Responsive Design**: Mobile-first approach with optimized layouts
- **CSS-Only Background Logo**: Performance-optimized watermark system
- **Custom Select Components**: Accessible dropdown filters with keyboard navigation
- **Reverse Chronological Sorting**: Most recent events displayed first

### Pages
1. **Ana Sayfa / Home** (`/` or `/en/`)
2. **Hakkımızda / About** (`/hakkimizda` or `/en/hakkimizda`)
3. **Etkinlikler / Events** (`/etkinlikler` or `/en/etkinlikler`)
4. **Gelecek Etkinlikler / Future Events** (`/gelecek-etkinlikler` or `/en/gelecek-etkinlikler`)
5. **İletişim / Contact** (`/iletisim` or `/en/iletisim`)

## 🛠 Tech Stack

- **Frontend**: Vanilla JavaScript (ES6+), HTML5, CSS3
- **Data Storage**: JSON (static file-based database)
- **Server**: http-server (development)
- **Version Control**: Git + GitHub
- **No Framework**: Pure JavaScript for maximum performance and simplicity

## 📁 Project Structure

```
.
├── index.html                  # Turkish homepage
├── en/
│   ├── index.html             # English homepage
│   ├── etkinlikler/           # English events page
│   ├── gelecek-etkinlikler/   # English future events
│   ├── hakkimizda/            # English about page
│   └── iletisim/              # English contact page
├── etkinlikler/               # Turkish events page
├── gelecek-etkinlikler/       # Turkish future events
├── hakkimizda/                # Turkish about page
├── iletisim/                  # Turkish contact page
├── css/
│   ├── main.css               # Global styles
│   └── events.css             # Events page styles
├── js/
│   ├── main.js                # Global JavaScript (routing, language, menu)
│   └── events.js              # Events page logic (filtering, display)
├── kod_muzik_events.json      # Main events database (375 events)
├── future_events.json         # Upcoming events database
└── KodLogo.svg                # Brand logo
```

## 🚀 Setup & Installation

### Prerequisites
- Node.js (for http-server)
- Git

### Installation

1. Clone the repository:
```bash
git clone https://github.com/CodaWorkz/kodmuzik-portfolio.git
cd kodmuzik-portfolio
```

2. Install dependencies:
```bash
npm install
```

3. Start the development server:
```bash
npm run serve
```

4. Open your browser:
```
http://localhost:8080
```

## 💻 Development

### Running Locally
```bash
npm run serve
```
Server runs on `http://localhost:8080` with cache disabled (`-c-1` flag).

### File Watching
The development server automatically serves updated files. No build step required.

### Making Changes

#### Adding New Events
Edit `kod_muzik_events.json` following this structure:
```json
{
  "id": "fe-2025-xxx",
  "artist": {
    "tr": "Sanatçı Adı",
    "en": "Artist Name"
  },
  "genre": {
    "tr": "Tür",
    "en": "Genre"
  },
  "date": "DD.MM.YYYY",
  "venue": {
    "tr": "Mekan Adı",
    "en": "Venue Name"
  },
  "city": {
    "tr": "Şehir",
    "en": "City"
  },
  "series": {
    "tr": "Seri Adı (opsiyonel)",
    "en": "Series Name (optional)"
  }
}
```

#### Modifying Styles
- Global styles: Edit `css/main.css`
- Events page styles: Edit `css/events.css`
- Mobile breakpoint: `@media (max-width: 768px)`

#### Adding Translations
Update the `translations` object in `js/events.js`:
```javascript
const translations = {
  tr: { key: "Türkçe değer" },
  en: { key: "English value" }
};
```

## 🔗 URL Query Parameters

The events page supports URL query parameters for sharing filtered results:

### Supported Parameters
- `artist`: Artist name (searches both TR and EN)
- `genre`: Genre (must match English genre name)
- `year`: Year (YYYY format)
- `venue`: Venue (must match English venue name)

### Example URLs
```
# All Jazz events
/etkinlikler?genre=Jazz

# Specific artist
/etkinlikler?artist=John%20Coltrane

# Events at CRR in 2020
/etkinlikler?venue=CRR&year=2020

# Multiple filters
/etkinlikler?genre=Classical&venue=CSO%20Ankara&year=2021

# Same filters in English
/en/etkinlikler?genre=Classical&venue=CSO%20Ankara&year=2021
```

### How It Works
1. Apply filters using the UI
2. URL automatically updates with current filter state
3. Share the URL with clients
4. Recipients see the exact same filtered view
5. Language switching preserves all filter parameters

## 🎨 Key Features Implementation

### Language Switching with Filter Preservation
The language switcher updates dynamically when filters change, ensuring query parameters are always included in language toggle links.

**Files**: `js/main.js` (lines 114-147), `js/events.js` (lines 130-133)

### Cascading Filters
Filters intelligently update based on available options. For example, selecting a genre shows only years and venues that have events in that genre.

**Files**: `js/events.js` (lines 278-341)

### Mobile-Optimized Background Logo
CSS-only implementation using pseudo-elements for better performance.

**Files**: `css/main.css` (lines 421-487), `js/main.js` (lines 229-238)

### Responsive Mobile Touch Language Switcher
Enhanced touch interactions for mobile devices with proper hover detection.

**Files**: `js/main.js` (lines 153-193)

## 📊 Database Schema

### Events Database (`kod_muzik_events.json`)
```json
{
  "meta": {
    "schema": "kodmuzik.v1",
    "generated": "YYYY-MM-DD",
    "count": 375,
    "genres": [...],
    "venues": [...],
    "cities": [...]
  },
  "events": [...]
}
```

### Event Object
```json
{
  "id": "unique-id",
  "artist": { "tr": "...", "en": "..." },
  "genre": { "tr": "...", "en": "..." },
  "date": "DD.MM.YYYY",
  "venue": { "tr": "...", "en": "..." },
  "city": { "tr": "...", "en": "..." },
  "series": { "tr": "...", "en": "..." }
}
```

## 🐛 Troubleshooting

### Filters Not Working
- Check browser console for JavaScript errors
- Ensure `kod_muzik_events.json` is valid JSON
- Clear browser cache and reload

### Language Switching Issues
- Verify language toggle elements exist: `#lang-tr` and `#lang-en`
- Check that paths include proper `/en/` prefix for English pages

### URL Parameters Not Persisting
- Ensure `updateLanguageLinks()` is called after filter changes
- Verify `writeFiltersToURL()` is invoked in `applyFilters()`

## 🚢 Deployment

The site is static and can be deployed to any hosting service:

### GitHub Pages
```bash
git push origin main
```
Configure GitHub Pages to serve from `main` branch.

### Netlify / Vercel
1. Connect repository
2. No build command needed
3. Publish directory: `/` (root)

### Traditional Hosting
Upload all files via FTP/SFTP to web root.

## 📝 Git Workflow

### Commit Messages
Follow the conventional format:
```
<type>: <description>

<optional body>

Co-Authored-By: Claude Sonnet 4.5 <noreply@anthropic.com>
```

### Recent Commits
- `Add URL query parameter support for shareable event filters`
- `Fix language switcher to update when filters change`
- `Sort events in reverse chronological order (newest first)`
- `Fix mobile language switcher and update events database`

## 📄 License

ISC

## 🙏 Credits

Built with assistance from Claude Sonnet 4.5 by Anthropic.

---

**Repository**: https://github.com/CodaWorkz/kodmuzik-portfolio
**Issues**: https://github.com/CodaWorkz/kodmuzik-portfolio/issues
