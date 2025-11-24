<?php
// modules/prestamos/estado_cuenta.php
require_once '../../includes/header.php';
require_once '../../config/db.php';

if (!isset($_GET['id'])) { header("Location: index.php"); exit; }
$prestamo_id = $_GET['id'];

// (Mismas consultas para obtener datos frescos)
$sql = "SELECT p.*, u.nombre_completo, u.dui, u.telefono, g.nombre as grupo 
        FROM Prestamo p
        JOIN Miembro_Ciclo mc ON p.miembro_ciclo_id = mc.id
        JOIN Usuario u ON mc.usuario_id = u.id
        JOIN Ciclo c ON mc.ciclo_id = c.id
        JOIN Grupo g ON c.grupo_id = g.id
        WHERE p.id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$prestamo_id]);
$p = $stmt->fetch();

// Historial
$sql_hist = "SELECT t.*, r.fecha as fecha_mov, r.numero_reunion
             FROM Transaccion_Caja t
             JOIN Reunion r ON t.reunion_id = r.id
             WHERE t.prestamo_id = ? ORDER BY t.id ASC";
$movimientos = $pdo->prepare($sql_hist);
$movimientos->execute([$prestamo_id]);
$movs = $movimientos->fetchAll();

// Cálculos
$saldo_actual = $p['monto_aprobado'];
$total_capital = 0;
$total_interes = 0;


// LÓGICA DE RETORNO INTELIGENTE
$origen = isset($_GET['origen']) ? $_GET['origen'] : '';

if ($origen == 'mi_perfil') {
    $link_volver = "../../modules/mi_perfil/mis_finanzas.php";
    $texto_volver = "Volver a Mis Finanzas";
} else {
    $link_volver = "ver.php?id=$prestamo_id";
    $texto_volver = "Regresar al Detalle";
}
?>

<div class="flex-between print-hide" style="margin-bottom: 20px;">
    <a href="<?php echo $link_volver; ?>" class="btn btn-secondary">
        <i class='bx bx-arrow-back'></i> <?php echo $texto_volver; ?>
    </a>
    <button onclick="window.print()" class="btn btn-primary">
        <i class='bx bx-printer'></i> IMPRIMIR
    </button>
</div>

<div class="documento-impresion">
    
    <div class="doc-header" style="text-align: center; border-bottom: 2px solid #000; padding-bottom: 20px; margin-bottom: 30px;">
        <h2 style="text-transform: uppercase; margin: 0;">Estado de Cuenta de Crédito</h2>
        <p style="margin: 5px 0;">GAPC: <?php echo htmlspecialchars($p['grupo']); ?></p>
        <p style="font-size: 0.9rem;">Fecha de Emisión: <?php echo date('d/m/Y'); ?></p>
    </div>

    <div class="grid-2" style="margin-bottom: 30px; font-size: 1rem;">
        <div style="border: 1px solid #999; padding: 15px; border-radius: 4px;">
            <h4 style="margin: 0 0 10px 0; border-bottom: 1px solid #ccc;">DATOS DEL SOCIO</h4>
            <p><strong>Nombre:</strong> <?php echo htmlspecialchars($p['nombre_completo']); ?></p>
            <p><strong>DUI:</strong> <?php echo htmlspecialchars($p['dui']); ?></p>
        </div>
        <div style="border: 1px solid #999; padding: 15px; border-radius: 4px;">
            <h4 style="margin: 0 0 10px 0; border-bottom: 1px solid #ccc;">CONDICIONES</h4>
            <p><strong>Monto:</strong> $<?php echo number_format($p['monto_aprobado'], 2); ?></p>
            <p><strong>Tasa:</strong> <?php echo $p['tasa_interes']; ?>% Mensual</p>
        </div>
    </div>

    <table class="doc-table" style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background: #eee;">
                <th style="border: 1px solid #999; padding: 8px;">Fecha</th>
                <th style="border: 1px solid #999; padding: 8px;">Concepto</th>
                <th style="border: 1px solid #999; padding: 8px; text-align: right;">Interés</th>
                <th style="border: 1px solid #999; padding: 8px; text-align: right;">Capital</th>
                <th style="border: 1px solid #999; padding: 8px; text-align: right;">Saldo</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="border: 1px solid #999; padding: 5px;">--</td>
                <td style="border: 1px solid #999; padding: 5px;"><strong>Crédito Inicial</strong></td>
                <td style="border: 1px solid #999; padding: 5px; text-align: right;">-</td>
                <td style="border: 1px solid #999; padding: 5px; text-align: right;">-</td>
                <td style="border: 1px solid #999; padding: 5px; text-align: right;"><strong>$<?php echo number_format($p['monto_aprobado'], 2); ?></strong></td>
            </tr>

            <?php foreach($movs as $m): 
                if ($m['tipo_movimiento'] == 'DESEMBOLSO_PRESTAMO') continue;
                
                $abono = ($m['tipo_movimiento'] == 'PAGO_PRESTAMO_CAPITAL') ? $m['monto'] : 0;
                $int = ($m['tipo_movimiento'] == 'PAGO_PRESTAMO_INTERES') ? $m['monto'] : 0;
                
                $saldo_actual -= $abono;
                $total_capital += $abono;
                $total_interes += $int;
            ?>
            <tr>
                <td style="border: 1px solid #999; padding: 5px;">
                    <?php echo date('d/m/Y', strtotime($m['fecha_mov'])); ?>
                </td>
                <td style="border: 1px solid #999; padding: 5px;">
                    <?php echo htmlspecialchars($m['observacion']); ?>
                </td>
                <td style="border: 1px solid #999; padding: 5px; text-align: right;">
                    <?php echo ($int > 0) ? '$'.number_format($int, 2) : '-'; ?>
                </td>
                <td style="border: 1px solid #999; padding: 5px; text-align: right;">
                    <?php echo ($abono > 0) ? '$'.number_format($abono, 2) : '-'; ?>
                </td>
                <td style="border: 1px solid #999; padding: 5px; text-align: right; font-weight: bold;">
                    $<?php echo number_format($saldo_actual, 2); ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr style="background: #f9f9f9; font-weight: bold;">
                <td colspan="2" style="border: 1px solid #999; padding: 8px; text-align: right;">TOTALES:</td>
                <td style="border: 1px solid #999; padding: 8px; text-align: right;">
                    $<?php echo number_format($total_interes, 2); ?>
                </td>
                <td style="border: 1px solid #999; padding: 8px; text-align: right;">
                    $<?php echo number_format($total_capital, 2); ?>
                </td>
                <td style="border: 1px solid #999; padding: 8px; text-align: right; background: #eee;">
                    $<?php echo number_format($saldo_actual, 2); ?>
                </td>
            </tr>
        </tfoot>
    </table>

    <br><br><br>

    <div style="text-align: center; width: 50%; margin: 0 auto;">
        <hr style="border: 1px solid #000;">
        <strong>Firma de Conformidad</strong>
        <br>
        <?php echo htmlspecialchars($p['nombre_completo']); ?>
    </div>

</div>

<style>
    .documento-impresion {
        background: white; padding: 40px; max-width: 850px; margin: 0 auto;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
        font-family: 'Times New Roman', serif; color: #000;
    }
    @media print {
        body { background: white; }
        .print-hide, .sidebar, .topbar { display: none !important; }
        .main-content { margin: 0; padding: 0; width: 100%; }
        .documento-impresion { box-shadow: none; max-width: 100%; padding: 0; }
    }
</style>

<?php require_once '../../includes/footer.php'; ?>