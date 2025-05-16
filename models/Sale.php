<?php
class Sale {
    private $conn;
    private $table = 'sales';
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // 売上データの登録
    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
                (amount, tax_amount, total_amount, items_count, user_id)
                VALUES (:amount, :tax_amount, :total_amount, :items_count, :user_id)";
        
        $stmt = $this->conn->prepare($query);
        
        // データのバインド
        $stmt->bindParam(':amount', $data['amount']);
        $stmt->bindParam(':tax_amount', $data['tax_amount']);
        $stmt->bindParam(':total_amount', $data['total_amount']);
        $stmt->bindParam(':items_count', $data['items_count']);
        $stmt->bindParam(':user_id', $data['user_id']);
        
        // クエリ実行
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        
        return false;
    }
    
    // 全売上データの取得
    public function getAll() {
        $query = "SELECT s.*, u.username 
                FROM " . $this->table . " s
                LEFT JOIN users u ON s.user_id = u.id
                ORDER BY s.sale_date DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // 日付範囲による売上データの取得
    public function getByDateRange($startDate, $endDate) {
        $query = "SELECT s.*, u.username 
                FROM " . $this->table . " s
                LEFT JOIN users u ON s.user_id = u.id
                WHERE s.sale_date BETWEEN :start_date AND :end_date
                ORDER BY s.sale_date DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':start_date', $startDate);
        $stmt->bindParam(':end_date', $endDate);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // 売上集計（日別）
    public function getDailySummary($startDate, $endDate) {
        $query = "SELECT 
                DATE(sale_date) as date,
                COUNT(*) as transaction_count,
                SUM(total_amount) as total_sales,
                SUM(tax_amount) as total_tax
                FROM " . $this->table . "
                WHERE sale_date BETWEEN :start_date AND :end_date
                GROUP BY DATE(sale_date)
                ORDER BY DATE(sale_date)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':start_date', $startDate);
        $stmt->bindParam(':end_date', $endDate);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}