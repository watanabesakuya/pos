<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>YSEレジシステム</title>
  <link rel="stylesheet" href="css/style_input.css">
  <!-- QuaggaJS CDN -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/quagga/0.12.1/quagga.min.js"></script>
</head>
<body>
  <!-- ヘッダーナビゲーション -->
  <header class="header">
    <div class="header-content">
      <h1 class="logo">YSE レジシステム</h1>
      <nav class="nav">
        <a href="index.php" class="nav-link active">レジ</a>
        <a href="sales.php" class="nav-link">売上履歴</a>
      </nav>
    </div>
  </header>

  <main class="main-content">
    <div class="register-container">
      <!-- 左側: レジ操作 -->
      <div class="register-panel">
        <div class="calculator-card">
          <div class="display-container">
            <div class="calculation-display" id="calculationDisplay">0</div>
            <div class="result-display" id="resultDisplay">0</div>
          </div>
          
          <div class="buttons">
            <!-- 数字ボタン -->
            <button onclick="appendNumber(7)" class="btn btn-number">7</button>
            <button onclick="appendNumber(8)" class="btn btn-number">8</button>
            <button onclick="appendNumber(9)" class="btn btn-number">9</button>
            <button class="btn btn-danger" onclick="clearDisplay()">AC</button>

            <button onclick="appendNumber(4)" class="btn btn-number">4</button>
            <button onclick="appendNumber(5)" class="btn btn-number">5</button>
            <button onclick="appendNumber(6)" class="btn btn-number">6</button>
            <button class="btn btn-operator" onclick="add()">＋</button>

            <button onclick="appendNumber(1)" class="btn btn-number">1</button>
            <button onclick="appendNumber(2)" class="btn btn-number">2</button>
            <button onclick="appendNumber(3)" class="btn btn-number">3</button>
            <button class="btn btn-operator" onclick="multiply()">×</button>

            <button onclick="appendNumber(0)" class="btn btn-number">0</button>
            <button onclick="appendDoubleZero()" class="btn btn-number">00</button>
            <button class="btn btn-calculate" onclick="calculate()">＝</button>
            <button class="btn btn-primary" onclick="addToCart()">計上</button>

            <button class="btn btn-barcode" onclick="openBarcodeScanner()">📷 バーコード</button>
            <button class="btn btn-secondary" onclick="location.href='sales.php'">売上確認</button>
            <button class="btn btn-success wide" onclick="finalRegister()">最終計上</button>
          </div>
        </div>
      </div>

      <!-- 右側: 商品登録テーブル -->
      <div class="cart-panel">
        <div class="cart-card">
          <div class="cart-header">
            <h3>商品登録</h3>
            <span class="item-count" id="itemCount">0 件</span>
          </div>
          
          <div class="cart-table-container">
            <table class="cart-table" id="cartTable">
              <thead>
                <tr>
                  <th>JANコード</th>
                  <th>金額</th>
                  <th>操作</th>
                </tr>
              </thead>
              <tbody id="cartTableBody">
                <tr class="empty-row">
                  <td colspan="3" class="empty-message">
                    商品を追加してください
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="cart-summary">
            <div class="summary-row">
              <span>小計:</span>
              <span id="subtotal">¥0</span>
            </div>
            <div class="summary-row">
              <span>消費税 (10%):</span>
              <span id="tax">¥0</span>
            </div>
            <div class="summary-row total">
              <span>合計:</span>
              <span id="total">¥0</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <!-- 隠しフォーム -->
  <form id="registerForm" action="register.php" method="POST" style="display: none;">
    <input type="hidden" name="amount" id="registerAmount">
  </form>

  <!-- バーコードスキャナーモーダル -->
  <div id="barcodeModal" class="modal">
    <div class="modal-content barcode-modal">
      <div class="modal-header">
        <h4>📷 バーコードスキャン</h4>
        <span class="close" onclick="closeBarcodeScanner()">&times;</span>
      </div>
      <div class="modal-body">
        <div id="scanner-container">
          <div id="scanner"></div>
          <div class="scanner-overlay">
            <div class="scanner-line"></div>
          </div>
        </div>
        <div class="scanner-status">
          <p id="scanner-status-text">カメラを商品のバーコードに向けてください</p>
        </div>
      </div>
    </div>
  </div>

  <!-- 商品登録モーダル -->
  <div id="productModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h4>商品登録</h4>
        <span class="close" onclick="closeProductModal()">&times;</span>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label for="scannedJan">JANコード:</label>
          <input type="text" id="scannedJan" readonly>
        </div>
        <div class="form-group">
          <label for="productName">商品名:</label>
          <input type="text" id="productName" placeholder="商品名を入力してください">
        </div>
        <div class="form-group">
          <label for="productPrice">価格 (円):</label>
          <input type="number" id="productPrice" placeholder="価格を入力してください" min="1">
        </div>
        <div class="product-status" id="productStatus"></div>
      </div>
      <div class="modal-footer">
        <button onclick="closeProductModal()" class="btn btn-secondary">キャンセル</button>
        <button onclick="addScannedProduct()" class="btn btn-primary">追加</button>
      </div>
    </div>
  </div>

  <!-- 編集モーダル -->
  <div id="editModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h4>金額編集</h4>
        <span class="close" onclick="closeEditModal()">&times;</span>
      </div>
      <div class="modal-body">
        <label for="editAmount">金額:</label>
        <input type="number" id="editAmount" placeholder="金額を入力">
      </div>
      <div class="modal-footer">
        <button onclick="closeEditModal()" class="btn btn-secondary">キャンセル</button>
        <button onclick="updateItem()" class="btn btn-primary">更新</button>
      </div>
    </div>
  </div>

  <script src="js/script.js"></script>
</body>
</html>