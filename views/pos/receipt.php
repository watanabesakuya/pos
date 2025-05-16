
<?php
require_once '../../config/config.php';
// ログイン要求
require_login();

// 売上IDの取得
$sale_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$sale_id) {
    $_SESSION['error'] = '売上IDが指定されていません';
    header('Location: /sales/index.php');
    exit;
}

// 売上データの取得
require_once '../../config/database.php';
require_once '../../models/Sale.php';

$db = new Database();
$conn = $db->getConnection();

$sale = new Sale($conn);
$saleData = $sale->getById($sale_id);

if (!$saleData) {
    $_SESSION['error'] = '指定された売上データが見つかりません';
    header('Location: /sales/index.php');
    exit;
}

$pageTitle = '領収書 #' . $sale_id;
include '../../includes/header.php';

// レシートヘッダー・フッター
$receiptHeader = get_setting('receipt_header', 'YSEマート');
$receiptFooter = get_setting('receipt_footer', 'ご利用ありがとうございました');
?>

<div class="container mx-auto px-4 py-6">
    <div class="bg-white rounded-lg shadow-lg max-w-lg mx-auto p-6">
        <div class="text-center mb-4">
            <h1 class="text-2xl font-bold"><?= htmlspecialchars($receiptHeader) ?></h1>
            <p class="text-sm text-gray-500"><?= format_date($saleData['sale_date'], 'Y年n月j日 H:i') ?></p>
        </div>
        
        <div class="border-t border-b py-4 my-4">
            <table class="w-full">
                <thead>
                    <tr>
                        <th class="text-left pb-2">商品</th>
                        <th class="text-right pb-2">金額</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($saleData['items'] as $item): ?>
                    <tr>
                        <td class="py-1">
                            <div class="flex flex-col">
                                <span><?= htmlspecialchars($item['item_name']) ?></span>
                                <span class="text-sm text-gray-500">
                                    <?= format_currency($item['price']) ?> × <?= $item['quantity'] ?>
                                </span>
                            </div>
                        </td>
                        <td class="py-1 text-right">
                            <?= format_currency($item['subtotal']) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="mb-4">
            <div class="flex justify-between py-1">
                <span>小計:</span>
                <span><?= format_currency($saleData['amount']) ?></span>
            </div>
            <div class="flex justify-between py-1">
                <span>消費税:</span>
                <span><?= format_currency($saleData['tax_amount']) ?></span>
            </div>
            <div class="flex justify-between py-1 text-lg font-bold">
                <span>合計:</span>
                <span><?= format_currency($saleData['total_amount']) ?></span>
            </div>
            <div class="flex justify-between py-1">
                <span>支払い方法:</span>
                <span>
                    <?php
                    $paymentMethods = [
                        'cash' => '現金',
                        'credit_card' => 'クレジットカード',
                        'other' => 'その他'
                    ];
                    echo $paymentMethods[$saleData['payment_method']] ?? $saleData['payment_method'];
                    ?>
                </span>
            </div>
        </div>
        
        <div class="text-center mt-6">
            <p><?= htmlspecialchars($receiptFooter) ?></p>
            <p class="text-sm text-gray-500 mt-2">販売ID: <?= $sale_id ?></p>
        </div>
        
        <div class="mt-6 flex justify-center">
            <button class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 mr-2"
                    onclick="window.print()">
                <i class="fas fa-print mr-1"></i> 印刷
            </button>
            <a href="/sales/index.php" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">
                戻る
            </a>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>