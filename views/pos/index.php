
<?php
require_once '../../config/config.php';
// ログイン要求
require_login();

$pageTitle = 'レジ';

// カテゴリと商品を取得
require_once '../../config/database.php';
require_once '../../models/Category.php';
require_once '../../models/Product.php';

$db = new Database();
$conn = $db->getConnection();

$category = new Category($conn);
$categories = $category->getAll();

$product = new Product($conn);
$activeProducts = $product->getAll(true);

// グループ化した商品リスト
$groupedProducts = [];
foreach ($activeProducts as $prod) {
    $catId = $prod['category_id'] ?? 0;
    if (!isset($groupedProducts[$catId])) {
        $groupedProducts[$catId] = [];
    }
    $groupedProducts[$catId][] = $prod;
}

include '../../includes/header.php';
?>

<div class="container mx-auto px-4 py-6" x-data="posApp()">
    <div class="flex flex-col lg:flex-row gap-6">
        <!-- 左カラム：商品カテゴリと商品リスト -->
        <div class="w-full lg:w-3/5">
            <div class="bg-white rounded-lg shadow-lg mb-6">
                <div class="p-4 border-b">
                    <h2 class="text-xl font-bold">商品選択</h2>
                </div>
                
                <!-- 検索バー -->
                <div class="p-4">
                    <div class="flex">
                        <input type="text" x-model="searchTerm" placeholder="商品検索" class="flex-grow px-4 py-2 border rounded-l focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <button class="bg-blue-500 text-white px-4 py-2 rounded-r" @click="searchProducts">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                
                <!-- カテゴリタブ -->
                <div class="px-4">
                    <div class="flex overflow-x-auto py-2 space-x-2">
                        <button class="px-4 py-2 rounded whitespace-nowrap" 
                                :class="selectedCategory === 0 ? 'bg-blue-500 text-white' : 'bg-gray-200 hover:bg-gray-300'"
                                @click="selectedCategory = 0">
                            すべて
                        </button>
                        <?php foreach ($categories as $cat): ?>
                        <button class="px-4 py-2 rounded whitespace-nowrap" 
                                :class="selectedCategory === <?= $cat['id'] ?> ? 'bg-blue-500 text-white' : 'bg-gray-200 hover:bg-gray-300'"
                                @click="selectedCategory = <?= $cat['id'] ?>">
                            <?= htmlspecialchars($cat['name']) ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- 商品グリッド -->
                <div class="p-4">
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                        <!-- 検索結果表示 -->
                        <template x-if="searchResults.length > 0">
                            <template x-for="product in searchResults" :key="product.id">
                                <div class="bg-white border rounded-lg shadow p-4 cursor-pointer hover:bg-blue-50"
                                     @click="addProductToCart(product)">
                                    <h3 class="font-bold text-lg" x-text="product.name"></h3>
                                    <p class="text-gray-500" x-text="product.category_name || 'カテゴリなし'"></p>
                                    <p class="text-lg font-semibold mt-2" x-text="formatCurrency(product.price)"></p>
                                </div>
                            </template>
                        </template>
                        
                        <!-- カテゴリ別商品表示 -->
                        <template x-if="searchResults.length === 0">
                            <?php foreach ($activeProducts as $prod): ?>
                            <div class="bg-white border rounded-lg shadow p-4 cursor-pointer hover:bg-blue-50"
                                 x-show="selectedCategory === 0 || selectedCategory === <?= $prod['category_id'] ?? 0 ?>"
                                 @click="addProductToCart({
                                     id: <?= $prod['id'] ?>,
                                     name: '<?= htmlspecialchars(addslashes($prod['name'])) ?>',
                                     price: <?= $prod['price'] ?>,
                                     tax_rate: <?= $prod['tax_rate'] ?>
                                 })">
                                <h3 class="font-bold text-lg"><?= htmlspecialchars($prod['name']) ?></h3>
                                <p class="text-gray-500"><?= htmlspecialchars($prod['category_name'] ?? 'カテゴリなし') ?></p>
                                <p class="text-lg font-semibold mt-2"><?= format_currency($prod['price']) ?></p>
                            </div>
                            <?php endforeach; ?>
                        </template>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 右カラム：計算機と会計 -->
        <div class="w-full lg:w-2/5">
            <div class="bg-white rounded-lg shadow-lg mb-6 sticky top-4">
                <div class="p-4 border-b">
                    <h2 class="text-xl font-bold">会計</h2>
                </div>
                
                <!-- 商品リスト -->
                <div class="p-4">
                    <div class="mb-4">
                        <h3 class="font-bold text-lg mb-2">カート内商品</h3>
                        <div class="border rounded max-h-64 overflow-y-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50 sticky top-0">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">商品</th>
                                        <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">数量</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">金額</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <template x-if="cart.length === 0">
                                        <tr>
                                            <td colspan="4" class="px-4 py-4 text-center text-gray-500">
                                                商品がありません
                                            </td>
                                        </tr>
                                    </template>
                                    <template x-for="(item, index) in cart" :key="index">
                                        <tr>
                                            <td class="px-4 py-2">
                                                <div class="flex flex-col">
                                                    <span class="font-medium" x-text="item.name"></span>
                                                    <span class="text-sm text-gray-500" x-text="formatCurrency(item.price) + ' × ' + item.quantity"></span>
                                                </div>
                                            </td>
                                            <td class="px-4 py-2 text-center">
                                                <div class="flex items-center justify-center">
                                                    <button class="w-6 h-6 rounded-full bg-gray-200 hover:bg-gray-300 focus:outline-none"
                                                            @click="decrementQuantity(index)">
                                                        <i class="fas fa-minus text-xs"></i>
                                                    </button>
                                                    <span class="mx-2" x-text="item.quantity"></span>
                                                    <button class="w-6 h-6 rounded-full bg-gray-200 hover:bg-gray-300 focus:outline-none"
                                                            @click="incrementQuantity(index)">
                                                        <i class="fas fa-plus text-xs"></i>
                                                    </button>
                                                </div>
                                            </td>
                                            <td class="px-4 py-2 text-right" x-text="formatCurrency(item.price * item.quantity)"></td>
                                            <td class="px-4 py-2 text-right">
                                                <button class="text-red-500 hover:text-red-700 focus:outline-none"
                                                        @click="removeFromCart(index)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- 合計金額 -->
                    <div class="mb-4">
                        <div class="flex justify-between py-2">
                            <span>小計:</span>
                            <span x-text="formatCurrency(calculateSubtotal())"></span>
                        </div>
                        <div class="flex justify-between py-2">
                            <span>消費税:</span>
                            <span x-text="formatCurrency(calculateTax())"></span>
                        </div>
                        <div class="flex justify-between py-2 text-lg font-bold">
                            <span>合計:</span>
                            <span x-text="formatCurrency(calculateTotal())"></span>
                        </div>
                    </div>
                    
                    <!-- 支払い方法 -->
                    <div class="mb-4">
                        <h3 class="font-bold mb-2">支払い方法</h3>
                        <div class="flex space-x-2">
                            <button class="flex-1 py-2 rounded" 
                                    :class="paymentMethod === 'cash' ? 'bg-blue-500 text-white' : 'bg-gray-200 hover:bg-gray-300'"
                                    @click="paymentMethod = 'cash'">
                                <i class="fas fa-money-bill-wave mr-1"></i> 現金
                            </button>
                            <button class="flex-1 py-2 rounded" 
                                    :class="paymentMethod === 'credit_card' ? 'bg-blue-500 text-white' : 'bg-gray-200 hover:bg-gray-300'"
                                    @click="paymentMethod = 'credit_card'">
                                <i class="fas fa-credit-card mr-1"></i> カード
                            </button>
                            <button class="flex-1 py-2 rounded" 
                                    :class="paymentMethod === 'other' ? 'bg-blue-500 text-white' : 'bg-gray-200 hover:bg-gray-300'"
                                    @click="paymentMethod = 'other'">
                                <i class="fas fa-wallet mr-1"></i> その他
                            </button>
                        </div>
                    </div>
                    
                    <!-- アクションボタン -->
                    <div class="grid grid-cols-2 gap-4">
                        <button class="py-3 bg-gray-500 text-white rounded hover:bg-gray-600"
                                @click="clearCart()">
                            <i class="fas fa-trash mr-1"></i> クリア
                        </button>
                        <button class="py-3 bg-green-500 text-white rounded hover:bg-green-600"
                                @click="processSale()"
                                :disabled="cart.length === 0"
                                :class="{'opacity-50 cursor-not-allowed': cart.length === 0}">
                            <i class="fas fa-check mr-1"></i> 会計
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- モーダル：領収書 -->
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
         x-show="showReceiptModal"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         x-cloak>
        <div class="bg-white rounded-lg shadow-lg max-w-md w-full mx-4 max-h-screen overflow-y-auto"
             @click.away="showReceiptModal = false">
            <div class="p-4 border-b flex justify-between items-center">
                <h2 class="text-xl font-bold">領収書</h2>
                <button class="text-gray-500 hover:text-gray-700 focus:outline-none"
                        @click="showReceiptModal = false">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-4">
                <div class="text-center mb-4">
                    <h3 class="text-xl font-bold"><?= htmlspecialchars(get_setting('receipt_header', 'YSEマート')) ?></h3>
                    <p class="text-sm text-gray-500" x-text="formatDate(new Date())"></p>
                </div>
                
                <div class="border-t border-b py-4 my-4">
                    <table class="w-full">
                        <thead>
                            <tr>
                                <th class="text-left pb-2">商品</th>
                                <th class="text-right pb-2">金額</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(item, index) in lastSaleItems" :key="index">
                                <tr>
                                    <td class="py-1">
                                        <div class="flex flex-col">
                                            <span x-text="item.name"></span>
                                            <span class="text-sm text-gray-500" x-text="formatCurrency(item.price) + ' × ' + item.quantity"></span>
                                        </div>
                                    </td>
                                    <td class="py-1 text-right" x-text="formatCurrency(item.price * item.quantity)"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                
                <div class="mb-4">
                    <div class="flex justify-between py-1">
                        <span>小計:</span>
                        <span x-text="formatCurrency(lastSaleSubtotal)"></span>
                    </div>
                    <div class="flex justify-between py-1">
                        <span>消費税:</span>
                        <span x-text="formatCurrency(lastSaleTax)"></span>
                    </div>
                    <div class="flex justify-between py-1 text-lg font-bold">
                        <span>合計:</span>
                        <span x-text="formatCurrency(lastSaleTotal)"></span>
                    </div>
                    <div class="flex justify-between py-1">
                        <span>支払い方法:</span>
                        <span x-text="formatPaymentMethod(lastSalePaymentMethod)"></span>
                    </div>
                </div>
                
                <div class="text-center mt-6">
                    <p><?= htmlspecialchars(get_setting('receipt_footer', 'ご利用ありがとうございました')) ?></p>
                    <p class="text-sm text-gray-500 mt-2">販売ID: <span x-text="lastSaleId"></span></p>
                </div>
                
                <div class="mt-6 flex justify-center">
                    <button class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 mr-2"
                            @click="printReceipt()">
                        <i class="fas fa-print mr-1"></i> 印刷
                    </button>
                    <button class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600"
                            @click="showReceiptModal = false">
                        閉じる
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$extraScripts = <<<EOT
<script>
function posApp() {
    return {
        cart: [],
        searchTerm: '',
        searchResults: [],
        selectedCategory: 0,
        paymentMethod: 'cash',
        showReceiptModal: false,
        lastSaleItems: [],
        lastSaleSubtotal: 0,
        lastSaleTax: 0,
        lastSaleTotal: 0,
        lastSaleId: null,
        lastSalePaymentMethod: '',
        
        // 商品検索
        searchProducts() {
            if (!this.searchTerm.trim()) {
                this.searchResults = [];
                return;
            }
            
            fetch('/controllers/ProductController.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'searchProducts',
                    keyword: this.searchTerm
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.searchResults = data.products;
                } else {
                    console.error('検索エラー:', data.message);
                    this.searchResults = [];
                }
            })
            .catch(error => {
                console.error('エラー:', error);
                this.searchResults = [];
            });
        },
        
        // カートに商品を追加
        addProductToCart(product) {
            // 既存の商品かチェック
            const existingIndex = this.cart.findIndex(item => item.id === product.id);
            
            if (existingIndex >= 0) {
                // 数量を増やす
                this.cart[existingIndex].quantity++;
            } else {
                // 新しい商品をカートに追加
                this.cart.push({
                    id: product.id,
                    name: product.name,
                    price: parseFloat(product.price),
                    tax_rate: parseFloat(product.tax_rate || 10),
                    quantity: 1
                });
            }
        },
        
        // カートから商品を削除
        removeFromCart(index) {
            this.cart.splice(index, 1);
        },
        
        // 数量を増やす
        incrementQuantity(index) {
            this.cart[index].quantity++;
        },
        
        // 数量を減らす
        decrementQuantity(index) {
            if (this.cart[index].quantity > 1) {
                this.cart[index].quantity--;
            } else {
                this.removeFromCart(index);
            }
        },
        
        // カートをクリア
        clearCart() {
            if (this.cart.length === 0 || confirm('カートをクリアしますか？')) {
                this.cart = [];
            }
        },
        
        // 金額フォーマット
        formatCurrency(amount) {
            return '¥' + parseFloat(amount).toLocaleString('ja-JP', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            });
        },
        
        // 日付フォーマット
        formatDate(date) {
            return date.toLocaleDateString('ja-JP', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        },
        
        // 支払い方法フォーマット
        formatPaymentMethod(method) {
            const methods = {
                'cash': '現金',
                'credit_card': 'クレジットカード',
                'other': 'その他'
            };
            return methods[method] || method;
        },
        
        // 小計計算
        calculateSubtotal() {
            return this.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        },
        
        // 消費税計算
        calculateTax() {
            return this.cart.reduce((sum, item) => {
                const taxRate = item.tax_rate / 100;
                return sum + (item.price * item.quantity * taxRate);
            }, 0);
        },
        
        // 合計金額計算
        calculateTotal() {
            return this.calculateSubtotal() + this.calculateTax();
        },
        
        // 売上処理
        processSale() {
            if (this.cart.length === 0) {
                return;
            }
            
            const saleData = {
                action: 'addSale',
                items: this.cart.map(item => ({
                    product_id: item.id,
                    name: item.name,
                    price: item.price,
                    quantity: item.quantity,
                    tax_rate: item.tax_rate,
                    tax_amount: (item.price * item.quantity * (item.tax_rate / 100)),
                    subtotal: (item.price * item.quantity)
                })),
                totalAmount: this.calculateTotal(),
                paymentMethod: this.paymentMethod
            };
            
            fetch('/controllers/SaleController.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(saleData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // 領収書用データを保存
                    this.lastSaleItems = [...this.cart];
                    this.lastSaleSubtotal = this.calculateSubtotal();
                    this.lastSaleTax = this.calculateTax();
                    this.lastSaleTotal = this.calculateTotal();
                    this.lastSaleId = data.saleId;
                    this.lastSalePaymentMethod = this.paymentMethod;
                    
                    // カートをクリア
                    this.cart = [];
                    
                    // 領収書モーダルを表示
                    this.showReceiptModal = true;
                } else {
                    alert('エラー: ' + data.message);
                }
            })
            .catch(error => {
                console.error('エラー:', error);
                alert('通信エラーが発生しました。');
            });
        },
        
        // 領収書印刷
        printReceipt() {
            const printWindow = window.open('', '_blank');
            
            if (printWindow) {
                printWindow.document.write(`
                    <html>
                    <head>
                        <title>領収書</title>
                        <style>
                            body { font-family: sans-serif; line-height: 1.5; }
                            .receipt { width: 300px; margin: 0 auto; }
                            .header { text-align: center; margin-bottom: 20px; }
                            .items { margin: 20px 0; }
                            .item { display: flex; justify-content: space-between; margin-bottom: 5px; }
                            .total { font-weight: bold; border-top: 1px solid #000; padding-top: 10px; }
                            .footer { text-align: center; margin-top: 30px; border-top: 1px solid #ccc; padding-top: 10px; }
                        </style>
                    </head>
                    <body onload="window.print(); window.close();">
                        <div class="receipt">
                            <div class="header">
                                <h2>${escapeHtml('<?= htmlspecialchars(get_setting('receipt_header', 'YSEマート')) ?>')}</h2>
                                <p>${this.formatDate(new Date())}</p>
                            </div>
                            
                            <div class="items">
                                ${this.lastSaleItems.map(item => `
                                    <div class="item">
                                        <div>
                                            ${escapeHtml(item.name)}<br>
                                            <small>${this.formatCurrency(item.price)} × ${item.quantity}</small>
                                        </div>
                                        <div>${this.formatCurrency(item.price * item.quantity)}</div>
                                    </div>
                                `).join('')}
                            </div>
                            
                            <div class="total">
                                <div class="item">
                                    <div>小計:</div>
                                    <div>${this.formatCurrency(this.lastSaleSubtotal)}</div>
                                </div>
                                <div class="item">
                                    <div>消費税:</div>
                                    <div>${this.formatCurrency(this.lastSaleTax)}</div>
                                </div>
                                <div class="item">
                                    <div>合計:</div>
                                    <div>${this.formatCurrency(this.lastSaleTotal)}</div>
                                </div>
                                <div class="item">
                                    <div>支払い方法:</div>
                                    <div>${this.formatPaymentMethod(this.lastSalePaymentMethod)}</div>
                                </div>
                            </div>
                            
                            <div class="footer">
                                <p>${escapeHtml('<?= htmlspecialchars(get_setting('receipt_footer', 'ご利用ありがとうございました')) ?>')}</p>
                                <p><small>販売ID: ${this.lastSaleId}</small></p>
                            </div>
                        </div>
                    </body>
                    </html>
                `);
                
                printWindow.document.close();
            }
        }
    };
}

// HTMLエスケープ関数
function escapeHtml(str) {
    return str
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}
</script>
EOT;

include '../../includes/footer.php';
?>