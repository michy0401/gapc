<?php
// modules/grupos/cierre_ciclo.php
require_once '../../includes/header.php';
require_once '../../config/db.php';

if (!isset($_GET['ciclo_id'])) { header("Location: index.php"); exit; }
$ciclo_id = $_GET['ciclo_id'];

// 1. DATOS DEL CICLO
$stmt = $pdo->prepare("SELECT c.*, g.nombre as grupo FROM Ciclo c JOIN Grupo g ON c.grupo_id = g.id WHERE c.id = ?");
$stmt->execute([$ciclo_id]);
$ciclo = $stmt->fetch();

// 2. VALIDACIÓN: ¿HAY DEUDAS ACTIVAS?
$sql_pendientes = "SELECT p.*, u.nombre_completo 
                   FROM Prestamo p 
                   JOIN Miembro_Ciclo mc ON p.miembro_ciclo_id = mc.id
                   JOIN Usuario u ON mc.usuario_id = u.id
                   WHERE mc.ciclo_id = ? AND p.estado = 'ACTIVO'";
$stmt_p = $pdo->prepare($sql_pendientes);
$stmt_p->execute([$ciclo_id]);
$deudores = $stmt_p->fetchAll();
$hay_deudas = count($deudores) > 0;

// 3. CÁLCULO DE UTILIDADES
$sql_ganancia = "SELECT SUM(t.monto) 
                 FROM Transaccion_Caja t 
                 JOIN Reunion r ON t.reunion_id = r.id 
                 WHERE r.ciclo_id = ? 
                 AND t.tipo_movimiento IN ('PAGO_PRESTAMO_INTERES', 'PAGO_MULTA', 'INGRESO_EXTRA')";
$stmt_gan = $pdo->prepare($sql_ganancia);
$stmt_gan->execute([$ciclo_id]);
$total_ganancia = $stmt_gan->fetchColumn();
$total_ganancia = $total_ganancia ? $total_ganancia : 0;

$sql_gastos = "SELECT SUM(t.monto) 
               FROM Transaccion_Caja t 
               JOIN Reunion r ON t.reunion_id = r.id 
               WHERE r.ciclo_id = ? 
               AND t.tipo_movimiento = 'GASTO_OPERATIVO'";
$stmt_gas = $pdo->prepare($sql_gastos);
$stmt_gas->execute([$ciclo_id]);
$total_gastos = $stmt_gas->fetchColumn();
$total_gastos = $total_gastos ? $total_gastos : 0;

$utilidad_neta = $total_ganancia - $total_gastos;

// 4. CAPITAL SOCIAL
$sql_ahorro = "SELECT SUM(saldo_ahorros) FROM Miembro_Ciclo WHERE ciclo_id = ?";
$stmt_a = $pdo->prepare($sql_ahorro);
$stmt_a->execute([$ciclo_id]);
$total_ahorro = $stmt_a->fetchColumn() ?: 1; 

$factor = $utilidad_neta / $total_ahorro;

// 5. LISTA DE DISTRIBUCIÓN
$sql_socios = "SELECT mc.*, u.nombre_completo FROM Miembro_Ciclo mc JOIN Usuario u ON mc.usuario_id = u.id WHERE mc.ciclo_id = ? ORDER BY u.nombre_completo";
$stmt_s = $pdo->prepare($sql_socios);
$stmt_s->execute([$ciclo_id]);
$socios = $stmt_s->fetchAll();

// 6. PROCESAR CIERRE FINAL
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$hay_deudas) {
    try {
        $stmt_close = $pdo->prepare("UPDATE Ciclo SET estado = 'LIQUIDADO' WHERE id = ?");
        $stmt_close->execute([$ciclo_id]);
        echo "<script>window.location.href='acta_cierre.php?ciclo_id=$ciclo_id';</script>";
        exit;
    } catch (Exception $e) {
        $error = "Error al cerrar: " . $e->getMessage();
    }
}
?>

<div class="flex-between" style="margin-bottom: 20px;">
    <a href="ver.php?id=<?php echo $ciclo['grupo_id']; ?>" class="btn btn-secondary">
        <i class='bx bx-arrow-back'></i> Volver al Grupo
    </a>
    <h2 style="margin-top: 10px;">Cierre y Liquidación de Ciclo</h2>
</div>

<?php if ($hay_deudas): ?>
    <div class="card" style="border-left: 5px solid #D32F2F; background-color: #FFEBEE;">
        <h3 style="color: #D32F2F;"><i class='bx bx-block'></i> NO SE PUEDE CERRAR</h3>
        <p>Hay préstamos activos. Todo debe estar pagado antes de distribuir utilidades.</p>
        <br>
        <strong>Deudores Pendientes:</strong>
        <ul>
            <?php foreach($deudores as $d): ?>
                <li><?php echo htmlspecialchars($d['nombre_completo']); ?> - Deuda: $<?php echo number_format($d['monto_aprobado'], 2); ?></li>
            <?php endforeach; ?>
        </ul>
        <p style="margin-top: 10px;">
            <a href="../prestamos/index.php" class="btn btn-sm btn-danger">Ir a Préstamos</a>
        </p>
    </div>
