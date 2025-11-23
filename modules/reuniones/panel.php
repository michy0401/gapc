<?php
// modules/reuniones/panel.php
require_once '../../includes/header.php';
require_once '../../config/db.php';

if (!isset($_GET['id'])) { header("Location: index.php"); exit; }
$reunion_id = $_GET['id'];

// 1. DATOS REUNIÓN
$stmt = $pdo->prepare("
    SELECT r.*, c.nombre as nombre_ciclo, g.nombre as nombre_grupo, c.id as ciclo_id
    FROM Reunion r
    JOIN Ciclo c ON r.ciclo_id = c.id
    JOIN Grupo g ON c.grupo_id = g.id
    WHERE r.id = ?
");
$stmt->execute([$reunion_id]);
$reunion = $stmt->fetch();

// 2. CÁLCULOS (TOTALES)
$sql_balance = "SELECT 
    SUM(CASE WHEN tipo_movimiento IN ('AHORRO','PAGO_PRESTAMO_CAPITAL','PAGO_PRESTAMO_INTERES','PAGO_MULTA','INGRESO_EXTRA') THEN monto ELSE 0 END) as entradas,
    SUM(CASE WHEN tipo_movimiento IN ('RETIRO_AHORRO','DESEMBOLSO_PRESTAMO','GASTO_OPERATIVO') THEN monto ELSE 0 END) as salidas
    FROM Transaccion_Caja 
    WHERE reunion_id = ?";
$stmt_b = $pdo->prepare($sql_balance);
$stmt_b->execute([$reunion_id]);
$balance = $stmt_b->fetch();

$total_entradas = $balance['entradas'] ?: 0;
$total_salidas = $balance['salidas'] ?: 0;
$saldo_actual_calculado = $reunion['saldo_caja_inicial'] + $total_entradas - $total_salidas;

if ($reunion['estado'] == 'ABIERTA') {
    $pdo->prepare("UPDATE Reunion SET saldo_caja_actual = ? WHERE id = ?")
        ->execute([$saldo_actual_calculado, $reunion_id]);
}

// 3. BITÁCORA (DETALLE)
$sql_log = "SELECT t.*, u.nombre_completo 
            FROM Transaccion_Caja t
            LEFT JOIN Miembro_Ciclo mc ON t.miembro_ciclo_id = mc.id
            LEFT JOIN Usuario u ON mc.usuario_id = u.id
            WHERE t.reunion_id = ?
            ORDER BY t.id DESC";
$stmt_log = $pdo->prepare($sql_log);
$stmt_log->execute([$reunion_id]);
$movimientos = $stmt_log->fetchAll();
?>

<div class="flex-between" style="margin-bottom: 20px;">
    <div>
        <a href="lista.php?ciclo_id=<?php echo $reunion['ciclo_id']; ?>" style="color: var(--text-muted); display: flex; align-items: center; gap: 5px;">
            <i class='bx bx-arrow-back'></i> Salir de la Reunión
        </a>
        <h2 style="margin-top: 10px; color: var(--color-brand);">
            Reunión #<?php echo $reunion['numero_reunion']; ?>
        </h2>
        <p style="color: var(--text-muted);">
            <?php echo htmlspecialchars($reunion['nombre_grupo']); ?> - 
            <?php echo date('d/m/Y', strtotime($reunion['fecha'])); ?>
        </p>
    </div>

    <div style="text-align: right; background: white; padding: 15px 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border-left: 5px solid var(--color-success);">
        <small style="color: #666; text-transform: uppercase; font-weight: bold;">Dinero en Caja</small>
        <div style="font-size: 2rem; font-weight: bold; color: var(--color-success);">
            $<?php echo number_format($saldo_actual_calculado, 2); ?>
        </div>
    </div>
</div>

<div class="grid-dashboard">
    <a href="asistencia.php?id=<?php echo $reunion_id; ?>" class="card-compact" style="border-left-color: #2196F3;">
        <div class="card-icon" style="background: #E3F2FD; color: #2196F3;">
            <i class='bx bx-user-check'></i>
        </div>
        <div class="card-info">
            <h3>1. Asistencia</h3>
            <p>Registrar presentes</p>
        </div>
    </a>
    <a href="multas.php?id=<?php echo $reunion_id; ?>" class="card-compact" style="border-left-color: var(--color-danger);">
        <div class="card-icon bg-red">
            <i class='bx bx-error-circle'></i>
        </div>
        <div class="card-info">
            <h3>2. Multas</h3>
            <p>Cobrar deudas</p>
        </div>
    </a>
    <a href="ahorros.php?id=<?php echo $reunion_id; ?>" class="card-compact" style="border-left-color: var(--color-success);">
        <div class="card-icon bg-green">
            <i class='bx bx-coin-stack'></i>
        </div>
        <div class="card-info">
            <h3>3. Ahorros</h3>
            <p>Recibir depósitos</p>
        </div>
    </a>
    <a href="prestamos.php?id=<?php echo $reunion_id; ?>" class="card-compact" style="border-left-color: var(--color-warning);">
        <div class="card-icon bg-orange">
            <i class='bx bx-money'></i>
        </div>
        <div class="card-info">
            <h3>4. Préstamos</h3>
            <p>Créditos y Pagos</p>
        </div>
    </a>
</div>

<br>

<div class="card">
    <h3><i class='bx bx-list-check'></i> Bitácora de Movimientos en Vivo</h3>
    <div class="table-container" style="max-height: 400px; overflow-y: auto;">
        <?php if(count($movimientos) > 0): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Socia</th>
                        <th>Concepto</th>
                        <th style="text-align: center;">Entrada ($)</th>
                        <th style="text-align: center;">Salida ($)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($movimientos as $m): 
                        $es_entrada = in_array($m['tipo_movimiento'], ['AHORRO','PAGO_PRESTAMO_CAPITAL','PAGO_PRESTAMO_INTERES','PAGO_MULTA','INGRESO_EXTRA']);
                        $tipo_txt = str_replace('_', ' ', $m['tipo_movimiento']);
                    ?>
                        <tr>
                            <td>
                                <strong>
                                    <?php echo $m['nombre_completo'] ? htmlspecialchars($m['nombre_completo']) : 'Grupo (General)'; ?>
                                </strong>
                            </td>
                            <td>
                                <small style="font-weight: bold; color: #666;"><?php echo $tipo_txt; ?></small>
                                <br>
                                <small style="color: #999;"><?php echo htmlspecialchars($m['observacion']); ?></small>
                            </td>
                            
                            <td style="text-align: center; color: var(--color-success);">
                                <?php echo $es_entrada ? '+ $'.number_format($m['monto'], 2) : ''; ?>
                            </td>

                            <td style="text-align: center; color: var(--color-danger);">
                                <?php echo !$es_entrada ? '- $'.number_format($m['monto'], 2) : ''; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="text-center" style="color: #999; padding: 20px;">Aún no hay movimientos registrados en esta sesión.</p>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <h3>Resumen de la Sesión</h3>
    <div class="grid-2">
        <div>
            <h4 style="color: var(--color-success); border-bottom: 2px solid var(--color-success); padding-bottom: 5px;">
                <i class='bx bx-up-arrow-alt'></i> Dinero que Entra
            </h4>
            <table class="table" style="margin-top: 10px;">
                <tr>
                    <td>Saldo Inicial (Apertura)</td>
                    <td style="text-align: right;">$<?php echo number_format($reunion['saldo_caja_inicial'], 2); ?></td>
                </tr>
                <tr>
                    <td>Ingresos del día (Ahorro/Pagos)</td>
                    <td style="text-align: right;">+ $<?php echo number_format($total_entradas, 2); ?></td>
                </tr>
                <tr style="background: #E8F5E9; font-weight: bold;">
                    <td>SUBTOTAL</td>
                    <td style="text-align: right;">$<?php echo number_format($reunion['saldo_caja_inicial'] + $total_entradas, 2); ?></td>
                </tr>
            </table>
        </div>

        <div>
            <h4 style="color: var(--color-danger); border-bottom: 2px solid var(--color-danger); padding-bottom: 5px;">
                <i class='bx bx-down-arrow-alt'></i> Dinero que Sale
            </h4>
            <table class="table" style="margin-top: 10px;">
                <tr>
                    <td>Salidas del día (Préstamos/Retiros)</td>
                    <td style="text-align: right;">- $<?php echo number_format($total_salidas, 2); ?></td>
                </tr>
                <tr style="background: #FFEBEE; font-weight: bold;">
                    <td>TOTAL SALIDAS</td>
                    <td style="text-align: right;">$<?php echo number_format($total_salidas, 2); ?></td>
                </tr>
            </table>
        </div>
    </div>

    <div style="margin-top: 30px; text-align: center;">
        <p style="color: var(--text-muted); margin-bottom: 10px;">
            Al finalizar todas las operaciones, proceda al cierre para generar el acta.
        </p>
        <a href="cierre.php?id=<?php echo $reunion_id; ?>" class="btn btn-primary" style="padding: 15px 40px; font-size: 1.1rem; background-color: #37474F;">
            <i class='bx bx-lock-alt'></i> REALIZAR CIERRE DE CAJA
        </a>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>