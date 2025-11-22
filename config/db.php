<?php

// 1. Credenciales 
$host = 'localhost';
$db_name = 'gapc_system'; // El nombre exacto que pusimos en phpMyAdmin
$username = 'root';
$password = ''; 

// 2. Configuración de la conexión para soportar emojis y tildes correctamente
$dsn = "mysql:host=$host;dbname=$db_name;charset=utf8mb4";

// 3. Opciones de configuración de PDO
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// 4. Crear la conexión PDO, try/catch para manejar errores
try {
    $pdo = new PDO($dsn, $username, $password, $options);
       
} catch (PDOException $e) {
     die("❌ Error Crítico de Conexión: " . $e->getMessage());
}
?>