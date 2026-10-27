-- Run this in phpMyAdmin (Import) or: mysql -u root < database.sql
CREATE DATABASE IF NOT EXISTS blockchain_tracker CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE blockchain_tracker;

DROP TABLE IF EXISTS blocks;
DROP TABLE IF EXISTS products;

CREATE TABLE products (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    product_code  VARCHAR(30)  NOT NULL UNIQUE,
    name          VARCHAR(120) NOT NULL,
    manufacturer  VARCHAR(120) NOT NULL,
    created_at    DATETIME     NOT NULL
) ENGINE=InnoDB;

-- Every row is one block. The whole table is ONE chain (block N points to block N-1).
CREATE TABLE blocks (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    block_index    INT UNSIGNED NOT NULL UNIQUE,
    product_code   VARCHAR(30)  NOT NULL,
    event_type     VARCHAR(30)  NOT NULL,
    location       VARCHAR(150) NOT NULL,
    handler        VARCHAR(120) NOT NULL,
    details        TEXT,
    timestamp      DATETIME     NOT NULL,
    previous_hash  CHAR(64)     NOT NULL,
    hash           CHAR(64)     NOT NULL,
    nonce          INT UNSIGNED NOT NULL,
    INDEX (product_code),
    FOREIGN KEY (product_code) REFERENCES products(product_code)
) ENGINE=InnoDB;
