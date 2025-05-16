<?php
require_once '../config/config.php';
require_login();

// 権限チェック - マネージャー以上のみアクセス可能
if (!has_permission('manager')) {
    $_SESSION['error'] = '商品管理へのアクセス権限がありません';
    header('Location: /');
    exit;
}

$pageTitle = '商品管理';

// データベース接続
require_once '../config/database.php';
require_once '../models/Product.php';
require_once '../models/Category.php';

$db = new Database();
$conn = $db->getConnection();

$product = new Product($conn);
$category = new Category($conn);

// 商品一覧取得
$products = $product->getAll(false);
$categories = $category->getAll();

// カテゴリIDでフィルタリング
$filterCategoryId = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
if ($filterCategoryId > 0) {
    $products = array_filter($products, function($p) use ($filterCategoryId) {
        return $p['category_id'] == $filterCategoryId;
    });
}

// 商品追加処理
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_product' && has_permission('manager')) {
        $productData = [
            'name' => $_POST['name'] ?? '',
            'category_id' => !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null,
            'price' => $_POST['price'] ?? 0,
            'tax_rate' => $_POST['tax_rate'] ?? get_tax_rate(),
            'description' => $_POST['description'] ?? '',
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];
        
        if (empty($productData['name'])) {
            $message = '商品名を入力してください';
        } elseif (!is_numeric($productData['price']) || $productData['price'] < 0) {
            $message = '価格は0以上の数値を入力してください';
        } else {
            $newProductId = $product->create($productData);
            
            if ($newProductId) {
                $_SESSION['success'] = '商品を追加しました';
                header('Location: /products/index.php');
                exit;
            } else {
                $message = '商品の追加に失敗しました';
            }
        }
    } elseif ($_POST['action'] === 'add_category' && has_permission('manager')) {
        $categoryData = [
            'name' => $_POST['name'] ?? '',
            'description' => $_POST['description'] ?? ''
        ];
        
        if (empty($categoryData['name'])) {
            $message = 'カテゴリ名を入力してください';
        } else {
            $newCategoryId = $category->create($categoryData);
            
            if ($newCategoryId) {
                $_SESSION['success'] = 'カテゴリを追加しました';
                header('Location: /products/index.php');
                exit;
            } else {
                $message = 'カテゴリの追加に失敗しました';
            }
        }
    }
}

include '../includes/header.php';
?>

