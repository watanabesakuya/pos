// ===== controllers/UserController.php =====

<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/User.php';

class UserController {
    private $db;
    private $user;
    
    public function __construct() {
        $this->db = new Database();
        $this->user = new User($this->db->getConnection());
    }
    
    // 受信したリクエストを処理
    public function handleRequest() {
        header('Content-Type: application/json');
        
        // POSTリクエストのボディを取得
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        
        if (!$data) {
            echo json_encode(['success' => false, 'message' => 'Invalid request data']);
            return;
        }
        
        // アクションによって処理を振り分け
        switch ($data['action']) {
            case 'login':
                $this->login($data);
                break;
            case 'logout':
                $this->logout();
                break;
            case 'getUsers':
                $this->getUsers();
                break;
            case 'getUserById':
                $this->getUserById($data['id'] ?? 0);
                break;
            case 'createUser':
                $this->createUser($data);
                break;
            case 'updateUser':
                $this->updateUser($data['id'] ?? 0, $data);
                break;
            case 'deleteUser':
                $this->deleteUser($data['id'] ?? 0);
                break;
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    }
    
    // ログイン処理
    private function login($data) {
        try {
            $username = $data['username'] ?? '';
            $password = $data['password'] ?? '';
            
            if (empty($username) || empty($password)) {
                echo json_encode(['success' => false, 'message' => 'ユーザー名とパスワードを入力してください']);
                return;
            }
            
            $user = $this->user->authenticate($username, $password);
            
            if ($user) {
                // セッションにユーザー情報を保存
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['display_name'] = $user['display_name'];
                $_SESSION['user_role'] = $user['role'];
                
                // リダイレクト先の取得
                $redirect = $_SESSION['redirect_after_login'] ?? '/';
                unset($_SESSION['redirect_after_login']);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'ログインしました',
                    'user' => [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'display_name' => $user['display_name'],
                        'role' => $user['role']
                    ],
                    'redirect' => $redirect
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'ユーザー名またはパスワードが正しくありません']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    // ログアウト処理
    private function logout() {
        // セッションを破棄
        session_destroy();
        
        echo json_encode([
            'success' => true,
            'message' => 'ログアウトしました',
            'redirect' => '/login.php'
        ]);
    }
    
    // ユーザー一覧取得
    private function getUsers() {
        try {
            // 権限チェック
            if (!has_permission('admin')) {
                echo json_encode(['success' => false, 'message' => '権限がありません']);
                return;
            }
            
            $users = $this->user->getAll();
            echo json_encode(['success' => true, 'users' => $users]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    // 指定IDのユーザー取得
    private function getUserById($id) {
        try {
            // 権限チェック
            if (!has_permission('admin') && $_SESSION['user_id'] != $id) {
                echo json_encode(['success' => false, 'message' => '権限がありません']);
                return;
            }
            
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ユーザーIDが指定されていません']);
                return;
            }
            
            $user = $this->user->getById($id);
            
            if ($user) {
                echo json_encode(['success' => true, 'user' => $user]);
            } else {
                echo json_encode(['success' => false, 'message' => '指定されたユーザーが見つかりません']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    // ユーザー作成
    private function createUser($data) {
        try {
            // 権限チェック
            if (!has_permission('admin')) {
                echo json_encode(['success' => false, 'message' => '権限がありません']);
                return;
            }
            
            // 必須項目の検証
            if (empty($data['username']) || empty($data['password']) || empty($data['role'])) {
                echo json_encode(['success' => false, 'message' => 'ユーザー名、パスワード、権限は必須です']);
                return;
            }
            
            // 権限の検証
            $validRoles = ['admin', 'manager', 'cashier'];
            if (!in_array($data['role'], $validRoles)) {
                echo json_encode(['success' => false, 'message' => '無効な権限です']);
                return;
            }
            
            $userData = [
                'username' => $data['username'],
                'password' => $data['password'],
                'display_name' => $data['display_name'] ?? $data['username'],
                'role' => $data['role']
            ];
            
            $userId = $this->user->create($userData);
            
            if ($userId) {
                echo json_encode([
                    'success' => true,
                    'userId' => $userId,
                    'message' => 'ユーザーを作成しました'
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'ユーザーの作成に失敗しました']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    // ユーザー更新
    private function updateUser($id, $data) {
        try {
            // 権限チェック
            if (!has_permission('admin') && $_SESSION['user_id'] != $id) {
                echo json_encode(['success' => false, 'message' => '権限がありません']);
                return;
            }
            
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ユーザーIDが指定されていません']);
                return;
            }
            
            // 自分自身の権限は変更できない
            if ($_SESSION['user_id'] == $id && isset($data['role']) && $data['role'] != $_SESSION['user_role']) {
                echo json_encode(['success' => false, 'message' => '自分自身の権限は変更できません']);
                return;
            }
            
            // 権限の検証
            if (isset($data['role'])) {
                $validRoles = ['admin', 'manager', 'cashier'];
                if (!in_array($data['role'], $validRoles)) {
                    echo json_encode(['success' => false, 'message' => '無効な権限です']);
                    return;
                }
            }
            
            $userData = [];
            
            // 更新するフィールド
            if (isset($data['display_name'])) {
                $userData['display_name'] = $data['display_name'];
            }
            
            if (isset($data['role']) && has_permission('admin')) {
                $userData['role'] = $data['role'];
            }
            
            if (isset($data['password']) && !empty($data['password'])) {
                $userData['password'] = $data['password'];
            }
            
            if (empty($userData)) {
                echo json_encode(['success' => false, 'message' => '更新するデータがありません']);
                return;
            }
            
            $updated = $this->user->update($id, $userData);
            
            if ($updated) {
                echo json_encode([
                    'success' => true,
                    'message' => 'ユーザー情報を更新しました'
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => '更新に失敗しました']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    // ユーザー削除
    private function deleteUser($id) {
        try {
            // 権限チェック
            if (!has_permission('admin')) {
                echo json_encode(['success' => false, 'message' => '権限がありません']);
                return;
            }
            
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ユーザーIDが指定されていません']);
                return;
            }
            
            // 自分自身は削除できない
            if ($_SESSION['user_id'] == $id) {
                echo json_encode(['success' => false, 'message' => '自分自身は削除できません']);
                return;
            }
            
            $deleted = $this->user->delete($id);
            
            if ($deleted) {
                echo json_encode([
                    'success' => true,
                    'message' => 'ユーザーを削除しました'
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => '削除に失敗しました']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}

// コントローラーのインスタンス化と実行
$controller = new UserController();
$controller->handleRequest();