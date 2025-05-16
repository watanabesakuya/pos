// ===== models/Category.php =====

<?php
class Category {
    private $conn;
    private $table = 'categories';
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // カテゴリ登録
    public function create($data) {
        $query = "INSERT INTO " . $this->table . " (name, description) VALUES (:name, :description)";
        
        $stmt = $this->conn->prepare($query);
        
        // データのバインド
        $stmt->bindParam(':name', $data['name'], PDO::PARAM_STR);
        $stmt->bindParam(':description', $data['description'], PDO::PARAM_STR);
        
        // クエリ実行
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        
        return false;
    }
    
    // 全カテゴリ取得
    public function getAll() {
        $query = "SELECT c.*, (SELECT COUNT(*) FROM products WHERE category_id = c.id) as product_count 
                FROM " . $this->table . " c ORDER BY c.name";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    // 指定IDのカテゴリ取得
    public function getById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch();
    }
    
    // カテゴリ更新
    public function update($id, $data) {
        $query = "UPDATE " . $this->table . " 
                SET name = :name, description = :description 
                WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        // データのバインド
        $stmt->bindParam(':name', $data['name'], PDO::PARAM_STR);
        $stmt->bindParam(':description', $data['description'], PDO::PARAM_STR);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }
    
    // カテゴリ削除
    public function delete($id) {
        // カテゴリに紐づく商品を確認
        $query = "SELECT COUNT(*) as count FROM products WHERE category_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch();
        
        if ($result['count'] > 0) {
            // 商品が存在する場合は商品のカテゴリをNULLに設定
            $query = "UPDATE products SET category_id = NULL WHERE category_id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
        }
        
        // カテゴリ削除
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }
}