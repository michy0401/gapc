-- Archivo: semillas.sql
-- Objetivo: Poblar la base de datos GAPC con los datos mínimos necesarios

USE gapc_system;

SET FOREIGN_KEY_CHECKS = 0; -- Desactivar seguridad para limpiar/insertar sin problemas

-- 1. LIMPIEZA PREVIA (Opcional: Para asegurar que los IDs sean 1, 2, 3...)
TRUNCATE TABLE Usuario;
TRUNCATE TABLE Rol;
TRUNCATE TABLE Catalogo_Cargos;
TRUNCATE TABLE Catalogo_Multas;
TRUNCATE TABLE Distrito;

-- 2. ROLES DEL SISTEMA (CRÍTICO: Respetar estos IDs porque el código PHP los usa)
INSERT INTO Rol (id, nombre, descripcion) VALUES 
(1, 'Administrador', 'Acceso total al sistema'),
(2, 'Promotora', 'Supervisa grupos y ve reportes'),
(3, 'Usuario', 'Miembro de grupo o directiva');

-- 3. CATÁLOGO DE CARGOS (Para la directiva de los grupos)
INSERT INTO Catalogo_Cargos (id, nombre, es_directiva) VALUES 
(1, 'Presidenta', 1),
(2, 'Tesorera', 1),
(3, 'Secretaria', 1),
(4, 'Responsable de Llave', 1),
(5, 'Miembro', 0);

-- 4. CATÁLOGO DE MULTAS (Tipos base)
INSERT INTO Catalogo_Multas (id, nombre, monto_defecto) VALUES 
(1, 'Inasistencia', 1.00),
(2, 'Llegada Tardía', 0.50),
(3, 'Falta de Respeto', 2.00),
(4, 'Incumplimiento de Reglas', 1.00);

-- 5. DISTRITOS (Necesarios para crear grupos)
INSERT INTO Distrito (nombre) VALUES 
('Distrito Norte'),
('Distrito Sur'),
('Distrito Central'),
('Distrito Occidente'),
('Distrito Oriente');

-- 6. USUARIO ADMINISTRADOR
-- Usuario: admin@gapc.com
-- Pass: admin123 (Hash generado con BCRYPT)
INSERT INTO Usuario (id, rol_id, nombre_completo, email, password, estado) VALUES 
(1, 1, 'Administrador Principal', 'admin@gapc.com', '$2y$10$vI8aWBnW3fID.ZQ4/zo1G.q1lRps.9cGLcZEiGDMVr5yUP1KUOYTa', 'ACTIVO');

SET FOREIGN_KEY_CHECKS = 1; -- Reactivar seguridad

SELECT '✅ Datos semilla insertados correctamente. Puedes iniciar sesión.' as Mensaje;