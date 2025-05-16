<?php
class Setting {
    private $conn;
    private $table = 'settings';
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // 設定取得
    public function get($key, $default = null) {
        $query = "SELECT setting_value FROM " . $this->table . " WHERE setting_key = :key";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':key', $key, PDO::PARAM_STR);
        $stmt->execute();
        
        $result = $stmt->fetch();
        
        return $result ? $result['setting_value'] : $default;
    }
    
    // 全設定取得
    public function getAll() {
        $query = "SELECT * FROM " . $this->table . " ORDER BY setting_key";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $settings = [];
        foreach ($stmt->fetchAll() as $row) {
            $settings[$row['setting_key']] = $row;
        }
        
        return $settings;
    }
    
    // 設定更新
    public function set($key, $value, $description = null) {
        // 既存設定を確認
        $query = "SELECT COUNT(*) as count FROM " . $this->table . " WHERE setting_key = :key";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':key', $key, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch();
        
        if ($result['count'] > 0) {
            // 更新
            $query = "UPDATE " . $this->table . " SET setting_value = :value";
            
            if ($description !== null) {
                $query .= ", description = :description";
            }
            
            $query .= " WHERE setting_key = :key";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':value', $value, PDO::PARAM_STR);
            $stmt->bindParam(':key', $key, PDO::PARAM_STR);
            
            if ($description !== null) {
                $stmt->bindParam(':description', $description, PDO::PARAM_STR);
            }
        } else {
            // 新規作成
            $query = "INSERT INTO " . $this->table . " (setting_key, setting_value, description) 
                    VALUES (:key, :value, :description)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':key', $key, PDO::PARAM_STR);
            $stmt->bindParam(':value', $value, PDO::PARAM_STR);
            $stmt->bindParam(':description', $description, PDO::PARAM_STR);
        }
        
        return $stmt->execute();
    }
    
    // 複数設定を一括更新
    public function updateBatch($settings) {
        $success = true;
        
        $this->conn->beginTransaction();
        
        try {
            foreach ($settings as $key => $value) {
                if (!$this->set($key, $value)) {
                    $success = false;
                    break;
                }
            }
            
            if ($success) {
                $this->conn->commit();
            } else {
                $this->conn->rollBack();
            }
        } catch (Exception $e) {
            $this->conn->rollBack();
            $success = false;
        }
        
        return $success;
    }
}