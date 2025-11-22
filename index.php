<?php
/* index.php - ROUTER PRINCIPAL */
session_start();

// 1. ¿Ya tiene llave (sesión)? -> Pasa al Dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: modules/dashboard/home.php");
    exit;
}

// 2. ¿No tiene llave? -> Vete al Módulo de Autenticación
header("Location: modules/auth/login.php");
exit;
?>