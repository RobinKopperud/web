-- Legg til denne tabellen for å håndtere kontraktstilbud
CREATE TABLE kontrakter (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  venteliste_id INT NOT NULL,
  plass_id INT NOT NULL,
  anlegg_id INT NOT NULL,
  status ENUM('tilbud','signert','fullfort') NOT NULL DEFAULT 'tilbud',
  tilbudt_dato DATETIME NOT NULL,
  signert_dato DATETIME DEFAULT NULL,
  filnavn VARCHAR(255) DEFAULT NULL,
  CONSTRAINT fk_kontrakt_user FOREIGN KEY (user_id) REFERENCES users(id),
  CONSTRAINT fk_kontrakt_venteliste FOREIGN KEY (venteliste_id) REFERENCES venteliste(id) ON DELETE CASCADE,
  CONSTRAINT fk_kontrakt_plass FOREIGN KEY (plass_id) REFERENCES plasser(id),
  CONSTRAINT fk_kontrakt_anlegg FOREIGN KEY (anlegg_id) REFERENCES anlegg(id)
);
