// ===== models/User.php =====

<?php
class User {
    private $conn;
    private $table = 'users';
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // ユーザー登録
    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
                (username, password, display_name, role) 
                VALUES (:username, :password, :display_name, :role)";
        
        $stmt = $this->conn->prepare($query);
        
        // パスワードハッシュ化
        $password_hash = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // データのバインド
        $stmt->bindParam(':username', $data['username'], PDO::PARAM_STR);
        $stmt->bindParam(':password', $password_hash, PDO::PARAM_STR);
        $stmt->bindParam(':display_name', $data['display_name'], PDO::PARAM_STR);
        $stmt->bindParam(':role', $data['role'], PDO::PARAM_STR);
        
        // クエリ実行
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        
        return false;
    }
    
    // ユーザー認証
    public function authenticate($username, $password) {
        $query = "SELECT * FROM " . $this->table . " WHERE username = :username";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->execute();
        
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // 最終ログイン時間更新
            $this->updateLastLogin($user['id']);
            
            // アクティビティログ記録
            require_once 'ActivityLog.php';
            $log = new ActivityLog($this->conn);
            $log->create([
                'user_id' => $user['id'],
                'action' => 'login',
                'description' => "ユーザー {$username} がログインしました。"
            ]);
            
            return $user;
        }
        
        return false;
    }
    
    // 最終ログイン時間更新
    private function updateLastLogin($id) {
        $query = "UPDATE " . $this->table . " SET last_login = NOW() WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }
    
    // 全ユーザー取得
    public function getAll() {
        $query = "SELECT id, username, display_name, role, created_at, last_login FROM " . $this->table . " ORDER BY id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    // 指定IDのユーザー取得
    public function getById($id) {
        $query = "SELECT id, username, display_name, role, created_at, last_login FROM " . $this->table . " WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch();
    }
    
    // ユーザー更新
    public function update($id, $data) {
        $updateFields = [];
        $params = [];
        
        // 更新可能なフィールド
        $allowedFields = ['display_name', 'role'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateFields[] = "{$field} = :{$field}";
                $params[":{$field}"] = $data[$field];
            }
        }
        
        // パスワード更新がある場合
        if (!empty($data['password'])) {
            $updateFields[] = "password = :password";
            $params[':password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        if (empty($updateFields)) {
            return false;
        }
        
        $query = "UPDATE " . $this->table . " SET " . implode(', ', $updateFields) . " WHERE id = :id";
        $params[':id'] = $id;
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        return $stmt->execute();
    }
    
    // ユーザー削除
    public function delete($id) {
        // 関連データを確認
        $query = "SELECT COUNT(*) as count FROM sales WHERE user_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch();
        
        if ($result['count'] > 0) {
            // 売上データが存在する場合は削除せず、売上のユーザーIDをNULLに設定
            $query = "UPDATE sales SET user_id = NULL WHERE user_id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
        }
        
        // ユーザー削除
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }
}
