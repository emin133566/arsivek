<?php
require_once __DIR__ . '/auth.php';
requireAdminAuth();
require_once __DIR__ . '/../db.php';
$newsItems = getAllNews();
$success = '';
$totalViews = 0;
$totalPositiveVotes = 0;
$mostViewedItem = null;
$mostPositiveItem = null;
foreach ($newsItems as $item) {
    $totalViews += (int) ($item['views'] ?? 0);
    $totalPositiveVotes += (int) ($item['positive_votes'] ?? 0);
}
if (!empty($newsItems)) {
    $mostViewedItem = $newsItems[0];
    $mostPositiveItem = $newsItems[0];
    foreach ($newsItems as $item) {
        if ((int) ($item['views'] ?? 0) > (int) ($mostViewedItem['views'] ?? 0)) {
            $mostViewedItem = $item;
        }
        if ((int) ($item['positive_votes'] ?? 0) > (int) ($mostPositiveItem['positive_votes'] ?? 0)) {
            $mostPositiveItem = $item;
        }
    }
}
if (isset($_GET['created']) && (int) $_GET['created'] === 1) {
    $success = 'Haber paylaşıldı';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $deleteId = (int)$_POST['delete_id'];
    deleteNews($deleteId);
    $newsItems = getAllNews();
    $success = 'Haber başarıyla silindi.';
}
?>
<!doctype html>
<html lang="tr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Haber Yönetimi</title>
  <link rel="stylesheet" href="../styles.css">
</head>
<body class="theme-light">
  <header class="site-header">
    <div class="topbar container">
      <div class="brand">
        <a href="../index.php" class="brand-link" aria-label="Ana sayfaya dön">
          <span class="brand-mark">Da</span>
        </a>
        <div>
          <p class="eyebrow">ARŞİVEK Admin</p>
          <h1>Haber Yönetimi</h1>
        </div>
      </div>
      <div class="admin-actions">
        <a href="../index.php" class="theme-button">Ana Sayfa</a>
        <a href="create.php" class="featured-action">Yeni Haber</a>
        <a href="logout.php" class="theme-button">Çıkış</a>
      </div>
    </div>
  </header>

  <?php if ($success): ?>
    <div class="site-toast" role="status" aria-live="polite"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <main class="container layout">
    <div class="admin-page-shell">
      <section class="news-section">
        <div class="section-heading">
          <h2>Mevcut Haberler</h2>
          <?php if ($success): ?>
            <p class="success-message"><?= htmlspecialchars($success) ?></p>
          <?php endif; ?>
          <p class="success-message"><a href="create.php">Yeni haber ekle</a></p>
        </div>

        <div class="admin-toolbar">
          <label class="admin-search-box">
            <span class="sr-only">Haber ara</span>
            <input id="adminSearchInput" type="search" placeholder="Haber ara..." autocomplete="off">
          </label>
          <div class="admin-stats-bar">
            <div class="stat-box">
              <span>Toplam görüntüleme</span>
              <strong><?= htmlspecialchars($totalViews) ?></strong>
            </div>
            <div class="stat-box">
              <span>En çok görüntülenen haber</span>
              <strong><?= htmlspecialchars($mostViewedItem['title'] ?? 'Henüz haber yok') ?></strong>
            </div>
            <div class="stat-box">
              <span>Toplam haber sayısı</span>
              <strong><?= count($newsItems) ?></strong>
            </div>
            <div class="stat-box">
              <span>Toplam olumlu oy</span>
              <strong><?= htmlspecialchars($totalPositiveVotes) ?></strong>
            </div>
            <div class="stat-box">
              <span>En çok olumlu oy alan haber</span>
              <strong><?= htmlspecialchars($mostPositiveItem['title'] ?? 'Henüz oy yok') ?></strong>
            </div>
          </div>
        </div>

        <p id="adminNoResults" class="no-news-message" style="display: none;">Arama kriterlerinize uygun haber bulunamadı.</p>

        <div id="adminNewsGrid" class="news-grid">
          <?php foreach ($newsItems as $item): ?>
            <?php $wordCount = countWords($item['content'] ?: $item['summary'] ?? ''); ?>
            <article class="news-card admin-card" data-title="<?= htmlspecialchars($item['title']) ?>" data-summary="<?= htmlspecialchars($item['summary']) ?>" data-category="<?= htmlspecialchars($item['category']) ?>">
              <div class="card-hero"<?php if (!empty($item['image'])): ?> style="background-image: url('<?= htmlspecialchars($item['image']) ?>');"<?php endif; ?>></div>
              <div class="card-body">
                <p class="tag"><?= htmlspecialchars($item['category']) ?></p>
                <h3><?= htmlspecialchars($item['title']) ?></h3>
                <p><?= htmlspecialchars($item['summary']) ?></p>
                <p class="meta"><?= htmlspecialchars($item['date']) ?> · <?= htmlspecialchars($item['views']) ?> görüntüleme · <?= htmlspecialchars($wordCount) ?> kelime</p>
                <div class="manage-actions">
                  <a href="edit.php?id=<?= urlencode($item['id']) ?>" class="theme-button">Düzenle</a>
                  <form method="post" class="inline-form" onsubmit="return confirm('Bu haberi silmek istediğinizden emin misiniz?');">
                    <button type="submit" name="delete_id" value="<?= htmlspecialchars($item['id']) ?>" class="theme-button danger-button">Sil</button>
                  </form>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </section>
    </div>
  </main>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const toast = document.querySelector('.site-toast');
      if (toast) {
        setTimeout(function () {
          toast.remove();
        }, 10000);
      }

      const searchInput = document.getElementById('adminSearchInput');
      const cards = Array.from(document.querySelectorAll('.admin-card'));
      const noResults = document.getElementById('adminNoResults');

      function applyAdminSearch() {
        const query = (searchInput?.value || '').toLowerCase().trim();
        let visibleCount = 0;

        cards.forEach(function (card) {
          const haystack = [card.dataset.title, card.dataset.summary, card.dataset.category].join(' ').toLowerCase();
          const matches = haystack.includes(query);
          card.style.display = matches ? '' : 'none';
          if (matches) {
            visibleCount += 1;
          }
        });

        if (noResults) {
          noResults.style.display = visibleCount === 0 ? 'block' : 'none';
        }
      }

      if (searchInput) {
        searchInput.addEventListener('input', applyAdminSearch);
        applyAdminSearch();
      }
    });
  </script>
</body>
</html>
