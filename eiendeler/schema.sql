-- SQL schema for personlig eiendels- og verdioversikt
CREATE TABLE IF NOT EXISTS assets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category ENUM('property','crypto','cash','other') NOT NULL,
    name VARCHAR(120) NOT NULL,
    provider VARCHAR(120) NULL,
    gross_value DECIMAL(18,2) NOT NULL DEFAULT 0,
    ownership_percent DECIMAL(5,2) NOT NULL DEFAULT 100,
    currency VARCHAR(10) NOT NULL DEFAULT 'NOK',
    notes TEXT NULL,
    valuation_date DATE NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_assets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
