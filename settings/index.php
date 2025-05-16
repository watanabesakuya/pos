// ===== settings/index.php =====

<?php
require_once '../config/config.php';
require_login();

// 権限チェック - 管理者のみアクセス可能
if (!has_permission('admin')) {
    $_SESSION['error'] = 'システム設定へのアクセス権限がありません';
    header('Location: /');
    exit;
}

$pageTitle = 'システム設定';

// データベース接続
require_once '../config/database.php';
require_once '../models/Setting.php';

$db = new Database();
$conn = $db->getConnection();

$setting = new Setting($conn);

// 設定の保存処理
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $updatedSettings = [
        'site_name' => $_POST['site_name'] ?? '',
        'tax_rate' => $_POST['tax_rate'] ?? 10,
        'reduced_tax_rate' => $_POST['reduced_tax_rate'] ?? 8,
        'receipt_header' => $_POST['receipt_header'] ?? '',
        'receipt_footer' => $_POST['receipt_footer'] ?? '',
        'business_hours' => $_POST['business_hours'] ?? ''
    ];
    
    // 数値の検証
    if (!is_numeric($updatedSettings['tax_rate']) || $updatedSettings['tax_rate'] < 0 || $updatedSettings['tax_rate'] > 100) {
        $message = '標準税率は0〜100の間で指定してください';
    } else if (!is_numeric($updatedSettings['reduced_tax_rate']) || $updatedSettings['reduced_tax_rate'] < 0 || $updatedSettings['reduced_tax_rate'] > 100) {
        $message = '軽減税率は0〜100の間で指定してください';
    } else {
        $updated = $setting->updateBatch($updatedSettings);
        
        if ($updated) {
            $_SESSION['success'] = '設定を保存しました';
            header('Location: /settings/index.php');
            exit;
        } else {
            $message = '設定の保存に失敗しました';
        }
    }
}

// 現在の設定を取得
$settings = $setting->getAll();

include '../includes/header.php';
?>

