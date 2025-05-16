<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Sale.php';
require_once __DIR__ . '/../../includes/functions.php';

// ヘッダーの読み込み
require_once __DIR__ . '/../../includes/header.php';

// データベース接続とモデルのインスタンス化
$database = new Database();
$db = $database->getConnection();
$sale = new Sale($db);

// 日付範囲の取得（未指定の場合は過去7日間）
$endDate = date('Y-m-d');
$startDate = date('Y-m-d', strtotime('-7 days'));

if (isset($_GET['start_date']) && !empty($_GET['start_date'])) {
    $startDate = $_GET['start_date'];
}

if (isset($_GET['end_date']) && !empty($_GET['end_date'])) {
    $endDate = $_GET['end_date'];
}

// 売上データの取得
$sales = $sale->getByDateRange($startDate . ' 00:00:00', $endDate . ' 23:59:59');

// 売上集計データの取得
$summary = $sale->getDailySummary($startDate . ' 00:00:00', $endDate . ' 23:59:59');

// 全体の集計
$totalSales = array_sum(array_column($sales, 'total_amount'));
$totalTransactions = count($sales);
$avgTransactionAmount = $totalTransactions > 0 ? $totalSales / $totalTransactions : 0;
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-6">売上管理</h1>

    <!-- 日付範囲選択フォーム -->
    <div class="bg-white p-4 rounded shadow mb-6">
        <h2 class="text-xl font-semibold mb-4">期間選択</h2>
        <form method="GET" class="flex flex-wrap items-center gap-4">
            <div>
                <label for="start_date" class="block text-sm font-medium text-gray-700">開始日</label>
                <input type="date" id="start_date" name="start_date" value="<?php echo $startDate; ?>" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm">
            </div>
            <div>
                <label for="end_date" class="block text-sm font-medium text-gray-700">終了日</label>
                <input type="date" id="end_date" name="end_date" value="<?php echo $endDate; ?>" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm">
            </div>
            <div class="mt-5">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">検索</button>
            </div>
        </form>
    </div>

    <!-- 売上サマリー -->
    <div class="bg-white p-4 rounded shadow mb-6">
        <h2 class="text-xl font-semibold mb-4">売上サマリー</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-blue-100 p-4 rounded">
                <p class="text-sm text-gray-600">合計売上</p>
                <p class="text-2xl font-bold"><?php echo formatCurrency($totalSales); ?></p>
            </div>
            <div class="bg-green-100 p-4 rounded">
                <p class="text-sm text-gray-600">取引数</p>
                <p class="text-2xl font-bold"><?php echo $totalTransactions; ?></p>
            </div>
            <div class="bg-purple-100 p-4 rounded">
                <p class="text-sm text-gray-600">平均取引額</p>
                <p class="text-2xl font-bold"><?php echo formatCurrency($avgTransactionAmount); ?></p>
            </div>
        </div>
    </div>

    <!-- 日別売上グラフ -->
    <div class="bg-white p-4 rounded shadow mb-6">
        <h2 class="text-xl font-semibold mb-4">日別売上</h2>
        <canvas id="salesChart" width="400" height="200"></canvas>
    </div>

    <!-- 売上一覧 -->
    <div class="bg-white p-4 rounded shadow">
        <h2 class="text-xl font-semibold mb-4">売上一覧</h2>
        <?php if (count($sales) > 0): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="py-2 px-4 border-b text-left">ID</th>
                            <th class="py-2 px-4 border-b text-left">日時</th>
                            <th class="py-2 px-4 border-b text-left">ユーザー</th>
                            <th class="py-2 px-4 border-b text-right">商品数</th>
                            <th class="py-2 px-4 border-b text-right">税抜金額</th>
                            <th class="py-2 px-4 border-b text-right">消費税</th>
                            <th class="py-2 px-4 border-b text-right">合計</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sales as $item): ?>
                            <tr>
                                <td class="py-2 px-4 border-b"><?php echo $item['id']; ?></td>
                                <td class="py-2 px-4 border-b"><?php echo date('Y-m-d H:i', strtotime($item['sale_date'])); ?></td>
                                <td class="py-2 px-4 border-b"><?php echo $item['username'] ?? 'Unknown'; ?></td>
                                <td class="py-2 px-4 border-b text-right"><?php echo $item['items_count']; ?></td>
                                <td class="py-2 px-4 border-b text-right"><?php echo formatCurrency($item['amount']); ?></td>
                                <td class="py-2 px-4 border-b text-right"><?php echo formatCurrency($item['tax_amount']); ?></td>
                                <td class="py-2 px-4 border-b text-right"><?php echo formatCurrency($item['total_amount']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-gray-500">該当する売上データはありません。</p>
        <?php endif; ?>
    </div>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 売上チャートの作成
        var ctx = document.getElementById('salesChart').getContext('2d');
        
        // サマリーデータの準備
        var labels = <?php echo json_encode(array_column($summary, 'date')); ?>;
        var salesData = <?php echo json_encode(array_column($summary, 'total_sales')); ?>;
        var transactionData = <?php echo json_encode(array_column($summary, 'transaction_count')); ?>;
        
        var salesChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: '売上合計',
                    data: salesData,
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1,
                    yAxisID: 'y-axis-1'
                }, {
                    label: '取引数',
                    data: transactionData,
                    type: 'line',
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 2,
                    fill: false,
                    yAxisID: 'y-axis-2'
                }]
            },
            options: {
                responsive: true,
                scales: {
                    'y-axis-1': {
                        type: 'linear',
                        position: 'left',
                        title: {
                            display: true,
                            text: '売上合計'
                        }
                    },
                    'y-axis-2': {
                        type: 'linear',
                        position: 'right',
                        title: {
                            display: true,
                            text: '取引数'
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                }
            }
        });
    });
</script>

<?php
// フッターの読み込み
require_once '../../includes/footer.php';
?>