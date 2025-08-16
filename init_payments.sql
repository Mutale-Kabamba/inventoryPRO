-- SQL for payments table for rental payments
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shop_no VARCHAR(10) NOT NULL,
    client VARCHAR(100) NOT NULL,
    month VARCHAR(10) NOT NULL,
    year INT NOT NULL,
    date_paid DATE NOT NULL,
    who_paid VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_payments_shop_month_year ON payments(shop_no, month, year);
