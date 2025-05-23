<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>YSEレジシステム</title>
  <link rel="stylesheet" href="css/style_input.css">
</head>
<body>
  <div class="container">
    <div class="display" id="display">0</div>
    <div class="buttons">
      <!-- 数字ボタン -->
      <button onclick="appendNumber(7)">7</button>
      <button onclick="appendNumber(8)">8</button>
      <button onclick="appendNumber(9)">9</button>
      <button class="danger" onclick="clearDisplay()">AC</button>

      <button onclick="appendNumber(4)">4</button>
      <button onclick="appendNumber(5)">5</button>
      <button onclick="appendNumber(6)">6</button>
      <button class="blue" onclick="add()">＋</button>

      <button onclick="appendNumber(1)">1</button>
      <button onclick="appendNumber(2)">2</button>
      <button onclick="appendNumber(3)">3</button>
      <button class="blue" onclick="multiply()">×</button>

      <button onclick="appendNumber(0)">0</button>
      <button onclick="appendDoubleZero()">00</button>
      <button class="green" onclick="tax()">TAX</button>
      <button class="green" onclick="calculate()">＝</button>

      <button class="gray wide" onclick="location.href='sales.php'">売上</button>
      <button class="gray wide" onclick="register()">計上</button>
    </div>
  </div>

  <!-- 隠しフォーム -->
  <form id="registerForm" action="register.php" method="POST" style="display: none;">
    <input type="hidden" name="amount" id="registerAmount">
  </form>

  <script src="js/script.js"></script>
</body>
</html>
