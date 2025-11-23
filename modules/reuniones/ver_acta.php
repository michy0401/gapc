<?php
// modules/reuniones/ver_acta.php
require_once '../../includes/header.php';
require_once '../../config/db.php';

if (!isset($_GET['id'])) { header("Location: index.php"); exit; }
$reunion_id = $_GET['id'];

// Obtener datos completos (Reunión + Grupo + Ciclo)
$stmt = $pdo->prepare("
    SELECT r.*, c.nombre as ciclo, g.nombre as grupo, g.distrito_id 
    FROM Reunion r
    JOIN Ciclo c ON r.ciclo_id = c.id
    JOIN Grupo g ON c.grupo_id = g.id
    WHERE r.id = ?
");
$stmt->execute([$reunion_id]);
$r = $stmt->fetch();

// Calcular diferencia (para mostrarla si existe)
$diferencia = $r['saldo_fisico_actual'] - $r['saldo_caja_actual'];
?>

<div class="flex-between print-hide" style="margin-bottom: 20px;">
    <a href="lista.php?ciclo_id=<?php echo $r['ciclo_id']; ?>" class="btn btn-secondary">
        <i class='bx bx-arrow-back'></i> Volver al Historial
    </a>
    <button onclick="window.print()" class="btn btn-primary">
        <i class='bx bx-printer'></i> IMPRIMIR ACTA OFICIAL
    </button>
</div>

<div class="documento-impresion">
    
    <div class="doc-header">

        <div>
            <h1 style="margin: 0; font-size: 1.5rem; text-transform: uppercase;">Acta de Cierre de Reunión</h1>
            <p style="margin: 5px 0; color: #666;">Sistema de Gestión de Ahorro y Préstamo Comunitario</p>
        </div>
        <div style="text-align: right;">
            <h3 style="margin: 0; color: var(--color-brand);">N° <?php echo str_pad($r['numero_reunion'], 3, '0', STR_PAD_LEFT); ?></h3>
            <small>Folio de Control</small>
        </div>
    </div>

    <hr style="border: 2px solid #333; margin: 20px 0;">

    <div class="doc-info-grid">
        <div>
            <strong>GRUPO:</strong> <?php echo htmlspecialchars($r['grupo']); ?>
        </div>
        <div>
            <strong>CICLO:</strong> <?php echo htmlspecialchars($r['ciclo']); ?>
        </div>
        <div>
            <strong>FECHA:</strong> <?php echo date('d/m/Y', strtotime($r['fecha'])); ?>
        </div>
    </div>

    <br>

    <h3 style="border-bottom: 1px solid #ccc; padding-bottom: 5px; font-size: 1.1rem;">I. BALANCE DE CAJA</h3>
    <table class="doc-table">
        <thead>
            <tr>
                <th>CONCEPTO</th>
                <th style="text-align: right;">MONTO ($)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Saldo Inicial (Apertura)</td>
                <td class="text-right">$<?php echo number_format($r['saldo_caja_inicial'], 2); ?></td>
            </tr>
            <tr>
                <td>(+) Total Entradas (Ahorros, Pagos, Multas)</td>
                <td class="text-right">$<?php echo number_format($r['total_entradas'], 2); ?></td>
            </tr>
            <tr>
                <td>(-) Total Salidas (Préstamos, Gastos)</td>
                <td class="text-right">$<?php echo number_format($r['total_salidas'], 2); ?></td>
            </tr>
            <tr style="background-color: #f0f0f0; font-weight: bold;">
                <td>(=) SALDO TEÓRICO DEL SISTEMA</td>
                <td class="text-right">$<?php echo number_format($r['saldo_caja_actual'], 2); ?></td>
            </tr>
        </tbody>
    </table>

    <br>

    <h3 style="border-bottom: 1px solid #ccc; padding-bottom: 5px; font-size: 1.1rem;">II. ARQUEO FÍSICO</h3>
    <table class="doc-table">
        <tr>
            <td style="width: 70%;"><strong>Dinero contado en efectivo al cierre:</strong></td>
            <td class="text-right" style="font-size: 1.2rem; font-weight: bold;">
                $<?php echo number_format($r['saldo_fisico_actual'], 2); ?>
            </td>
        </tr>
        <?php if(abs($diferencia) > 0.00): ?>
        <tr>
            <td style="color: var(--color-danger);">Diferencia (Sobrante/Faltante):</td>
            <td class="text-right" style="color: var(--color-danger); font-weight: bold;">
                $<?php echo number_format($diferencia, 2); ?>
            </td>
        </tr>
        <?php endif; ?>
    </table>

    <br>

    <div style="border: 1px solid #ccc; padding: 15px; min-height: 100px; border-radius: 4px;">
        <strong>OBSERVACIONES Y ACUERDOS:</strong>
        <br><br>
        <p style="white-space: pre-wrap; color: #444;">
            <?php 
                // Limpiamos un poco el texto generado automáticamente para no repetir los números
                // Buscamos donde dice "Observaciones:" en el texto guardado
                $partes = explode("Observaciones:", $r['acta']);
                echo isset($partes[1]) ? trim($partes[1]) : "Ninguna observación registrada."; 
            ?>
        </p>
    </div>

    <br><br><br>

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
    
    <div style="text-align: center; margin-top: 60px;">
        <div style="width: 40%; margin: 0 auto;">
            <hr style="border: 1px solid #000;">
            <strong>PROMOTORA / FACILITADOR</strong>
        </div>
    </div>

</div>

<style>
    .documento-impresion {
        background: white;
        padding: 40px;
        margin: 0 auto;
        max-width: 850px; /* Ancho carta aprox */
        box-shadow: 0 0 15px rgba(0,0,0,0.1);
        border-radius: 4px;
        font-family: 'Times New Roman', serif; /* Tipografía formal */
    }

    .doc-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }

    .doc-info-grid {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 20px;
        font-size: 1.1rem;
        text-transform: uppercase;
    }

    .doc-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }
    .doc-table th, .doc-table td {
        border: 1px solid #ddd;
        padding: 8px 12px;
    }
    .doc-table th {
        background-color: #f9f9f9;
        font-weight: bold;
        text-align: left;
    }
    .text-right { text-align: right; }

    /* REGLAS DE IMPRESIÓN */
    @media print {
        body { background: white; font-size: 12pt; }
        .sidebar, .print-hide { display: none !important; } /* Ocultar menú y botones */
        .main-content { margin: 0; width: 100%; padding: 0; }
        .documento-impresion {
            box-shadow: none;
            padding: 0;
            max-width: 100%;
        }
        /* Forzar colores de fondo en impresión (para las tablas grises) */
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    }
</style>

<?php require_once '../../includes/footer.php'; ?>