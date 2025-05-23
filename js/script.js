// レジシステムの状態管理
let calculationDisplay = ""; // 計算過程表示用
let currentInput = "0"; // 現在の入力値
let cart = []; // 商品カート
let editingIndex = -1; // 編集中のアイテムインデックス
let scannerActive = false; // スキャナーの状態

// 数字入力
function appendNumber(num) {
  if (currentInput === "0") {
    currentInput = String(num);
  } else {
    currentInput += String(num);
  }
  updateDisplays();
}

// ダブルゼロ入力
function appendDoubleZero() {
  if (currentInput !== "0") {
    currentInput += "00";
  } else {
    currentInput = "0";
  }
  updateDisplays();
}

// クリア
function clearDisplay() {
  calculationDisplay = "";
  currentInput = "0";
  updateDisplays();
}

// 加算オペレーター
function add() {
  if (currentInput !== "0" && currentInput !== "") {
    calculationDisplay += currentInput + "+";
    currentInput = "0";
    updateDisplays();
  }
}

// 乗算オペレーター
function multiply() {
  if (currentInput !== "0" && currentInput !== "") {
    calculationDisplay += currentInput + "*";
    currentInput = "0";
    updateDisplays();
  }
}

// 計算実行
function calculate() {
  try {
    let expression = calculationDisplay + currentInput;
    if (expression && expression !== "0" && expression !== "") {
      let result = eval(expression);
      currentInput = result.toString();
      calculationDisplay = "";
      updateDisplays();
    }
  } catch (error) {
    currentInput = "0";
    calculationDisplay = "";
    updateDisplays();
  }
}

// 統合されたディスプレイ更新関数
function updateDisplays() {
  updateCalculationDisplay();
  updateResultDisplay();
}

// 計算過程表示の更新
function updateCalculationDisplay() {
  const display = document.getElementById("calculationDisplay");
  if (!display) return;
  
  let displayText = calculationDisplay;
  
  if (currentInput !== "0" && currentInput !== "") {
    displayText += currentInput;
  }
  
  if (displayText === "" || displayText === "0") {
    displayText = "0";
  }
  
  // 数値をカンマ区切りでフォーマット（安全に処理）
  try {
    displayText = displayText.replace(/\d+/g, function(match) {
      const num = parseInt(match);
      return isNaN(num) ? match : num.toLocaleString();
    });
  } catch (error) {
    // フォーマットエラーの場合はそのまま表示
  }
  
  display.textContent = displayText;
}

// 結果表示の更新
function updateResultDisplay() {
  const display = document.getElementById("resultDisplay");
  if (!display) return;
  
  try {
    let expression = calculationDisplay + currentInput;
    if (expression && expression !== "0" && expression !== "") {
      let result = eval(expression);
      const num = parseInt(result);
      display.textContent = isNaN(num) ? "0" : num.toLocaleString();
    } else {
      const num = parseInt(currentInput) || 0;
      display.textContent = num.toLocaleString();
    }
  } catch (error) {
    display.textContent = "0";
  }
}

// カートに商品追加
function addToCart() {
  try {
    let expression = calculationDisplay + currentInput;
    let amount;
    
    if (expression && expression !== "0") {
      amount = eval(expression);
    } else {
      amount = parseInt(currentInput);
    }
    
    if (isNaN(amount) || amount <= 0) {
      alert("正しい金額を入力してください。");
      return;
    }
    
    // カートに追加
    cart.push({
      id: Date.now(),
      amount: amount,
      janCode: null, // 手動入力の場合はnull
      productName: "手動入力"
    });
    
    // ディスプレイクリア
    clearDisplay();
    
    // カート表示更新
    updateCartDisplay();
    
  } catch (error) {
    alert("計算エラーが発生しました。");
  }
}

// カート表示更新
function updateCartDisplay() {
  const tableBody = document.getElementById("cartTableBody");
  const itemCount = document.getElementById("itemCount");
  
  // テーブルクリア
  tableBody.innerHTML = "";
  
  if (cart.length === 0) {
    tableBody.innerHTML = `
      <tr class="empty-row">
        <td colspan="3" class="empty-message">
          商品を追加してください
        </td>
      </tr>
    `;
    itemCount.textContent = "0 件";
  } else {
    cart.forEach((item, index) => {
      const row = document.createElement("tr");
      const janDisplay = item.janCode ? `${item.janCode}` : `手動${index + 1}`;
      const nameDisplay = item.productName || "手動入力";
      
      row.innerHTML = `
        <td class="item-jan" title="${nameDisplay}">${janDisplay}</td>
        <td class="item-amount">¥${item.amount.toLocaleString()}</td>
        <td class="item-actions">
          <button class="btn-small btn-edit" onclick="editItem(${index})">編集</button>
          <button class="btn-small btn-delete" onclick="deleteItem(${index})">削除</button>
        </td>
      `;
      tableBody.appendChild(row);
    });
    itemCount.textContent = `${cart.length} 件`;
  }
  
  // 合計更新
  updateCartSummary();
}