<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">商品管理</h1>
        <div class="flex space-x-2">
            <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                    data-modal="add-product-modal">
                <i class="fas fa-plus mr-1"></i> 商品追加
            </button>
            <button class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded"
                    data-modal="add-category-modal">
                <i class="fas fa-folder-plus mr-1"></i> カテゴリ追加
            </button>
        </div>
    </div>
    
    <?php if ($message): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
            <p><?= htmlspecialchars($message) ?></p>
        </div>
    <?php endif; ?>
    
    <!-- カテゴリフィルター -->
    <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
        <h2 class="text-lg font-bold mb-4">カテゴリでフィルター</h2>
        <div class="flex overflow-x-auto py-2 space-x-2">
            <a href="/products/index.php" class="px-4 py-2 rounded whitespace-nowrap <?= $filterCategoryId === 0 ? 'bg-blue-500 text-white' : 'bg-gray-200 hover:bg-gray-300' ?>">
                すべて
            </a>
            <?php foreach ($categories as $cat): ?>
                <a href="/products/index.php?category_id=<?= $cat['id'] ?>" class="px-4 py-2 rounded whitespace-nowrap <?= $filterCategoryId === $cat['id'] ? 'bg-blue-500 text-white' : 'bg-gray-200 hover:bg-gray-300' ?>">
                    <?= htmlspecialchars($cat['name']) ?> (<?= $cat['product_count'] ?>)
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- 商品一覧 -->
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="p-6 border-b">
            <h2 class="text-lg font-bold">商品一覧</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">商品名</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">カテゴリ</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">価格</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">税率</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">状態</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                            商品がありません
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($products as $p): ?>
                        <tr <?= $p['is_active'] ? '' : 'class="bg-gray-100"' ?>>
                            <td class="px-6 py-4 whitespace-nowrap"><?= $p['id'] ?></td>
                            <td class="px-6 py-4">
                                <div class="font-medium"><?= htmlspecialchars($p['name']) ?></div>
                                <?php if (!empty($p['description'])): ?>
                                    <div class="text-sm text-gray-500"><?= htmlspecialchars(mb_substr($p['description'], 0, 30)) ?><?= mb_strlen($p['description']) > 30 ? '...' : '' ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($p['category_name'] ?? '未分類') ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-right"><?= format_currency($p['price']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-center"><?= $p['tax_rate'] ?>%</td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <?php if ($p['is_active']): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        有効
                                    </span>
                                <?php else: ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                        無効
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <button class="text-blue-500 hover:text-blue-700 mr-3"
                                        data-product-id="<?= $p['id'] ?>"
                                        data-edit-product>
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="text-red-500 hover:text-red-700"
                                        data-product-id="<?= $p['id'] ?>"
                                        data-delete-product>
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- 商品追加モーダル -->
    <div id="add-product-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-lg max-w-lg w-full mx-4">
            <div class="p-4 border-b flex justify-between items-center">
                <h2 class="text-xl font-bold">商品追加</h2>
                <button class="text-gray-500 hover:text-gray-700 focus:outline-none" data-close-modal>
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form action="/products/index.php" method="post">
                <input type="hidden" name="action" value="add_product">
                <div class="p-4">
                    <div class="mb-4">
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">商品名 <span class="text-red-500">*</span></label>
                        <input type="text" id="name" name="name" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="mb-4">
                        <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1">カテゴリ</label>
                        <select id="category_id" name="category_id"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">-- 選択してください --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label for="price" class="block text-sm font-medium text-gray-700 mb-1">価格 <span class="text-red-500">*</span></label>
                        <input type="number" id="price" name="price" min="0" step="1" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="mb-4">
                        <label for="tax_rate" class="block text-sm font-medium text-gray-700 mb-1">税率 (%)</label>
                        <select id="tax_rate" name="tax_rate"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="10">標準税率 (10%)</option>
                            <option value="8">軽減税率 (8%)</option>
                            <option value="0">非課税 (0%)</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">説明</label>
                        <textarea id="description" name="description" rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                    <div class="mb-4">
                        <div class="flex items-center">
                            <input type="checkbox" id="is_active" name="is_active" checked
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="is_active" class="ml-2 block text-sm font-medium text-gray-700">有効</label>
                        </div>
                    </div>
                </div>
                <div class="p-4 border-t flex justify-end">
                    <button type="button" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded mr-2" data-close-modal>
                        キャンセル
                    </button>
                    <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        商品を追加
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- カテゴリ追加モーダル -->
    <div id="add-category-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-lg max-w-lg w-full mx-4">
            <div class="p-4 border-b flex justify-between items-center">
                <h2 class="text-xl font-bold">カテゴリ追加</h2>
                <button class="text-gray-500 hover:text-gray-700 focus:outline-none" data-close-modal>
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form action="/products/index.php" method="post">
                <input type="hidden" name="action" value="add_category">
                <div class="p-4">
                    <div class="mb-4">
                        <label for="category_name" class="block text-sm font-medium text-gray-700 mb-1">カテゴリ名 <span class="text-red-500">*</span></label>
                        <input type="text" id="category_name" name="name" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="mb-4">
                        <label for="category_description" class="block text-sm font-medium text-gray-700 mb-1">説明</label>
                        <textarea id="category_description" name="description" rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                </div>
                <div class="p-4 border-t flex justify-end">
                    <button type="button" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded mr-2" data-close-modal>
                        キャンセル
                    </button>
                    <button type="submit" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                        カテゴリを追加
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$extraScripts = <<<EOT
<script>
document.addEventListener('DOMContentLoaded', function() {
    // モーダル関連
    const openModalButtons = document.querySelectorAll('[data-modal]');
    const closeModalButtons = document.querySelectorAll('[data-close-modal]');
    
    openModalButtons.forEach(button => {
        button.addEventListener('click', () => {
            const modalId = button.getAttribute('data-modal');
            const modal = document.getElementById(modalId);
            modal.classList.remove('hidden');
        });
    });
    
    closeModalButtons.forEach(button => {
        button.addEventListener('click', () => {
            const modal = button.closest('[id$="-modal"]');
            modal.classList.add('hidden');
        });
    });
    
    // 商品編集処理
    const editButtons = document.querySelectorAll('[data-edit-product]');
    editButtons.forEach(button => {
        button.addEventListener('click', () => {
            const productId = button.getAttribute('data-product-id');
            window.location.href = `/products/edit.php?id=\${productId}`;
        });
    });
    
    // 商品削除処理
    const deleteButtons = document.querySelectorAll('[data-delete-product]');
    deleteButtons.forEach(button => {
        button.addEventListener('click', () => {
            const productId = button.getAttribute('data-product-id');
            if (confirm('この商品を削除しますか？')) {
                window.location.href = `/products/delete.php?id=\${productId}`;
            }
        });
    });
});
</script>
EOT;

include '../includes/footer.php';
?>