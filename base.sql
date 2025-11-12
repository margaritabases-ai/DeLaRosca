
-- 1. Eliminación segura de objetos existentes (en orden inverso: hijos antes que padres)
DROP TABLE IF EXISTS Telefono, Impresora, Computadora, NoBreak, 
    Empleado_Permanente, Empleado_Temporal, Empleado, 
    Departamento_Creativo, Departamento_Digital, Departamento_Investigacion, Departamento;

-- =====================================================
-- SECCIÓN 1: DEPARTAMENTO (PADRES Y ESPECIALIZACIÓN)
-- =====================================================

-- Tabla Padre: Departamento
CREATE TABLE Departamento (
  id_departamento SERIAL PRIMARY KEY,
  nombre_departamento VARCHAR(100) NOT NULL,
  presupuesto NUMERIC(15, 2) DEFAULT 0.00,
  
  -- Restricción CHECK: Presupuesto no puede ser negativo
  CONSTRAINT chk_presupuesto_positivo CHECK (presupuesto >= 0.00)
);

-- Subclase: Departamento Creativo (Dependencia Fuerte)
CREATE TABLE Departamento_Creativo (
  id_departamento INTEGER PRIMARY KEY,
  software_diseno VARCHAR(255),
  
  CONSTRAINT fk_depto_creativo
    FOREIGN KEY (id_departamento)
    REFERENCES Departamento (id_departamento)
    ON DELETE CASCADE -- Si se elimina el padre, se elimina el hijo
    ON UPDATE CASCADE 
);

-- Subclase: Departamento Digital (Dependencia Fuerte)
CREATE TABLE Departamento_Digital (
  id_departamento INTEGER PRIMARY KEY,
  plataforma_web VARCHAR(100),
  redes_sociales VARCHAR(255),
  
  CONSTRAINT fk_depto_digital
    FOREIGN KEY (id_departamento)
    REFERENCES Departamento (id_departamento)
    ON DELETE CASCADE
    ON UPDATE CASCADE
);

-- Subclase: Departamento Investigacion (Dependencia Fuerte)
CREATE TABLE Departamento_Investigacion (
  id_departamento INTEGER PRIMARY KEY,
  herramientas_analisis VARCHAR(255),
  
  CONSTRAINT fk_depto_investigacion
    FOREIGN KEY (id_departamento)
    REFERENCES Departamento (id_departamento)
    ON DELETE CASCADE
    ON UPDATE CASCADE
);

-- =====================================================
-- SECCIÓN 2: EMPLEADO (PADRES Y HERENCIA)
-- =====================================================

-- Tabla Padre: Empleado
CREATE TABLE Empleado (
  id_empleado SERIAL PRIMARY KEY,
  nombre VARCHAR(50) NOT NULL,
  apellido VARCHAR(50) NOT NULL,
  correo VARCHAR(100) UNIQUE NOT NULL, -- UNIQUE
  id_departamento INTEGER, -- Relación Opcional
  
  CONSTRAINT fk_departamento
    FOREIGN KEY (id_departamento)
    REFERENCES Departamento (id_departamento)
    ON DELETE SET NULL -- Relación Opcional
    ON UPDATE CASCADE,

  -- Restricción CHECK: Formato básico de correo
  CONSTRAINT chk_correo_format CHECK (correo LIKE '%@%')
);

-- Subclase: Empleado Permanente (Dependencia Fuerte)
CREATE TABLE Empleado_Permanente (
  id_empleado INTEGER PRIMARY KEY,
  salario_base NUMERIC(10, 2) NOT NULL, -- NOT NULL
  
  -- Restricción CHECK: Salario debe ser positivo
  CONSTRAINT chk_salario_positivo CHECK (salario_base > 0.00),

  CONSTRAINT fk_empleado_perm
    FOREIGN KEY (id_empleado)
    REFERENCES Empleado (id_empleado)
    ON DELETE CASCADE -- Dependencia Fuerte
    ON UPDATE CASCADE
);

