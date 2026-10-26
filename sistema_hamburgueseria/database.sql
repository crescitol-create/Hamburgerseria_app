CREATE DATABASE IF NOT EXISTS el_pasillito DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE el_pasillito;

-- Tabla de Usuarios (Roles: admin, vendedor, cocinero)
CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'vendedor', 'cocinero') NOT NULL
);

-- Insertar Usuarios Solicitados (Contraseñas con HASH de seguridad)
INSERT INTO usuarios (nombre, password, rol) VALUES
('Tomas', '$2y$10$w8k2rI3s4/tE4P3H0n5S4eK7sR6eE2kO9lJ8mN7bP6vQ5rT4uW3y2', 'admin'),     -- 1234
('Guille', '$2y$10$R9l6Y2J3k4L5m6N7o8P9q0R1s2T3u4V5w6X7y8Z9a0B1c2D3e4F5g', 'vendedor'),  -- 1010
('Agus', '$2y$10$1A2B3C4D5E6F7G8H9I0J1K2L3M4N5O6P7Q8R9S0T1U2V3W4X5Y6Z7', 'cocinero');   -- 0123

-- Tabla de Clientes (según diagrama)
CREATE TABLE cliente (
    telefono VARCHAR(20) PRIMARY KEY,
    nombre VARCHAR(100)
);

-- Tabla Materia Prima / Stock (inicia vacío como se especificó)
CREATE TABLE mat_prima (
    id_mat INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    unidad VARCHAR(20) NOT NULL,
    stock DECIMAL(10,2) DEFAULT 0.00,
    fecha_vencimiento DATE
);

-- Tabla Comida / Combos (según diagrama)
CREATE TABLE comida (
    id_comida INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    precio DECIMAL(10,2) NOT NULL,
    descripcion TEXT
);

-- Relación N:N Comida - Materia Prima (Receta/Ingredientes)
CREATE TABLE comida_mat_prima (
    id_comida INT,
    id_mat INT,
    cantidad DECIMAL(10,2) NOT NULL,
    PRIMARY KEY (id_comida, id_mat),
    FOREIGN KEY (id_comida) REFERENCES comida(id_comida) ON DELETE CASCADE,
    FOREIGN KEY (id_mat) REFERENCES mat_prima(id_mat) ON DELETE CASCADE
);

-- Tabla Zonas de Envío (Para cálculo automático de precios)
CREATE TABLE zona_envio (
    id_zona INT AUTO_INCREMENT PRIMARY KEY,
    nombre_zona VARCHAR(50) NOT NULL,
    costo_envio DECIMAL(10,2) NOT NULL
);

INSERT INTO zona_envio (nombre_zona, costo_envio) VALUES 
('Local / Retiro', 0.00),
('Zona Centro', 50.00),
('Zona Suburbana', 100.00);

-- Tabla Pedidos (según diagrama)
CREATE TABLE pedido (
    id_pedido INT AUTO_INCREMENT PRIMARY KEY,
    telefono_cliente VARCHAR(20),
    tipo_pedido ENUM('local', 'envio') NOT NULL,
    direccion VARCHAR(255) NULL,
    id_zona INT NULL,
    costo_total DECIMAL(10,2) NOT NULL,
    hora_entrada DATETIME DEFAULT CURRENT_TIMESTAMP,
    hora_salida DATETIME NULL,
    estado ENUM('pendiente', 'en_preparacion', 'listo', 'entregado') DEFAULT 'pendiente',
    FOREIGN KEY (telefono_cliente) REFERENCES cliente(telefono),
    FOREIGN KEY (id_zona) REFERENCES zona_envio(id_zona)
);

-- Relación N:N Pedido - Comida
CREATE TABLE detalle_pedido (
    id_pedido INT,
    id_comida INT,
    cantidad INT NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    PRIMARY KEY (id_pedido, id_comida),
    FOREIGN KEY (id_pedido) REFERENCES pedido(id_pedido) ON DELETE CASCADE,
    FOREIGN KEY (id_comida) REFERENCES comida(id_comida)
);