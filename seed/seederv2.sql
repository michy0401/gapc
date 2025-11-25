USE gapc_system;

INSERT INTO `rol` (id, nombre, descripcion) 
VALUES (NULL, 'Administrador', 'Acceso completo a todas las funciones del sistema'), (NULL, 'Promotora', 'Persona que acompaña varios grupos del mismo distrito y supervisa su funcionamiento.'), (NULL, 'Miembro', 'Miembro de GAPC')

INSERT INTO `catalogo_cargos` (id, nombre, es_directiva) 
VALUES (NULL, 'Presidente/a', '1'), (NULL, 'Tesorero/a', '1'), (NULL, 'Secretario/a', '1'), (NULL, 'Encargado/a de la Llave', '1'), (NULL, 'Miembro', '0');

INSERT INTO `distrito` (`id`, `nombre`) 
VALUES (NULL, 'Occidente'), (NULL, 'Central'), (NULL, 'Oriente');

INSERT INTO `catalogo_multas` (`id`, `nombre`, `monto_defecto`) 
VALUES (NULL, 'Inasistencia', '0.50'), (NULL, 'Llegada Tardía', '0.50'), (NULL, 'Incumplimiento de Reglas', '1.00');


-- 1. ADMINISTRADOR (Rol 1)
INSERT INTO Usuario (rol_id, nombre_completo, email, password, dui, telefono, direccion, estado) 
VALUES (
    1, 
    'Josue Argueta', 
    'josue@gapc.com', 
    '$2a$12$8DQXhdnoqCDGZ5nm7G7q3.zZGPi5XLDzYnuEN0t8mlbn6B2a3h4/m', 
    '000000010', 
    '70000001', 
    'Lourdes Colon', 
    'ACTIVO'
);

-- 2. PROMOTORAS (Rol 2)
INSERT INTO Usuario (rol_id, nombre_completo, email, password, dui, telefono, direccion, estado) 
VALUES 
(
    2, 
    'Sandra Melendez', 
    'sandra@gapc.com', 
    '$2a$12$8DQXhdnoqCDGZ5nm7G7q3.zZGPi5XLDzYnuEN0t8mlbn6B2a3h4/m', 
    '000000020', 
    '70000002', 
    'Santa Tecla', 
    'ACTIVO'
),
(
    2, 
    'Elizabeth Leiva', 
    'elizabeth@gapc.com', 
    '$2a$12$8DQXhdnoqCDGZ5nm7G7q3.zZGPi5XLDzYnuEN0t8mlbn6B2a3h4/m', 
    '000000030', 
    '70000003', 
    'Santa Tecla', 
    'ACTIVO'
);

-- 3. MIEMBROS (Rol 3)
INSERT INTO Usuario (rol_id, nombre_completo, email, password, dui, telefono, direccion, estado) 
VALUES 
(
    3, 
    'Rodrigo Araujo', 
    'rodrigo@gapc.com', 
    '$2a$12$8DQXhdnoqCDGZ5nm7G7q3.zZGPi5XLDzYnuEN0t8mlbn6B2a3h4/m', 
    '000000040', 
    '70000004', 
    'Santa Elna ', 
    'ACTIVO'
),
(
    3, 
    'Michelle Lemus', 
    'michelle@gapc.com', 
    '$2a$12$8DQXhdnoqCDGZ5nm7G7q3.zZGPi5XLDzYnuEN0t8mlbn6B2a3h4/m', 
    '000000050', 
    '70000005', 
    'Lourdes', 
    'ACTIVO'
);

