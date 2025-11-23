<?php
// modules/reuniones/cierre.php
require_once '../../includes/header.php';
require_once '../../config/db.php';

if (!isset($_GET['id'])) { header("Location: index.php"); exit; }
$reunion_id = $_GET['id'];
$error = ''; 

// 1. OBTENER DATOS COMPLETOS
$stmt = $pdo->prepare("
    SELECT r.*, c.nombre as ciclo, g.nombre as grupo, c.id as ciclo_id
    FROM Reunion r
    JOIN Ciclo c ON r.ciclo_id = c.id
    JOIN Grupo g ON c.grupo_id = g.id
    WHERE r.id = ?
");
$stmt->execute([$reunion_id]);
$reunion = $stmt->fetch();

// 2. CALCULAR TOTALES FINALES
$sql_balance = "SELECT 
    SUM(CASE WHEN tipo_movimiento IN ('AHORRO','PAGO_PRESTAMO_CAPITAL','PAGO_PRESTAMO_INTERES','PAGO_MULTA','INGRESO_EXTRA') THEN monto ELSE 0 END) as total_entradas,
    SUM(CASE WHEN tipo_movimiento IN ('RETIRO_AHORRO','DESEMBOLSO_PRESTAMO','GASTO_OPERATIVO') THEN monto ELSE 0 END) as total_salidas
    FROM Transaccion_Caja WHERE reunion_id = ?";
$stmt_b = $pdo->prepare($sql_balance);
$stmt_b->execute([$reunion_id]);
$balance = $stmt_b->fetch();

$entradas = $balance['total_entradas'] ?: 0;
$salidas = $balance['total_salidas'] ?: 0;
$saldo_sistema = $reunion['saldo_caja_inicial'] + $entradas - $salidas;

// 3. PROCESAR CIERRE
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $saldo_fisico = floatval($_POST['saldo_fisico']);
    $observaciones = $_POST['observaciones'];
    
    // Generar Acta Automática
    $texto_acta = "ACTA DE REUNIÓN #{$reunion['numero_reunion']}\n";
    $texto_acta .= "Grupo: {$reunion['grupo']} - Fecha: " . date('d/m/Y', strtotime($reunion['fecha'])) . "\n";
    $texto_acta .= "----------------------------------------\n";
    $texto_acta .= "Saldo Inicial: $" . number_format($reunion['saldo_caja_inicial'], 2) . "\n";
    $texto_acta .= "Total Entradas: $" . number_format($entradas, 2) . "\n";
    $texto_acta .= "Total Salidas:  $" . number_format($salidas, 2) . "\n";
    $texto_acta .= "----------------------------------------\n";
    $texto_acta .= "Saldo Final Sistema: $" . number_format($saldo_sistema, 2) . "\n";
    $texto_acta .= "Saldo Final Físico:  $" . number_format($saldo_fisico, 2) . "\n";
    $texto_acta .= "Diferencia: $" . number_format($saldo_fisico - $saldo_sistema, 2) . "\n\n";
    $texto_acta .= "Observaciones:\n" . $observaciones;

    try {
        $sql_close = "UPDATE Reunion SET 
                      estado = 'CERRADA', 
                      total_entradas = ?, 
                      total_salidas = ?, 
                      saldo_caja_actual = ?, 
                      saldo_fisico_actual = ?, 
                      acta = ? 
                      WHERE id = ?";
        
        $pdo->prepare($sql_close)->execute([
            $entradas, $salidas, $saldo_sistema, $saldo_fisico, $texto_acta, $reunion_id
        ]);

        echo "<script>window.location.href='ver_acta.php?id=$reunion_id';</script>";
        exit;

    } catch (Exception $e) {
        $error = "Error al cerrar: " . $e->getMessage();
    }
}
?>

<div class="flex-between" style="margin-bottom: 20px;">
    <div>
        <a href="panel.php?id=<?php echo $reunion_id; ?>" class="btn btn-secondary">
            <i class='bx bx-arrow-back'></i> Volver al Panel
        </a>
        <h2 style="margin-top: 10px;">Cierre de Caja</h2>
    </div>
