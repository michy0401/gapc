<?php
// modules/grupos/acta_cierre.php
require_once '../../includes/header.php';
require_once '../../config/db.php';

if (!isset($_GET['ciclo_id'])) { header("Location: index.php"); exit; }
$ciclo_id = $_GET['ciclo_id'];

// DETECTAR ORIGEN
$origen = isset($_GET['origen']) ? $_GET['origen'] : '';

// 1. DATOS DEL CICLO
$stmt = $pdo->prepare("SELECT c.*, g.nombre as grupo FROM Ciclo c JOIN Grupo g ON c.grupo_id = g.id WHERE c.id = ?");
$stmt->execute([$ciclo_id]);
$ciclo = $stmt->fetch();

// 2. CÁLCULOS FINANCIEROS (BLINDADOS)

// Ganancias (Intereses + Multas + Ingresos Extra)
$sql_ganancia = "
    SELECT SUM(t.monto) 
    FROM Transaccion_Caja t 
    JOIN Reunion r ON t.reunion_id = r.id 
    WHERE r.ciclo_id = ? 
    AND t.tipo_movimiento IN ('PAGO_PRESTAMO_INTERES', 'PAGO_MULTA', 'INGRESO_EXTRA')";

$stmt_gan = $pdo->prepare($sql_ganancia);
$stmt_gan->execute([$ciclo_id]);
$total_ganancia = (float) $stmt_gan->fetchColumn(); // Convertimos a número

// Gastos
$sql_gastos = "
    SELECT SUM(t.monto) 
    FROM Transaccion_Caja t 
    JOIN Reunion r ON t.reunion_id = r.id 
    WHERE r.ciclo_id = ? 
    AND t.tipo_movimiento = 'GASTO_OPERATIVO'";

$stmt_gas = $pdo->prepare($sql_gastos);
$stmt_gas->execute([$ciclo_id]);
$total_gastos = (float) $stmt_gas->fetchColumn(); // Convertimos a número

// Utilidad Neta
$utilidad_neta = $total_ganancia - $total_gastos;

// Capital Social (Ahorros Totales)
$stmt_a = $pdo->prepare("SELECT SUM(saldo_ahorros) FROM Miembro_Ciclo WHERE ciclo_id = ?");
$stmt_a->execute([$ciclo_id]);
$total_ahorro = (float) $stmt_a->fetchColumn(); // Convertimos a número

// Factor de Distribución (PROTECCIÓN CONTRA ERROR / 0)
if ($total_ahorro > 0) {
    $factor = $utilidad_neta / $total_ahorro;
} else {
    $factor = 0;
}

// Socios para firmas
$stmt_s = $pdo->prepare("SELECT mc.*, u.nombre_completo, u.dui FROM Miembro_Ciclo mc JOIN Usuario u ON mc.usuario_id = u.id WHERE mc.ciclo_id = ? ORDER BY u.nombre_completo");
$stmt_s->execute([$ciclo_id]);
$socios = $stmt_s->fetchAll();
?>

<div class="flex-between print-hide" style="margin-bottom: 20px;">
    <?php 
        // LÓGICA DEL BOTÓN VOLVER
        if (strpos($origen, 'reportes_') !== false) {
            // Si viene de reportes, extraemos el estado (ej: reportes_LIQUIDADO -> LIQUIDADO)
            $estado_filtro = str_replace('reportes_', '', $origen);
            $link_volver = "../reportes/index.php?estado=" . $estado_filtro;
            $texto_volver = "Volver a Reportes";
        } else {
            // Por defecto vuelve al grupo
            $link_volver = "ver.php?id=" . $ciclo['grupo_id'];
            $texto_volver = "Volver al Grupo";
        }
    ?>
    
    <a href="<?php echo $link_volver; ?>" class="btn btn-secondary">
        <i class='bx bx-arrow-back'></i> <?php echo $texto_volver; ?>
    </a>
    
    <button onclick="window.print()" class="btn btn-primary">
        <i class='bx bx-printer'></i> IMPRIMIR ACTA DE LIQUIDACIÓN
    </button>
</div>

