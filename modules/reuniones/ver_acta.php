<?php
// modules/reuniones/ver_acta.php
require_once '../../includes/header.php';
require_once '../../config/db.php';

if (!isset($_GET['id'])) { header("Location: index.php"); exit; }
$reunion_id = $_GET['id'];

// Obtener datos
$stmt = $pdo->prepare("
    SELECT r.*, c.nombre as ciclo, g.nombre as grupo, g.distrito_id 
    FROM Reunion r
    JOIN Ciclo c ON r.ciclo_id = c.id
    JOIN Grupo g ON c.grupo_id = g.id
    WHERE r.id = ?
");
$stmt->execute([$reunion_id]);
$r = $stmt->fetch();

// Calcular diferencia
$diferencia = $r['saldo_fisico_actual'] - $r['saldo_caja_actual'];
?>

<div class="flex-between print-hide" style="margin-bottom: 20px;">
    <a href="lista.php?ciclo_id=<?php echo $r['ciclo_id']; ?>" class="btn btn-secondary">
        <i class='bx bx-arrow-back'></i> Volver al Historial
    </a>
    <button onclick="window.print()" class="btn btn-primary">
        <i class='bx bx-printer'></i> IMPRIMIR ACTA
    </button>
</div>

<div class="documento-impresion">
    
    <div class="doc-header" style="text-align: center; border-bottom: 2px solid #000; padding-bottom: 20px; margin-bottom: 30px;">
        <h2 style="text-transform: uppercase; margin: 0;">Acta de Cierre de Reunión</h2>
        <p style="margin: 5px 0;">GAPC: <?php echo htmlspecialchars($r['grupo']); ?></p>
        <p style="font-size: 0.9rem;">Ciclo: <?php echo htmlspecialchars($r['ciclo']); ?></p>
        <small>Folio Reunión: #<?php echo str_pad($r['numero_reunion'], 3, '0', STR_PAD_LEFT); ?></small>
    </div>

    <div class="grid-2" style="margin-bottom: 30px; font-size: 1rem;">
        <div style="border: 1px solid #999; padding: 15px; border-radius: 4px;">
            <h4 style="margin: 0 0 10px 0; border-bottom: 1px solid #ccc;">DETALLES DE SESIÓN</h4>
            <p style="margin: 2px 0;"><strong>Fecha:</strong> <?php echo date('d/m/Y', strtotime($r['fecha'])); ?></p>
            <p style="margin: 2px 0;"><strong>Estado:</strong> <?php echo $r['estado']; ?></p>
        </div>
        <div style="border: 1px solid #999; padding: 15px; border-radius: 4px;">
            <h4 style="margin: 0 0 10px 0; border-bottom: 1px solid #ccc;">RESUMEN DE CAJA</h4>
            <p style="margin: 2px 0;"><strong>Saldo Inicial:</strong> $<?php echo number_format($r['saldo_caja_inicial'], 2); ?></p>
            <p style="margin: 2px 0;"><strong>Saldo Final:</strong> $<?php echo number_format($r['saldo_caja_actual'], 2); ?></p>
        </div>
    </div>

    <h4 style="margin-bottom: 5px; text-transform: uppercase; background: #eee; padding: 5px; border: 1px solid #999; border-bottom: 0;">Balance de Movimientos</h4>
    <table class="doc-table" style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background: #eee;">
                <th style="border: 1px solid #999; padding: 8px;">Concepto</th>
                <th style="border: 1px solid #999; padding: 8px; text-align: right;">Monto</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="border: 1px solid #999; padding: 8px;">(+) Total Entradas (Ahorros, Pagos, Multas)</td>
                <td style="border: 1px solid #999; padding: 8px; text-align: right;">$<?php echo number_format($r['total_entradas'], 2); ?></td>
            </tr>
            <tr>
                <td style="border: 1px solid #999; padding: 8px;">(-) Total Salidas (Préstamos, Gastos)</td>
                <td style="border: 1px solid #999; padding: 8px; text-align: right;">$<?php echo number_format($r['total_salidas'], 2); ?></td>
            </tr>
            <tr style="background-color: #f9f9f9; font-weight: bold;">
                <td style="border: 1px solid #999; padding: 8px;">(=) SALDO TEÓRICO EN SISTEMA</td>
                <td style="border: 1px solid #999; padding: 8px; text-align: right;">$<?php echo number_format($r['saldo_caja_actual'], 2); ?></td>
            </tr>
        </tbody>
    </table>

    <br>

    <h4 style="margin-bottom: 5px; text-transform: uppercase; background: #eee; padding: 5px; border: 1px solid #999; border-bottom: 0;">Arqueo de Efectivo</h4>
    <table class="doc-table" style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="border: 1px solid #999; padding: 8px; width: 70%;">Dinero contado en efectivo al cierre:</td>
            <td style="border: 1px solid #999; padding: 8px; text-align: right; font-size: 1.1rem; font-weight: bold;">
                $<?php echo number_format($r['saldo_fisico_actual'], 2); ?>
            </td>
        </tr>
        <?php if(abs($diferencia) > 0.00): ?>
        <tr>
            <td style="border: 1px solid #999; padding: 8px; color: #D32F2F;">Diferencia (Sobrante/Faltante):</td>
            <td style="border: 1px solid #999; padding: 8px; text-align: right; color: #D32F2F; font-weight: bold;">
                $<?php echo number_format($diferencia, 2); ?>
            </td>
        </tr>
        <?php endif; ?>
    </table>

    <br><br>

    <div style="border: 1px solid #999; padding: 15px; min-height: 80px; border-radius: 4px;">
        <strong>NOTAS / ACUERDOS:</strong>
        <p style="white-space: pre-wrap; color: #444; margin-top: 5px;">
            <?php 
                $partes = explode("Observaciones:", $r['acta']);
                echo isset($partes[1]) ? trim($partes[1]) : "Ninguna observación registrada."; 
            ?>
        </p>
    </div>

    <br><br><br>

    <div style="display: flex; justify-content: space-between; text-align: center; margin-top: 40px;">
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