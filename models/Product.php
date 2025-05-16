// ===== models/Product.php =====

<?php
class Product {
    private $conn;
    private $table = 'products';
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // 商品登録
    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
                (name, category_id, price, tax_rate, description, is_active) 
                VALUES (:name, :category_id, :price, :tax_rate, :description, :is_active)";
        
        $stmt = $this->conn->prepare($query);
        
        // データのバインド
        $stmt->bindParam(':name', $data['name'], PDO::PARAM_STR);
        $stmt->bindParam(':category_id', $data['category_id'], $data['category_id'] ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindParam(':price', $data['price'], PDO::PARAM_STR);
        $stmt->bindParam(':tax_rate', $data['tax_rate'], PDO::PARAM_STR);
        $stmt->bindParam(':description', $data['description'], PDO::PARAM_STR);
        $stmt->bindParam(':is_active', $data['is_active'] ?? 1, PDO::PARAM_INT);
        
        // クエリ実行
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        
        return false;
    }
    
    // 全商品取得
    public function getAll($activeOnly = false) {
        $query = "SELECT p.*, c.name as category_name 
                FROM " . $this->table . " p
                LEFT JOIN categories c ON p.category_id = c.id";
                
        if ($activeOnly) {
            $query .= " WHERE p.is_active = 1";
        }
                
        $query .= " ORDER BY p.name";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    // カテゴリ別商品取得
    public function getByCategory($category_id) {
        $query = "SELECT p.*, c.name as category_name 
                FROM " . $this->table . " p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.category_id = :category_id AND p.is_active = 1
                ORDER BY p.name";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':category_id', $category_id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    // 指定IDの商品取得
    public function getById($id) {
        $query = "SELECT p.*, c.name as category_name 
                FROM " . $this->table . " p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch();
    }
    
    // 商品更新
    public function update($id, $data) {
        $updateFields = [];
        $params = [];
        
        // 更新可能なフィールド
        $allowedFields = ['name', 'category_id', 'price', 'tax_rate', 'description', 'is_active'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                if ($field === 'category_id' && empty($data[$field])) {
                    $updateFields[] = "{$field} = NULL";
                } else {
                    $updateFields[] = "{$field} = :{$field}";
                    $params[":{$field}"] = $data[$field];
                }
            }
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
    
    // 商品削除（is_activeを0に設定）
    public function softDelete($id) {
        $query = "UPDATE " . $this->table . " SET is_active = 0 WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }
    
    // 商品検索
    public function search($keyword) {
        $query = "SELECT p.*, c.name as category_name 
                FROM " . $this->table . " p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.name LIKE :keyword OR p.description LIKE :keyword
                ORDER BY p.name";
        
        $stmt = $this->conn->prepare($query);
        $searchTerm = "%{$keyword}%";
        $stmt->bindParam(':keyword', $searchTerm, PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}

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