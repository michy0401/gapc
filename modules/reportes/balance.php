<?php
// modules/reportes/balance.php
require_once '../../includes/header.php';
require_once '../../config/db.php';

if (!isset($_GET['ciclo_id'])) { header("Location: index.php"); exit; }
$ciclo_id = $_GET['ciclo_id'];



// 1. DATOS DEL CICLO
$stmt_c = $pdo->prepare("SELECT c.*, g.nombre as grupo FROM Ciclo c JOIN Grupo g ON c.grupo_id = g.id WHERE c.id = ?");
$stmt_c->execute([$ciclo_id]);
$ciclo = $stmt_c->fetch();

// 2. CÁLCULOS FINANCIEROS

// A. Ahorros Totales (Pasivo/Capital)
$stmt_ahorros = $pdo->prepare("SELECT SUM(saldo_ahorros) FROM Miembro_Ciclo WHERE ciclo_id = ?");
$stmt_ahorros->execute([$ciclo_id]);
$total_ahorros = $stmt_ahorros->fetchColumn() ?: 0;

// B. Dinero en Caja (Activo Corriente) - CÁLCULO DINÁMICO REAL
// Sumar Saldo Inicial
$stmt_ini = $pdo->prepare("SELECT saldo_caja_inicial FROM Reunion WHERE ciclo_id = ? ORDER BY id ASC LIMIT 1");
$stmt_ini->execute([$ciclo_id]);
$saldo_inicial_historico = $stmt_ini->fetchColumn() ?: 0;

// Sumar Entradas
$sql_entradas = "SELECT SUM(monto) FROM Transaccion_Caja tc JOIN Reunion r ON tc.reunion_id = r.id 
                 WHERE r.ciclo_id = ? 
                 AND tc.tipo_movimiento IN ('AHORRO','PAGO_PRESTAMO_CAPITAL','PAGO_PRESTAMO_INTERES','PAGO_MULTA','INGRESO_EXTRA')";
$stmt_ent = $pdo->prepare($sql_entradas);
$stmt_ent->execute([$ciclo_id]);
$total_entradas = $stmt_ent->fetchColumn() ?: 0;

// Sumar Salidas
$sql_salidas = "SELECT SUM(monto) FROM Transaccion_Caja tc JOIN Reunion r ON tc.reunion_id = r.id 
                WHERE r.ciclo_id = ? 
                AND tc.tipo_movimiento IN ('RETIRO_AHORRO','DESEMBOLSO_PRESTAMO','GASTO_OPERATIVO')";
$stmt_sal = $pdo->prepare($sql_salidas);
$stmt_sal->execute([$ciclo_id]);
$total_salidas = $stmt_sal->fetchColumn() ?: 0;

$caja_actual = $saldo_inicial_historico + $total_entradas - $total_salidas;


// C. Cartera de Préstamos (Activo Por Cobrar)
// NOTA: Si pagaste todo, esto debe dar 0. Si deben, debe dar > 0.
$sql_cartera = "
    SELECT SUM(
        GREATEST(0, p.monto_aprobado - IFNULL((SELECT SUM(monto) FROM Transaccion_Caja WHERE prestamo_id = p.id AND tipo_movimiento='PAGO_PRESTAMO_CAPITAL'), 0))
    ) 
    FROM Prestamo p 
    JOIN Miembro_Ciclo mc ON p.miembro_ciclo_id = mc.id
    WHERE mc.ciclo_id = ? AND p.estado IN ('ACTIVO', 'MORA', 'FINALIZADO')";
$stmt_cartera = $pdo->prepare($sql_cartera);
$stmt_cartera->execute([$ciclo_id]);
$dinero_en_calle = $stmt_cartera->fetchColumn() ?: 0;


// D. Ganancias (CORREGIDO AQUÍ) 🚨
// Antes fallaba porque estaba en una línea. Ahora lo separamos.
$sql_ganancia = "SELECT SUM(t.monto) 
                 FROM Transaccion_Caja t 
                 JOIN Reunion r ON t.reunion_id = r.id 
                 WHERE r.ciclo_id = ? 
                 AND t.tipo_movimiento IN ('PAGO_PRESTAMO_INTERES', 'PAGO_MULTA', 'INGRESO_EXTRA')";
$stmt_gan = $pdo->prepare($sql_ganancia);
$stmt_gan->execute([$ciclo_id]);
$total_ganancia = $stmt_gan->fetchColumn(); 
$total_ganancia = $total_ganancia ? $total_ganancia : 0;

// Gastos
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

// E. Totales Generales
$total_activos = $caja_actual + $dinero_en_calle;
$total_pasivo_patrimonio = $total_ahorros + $utilidad_neta;
$diferencia_contable = $total_activos - $total_pasivo_patrimonio;