</div>

<?php if($error): ?>
    <div class="badge badge-danger" style="display:block; padding:15px; margin-bottom:20px; text-align:center;">
        <i class='bx bx-error'></i> <?php echo $error; ?>
    </div>
<?php endif; ?>

<div class="grid-2">
    
    <div class="card">
        <h3><i class='bx bx-calculator'></i> Cálculo del Sistema</h3>
        <table class="table">
            <tr>
                <td>Saldo Inicial</td>
                <td class="text-right">$<?php echo number_format($reunion['saldo_caja_inicial'], 2); ?></td>
            </tr>
            <tr>
                <td style="color: var(--color-success);"> (+) Entradas Totales</td>
                <td class="text-right">$<?php echo number_format($entradas, 2); ?></td>
            </tr>
            <tr>
                <td style="color: var(--color-danger);"> (-) Salidas Totales</td>
                <td class="text-right">$<?php echo number_format($salidas, 2); ?></td>
            </tr>
            <tr style="background: #E3F2FD; font-size: 1.2rem; font-weight: bold;">
                <td>SALDO ESPERADO</td>
                <td class="text-right" style="color: var(--color-brand);">
                    $<?php echo number_format($saldo_sistema, 2); ?>
                </td>
            </tr>
        </table>
    </div>

    <div class="card" style="border-left-color: var(--color-warning);">
        <h3><i class='bx bx-money'></i> Arqueo Físico</h3>
        <p style="color: var(--text-muted);">
            Cuente el dinero en la caja y confirme el monto final.
        </p>

        <form method="POST" id="form-cierre">
            
            <div class="form-group">
                <label>Saldo Físico Real ($):</label>
                <input type="number" name="saldo_fisico" step="0.01" required 
                       style="font-size: 2rem; color: var(--color-success); font-weight: bold; text-align: center;"
                       placeholder="0.00"
                       value="<?php echo $saldo_sistema; ?>"> 
            </div>

            <div class="form-group">
                <label>Observaciones / Notas del Acta:</label>
                <textarea name="observaciones" rows="4" placeholder="Ej: Todo cuadró perfectamente..."></textarea>
            </div>

            <button type="button" onclick="toggleModal(true)" class="btn btn-primary btn-block" style="padding: 15px; font-size: 1.2rem; background-color: #37474F;">
                <i class='bx bx-lock-alt'></i> CERRAR REUNIÓN Y GENERAR ACTA
            </button>
        </form>
    </div>

</div>

<div id="modal-confirmacion" class="modal-overlay-custom">
    <div class="modal-box">
        <div class="text-center">
            <i class='bx bx-error-circle' style="font-size: 4rem; color: var(--color-warning);"></i>
            <h3>¿Está seguro de cerrar la reunión?</h3>
            <p style="color: #666; margin-bottom: 20px;">
                Esta acción bloqueará los movimientos y generará el acta oficial. No se podrán hacer cambios después.
            </p>
            
            <div class="flex-center" style="gap: 10px;">
                <button onclick="toggleModal(false)" class="btn btn-secondary" style="width: 120px;">Cancelar</button>
                <button onclick="document.getElementById('form-cierre').submit()" class="btn btn-primary" style="width: 120px;">
                    SÍ, CERRAR
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    .text-right { text-align: right; }
    
    /* Estilos del Modal */
    .modal-overlay-custom {
        display: none; /* Oculto por defecto */
        position: fixed;
        top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 2000;
        align-items: center;
        justify-content: center;
    }
    .modal-box {
        background: white;
        padding: 30px;
        border-radius: 12px;
        max-width: 400px;
        width: 90%;
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        animation: slideDown 0.3s ease;
    }
    @keyframes slideDown {
        from { transform: translateY(-20px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
</style>

<script>
    function toggleModal(show) {
        const modal = document.getElementById('modal-confirmacion');
        modal.style.display = show ? 'flex' : 'none';
    }
</script>

<?php require_once '../../includes/footer.php'; ?>