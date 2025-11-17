-- Lagre faste priser for de ulike plassetypene.
CREATE TABLE IF NOT EXISTS plass_priser (
  type ENUM('ute','garasje') PRIMARY KEY,
  depositum INT NOT NULL,
  leiepris INT NOT NULL
);

INSERT INTO plass_priser (type, depositum, leiepris) VALUES
  ('ute', 20000, 150),
  ('garasje', 20000, 230)
ON DUPLICATE KEY UPDATE
  depositum = VALUES(depositum),
  leiepris = VALUES(leiepris);
