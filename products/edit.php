// ===== products/edit.php =====

<?php
require_once '../config/config.php';
require_login();

// 権限チェック - マネージャー以上のみアクセス可能
if (!has_permission('manager')) {
    $_SESSION['error'] = '商品管理へのアクセス権限がありません';
    header('Location: /');
    exit;
}

$pageTitle = '商品編集';

// データベース接続
require_once '../config/database.php';
require_once '../models/Product.php';
require_once '../models/Category.php';

$db = new Database();
$conn = $db->getConnection();

$product = new Product($conn);
$category = new Category($conn);

// 商品IDの取得
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$product_id) {
    $_SESSION['error'] = '商品IDが指定されていません';
    header('Location: /products/index.php');
    exit;
}

// 商品データの取得
$productData = $product->getById($product_id);

if (!$productData) {
    $_SESSION['error'] = '指定された商品が見つかりません';
    header('Location: /products/index.php');
    exit;
}

// カテゴリ一覧取得
$categories = $category->getAll();

// 商品更新処理
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $updatedData = [
        'name' => $_POST['name'] ?? '',
        'category_id' => !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null,
        'price' => $_POST['price'] ?? 0,
        'tax_rate' => $_POST['tax_rate'] ?? get_tax_rate(),
        'description' => $_POST['description'] ?? '',
        'is_active' => isset($_POST['is_active']) ? 1 : 0
    ];
    
    if (empty($updatedData['name'])) {
        $message = '商品名を入力してください';
    } elseif (!is_numeric($updatedData['price']) || $updatedData['price'] < 0) {
        $message = '価格は0以上の数値を入力してください';
    } else {
        $updated = $product->update($product_id, $updatedData);
        
        if ($updated) {
            // アクティビティログ記録
            log_activity('update_product', "商品ID: {$product_id} ({$updatedData['name']}) を更新しました");
            
            $_SESSION['success'] = '商品情報を更新しました';
            header('Location: /products/index.php');
            exit;
        } else {
            $message = '商品の更新に失敗しました';
        }
    }
}

include '../includes/header.php';
?>

<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">商品編集</h1>
        <div>
            <a href="/products/index.php" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                <i class="fas fa-arrow-left mr-1"></i> 商品一覧に戻る
            </a>
        </div>
    </div>
    
    <?php if ($message): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
            <p><?= htmlspecialchars($message) ?></p>
        </div>
    <?php endif; ?>
    
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="p-6 border-b">
            <h2 class="text-lg font-bold">商品の編集: <?= htmlspecialchars($productData['name']) ?></h2>
        </div>
        <div class="p-6">
            <form action="/products/edit.php?id=<?= $product_id ?>" method="post">
                <div class="mb-4">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">商品名 <span class="text-red-500">*</span></label>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($productData['name']) ?>" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="mb-4">
                    <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1">カテゴリ</label>
                    <select id="category_id" name="category_id"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">-- 選択してください --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $productData['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-4">
                    <label for="price" class="block text-sm font-medium text-gray-700 mb-1">価格 <span class="text-red-500">*</span></label>
                    <input type="number" id="price" name="price" value="<?= $productData['price'] ?>" min="0" step="1" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="mb-4">
                    <label for="tax_rate" class="block text-sm font-medium text-gray-700 mb-1">税率 (%)</label>
                    <select id="tax_rate" name="tax_rate"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="10" <?= $productData['tax_rate'] == 10 ? 'selected' : '' ?>>標準税率 (10%)</option>
                        <option value="8" <?= $productData['tax_rate'] == 8 ? 'selected' : '' ?>>軽減税率 (8%)</option>
                        <option value="0" <?= $productData['tax_rate'] == 0 ? 'selected' : '' ?>>非課税 (0%)</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">説明</label>
                    <textarea id="description" name="description" rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($productData['description']) ?></textarea>
                </div>
                <div class="mb-4">
                    <div class="flex items-center">
                        <input type="checkbox" id="is_active" name="is_active" <?= $productData['is_active'] ? 'checked' : '' ?>
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="is_active" class="ml-2 block text-sm font-medium text-gray-700">有効</label>
                    </div>
                </div>
                <div class="flex justify-end">
                    <a href="/products/index.php" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded mr-2">
                        キャンセル
                    </a>
                    <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        更新
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>