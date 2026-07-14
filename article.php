<?php
require_once __DIR__ . '/db.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$slug = trim($_GET['slug'] ?? '');
$article = null;
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
if ($basePath === '' || $basePath === '.') {
    $basePath = '';
}
$assetBase = $basePath === '' ? '' : $basePath;
$wordCount = 0;
$voteMessage = '';
$voteCookieName = '';
$hasVoted = false;
$newsItems = getAllNews();
$mostViewedItem = null;
$mostPositiveItem = null;
$latestNewsItems = [];

foreach ($newsItems as $item) {
    if ($mostViewedItem === null || (int) ($item['views'] ?? 0) > (int) ($mostViewedItem['views'] ?? 0)) {
        $mostViewedItem = $item;
    }
    if ($mostPositiveItem === null || (int) ($item['positive_votes'] ?? 0) > (int) ($mostPositiveItem['positive_votes'] ?? 0)) {
        $mostPositiveItem = $item;
    }
}

if ($slug !== '') {
    $article = getNewsBySlug($slug);
}
if (!$article && $id > 0) {
    $article = getNewsById($id);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vote']) && $article) {
    $voteValue = $_POST['vote'] === 'negative' ? 'negative' : 'positive';
    $voteCookieName = 'hbr_vote_' . (int) $article['id'];
    if (isset($_COOKIE[$voteCookieName])) {
        $voteMessage = 'Bu habere zaten oy verdiniz.';
    } else {
        castNewsVote((int) $article['id'], $voteValue);
        setcookie($voteCookieName, $voteValue, time() + 60 * 60 * 24 * 365, '/');
        $voteMessage = 'Oyunuz kaydedildi.';
    }

    $redirectParams = ['id' => (int) $article['id']];
    if (!empty($article['slug'])) {
        $redirectParams['slug'] = $article['slug'];
    }
    $redirectParams['vote_status'] = isset($_COOKIE[$voteCookieName]) ? 'duplicate' : 'success';
    header('Location: ' . $assetBase . '/article.php?' . http_build_query($redirectParams));
    exit;
}

if ($article) {
    incrementNewsViews((int) $article['id']);
    $article = getNewsById((int) $article['id']);
    $wordCount = countWords($article['content'] ?: $article['summary'] ?? '');
    $voteCookieName = 'hbr_vote_' . (int) $article['id'];
    $hasVoted = isset($_COOKIE[$voteCookieName]);
    $latestNewsItems = array_slice(array_values(array_filter($newsItems, function ($item) use ($article) {
        return (int) ($item['id'] ?? 0) !== (int) ($article['id'] ?? 0);
    })), 0, 60);
}

if (isset($_GET['vote_status']) && $_GET['vote_status'] === 'success') {
    $voteMessage = 'Oyunuz kaydedildi.';
} elseif (isset($_GET['vote_status']) && $_GET['vote_status'] === 'duplicate') {
    $voteMessage = 'Bu habere zaten oy verdiniz.';
}

$canonicalUrl = $article && !empty($article['slug'])
    ? 'article/' . rawurlencode($article['slug'])
    : 'article.php' . ($id ? '?id=' . $id : '');
?>
<!doctype html>
<html lang="tr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $article ? htmlspecialchars($article['title']) : 'Haber Bulunamadı' ?></title>
  <?php if ($article): ?>
    <meta name="description" content="<?= htmlspecialchars($article['summary'] ?? '') ?>">
    <link rel="canonical" href="<?= htmlspecialchars($canonicalUrl) ?>">
  <?php endif; ?>
  <link rel="icon" type="image/png" href="<?= $assetBase ?>/logo.png">
  <link rel="stylesheet" href="<?= $assetBase ?>/styles.css">
