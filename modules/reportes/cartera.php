<?php
// modules/reportes/cartera.php
require_once '../../includes/header.php';
require_once '../../config/db.php';

if (!isset($_GET['ciclo_id'])) { header("Location: index.php"); exit; }
$ciclo_id = $_GET['ciclo_id'];

// 1. INFO DEL CICLO
$stmt_c = $pdo->prepare("SELECT c.*, g.nombre as grupo FROM Ciclo c JOIN Grupo g ON c.grupo_id = g.id WHERE c.id = ?");
$stmt_c->execute([$ciclo_id]);
$ciclo = $stmt_c->fetch();

// 2. CONSULTA DE CARTERA
$sql = "SELECT p.*, u.nombre_completo, 
        (SELECT IFNULL(SUM(monto), 0) FROM Transaccion_Caja WHERE prestamo_id = p.id AND tipo_movimiento = 'PAGO_PRESTAMO_CAPITAL') as pagado
        FROM Prestamo p
        JOIN Miembro_Ciclo mc ON p.miembro_ciclo_id = mc.id
        JOIN Usuario u ON mc.usuario_id = u.id
        WHERE mc.ciclo_id = ? AND p.estado = 'ACTIVO'
        ORDER BY p.fecha_vencimiento ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$ciclo_id]);
$prestamos = $stmt->fetchAll();

// 3. CÁLCULOS PREVIOS (KPIs)
$total_prestamos_count = count($prestamos);
$prestamos_en_mora = 0;
$dinero_en_mora = 0;
$hoy = date('Y-m-d');

// Variables para los totales del pie de página
$suma_total_prestado = 0;
$suma_total_pendiente = 0;

// Hacemos un primer barrido rápido para los KPIs de arriba
foreach($prestamos as $p) {
    $saldo = $p['monto_aprobado'] - $p['pagado'];
    if ($saldo > 0 && $p['fecha_vencimiento'] < $hoy) {
        $prestamos_en_mora++;
        $dinero_en_mora += $saldo;
    }
}

$porcentaje_mora = ($total_prestamos_count > 0) ? ($prestamos_en_mora / $total_prestamos_count) * 100 : 0;

$origen = isset($_GET['origen']) ? $_GET['origen'] : 'ACTIVO';
?>

<div class="flex-between print-hide" style="margin-bottom: 20px;">
    <a href="index.php?estado=<?php echo $origen; ?>" class="btn btn-secondary">
        <i class='bx bx-arrow-back'></i> Volver a Reportes
    </a>
    <button onclick="window.print()" class="btn btn-primary">
        <i class='bx bx-printer'></i> IMPRIMIR REPORTE
    </button>
</div>

