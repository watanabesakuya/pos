<?php
// 数値を通貨形式でフォーマット
function formatCurrency($amount) {
    return CURRENCY . number_format($amount, 2);
}

// 税込価格を計算
function calculateTax($amount) {
    return $amount * TAX_RATE;
}

// 税込み合計額を計算
function calculateTotalWithTax($amount) {
    return $amount + calculateTax($amount);
}

// エラーメッセージを表示
function showError($message) {
    echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">';
    echo '<strong class="font-bold">エラー!</strong>';
    echo '<span class="block sm:inline"> ' . $message . '</span>';
    echo '</div>';
}

// 成功メッセージを表示
function showSuccess($message) {
    echo '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">';
    echo '<strong class="font-bold">成功!</strong>';
    echo '<span class="block sm:inline"> ' . $message . '</span>';
    echo '</div>';
}

// ログインチェック
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// 現在のユーザーIDを取得
function getCurrentUserId() {
    return isLoggedIn() ? $_SESSION['user_id'] : 1; // デフォルトユーザーID = 1
}