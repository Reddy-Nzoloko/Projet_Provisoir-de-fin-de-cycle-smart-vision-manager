CREATE TABLE reinitialisations_mot_de_passe (
  id_reinitialisation BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_utilisateur BIGINT UNSIGNED NOT NULL,
  jeton_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL UNIQUE,
  expire_le DATETIME NOT NULL,
  date_creation TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_reinitialisations_utilisateur FOREIGN KEY (id_utilisateur)
    REFERENCES utilisateurs(id_utilisateur) ON UPDATE CASCADE ON DELETE CASCADE,
  INDEX idx_reinitialisations_utilisateur (id_utilisateur),
  INDEX idx_reinitialisations_expiration (expire_le)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;