-- Subclase: Empleado Temporal (Dependencia Fuerte)
CREATE TABLE Empleado_Temporal (
  id_empleado INTEGER PRIMARY KEY,
  ganancia_proyecto NUMERIC(10, 2),
  fecha_inicio DATE NOT NULL DEFAULT CURRENT_DATE, -- DEFAULT
  fecha_termino DATE,
  
  -- Restricción CHECK: Fecha de término posterior a la de inicio
  CONSTRAINT chk_fechas CHECK (fecha_termino IS NULL OR fecha_termino > fecha_inicio),

  CONSTRAINT fk_empleado_temp
    FOREIGN KEY (id_empleado)
    REFERENCES Empleado (id_empleado)
    ON DELETE CASCADE
    ON UPDATE CASCADE
);

-- =====================================================
-- SECCIÓN 3: INVENTARIO (ENTIDADES MODERADAS Y DÉBILES)
-- =====================================================

-- Entidad NoBreak (UPS)
CREATE TABLE NoBreak (
  id_nobreak SERIAL PRIMARY KEY,
  marca VARCHAR(50) NOT NULL,
  modelo VARCHAR(100) NOT NULL
);

-- Entidad Computadora
CREATE TABLE Computadora (
  id_computadora SERIAL PRIMARY KEY,
  procesador VARCHAR(100) NOT NULL,
  ram VARCHAR(20) NOT NULL,
  almacenamiento VARCHAR(20) NOT NULL,
  tipo_almacenamiento VARCHAR(10) NOT NULL, 
  estado VARCHAR(20) NOT NULL DEFAULT 'ACTIVO', -- DEFAULT
  id_empleado INTEGER UNIQUE, -- FK (Relación 1:1 Opcional)
  id_nobreak INTEGER UNIQUE, -- FK (Relación 1:1 Opcional)

  -- Restricciones CHECK
  CONSTRAINT chk_estado_comp CHECK (estado IN ('ACTIVO', 'EN MANTENIMIENTO', 'BAJA', 'STOCK')),
  
  CONSTRAINT fk_empleado
    FOREIGN KEY (id_empleado)
    REFERENCES Empleado (id_empleado)
    ON DELETE SET NULL -- Si el empleado se va, la PC queda en stock
    ON UPDATE CASCADE,
  CONSTRAINT fk_nobreak
    FOREIGN KEY (id_nobreak)
    REFERENCES NoBreak (id_nobreak)
    ON DELETE SET NULL
    ON UPDATE CASCADE
);

-- Entidad Telefono
CREATE TABLE Telefono (
  id_telefono SERIAL PRIMARY KEY,
  marca VARCHAR(50) NOT NULL,
  modelo VARCHAR(100) NOT NULL,
  numero VARCHAR(20) UNIQUE NOT NULL, -- UNIQUE
  ip INET UNIQUE, -- Tipo INET y UNIQUE
  extension VARCHAR(10),
  estado VARCHAR(20) NOT NULL DEFAULT 'ACTIVO', -- DEFAULT
  id_empleado INTEGER, -- FK (Relación 1:N Opcional)

  -- Restricción CHECK
  CONSTRAINT chk_estado_tel CHECK (estado IN ('ACTIVO', 'EN MANTENIMIENTO', 'BAJA', 'STOCK')),

  CONSTRAINT fk_empleado
    FOREIGN KEY (id_empleado)
    REFERENCES Empleado (id_empleado)
    ON DELETE SET NULL
    ON UPDATE CASCADE
);

-- Entidad Impresora
CREATE TABLE Impresora (
  id_impresora SERIAL PRIMARY KEY,
  marca VARCHAR(50) NOT NULL,
  modelo VARCHAR(100) NOT NULL,
  ip INET UNIQUE, -- Tipo INET y UNIQUE
  estado VARCHAR(20) NOT NULL DEFAULT 'ACTIVO', -- DEFAULT
  id_empleado INTEGER, -- FK (Relación 1:N Opcional)
  
  -- Restricción CHECK
  CONSTRAINT chk_estado_imp CHECK (estado IN ('ACTIVO', 'EN MANTENIMIENTO', 'BAJA', 'STOCK')),

  CONSTRAINT fk_empleado
    FOREIGN KEY (id_empleado)
    REFERENCES Empleado (id_empleado)
    ON DELETE SET NULL
    ON UPDATE CASCADE
);


-- 1. DEPARTAMENTOS (Superclase y Subclases)
INSERT INTO Departamento (nombre_departamento, presupuesto) VALUES
('Recursos Humanos', 15000.00), -- ID 1
('Diseño y Creatividad', 50000.00), -- ID 2 (Subclase Creativo)
('Marketing Digital', 65000.00), -- ID 3 (Subclase Digital)
('Investigacion y Desarrollo', 90000.00), -- ID 4 (Subclase Investigacion)
('Administracion General', 20000.00); -- ID 5

