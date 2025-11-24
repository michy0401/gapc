<?php 
require_once '../../includes/header.php'; 
require_once '../../includes/permissions.php';
require_once '../../config/db.php';

$rol = $_SESSION['rol_usuario'];
$uid = $_SESSION['user_id'];
?>

<?php if ($rol == ROL_ADMIN || $rol == ROL_PROMOTORA): ?>

    <h2 style="color: var(--color-brand); margin-bottom: 20px;">Panel de Gestión</h2>

    <div class="grid-dashboard">
        <a href="../grupos/index.php" class="card-compact" style="border-left-color: var(--color-brand);">
            <div class="card-icon bg-blue"><i class='bx bxs-group'></i></div>
            <div class="card-info"><h3>Grupos</h3><p>Gestionar mis grupos</p></div>
        </a>
        <a href="../reuniones/index.php" class="card-compact" style="border-left-color: var(--color-success);">
            <div class="card-icon bg-green"><i class='bx bxs-calendar-check'></i></div>
            <div class="card-info"><h3>Reuniones</h3><p>Operar caja y asistencia</p></div>
        </a>
        <a href="../ahorros/index.php" class="card-compact" style="border-left-color: #673AB7;">
            <div class="card-icon" style="background-color: #EDE7F6; color: #673AB7;"><i class='bx bxs-coin-stack'></i></div>
            <div class="card-info"><h3>Ahorros</h3><p>Cuentas individuales</p></div>
        </a>
        <a href="../prestamos/index.php" class="card-compact" style="border-left-color: var(--color-warning);">
            <div class="card-icon bg-orange"><i class='bx bxs-bank'></i></div>
            <div class="card-info"><h3>Préstamos</h3><p>Cartera global</p></div>
        </a>
        <a href="../reportes/index.php" class="card-compact" style="border-left-color: var(--color-danger);">
            <div class="card-icon bg-red"><i class='bx bxs-file-pdf'></i></div>
            <div class="card-info"><h3>Reportes</h3><p>Auditoría y Cierre</p></div>
        </a>
    </div>

<?php else: ?>
    
    <?php
    // Obtener resumen rápido del socio
    // 1. Total Ahorrado
    $stmt_a = $pdo->prepare("SELECT SUM(saldo_ahorros) FROM Miembro_Ciclo WHERE usuario_id = ?");
    $stmt_a->execute([$uid]);
    $mi_ahorro = $stmt_a->fetchColumn() ?: 0;

    // 2. Deuda Activa
    $stmt_d = $pdo->prepare("
        SELECT SUM(p.monto_aprobado - IFNULL((SELECT SUM(monto) FROM Transaccion_Caja WHERE prestamo_id = p.id AND tipo_movimiento='PAGO_PRESTAMO_CAPITAL'), 0)) 
        FROM Prestamo p 
        JOIN Miembro_Ciclo mc ON p.miembro_ciclo_id = mc.id
        WHERE mc.usuario_id = ? AND p.estado = 'ACTIVO'");
    $stmt_d->execute([$uid]);
    $mi_deuda = $stmt_d->fetchColumn() ?: 0;
    ?>

    <h2 style="color: var(--color-brand);">Mi Espacio Personal</h2>
    <p style="color: var(--text-muted); margin-bottom: 30px;">Bienvenido/a, aquí está el resumen de tus cuentas.</p>

    <div class="grid-2">
        <div class="card" style="border-left: 5px solid var(--color-success); display: flex; align-items: center; justify-content: space-between;">
            <div>
                <small style="color: #666; text-transform: uppercase;">Mis Ahorros Totales</small>
                <div style="font-size: 2.5rem; font-weight: bold; color: var(--color-success);">
                    $<?php echo number_format($mi_ahorro, 2); ?>
                </div>
            </div>
            <i class='bx bxs-piggy-bank' style="font-size: 4rem; color: #C8E6C9;"></i>
        </div>

        <div class="card" style="border-left: 5px solid var(--color-warning); display: flex; align-items: center; justify-content: space-between;">
            <div>
                <small style="color: #666; text-transform: uppercase;">Préstamos Pendientes</small>
                <div style="font-size: 2.5rem; font-weight: bold; color: var(--color-warning);">
                    $<?php echo number_format($mi_deuda, 2); ?>
                </div>
            </div>
            <i class='bx bxs-credit-card' style="font-size: 4rem; color: #FFE0B2;"></i>
        </div>
    </div>

    <h3 style="margin-top: 30px;">Accesos Rápidos</h3>
    <div class="grid-dashboard">
        <a href="../mi_perfil/mis_grupos.php" class="card-compact">
            <div class="card-icon bg-blue"><i class='bx bxs-group'></i></div>
            <div class="card-info"><h3>Mis Grupos</h3><p>Ver mis asociaciones</p></div>
        </a>
        <a href="../mi_perfil/mis_finanzas.php" class="card-compact">
            <div class="card-icon bg-green"><i class='bx bx-history'></i></div>
            <div class="card-info"><h3>Mis Movimientos</h3><p>Historial detallado</p></div>
        </a>
    </div>

<?php endif; ?>

<?php require_once '../../includes/footer.php'; ?>