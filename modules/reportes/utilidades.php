<?php
// modules/reportes/utilidades.php
require_once '../../includes/header.php';
require_once '../../config/db.php';

if (!isset($_GET['ciclo_id'])) { header("Location: index.php"); exit; }
$ciclo_id = $_GET['ciclo_id'];

$stmt_c = $pdo->prepare("SELECT nombre, grupo_id FROM Ciclo WHERE id = ?");
$stmt_c->execute([$ciclo_id]);
$ciclo = $stmt_c->fetch();

// Obtener nombre grupo
$stmt_g = $pdo->prepare("SELECT nombre FROM Grupo WHERE id = ?");
$stmt_g->execute([$ciclo['grupo_id']]);
$grupo_nombre = $stmt_g->fetchColumn();

// CÁLCULOS CORREGIDOS ✅
$sql_ganancia = "SELECT SUM(t.monto) FROM Transaccion_Caja t JOIN Reunion r ON t.reunion_id = r.id 
                 WHERE r.ciclo_id = ? AND t.tipo_movimiento IN ('PAGO_PRESTAMO_INTERES', 'PAGO_MULTA', 'INGRESO_EXTRA')";
$stmt_gan = $pdo->prepare($sql_ganancia);
$stmt_gan->execute([$ciclo_id]);
$total_ganancia = $stmt_gan->fetchColumn() ?: 0;

$sql_gastos = "SELECT SUM(t.monto) FROM Transaccion_Caja t JOIN Reunion r ON t.reunion_id = r.id 
               WHERE r.ciclo_id = ? AND t.tipo_movimiento = 'GASTO_OPERATIVO'";
$stmt_gas = $pdo->prepare($sql_gastos);
$stmt_gas->execute([$ciclo_id]);
$total_gastos = $stmt_gas->fetchColumn() ?: 0;

$utilidad_neta = $total_ganancia - $total_gastos;

// Capital
$stmt_a = $pdo->prepare("SELECT SUM(saldo_ahorros) FROM Miembro_Ciclo WHERE ciclo_id = ?");
$stmt_a->execute([$ciclo_id]);
$total_ahorro = $stmt_a->fetchColumn() ?: 1;

$factor = $utilidad_neta / $total_ahorro;

// Socios
$stmt_s = $pdo->prepare("SELECT mc.*, u.nombre_completo FROM Miembro_Ciclo mc JOIN Usuario u ON mc.usuario_id = u.id WHERE mc.ciclo_id = ? ORDER BY u.nombre_completo");
$stmt_s->execute([$ciclo_id]);
$socios = $stmt_s->fetchAll();
?>

<div class="flex-between print-hide" style="margin-bottom: 20px;">
    <a href="index.php" class="btn btn-secondary"><i class='bx bx-arrow-back'></i> Volver</a>
    <button onclick="window.print()" class="btn btn-primary"><i class='bx bx-printer'></i> IMPRIMIR</button>
</div>

<div class="documento-impresion">
    <div class="doc-header" style="text-align: center; border-bottom: 2px solid #000; padding-bottom: 20px; margin-bottom: 30px;">
        <h2 style="text-transform: uppercase; margin: 0;">Distribución de Utilidades</h2>
        <p style="margin: 5px 0;">Grupo: <?php echo htmlspecialchars($grupo_nombre); ?></p>
        <p style="font-size: 0.9rem;">Ciclo: <?php echo htmlspecialchars($ciclo['nombre']); ?></p>
    </div>

    <div style="background: #f9f9f9; padding: 15px; border: 1px solid #ccc; margin-bottom: 20px;">
        <strong>Rentabilidad del Ciclo:</strong> 
        Por cada $1.00 ahorrado, se ganaron <span style="color: var(--color-success); font-weight: bold;">$<?php echo number_format($factor, 4); ?></span>
    </div>

    <table class="doc-table" style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background: #eee;">
                <th style="border: 1px solid #999; padding: 5px;">Socia</th>
                <th style="border: 1px solid #999; padding: 5px; text-align: right;">Ahorro Total</th>
                <th style="border: 1px solid #999; padding: 5px; text-align: right;">Utilidad</th>
                <th style="border: 1px solid #999; padding: 5px; text-align: right;">TOTAL A RETIRAR</th>
                <th style="border: 1px solid #999; padding: 5px; text-align: center; width: 150px;">FIRMA</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($socios as $s): 
                $ganancia = $s['saldo_ahorros'] * $factor;
                $total = $s['saldo_ahorros'] + $ganancia;
            ?>
            <tr>
                <td style="border: 1px solid #999; padding: 5px;"><?php echo htmlspecialchars($s['nombre_completo']); ?></td>
                <td style="border: 1px solid #999; padding: 5px; text-align: right;">$<?php echo number_format($s['saldo_ahorros'], 2); ?></td>
                <td style="border: 1px solid #999; padding: 5px; text-align: right;">$<?php echo number_format($ganancia, 2); ?></td>
                <td style="border: 1px solid #999; padding: 5px; text-align: right; font-weight: bold;">
                    $<?php echo number_format($total, 2); ?>
                </td>
                <td style="border: 1px solid #999; padding: 5px;"></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr style="background: #f0f0f0; font-weight: bold;">
                <td style="border: 1px solid #999; padding: 5px; text-align: right;">TOTALES:</td>
                <td style="border: 1px solid #999; padding: 5px; text-align: right;">$<?php echo number_format($total_ahorro, 2); ?></td>
                <td style="border: 1px solid #999; padding: 5px; text-align: right;">$<?php echo number_format($utilidad_neta, 2); ?></td>
                <td style="border: 1px solid #999; padding: 5px; text-align: right;">$<?php echo number_format($total_ahorro + $utilidad_neta, 2); ?></td>
                <td style="border: 1px solid #999;"></td>
            </tr>
        </tfoot>
    </table>
    
    <br><br><br>
    <div style="text-align: center; width: 50%; margin: 0 auto;">
        <hr style="border: 1px solid #000;">
        <small>Firma de Conformidad (Tesorera)</small>
    </div>
</div>

<style>
    .documento-impresion { background: white; padding: 40px; max-width: 900px; margin: 0 auto; box-shadow: 0 0 10px rgba(0,0,0,0.1); font-family: 'Times New Roman', serif; color: #000; }
    @media print { .print-hide, .sidebar, .topbar { display: none !important; } .main-content { margin:0; padding:0; } .documento-impresion { box-shadow: none; max-width: 100%; } }
</style>

<?php require_once '../../includes/footer.php'; ?>