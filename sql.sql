CREATE TABLE garants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    amount_sponsored decimal(10,0) NOT NULL,
    phone VARCHAR(20) UNIQUE NOT NULL,
    balance DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    donate_id INT NOT NULL
);

CREATE TABLE stock_monthly_payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    garant_id INT NOT NULL,
    month VARCHAR(20) NULL,
    des VARCHAR(255) NULL,
    amount_paid DECIMAL(10, 2) NOT NULL,
    due_amount DECIMAL(10,2) NULL DEFAULT '0.00',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (garant_id) REFERENCES garants(id)
);