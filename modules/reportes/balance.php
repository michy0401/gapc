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

// 2. CÁLCULOS MACRO (SQL POWER)
// A. Ahorros Totales (Capital Social)
$stmt_ahorros = $pdo->prepare("SELECT SUM(saldo_ahorros) FROM Miembro_Ciclo WHERE ciclo_id = ?");
$stmt_ahorros->execute([$ciclo_id]);
$total_ahorros = $stmt_ahorros->fetchColumn() ?: 0;

// B. Dinero en Caja (Efectivo disponible)
// Buscamos la última reunión cerrada para tener el saldo físico exacto, 
// o calculamos si hay una abierta.
$stmt_caja = $pdo->prepare("SELECT saldo_caja_actual FROM Reunion WHERE ciclo_id = ? ORDER BY id DESC LIMIT 1");
$stmt_caja->execute([$ciclo_id]);
$caja_actual = $stmt_caja->fetchColumn() ?: 0;

// C. Cartera de Préstamos (Dinero en la calle)
// Sumamos (Monto Prestado - Capital Pagado) de todos los préstamos activos
$sql_cartera = "
    SELECT SUM(p.monto_aprobado - IFNULL((SELECT SUM(monto) FROM Transaccion_Caja WHERE prestamo_id = p.id AND tipo_movimiento='PAGO_PRESTAMO_CAPITAL'), 0)) 
    FROM Prestamo p 
    JOIN Miembro_Ciclo mc ON p.miembro_ciclo_id = mc.id
    WHERE mc.ciclo_id = ? AND p.estado = 'ACTIVO'";
$stmt_cartera = $pdo->prepare($sql_cartera);
$stmt_cartera->execute([$ciclo_id]);
$dinero_en_calle = $stmt_cartera->fetchColumn() ?: 0;

// D. Ganancias (Intereses + Multas + Otros)
// Obtenemos esto sumando transacciones de tipo ganancia en todas las reuniones de este ciclo
$sql_ganancia = "
    SELECT SUM(monto) FROM Transaccion_Caja tc
    JOIN Reunion r ON tc.reunion_id = r.id
    WHERE r.ciclo_id = ? 
    AND tc.tipo_movimiento IN ('PAGO_PRESTAMO_INTERES', 'PAGO_MULTA', 'INGRESO_EXTRA')";
$stmt_ganancia = $pdo->prepare($sql_ganancia);
$stmt_ganancia->execute([$ciclo_id]);
$total_ganancia = $stmt_ganancia->fetchColumn() ?: 0;

// E. Gastos Operativos
$sql_gastos = "
    SELECT SUM(monto) FROM Transaccion_Caja tc
    JOIN Reunion r ON tc.reunion_id = r.id
    WHERE r.ciclo_id = ? 
    AND tc.tipo_movimiento = 'GASTO_OPERATIVO'";
$stmt_gastos = $pdo->prepare($sql_gastos);
$stmt_gastos->execute([$ciclo_id]);
$total_gastos = $stmt_gastos->fetchColumn() ?: 0;

$utilidad_neta = $total_ganancia - $total_gastos;
$activos_totales = $caja_actual + $dinero_en_calle;
?>