<?php endif; ?>

<div class="grid-2">
    
    <div class="card">
        <h3><i class='bx bx-pie-chart-alt-2'></i> La "Bolsa" a Repartir</h3>
        <table class="table">
            <tr>
                <td>Total Ahorrado (Capital)</td>
                <td class="text-right">$<?php echo number_format($total_ahorro, 2); ?></td>
            </tr>
            <tr>
                <td>(+) Intereses y Multas</td>
                <td class="text-right">$<?php echo number_format($total_ganancia, 2); ?></td>
            </tr>
            <tr>
                <td>(-) Gastos Operativos</td>
                <td class="text-right">$<?php echo number_format($total_gastos, 2); ?></td>
            </tr>
            <tr style="background: #E8F5E9; font-weight: bold; font-size: 1.1rem;">
                <td>UTILIDAD NETA (GANANCIA)</td>
                <td class="text-right" style="color: var(--color-success);">
                    $<?php echo number_format($utilidad_neta, 2); ?>
                </td>
            </tr>
        </table>
        
        <div style="margin-top: 20px; padding: 15px; background: #E3F2FD; border-radius: 8px; text-align: center;">
            <small>RENTABILIDAD</small>
            <div style="font-size: 1.5rem; font-weight: bold; color: #1565C0;">
                <?php echo number_format($factor * 100, 1); ?>%
            </div>
            <small>Por cada $1.00 ahorrado, se ganan $<?php echo number_format($factor, 4); ?></small>
        </div>
    </div>

    <div class="card">
        <h3><i class='bx bx-list-ol'></i> Simulación de Reparto</h3>
        <div class="table-container" style="max-height: 400px; overflow-y: auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Socia</th>
                        <th class="text-center">Ahorro</th>
                        <th class="text-center">Ganancia</th>
                        <th class="text-center">Total a Recibir</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($socios as $s): 
                        $ganancia = $s['saldo_ahorros'] * $factor;
                        $total = $s['saldo_ahorros'] + $ganancia;
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($s['nombre_completo']); ?></td>
                        <td class="text-center">$<?php echo number_format($s['saldo_ahorros'], 2); ?></td>
                        <td class="text-center" style="color: var(--color-success);">
                            + $<?php echo number_format($ganancia, 2); ?>
                        </td>
                        <td class="text-center" style="font-weight: bold;">
                            $<?php echo number_format($total, 2); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php if (!$hay_deudas): ?>
    <div class="card" style="text-align: center; border: 2px dashed var(--color-warning);">
        <h3>⚠️ Acción Irreversible</h3>
        <p>Al confirmar, el ciclo pasará a estado <strong>LIQUIDADO</strong>. Asegúrese de tener todo el dinero en efectivo.</p>
        
        <form method="POST" id="form-liquidacion">
            <button type="button" onclick="toggleModal(true)" class="btn btn-primary" style="padding: 15px 30px; font-size: 1.2rem; background-color: #37474F;">
                <i class='bx bx-check-double'></i> CONFIRMAR LIQUIDACIÓN Y GENERAR ACTA
            </button>
        </form>
    </div>
<?php endif; ?>

<div id="modal-liquidacion" class="modal-overlay-custom">
    <div class="modal-box">
        <div class="text-center">
            <i class='bx bx-error-circle' style="font-size: 4rem; color: var(--color-warning);"></i>
            <h3 style="margin-top: 15px;">¿Confirmar Liquidación?</h3>
            <p style="color: #666; margin-bottom: 25px;">
                Esta acción finalizará el ciclo operativo y generará el acta oficial de reparto. 
                <br><br>
                <strong>¡Esta acción no se puede deshacer!</strong>
            </p>
            
            <div class="flex-center" style="gap: 15px;">
                <button onclick="toggleModal(false)" class="btn btn-secondary" style="width: 120px;">Cancelar</button>
                <button onclick="document.getElementById('form-liquidacion').submit()" class="btn btn-primary" style="width: 140px; background-color: #37474F;">
                    SÍ, LIQUIDAR
                </button>
            </div>
        </div>
    </div>
</div>

<style> 
    .text-right { text-align: right; } 
    
    .modal-overlay-custom {
        display: none;
        position: fixed;
        top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 2000;
        align-items: center;
        justify-content: center;
    }
    .modal-box {
        background: white;
        padding: 30px;
        border-radius: 12px;
        max-width: 450px;
        width: 90%;
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        animation: slideDown 0.3s ease;
    }
    @keyframes slideDown {
        from { transform: translateY(-20px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
</style>

<script>
    function toggleModal(show) {
        const modal = document.getElementById('modal-liquidacion');
        modal.style.display = show ? 'flex' : 'none';
    }
</script>

<?php require_once '../../includes/footer.php'; ?>