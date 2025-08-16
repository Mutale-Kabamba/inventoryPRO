-- SQL for SPF Shopping Complex Rentals Table
CREATE TABLE IF NOT EXISTS rentals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shop_no VARCHAR(10) NOT NULL,
    client VARCHAR(100) NOT NULL,
    contact VARCHAR(30),
    month VARCHAR(10) NOT NULL,
    year INT NOT NULL DEFAULT 2025,
    status ENUM('PAID','UNPAID') NOT NULL DEFAULT 'UNPAID',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Optional: Add index for faster queries
CREATE INDEX idx_rentals_shop_month_year ON rentals(shop_no, month, year);
