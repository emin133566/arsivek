const themeToggle = document.getElementById('themeToggle');
const searchInput = document.getElementById('searchInput');
const newsGrid = document.getElementById('newsGrid');
const searchResults = document.getElementById('searchResults');
const featuredBanner = document.querySelector('.featured-banner');
const newsSection = document.querySelector('.news-section');

const storedTheme = localStorage.getItem('hbr-theme');
if (storedTheme) {
  document.body.classList.remove('theme-light', 'theme-dark');
  document.body.classList.add(storedTheme.includes('theme-dark') ? 'theme-dark' : 'theme-light');
}

function toggleTheme() {
  const isDark = document.body.classList.toggle('theme-dark');
  document.body.classList.toggle('theme-light', !isDark);
  localStorage.setItem('hbr-theme', isDark ? 'theme-dark' : 'theme-light');
}

function filterNews() {
  if (!searchInput || !newsGrid) return;
  const query = searchInput.value.trim().toLowerCase();
  const cards = Array.from(newsGrid.querySelectorAll('.news-card'));

  if (query) {
    const matches = cards.filter(card => {
      const title = (card.dataset.title || '').toLowerCase();
      const summary = (card.dataset.summary || '').toLowerCase();
      return title.includes(query) || summary.includes(query);
    });

    cards.forEach(card => {
      card.style.display = 'none';
    });

    if (featuredBanner) {
      featuredBanner.style.display = 'none';
    }

    if (searchResults) {
      searchResults.style.display = 'block';
      searchResults.innerHTML = '';

      if (matches.length > 0) {
        const resultsHeading = document.createElement('div');
        resultsHeading.className = 'search-results-heading';
        resultsHeading.innerHTML = '<h2>Arama Sonuçları</h2><p>"' + searchInput.value.trim() + '" için bulunan haberler</p>';
        searchResults.appendChild(resultsHeading);

        const resultsGrid = document.createElement('div');
        resultsGrid.className = 'search-results-grid';

        matches.forEach(card => {
          const clone = card.cloneNode(true);
          clone.style.display = 'flex';
          resultsGrid.appendChild(clone);
        });

        searchResults.appendChild(resultsGrid);
      } else {
        searchResults.innerHTML = '<div class="no-search-results">Aradığınız habere uygun sonuç bulunamadı.</div>';
      }
    }
  } else {
    cards.forEach(card => {
      card.style.display = 'flex';
    });

    if (featuredBanner) {
      featuredBanner.style.display = 'grid';
    }

    if (searchResults) {
      searchResults.style.display = 'none';
      searchResults.innerHTML = '';
    }
  }
}

if (themeToggle) {
  themeToggle.addEventListener('click', toggleTheme);
}

if (searchInput) {
  searchInput.addEventListener('input', filterNews);
}

const featuredSlides = Array.from(document.querySelectorAll('.featured-hero-slide'));
const featuredSideItems = Array.from(document.querySelectorAll('.featured-side-item'));
const featuredSidePrev = document.getElementById('featuredSidePrev');
const featuredSideNext = document.getElementById('featuredSideNext');
const featuredSidePageSize = 4;
let activeFeaturedIndex = 0;
let featuredSideOffset = 0;

function renderFeaturedSideItems() {
  if (featuredSideItems.length === 0) return;

  const availableItems = featuredSideItems.filter(item => {
    return Number(item.dataset.featuredIndex) !== activeFeaturedIndex;
  });

  if (availableItems.length === 0) return;

  if (featuredSideOffset >= availableItems.length) {
    featuredSideOffset = 0;
  }

  featuredSideItems.forEach(item => {
    item.classList.remove('is-visible');
  });

  const visibleCount = Math.min(featuredSidePageSize, availableItems.length);
  for (let i = 0; i < visibleCount; i += 1) {
    const item = availableItems[(featuredSideOffset + i) % availableItems.length];
    item.classList.add('is-visible');
  }
}

function showFeatured(index) {
  if (featuredSlides.length === 0) return;
  activeFeaturedIndex = index % featuredSlides.length;

  featuredSlides.forEach(slide => {
    slide.classList.toggle('is-active', Number(slide.dataset.featuredIndex) === activeFeaturedIndex);
  });

  featuredSideItems.forEach(item => {
    item.classList.toggle('is-active', Number(item.dataset.featuredIndex) === activeFeaturedIndex);
  });

  renderFeaturedSideItems();
}

if (featuredSideNext) {
  featuredSideNext.addEventListener('click', () => {
    const availableItems = featuredSideItems.filter(item => {
      return Number(item.dataset.featuredIndex) !== activeFeaturedIndex;
    });
    if (availableItems.length === 0) return;

    featuredSideOffset = (featuredSideOffset + featuredSidePageSize) % availableItems.length;
    renderFeaturedSideItems();
  });
}

if (featuredSidePrev) {
  featuredSidePrev.addEventListener('click', () => {
    const availableItems = featuredSideItems.filter(item => {
      return Number(item.dataset.featuredIndex) !== activeFeaturedIndex;
    });
    if (availableItems.length === 0) return;

    featuredSideOffset = (featuredSideOffset - featuredSidePageSize + availableItems.length) % availableItems.length;
    renderFeaturedSideItems();
  });
}

showFeatured(0);

if (featuredSlides.length > 1) {
  setInterval(() => {
    showFeatured(activeFeaturedIndex + 1);
  }, 10000);
}

if (searchInput && searchInput.value) {
  filterNews();
}
