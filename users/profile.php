
<?php
require_once '../config/config.php';
require_login();

$pageTitle = 'プロフィール';

// データベース接続
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../models/ActivityLog.php';

$db = new Database();
$conn = $db->getConnection();

$user = new User($conn);
$log = new ActivityLog($conn);

// 現在のユーザー情報を取得
$userData = $user->getById($_SESSION['user_id']);

if (!$userData) {
    $_SESSION['error'] = 'ユーザー情報の取得に失敗しました';
    header('Location: /');
    exit;
}

// アクティビティログを取得
$userLogs = $log->getByUser($_SESSION['user_id'], 10);

include '../includes/header.php';
?>

<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">マイプロフィール</h1>
        <div>
            <a href="/users/edit.php?id=<?= $_SESSION['user_id'] ?>&from=profile" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                <i class="fas fa-edit mr-1"></i> 編集
            </a>
        </div>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- ユーザー情報 -->
        <div class="md:col-span-1">
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <div class="p-6 border-b">
                    <h2 class="text-lg font-bold">アカウント情報</h2>
                </div>
                <div class="p-6">
                    <div class="flex items-center justify-center mb-6">
                        <div class="w-24 h-24 rounded-full bg-blue-500 flex items-center justify-center text-white text-4xl">
                            <?= strtoupper(substr($userData['display_name'], 0, 1)) ?>
                        </div>
                    </div>
                    
                    <table class="w-full">
                        <tbody>
                            <tr>
                                <td class="py-2 font-medium">ユーザー名</td>
                                <td class="py-2"><?= htmlspecialchars($userData['username']) ?></td>
                            </tr>
                            <tr>
                                <td class="py-2 font-medium">表示名</td>
                                <td class="py-2"><?= htmlspecialchars($userData['display_name']) ?></td>
                            </tr>
                            <tr>
                                <td class="py-2 font-medium">権限</td>
                                <td class="py-2">
                                    <?php
                                    $roleNames = [
                                        'admin' => '上級管理者',
                                        'manager' => '管理者',
                                        'cashier' => 'レジ担当'
                                    ];
                                    echo $roleNames[$userData['role']] ?? $userData['role'];
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="py-2 font-medium">作成日</td>
                                <td class="py-2"><?= format_date($userData['created_at'], 'Y/m/d') ?></td>
                            </tr>
                            <tr>
                                <td class="py-2 font-medium">最終ログイン</td>
                                <td class="py-2"><?= $userData['last_login'] ? format_date($userData['last_login'], 'Y/m/d H:i') : '未ログイン' ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- アクティビティログ -->
        <div class="md:col-span-2">
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <div class="p-6 border-b">
                    <h2 class="text-lg font-bold">最近の活動</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">日時</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">アクション</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">詳細</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IPアドレス</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($userLogs)): ?>
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                                    活動ログはありません
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($userLogs as $l): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap"><?= format_date($l['created_at'], 'Y/m/d H:i:s') ?></td>
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
    </div>
</div>

<?php include '../includes/footer.php'; ?>