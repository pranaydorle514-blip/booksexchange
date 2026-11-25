USE bookswap;

ALTER TABLE users MODIFY password VARCHAR(255) DEFAULT NULL;
UPDATE users
SET phone = CONCAT('+91', phone)
WHERE phone REGEXP '^[0-9]{10}$';
ALTER TABLE users MODIFY phone VARCHAR(16) DEFAULT NULL;
ALTER TABLE users ADD UNIQUE KEY uq_users_phone (phone);
