// ===== sales/export_csv.php =====

<?php
require_once '../config/config.php';
require_login();

// 権限チェック - マネージャー以上のみアクセス可能
if (!has_permission('manager')) {
    $_SESSION['error'] = '売上管理へのアクセス権限がありません';
    header('Location: /');
    exit;
}

// データベース接続
require_once '../config/database.php';
require_once '../models/Sale.php';

$db = new Database();
$conn = $db->getConnection();

$sale = new Sale($conn);

// レポートタイプ（日別/月別）
$reportType = $_GET['type'] ?? 'daily';

// 期間設定
$currentYear = date('Y');
$currentMonth = date('m');

if ($reportType === 'monthly') {
    // 月別レポートの場合
    $year = isset($_GET['year']) ? (int)$_GET['year'] : $currentYear;
    
    // 有効な年かチェック（過去5年から現在までの範囲）
    $minYear = $currentYear - 5;
    if ($year < $minYear || $year > $currentYear) {
        $year = $currentYear;
    }
    
    // 月別サマリーを取得
    $summary = $sale->getMonthlySummary($year);
    
    // ファイル名
    $filename = sprintf('monthly_sales_%04d.csv', $year);
} else {
    // 日別レポートの場合
    $year = isset($_GET['year']) ? (int)$_GET['year'] : $currentYear;
    $month = isset($_GET['month']) ? (int)$_GET['month'] : $currentMonth;
    
    // 有効な年月かチェック
    $minYear = $currentYear - 5;
    if ($year < $minYear || $year > $currentYear) {
        $year = $currentYear;
    }
    
    if ($month < 1 || $month > 12) {
        $month = $currentMonth;
    }
    
    // 月の日数を取得
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    
    // 日付範囲を設定
    $startDate = sprintf('%04d-%02d-01', $year, $month);
    $endDate = sprintf('%04d-%02d-%02d', $year, $month, $daysInMonth);
    
    // 日別サマリーを取得
    $summary = $sale->getDailySummary($startDate . ' 00:00:00', $endDate . ' 23:59:59');
    
    // ファイル名
    $filename = sprintf('daily_sales_%04d_%02d.csv', $year, $month);
}

// CSVヘッダー設定
set_csv_headers($filename);

// 文字化け対策
echo "\xEF\xBB\xBF"; // UTF-8 BOM

// CSVデータの作成
$output = fopen('php://output', 'w');

// ヘッダー行
if ($reportType === 'monthly') {
    fputcsv($output, ['月', '取引数', '税抜金額', '消費税', '合計金額', '平均取引額']);
} else {
    fputcsv($output, ['日付', '曜日', '取引数', '税抜金額', '消費税', '合計金額', '平均取引額']);
}

// データ行
foreach ($summary as $s) {
    if ($reportType === 'monthly') {
        // 月別データ
        $row = [
            $s['month'] . '月',
            $s['transaction_count'],
            $s['total_amount'],
            $s['total_tax'],
            $s['total_sales'],
            $s['transaction_count'] > 0 ? $s['total_sales'] / $s['transaction_count'] : 0
        ];
    } else {
        // 日別データ
        $row = [
            format_date($s['date'], 'Y/m/d'),
            get_day_of_week($s['date']),
            $s['transaction_count'],
            $s['total_amount'],
            $s['total_tax'],
            $s['total_sales'],
            $s['transaction_count'] > 0 ? $s['total_sales'] / $s['transaction_count'] : 0
        ];
    }
    
    fputcsv($output, $row);
}

// 合計行
$totalSales = 0;
$totalTax = 0;
$totalAmount = 0;
$totalTransactions = 0;

foreach ($summary as $s) {
    $totalSales += (float)$s['total_sales'];
    $totalTax += (float)$s['total_tax'];
    $totalAmount += (float)$s['total_amount'];
    $totalTransactions += (int)$s['transaction_count'];
}

$averageTransaction = $totalTransactions > 0 ? $totalSales / $totalTransactions : 0;

if ($reportType === 'monthly') {
    fputcsv($output, ['合計', $totalTransactions, $totalAmount, $totalTax, $totalSales, $averageTransaction]);
} else {
    fputcsv($output, ['合計', '', $totalTransactions, $totalAmount, $totalTax, $totalSales, $averageTransaction]);
}

fclose($output);
exit;