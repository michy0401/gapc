<?php
require_once '../../includes/header.php';
require_once '../../config/db.php';

if (!isset($_GET['ciclo_id'])) { header("Location: index.php"); exit; }
$ciclo_id = $_GET['ciclo_id'];

$stmt_c = $pdo->prepare("SELECT c.*, g.nombre as grupo FROM Ciclo c JOIN Grupo g ON c.grupo_id = g.id WHERE c.id = ?");
$stmt_c->execute([$ciclo_id]);
$ciclo = $stmt_c->fetch();

// CONSULTA DE AHORROS
$sql = "SELECT mc.*, u.nombre_completo, u.dui
        FROM Miembro_Ciclo mc
        JOIN Usuario u ON mc.usuario_id = u.id
        WHERE mc.ciclo_id = ?
        ORDER BY u.nombre_completo ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$ciclo_id]);
$socios = $stmt->fetchAll();

$total_ahorrado = 0;
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
        <h2 style="text-transform: uppercase; margin: 0;">Sábana de Ahorros Acumulados</h2>
        <p style="margin: 5px 0;">GAPC: <?php echo htmlspecialchars($ciclo['grupo']); ?></p>
        <p style="font-size: 0.9rem;">Ciclo: <?php echo htmlspecialchars($ciclo['nombre']); ?></p>
    </div>

    <table class="doc-table" style="width: 100%; border-collapse: collapse; font-size: 0.95rem;">
        <thead>
            <tr style="background: #eee;">
                <th style="border: 1px solid #999; padding: 8px; width: 5%;">#</th>
                <th style="border: 1px solid #999; padding: 8px;">Nombre del Socio</th>
                <th style="border: 1px solid #999; padding: 8px;">DUI</th>
                <th style="border: 1px solid #999; padding: 8px; text-align: right;">Ahorro Total</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $i = 1;
            foreach($socios as $s): 
                $total_ahorrado += $s['saldo_ahorros'];
            ?>
            <tr>
                <td style="border: 1px solid #999; padding: 5px; text-align: center;"><?php echo $i++; ?></td>
                <td style="border: 1px solid #999; padding: 5px;">
                    <?php echo htmlspecialchars($s['nombre_completo']); ?>
                </td>
                <td style="border: 1px solid #999; padding: 5px;">
                    <?php echo htmlspecialchars($s['dui']); ?>
                </td>
                <td style="border: 1px solid #999; padding: 5px; text-align: right; font-weight: bold;">
                    $<?php echo number_format($s['saldo_ahorros'], 2); ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr style="background: #f9f9f9; font-weight: bold;">
                <td colspan="3" style="border: 1px solid #999; padding: 8px; text-align: right;">GRAN TOTAL AHORRADO:</td>
                <td style="border: 1px solid #999; padding: 8px; text-align: right; font-size: 1.1rem;">
                    $<?php echo number_format($total_ahorrado, 2); ?>
                </td>
            </tr>
        </tfoot>
    </table>

    <br><br>
    <div style="display: flex; justify-content: space-between; text-align: center; margin-top: 40px;">
        <div style="width: 40%;">
            <hr style="border: 1px solid #000;">
            <small>Sello y Firma Presidenta</small>
        </div>
        <div style="width: 40%;">
            <hr style="border: 1px solid #000;">
            <small>Sello y Firma Tesorera</small>
        </div>
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
        .documento-impresion { box-shadow: none; max-width: 100%; padding: 0; }
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    }
</style>

<?php require_once '../../includes/footer.php'; ?>