// カート合計更新
function updateCartSummary() {
  const subtotalElement = document.getElementById("subtotal");
  const taxElement = document.getElementById("tax");
  const totalElement = document.getElementById("total");
  
  const subtotal = cart.reduce((sum, item) => sum + item.amount, 0);
  const tax = Math.floor(subtotal * 0.1);
  const total = subtotal + tax;
  
  subtotalElement.textContent = `¥${subtotal.toLocaleString()}`;
  taxElement.textContent = `¥${tax.toLocaleString()}`;
  totalElement.textContent = `¥${total.toLocaleString()}`;
}

// アイテム削除
function deleteItem(index) {
  if (confirm("この商品を削除しますか？")) {
    cart.splice(index, 1);
    updateCartDisplay();
  }
}

// アイテム編集
function editItem(index) {
  editingIndex = index;
  const item = cart[index];
  document.getElementById("editAmount").value = item.amount;
  document.getElementById("editModal").style.display = "block";
}

// アイテム更新
function updateItem() {
  const newAmount = parseInt(document.getElementById("editAmount").value);
  
  if (isNaN(newAmount) || newAmount <= 0) {
    alert("正しい金額を入力してください。");
    return;
  }
  
  if (editingIndex >= 0) {
    cart[editingIndex].amount = newAmount;
    updateCartDisplay();
    closeEditModal();
  }
}

// モーダル閉じる
function closeEditModal() {
  document.getElementById("editModal").style.display = "none";
  editingIndex = -1;
}

// 最終計上
function finalRegister() {
  if (cart.length === 0) {
    alert("商品が登録されていません。");
    return;
  }
  
  const subtotal = cart.reduce((sum, item) => sum + item.amount, 0);
  const tax = Math.floor(subtotal * 0.1);
  const total = subtotal + tax;
  
  const result = confirm(`
【計上内容】
商品数: ${cart.length}件
小計: ¥${subtotal.toLocaleString()}
消費税: ¥${tax.toLocaleString()}
合計: ¥${total.toLocaleString()}

この内容で売上を計上しますか？
  `);
  
  if (!result) return;
  
  console.log("▶ 最終計上準備OK: ", total);
  
  document.getElementById("registerAmount").value = total;
  document.getElementById("registerForm").submit();
}

// バーコードスキャナー関連関数
function openBarcodeScanner() {
  const modal = document.getElementById("barcodeModal");
  modal.style.display = "block";
  
  // カメラの許可をリクエストしてスキャナー開始
  setTimeout(() => {
    startBarcodeScanner();
  }, 300);
}

function startBarcodeScanner() {
  if (scannerActive) return;
  
  const statusText = document.getElementById("scanner-status-text");
  statusText.textContent = "カメラを初期化中...";
  
  Quagga.init({
    inputStream: {
      name: "Live",
      type: "LiveStream",
      target: document.querySelector('#scanner'),
      constraints: {
        width: 640,
        height: 480,
        facingMode: "environment" // 背面カメラを優先
      }
    },
    locator: {
      patchSize: "medium",
      halfSample: true
    },
    numOfWorkers: 2,
    decoder: {
      readers: [
        "ean_reader",      // EAN-13, EAN-8
        "ean_8_reader",
        "code_128_reader", // Code 128
        "code_39_reader",  // Code 39
        "code_39_vin_reader",
        "codabar_reader",
        "upc_reader",      // UPC-A, UPC-E
        "upc_e_reader"
      ]
    },
    locate: true
  }, function(err) {
    if (err) {
      console.error("スキャナー初期化エラー:", err);
      statusText.textContent = "カメラの初期化に失敗しました。カメラの許可を確認してください。";
      return;
    }
    
    console.log("スキャナー初期化成功");
    statusText.textContent = "バーコードをスキャンしてください";
    Quagga.start();
    scannerActive = true;
  });
  
  // バーコード検出時のイベント
  Quagga.onDetected(function(data) {
    const code = data.codeResult.code;
    console.log("バーコード検出:", code);
    
    // スキャナー停止
    stopBarcodeScanner();
    
    // 商品登録モーダルを開く
    openProductModal(code);
  });
}

