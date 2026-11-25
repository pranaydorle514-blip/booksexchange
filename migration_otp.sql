USE bookswap;

ALTER TABLE users MODIFY password VARCHAR(255) DEFAULT NULL;
UPDATE users
SET phone = CONCAT('+91', phone)
WHERE phone REGEXP '^[0-9]{10}$';
ALTER TABLE users MODIFY phone VARCHAR(16) DEFAULT NULL;
ALTER TABLE users ADD UNIQUE KEY uq_users_phone (phone);

CREATE TABLE IF NOT EXISTS phone_otp_challenges (
  phone VARCHAR(16) PRIMARY KEY,
  otp_hash VARCHAR(255) DEFAULT NULL,
  purpose ENUM('login','register') NOT NULL DEFAULT 'login',
  expires_at DATETIME DEFAULT NULL,
  last_sent_at DATETIME DEFAULT NULL,
  window_started_at DATETIME DEFAULT NULL,
  sent_count TINYINT UNSIGNED NOT NULL DEFAULT 0,
  failed_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB;