</head>
<body id="pageTop" class="theme-light article-detail-page">
  <header class="site-header">
    <div class="topbar container">
      <div class="brand">
        <a href="<?= $assetBase ?>/index.php" class="brand-link" aria-label="Ana sayfaya dön">
          <img src="<?= $assetBase ?>/logo.png" alt="ARSIVEK Logo" class="brand-logo">
        </a>
        <div>
          <p class="eyebrow">ARŞİVEK</p>
          <h1><?= $article ? htmlspecialchars($article['title']) : 'Haber Bulunamadı' ?></h1>
        </div>
      </div>
      <div class="tools">
        <a href="<?= $assetBase ?>/index.php" class="theme-button nav-link">Geri</a>
        <button id="themeToggle" class="theme-button" aria-label="Tema değiştir">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z"></path>
          </svg>
        </button>
      </div>
    </div>
  </header>

  <main class="container article-page">
    <?php if (!$article): ?>
      <section class="article-body">
        <p>Aradığınız haber bulunamadı. Lütfen ana sayfaya dönün.</p>
        <a href="<?= $assetBase ?>/index.php" class="featured-action">Ana Sayfaya Dön</a>
      </section>
    <?php else: ?>
      <section class="article-body">
        <?php if (!empty($article['image'])): ?>
          <div class="article-media article-media-small">
            <img src="<?= htmlspecialchars($article['image']) ?>" alt="<?= htmlspecialchars($article['title']) ?>">
          </div>
        <?php endif; ?>

        <div class="article-meta">
          <span><?= htmlspecialchars($article['category']) ?></span>
          <span><?= htmlspecialchars($article['date']) ?></span>
          <span><?= htmlspecialchars($article['views']) ?> görüntüleme</span>
          <span><?= htmlspecialchars($wordCount) ?> kelime</span>
          <span>(yazının sonunda Olumlu veya Olumsuz geri bildirim yapabilirsiniz)</span>
        </div>

        <div class="article-content article-content-panel">
          <?php if (!empty($article['content'])): ?>
            <?= $article['content'] ?>
          <?php else: ?>
            <p><?= nl2br(htmlspecialchars($article['summary'])) ?></p>
          <?php endif; ?>
        </div>

        <div class="vote-card">
          <p class="vote-title">Bu haberi faydalı buldunuz mu?</p>
          <div class="vote-actions">
            <form method="post" class="vote-form">
              <input type="hidden" name="article_id" value="<?= (int) $article['id'] ?>">
              <input type="hidden" name="vote" value="positive">
              <button type="submit" class="vote-button positive" <?= $hasVoted ? 'disabled' : '' ?>>Olumlu <span>(<?= (int) ($article['positive_votes'] ?? 0) ?>)</span></button>
            </form>
            <form method="post" class="vote-form">
              <input type="hidden" name="article_id" value="<?= (int) $article['id'] ?>">
              <input type="hidden" name="vote" value="negative">
              <button type="submit" class="vote-button negative" <?= $hasVoted ? 'disabled' : '' ?>>Olumsuz <span>(<?= (int) ($article['negative_votes'] ?? 0) ?>)</span></button>
            </form>
          </div>
          <?php if ($voteMessage): ?>
            <p class="vote-status"><?= htmlspecialchars($voteMessage) ?></p>
          <?php endif; ?>
        </div>

        <?php if (!empty($latestNewsItems)): ?>
          <section class="article-latest-news" aria-labelledby="articleLatestNewsTitle">
            <div class="article-latest-heading">
              <h2 id="articleLatestNewsTitle">Son haberler</h2>
            </div>
            <div class="article-latest-grid">
              <?php foreach ($latestNewsItems as $item): ?>
                <?php $isMostViewed = $mostViewedItem !== null && (int) ($mostViewedItem['views'] ?? 0) > 0 && (int) ($item['id'] ?? 0) === (int) ($mostViewedItem['id'] ?? 0); ?>
                <?php $isMostPositive = $mostPositiveItem !== null && (int) ($mostPositiveItem['positive_votes'] ?? 0) > 0 && (int) ($item['id'] ?? 0) === (int) ($mostPositiveItem['id'] ?? 0); ?>
                <article class="article-latest-card">
                  <a href="<?= $assetBase ?>/article/<?= rawurlencode((string) ($item['slug'] ?? $item['id'])) ?>" class="article-latest-link">
                    <div class="article-latest-image <?= empty($item['image']) ? htmlspecialchars($item['hero'] ?? '') : '' ?>"<?php if (!empty($item['image'])): ?> style="background-image: url('<?= htmlspecialchars($item['image']) ?>');"<?php endif; ?>>
                      <?php if ($isMostPositive): ?>
                        <span class="media-badge badge-positive">OLUMLU</span>
                      <?php endif; ?>
                      <?php if ($isMostViewed): ?>
                        <span class="media-badge badge-popular">POPULER</span>
                      <?php endif; ?>
                    </div>
                    <div class="article-latest-body">
                      <p class="tag"><?= htmlspecialchars($item['category'] ?? '') ?></p>
                      <h3><?= htmlspecialchars($item['title'] ?? '') ?></h3>
                    </div>
                  </a>
                </article>
              <?php endforeach; ?>
            </div>
            <div class="article-latest-actions">
              <a href="<?= $assetBase ?>/index.php" class="article-action-button">Ana sayfaya dön</a>
              <a href="#pageTop" class="article-action-button article-action-button-muted">Başa dön</a>
            </div>
          </section>
        <?php endif; ?>

        <?php if (!empty($article['video'])): ?>
          <div class="article-media article-media-small">
            <iframe src="<?= htmlspecialchars($article['video']) ?>" title="<?= htmlspecialchars($article['title']) ?> video" frameborder="0" allowfullscreen></iframe>
          </div>
        <?php endif; ?>

        <?php if (!empty($article['yz_help'])): ?>
          <p class="yz-note">YZ yardımı ile yapılmıştır.</p>
        <?php endif; ?>
      </section>
    <?php endif; ?>
  </main>

  <script src="<?= $assetBase ?>/scripts.js"></script>
</body>
</html>
