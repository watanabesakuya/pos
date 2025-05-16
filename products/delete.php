// ===== products/delete.php =====

<?php
require_once '../config/config.php';
require_login();

// 権限チェック - マネージャー以上のみアクセス可能
if (!has_permission('manager')) {
    $_SESSION['error'] = '商品管理へのアクセス権限がありません';
    header('Location: /');
    exit;
}

// データベース接続
require_once '../config/database.php';
require_once '../models/Product.php';

$db = new Database();
$conn = $db->getConnection();

$product = new Product($conn);

// 商品IDの取得
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$product_id) {
    $_SESSION['error'] = '商品IDが指定されていません';
    header('Location: /products/index.php');
    exit;
}

// 商品データの取得
$productData = $product->getById($product_id);

if (!$productData) {
    $_SESSION['error'] = '指定された商品が見つかりません';
    header('Location: /products/index.php');
    exit;
}

// 商品を論理削除（is_activeを0に設定）
$deleted = $product->softDelete($product_id);

if ($deleted) {
    // アクティビティログ記録
    log_activity('delete_product', "商品ID: {$product_id} ({$productData['name']}) を無効化しました");
    
    $_SESSION['success'] = '商品を無効化しました';
} else {
    $_SESSION['error'] = '商品の無効化に失敗しました';
}

header('Location: /products/index.php');
exit;