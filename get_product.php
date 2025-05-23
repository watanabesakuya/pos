<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// エラー表示設定
ini_set('display_errors', 1);
error_reporting(E_ALL);

// DB接続情報
$host = 'localhost';
$dbname = 'register_db';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // JANコードの取得
    $janCode = $_GET['jan_code'] ?? '';
    
    if (empty($janCode)) {
        echo json_encode([
            'success' => false,
            'error' => 'JANコードが指定されていません'
        ]);
        exit;
    }

    // 商品情報の検索
    $stmt = $pdo->prepare("
        SELECT id, jan_code, product_name, price, is_active 
        FROM products 
        WHERE jan_code = ? AND is_active = TRUE
    ");
    $stmt->execute([$janCode]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($product) {
        // 商品が見つかった場合
        echo json_encode([
            'success' => true,
            'found' => true,
            'product' => [
                'id' => $product['id'],
                'jan_code' => $product['jan_code'],
                'product_name' => $product['product_name'],
                'price' => (int)$product['price']
            ]
        ]);
    } else {
        // 商品が見つからない場合
        echo json_encode([
            'success' => true,
            'found' => false,
            'jan_code' => $janCode
        ]);
    }

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'データベースエラー: ' . $e->getMessage()
    ]);
}