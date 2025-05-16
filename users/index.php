
<?php
require_once '../config/config.php';
require_login();

// 権限チェック - 管理者のみアクセス可能
if (!has_permission('admin')) {
    $_SESSION['error'] = 'ユーザー管理へのアクセス権限がありません';
    header('Location: /');
    exit;
}

$pageTitle = 'ユーザー管理';

// データベース接続
require_once '../config/database.php';
require_once '../models/User.php';

$db = new Database();
$conn = $db->getConnection();

$user = new User($conn);

// ユーザー一覧取得
$users = $user->getAll();

// ユーザー追加処理
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_user') {
        $userData = [
            'username' => $_POST['username'] ?? '',
            'password' => $_POST['password'] ?? '',
            'display_name' => $_POST['display_name'] ?? '',
            'role' => $_POST['role'] ?? 'cashier'
        ];
        
        if (empty($userData['username']) || empty($userData['password'])) {
            $message = 'ユーザー名とパスワードは必須です';
        } else {
            // 権限の検証
            $validRoles = ['admin', 'manager', 'cashier'];
            if (!in_array($userData['role'], $validRoles)) {
                $message = '無効な権限です';
            } else {
                // 表示名が空の場合はユーザー名を使用
                if (empty($userData['display_name'])) {
                    $userData['display_name'] = $userData['username'];
                }
                
                $newUserId = $user->create($userData);
                
                if ($newUserId) {
                    $_SESSION['success'] = 'ユーザーを追加しました';
                    header('Location: /users/index.php');
                    exit;
                } else {
                    $message = 'ユーザーの追加に失敗しました';
                }
            }
        }
    }
}

include '../includes/header.php';
?>

<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">ユーザー管理</h1>
        <div>
            <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                    data-modal="add-user-modal">
                <i class="fas fa-user-plus mr-1"></i> ユーザー追加
            </button>
        </div>
    </div>
    
    <?php if ($message): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
            <p><?= htmlspecialchars($message) ?></p>
        </div>
    <?php endif; ?>
    
    <!-- ユーザー一覧 -->
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="p-6 border-b">
            <h2 class="text-lg font-bold">ユーザー一覧</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ユーザー名</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">表示名</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">権限</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">作成日</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">最終ログイン</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                            ユーザーがありません
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($users as $u): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap"><?= $u['id'] ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($u['username']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($u['display_name']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                $roleNames = [
                                    'admin' => '上級管理者',
                                    'manager' => '管理者',
                                    'cashier' => 'レジ担当'
                                ];
                                echo $roleNames[$u['role']] ?? $u['role'];
                                ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= format_date($u['created_at'], 'Y/m/d') ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?= $u['last_login'] ? format_date($u['last_login'], 'Y/m/d H:i') : '未ログイン' ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <button class="text-blue-500 hover:text-blue-700 mr-3"
                                        data-user-id="<?= $u['id'] ?>"
                                        data-edit-user>
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php if ($u['id'] != $_SESSION['user_id']): // 自分自身は削除できない ?>
                                <button class="text-red-500 hover:text-red-700"
                                        data-user-id="<?= $u['id'] ?>"
                                        data-delete-user>
                                    <i class="fas fa-trash"></i>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- ユーザー追加モーダル -->
    <div id="add-user-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-lg max-w-lg w-full mx-4">
            <div class="p-4 border-b flex justify-between items-center">
                <h2 class="text-xl font-bold">ユーザー追加</h2>
                <button class="text-gray-500 hover:text-gray-700 focus:outline-none" data-close-modal>
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form action="/users/index.php" method="post">
                <input type="hidden" name="action" value="add_user">
                <div class="p-4">
                    <div class="mb-4">
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-1">ユーザー名 <span class="text-red-500">*</span></label>
                        <input type="text" id="username" name="username" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="mb-4">
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">パスワード <span class="text-red-500">*</span></label>
                        <input type="password" id="password" name="password" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="mb-4">
                        <label for="display_name" class="block text-sm font-medium text-gray-700 mb-1">表示名</label>
                        <input type="text" id="display_name" name="display_name"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <p class="text-sm text-gray-500 mt-1">空の場合はユーザー名が使用されます</p>
                    </div>
                    <div class="mb-4">
                        <label for="role" class="block text-sm font-medium text-gray-700 mb-1">権限 <span class="text-red-500">*</span></label>
                        <select id="role" name="role" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="cashier">レジ担当</option>
                            <option value="manager">管理者</option>
                            <option value="admin">上級管理者</option>
                        </select>
                    </div>
                </div>
                <div class="p-4 border-t flex justify-end">
                    <button type="button" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded mr-2" data-close-modal>
                        キャンセル
                    </button>
                    <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        ユーザーを追加
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$extraScripts = <<<EOT
<script>
document.addEventListener('DOMContentLoaded', function() {
    // モーダル関連
    const openModalButtons = document.querySelectorAll('[data-modal]');
    const closeModalButtons = document.querySelectorAll('[data-close-modal]');
    
    openModalButtons.forEach(button => {
        button.addEventListener('click', () => {
            const modalId = button.getAttribute('data-modal');
            const modal = document.getElementById(modalId);
            modal.classList.remove('hidden');
        });
    });
    
    closeModalButtons.forEach(button => {
        button.addEventListener('click', () => {
            const modal = button.closest('[id$="-modal"]');
            modal.classList.add('hidden');
        });
    });
    
    // ユーザー編集処理
    const editButtons = document.querySelectorAll('[data-edit-user]');
    editButtons.forEach(button => {
        button.addEventListener('click', () => {
            const userId = button.getAttribute('data-user-id');
            window.location.href = `/users/edit.php?id=\${userId}`;
        });
    });
    
    // ユーザー削除処理
    const deleteButtons = document.querySelectorAll('[data-delete-user]');
    deleteButtons.forEach(button => {
        button.addEventListener('click', () => {
            const userId = button.getAttribute('data-user-id');
            if (confirm('このユーザーを削除しますか？')) {
                window.location.href = `/users/delete.php?id=\${userId}`;
            }
        });
    });
});
</script>
EOT;

include '../includes/footer.php';
?>