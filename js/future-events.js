/* ================================================
   future-events.js - Future Events (Timeline)
   KOD Müzik Website

   Loads /future_events.json and renders timeline items.
   Supports TR / EN via URL prefix, and disables action
   pills when URLs are not provided.
   ================================================ */

(function () {
  const lang = window.location.pathname.startsWith("/en") ? "en" : "tr";

  const i18n = {
    tr: {
      date: "Tarih",
      venue: "Mekan",
      info: "Bilgi",
      tickets: "Bilet",
      loading: "Yükleniyor…",
      error: "Gelecek etkinlikler yüklenemedi",
      tba: "Duyurulacak",
    },
    en: {
      date: "Date",
      venue: "Venue",
      info: "Info",
      tickets: "Tickets",
      loading: "Loading…",
      error: "Failed to load future events",
      tba: "TBA",
    },
  };

  const elTimeline = document.querySelector(".timeline-section");
  if (!elTimeline) return; // Not on this page

  // Show a lightweight loading state
  elTimeline.innerHTML = `<div class="loading" style="padding:1rem 0;color:var(--color-muted);">${i18n[lang].loading}</div>`;

  fetch("/future_events.json")
    .then((r) => {
      if (!r.ok) throw new Error("network");
      return r.json();
    })
    .then((data) => {
      const events = (data.events || [])
        .filter(isFuture)
        .sort((a, b) => parseDate(a.date) - parseDate(b.date));

      if (!events.length) {
        elTimeline.innerHTML = "";
        return;
      }

      const html = events.map(renderItem).join("");
      elTimeline.innerHTML = html;
      wireActions(elTimeline);
    })
    .catch((e) => {
      console.error(e);
      elTimeline.innerHTML = `<div class="error" style="padding:1rem 0;color:var(--color-muted);">${i18n[lang].error}</div>`;
    });

  function parseDate(dmy) {
    // Expect DD-MM-YYYY
    const [d, m, y] = dmy.split("-").map((v) => parseInt(v, 10));
    return new Date(y, (m || 1) - 1, d || 1);
  }

  function isFuture(ev) {
    const now = new Date();
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    const t = parseDate(ev.date);
    return t >= today;
  }

  function renderItem(ev) {
    const dt = parseDate(ev.date);
    const dateText = dt.toLocaleDateString(lang === "tr" ? "tr-TR" : "en-US", {
      year: "numeric",
      month: "long",
      day: "2-digit",
    });
    const title = (ev.title && ev.title[lang]) || "";
    const venue = (ev.venue && ev.venue[lang]) || i18n[lang].tba;
    const city = (ev.city && ev.city[lang]) || "";

    const infoDisabled = !ev.infoUrl;
    const ticketDisabled = !ev.ticketUrl;

    return `
      <article class="timeline-item" data-id="${ev.id}">
        <div class="timeline-content">
          <div class="timeline-date">${dateText}</div>
          <h2 class="timeline-title">${escapeHtml(title)}</h2>
          <div class="timeline-venue">${escapeHtml(city)} • ${escapeHtml(venue)}</div>
          ${ev.note && ev.note[lang] ? `<p class="timeline-desc">${escapeHtml(ev.note[lang])}</p>` : ""}
        </div>
        <div class="timeline-actions">
          ${renderPill(i18n[lang].info, "pill-info", ev.infoUrl, infoDisabled)}
          ${renderPill(i18n[lang].tickets, "pill-tickets", ev.ticketUrl, ticketDisabled)}
        </div>
      </article>`;
  }

  function renderPill(text, cls, url, disabled) {
    if (!url) {
      return `<button class="action-pill ${cls}" type="button" disabled aria-disabled="true" style="opacity:.5;cursor:not-allowed;">${text}</button>`;
    }
    return `<a class="action-pill ${cls}" href="${encodeURI(url)}" target="_blank" rel="noopener">${text}</a>`;
  }

  function wireActions(root) {
    // If we ever render disabled anchors, convert to buttons safely.
    root.querySelectorAll(".action-pill[disabled]").forEach((btn) => {
      btn.addEventListener("click", (e) => e.preventDefault());
    });

    // Optional: newsletter form (only if present)
    const form = document.querySelector(".newsletter-form");
    if (form) {
      form.addEventListener("submit", (e) => {
        e.preventDefault();
        const input = form.querySelector('input[type="email"]');
        const email = ((input && input.value) || "").trim();
        if (!email) return;
        // Placeholder action – integrate with your backend later
        console.log("Newsletter signup:", email);
        input.value = "";
      });
    }
  }

  function escapeHtml(str) {
    return String(str)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }
})();
