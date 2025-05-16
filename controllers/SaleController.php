<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Sale.php';

class SaleController {
    private $db;
    private $sale;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->sale = new Sale($this->db);
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
            case 'addSale':
                $this->addSale($data);
                break;
            case 'getSales':
                $this->getSales();
                break;
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    }
    
    // 売上データの登録処理
    private function addSale($data) {
        try {
            $items = $data['items'];
            $totalAmount = $data['totalAmount'];
            $itemsCount = count($items);
            
            // 税抜き金額と消費税額を計算 (税率10%で固定)
            $taxRate = 0.1;
            $taxExcludedAmount = $totalAmount / (1 + $taxRate);
            $taxAmount = $totalAmount - $taxExcludedAmount;
            
            // 現在のユーザーID (認証システム導入時はセッションから取得)
            $userId = 1; // 仮のユーザーID
            
            // 売上データを登録
            $saleId = $this->sale->create([
                'amount' => $taxExcludedAmount,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'items_count' => $itemsCount,
                'user_id' => $userId
            ]);
            
            if ($saleId) {
                // 必要に応じて売上明細も登録（sale_itemsテーブルを使う場合）
                echo json_encode([
                    'success' => true,
                    'saleId' => $saleId,
                    'message' => '売上を登録しました'
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => '売上の登録に失敗しました']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    // 売上データの取得処理
    private function getSales() {
        try {
            $sales = $this->sale->getAll();
            echo json_encode(['success' => true, 'sales' => $sales]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}

// コントローラーのインスタンス化と実行
$controller = new SaleController();
$controller->handleRequest();