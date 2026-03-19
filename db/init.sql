CREATE DATABASE IF NOT EXISTS simulacija;
USE simulacija;

-- Tabela kojom se prati nivo privilegija i login za zaposlene
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('worker', 'admin') DEFAULT 'worker'
);

-- Tabela sa proizvodima koji mogu biti štampani
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    order_number TEXT,
    available_stock INT NOT NULL DEFAULT 0
);

-- Tabela za praćenje istorije realizovane stampe
CREATE TABLE IF NOT EXISTS print_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    print_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Nova tabela za ukupnu odštampanu količinu za svaki proizvod (Agregacija)
CREATE TABLE IF NOT EXISTS printed_quantities (
    product_id INT PRIMARY KEY,
    total_quantity INT NOT NULL DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Ubacivanje test podataka kako bi aplikacija odmah radila
INSERT IGNORE INTO users (username, password, role) VALUES 
('radnik1', '123', 'worker'),
('radnik2', '123', 'worker'),
('poslovodja', '123', 'admin');

INSERT IGNORE INTO products (id, name, order_number, available_stock) VALUES 
(1, 'Ikea hook 2xl', '26-123456789', 150),
(2, 'Slider nail', '26-987654321', 400),
(3, 'Lock 16 gnezda', '26-123456789', 220);

INSERT IGNORE INTO printed_quantities (product_id, total_quantity) VALUES 
(1, 0),
(2, 0),
(3, 0);
