<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

//  var_dump($_POST); // ← 追加！
//  exit;


$amount = isset($_POST['amount']) ? (int)$_POST['amount'] : 0;

if ($amount <= 0) {
  echo "無効な金額です。";
  exit;
}

// DB接続情報
$host = 'localhost';
$dbname = 'register_db';
$user = 'root'; // 環境により変更
$pass = '';     // MAMPなら 'root'

try {
  $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  $stmt = $pdo->prepare("INSERT INTO sales (sales_at, amount) VALUES (NOW(), :amount)");
  $stmt->bindValue(':amount', $amount, PDO::PARAM_INT);
  $stmt->execute();

  header("Location: sales.php");
  exit;
} catch (PDOException $e) {
  echo "DBエラー: " . $e->getMessage(); // ← ここがちゃんと出るか確認
  exit;
}

