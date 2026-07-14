<?php
require_once __DIR__ . '/db.php';

function limitSummaryText($text, $limit = 150) {
  $text = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $text)));
  if ($text === '') {
    return '';
  }

  if (function_exists('mb_strlen') && function_exists('mb_substr')) {
    return mb_strlen($text, 'UTF-8') > $limit
      ? rtrim(mb_substr($text, 0, $limit, 'UTF-8')) . '...'
      : $text;
  }

  return strlen($text) > $limit ? rtrim(substr($text, 0, $limit)) . '...' : $text;
}

$newsItems = getAllNews();
$featuredItems = array_values(getFeaturedNews());
$totalViews = 0;
$mostViewedItem = null;
$mostPositiveItem = null;
foreach ($newsItems as $item) {
  $totalViews += (int) ($item['views'] ?? 0);
  if ($mostViewedItem === null || (int) ($item['views'] ?? 0) > (int) ($mostViewedItem['views'] ?? 0)) {
    $mostViewedItem = $item;
  }
  if ($mostPositiveItem === null || (int) ($item['positive_votes'] ?? 0) > (int) ($mostPositiveItem['positive_votes'] ?? 0)) {
    $mostPositiveItem = $item;
  }
}
?>
<!doctype html>
<html lang="tr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ARSIVEK</title>
  <link rel="icon" type="image/png" href="logo.png">
  <link rel="stylesheet" href="styles.css">
