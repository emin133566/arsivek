<?php
require_once __DIR__ . '/auth.php';
requireAdminAuth();
require_once __DIR__ . '/../db.php';
$error = '';
$success = '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$article = getNewsById($id);
if (!$article) {
    header('Location: index.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $summary = trim($_POST['summary'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $image = trim($_POST['image'] ?? '');
    $video = trim($_POST['video'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $date = trim($_POST['date'] ?? date('Y-m-d'));
    $featured = isset($_POST['featured']) ? true : false;
    $yzHelp = isset($_POST['yz_help']) ? true : false;

    if ($title === '' || $summary === '' || $category === '') {
        $error = 'Lütfen başlık, özet ve kategori alanlarını doldurun.';
    } else {
        updateNews($id, [
            'title' => $title,
            'summary' => $summary,
            'content' => $content,
            'category' => $category,
            'image' => $image,
            'video' => $video,
            'hero' => $article['hero'] ?? 'hero-tech',
            'featured' => $featured,
            'yz_help' => $yzHelp,
            'date' => $date,
            'slug' => $slug,
        ]);
        $success = 'Haber başarıyla güncellendi.';
        $article = getNewsById($id);
    }
}
?>
<!doctype html>
<html lang="tr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Haber Düzenle</title>
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
          <h1>Haber Düzenle</h1>
        </div>
      </div>
      <div class="admin-actions">
        <a href="../index.php" class="theme-button">Ana Sayfa</a>
        <a href="index.php" class="theme-button">Yönetim</a>
        <a href="logout.php" class="theme-button">Çıkış</a>
      </div>
    </div>
  </header>

  <main class="container layout">
    <section class="news-section">
      <div class="section-heading">
        <h2><?= htmlspecialchars($article['title']) ?></h2>
        <?php if ($error): ?>
          <p class="error-message"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <?php if ($success): ?>
          <p class="success-message"><?= htmlspecialchars($success) ?></p>
        <?php endif; ?>
      </div>

      <form method="post" class="news-form">
        <label>
          Başlık
          <input type="text" name="title" value="<?= htmlspecialchars($article['title']) ?>" required>
        </label>
        <label>
          Özet
          <textarea name="summary" rows="3" required><?= htmlspecialchars($article['summary']) ?></textarea>
        </label>
        <label>
          İçerik
          <div class="content-toolbar" role="toolbar" aria-label="İçerik düzenleme araçları">
            <button type="button" class="toolbar-btn" data-tag="p">P</button>
            <button type="button" class="toolbar-btn" data-tag="strong">B</button>
            <button type="button" class="toolbar-btn" data-tag="h1">H1</button>
            <button type="button" class="toolbar-btn" data-tag="h2">H2</button>
            <button type="button" class="toolbar-btn" data-tag="h3">H3</button>
            <button type="button" class="toolbar-btn" data-tag="h4">H4</button>
            <button type="button" class="toolbar-btn" data-tag="h5">H5</button>
            <button type="button" class="toolbar-btn" data-action="image">IMG</button>
          </div>
          <textarea id="contentEditor" name="content" rows="8"><?= htmlspecialchars($article['content']) ?></textarea>
          <div id="wordCount" class="word-count">Kelime sayısı: 0</div>
          <small class="form-note">Metni seçip üstteki başlık düğmesine basarak başlık yapabilirsiniz. IMG ile URL'den görsel ekleyebilirsiniz.</small>
        </label>
        <label>
          Kategori
          <input type="text" name="category" value="<?= htmlspecialchars($article['category']) ?>" required>
        </label>
        <label>
          Görsel URL
          <input type="url" name="image" value="<?= htmlspecialchars($article['image']) ?>" placeholder="https://...">
        </label>
        <label>
          Video URL
          <input type="url" name="video" value="<?= htmlspecialchars($article['video']) ?>" placeholder="https://...">
        </label>
        <label>
          SEO URL
          <input type="text" name="slug" value="<?= htmlspecialchars($article['slug'] ?? '') ?>" placeholder="haber-baslik">
          <small class="form-note">Boş bırakırsanız başlıktan otomatik oluşturulur.</small>
        </label>
        <label>
          Tarih
          <input type="date" name="date" value="<?= htmlspecialchars($article['date']) ?>">
        </label>
        <label class="checkbox-label">
          <input type="checkbox" name="featured" <?= $article['featured'] ? 'checked' : '' ?>> Öne çıkan haber yap
        </label>
        <label class="checkbox-label">
          <input type="checkbox" name="yz_help" <?= !empty($article['yz_help']) ? 'checked' : '' ?>> YZ
        </label>
        <button type="submit" class="featured-action">Güncelle</button>
      </form>
    </section>
  </main>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const textarea = document.getElementById('contentEditor');
      const buttons = document.querySelectorAll('.toolbar-btn');
      const wordCount = document.getElementById('wordCount');

      if (!textarea) return;

      function updateWordCount() {
        const plainText = textarea.value.replace(/<[^>]*>/g, ' ').replace(/&nbsp;/gi, ' ');
        const matches = plainText.match(/[\p{L}\p{N}]+/gu) || [];
        if (wordCount) {
          wordCount.textContent = 'Kelime sayısı: ' + matches.length;
        }
      }

      function escapeAttribute(value) {
        return value
          .replace(/&/g, '&amp;')
          .replace(/"/g, '&quot;')
          .replace(/</g, '&lt;')
          .replace(/>/g, '&gt;');
      }

      buttons.forEach(button => {
        button.addEventListener('click', function () {
          const action = this.dataset.action;
          const tag = this.dataset.tag;
          const start = textarea.selectionStart;
          const end = textarea.selectionEnd;
          const selectedText = textarea.value.substring(start, end);

          if (action === 'image') {
            const imageUrl = prompt('Görsel URL adresini girin:');
            if (!imageUrl) return;
            const altText = selectedText.trim() || 'Haber görseli';
            const replacement = '<img src="' + escapeAttribute(imageUrl.trim()) + '" alt="' + escapeAttribute(altText) + '">';
            textarea.setRangeText(replacement, start, end, 'end');
            textarea.focus();
            updateWordCount();
            return;
          }

          const text = selectedText.trim() || 'Başlık metni';
          const replacement = '<' + tag + '>' + text + '</' + tag + '>';
          textarea.setRangeText(replacement, start, end, 'end');
          textarea.focus();
          updateWordCount();
        });
      });

      textarea.addEventListener('input', updateWordCount);
      updateWordCount();
    });
  </script>
</body>
</html>