function stopBarcodeScanner() {
  if (scannerActive) {
    Quagga.stop();
    scannerActive = false;
    console.log("スキャナー停止");
  }
}

function closeBarcodeScanner() {
  stopBarcodeScanner();
  document.getElementById("barcodeModal").style.display = "none";
}

// 商品登録モーダル関連
function openProductModal(janCode) {
  closeBarcodeScanner();
  
  // フィールドの初期化
  document.getElementById("scannedJan").value = janCode;
  document.getElementById("productName").value = "";
  document.getElementById("productPrice").value = "";
  document.getElementById("productStatus").innerHTML = "";
  
  // 商品情報の検索
  searchProduct(janCode);
  
  document.getElementById("productModal").style.display = "block";
}

// 商品情報検索
async function searchProduct(janCode) {
  const statusDiv = document.getElementById("productStatus");
  const nameField = document.getElementById("productName");
  const priceField = document.getElementById("productPrice");
  
  statusDiv.innerHTML = '<div class="status-loading">商品情報を検索中...</div>';
  
  try {
    const response = await fetch(`get_product.php?jan_code=${encodeURIComponent(janCode)}`);
    const data = await response.json();
    
    if (!data.success) {
      throw new Error(data.error || '検索エラーが発生しました');
    }
    
    if (data.found) {
      // 登録済み商品
      const product = data.product;
      nameField.value = product.product_name;
      priceField.value = product.price;
      nameField.readOnly = true;
      priceField.readOnly = true;
      
      statusDiv.innerHTML = `
        <div class="status-found">
          ✅ 登録済み商品
          <p>この商品は既に登録されています。</p>
        </div>
      `;
      
      // 価格フィールドにフォーカス（確認用）
      setTimeout(() => {
        priceField.focus();
      }, 100);
      
    } else {
      // 未登録商品
      nameField.readOnly = false;
      priceField.readOnly = false;
      
      statusDiv.innerHTML = `
        <div class="status-new">
          ℹ️ 新規商品
          <p>この商品は未登録です。商品名と価格を入力してください。</p>
        </div>
      `;
      
      // 商品名フィールドにフォーカス
      setTimeout(() => {
        nameField.focus();
      }, 100);
    }
    
  } catch (error) {
    console.error('商品検索エラー:', error);
    statusDiv.innerHTML = `
      <div class="status-error">
        ❌ 検索エラー
        <p>${error.message}</p>
      </div>
    `;
    
    // エラー時は手動入力可能にする
    nameField.readOnly = false;
    priceField.readOnly = false;
  }
}

function closeProductModal() {
  document.getElementById("productModal").style.display = "none";
}

function addScannedProduct() {
  const janCode = document.getElementById("scannedJan").value;
  const productName = document.getElementById("productName").value.trim();
  const price = parseInt(document.getElementById("productPrice").value);
  
  if (!janCode) {
    alert("JANコードが読み取れていません。");
    return;
  }
  
  if (!productName) {
    alert("商品名を入力してください。");
    return;
  }
  
  if (isNaN(price) || price <= 0) {
    alert("正しい価格を入力してください。");
    return;
  }
  
  // 新規商品の場合は商品マスターに保存
  const nameField = document.getElementById("productName");
  if (!nameField.readOnly) {
    saveNewProduct(janCode, productName, price);
  }
  
  // カートに追加
  cart.push({
    id: Date.now(),
    amount: price,
    janCode: janCode,
    productName: productName
  });
  
  // 表示更新
  updateCartDisplay();
  
  // モーダルを閉じる
  closeProductModal();
  
  console.log("商品追加:", { janCode, productName, price });
}

// 新規商品の保存
async function saveNewProduct(janCode, productName, price) {
  try {
    const formData = new FormData();
    formData.append('jan_code', janCode);
    formData.append('product_name', productName);
    formData.append('price', price);
    
    const response = await fetch('save_product.php', {
      method: 'POST',
      body: formData
    });
    
    const data = await response.json();
    
    if (data.success) {
      console.log('商品マスター更新:', data.message);
    } else {
      console.error('商品マスター更新エラー:', data.error);
    }
    
  } catch (error) {
    console.error('商品保存エラー:', error);
  }
}
window.onclick = function(event) {
  const modal = document.getElementById("editModal");
  if (event.target === modal) {
    closeEditModal();
  }
}

// 初期化
document.addEventListener("DOMContentLoaded", function() {
  // 初期状態設定
  calculationDisplay = "";
  currentInput = "0";
  cart = [];
  editingIndex = -1;
  scannerActive = false;
  
  // ディスプレイ初期化
  updateDisplays();
  updateCartDisplay();
  
  console.log("レジシステム初期化完了");
});