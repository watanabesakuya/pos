-- 既存のsalesテーブル
CREATE TABLE IF NOT EXISTS sales (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sales_at DATETIME NOT NULL,
  amount INT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 商品マスターテーブル
CREATE TABLE IF NOT EXISTS products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  jan_code VARCHAR(20) NOT NULL UNIQUE COMMENT 'JANコード（バーコード）',
  product_name VARCHAR(255) NOT NULL COMMENT '商品名',
  price INT NOT NULL COMMENT '価格（円）',
  is_active BOOLEAN DEFAULT TRUE COMMENT '有効フラグ',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_jan_code (jan_code),
  INDEX idx_active (is_active)
) COMMENT='商品マスターテーブル';

-- 売上明細テーブル（売上の詳細を記録）
CREATE TABLE IF NOT EXISTS sales_details (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sales_id INT NOT NULL COMMENT '売上ID',
  jan_code VARCHAR(20) NULL COMMENT 'JANコード（手動入力の場合はNULL）',
  product_name VARCHAR(255) NULL COMMENT '商品名（スナップショット）',
  price INT NOT NULL COMMENT '販売価格',
  quantity INT DEFAULT 1 COMMENT '数量',
  subtotal INT NOT NULL COMMENT '小計',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (sales_id) REFERENCES sales(id) ON DELETE CASCADE,
  INDEX idx_sales_id (sales_id),
  INDEX idx_jan_code (jan_code)
) COMMENT='売上明細テーブル';

-- サンプルデータの追加
INSERT INTO products (jan_code, product_name, price) VALUES
('4901234567890', 'サンプル商品A', 150),
('4987654321098', 'サンプル商品B', 280),
('4901111222333', 'サンプル商品C', 98),
('4902345678901', 'ドリンクX', 120),
('4903456789012', 'スナック菓子Y', 180)
ON DUPLICATE KEY UPDATE
  product_name = VALUES(product_name),
  price = VALUES(price),
  updated_at = CURRENT_TIMESTAMP;