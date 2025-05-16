
<?php
require_once '../config/config.php';
require_login();

$pageTitle = 'ユーザー編集';

// データベース接続
require_once '../config/database.php';
require_once '../models/User.php';

$db = new Database();
$conn = $db->getConnection();

$user = new User($conn);

// ユーザーIDの取得
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$user_id) {
    $_SESSION['error'] = 'ユーザーIDが指定されていません';
    header('Location: /users/index.php');
    exit;
}

// 管理者権限チェック - 自分以外のユーザーを編集するには管理者権限が必要
if ($user_id != $_SESSION['user_id'] && !has_permission('admin')) {
    $_SESSION['error'] = '他のユーザーを編集する権限がありません';
    header('Location: /');
    exit;
}

// ユーザーデータの取得
$userData = $user->getById($user_id);

if (!$userData) {
    $_SESSION['error'] = '指定されたユーザーが見つかりません';
    header('Location: /users/index.php');
    exit;
}

// ユーザー更新処理
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $updatedData = [];
    
    // 表示名の更新
    if (isset($_POST['display_name']) && $_POST['display_name'] !== $userData['display_name']) {
        $updatedData['display_name'] = $_POST['display_name'];
    }
    
    // 権限の更新（管理者のみ可能）
    if (isset($_POST['role']) && has_permission('admin') && $_POST['role'] !== $userData['role']) {
        // 自分自身の権限は変更できない
        if ($user_id == $_SESSION['user_id']) {
            $message = '自分自身の権限は変更できません';
        } else {
            $validRoles = ['admin', 'manager', 'cashier'];
            if (in_array($_POST['role'], $validRoles)) {
                $updatedData['role'] = $_POST['role'];
            } else {
                $message = '無効な権限です';
            }
        }
    }
    
    // パスワードの更新
    if (!empty($_POST['new_password'])) {
        // 現在のパスワード確認（自分自身の場合のみ）
        if ($user_id == $_SESSION['user_id']) {
            if (empty($_POST['current_password'])) {
                $message = '現在のパスワードを入力してください';
            } else {
                // 現在のパスワードの検証
                $currentUser = $user->authenticate($userData['username'], $_POST['current_password']);
                if (!$currentUser) {
                    $message = '現在のパスワードが正しくありません';
                }
            }
        }
        
        // 新しいパスワードの検証
        if (empty($message)) {
            if (strlen($_POST['new_password']) < 6) {
                $message = '新しいパスワードは6文字以上で入力してください';
            } elseif ($_POST['new_password'] !== $_POST['confirm_password']) {
                $message = '新しいパスワードと確認用パスワードが一致しません';
            } else {
                $updatedData['password'] = $_POST['new_password'];
            }
        }
    }
    
    // 更新処理
    if (empty($message) && !empty($updatedData)) {
        $updated = $user->update($user_id, $updatedData);
        
        if ($updated) {
            // アクティビティログ記録
            log_activity('update_user', "ユーザーID: {$user_id} ({$userData['username']}) を更新しました");
            
            $_SESSION['success'] = 'ユーザー情報を更新しました';
            
            // 自分自身の表示名を更新した場合はセッションも更新
            if ($user_id == $_SESSION['user_id'] && isset($updatedData['display_name'])) {
                $_SESSION['display_name'] = $updatedData['display_name'];
            }
            
            header('Location: ' . ($_GET['from'] === 'profile' ? '/users/profile.php' : '/users/index.php'));
            exit;
        } else {
            $message = 'ユーザーの更新に失敗しました';
        }
    } elseif (empty($updatedData) && empty($message)) {
        $message = '変更内容がありません';
    }
}

include '../includes/header.php';
?>

<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">ユーザー編集</h1>
        <div>
            <a href="<?= $_GET['from'] === 'profile' ? '/users/profile.php' : '/users/index.php' ?>" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                <i class="fas fa-arrow-left mr-1"></i> 戻る
            </a>
        </div>
    </div>
    
    <?php if ($message): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
            <p><?= htmlspecialchars($message) ?></p>
        </div>
    <?php endif; ?>
    
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="p-6 border-b">
            <h2 class="text-lg font-bold">ユーザーの編集: <?= htmlspecialchars($userData['username']) ?></h2>
        </div>
        <div class="p-6">
            <form action="/users/edit.php?id=<?= $user_id ?>&from=<?= $_GET['from'] ?? '' ?>" method="post">
                <div class="mb-4">
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-1">ユーザー名</label>
                    <input type="text" id="username" value="<?= htmlspecialchars($userData['username']) ?>" readonly
                           class="w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-md">
                    <p class="text-sm text-gray-500 mt-1">ユーザー名は変更できません</p>
                </div>
                <div class="mb-4">
                    <label for="display_name" class="block text-sm font-medium text-gray-700 mb-1">表示名</label>
                    <input type="text" id="display_name" name="display_name" value="<?= htmlspecialchars($userData['display_name']) ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <?php if (has_permission('admin') && $user_id != $_SESSION['user_id']): ?>
                <div class="mb-4">
                    <label for="role" class="block text-sm font-medium text-gray-700 mb-1">権限</label>
                    <select id="role" name="role"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="cashier" <?= $userData['role'] === 'cashier' ? 'selected' : '' ?>>レジ担当</option>
                        <option value="manager" <?= $userData['role'] === 'manager' ? 'selected' : '' ?>>管理者</option>
                        <option value="admin" <?= $userData['role'] === 'admin' ? 'selected' : '' ?>>上級管理者</option>
                    </select>
                </div>
                <?php endif; ?>
                
                <div class="my-6 border-t pt-6">
                    <h3 class="text-md font-bold mb-4">パスワード変更</h3>
                    
                    <?php if ($user_id == $_SESSION['user_id']): ?>
                    <div class="mb-4">
                        <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">現在のパスワード</label>
                        <input type="password" id="current_password" name="current_password"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <?php endif; ?>
                    
                    <div class="mb-4">
                        <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">新しいパスワード</label>
                        <input type="password" id="new_password" name="new_password"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <p class="text-sm text-gray-500 mt-1">6文字以上で入力してください</p>
                    </div>
                    
                    <div class="mb-4">
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">パスワード確認</label>
                        <input type="password" id="confirm_password" name="confirm_password"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                
                <div class="flex justify-end">
                    <a href="<?= $_GET['from'] === 'profile' ? '/users/profile.php' : '/users/index.php' ?>" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded mr-2">
                        キャンセル
                    </a>
                    <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        更新
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>