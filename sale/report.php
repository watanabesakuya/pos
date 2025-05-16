// ===== sales/report.php =====

<?php
require_once '../config/config.php';
require_login();

// 権限チェック - マネージャー以上のみアクセス可能
if (!has_permission('manager')) {
    $_SESSION['error'] = '売上管理へのアクセス権限がありません';
    header('Location: /');
    exit;
}

$pageTitle = '売上レポート';

// データベース接続
require_once '../config/database.php';
require_once '../models/Sale.php';

$db = new Database();
$conn = $db->getConnection();

$sale = new Sale($conn);

// レポートタイプ（日別/月別）
$reportType = $_GET['type'] ?? 'daily';

// 期間設定
$currentYear = date('Y');
$currentMonth = date('m');

if ($reportType === 'monthly') {
    // 月別レポートの場合
    $year = isset($_GET['year']) ? (int)$_GET['year'] : $currentYear;
    
    // 有効な年かチェック（過去5年から現在までの範囲）
    $minYear = $currentYear - 5;
    if ($year < $minYear || $year > $currentYear) {
        $year = $currentYear;
    }
    
    // 月別サマリーを取得
    $summary = $sale->getMonthlySummary($year);
} else {
    // 日別レポートの場合
    $year = isset($_GET['year']) ? (int)$_GET['year'] : $currentYear;
    $month = isset($_GET['month']) ? (int)$_GET['month'] : $currentMonth;
    
    // 有効な年月かチェック
    $minYear = $currentYear - 5;
    if ($year < $minYear || $year > $currentYear) {
        $year = $currentYear;
    }
    
    if ($month < 1 || $month > 12) {
        $month = $currentMonth;
    }
    
    // 月の日数を取得
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    
    // 日付範囲を設定
    $startDate = sprintf('%04d-%02d-01', $year, $month);
    $endDate = sprintf('%04d-%02d-%02d', $year, $month, $daysInMonth);
    
    // 日別サマリーを取得
    $summary = $sale->getDailySummary($startDate . ' 00:00:00', $endDate . ' 23:59:59');
}

// 合計を計算
$totalSales = 0;
$totalTax = 0;
$totalTransactions = 0;

foreach ($summary as $s) {
    $totalSales += (float)$s['total_sales'];
    $totalTax += (float)$s['total_tax'];
    $totalTransactions += (int)$s['transaction_count'];
}

// 平均取引額
$averageTransaction = $totalTransactions > 0 ? $totalSales / $totalTransactions : 0;

include '../includes/header.php';
?>

