CREATE TABLE IF NOT EXISTS asset_valuations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asset_id INT NOT NULL,
    user_id INT NOT NULL,
    valuation_date DATE NOT NULL,
    gross_value DECIMAL(18,2) NOT NULL DEFAULT 0,
    loan_amount DECIMAL(18,2) NOT NULL DEFAULT 0,
    ownership_percent DECIMAL(5,2) NOT NULL DEFAULT 100,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_asset_valuation_day (asset_id, valuation_date),
    KEY idx_asset_valuations_user_date (user_id, valuation_date),
    CONSTRAINT fk_asset_valuations_asset FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE CASCADE,
    CONSTRAINT fk_asset_valuations_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