-- Especializaciones
INSERT INTO Departamento_Creativo (id_departamento, software_diseno) VALUES
(2, 'Adobe Creative Suite, Figma');
INSERT INTO Departamento_Digital (id_departamento, plataforma_web, redes_sociales) VALUES
(3, 'WordPress, Shopify', 'Instagram, X, LinkedIn');
INSERT INTO Departamento_Investigacion (id_departamento, herramientas_analisis) VALUES
(4, 'Python/R, Tableau, PowerBI');


-- 2. EMPLEADOS (Superclase y Herencia)
-- Permanente (ID 1-3)
INSERT INTO Empleado (nombre, apellido, correo, id_departamento) VALUES
('Elena', 'Gutiérrez', 'e.gutierrez@corp.com', 1), -- ID 1: RRHH
('Ricardo', 'Sánchez', 'r.sanchez@corp.com', 2), -- ID 2: Creativo
('Sofia', 'Méndez', 's.mendez@corp.com', 3); -- ID 3: Digital
INSERT INTO Empleado_Permanente (id_empleado, salario_base) VALUES
(1, 45000.00),
(2, 60000.00),
(3, 58000.00);

-- Temporal (ID 4-5)
INSERT INTO Empleado (nombre, apellido, correo, id_departamento) VALUES
('Javier', 'Reyes', 'j.reyes@corp.com', 4), -- ID 4: Investigacion
('Andrea', 'Flores', 'a.flores@corp.com', NULL); -- ID 5: Temporal (Sin Depto asignado, caso límite)
INSERT INTO Empleado_Temporal (id_empleado, ganancia_proyecto, fecha_inicio, fecha_termino) VALUES
(4, 12000.00, '2025-09-01', '2026-03-01'),
(5, NULL, '2025-10-15', NULL); -- Caso límite: ganancia y fecha_termino nula


-- 3. NOBREAKS (UPS)
INSERT INTO NoBreak (marca, modelo) VALUES
('APC', 'Back-UPS-1000'), -- ID 1: Asignado a PC 1
('CyberPower', 'CP1500PFCLCD'), -- ID 2: Asignado a PC 2
('Tripp Lite', 'OMNI900LCD'), -- ID 3: Asignado a PC 3
('APC', 'Back-UPS-650VA'), -- ID 4: En Stock
('CyberPower', 'CP850PFCLCD'); -- ID 5: En Stock


-- 4. COMPUTADORAS (Asignación 1:1)
INSERT INTO Computadora (procesador, ram, almacenamiento, tipo_almacenamiento, id_empleado, id_nobreak) VALUES
('Intel Core i7 Gen13', '32 GB', '1 TB', 'SSD', 1, 1), -- PC Asignada y con NoBreak (Elena)
('AMD Ryzen 9', '64 GB', '2 TB', 'NVMe', 2, 2), -- PC Asignada y con NoBreak (Ricardo)
('Intel Core i5 Gen11', '16 GB', '512 GB', 'SSD', 3, 3), -- PC Asignada y con NoBreak (Sofia)
('AMD Ryzen 5', '8 GB', '512 GB', 'HDD', 4, NULL), -- PC Asignada, sin NoBreak (Javier)
('Intel Core i3 Gen10', '8 GB', '256 GB', 'SSD', NULL, NULL); -- PC en Stock (Caso límite: Ambos FK NULL)


-- 5. IMPRESORAS (Asignación 1:N)
INSERT INTO Impresora (marca, modelo, ip, id_empleado, estado) VALUES
('HP', 'LaserJet Pro M404dn', '192.168.1.50', 1, 'ACTIVO'), -- Asignada a Elena
('Epson', 'EcoTank L3110', '192.168.1.51', 1, 'ACTIVO'), -- Asignada a Elena (1:N funcionando)
('Canon', 'MAXIFY MB2720', '192.168.1.52', 2, 'EN MANTENIMIENTO'), -- Asignada a Ricardo
('Brother', 'HL-L2350DW', '192.168.1.53', NULL, 'STOCK'), -- Impresora en Stock
('HP', 'Color LaserJet Pro M479', '192.168.1.54', NULL, 'BAJA'); -- Impresora de Baja