</head>
<body class="theme-light">
  <header class="site-header">
    <div class="topbar container">
      <div class="brand">
        <a href="index.php" class="brand-link" aria-label="Ana sayfaya dön">
          <img src="logo.png" alt="ARSIVEK Logo" class="brand-logo">
        </a>
        <div>
          <p class="eyebrow">ARŞİVEK</p>
          <h1>ARŞİVLER, MAKALELER</h1>
        </div>
      </div>

      <div class="tools">
        <div class="search-box">
          <button id="themeToggle" class="theme-button" aria-label="Tema değiştir">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z"></path>
            </svg>
          </button>
          <input id="searchInput" type="search" placeholder="Haber ara...">
        </div>
        
      </div>
    </div>
  </header>

  <section class="featured-banner container">
    <div class="featured-header">
      <div class="featured-copy">
        <p class="small-label">Öne Çıkan Makaleler</p>
        <h2>Haftanın makaleleri</h2>
      </div>
    </div>

    <?php if (!empty($featuredItems)): ?>
      <div class="featured-layout">
        <div class="featured-main">
          <?php foreach ($featuredItems as $index => $item): ?>
            <?php $isMostViewed = $mostViewedItem !== null && (int) ($item['id'] ?? 0) === (int) ($mostViewedItem['id'] ?? 0); ?>
            <?php $isMostPositive = $mostPositiveItem !== null && (int) ($item['id'] ?? 0) === (int) ($mostPositiveItem['id'] ?? 0); ?>
            <article class="featured-card featured-card-large featured-hero-slide <?= $index === 0 ? 'is-active' : '' ?>" data-featured-index="<?= (int) $index ?>">
              <a href="article/<?= rawurlencode((string) ($item['slug'] ?? $item['id'])) ?>" class="featured-link">
                <div class="featured-image <?= empty($item['image']) ? htmlspecialchars($item['hero']) : '' ?>"<?php if (!empty($item['image'])): ?> style="background-image: url('<?= htmlspecialchars($item['image']) ?>');"<?php endif; ?>>
                  <?php if ($isMostPositive): ?>
                    <span class="media-badge badge-positive">OLUMLU</span>
                  <?php endif; ?>
                  <?php if ($isMostViewed): ?>
                    <span class="media-badge badge-popular">POPÜLER</span>
                  <?php endif; ?>
                  <?php if (!empty($item['yz_help'])): ?>
                    <span class="media-badge badge-ai">YZ</span>
                  <?php endif; ?>
                </div>
                <div class="featured-card-body">
                  <p class="tag"><?= htmlspecialchars($item['category']) ?></p>
                  <h3><?= htmlspecialchars($item['title']) ?></h3>
                  <p><?= htmlspecialchars(limitSummaryText($item['summary'] ?? '')) ?></p>
                </div>
              </a>
            </article>
          <?php endforeach; ?>
        </div>

        <?php if (count($featuredItems) > 1): ?>
          <div class="featured-side-panel">
            <?php if (count($featuredItems) > 5): ?>
              <div class="featured-side-controls" aria-label="Öne çıkan haber bandı kontrolleri">
                <button type="button" id="featuredSidePrev" class="featured-side-button" aria-label="Önceki öne çıkan haberleri göster">
                  <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false">
                    <path d="M6 15l6-6 6 6" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"></path>
                  </svg>
                </button>
                <button type="button" id="featuredSideNext" class="featured-side-button" aria-label="Sonraki öne çıkan haberleri göster">
                  <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false">
                    <path d="M6 9l6 6 6-6" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"></path>
                  </svg>
                </button>
              </div>
            <?php endif; ?>
            <div class="featured-side-list">
              <?php foreach ($featuredItems as $index => $item): ?>
                <?php $isMostViewed = $mostViewedItem !== null && (int) ($item['id'] ?? 0) === (int) ($mostViewedItem['id'] ?? 0); ?>
                <?php $isMostPositive = $mostPositiveItem !== null && (int) ($item['id'] ?? 0) === (int) ($mostPositiveItem['id'] ?? 0); ?>
                <article class="featured-card featured-card-compact featured-side-item <?= $index === 0 ? 'is-active' : '' ?>" data-featured-index="<?= (int) $index ?>">
                  <a href="article/<?= rawurlencode((string) ($item['slug'] ?? $item['id'])) ?>" class="featured-link">
                    <div class="featured-image <?= empty($item['image']) ? htmlspecialchars($item['hero']) : '' ?>"<?php if (!empty($item['image'])): ?> style="background-image: url('<?= htmlspecialchars($item['image']) ?>');"<?php endif; ?>>
                      <?php if ($isMostPositive): ?>
                        <span class="media-badge badge-positive">OLUMLU</span>
                      <?php endif; ?>
                      <?php if ($isMostViewed): ?>
                        <span class="media-badge badge-popular">POPÜLER</span>
                      <?php endif; ?>
                      <?php if (!empty($item['yz_help'])): ?>
                        <span class="media-badge badge-ai">YZ</span>
                      <?php endif; ?>
                    </div>
                    <div class="featured-card-body">
                      <p class="tag"><?= htmlspecialchars($item['category']) ?></p>
                      <h3><?= htmlspecialchars($item['title']) ?></h3>
                    </div>
                  </a>
                </article>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <p class="no-news-message">Henüz öne çıkan haber yok. Admin panelinden öne çıkan bir haber seçebilirsiniz.</p>
    <?php endif; ?>
  </section>

  <div id="searchResults" class="search-results container" aria-live="polite"></div>

  <main class="container layout">
    <section class="news-section">
      <div class="section-heading">
        <h2>son makaleler</h2>
        <?php if (empty($newsItems)): ?>
          <p class="no-news-message">Henüz yayınlanmış bir haber yok. Admin panelinden yeni haber ekleyebilirsiniz.</p>
        <?php else: ?>
          <p></p>
        <?php endif; ?>
      </div>

      <div id="newsGrid" class="news-grid">
        <?php foreach ($newsItems as $index => $item): ?>
          <?php $isMostViewed = $mostViewedItem !== null && (int) ($item['id'] ?? 0) === (int) ($mostViewedItem['id'] ?? 0); ?>
          <?php $isMostPositive = $mostPositiveItem !== null && (int) ($item['id'] ?? 0) === (int) ($mostPositiveItem['id'] ?? 0); ?>
          <?php $summaryLimit = $index >= 6 ? 50 : 150; ?>
          <article class="news-card" data-title="<?= htmlspecialchars($item['title']) ?>" data-summary="<?= htmlspecialchars($item['summary']) ?>">
            <a href="article/<?= rawurlencode((string) ($item['slug'] ?? $item['id'])) ?>" class="news-link">
              <div class="card-hero <?= htmlspecialchars($item['hero']) ?>"<?php if (!empty($item['image'])): ?> style="background-image: url('<?= htmlspecialchars($item['image']) ?>');"<?php endif; ?>>
                <?php if ($isMostPositive): ?>
                  <span class="media-badge badge-positive">OLUMLU</span>
                <?php endif; ?>
                <?php if ($isMostViewed): ?>
                  <span class="media-badge badge-popular">POPÜLER</span>
                <?php endif; ?>
                <?php if (!empty($item['yz_help'])): ?>
                  <span class="media-badge badge-ai">YZ</span>
                <?php endif; ?>
              </div>
              <div class="card-body">
                <p class="tag"><?= htmlspecialchars($item['category']) ?></p>
                <h3><?= htmlspecialchars($item['title']) ?></h3>
                <p><?= htmlspecialchars(limitSummaryText($item['summary'] ?? '', $summaryLimit)) ?></p>
                <span class="read-more">Detayları oku</span>
              </div>
            </a>
          </article>
        <?php endforeach; ?>
      </div>
    </section>
  </main>

  <footer class="site-footer">
    <div class="container footer-inner">
      <p>2026 ARŞİVEK. YZ olarak işaretlenmiş yazılar YAPAY ZEKA tarafından yazılmıştır.</p>
      <p></p>
      <p><a href="admin/login.php" class="footer-admin-link">Admin </a></p>
    </div>
  </footer>

  <script src="scripts.js"></script>
</body>
</html>
