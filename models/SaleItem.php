// ===== models/SaleItem.php =====

<?php
class SaleItem {
    private $conn;
    private $table = 'sale_items';
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // 売上明細データの登録
    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
                (sale_id, product_id, item_name, price, quantity, tax_rate, tax_amount, subtotal) 
                VALUES (:sale_id, :product_id, :item_name, :price, :quantity, :tax_rate, :tax_amount, :subtotal)";
        
        $stmt = $this->conn->prepare($query);
        
        // データのバインド
        $stmt->bindParam(':sale_id', $data['sale_id'], PDO::PARAM_INT);
        $stmt->bindParam(':product_id', $data['product_id'], $data['product_id'] ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindParam(':item_name', $data['item_name'], PDO::PARAM_STR);
        $stmt->bindParam(':price', $data['price'], PDO::PARAM_STR);
        $stmt->bindParam(':quantity', $data['quantity'], PDO::PARAM_INT);
        $stmt->bindParam(':tax_rate', $data['tax_rate'], PDO::PARAM_STR);
        $stmt->bindParam(':tax_amount', $data['tax_amount'], PDO::PARAM_STR);
        $stmt->bindParam(':subtotal', $data['subtotal'], PDO::PARAM_STR);
        
        // クエリ実行
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        
        return false;
    }
    
    // 売上IDによる明細取得
    public function getBySaleId($sale_id) {
        $query = "SELECT si.*, p.name as product_name, p.description as product_description
                FROM " . $this->table . " si
                LEFT JOIN products p ON si.product_id = p.id
                WHERE si.sale_id = :sale_id
                ORDER BY si.id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':sale_id', $sale_id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}