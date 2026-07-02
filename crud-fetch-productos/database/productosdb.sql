CREATE DATABASE IF NOT EXISTS productosdb
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE productosdb;

CREATE TABLE IF NOT EXISTS productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(20) NOT NULL,
    producto VARCHAR(100) NOT NULL,
    precio DECIMAL(10,2) NOT NULL,
    cantidad INT NOT NULL,
    UNIQUE KEY uk_productos_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO productos (codigo, producto, precio, cantidad) VALUES
('P-001', 'Teclado mecanico', 39.99, 12),
('P-002', 'Mouse inalambrico', 18.50, 20),
('P-003', 'Monitor LED 24 pulgadas', 149.90, 5)
ON DUPLICATE KEY UPDATE
    producto = VALUES(producto),
    precio = VALUES(precio),
    cantidad = VALUES(cantidad);
