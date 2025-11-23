<?php
// modules/ahorros/ver.php
require_once '../../includes/header.php';
require_once '../../config/db.php';

if (!isset($_GET['id'])) { header("Location: index.php"); exit; }
$miembro_id = $_GET['id'];

// 1. DATOS DEL SOCIO
$stmt = $pdo->prepare("
    SELECT mc.*, u.nombre_completo, g.nombre as grupo, c.nombre as ciclo 
    FROM Miembro_Ciclo mc
    JOIN Usuario u ON mc.usuario_id = u.id
    JOIN Ciclo c ON mc.ciclo_id = c.id
    JOIN Grupo g ON c.grupo_id = g.id
    WHERE mc.id = ?");
$stmt->execute([$miembro_id]);
$socio = $stmt->fetch();

// 2. HISTORIAL DE TRANSACCIONES (FILTRADO SOLO AHORRO)
$sql_hist = "SELECT t.*, r.fecha as fecha_mov, r.numero_reunion
             FROM Transaccion_Caja t
             JOIN Reunion r ON t.reunion_id = r.id
             WHERE t.miembro_ciclo_id = ? 
             AND t.tipo_movimiento IN ('AHORRO', 'RETIRO_AHORRO')
             ORDER BY t.id ASC";
$stmt_h = $pdo->prepare($sql_hist);
$stmt_h->execute([$miembro_id]);
$movimientos = $stmt_h->fetchAll();
?>

<div class="flex-between" style="margin-bottom: 20px;">
    <a href="index.php" class="btn btn-secondary">
        <i class='bx bx-arrow-back'></i> Volver a Lista
    </a>
    
    <a href="estado_cuenta.php?id=<?php echo $miembro_id; ?>" class="btn btn-primary">
        <i class='bx bx-file-blank'></i> VER ESTADO DE CUENTA OFICIAL
    </a>
</div>

<div class="grid-2">
    <div class="card" style="border-left: 5px solid var(--color-success);">
        <h3>Libreta de Ahorro Digital</h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
            <div>
                <small style="color:#999;">Socia</small>
                <p><strong><?php echo htmlspecialchars($socio['nombre_completo']); ?></strong></p>
            </div>
            <div>
                <small style="color:#999;">Ubicación</small>
                <p><?php echo htmlspecialchars($socio['grupo']); ?> - <?php echo htmlspecialchars($socio['ciclo']); ?></p>
            </div>
        </div>
    </div>

    <div class="card">
        <h3>Saldo Disponible</h3>
        <div style="text-align: center; padding: 10px;">
            <span style="font-size: 3rem; font-weight: bold; color: var(--color-success);">
                $<?php echo number_format($socio['saldo_ahorros'], 2); ?>
            </span>
            <p style="color: #999; margin-top: 5px;">Ahorro acumulado a la fecha</p>
        </div>
    </div>
</div>

<div class="card">
    <h3>Historial de Depósitos y Retiros</h3>
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Reunión</th>
                    <th>Concepto</th>
                    <th style="text-align: right;">Retiro</th>
                    <th style="text-align: right;">Depósito</th>
                    <th style="text-align: right;">Saldo</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $saldo_temp = 0;
                foreach($movimientos as $m): 
                    if($m['tipo_movimiento'] == 'AHORRO') {
                        $saldo_temp += $m['monto'];
                        $clase = "text-success";
                        $signo = "+";
                    } else {
                        $saldo_temp -= $m['monto'];
                        $clase = "text-danger";
                        $signo = "-";
                    }
                ?>
                    <tr>
                        <td><?php echo date('d/m/Y', strtotime($m['fecha_mov'])); ?></td>
                        <td>#<?php echo $m['numero_reunion']; ?></td>
                        <td><?php echo htmlspecialchars($m['observacion']); ?></td>
                        
                        <td style="text-align: right; color: var(--color-danger);">
                            <?php echo ($m['tipo_movimiento'] == 'RETIRO_AHORRO') ? '$'.number_format($m['monto'], 2) : '-'; ?>
                        </td>

                        <td style="text-align: right; color: var(--color-success); font-weight: bold;">
                            <?php echo ($m['tipo_movimiento'] == 'AHORRO') ? '$'.number_format($m['monto'], 2) : '-'; ?>
                        </td>

                        <td style="text-align: right; font-weight: bold;">
                            $<?php echo number_format($saldo_temp, 2); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>