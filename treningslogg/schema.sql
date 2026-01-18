CREATE TABLE IF NOT EXISTS treningslogg_measurements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  name VARCHAR(80) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_measurement (user_id, name),
  CONSTRAINT fk_measurement_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS treningslogg_entries (
  id INT AUTO_INCREMENT PRIMARY KEY,
  measurement_id INT NOT NULL,
  entry_date DATE NOT NULL,
  value DECIMAL(6,2) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_entry_per_day (measurement_id, entry_date),
  CONSTRAINT fk_entry_measurement FOREIGN KEY (measurement_id) REFERENCES treningslogg_measurements(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS treningslogg_remember_tokens (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  token_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_token_hash (token_hash),
  KEY idx_user_id (user_id),
  CONSTRAINT fk_remember_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
