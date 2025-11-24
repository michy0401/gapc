<?php
// includes/permissions.php

// Definimos constantes para no usar números mágicos
define('ROL_ADMIN', 1);
define('ROL_PROMOTORA', 2);
define('ROL_USUARIO', 3);

/**
 * Verifica si el usuario actual tiene permiso para realizar acciones administrativas
 * (Admin, Promotora o Directiva)
 */
function es_operador() {
    // Si es Admin o Promotora, tiene permiso automático
    if ($_SESSION['rol_usuario'] == ROL_ADMIN || $_SESSION['rol_usuario'] == ROL_PROMOTORA) {
        return true;
    }
    
    // Si es Usuario normal, tendríamos que verificar si es Directiva en el ciclo actual
    // Por ahora, asumiremos que si tiene acceso al módulo, verificaremos su cargo puntualmente
    return false; 
}

/**
 * Genera la cláusula SQL para filtrar grupos según el rol
 * @param string $campo_db El nombre de la columna en la base de datos (ej: 'g.promotora_id')
 */
function obtener_filtro_sql($campo_db = 'g.promotora_id') {
    if ($_SESSION['rol_usuario'] == ROL_ADMIN) {
        return " 1=1 "; // El Admin ve todo (siempre verdadero)
    } elseif ($_SESSION['rol_usuario'] == ROL_PROMOTORA) {
        return " $campo_db = " . $_SESSION['user_id'] . " "; // Filtra por su ID
    } else {
        // El usuario normal solo ve grupos donde es miembro
        // (Esta lógica es más compleja, la manejaremos en el dashboard de miembro)
        return " 1=0 "; // Por seguridad, bloqueamos por defecto si no se especifica
    }
}
?>