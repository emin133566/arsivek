<?php
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['user'] ?? '');
    $pass = trim($_POST['pass'] ?? '');
    if ($user === 'admin' && $pass === 'admin') {
        $_SESSION['admin_authenticated'] = true;
        rememberAdminAuth();
        header('Location: index.php');
        exit;
    }
    $error = 'Kullanıcı adı veya şifre hatalı.';
}
?>
<!doctype html>
<html lang="tr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin Girişi</title>
  <link rel="stylesheet" href="../styles.css">
</head>
<body class="theme-light">
  <main class="container layout">
    <section class="news-section">
      <div class="section-heading">
        <h2>Admin Girişi</h2>
        <?php if (!empty($error)): ?>
          <p class="error-message"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
      </div>
      <form method="post" class="news-form">
        <label>
          Kullanıcı Adı
          <input type="text" name="user" required>
        </label>
        <label>
          Şifre
          <input type="password" name="pass" required>
        </label>
        <button type="submit" class="featured-action">Giriş Yap</button>
      </form>
    </section>
  </main>
</body>
</html>
