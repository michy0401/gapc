<?php
require_once '../../includes/header.php';
require_once '../../config/db.php';

if (!isset($_GET['ciclo_id'])) { header("Location: index.php"); exit; }
$ciclo_id = $_GET['ciclo_id'];

// 1. INFO GENERAL
$stmt_c = $pdo->prepare("SELECT c.*, g.nombre as grupo FROM Ciclo c JOIN Grupo g ON c.grupo_id = g.id WHERE c.id = ?");
$stmt_c->execute([$ciclo_id]);
$ciclo = $stmt_c->fetch();

// 2. CONSULTA DE FLUJO DE EFECTIVO (TODO EL HISTORIAL)
$sql = "SELECT t.*, r.fecha, r.numero_reunion, u.nombre_completo
        FROM Transaccion_Caja t
        JOIN Reunion r ON t.reunion_id = r.id
        LEFT JOIN Miembro_Ciclo mc ON t.miembro_ciclo_id = mc.id
        LEFT JOIN Usuario u ON mc.usuario_id = u.id
        WHERE r.ciclo_id = ?
        ORDER BY t.id ASC"; // Cronológico
$stmt = $pdo->prepare($sql);
$stmt->execute([$ciclo_id]);
$movimientos = $stmt->fetchAll();
?>

<div class="flex-between print-hide" style="margin-bottom: 20px;">
    <a href="index.php" class="btn btn-secondary"><i class='bx bx-arrow-back'></i> Volver</a>
    <button onclick="window.print()" class="btn btn-primary"><i class='bx bx-printer'></i> IMPRIMIR REPORTE</button>
</div>

<div class="documento-impresion">
    <div class="doc-header" style="text-align: center; border-bottom: 2px solid #000; padding-bottom: 20px; margin-bottom: 30px;">
        <h2 style="text-transform: uppercase; margin: 0;">Reporte de Flujo de Caja</h2>
        <p style="margin: 5px 0;">Ciclo: <?php echo htmlspecialchars($ciclo['nombre']); ?></p>
    </div>

    <table class="doc-table" style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
        <thead>
            <tr style="background: #eee;">
                <th style="border: 1px solid #999; padding: 5px;">Fecha</th>
                <th style="border: 1px solid #999; padding: 5px;">Reunión</th>
                <th style="border: 1px solid #999; padding: 5px;">Responsable</th>
                <th style="border: 1px solid #999; padding: 5px;">Concepto</th>
                <th style="border: 1px solid #999; padding: 5px; text-align: right;">Ingreso</th>
                <th style="border: 1px solid #999; padding: 5px; text-align: right;">Egreso</th>
                <th style="border: 1px solid #999; padding: 5px; text-align: right;">Saldo Caja</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $saldo = 0; 
            $total_in = 0;
            $total_out = 0;
            ?>
            <?php foreach($movimientos as $m): 
                $es_ingreso = in_array($m['tipo_movimiento'], ['AHORRO','PAGO_PRESTAMO_CAPITAL','PAGO_PRESTAMO_INTERES','PAGO_MULTA','INGRESO_EXTRA']);
                
                if($es_ingreso) {
                    $saldo += $m['monto'];
                    $total_in += $m['monto'];
                    $in = '$'.number_format($m['monto'], 2);
                    $out = '-';
                } else {
                    $saldo -= $m['monto'];
                    $total_out += $m['monto'];
                    $in = '-';
                    $out = '$'.number_format($m['monto'], 2);
                }
            ?>
            <tr>
                <td style="border: 1px solid #999; padding: 5px;"><?php echo date('d/m/Y', strtotime($m['fecha'])); ?></td>
                <td style="border: 1px solid #999; padding: 5px;">#<?php echo $m['numero_reunion']; ?></td>
                <td style="border: 1px solid #999; padding: 5px;">
                    <?php echo $m['nombre_completo'] ? htmlspecialchars($m['nombre_completo']) : 'Grupo'; ?>
                </td>
                <td style="border: 1px solid #999; padding: 5px; font-size: 0.85rem;"><?php echo htmlspecialchars($m['observacion']); ?></td>
                <td style="border: 1px solid #999; padding: 5px; text-align: right;"><?php echo $in; ?></td>
                <td style="border: 1px solid #999; padding: 5px; text-align: right;"><?php echo $out; ?></td>
                <td style="border: 1px solid #999; padding: 5px; text-align: right; font-weight: bold;">$<?php echo number_format($saldo, 2); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr style="background: #f0f0f0; font-weight: bold;">
                <td colspan="4" style="border: 1px solid #999; padding: 5px; text-align: right;">TOTALES:</td>
                <td style="border: 1px solid #999; padding: 5px; text-align: right; color: var(--color-success);">$<?php echo number_format($total_in, 2); ?></td>
                <td style="border: 1px solid #999; padding: 5px; text-align: right; color: var(--color-danger);">$<?php echo number_format($total_out, 2); ?></td>
                <td style="border: 1px solid #999; padding: 5px; text-align: right; background: #ddd;">$<?php echo number_format($saldo, 2); ?></td>
            </tr>
        </tfoot>
    </table>
</div>

<style>
    .documento-impresion { background: white; padding: 40px; max-width: 950px; margin: 0 auto; box-shadow: 0 0 10px rgba(0,0,0,0.1); font-family: 'Times New Roman', serif; color: #000; }
    @media print { .print-hide, .sidebar, .topbar { display: none !important; } .main-content { margin:0; padding:0; } .documento-impresion { box-shadow: none; max-width: 100%; } }
</style>

<?php require_once '../../includes/footer.php'; ?>