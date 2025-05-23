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

    $sql .= " ORDER BY id ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "DBエラー: " . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>売上履歴</title>
    <link rel="stylesheet" href="css/style_sales.css">
</head>

<body>
    <div class="container">
        <h2>売上履歴</h2>

        <!-- 🔍 検索フォーム -->
        <form method="GET" class="filter-form">
            <div class="filter-group">
                <label for="date">日付で絞り込み</label>
                <input type="date" name="date" id="date" value="<?= htmlspecialchars($date) ?>">
            </div>
            <div class="filter-group">
                <label for="min_amount">金額（最小）</label>
                <input type="number" name="min_amount" id="min_amount" value="<?= htmlspecialchars($minAmount) ?>" placeholder="例：1000">
            </div>
            <div class="filter-group">
                <label for="max_amount">金額（最大）</label>
                <input type="number" name="max_amount" id="max_amount" value="<?= htmlspecialchars($maxAmount) ?>" placeholder="例：5000">
            </div>
            <div class="filter-buttons">
                <button type="submit">検索</button>
                <a href="sales.php" class="reset-button">リセット</a>
            </div>
        </form>

        <!-- 売上テーブル -->
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>売上高</th>
                    <th>売上日時</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($sales)): ?>
                    <?php foreach ($sales as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['id']) ?></td>
                            <td><?= number_format($row['amount']) ?> 円</td>
                            <td><?= htmlspecialchars($row['sales_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3">該当する売上データがありません。</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <a href="index.php" class="back-button">← 戻る</a>
    </div>
</body>

</html>