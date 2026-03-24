/* ================================================
   gallery.js - Gallery Page
   KOD Müzik Website
   ================================================ */

(function () {
  const lang = detectCurrentLanguage();
  const grid = document.getElementById("gallery-grid");
  const lightbox = document.getElementById("gallery-lightbox");
  const lightboxImg = document.getElementById("lightbox-img");
  const lightboxCaption = document.getElementById("lightbox-caption");
  const categoryFilter = document.getElementById("gallery-category-filter");
  const yearFilter = document.getElementById("gallery-year-filter");

  if (!grid) return;

  let allImages = [];
  let currentIndex = 0;

  // Load gallery data
  fetch("/api/gallery.php")
    .then((r) => {
      if (!r.ok) throw new Error("network");
      return r.json();
    })
    .then((data) => {
      allImages = data.images || [];

      if (!allImages.length) {
        grid.innerHTML =
          lang === "tr"
            ? '<p class="gallery-empty">Henüz görsel yok.</p>'
            : '<p class="gallery-empty">No images yet.</p>';
        return;
      }

      // Populate year filter from data
      const years = [...new Set(allImages.map((img) => img.year).filter(Boolean))].sort(
        (a, b) => b - a,
      );
      years.forEach((y) => {
        const opt = document.createElement("option");
        opt.value = y;
        opt.textContent = y;
        yearFilter.appendChild(opt);
      });

      renderGallery(allImages);
    })
    .catch((e) => {
      console.error(e);
      grid.innerHTML =
        lang === "tr"
          ? '<p class="gallery-empty">Galeri yüklenemedi.</p>'
          : '<p class="gallery-empty">Failed to load gallery.</p>';
    });

  // Filters
  categoryFilter.addEventListener("change", applyFilters);
  yearFilter.addEventListener("change", applyFilters);

  function applyFilters() {
    const cat = categoryFilter.value;
    const year = yearFilter.value;

    let filtered = allImages;
    if (cat) filtered = filtered.filter((img) => img.category === cat);
    if (year) filtered = filtered.filter((img) => img.year === parseInt(year));

    renderGallery(filtered);
  }

  function renderGallery(images) {
    if (!images.length) {
      grid.innerHTML =
        lang === "tr"
          ? '<p class="gallery-empty">Sonuç bulunamadı.</p>'
          : '<p class="gallery-empty">No results found.</p>';
      return;
    }

    grid.innerHTML = images
      .map(
        (img, i) => `
      <div class="gallery-item" data-index="${i}">
        <img src="${img.thumbnail || img.image}"
             alt="${img.caption[lang] || ""}"
             loading="lazy"
             data-full="${img.image}" />
        ${img.caption[lang] ? `<p class="gallery-item-caption">${img.caption[lang]}</p>` : ""}
      </div>
    `,
      )
      .join("");

    // Click handlers
    grid.querySelectorAll(".gallery-item").forEach((item) => {
      item.addEventListener("click", () => {
        currentIndex = parseInt(item.dataset.index);
        openLightbox(images[currentIndex]);
      });
    });
  }

  // Lightbox
  function openLightbox(img) {
    lightboxImg.src = img.image;
    lightboxImg.alt = img.caption[lang] || "";
    lightboxCaption.textContent = img.caption[lang] || "";
    lightbox.hidden = false;
    document.body.style.overflow = "hidden";
  }

  function closeLightbox() {
    lightbox.hidden = true;
    lightboxImg.src = "";
    document.body.style.overflow = "";
  }

  function navigate(direction) {
    const filtered = getFilteredImages();
    currentIndex = (currentIndex + direction + filtered.length) % filtered.length;
    openLightbox(filtered[currentIndex]);
  }

  function getFilteredImages() {
    const cat = categoryFilter.value;
    const year = yearFilter.value;
    let filtered = allImages;
    if (cat) filtered = filtered.filter((img) => img.category === cat);
    if (year) filtered = filtered.filter((img) => img.year === parseInt(year));
    return filtered;
  }

  lightbox.querySelector(".lightbox-close").addEventListener("click", closeLightbox);
  lightbox.querySelector(".lightbox-prev").addEventListener("click", () => navigate(-1));
  lightbox.querySelector(".lightbox-next").addEventListener("click", () => navigate(1));

  lightbox.addEventListener("click", (e) => {
    if (e.target === lightbox) closeLightbox();
  });

  document.addEventListener("keydown", (e) => {
    if (lightbox.hidden) return;
    if (e.key === "Escape") closeLightbox();
    if (e.key === "ArrowLeft") navigate(-1);
    if (e.key === "ArrowRight") navigate(1);
  });
})();