<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">システム設定</h1>
    </div>
    
    <?php if ($message): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
            <p><?= htmlspecialchars($message) ?></p>
        </div>
    <?php endif; ?>
    
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="p-6 border-b">
            <h2 class="text-lg font-bold">基本設定</h2>
        </div>
        <div class="p-6">
            <form action="/settings/index.php" method="post">
                <!-- 基本情報 -->
                <div class="mb-6">
                    <h3 class="text-md font-bold mb-3 pb-2 border-b">システム情報</h3>
                    
                    <div class="mb-4">
                        <label for="site_name" class="block text-sm font-medium text-gray-700 mb-1">サイト名</label>
                        <input type="text" id="site_name" name="site_name" 
                               value="<?= htmlspecialchars($settings['site_name']['setting_value'] ?? 'YSEレジシステム') ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <p class="text-sm text-gray-500 mt-1">ブラウザのタイトルやヘッダーに表示されます</p>
                    </div>
                    
                    <div class="mb-4">
                        <label for="business_hours" class="block text-sm font-medium text-gray-700 mb-1">営業時間</label>
                        <input type="text" id="business_hours" name="business_hours"
                               value="<?= htmlspecialchars($settings['business_hours']['setting_value'] ?? '9:00-21:00') ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <p class="text-sm text-gray-500 mt-1">フッターに表示される営業時間</p>
                    </div>
                </div>
                
                <!-- 税率設定 -->
                <div class="mb-6">
                    <h3 class="text-md font-bold mb-3 pb-2 border-b">税率設定</h3>
                    
                    <div class="mb-4">
                        <label for="tax_rate" class="block text-sm font-medium text-gray-700 mb-1">標準税率 (%)</label>
                        <input type="number" id="tax_rate" name="tax_rate" 
                               value="<?= htmlspecialchars($settings['tax_rate']['setting_value'] ?? 10) ?>"
                               min="0" max="100" step="0.1"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div class="mb-4">
                        <label for="reduced_tax_rate" class="block text-sm font-medium text-gray-700 mb-1">軽減税率 (%)</label>
                        <input type="number" id="reduced_tax_rate" name="reduced_tax_rate"
                               value="<?= htmlspecialchars($settings['reduced_tax_rate']['setting_value'] ?? 8) ?>"
                               min="0" max="100" step="0.1"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                
                <!-- レシート設定 -->
                <div class="mb-6">
                    <h3 class="text-md font-bold mb-3 pb-2 border-b">レシート設定</h3>
                    
                    <div class="mb-4">
                        <label for="receipt_header" class="block text-sm font-medium text-gray-700 mb-1">レシートヘッダー</label>
                        <input type="text" id="receipt_header" name="receipt_header"
                               value="<?= htmlspecialchars($settings['receipt_header']['setting_value'] ?? 'YSEマート') ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <p class="text-sm text-gray-500 mt-1">レシート上部に表示される店舗名など</p>
                    </div>
                    
                    <div class="mb-4">
                        <label for="receipt_footer" class="block text-sm font-medium text-gray-700 mb-1">レシートフッター</label>
                        <input type="text" id="receipt_footer" name="receipt_footer"
                               value="<?= htmlspecialchars($settings['receipt_footer']['setting_value'] ?? 'ご利用ありがとうございました') ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <p class="text-sm text-gray-500 mt-1">レシート下部に表示されるメッセージ</p>
                    </div>
                </div>
                
                <div class="flex justify-end">
                    <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        設定を保存
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- システム情報 -->
    <div class="bg-white rounded-lg shadow-lg overflow-hidden mt-6">
        <div class="p-6 border-b">
            <h2 class="text-lg font-bold">システム情報</h2>
        </div>
        <div class="p-6">
            <table class="min-w-full">
                <tbody>
                    <tr>
                        <td class="py-2 font-medium">システム名</td>
                        <td class="py-2"><?= APP_NAME ?></td>
                    </tr>
                    <tr>
                        <td class="py-2 font-medium">バージョン</td>
                        <td class="py-2"><?= APP_VERSION ?></td>
                    </tr>
                    <tr>
                        <td class="py-2 font-medium">PHPバージョン</td>
                        <td class="py-2"><?= phpversion() ?></td>
                    </tr>
                    <tr>
                        <td class="py-2 font-medium">サーバー</td>
                        <td class="py-2"><?= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ?></td>
                    </tr>
                    <tr>
                        <td class="py-2 font-medium">データベース</td>
                        <td class="py-2">MySQL</td>
                    </tr>
                    <tr>
                        <td class="py-2 font-medium">現在時刻</td>
                        <td class="py-2"><?= date('Y-m-d H:i:s') ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- 活動ログ -->
    <div class="bg-white rounded-lg shadow-lg overflow-hidden mt-6">
        <div class="p-6 border-b">
            <h2 class="text-lg font-bold">最近の活動ログ</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">日時</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ユーザー</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">アクション</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">詳細</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IPアドレス</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php
                    // アクティビティログの取得
                    require_once '../models/ActivityLog.php';
                    $log = new ActivityLog($conn);
                    $logs = $log->getAll(10); // 最新10件
                    
                    if (empty($logs)):
                    ?>
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                            活動ログはありません
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $l): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap"><?= format_date($l['created_at'], 'Y/m/d H:i:s') ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($l['display_name'] ?? $l['username'] ?? '不明') ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                $actionLabels = [
                                    'login' => 'ログイン',
                                    'logout' => 'ログアウト',
                                    'create_sale' => '売上登録',
                                    'update_sale' => '売上更新',
                                    'create_product' => '商品登録',
                                    'update_product' => '商品更新',
                                    'delete_product' => '商品削除',
                                    'create_user' => 'ユーザー登録',
                                    'update_user' => 'ユーザー更新',
                                    'delete_user' => 'ユーザー削除',
                                    'update_settings' => '設定更新'
                                ];
                                echo $actionLabels[$l['action']] ?? $l['action'];
                                ?>
                            </td>
                            <td class="px-6 py-4"><?= htmlspecialchars($l['description']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($l['ip_address']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>