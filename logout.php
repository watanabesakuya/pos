
<?php
require_once 'config/config.php';

// ログインしていない場合はログインページにリダイレクト
if (!is_logged_in()) {
    header('Location: /login.php');
    exit;
}

// アクティビティログ記録
log_activity('logout', "ユーザー {$_SESSION['username']} がログアウトしました。");

// ログアウト処理
logout();

// リダイレクト
$_SESSION['success'] = 'ログアウトしました';
header('Location: /login.php');
exit;