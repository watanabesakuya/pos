// ===== controllers/ProductController.php =====

<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/Product.php';

class ProductController {
    private $db;
    private $product;
    
    public function __construct() {
        $this->db = new Database();
        $this->product = new Product($this->db->getConnection());
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
            case 'getProducts':
                $this->getProducts($data['activeOnly'] ?? false);
                break;
            case 'getProductById':
                $this->getProductById($data['id'] ?? 0);
                break;
            case 'getProductsByCategory':
                $this->getProductsByCategory($data['categoryId'] ?? 0);
                break;
            case 'createProduct':
                $this->createProduct($data);
                break;
            case 'updateProduct':
                $this->updateProduct($data['id'] ?? 0, $data);
                break;
            case 'deleteProduct':
                $this->deleteProduct($data['id'] ?? 0);
                break;
            case 'searchProducts':
                $this->searchProducts($data['keyword'] ?? '');
                break;
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    }
    
    // 商品一覧取得
    private function getProducts($activeOnly = false) {
        try {
            $products = $this->product->getAll($activeOnly);
            echo json_encode(['success' => true, 'products' => $products]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    // 指定IDの商品取得
    private function getProductById($id) {
        try {
            if (!$id) {
                echo json_encode(['success' => false, 'message' => '商品IDが指定されていません']);
                return;
            }
            
            $product = $this->product->getById($id);
            
            if ($product) {
                echo json_encode(['success' => true, 'product' => $product]);
            } else {
                echo json_encode(['success' => false, 'message' => '指定された商品が見つかりません']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    // カテゴリ別商品取得
    private function getProductsByCategory($categoryId) {
        try {
            if (!$categoryId) {
                echo json_encode(['success' => false, 'message' => 'カテゴリIDが指定されていません']);
                return;
            }
            
            $products = $this->product->getByCategory($categoryId);
            echo json_encode(['success' => true, 'products' => $products]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    // 商品作成
    private function createProduct($data) {
        try {
            // 権限チェック
            if (!has_permission('manager')) {
                echo json_encode(['success' => false, 'message' => '権限がありません']);
                return;
            }
            
            // 必須項目の検証
            if (empty($data['name']) || !isset($data['price'])) {
                echo json_encode(['success' => false, 'message' => '商品名と価格は必須です']);
                return;
            }
            
            $productData = [
                'name' => $data['name'],
                'category_id' => $data['category_id'] ?? null,
                'price' => $data['price'],
                'tax_rate' => $data['tax_rate'] ?? get_tax_rate(),
                'description' => $data['description'] ?? '',
                'is_active' => $data['is_active'] ?? 1
            ];
            
            $productId = $this->product->create($productData);
            
            if ($productId) {
                echo json_encode([
                    'success' => true,
                    'productId' => $productId,
                    'message' => '商品を登録しました'
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => '商品の登録に失敗しました']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    // 商品更新
    private function updateProduct($id, $data) {
        try {
            // 権限チェック
            if (!has_permission('manager')) {
                echo json_encode(['success' => false, 'message' => '権限がありません']);
                return;
            }
            
            if (!$id) {
                echo json_encode(['success' => false, 'message' => '商品IDが指定されていません']);
                return;
            }
            
            $productData = [];
            
            // 更新するフィールド
            if (isset($data['name'])) {
                $productData['name'] = $data['name'];
            }
            
            if (isset($data['category_id'])) {
                $productData['category_id'] = $data['category_id'];
            }
            
            if (isset($data['price'])) {
                $productData['price'] = $data['price'];
            }
            
            if (isset($data['tax_rate'])) {
                $productData['tax_rate'] = $data['tax_rate'];
            }
            
            if (isset($data['description'])) {
                $productData['description'] = $data['description'];
            }
            
            if (isset($data['is_active'])) {
                $productData['is_active'] = $data['is_active'];
            }
            
            if (empty($productData)) {
                echo json_encode(['success' => false, 'message' => '更新するデータがありません']);
                return;
            }
            
            $updated = $this->product->update($id, $productData);
            
            if ($updated) {
                echo json_encode([
                    'success' => true,
                    'message' => '商品情報を更新しました'
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => '更新に失敗しました']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    // 商品削除（論理削除）
    private function deleteProduct($id) {
        try {
            // 権限チェック
            if (!has_permission('manager')) {
                echo json_encode(['success' => false, 'message' => '権限がありません']);
                return;
            }
            
            if (!$id) {
                echo json_encode(['success' => false, 'message' => '商品IDが指定されていません']);
                return;
            }
            
            $deleted = $this->product->softDelete($id);
            
            if ($deleted) {
                echo json_encode([
                    'success' => true,
                    'message' => '商品を無効化しました'
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => '無効化に失敗しました']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    // 商品検索
    private function searchProducts($keyword) {
        try {
            if (empty($keyword)) {
                echo json_encode(['success' => false, 'message' => '検索キーワードを入力してください']);
                return;
            }
            
            $products = $this->product->search($keyword);
            echo json_encode(['success' => true, 'products' => $products]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}

// コントローラーのインスタンス化と実行
$controller = new ProductController();
$controller->handleRequest();