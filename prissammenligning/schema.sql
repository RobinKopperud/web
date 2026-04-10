-- Minimal database-skjema for tilbudssammenligning MVP.
-- Oppretter kun de tabellene som er spesifisert.

CREATE TABLE IF NOT EXISTS stores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
);

CREATE TABLE IF NOT EXISTS imports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    created_at DATETIME NOT NULL
);

CREATE TABLE IF NOT EXISTS import_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    import_id INT NOT NULL,
    store_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    FOREIGN KEY (import_id) REFERENCES imports(id),
    FOREIGN KEY (store_id) REFERENCES stores(id)
);

CREATE TABLE IF NOT EXISTS offers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    import_file_id INT NOT NULL,
    store_id INT NOT NULL,
    raw_name VARCHAR(255) NOT NULL,
    normalized_name VARCHAR(255) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    unit VARCHAR(50) NULL,
    match_group VARCHAR(255) NULL,
    confidence DECIMAL(3,2) NOT NULL DEFAULT 0.00,
    FOREIGN KEY (import_file_id) REFERENCES import_files(id),
    FOREIGN KEY (store_id) REFERENCES stores(id)
);
