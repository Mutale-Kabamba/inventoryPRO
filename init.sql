-- Inventory Management System Database Schema
-- Run this script to create the database and tables

CREATE DATABASE IF NOT EXISTS inventory_db;
USE inventory_db;

-- Users table for authentication
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'employee') DEFAULT 'employee',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Products table
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    category VARCHAR(50) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    branches TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Purchases table
CREATE TABLE IF NOT EXISTS purchases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    supplier VARCHAR(100) NOT NULL,
    branch VARCHAR(50) NOT NULL,
    damages INT DEFAULT 0,
    purchase_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Sales table
CREATE TABLE IF NOT EXISTS sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    branch VARCHAR(50) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    sale_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Stock transfers table
CREATE TABLE IF NOT EXISTS transfers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    source_branch VARCHAR(50) NOT NULL,
    dest_branch VARCHAR(50) NOT NULL,
    transfer_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Expenses table
CREATE TABLE IF NOT EXISTS expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category VARCHAR(50) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    branch VARCHAR(50) NOT NULL,
    description TEXT,
    expense_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin user (password: admin123)
-- Note: This inserts a hashed password. Use password 'admin123' to login
INSERT INTO users (username, password, role) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Insert sample products
INSERT INTO products (name, category, price, branches) VALUES
('Makoro Sunrise', 'Brick', 15.00, 'Livingstone,Chisamba,Lusaka'),
('Makoro Granite', 'Brick', 15.00, 'Livingstone,Chisamba,Lusaka'),
('Tank 10000L', 'Water Storage', 25000.00, 'Livingstone,Chisamba'),
('Tank 5000L', 'Water Storage', 13000.00, 'Livingstone,Chisamba'),
('Tank 2500L', 'Water Storage', 7000.00, 'Livingstone,Chisamba'),
('Tank 1000L', 'Water Storage', 5000.00, 'Livingstone,Chisamba'),
('Tank 500L', 'Water Storage', 4000.00, 'Livingstone,Chisamba'),
('Water Trough', 'Water Storage', 5000.00, 'Livingstone,Chisamba'),
('Wash Trough', 'Water Storage', 2500.00, 'Livingstone,Chisamba'),
('Six Conners', 'Paver', 190.00, 'Livingstone'),
('Four Conners', 'Paver', 190.00, 'Livingstone'),
('Diamond', 'Paver', 190.00, 'Livingstone'),
('Y-Model', 'Paver', 190.00, 'Livingstone'),
('K-Model', 'Paver', 190.00, 'Livingstone'),
('Fish Model', 'Paver', 190.00, 'Livingstone'),
('Shampool', 'Paver', 190.00, 'Livingstone'),
('Kerb 50cm', 'Kerb Stone', 100.00, 'Livingstone'),
('Kerb 100cm', 'Kerb Stone', 190.00, 'Livingstone'),
('6" Block', 'Block', 100.00, 'Livingstone');