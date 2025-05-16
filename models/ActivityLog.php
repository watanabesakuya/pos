<?php
class ActivityLog {
    private $conn;
    private $table = 'activity_logs';
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // ログ登録
    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
                (user_id, action, description, ip_address) 
                VALUES (:user_id, :action, :description, :ip_address)";
        
        $stmt = $this->conn->prepare($query);
        
        // IPアドレス取得
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
        
        // データのバインド
        $stmt->bindParam(':user_id', $data['user_id'], PDO::PARAM_INT);
        $stmt->bindParam(':action', $data['action'], PDO::PARAM_STR);
        $stmt->bindParam(':description', $data['description'], PDO::PARAM_STR);
        $stmt->bindParam(':ip_address', $ip_address, PDO::PARAM_STR);
        
        // クエリ実行
        return $stmt->execute();
    }
    
    // ログ取得（最新順）
    public function getAll($limit = 100) {
        $query = "SELECT l.*, u.username, u.display_name
                FROM " . $this->table . " l
                LEFT JOIN users u ON l.user_id = u.id
                ORDER BY l.created_at DESC
                LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    // ユーザー別ログ取得
    public function getByUser($user_id, $limit = 50) {
        $query = "SELECT l.*, u.username, u.display_name
                FROM " . $this->table . " l
                LEFT JOIN users u ON l.user_id = u.id
                WHERE l.user_id = :user_id
                ORDER BY l.created_at DESC
                LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}