// CAPTURAR ORIGEN PARA VOLVER A LA PESTAÑA CORRECTA
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
        <h2 style="text-transform: uppercase; margin: 0;">Balance General</h2>
        <p style="margin: 5px 0;">GAPC: <?php echo htmlspecialchars($ciclo['grupo']); ?></p>
        <p style="font-size: 0.9rem;"><?php echo htmlspecialchars($ciclo['nombre']); ?> | Al: <?php echo date('d/m/Y'); ?></p>
    </div>

    <table class="doc-table" style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background: #eee;">
                <th style="border: 1px solid #999; padding: 8px; text-align: left;">CUENTA</th>
                <th style="border: 1px solid #999; padding: 8px; text-align: right; width: 150px;">MONTO</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan="2" style="border: 1px solid #999; padding: 8px; background-color: #f9f9f9;"><strong>1. ACTIVOS (Lo que tenemos)</strong></td>
            </tr>
            <tr>
                <td style="border: 1px solid #999; padding: 8px; padding-left: 20px;">Efectivo en Caja (Disponible)</td>
                <td style="border: 1px solid #999; padding: 8px; text-align: right;">$<?php echo number_format($caja_actual, 2); ?></td>
            </tr>
            <tr>
                <td style="border: 1px solid #999; padding: 8px; padding-left: 20px;">Cartera de Préstamos (Por Cobrar)</td>
                <td style="border: 1px solid #999; padding: 8px; text-align: right;">$<?php echo number_format($dinero_en_calle, 2); ?></td>
            </tr>
            <tr style="font-weight: bold;">
                <td style="border: 1px solid #999; padding: 8px; text-align: right;">TOTAL ACTIVOS:</td>
                <td style="border: 1px solid #999; padding: 8px; text-align: right;">$<?php echo number_format($total_activos, 2); ?></td>
            </tr>

            <tr><td colspan="2" style="border:none; height:15px;"></td></tr>

            <tr>
                <td colspan="2" style="border: 1px solid #999; padding: 8px; background-color: #f9f9f9;"><strong>2. PASIVO Y PATRIMONIO (De quién es el dinero)</strong></td>
            </tr>
            <tr>
                <td style="border: 1px solid #999; padding: 8px; padding-left: 20px;">Ahorros de Socias (Capital Social)</td>
                <td style="border: 1px solid #999; padding: 8px; text-align: right;">$<?php echo number_format($total_ahorros, 2); ?></td>
            </tr>
            <tr>
                <td style="border: 1px solid #999; padding: 8px; padding-left: 20px;">Utilidad Neta (Ganancias por Repartir)</td>
                <td style="border: 1px solid #999; padding: 8px; text-align: right;">$<?php echo number_format($utilidad_neta, 2); ?></td>
            </tr>
            <tr style="font-weight: bold;">
                <td style="border: 1px solid #999; padding: 8px; text-align: right;">TOTAL PASIVO + PATRIMONIO:</td>
                <td style="border: 1px solid #999; padding: 8px; text-align: right;">$<?php echo number_format($total_pasivo_patrimonio, 2); ?></td>
            </tr>
        </tbody>
    </table>

    <?php if (abs($diferencia_contable) > 0.05): ?>
        <div style="margin-top: 20px; padding: 10px; border: 1px solid #D32F2F; color: #D32F2F; text-align: center; font-size: 0.9rem;">
            <strong>⚠️ NOTA AUDITORÍA:</strong> Existe una diferencia contable de $<?php echo number_format($diferencia_contable, 2); ?>. Revisar cuadres de caja.
        </div>
    <?php endif; ?>

    <br><br><br>

    <div style="display: flex; justify-content: space-between; text-align: center; margin-top: 50px;">
        <div style="width: 30%;">
            <hr style="border: 1px solid #000;">
            <small>PRESIDENTA</small>
        </div>
        <div style="width: 30%;">
            <hr style="border: 1px solid #000;">
            <small>TESORERA</small>
        </div>
        <div style="width: 30%;">
            <hr style="border: 1px solid #000;">
            <small>SECRETARIA</small>
        </div>
    </div>

    <div style="margin-top: 30px; text-align: center; font-size: 0.8rem; color: #666;">
        <p>Documento generado el <?php echo date('d/m/Y H:i'); ?>.</p>
    </div>

</div>

<style>
    .documento-impresion {
        background: white; padding: 40px; max-width: 850px; margin: 0 auto;
        box-shadow: 0 0 10px rgba(0,0,0,0.1); font-family: 'Times New Roman', serif; color: #000;
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