<?php
// Detectar ruta
$path_parts = explode('/', trim($_SERVER['SCRIPT_NAME'], '/'));
$base_url = "/" . $path_parts[0];

// Cargar constantes de roles
require_once 'permissions.php'; 
$rol = $_SESSION['rol_usuario'] ?? 0;
?>

<div class="sidebar">
    <div class="sidebar-header">
        <h2 style="color: #ffffff">GAPC</h2>
        <b style="font-size: 0.8rem; opacity: 0.9;">Grupos de Ahorro y Préstamo Comunitario</b>

        <p style="font-size: 0.8rem; opacity: 0.9; ">
            <?php 
                if($rol == ROL_ADMIN) echo "Administrador";
                elseif($rol == ROL_PROMOTORA) echo "Promotora";
                else echo "Miembro / Directiva";
            ?>
        </p>
    </div>

    <ul class="sidebar-menu">
        
        <li>
            <a href="<?php echo $base_url; ?>/modules/dashboard/home.php">
                <i class='bx bxs-dashboard'></i> <span>Inicio</span>
            </a>
        </li>

        <?php if ($rol == ROL_ADMIN || $rol == ROL_PROMOTORA): ?>
            <li>
                <a href="<?php echo $base_url; ?>/modules/grupos/index.php">
                    <i class='bx bxs-group'></i> <span>Grupos</span>
                </a>
            </li>
            <li>
                <a href="<?php echo $base_url; ?>/modules/grupos/ciclos_global.php">
                    <i class='bx bx-recycle'></i> <span>Ciclos</span>
                </a>
            </li>
            <li>
                <a href="<?php echo $base_url; ?>/modules/miembros/index.php">
                    <i class='bx bxs-user-detail'></i> <span>Directorio Miembros</span>
                </a>
            </li>
            <li>
                <a href="<?php echo $base_url; ?>/modules/reuniones/index.php">
                    <i class='bx bxs-calendar-event'></i> <span>Gestión Reuniones</span>
                </a>
            </li>
            <li>
                <a href="<?php echo $base_url; ?>/modules/prestamos/index.php">
                    <i class='bx bxs-bank'></i> <span>Cartera Préstamos</span>
                </a>
            </li>
            <li>
                <a href="<?php echo $base_url; ?>/modules/ahorros/index.php">
                    <i class='bx bxs-coin-stack'></i> <span>Cartera Ahorros</span>
                </a>
            </li>
            <li>
                <a href="<?php echo $base_url; ?>/modules/reportes/index.php">
                    <i class='bx bxs-report'></i> <span>Reportes</span>
                </a>
            </li>

        <?php else: ?>
            <li>
                <a href="<?php echo $base_url; ?>/modules/mi_perfil/mis_grupos.php">
                    <i class='bx bxs-group'></i> <span>Mis Grupos</span>
                </a>
            </li>
            <li>
                <a href="<?php echo $base_url; ?>/modules/mi_perfil/mis_finanzas.php">
                    <i class='bx bx-money'></i> <span>Mis Finanzas</span>
                </a>
            </li>
        <?php endif; ?>

    </ul>

    <div class="sidebar-footer">
        <a href="<?php echo $base_url; ?>/logout.php" style="color: #ffcccc; text-decoration: none; display: flex; align-items: center; gap: 10px;">
            <i class='bx bx-log-out'></i> Cerrar Sesión
        </a>
    </div>
</div>