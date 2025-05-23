<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// エラー表示設定
ini_set('display_errors', 1);
error_reporting(E_ALL);

// POSTリクエストのみ受け付け
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'error' => 'POSTリクエストのみ対応しています'
    ]);
    exit;
}

// DB接続情報
$host = 'localhost';
$dbname = 'register_db';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // POSTデータの取得
    $janCode = $_POST['jan_code'] ?? '';
    $productName = $_POST['product_name'] ?? '';
    $price = $_POST['price'] ?? 0;

    // バリデーション
    if (empty($janCode)) {
        echo json_encode([
            'success' => false,
            'error' => 'JANコードが指定されていません'
        ]);
        exit;
    }

    if (empty($productName)) {
        echo json_encode([
            'success' => false,
            'error' => '商品名が指定されていません'
        ]);
        exit;
    }

    $price = (int)$price;
    if ($price <= 0) {
        echo json_encode([
            'success' => false,
            'error' => '正しい価格を指定してください'
        ]);
        exit;
    }

    // 既存商品の確認
    $checkStmt = $pdo->prepare("SELECT id FROM products WHERE jan_code = ?");
    $checkStmt->execute([$janCode]);
    $existingProduct = $checkStmt->fetch();

    if ($existingProduct) {
        // 既存商品の更新
        $updateStmt = $pdo->prepare("
            UPDATE products 
            SET product_name = ?, price = ?, is_active = TRUE, updated_at = CURRENT_TIMESTAMP 
            WHERE jan_code = ?
        ");
        $updateStmt->execute([$productName, $price, $janCode]);
        
        echo json_encode([
            'success' => true,
            'action' => 'updated',
            'message' => '商品情報を更新しました'
        ]);
    } else {
        // 新規商品の登録
        $insertStmt = $pdo->prepare("
            INSERT INTO products (jan_code, product_name, price) 
            VALUES (?, ?, ?)
        ");
        $insertStmt->execute([$janCode, $productName, $price]);
        
        echo json_encode([
            'success' => true,
            'action' => 'inserted',
            'message' => '新しい商品を登録しました'
        ]);
    }

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'データベースエラー: ' . $e->getMessage()
    ]);
}