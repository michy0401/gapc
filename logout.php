<?php
// logout.php

// 1. Iniciar la sesión (necesario para poder destruirla)
session_start();

// 2. Eliminar todas las variables de sesión ($_SESSION['user_id'], etc.)
$_SESSION = array();

// 3. Si se desea destruir la sesión completamente, borre también la cookie de sesión.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Destruir la sesión en el servidor
session_destroy();

// 5. Redirigir al Login
header("Location: index.php");
exit;
?>