<div class="documento-impresion">
    
    <div class="doc-header" style="text-align: center; border-bottom: 2px solid #000; padding-bottom: 20px; margin-bottom: 30px;">
        <h2 style="text-transform: uppercase; margin: 0;">Reporte de Cartera de Créditos</h2>
        <p style="margin: 5px 0;">GAPC: <?php echo htmlspecialchars($ciclo['grupo']); ?></p>
        <p style="font-size: 0.9rem;">Fecha de Corte: <?php echo date('d/m/Y'); ?></p>
    </div>

    <div class="grid-2" style="margin-bottom: 20px;">
        <div style="border: 1px solid #ccc; padding: 15px; border-radius: 4px; display: flex; justify-content: space-between; align-items: center;">
            <span>Índice de Mora (Cantidad):</span>
            <strong style="font-size: 1.2rem; color: <?php echo $porcentaje_mora > 0 ? '#D32F2F' : '#2E7D32'; ?>;">
                <?php echo number_format($porcentaje_mora, 1); ?>%
            </strong>
        </div>
        <div style="border: 1px solid #ccc; padding: 15px; border-radius: 4px; display: flex; justify-content: space-between; align-items: center;">
            <span>Capital en Riesgo (Vencido):</span>
            <strong style="font-size: 1.2rem; color: <?php echo $dinero_en_mora > 0 ? '#D32F2F' : '#2E7D32'; ?>;">
                $<?php echo number_format($dinero_en_mora, 2); ?>
            </strong>
        </div>
    </div>

    <table class="doc-table" style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
        <thead>
            <tr style="background: #eee;">
                <th style="border: 1px solid #999; padding: 8px;">Socia</th>
                <th style="border: 1px solid #999; padding: 8px;">Vencimiento</th>
                <th style="border: 1px solid #999; padding: 8px; text-align: right;">Monto Original</th>
                <th style="border: 1px solid #999; padding: 8px; text-align: right;">Pagado</th>
                <th style="border: 1px solid #999; padding: 8px; text-align: right;">Saldo Pendiente</th>
                <th style="border: 1px solid #999; padding: 8px; text-align: center;">Estado</th>
            </tr>
        </thead>
        <tbody>
            <?php if(count($prestamos) > 0): ?>
                <?php foreach($prestamos as $p): 
                    $saldo = $p['monto_aprobado'] - $p['pagado'];
                    
                    // Acumulamos totales para el footer
                    $suma_total_prestado += $p['monto_aprobado'];
                    $suma_total_pendiente += $saldo;
                    
                    // Calcular Mora por fila
                    $vencido = ($p['fecha_vencimiento'] < $hoy);
                ?>
                <tr>
                    <td style="border: 1px solid #999; padding: 5px;">
                        <?php echo htmlspecialchars($p['nombre_completo']); ?>
                    </td>
                    <td style="border: 1px solid #999; padding: 5px;">
                        <?php echo date('d/m/Y', strtotime($p['fecha_vencimiento'])); ?>
                    </td>
                    <td style="border: 1px solid #999; padding: 5px; text-align: right;">
                        $<?php echo number_format($p['monto_aprobado'], 2); ?>
                    </td>
                    <td style="border: 1px solid #999; padding: 5px; text-align: right;">
                        $<?php echo number_format($p['pagado'], 2); ?>
                    </td>
                    <td style="border: 1px solid #999; padding: 5px; text-align: right; font-weight: bold;">
                        $<?php echo number_format($saldo, 2); ?>
                    </td>
                    <td style="border: 1px solid #999; padding: 5px; text-align: center;">
                        <?php if($vencido): ?>
                            <span style="color: #D32F2F; font-weight: bold;">¡VENCIDO!</span>
                        <?php else: ?>
                            Vigente
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="6" class="text-center" style="padding:20px;">No hay créditos activos.</td></tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr style="background: #f9f9f9; font-weight: bold;">
                <td colspan="2" style="border: 1px solid #999; padding: 8px; text-align: right;">TOTALES:</td>
                <td style="border: 1px solid #999; padding: 8px; text-align: right;">
                    $<?php echo number_format($suma_total_prestado, 2); ?>
                </td>
                <td style="border: 1px solid #999; padding: 8px; text-align: right;">-</td>
                <td style="border: 1px solid #999; padding: 8px; text-align: right;">
                    $<?php echo number_format($suma_total_pendiente, 2); ?>
                </td>
                <td style="border: 1px solid #999;"></td>
            </tr>
        </tfoot>
    </table>

    <br>
    <p style="text-align: center; font-size: 0.8rem; color: #666;">--- Fin del Reporte ---</p>
</div>

<style>
    .documento-impresion {
        background: white; padding: 40px; max-width: 900px; margin: 0 auto;
        box-shadow: 0 0 10px rgba(0,0,0,0.1); font-family: 'Times New Roman', serif; color: #000;
    }
    @media print {
        body { background: white; }
        /* IMPORTANTE: Ocultamos botones y sidebar */
        .print-hide, .sidebar, .topbar { display: none !important; }
        .main-content { margin: 0; padding: 0; width: 100%; }
        .documento-impresion { box-shadow: none; max-width: 100%; padding: 0; }
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    }
</style>

<?php require_once '../../includes/footer.php'; ?>