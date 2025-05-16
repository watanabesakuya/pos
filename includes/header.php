<?php
// セッション開始（認証用）

// 設定ファイルの読み込み
require_once __DIR__ . '/../config/config.php';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?></title>
    <!-- TailwindCSSのCDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- 独自スタイル -->
    <style>
        .calculator-container {
            max-width: 400px;
        }
        .calculator-keys button {
            min-width: 60px;
            height: 60px;
            margin: 4px;
        }
        .display-container input {
            font-size: 1.5rem;
            height: 60px;
        }
    </style>
</head>
<body class="bg-gray-100">
    <header class="bg-blue-600 text-white p-4 shadow-md">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold"><?php echo APP_NAME; ?></h1>
            <nav>
                <ul class="flex space-x-4">
                    <li><a href="<?php echo BASE_URL; ?>" class="hover:underline">ホーム</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/views/sales/list.php" class="hover:underline">売上管理</a></li>
                </ul>
            </nav>
        </div>
    </header>
    <main class="container mx-auto p-4">