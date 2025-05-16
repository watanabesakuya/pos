<?php
// セッション開始 (認証システム用)
session_start();

// 共通ヘッダーの読み込み
require_once __DIR__ . '/includes/header.php';

// 共通関数の読み込み
require_once __DIR__ . '/includes/functions.php';

// データベース接続とモデルの読み込み (今日の売上データ取得用)
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/Sale.php';

// 本日の売上データを取得
$database = new Database();
$db = $database->getConnection();
$saleModel = new Sale($db);

$today = date('Y-m-d');
$todaySales = $saleModel->getByDateRange($today . ' 00:00:00', $today . ' 23:59:59');

// 売上集計
$totalSales = array_sum(array_column($todaySales, 'total_amount'));
$transactionCount = count($todaySales);
$avgTransaction = $transactionCount > 0 ? $totalSales / $transactionCount : 0;
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex flex-col md:flex-row gap-6">
        <!-- 左カラム：計算機UI -->
        <div class="w-full md:w-1/2 lg:w-2/5">
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-2xl font-bold mb-4">YSEレジシステム</h2>
                <?php include 'views/pos/calculator.php'; ?>
            </div>
        </div>
        
        <!-- 右カラム：売上情報 -->
        <div class="w-full md:w-1/2 lg:w-3/5">
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-2xl font-bold mb-4">本日の売上</h2>
                <div id="today-sales-container">
                    <div class="stats grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
                        <div class="stat bg-blue-100 p-4 rounded">
                            <div class="stat-title text-sm text-gray-600">総売上</div>
                            <div class="stat-value text-xl font-bold" id="total-sales"><?php echo formatCurrency($totalSales); ?></div>
                        </div>
                        <div class="stat bg-green-100 p-4 rounded">
                            <div class="stat-title text-sm text-gray-600">取引数</div>
                            <div class="stat-value text-xl font-bold" id="transaction-count"><?php echo $transactionCount; ?></div>
                        </div>
                        <div class="stat bg-purple-100 p-4 rounded">
                            <div class="stat-title text-sm text-gray-600">平均取引額</div>
                            <div class="stat-value text-xl font-bold" id="average-transaction"><?php echo formatCurrency($avgTransaction); ?></div>
                        </div>
                    </div>
                    
                    <h3 class="text-lg font-bold mb-2">最近の取引</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white">
                            <thead>
                                <tr>
                                    <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold text-gray-600">ID</th>
                                    <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold text-gray-600">時間</th>
                                    <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold text-gray-600">金額</th>
                                    <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold text-gray-600"></th>
                                </tr>
                            </thead>
                            <tbody id="recent-sales">
                                <?php if (count($todaySales) > 0): ?>
                                    <?php foreach (array_slice($todaySales, 0, 10) as $sale): ?>
                                        <tr>
                                            <td class="py-2 px-4 border-b border-gray-200"><?php echo $sale['id']; ?></td>
                                            <td class="py-2 px-4 border-b border-gray-200"><?php echo date('H:i', strtotime($sale['sale_date'])); ?></td>
                                            <td class="py-2 px-4 border-b border-gray-200"><?php echo formatCurrency($sale['total_amount']); ?></td>
                                            <td class="py-2 px-4 border-b border-gray-200">
                                                <button class="view-details bg-blue-500 hover:bg-blue-700 text-white text-xs py-1 px-2 rounded" data-id="<?php echo $sale['id']; ?>">詳細</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="py-4 px-4 text-center text-gray-500">本日の取引はまだありません</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 売上データ取得用スクリプト -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    // 今日の売上データを取得
    fetchTodaySales();
    
    // 30秒ごとに更新
    setInterval(fetchTodaySales, 30000);
});

function fetchTodaySales() {
    fetch('/pos/controllers/SaleController.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            action: 'getSales'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateSalesDisplay(data.sales);
        } else {
            console.error('Error fetching sales:', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

function updateSalesDisplay(sales) {
    // 今日の日付のデータのみをフィルタリング
    const today = new Date().toISOString().split('T')[0];
    const todaySales = sales.filter(sale => sale.sale_date.startsWith(today));
    
    // 総売上を計算
    const totalSales = todaySales.reduce((sum, sale) => sum + parseFloat(sale.total_amount), 0);
    document.getElementById('total-sales').textContent = `¥${totalSales.toLocaleString()}`;
    
    // 取引数
    document.getElementById('transaction-count').textContent = todaySales.length;
    
    // 平均取引額
    const avgTransaction = todaySales.length > 0 ? totalSales / todaySales.length : 0;
    document.getElementById('average-transaction').textContent = `¥${avgTransaction.toLocaleString()}`;
    
    // 最近の取引テーブルを更新
    const recentSalesElement = document.getElementById('recent-sales');
    recentSalesElement.innerHTML = '';
    
    // 最新10件を表示
    const recentSales = todaySales.slice(0, 10);
    
    recentSales.forEach(sale => {
        const row = document.createElement('tr');
        
        // 日時のフォーマット
        const saleDate = new Date(sale.sale_date);
        const formattedTime = saleDate.toLocaleTimeString('ja-JP', {
            hour: '2-digit',
            minute: '2-digit'
        });
        
        row.innerHTML = `
            <td class="py-2 px-4 border-b border-gray-200">${sale.id}</td>
            <td class="py-2 px-4 border-b border-gray-200">${formattedTime}</td>
            <td class="py-2 px-4 border-b border-gray-200">¥${parseFloat(sale.total_amount).toLocaleString()}</td>
            <td class="py-2 px-4 border-b border-gray-200">
                <button class="view-details bg-blue-500 hover:bg-blue-700 text-white text-xs py-1 px-2 rounded" data-id="${sale.id}">詳細</button>
            </td>
        `;
        
        recentSalesElement.appendChild(row);
    });
    
    // 詳細ボタンにイベントリスナーを追加
    document.querySelectorAll('.view-details').forEach(button => {
        button.addEventListener('click', () => {
            const saleId = button.getAttribute('data-id');
            // 詳細表示処理（モーダルウィンドウなど）
            alert(`売上ID: ${saleId} の詳細表示機能は実装予定です`);
        });
    });
}
</script>

<?php
// 共通フッターの読み込み
require_once __DIR__ . '/includes/footer.php';
?>