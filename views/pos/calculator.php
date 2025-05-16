<div class="calculator-container bg-white rounded-lg shadow-lg p-6">
    <div id="alert-container" class="mb-3"></div>
    
    <div class="display-container mb-4">
        <input type="text" id="calculator-display" readonly class="w-full p-4 text-right text-2xl border rounded-lg" value="0">
    </div>
    
    <div class="calculator-keys grid grid-cols-4 gap-2">
        <!-- 1行目 -->
        <button class="all-clear col-span-2 bg-red-500 text-white p-3 rounded" onclick="calculator.clearAll()">AC</button>
        <button class="tax bg-blue-500 text-white p-3 rounded" onclick="calculator.calculateTax()">税込</button>
        <button class="operator bg-gray-300 p-3 rounded" value="/">÷</button>
        
        <!-- 2行目 -->
        <button class="p-3 bg-gray-200 rounded" value="7">7</button>
        <button class="p-3 bg-gray-200 rounded" value="8">8</button>
        <button class="p-3 bg-gray-200 rounded" value="9">9</button>
        <button class="operator bg-gray-300 p-3 rounded" value="*">×</button>
        
        <!-- 3行目 -->
        <button class="p-3 bg-gray-200 rounded" value="4">4</button>
        <button class="p-3 bg-gray-200 rounded" value="5">5</button>
        <button class="p-3 bg-gray-200 rounded" value="6">6</button>
        <button class="operator bg-gray-300 p-3 rounded" value="-">-</button>
        
        <!-- 4行目 -->
        <button class="p-3 bg-gray-200 rounded" value="1">1</button>
        <button class="p-3 bg-gray-200 rounded" value="2">2</button>
        <button class="p-3 bg-gray-200 rounded" value="3">3</button>
        <button class="operator bg-gray-300 p-3 rounded" value="+">+</button>
        
        <!-- 5行目 -->
        <button class="p-3 bg-gray-200 rounded col-span-2" value="0">0</button>
        <button class="decimal p-3 bg-gray-200 rounded" value=".">.</button>
        <button class="equals bg-blue-500 text-white p-3 rounded" value="=">=</button>
    </div>
    
    <div class="action-buttons mt-4 grid grid-cols-2 gap-2">
        <button id="btn-add-item" class="add-sale bg-green-500 text-white p-3 rounded">商品追加</button>
        <button id="btn-submit-sale" class="bg-purple-500 text-white p-3 rounded">計上</button>
    </div>
    
    <div class="current-sale mt-4">
        <h3 class="text-lg font-bold mb-2">現在の合計</h3>
        <ul id="sale-items" class="border rounded p-2 min-h-24">
            <!-- 商品リストが動的に挿入されます -->
        </ul>
        <div class="total-container mt-2 text-right">
            <span class="font-bold">合計:</span>
            <span id="total-amount">0</span>円
        </div>
    </div>
</div>

<script>
// クラス定義
class Calculator {
    constructor() {
        this.displayValue = '0';
        this.firstOperand = null;
        this.waitingForSecondOperand = false;
        this.operator = null;
        this.items = [];
    }
    
    // 数字入力処理
    inputDigit(digit) {
        if (this.waitingForSecondOperand === true) {
            this.displayValue = digit;
            this.waitingForSecondOperand = false;
        } else {
            this.displayValue = this.displayValue === '0' ? digit : this.displayValue + digit;
        }
        this.updateDisplay();
    }
    
    // 小数点入力処理
    inputDecimal() {
        if (this.waitingForSecondOperand) return;
        
        if (!this.displayValue.includes('.')) {
            this.displayValue += '.';
        }
        this.updateDisplay();
    }
    
    // 演算子処理
    handleOperator(nextOperator) {
        const inputValue = parseFloat(this.displayValue);
        
        if (this.firstOperand === null) {
            this.firstOperand = inputValue;
        } else if (this.operator) {
            const result = this.calculate(this.firstOperand, inputValue, this.operator);
            this.displayValue = String(result);
            this.firstOperand = result;
        }
        
        this.waitingForSecondOperand = true;
        this.operator = nextOperator;
        this.updateDisplay();
    }
    
