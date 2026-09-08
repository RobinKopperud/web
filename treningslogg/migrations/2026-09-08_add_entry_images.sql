CREATE TABLE IF NOT EXISTS treningslogg_entry_images (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  entry_id INT NOT NULL,
  image_date DATE NOT NULL,
  file_name VARCHAR(160) NOT NULL,
  mime_type VARCHAR(40) NOT NULL,
  file_size INT UNSIGNED NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_image_per_user_day (user_id, image_date),
  UNIQUE KEY unique_image_per_entry (entry_id),
  CONSTRAINT fk_entry_image_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_entry_image_entry FOREIGN KEY (entry_id) REFERENCES treningslogg_entries(id) ON DELETE CASCADE
);
