CREATE DATABASE IF NOT EXISTS bookswap CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bookswap;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) DEFAULT NULL,
  college VARCHAR(180) DEFAULT NULL,
  phone VARCHAR(16) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_users_phone (phone)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS books (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  title VARCHAR(180) NOT NULL,
  author VARCHAR(150) NOT NULL,
  subject VARCHAR(100) NOT NULL,
  branch VARCHAR(120) DEFAULT NULL,
  semester VARCHAR(60) DEFAULT NULL,
  book_condition ENUM('Like New','Good','Used') NOT NULL DEFAULT 'Good',
  price DECIMAL(10,2) DEFAULT NULL,
  description TEXT,
  exchange_available TINYINT(1) NOT NULL DEFAULT 1,
  status ENUM('available','reserved','sold') NOT NULL DEFAULT 'available',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_books_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_books_status_subject (status, subject),
  INDEX idx_books_title (title)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS book_requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  book_id INT NOT NULL,
  requester_id INT NOT NULL,
  request_type ENUM('buy','exchange') NOT NULL DEFAULT 'buy',
  message TEXT,
  status ENUM('pending','accepted','declined') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_request_book FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
  CONSTRAINT fk_request_user FOREIGN KEY (requester_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_request_requester (requester_id),
  INDEX idx_request_status (status)
) ENGINE=InnoDB;