<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">売上レポート</h1>
        <div class="flex space-x-2">
            <a href="/sales/index.php" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                <i class="fas fa-arrow-left mr-1"></i> 売上管理に戻る
            </a>
            <button id="btn-export-csv" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                <i class="fas fa-file-csv mr-1"></i> CSV出力
            </button>
            <button id="btn-print" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                <i class="fas fa-print mr-1"></i> 印刷
            </button>
        </div>
    </div>
    
    <!-- レポートタイプ切り替え -->
    <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
        <div class="flex flex-wrap items-center gap-4">
            <div>
                <span class="mr-2">レポートタイプ:</span>
                <a href="?type=daily&year=<?= $year ?>&month=<?= $month ?>" class="<?= $reportType === 'daily' ? 'font-bold text-blue-500' : 'text-gray-500' ?>">日別</a>
                <span class="mx-1">|</span>
                <a href="?type=monthly&year=<?= $year ?>" class="<?= $reportType === 'monthly' ? 'font-bold text-blue-500' : 'text-gray-500' ?>">月別</a>
            </div>
            
            <?php if ($reportType === 'daily'): ?>
                <div class="flex items-center">
                    <label for="year-month" class="mr-2">年月:</label>
                    <input type="month" id="year-month" name="year-month" 
                           value="<?= sprintf('%04d-%02d', $year, $month) ?>"
                           class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            <?php else: ?>
                <div class="flex items-center">
                    <label for="year" class="mr-2">年:</label>
                    <select id="year" name="year" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <?php for ($y = $currentYear; $y >= $minYear; $y--): ?>
                            <option value="<?= $y ?>" <?= $y === $year ? 'selected' : '' ?>><?= $y ?>年</option>
                        <?php endfor; ?>
                    </select>
                </div>
            <?php endif; ?>
            
            <button id="btn-update-report" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                <i class="fas fa-sync-alt mr-1"></i> 更新
            </button>
        </div>
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
            <p class="text-2xl font-bold"><?= $totalTransactions ?></p>
        </div>
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-sm font-medium text-gray-500 mb-1">平均取引額</h3>
            <p class="text-2xl font-bold"><?= format_currency($averageTransaction) ?></p>
        </div>
    </div>
    
    <!-- グラフ -->
    <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
        <h2 class="text-lg font-bold mb-4">
            <?= $reportType === 'daily' ? sprintf('%04d年%02d月', $year, $month) : sprintf('%04d年', $year) ?>の売上推移
        </h2>
        <div class="w-full h-64">
            <canvas id="sales-chart"></canvas>
        </div>
    </div>
    
    <!-- 詳細データ -->
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="p-6 border-b">
            <h2 class="text-lg font-bold">詳細データ</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <?= $reportType === 'daily' ? '日付' : '月' ?>
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">取引数</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">税抜金額</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">消費税</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">合計</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">平均</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($summary)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                            この期間のデータはありません
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($summary as $s): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($reportType === 'daily'): ?>
                                    <?= format_date($s['date'], 'Y/m/d') ?> (<?= get_day_of_week($s['date']) ?>)
                                <?php else: ?>
                                    <?= $s['month'] ?>月
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right"><?= $s['transaction_count'] ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-right"><?= format_currency($s['total_amount']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-right"><?= format_currency($s['total_tax']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-right font-bold"><?= format_currency($s['total_sales']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <?= format_currency($s['transaction_count'] > 0 ? $s['total_sales'] / $s['transaction_count'] : 0) ?>
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
    // 売上グラフ
    const salesCtx = document.getElementById('sales-chart').getContext('2d');
    
    // データの準備
    const labels = [];
    const salesData = [];
    
    // PHP配列からJavaScriptに変換
    const summaryData = JSON.parse('{$summaryJSON}');
    
    summaryData.forEach(item => {
        if ({$reportType === 'daily' ? 'true' : 'false'}) {
            // 日付を「日」のみに変換
            const date = new Date(item.date);
            const formattedDate = `\${date.getDate()}日`;
            labels.push(formattedDate);
        } else {
            // 月表示
            labels.push(`\${item.month}月`);
        }
        
        salesData.push(parseFloat(item.total_sales));
    });
    
    const salesChart = new Chart(salesCtx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: '売上',
                data: salesData,
                backgroundColor: 'rgba(59, 130, 246, 0.2)',
                borderColor: 'rgba(59, 130, 246, 1)',
                borderWidth: 1
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
    
    // 年月選択の更新ボタン
    document.getElementById('btn-update-report').addEventListener('click', function() {
        if ({$reportType === 'daily' ? 'true' : 'false'}) {
            const yearMonth = document.getElementById('year-month').value;
            if (yearMonth) {
                const [year, month] = yearMonth.split('-');
                window.location.href = `?type=daily&year=\${year}&month=\${month}`;
            }
        } else {
            const year = document.getElementById('year').value;
            window.location.href = `?type=monthly&year=\${year}`;
        }
    });
    
    // 年選択の変更イベント
    if (!{$reportType === 'daily' ? 'true' : 'false'}) {
        document.getElementById('year').addEventListener('change', function() {
            document.getElementById('btn-update-report').click();
        });
    }
    
    // CSV出力ボタン
    document.getElementById('btn-export-csv').addEventListener('click', function() {
        if ({$reportType === 'daily' ? 'true' : 'false'}) {
            window.location.href = `/sales/export_csv.php?type=daily&year={$year}&month={$month}`;
        } else {
            window.location.href = `/sales/export_csv.php?type=monthly&year={$year}`;
        }
    });
    
    // 印刷ボタン
    document.getElementById('btn-print').addEventListener('click', function() {
        window.print();
    });
});
</script>
EOT;

// サマリーデータをJSON形式に変換
$summaryJSON = json_encode($summary);

include '../includes/footer.php';
?>