    // 計算実行
    calculate(firstOperand, secondOperand, operator) {
        if (operator === '+') {
            return firstOperand + secondOperand;
        } else if (operator === '-') {
            return firstOperand - secondOperand;
        } else if (operator === '*') {
            return firstOperand * secondOperand;
        } else if (operator === '/') {
            return firstOperand / secondOperand;
        }
        
        return secondOperand;
    }
    
    // 税込み計算 (10%)
    calculateTax() {
        const amount = parseFloat(this.displayValue);
        const taxAmount = amount * 0.1;
        this.displayValue = String(amount + taxAmount);
        this.updateDisplay();
    }
    
    // クリア処理
    clearAll() {
        this.displayValue = '0';
        this.firstOperand = null;
        this.waitingForSecondOperand = false;
        this.operator = null;
        this.updateDisplay();
    }
    
    // 売上追加
    addItem() {
        this.items.push({
            amount: parseFloat(this.displayValue),
            timestamp: new Date()
        });
        
        // 売上リストの更新
        this.updateItemsList();
        
        // 表示をクリア
        this.clearAll();
    }
    
    // 売上リストの更新
    updateItemsList() {
        const itemsList = document.getElementById('sale-items');
        itemsList.innerHTML = '';
        
        this.items.forEach((item, index) => {
            const li = document.createElement('li');
            li.className = 'flex justify-between items-center p-1 border-b';
            li.innerHTML = `
                <span>商品 ${index + 1}</span>
                <span>${item.amount.toFixed(2)}円</span>
            `;
            itemsList.appendChild(li);
        });
        
        // 合計金額の更新
        this.updateTotal();
    }
    
    // 合計金額の更新
    updateTotal() {
        const totalAmount = this.items.reduce((total, item) => total + item.amount, 0);
        document.getElementById('total-amount').textContent = totalAmount.toFixed(2);
    }
    
    // 表示更新
    updateDisplay() {
        document.getElementById('calculator-display').value = this.displayValue;
    }
}

// インスタンス作成
const calculator = new Calculator();

// イベントリスナー設定
document.addEventListener('DOMContentLoaded', () => {
    const keys = document.querySelector('.calculator-keys');
    
    keys.addEventListener('click', (event) => {
        const { target } = event;
        
        if (!target.matches('button')) {
            return;
        }
        
        if (target.classList.contains('operator')) {
            calculator.handleOperator(target.value);
            return;
        }
        
        if (target.classList.contains('decimal')) {
            calculator.inputDecimal();
            return;
        }
        
        if (target.classList.contains('tax')) {
            calculator.calculateTax();
            return;
        }
        
        if (target.classList.contains('all-clear')) {
            calculator.clearAll();
            return;
        }
        
        if (target.classList.contains('add-sale')) {
            calculator.addItem();
            return;
        }
        
        if (target.classList.contains('equals')) {
            calculator.handleOperator('=');
            return;
        }
        
        // 数字ボタン
        calculator.inputDigit(target.value);
    });
    
    // 売上計上ボタン
    document.getElementById('btn-submit-sale').addEventListener('click', () => {
        // AJAX送信で売上情報をサーバーに保存
        if (calculator.items.length > 0) {
            const totalAmount = calculator.items.reduce((total, item) => total + item.amount, 0);
            
            fetch('/pos/controllers/SaleController.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'addSale',
                    items: calculator.items,
                    totalAmount: totalAmount
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // 成功メッセージ表示
                    showAlert('売上を計上しました', 'success');
                    calculator.items = [];
                    calculator.updateItemsList();
                } else {
                    // エラーメッセージ表示
                    showAlert('エラーが発生しました: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('通信エラーが発生しました', 'error');
            });
        } else {
            showAlert('計上する商品がありません', 'warning');
        }
    });
});

// アラート表示用の関数
function showAlert(message, type) {
    const alertContainer = document.getElementById('alert-container');
    
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} mb-2 p-2 rounded`;
    alert.textContent = message;
    
    // 3秒後に自動的に消える
    setTimeout(() => {
        alert.remove();
    }, 3000);
    
    alertContainer.appendChild(alert);
}
</script>