ALTER TABLE orders
    ADD COLUMN purchased_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER realized_profit,
    ADD COLUMN strategy VARCHAR(60) NULL AFTER purchased_at,
    ADD COLUMN notes TEXT NULL AFTER strategy;
