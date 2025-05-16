
<?php
require_once '../config/config.php';
require_login();

// 権限チェック - 管理者のみアクセス可能
if (!has_permission('admin')) {
    $_SESSION['error'] = 'ユーザー管理へのアクセス権限がありません';
    header('Location: /');
    exit;
}

// データベース接続
require_once '../config/database.php';
require_once '../models/User.php';

$db = new Database();
$conn = $db->getConnection();

$user = new User($conn);

// ユーザーIDの取得
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$user_id) {
    $_SESSION['error'] = 'ユーザーIDが指定されていません';
    header('Location: /users/index.php');
    exit;
}

// 自分自身は削除できない
if ($user_id == $_SESSION['user_id']) {
    $_SESSION['error'] = '自分自身は削除できません';
    header('Location: /users/index.php');
    exit;
}

// ユーザーデータの取得
$userData = $user->getById($user_id);

if (!$userData) {
    $_SESSION['error'] = '指定されたユーザーが見つかりません';
    header('Location: /users/index.php');
    exit;
}

// ユーザーを削除
$deleted = $user->delete($user_id);

if ($deleted) {
    // アクティビティログ記録
    log_activity('delete_user', "ユーザーID: {$user_id} ({$userData['username']}) を削除しました");
    
    $_SESSION['success'] = 'ユーザーを削除しました';
} else {
    $_SESSION['error'] = 'ユーザーの削除に失敗しました';
}

header('Location: /users/index.php');
exit;
