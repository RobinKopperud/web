-- SQL schema for manual crypto order tracker
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asset VARCHAR(20) NOT NULL,
    side ENUM('BUY','SELL') NOT NULL DEFAULT 'BUY',
    quantity DECIMAL(18,8) NOT NULL,
    entry_price DECIMAL(18,8) NOT NULL,
    fee DECIMAL(18,8) NOT NULL DEFAULT 0,
    status ENUM('OPEN','CLOSED') NOT NULL DEFAULT 'OPEN',
    remaining_quantity DECIMAL(18,8) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    closed_at DATETIME NULL,
    realized_profit DECIMAL(18,8) NULL
);

CREATE TABLE IF NOT EXISTS order_closures (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    close_quantity DECIMAL(18,8) NOT NULL,
    close_price DECIMAL(18,8) NOT NULL,
    fee DECIMAL(18,8) NOT NULL DEFAULT 0,
    profit DECIMAL(18,8) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);