<div class="documento-impresion">
    
    <div class="doc-header" style="text-align: center; border-bottom: 2px solid #000; padding-bottom: 20px; margin-bottom: 30px;">
        <h1 style="text-transform: uppercase; margin: 0; font-size: 1.8rem;">Acta de Liquidación de Ciclo</h1>
        <p style="margin: 10px 0; font-size: 1.1rem;">Grupo de Ahorro y Préstamo Comunitario</p>
        <h3 style="margin: 5px 0;">"<?php echo htmlspecialchars($ciclo['grupo']); ?>"</h3>
    </div>

    <p style="text-align: justify;">
        En la fecha <strong><?php echo date('d/m/Y'); ?></strong>, se reúnen los miembros del grupo para realizar el cierre oficial del ciclo operativo <strong>"<?php echo htmlspecialchars($ciclo['nombre']); ?>"</strong>, declarando que se han recuperado todos los préstamos y se procede a la distribución de ahorros y utilidades según el siguiente detalle:
    </p>

    <h3 style="border-bottom: 1px solid #000; margin-top: 30px;">I. Resumen Financiero</h3>
    <table class="doc-table" style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="padding: 8px; border: 1px solid #999;">Capital Social (Ahorros):</td>
            <td style="padding: 8px; border: 1px solid #999; text-align: right;">$<?php echo number_format($total_ahorro, 2); ?></td>
        </tr>
        <tr>
            <td style="padding: 8px; border: 1px solid #999;">Utilidad Neta Generada:</td>
            <td style="padding: 8px; border: 1px solid #999; text-align: right;">$<?php echo number_format($utilidad_neta, 2); ?></td>
        </tr>
        <tr style="background: #eee; font-weight: bold;">
            <td style="padding: 8px; border: 1px solid #999;">MONTO TOTAL A REPARTIR:</td>
            <td style="padding: 8px; border: 1px solid #999; text-align: right;">$<?php echo number_format($total_ahorro + $utilidad_neta, 2); ?></td>
        </tr>
    </table>

    <h3 style="border-bottom: 1px solid #000; margin-top: 30px;">II. Detalle de Distribución y Firmas</h3>
    <table class="doc-table" style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
        <thead>
            <tr style="background: #eee;">
                <th style="border: 1px solid #999; padding: 5px;">Nombre Socia</th>
                <th style="border: 1px solid #999; padding: 5px; text-align: right;">Ahorro</th>
                <th style="border: 1px solid #999; padding: 5px; text-align: right;">Utilidad</th>
                <th style="border: 1px solid #999; padding: 5px; text-align: right;">TOTAL</th>
                <th style="border: 1px solid #999; padding: 5px; text-align: center; width: 150px;">FIRMA RECIBIDO</th>
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
                <td style="border: 1px solid #999; padding: 5px; text-align: right; font-weight: bold;">$<?php echo number_format($total, 2); ?></td>
                <td style="border: 1px solid #999; padding: 5px;"></td> </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <br><br><br>
    
    <p style="text-align: center;">Damos fe de la transparencia y exactitud de estos datos.</p>

    <div style="display: flex; justify-content: space-between; text-align: center; margin-top: 50px;">
        <div style="width: 30%;">
            <hr style="border: 1px solid #000;">
            <strong>PRESIDENTA</strong>
        </div>
        <div style="width: 30%;">
            <hr style="border: 1px solid #000;">
            <strong>TESORERA</strong>
        </div>
        <div style="width: 30%;">
            <hr style="border: 1px solid #000;">
            <strong>SECRETARIA</strong>
        </div>
    </div>

</div>

<style>
    .documento-impresion {
        background: white; padding: 50px; max-width: 900px; margin: 0 auto;
        box-shadow: 0 0 10px rgba(0,0,0,0.1); font-family: 'Times New Roman', serif; color: #000;
    }
    @media print {
        body { background: white; }
        .print-hide, .sidebar, .topbar { display: none !important; }
        .main-content { margin: 0; padding: 0; width: 100%; }
        .documento-impresion { box-shadow: none; max-width: 100%; padding: 0; }
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    }
</style>

<?php require_once '../../includes/footer.php'; ?>