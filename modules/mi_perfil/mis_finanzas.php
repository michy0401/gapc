<?php
// modules/mi_perfil/mis_finanzas.php
require_once '../../includes/header.php';
require_once '../../config/db.php';

$uid = $_SESSION['user_id'];

// 1. RESUMEN GENERAL (Acumulado de todos los ciclos activos)
$sql_resumen = "
    SELECT 
        SUM(saldo_ahorros) as total_ahorro,
        (SELECT COUNT(*) FROM Prestamo p 
         JOIN Miembro_Ciclo mc2 ON p.miembro_ciclo_id = mc2.id 
         WHERE mc2.usuario_id = ? AND p.estado = 'ACTIVO') as prestamos_activos,
        (SELECT SUM(monto) FROM Deuda_Multa dm 
         JOIN Miembro_Ciclo mc3 ON dm.miembro_ciclo_id = mc3.id 
         WHERE mc3.usuario_id = ? AND dm.estado = 'PENDIENTE') as multas_pendientes
    FROM Miembro_Ciclo 
    WHERE usuario_id = ?";
    
$stmt_r = $pdo->prepare($sql_resumen);
$stmt_r->execute([$uid, $uid, $uid]);
$resumen = $stmt_r->fetch();

// 2. HISTORIAL DETALLADO (Últimos 50 movimientos)
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
    <div class="card" style="border-left: 5px solid var(--color-success); display: flex; align-items: center; justify-content: space-between;">
        <div>
            <small style="text-transform: uppercase; color: #666;">Mi Ahorro Total</small>
            <div style="font-size: 2.5rem; font-weight: bold; color: var(--color-success);">
                $<?php echo number_format($resumen['total_ahorro'], 2); ?>
            </div>
        </div>
        <i class='bx bxs-wallet' style="font-size: 3.5rem; color: #C8E6C9;"></i>
    </div>

    <div class="card" style="border-left: 5px solid var(--color-warning); display: flex; align-items: center; justify-content: space-between;">
        <div>
            <small style="text-transform: uppercase; color: #666;">Préstamos Activos</small>
            <div style="font-size: 2.5rem; font-weight: bold; color: var(--color-warning);">
                <?php echo $resumen['prestamos_activos']; ?>
            </div>
            <?php if($resumen['multas_pendientes'] > 0): ?>
                <small style="color: var(--color-danger);">+ $<?php echo number_format($resumen['multas_pendientes'], 2); ?> en multas</small>
            <?php endif; ?>
        </div>
        <i class='bx bxs-bank' style="font-size: 3.5rem; color: #FFE0B2;"></i>
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
                        $es_ingreso = in_array($h['tipo_movimiento'], ['AHORRO', 'PAGO_PRESTAMO_CAPITAL', 'PAGO_PRESTAMO_INTERES', 'PAGO_MULTA']);
                        // Nota: Desde la perspectiva del usuario:
                        // Pagar prestamo = Sale dinero de mi bolsa (Rojo)
                        // Ahorrar = Sale dinero de mi bolsa hacia el grupo (Verde porque es activo)
                        
                        // Vamos a usar lógica de "Caja Personal":
                        // Ahorro = Positivo (Crece mi capital)
                        // Retiro = Negativo (Baja mi capital)
                        // Pago Préstamo = Neutro o informativo
                        
                        $color = '#333';
                        if ($h['tipo_movimiento'] == 'AHORRO') $color = 'var(--color-success)';
                        if ($h['tipo_movimiento'] == 'RETIRO_AHORRO') $color = 'var(--color-danger)';
                    ?>
                    <tr>
                        <td><?php echo date('d/m/Y', strtotime($h['fecha'])); ?></td>
                        <td>
                            <small style="display:block; font-weight:bold;"><?php echo htmlspecialchars($h['ciclo']); ?></small>
                            <small>Reunión #<?php echo $h['numero_reunion']; ?></small>
                        </td>
                        <td><?php echo htmlspecialchars($h['observacion']); ?></td>
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