<div class="container" style="max-width: 900px;">
    
    <div class="flex-between print-hide" style="margin-bottom: 20px;">
        <a href="index.php" class="btn btn-secondary">
            <i class='bx bx-arrow-back'></i> Volver a Reportes
        </a>
        <button onclick="window.print()" class="btn btn-primary">
            <i class='bx bx-printer'></i> Imprimir Balance
        </button>
    </div>

    <div class="card" id="hoja-impresion">
        <div class="text-center" style="border-bottom: 2px solid var(--color-brand); padding-bottom: 20px; margin-bottom: 20px;">
            <h2 style="margin:0; color: var(--color-brand);">BALANCE GENERAL</h2>
            <h3 style="margin:5px 0; font-weight: normal;"><?php echo htmlspecialchars($ciclo['grupo']); ?></h3>
            <p style="color: #666;"><?php echo htmlspecialchars($ciclo['nombre']); ?> | Fecha: <?php echo date('d/m/Y'); ?></p>
        </div>

        <div class="grid-2" style="margin-bottom: 30px;">
            <div style="background: #E3F2FD; padding: 20px; border-radius: 12px; text-align: center;">
                <small style="color: #1565C0; font-weight: bold; text-transform: uppercase;">Activos Totales (Lo que tenemos)</small>
                <div style="font-size: 2.5rem; font-weight: bold; color: #1565C0;">
                    $<?php echo number_format($activos_totales, 2); ?>
                </div>
                <p style="font-size: 0.9rem; color: #1565C0;">Caja + Préstamos</p>
            </div>

            <div style="background: #FFF3E0; padding: 20px; border-radius: 12px; text-align: center;">
                <small style="color: #EF6C00; font-weight: bold; text-transform: uppercase;">Utilidad Neta (Ganancia)</small>
                <div style="font-size: 2.5rem; font-weight: bold; color: #EF6C00;">
                    $<?php echo number_format($utilidad_neta, 2); ?>
                </div>
                <p style="font-size: 0.9rem; color: #EF6C00;">Intereses + Multas - Gastos</p>
            </div>
        </div>

        <h3 style="color: var(--color-brand); border-bottom: 1px solid #eee; padding-bottom: 10px;">Desglose de Cuentas</h3>
        
        <table class="table" style="margin-top: 10px;">
            <tr style="background: #FAFAFA;"><td colspan="2"><strong>1. ¿DÓNDE ESTÁ EL DINERO? (ACTIVOS)</strong></td></tr>
            <tr>
                <td>Efectivo en Caja (Mano)</td>
                <td class="text-right">$<?php echo number_format($caja_actual, 2); ?></td>
            </tr>
            <tr>
                <td>Cartera de Préstamos (Calle)</td>
                <td class="text-right">$<?php echo number_format($dinero_en_calle, 2); ?></td>
            </tr>
            <tr style="border-top: 2px solid #ccc;">
                <td><strong>TOTAL ACTIVOS</strong></td>
                <td class="text-right"><strong>$<?php echo number_format($activos_totales, 2); ?></strong></td>
            </tr>

            <tr><td colspan="2" style="border:none; height: 20px;"></td></tr>

            <tr style="background: #FAFAFA;"><td colspan="2"><strong>2. ¿DE QUIÉN ES EL DINERO? (PASIVO + PATRIMONIO)</strong></td></tr>
            <tr>
                <td>Ahorros de los Socios</td>
                <td class="text-right">$<?php echo number_format($total_ahorros, 2); ?></td>
            </tr>
            <tr>
                <td>Utilidades por Repartir</td>
                <td class="text-right">$<?php echo number_format($utilidad_neta, 2); ?></td>
            </tr>
            <tr style="border-top: 2px solid #ccc;">
                <td><strong>TOTAL OBLIGACIONES</strong></td>
                <td class="text-right"><strong>$<?php echo number_format($total_ahorros + $utilidad_neta, 2); ?></strong></td>
            </tr>
        </table>

        <?php if (abs($activos_totales - ($total_ahorros + $utilidad_neta)) > 0.05): ?>
            <div style="margin-top: 20px; padding: 10px; background: #FFEBEE; color: #D32F2F; border-radius: 8px; text-align: center;">
                ⚠️ <strong>Atención:</strong> Existe una pequeña diferencia contable de $<?php echo number_format($activos_totales - ($total_ahorros + $utilidad_neta), 2); ?>. Verifique si hubo ingresos o gastos no registrados.
            </div>
        <?php endif; ?>

    </div>
</div>

<style>
    .text-right { text-align: right; }
    /* Estilo para ocultar botones al imprimir */
    @media print {
        .print-hide, .sidebar { display: none !important; }
        .main-content { margin: 0; width: 100%; }
        body { background: white; }
        .card { box-shadow: none; border: none; }
    }
</style>

<?php require_once '../../includes/footer.php'; ?>