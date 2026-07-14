<?php
session_start();

if (empty($_SESSION['admin_authenticated']) && !empty($_COOKIE['admin_remember'])) {
    $_SESSION['admin_authenticated'] = true;
}

function requireAdminAuth() {
    if (empty($_SESSION['admin_authenticated'])) {
        header('Location: login.php');
        exit;
    }
}

function rememberAdminAuth() {
    setcookie('admin_remember', '1', [
        'expires' => time() + 60 * 60 * 24 * 30,
        'path' => '/admin',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function forgetAdminAuth() {
    setcookie('admin_remember', '', [
        'expires' => time() - 3600,
        'path' => '/admin',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}