-- 6. TELÉFONOS (Asignación 1:N)
INSERT INTO Telefono (marca, modelo, numero, ip, extension, id_empleado) VALUES
('Cisco', '8841', '5510001001', '10.0.0.101', '101', 1), -- Asignado a Elena
('Polycom', 'VVX 450', '5510001002', '10.0.0.102', '102', 2), -- Asignado a Ricardo
('Cisco', '8841', '5510001003', '10.0.0.103', '103', 2), -- Asignado a Ricardo (1:N funcionando)
('Xiaomi', 'Redmi Note 10', '5510001004', NULL, NULL, 5), -- Asignado a Andrea, móvil (sin IP/ext)
('Grandstream', 'GXP2130', '5510001005', '10.0.0.105', '105', NULL); -- Teléfono en Stock

-- Borramos las vistas si ya existen, para poder actualizarlas
DROP VIEW IF EXISTS v_empleados_completos, v_departamentos_completos, v_equipos_asignados;

-- VISTA 1: Empleados Completos
-- Esta vista une Empleado con sus tablas hijas (Permanente y Temporal)
CREATE VIEW v_empleados_completos AS
SELECT
    e.id_empleado,
    e.nombre,
    e.apellido,
    e.correo,
    d.nombre_departamento AS departamento,
    -- Usamos COALESCE para mostrar el tipo correcto y el salario/ganancia
    CASE 
        WHEN ep.id_empleado IS NOT NULL THEN 'Permanente'
        WHEN et.id_empleado IS NOT NULL THEN 'Temporal'
        ELSE 'N/A' 
    END AS tipo_contrato,
    COALESCE(ep.salario_base, et.ganancia_proyecto) AS remuneracion,
    et.fecha_inicio,
    et.fecha_termino
FROM 
    Empleado e
LEFT JOIN 
    Departamento d ON e.id_departamento = d.id_departamento
LEFT JOIN 
    Empleado_Permanente ep ON e.id_empleado = ep.id_empleado
LEFT JOIN 
    Empleado_Temporal et ON e.id_empleado = et.id_empleado;

-- VISTA 2: Departamentos Completos
-- Esta vista une Departamento con sus tablas hijas (Creativo, Digital, Investigacion)
CREATE VIEW v_departamentos_completos AS
SELECT 
    d.id_departamento,
    d.nombre_departamento,
    d.presupuesto,
    -- Mostramos el software/plataforma de la tabla hija correspondiente
    COALESCE(dc.software_diseno, dd.plataforma_web, di.herramientas_analisis) AS detalle_especializacion
FROM 
    Departamento d
LEFT JOIN 
    Departamento_Creativo dc ON d.id_departamento = dc.id_departamento
LEFT JOIN 
    Departamento_Digital dd ON d.id_departamento = dd.id_departamento
LEFT JOIN 
    Departamento_Investigacion di ON d.id_departamento = di.id_departamento;

-- VISTA 3: Resumen de Equipos Asignados
-- Esta vista nos dice qué equipo tiene cada empleado
-- Borra la vista si existe (aunque haya fallado, por si acaso)
DROP VIEW IF EXISTS v_equipos_asignados;

-- CREA LA VISTA CORREGIDA Y MEJORADA
CREATE VIEW v_equipos_asignados AS
SELECT 
    e.nombre,
    e.apellido,
    c.procesador AS pc_procesador,
    c.ram AS pc_ram,
    n.marca AS nobreak_marca,
    n.modelo AS nobreak_modelo,
    
    -- Esta función agrupa todos los teléfonos de un empleado en un solo campo
    STRING_AGG(DISTINCT t.modelo, ', ') AS telefonos_asignados,
    
    -- Esta función agrupa todas las impresoras de un empleado en un solo campo
    STRING_AGG(DISTINCT i.modelo, ', ') AS impresoras_asignadas
FROM 
    Empleado e
LEFT JOIN 
    Computadora c ON e.id_empleado = c.id_empleado
LEFT JOIN 
    NoBreak n ON c.id_nobreak = n.id_nobreak
LEFT JOIN 
    Telefono t ON e.id_empleado = t.id_empleado
LEFT JOIN 
    Impresora i ON e.id_empleado = i.id_empleado
GROUP BY
    -- Agrupamos por empleado y su equipo principal
    e.id_empleado, c.id_computadora, n.id_nobreak;
