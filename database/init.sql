CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    description TEXT,
    stock INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Data Sampel Produk
INSERT INTO products (name, description, price, stock) VALUES
('Keyboard Mekanikal', 'Keyboard mekanikal switch blue', 500000.00, 25),
('Mouse Wireless', 'Mouse wireless dengan sensor optimal', 350000.00, 10),
('Headset Gaming', 'Headset gaming dengan kualitas audio terbaik', 800000.00, 15),
('Monitor Gaming 24 Inch', 'Monitor 144Hz Full HD IPS Panel', 2100000.00, 8),
('Webcam Full HD', 'Webcam 1080p dengan built-in microphone', 450000.00, 12),
('USB Hub Type-C', 'USB Hub 7 in 1 HDMI Thunderbolt', 300000.00, 30),
('Speaker Bluetooth', 'Speaker portable dengan bass mantap', 650000.00, 20),
('Stand Laptop Aluminium', 'Stand laptop ergonomis bahan alumunium', 180000.00, 50);