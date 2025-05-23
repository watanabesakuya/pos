<?php
$host = 'localhost';
$dbname = 'register_db';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // --- フィルター条件取得 ---
    $date = $_GET['date'] ?? '';
    $minAmount = $_GET['min_amount'] ?? '';
    $maxAmount = $_GET['max_amount'] ?? '';

    // --- クエリ構築 ---
    $sql = "SELECT * FROM sales WHERE 1";
    $params = [];

    if ($date !== '') {
        $sql .= " AND DATE(sales_at) = :date";
        $params[':date'] = $date;
    }
    if ($minAmount !== '') {
        $sql .= " AND amount >= :min_amount";
        $params[':min_amount'] = $minAmount;
    }
    if ($maxAmount !== '') {
        $sql .= " AND amount <= :max_amount";
        $params[':max_amount'] = $maxAmount;
    }

    $sql .= " ORDER BY id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- 統計情報の取得 ---
    $statsSQL = "SELECT 
        COUNT(*) as total_count,
        SUM(amount) as total_amount,
        AVG(amount) as avg_amount,
        MAX(amount) as max_amount,
        MIN(amount) as min_amount
        FROM sales WHERE 1";
    
    $statsStmt = $pdo->prepare($statsSQL . 
        (isset($params[':date']) ? " AND DATE(sales_at) = :date" : "") .
        (isset($params[':min_amount']) ? " AND amount >= :min_amount" : "") .
        (isset($params[':max_amount']) ? " AND amount <= :max_amount" : "")
    );
    $statsStmt->execute($params);
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "DBエラー: " . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>売上履歴 - YSEレジシステム</title>
    <link rel="stylesheet" href="css/style_sales.css">
</head>
<body>
    <!-- ヘッダーナビゲーション -->
    <header class="header">
        <div class="header-content">
            <h1 class="logo">YSE レジシステム</h1>
            <nav class="nav">
                <a href="index.php" class="nav-link">レジ</a>
                <a href="sales.php" class="nav-link active">売上履歴</a>
            </nav>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
            <div class="page-header">
                <h2>売上履歴</h2>
                <p class="page-description">売上データの確認と分析</p>
            </div>

            <!-- 統計情報カード -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?= number_format($stats['total_count'] ?? 0) ?></div>
                    <div class="stat-label">取引件数</div>
                </div>
                <div class="stat-card primary">
                    <div class="stat-value">¥<?= number_format($stats['total_amount'] ?? 0) ?></div>
                    <div class="stat-label">合計売上</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">¥<?= number_format($stats['avg_amount'] ?? 0) ?></div>
                    <div class="stat-label">平均単価</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">¥<?= number_format($stats['max_amount'] ?? 0) ?></div>
                    <div class="stat-label">最高額</div>
                </div>
            </div>

            <!-- 検索フィルター -->
            <div class="filter-card">
                <h3>🔍 データ絞り込み</h3>
                <form method="GET" class="filter-form">
                    <div class="filter-group">
                        <label for="date">日付</label>
                        <input type="date" name="date" id="date" value="<?= htmlspecialchars($date) ?>">
                    </div>
                    <div class="filter-group">
                        <label for="min_amount">最小金額</label>
                        <input type="number" name="min_amount" id="min_amount" value="<?= htmlspecialchars($minAmount) ?>" placeholder="¥1,000">
                    </div>
                    <div class="filter-group">
                        <label for="max_amount">最大金額</label>
                        <input type="number" name="max_amount" id="max_amount" value="<?= htmlspecialchars($maxAmount) ?>" placeholder="¥10,000">
                    </div>
                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary">検索</button>
                        <a href="sales.php" class="btn btn-secondary">リセット</a>
                    </div>
                </form>
            </div>

            <!-- 売上テーブル -->
            <div class="table-card">
                <div class="table-header">
                    <h3>取引一覧</h3>
                    <span class="result-count"><?= count($sales) ?> 件の結果</span>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>売上金額</th>
                                <th>日時</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($sales)): ?>
                                <?php foreach ($sales as $row): ?>
                                    <tr>
                                        <td><span class="id-badge">#<?= htmlspecialchars($row['id']) ?></span></td>
                                        <td class="amount">¥<?= number_format($row['amount']) ?></td>
                                        <td class="datetime"><?= date('m/d H:i', strtotime($row['sales_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="no-data">
                                        <div class="no-data-message">
                                            📊 該当する売上データがありません
                                            <p>条件を変更して再検索してください</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</body>
</html>