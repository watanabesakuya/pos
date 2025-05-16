
<?php
require_once 'config/config.php';

// すでにログインしている場合はホームページにリダイレクト
if (is_logged_in()) {
    header('Location: /');
    exit;
}

$pageTitle = 'ログイン';

// POST送信時の処理
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'ユーザー名とパスワードを入力してください';
    } else {
        require_once 'config/database.php';
        require_once 'models/User.php';
        
        $db = new Database();
        $conn = $db->getConnection();
        
        $user = new User($conn);
        $userData = $user->authenticate($username, $password);
        
        if ($userData) {
            // セッションにユーザー情報を保存
            $_SESSION['user_id'] = $userData['id'];
            $_SESSION['username'] = $userData['username'];
            $_SESSION['display_name'] = $userData['display_name'] ?? $userData['username'];
            $_SESSION['user_role'] = $userData['role'];
            
            // アクティビティログ記録
            log_activity('login', "ユーザー {$username} がログインしました。");
            
            // リダイレクト先の取得
            $redirect = $_SESSION['redirect_after_login'] ?? '/';
            unset($_SESSION['redirect_after_login']);
            
            // 成功メッセージとリダイレクト
            $_SESSION['success'] = 'ログインしました';
            header("Location: {$redirect}");
            exit;
        } else {
            $error = 'ユーザー名またはパスワードが正しくありません';
        }
    }
}

include 'includes/header.php';
?>

<div class="min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full p-6 bg-white rounded-lg shadow-lg">
        <div class="flex justify-center mb-8">
            <h1 class="text-2xl font-bold"><?= htmlspecialchars(get_setting('site_name', APP_NAME)) ?></h1>
        </div>
        
        <h2 class="text-2xl font-bold text-center mb-6">ログイン</h2>
        
        <?php if ($error): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                <p><?= htmlspecialchars($error) ?></p>
            </div>
        <?php endif; ?>
        
        <form action="/login.php" method="post">
            <div class="mb-4">
                <label for="username" class="block text-gray-700 font-bold mb-2">ユーザー名</label>
                <input type="text" id="username" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                       required autofocus>
            </div>
            
            <div class="mb-6">
                <label for="password" class="block text-gray-700 font-bold mb-2">パスワード</label>
                <input type="password" id="password" name="password"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                       required>
            </div>
            
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center">
                    <input type="checkbox" id="remember" name="remember" class="h-4 w-4 text-blue-600">
                    <label for="remember" class="ml-2 block text-gray-700">ログイン状態を保持</label>
                </div>
            </div>
            
            <button type="submit"
                    class="w-full bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                ログイン
            </button>
        </form>
        
        <div class="mt-8 text-center">
            <p class="text-sm text-gray-600">
                デフォルトユーザー情報:<br>
                ユーザー名: admin / パスワード: admin123
            </p>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>