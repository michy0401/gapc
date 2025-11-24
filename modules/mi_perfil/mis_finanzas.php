<?php
// modules/mi_perfil/mis_finanzas.php
require_once '../../includes/header.php';
require_once '../../config/db.php';

$uid = $_SESSION['user_id'];

// 1. OBTENER EL ID DEL MIEMBRO EN EL CICLO ACTIVO (Para el reporte de ahorros)
// Asumimos que queremos ver el del ciclo activo actual.
$stmt_mc = $pdo->prepare("
    SELECT mc.id, mc.saldo_ahorros 
    FROM Miembro_Ciclo mc 
    JOIN Ciclo c ON mc.ciclo_id = c.id 
    WHERE mc.usuario_id = ? AND c.estado = 'ACTIVO' 
    LIMIT 1");
$stmt_mc->execute([$uid]);
$datos_ahorro = $stmt_mc->fetch();
$mi_mc_id = $datos_ahorro['id'] ?? null;
$saldo_ahorro = $datos_ahorro['saldo_ahorros'] ?? 0;

// 2. OBTENER PRÉSTAMOS ACTIVOS (Para los reportes de crédito)
$stmt_p = $pdo->prepare("
    SELECT p.id, p.monto_aprobado, 
    (p.monto_aprobado - IFNULL((SELECT SUM(monto) FROM Transaccion_Caja WHERE prestamo_id = p.id AND tipo_movimiento='PAGO_PRESTAMO_CAPITAL'), 0)) as saldo_pendiente
    FROM Prestamo p 
    JOIN Miembro_Ciclo mc ON p.miembro_ciclo_id = mc.id
    WHERE mc.usuario_id = ? AND p.estado = 'ACTIVO'");
$stmt_p->execute([$uid]);
$mis_prestamos = $stmt_p->fetchAll();

// Calcular deuda total para el resumen visual
$deuda_total = 0;
foreach($mis_prestamos as $mp) { $deuda_total += $mp['saldo_pendiente']; }

// 3. OBTENER MULTAS PENDIENTES
$stmt_m = $pdo->prepare("
    SELECT SUM(monto) FROM Deuda_Multa dm 
    JOIN Miembro_Ciclo mc ON dm.miembro_ciclo_id = mc.id 
    WHERE mc.usuario_id = ? AND dm.estado = 'PENDIENTE'");
$stmt_m->execute([$uid]);
$deuda_multas = $stmt_m->fetchColumn() ?: 0;

// 4. HISTORIAL DETALLADO (Últimos 50 movimientos)
$sql_hist = "
    SELECT t.*, r.fecha, r.numero_reunion, c.nombre as ciclo
    FROM Transaccion_Caja t
    JOIN Reunion r ON t.reunion_id = r.id
    JOIN Ciclo c ON r.ciclo_id = c.id
    JOIN Miembro_Ciclo mc ON t.miembro_ciclo_id = mc.id
    WHERE mc.usuario_id = ?
    ORDER BY t.id DESC LIMIT 50";
$stmt_h = $pdo->prepare($sql_hist);
$stmt_h->execute([$uid]);
$historial = $stmt_h->fetchAll();
?>

<h2 style="color: var(--color-brand); margin-bottom: 20px;">Mis Finanzas</h2>

<div class="grid-2">
    
    <div class="card" style="border-left: 5px solid var(--color-success);">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px;">
            <div>
                <small style="text-transform: uppercase; color: #666;">Mi Ahorro Total</small>
                <div style="font-size: 2.5rem; font-weight: bold; color: var(--color-success);">
                    $<?php echo number_format($saldo_ahorro, 2); ?>
                </div>
            </div>
            <i class='bx bxs-wallet' style="font-size: 3.5rem; color: #C8E6C9;"></i>
        </div>
        
        <?php if($mi_mc_id): ?>
            <hr style="border: 0; border-top: 1px solid #eee; margin: 10px 0;">
            <a href="../ahorros/estado_cuenta.php?id=<?php echo $mi_mc_id; ?>&origen=mi_perfil" class="btn btn-sm btn-success btn-block" style="text-align: center;">
                <i class='bx bx-printer'></i> Imprimir Estado de Cuenta
            </a>
        <?php endif; ?>
    </div>

    <div class="card" style="border-left: 5px solid var(--color-warning);">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
            <div>
                <small style="text-transform: uppercase; color: #666;">Deuda Total</small>
                <div style="font-size: 2.5rem; font-weight: bold; color: var(--color-warning);">
                    $<?php echo number_format($deuda_total + $deuda_multas, 2); ?>
                </div>
            </div>
            <i class='bx bxs-bank' style="font-size: 3.5rem; color: #FFE0B2;"></i>
        </div>

        <?php if($deuda_multas > 0): ?>
            <div style="background: #FFEBEE; color: #D32F2F; padding: 5px 10px; border-radius: 4px; font-size: 0.9rem; margin-bottom: 10px;">
                <i class='bx bx-error-circle'></i> Tienes $<?php echo number_format($deuda_multas, 2); ?> en multas pendientes.
            </div>
        <?php endif; ?>

        <?php if(count($mis_prestamos) > 0): ?>
            <hr style="border: 0; border-top: 1px solid #eee; margin: 10px 0;">
            <small style="color: #999; display: block; margin-bottom: 5px;">Préstamos Activos:</small>
            <?php foreach($mis_prestamos as $mp): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; background: #FFF3E0; padding: 8px; border-radius: 4px; margin-bottom: 5px;">
                    <span style="font-weight: bold; color: #E65100;">$<?php echo number_format($mp['saldo_pendiente'], 2); ?></span>
                    
                    <a href="../prestamos/estado_cuenta.php?id=<?php echo $mp['id']; ?>&origen=mi_perfil" class="btn btn-sm btn-secondary" style="padding: 2px 8px; font-size: 0.8rem;">
                        <i class='bx bx-file'></i> Ver Recibo
                    </a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <small style="color: #999;">No tienes préstamos activos.</small>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <h3><i class='bx bx-history'></i> Mis Movimientos Recientes</h3>
    <div class="table-container">
        <?php if (count($historial) > 0): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Ciclo / Reunión</th>
                        <th>Concepto</th>
                        <th style="text-align: right;">Monto</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($historial as $h): 
                        // Lógica de colores
                        $color = '#333';
                        if ($h['tipo_movimiento'] == 'AHORRO') $color = 'var(--color-success)';
                        if ($h['tipo_movimiento'] == 'RETIRO_AHORRO') $color = 'var(--color-danger)';
                        if (strpos($h['tipo_movimiento'], 'PAGO_') !== false) $color = '#1565C0'; // Pagos en azul
                        
                        $concepto_txt = str_replace('_', ' ', $h['tipo_movimiento']);
                    ?>
                    <tr>
                        <td><?php echo date('d/m/Y', strtotime($h['fecha'])); ?></td>
                        <td>
                            <small style="display:block; font-weight:bold;"><?php echo htmlspecialchars($h['ciclo']); ?></small>
                            <small>Reunión #<?php echo $h['numero_reunion']; ?></small>
                        </td>
                        <td>
                            <strong><?php echo $concepto_txt; ?></strong><br>
                            <small style="color:#999;"><?php echo htmlspecialchars($h['observacion']); ?></small>
                        </td>
                        <td style="text-align: right; font-weight: bold; color: <?php echo $color; ?>;">
                            $<?php echo number_format($h['monto'], 2); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="text-center" style="padding: 30px; color: #999;">
                <p>No tienes movimientos registrados aún.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>