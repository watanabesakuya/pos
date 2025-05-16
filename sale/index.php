<?php
require_once '../config/config.php';
require_login();

// 権限チェック - マネージャー以上のみアクセス可能
if (!has_permission('manager')) {
    $_SESSION['error'] = '売上管理へのアクセス権限がありません';
    header('Location: /');
    exit;
}

$pageTitle = '売上管理';

// 日付範囲のフィルタリング
$today = date('Y-m-d');
$startDate = $_GET['start_date'] ?? date('Y-m-01'); // 今月の1日がデフォルト
$endDate = $_GET['end_date'] ?? date('Y-m-t'); // 今月の末日がデフォルト

// データベース接続
require_once '../config/database.php';
require_once '../models/Sale.php';

$db = new Database();
$conn = $db->getConnection();

$sale = new Sale($conn);

// 売上データ取得
$sales = $sale->getByDateRange($startDate . ' 00:00:00', $endDate . ' 23:59:59');

// 集計データ取得
$dailySummary = $sale->getDailySummary($startDate . ' 00:00:00', $endDate . ' 23:59:59');

// 合計値を計算
$totalSales = 0;
$totalTax = 0;
$transactionCount = count($sales);

foreach ($sales as $s) {
    $totalSales += (float)$s['total_amount'];
    $totalTax += (float)$s['tax_amount'];
}

// 平均取引額
$averageTransaction = $transactionCount > 0 ? $totalSales / $transactionCount : 0;

include '../includes/header.php';
?>

<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">売上管理</h1>
        <div>
            <a href="/views/pos/index.php" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                <i class="fas fa-cash-register mr-1"></i> レジに戻る
            </a>
        </div>
    </div>
    
    <!-- 日付範囲フィルター -->
    <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
        <h2 class="text-lg font-bold mb-4">期間指定</h2>
        <form action="" method="get" class="flex flex-wrap items-end gap-4">
            <div>
                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">開始日</label>
                <input type="date" id="start_date" name="start_date" value="<?= htmlspecialchars($startDate) ?>"
                       class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">終了日</label>
                <input type="date" id="end_date" name="end_date" value="<?= htmlspecialchars($endDate) ?>"
                       class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    <i class="fas fa-search mr-1"></i> 検索
                </button>
            </div>
            <div>
                <button type="button" id="btn-export-csv" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                    <i class="fas fa-file-csv mr-1"></i> CSV出力
                </button>
            </div>
        </form>
    </div>
    
    <!-- 集計情報 -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-sm font-medium text-gray-500 mb-1">総売上</h3>
            <p class="text-2xl font-bold"><?= format_currency($totalSales) ?></p>
        </div>
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-sm font-medium text-gray-500 mb-1">消費税合計</h3>
            <p class="text-2xl font-bold"><?= format_currency($totalTax) ?></p>
        </div>
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-sm font-medium text-gray-500 mb-1">取引数</h3>
            <p class="text-2xl font-bold"><?= $transactionCount ?></p>
        </div>
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-sm font-medium text-gray-500 mb-1">平均取引額</h3>
            <p class="text-2xl font-bold"><?= format_currency($averageTransaction) ?></p>
        </div>
    </div>
    
    <!-- 日別集計グラフ -->
    <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
        <h2 class="text-lg font-bold mb-4">日別売上推移</h2>
        <div class="w-full h-64">
            <canvas id="daily-sales-chart"></canvas>
        </div>
    </div>
    
    <!-- 売上一覧 -->
    <div class="bg-white rounded-lg shadow-lg overflow-hidden mb-6">
        <div class="p-6 border-b">
            <h2 class="text-lg font-bold">売上一覧</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">日時</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">担当者</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">点数</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">支払方法</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">税抜金額</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">消費税</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">合計</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($sales)): ?>
                    <tr>
                        <td colspan="9" class="px-6 py-4 text-center text-gray-500">
                            この期間の売上データはありません
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($sales as $s): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap"><?= $s['id'] ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= format_date($s['sale_date'], 'Y/m/d H:i') ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($s['display_name'] ?? $s['username'] ?? '不明') ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= $s['items_count'] ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                $paymentMethods = [
                                    'cash' => '現金',
                                    'credit_card' => 'カード',
                                    'other' => 'その他'
                                ];
                                echo $paymentMethods[$s['payment_method']] ?? $s['payment_method'];
                                ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right"><?= format_currency($s['amount']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-right"><?= format_currency($s['tax_amount']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-right font-bold"><?= format_currency($s['total_amount']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <a href="/views/pos/receipt.php?id=<?= $s['id'] ?>" class="text-blue-500 hover:text-blue-700 mr-3">
                                    <i class="fas fa-receipt"></i>
                                </a>
                                <?php if (has_permission('admin')): ?>
                                <a href="#" class="text-red-500 hover:text-red-700" 
                                   onclick="return confirm('この売上を取り消しますか？')">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
// ChartJSの追加
$extraScripts = <<<EOT
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 日別売上グラフ
    const dailySalesCtx = document.getElementById('daily-sales-chart').getContext('2d');
    
    // データの準備
    const dates = [];
    const sales = [];
    
    // PHP配列からJavaScriptに変換
    const dailySummaryData = JSON.parse('{$dailySummaryJSON}');
    
    dailySummaryData.forEach(day => {
        // 日付を「月/日」形式に変換
        const date = new Date(day.date);
        const formattedDate = `\${date.getMonth() + 1}/\${date.getDate()}`;
        
        dates.push(formattedDate);
        sales.push(parseFloat(day.total_sales));
    });
    
    const dailySalesChart = new Chart(dailySalesCtx, {
        type: 'line',
        data: {
            labels: dates,
            datasets: [{
                label: '日別売上',
                data: sales,
                backgroundColor: 'rgba(59, 130, 246, 0.2)',
                borderColor: 'rgba(59, 130, 246, 1)',
                borderWidth: 2,
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '¥' + value.toLocaleString();
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return '売上: ¥' + context.raw.toLocaleString();
                        }
                    }
                }
            }
        }
    });
    
    // CSV出力ボタン
    document.getElementById('btn-export-csv').addEventListener('click', function() {
        window.location.href = `/sales/export_csv.php?start_date=${encodeURIComponent('$startDate')}&end_date=${encodeURIComponent('$endDate')}`;
    });
});
</script>
EOT;

// 日別売上データをJSON形式に変換
$dailySummaryJSON = json_encode($dailySummary);

include '../includes/footer.php';
?>