<?php
// modules/ahorros/estado_cuenta.php
require_once '../../includes/header.php';
require_once '../../config/db.php';

if (!isset($_GET['id'])) { header("Location: index.php"); exit; }
$miembro_id = $_GET['id'];

// DATOS
$stmt = $pdo->prepare("
    SELECT mc.*, u.nombre_completo, u.dui, g.nombre as grupo, c.nombre as ciclo 
    FROM Miembro_Ciclo mc
    JOIN Usuario u ON mc.usuario_id = u.id
    JOIN Ciclo c ON mc.ciclo_id = c.id
    JOIN Grupo g ON c.grupo_id = g.id
    WHERE mc.id = ?");
$stmt->execute([$miembro_id]);
$s = $stmt->fetch();

// HISTORIAL
$sql_hist = "SELECT t.*, r.fecha as fecha_mov, r.numero_reunion
             FROM Transaccion_Caja t
             JOIN Reunion r ON t.reunion_id = r.id
             WHERE t.miembro_ciclo_id = ? 
             AND t.tipo_movimiento IN ('AHORRO', 'RETIRO_AHORRO')
             ORDER BY t.id ASC";
$stmt_h = $pdo->prepare($sql_hist);
$stmt_h->execute([$miembro_id]);
$movs = $stmt_h->fetchAll();


// LÓGICA DE RETORNO INTELIGENTE
$origen = isset($_GET['origen']) ? $_GET['origen'] : '';

if ($origen == 'mi_perfil') {
    // Si soy socio viendo mis cosas
    $link_volver = "../../modules/mi_perfil/mis_finanzas.php";
    $texto_volver = "Volver a Mis Finanzas";
} else {
    // Si soy admin/promotora
    $link_volver = "ver.php?id=$miembro_id";
    $texto_volver = "Regresar al Detalle";
}
?>

<div class="flex-between print-hide" style="margin-bottom: 20px;">
    <a href="<?php echo $link_volver; ?>" class="btn btn-secondary">
        <i class='bx bx-arrow-back'></i> <?php echo $texto_volver; ?>
    </a>
    <button onclick="window.print()" class="btn btn-primary">
        <i class='bx bx-printer'></i> IMPRIMIR ESTADO DE CUENTA
    </button>
</div>

<div class="documento-impresion">
    
    <div class="doc-header" style="text-align: center; border-bottom: 2px solid #000; padding-bottom: 20px; margin-bottom: 30px;">
        <h2 style="text-transform: uppercase; margin: 0;">Estado de Cuenta de Ahorros</h2>
        <p style="margin: 5px 0;">GAPC: <?php echo htmlspecialchars($s['grupo']); ?></p>
        <p style="font-size: 0.9rem;">Periodo: <?php echo htmlspecialchars($s['ciclo']); ?></p>
    </div>

    <div class="grid-2" style="margin-bottom: 30px; font-size: 1rem;">
        <div style="border: 1px solid #999; padding: 15px; border-radius: 4px;">
            <h4 style="margin: 0 0 10px 0; border-bottom: 1px solid #ccc;">TITULAR DE LA CUENTA</h4>
            <p style="margin: 2px 0;"><strong>Nombre:</strong> <?php echo htmlspecialchars($s['nombre_completo']); ?></p>
            <p style="margin: 2px 0;"><strong>DUI:</strong> <?php echo htmlspecialchars($s['dui']); ?></p>
        </div>
        <div style="border: 1px solid #999; padding: 15px; border-radius: 4px; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; background-color: #f9f9f9;">
            <small>SALDO DISPONIBLE</small>
            <div style="font-size: 2rem; font-weight: bold;">
                $<?php echo number_format($s['saldo_ahorros'], 2); ?>
            </div>
        </div>
    </div>

    <h4 style="margin-bottom: 5px; text-transform: uppercase; background: #eee; padding: 5px; border: 1px solid #999; border-bottom: 0;">Historial de Movimientos</h4>
    <table class="doc-table" style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background: #eee;">
                <th style="border: 1px solid #999; padding: 8px;">Fecha</th>
                <th style="border: 1px solid #999; padding: 8px;">Reunión</th>
                <th style="border: 1px solid #999; padding: 8px; text-align: right;">Retiros</th>
                <th style="border: 1px solid #999; padding: 8px; text-align: right;">Depósitos</th>
                <th style="border: 1px solid #999; padding: 8px; text-align: right;">Saldo</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $saldo_temp = 0;
            $total_dep = 0;
            $total_ret = 0;

            foreach($movs as $m): 
                if($m['tipo_movimiento'] == 'AHORRO') {
                    $saldo_temp += $m['monto'];
                    $total_dep += $m['monto'];
                    $dep = '$'.number_format($m['monto'], 2);
                    $ret = '-';
                } else {
                    $saldo_temp -= $m['monto'];
                    $total_ret += $m['monto'];
                    $dep = '-';
                    $ret = '$'.number_format($m['monto'], 2);
                }
            ?>
            <tr>
                <td style="border: 1px solid #999; padding: 8px;">
                    <?php echo date('d/m/Y', strtotime($m['fecha_mov'])); ?>
                </td>
                <td style="border: 1px solid #999; padding: 8px;">
                    #<?php echo $m['numero_reunion']; ?>
                </td>
                <td style="border: 1px solid #999; padding: 8px; text-align: right; color: #D32F2F;">
                    <?php echo $ret; ?>
                </td>
                <td style="border: 1px solid #999; padding: 8px; text-align: right; color: #388E3C;">
                    <?php echo $dep; ?>
                </td>
                <td style="border: 1px solid #999; padding: 8px; text-align: right; font-weight: bold;">
                    $<?php echo number_format($saldo_temp, 2); ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr style="background: #f9f9f9; font-weight: bold;">
                <td colspan="2" style="border: 1px solid #999; padding: 8px; text-align: right;">TOTALES:</td>
                <td style="border: 1px solid #999; padding: 8px; text-align: right; color: #D32F2F;">
                    $<?php echo number_format($total_ret, 2); ?>
                </td>
                <td style="border: 1px solid #999; padding: 8px; text-align: right; color: #388E3C;">
                    $<?php echo number_format($total_dep, 2); ?>
                </td>
                <td style="border: 1px solid #999; padding: 8px; text-align: right; background: #eee; font-size: 1.1rem;">
                    $<?php echo number_format($saldo_temp, 2); ?>
                </td>
            </tr>
        </tfoot>
    </table>

    <br><br><br>

    <div style="text-align: center; width: 50%; margin: 0 auto;">
        <hr style="border: 1px solid #000;">
        <p style="margin: 5px 0;"><strong><?php echo htmlspecialchars($s['nombre_completo']); ?></strong></p>
        <small>Firma de Conformidad del Socio</small>
    </div>

    <div style="margin-top: 30px; text-align: center; font-size: 0.8rem; color: #666;">
        <p>Documento oficial generado el <?php echo date('d/m/Y H:i'); ?>.</p>
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
        .documento-impresion { box-shadow: none; max-width: 100%; padding: 0; margin: 0; }
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    }
</style>

<?php require_once '../../includes/footer.php'; ?>