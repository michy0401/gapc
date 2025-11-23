<?php
// modules/reuniones/crear.php
require_once '../../includes/header.php';
require_once '../../config/db.php';

// 1. VALIDAR ENTRADA
if (!isset($_GET['ciclo_id']) || !isset($_GET['num'])) {
    header("Location: index.php");
    exit;
}
$ciclo_id = $_GET['ciclo_id'];
$numero_reunion = $_GET['num'];

// 2. BUSCAR SALDO ANTERIOR (Inteligencia Financiera)
// Buscamos la última reunión cerrada para ver con cuánto dinero se quedaron.
$saldo_sugerido = 0.00;

if ($numero_reunion > 1) {
    $stmt_prev = $pdo->prepare("SELECT saldo_caja_actual FROM Reunion WHERE ciclo_id = ? AND estado = 'CERRADA' ORDER BY numero_reunion DESC LIMIT 1");
    $stmt_prev->execute([$ciclo_id]);
    $prev = $stmt_prev->fetch();
    if ($prev) {
        $saldo_sugerido = $prev['saldo_caja_actual'];
    }
}

$mensaje = '';

// 3. PROCESAR FORMULARIO (ABRIR LA REUNIÓN)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fecha = $_POST['fecha'];
    $saldo_inicial = $_POST['saldo_inicial'];

    try {
        // Insertamos la nueva reunión con estado 'ABIERTA'
        // Nota: saldo_caja_actual empieza igual al inicial hasta que entre dinero
        $sql = "INSERT INTO Reunion (ciclo_id, numero_reunion, fecha, estado, saldo_caja_inicial, saldo_caja_actual) 
                VALUES (?, ?, ?, 'ABIERTA', ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$ciclo_id, $numero_reunion, $fecha, $saldo_inicial, $saldo_inicial]);
        
        $id_reunion = $pdo->lastInsertId();

        // REDIRECCIÓN DIRECTA AL PANEL DE TRABAJO
        // Aquí es donde ocurrirá la magia de la asistencia y pagos
        echo "<script>window.location.href='panel.php?id=$id_reunion';</script>";
        exit;

    } catch (PDOException $e) {
        $mensaje = "Error al abrir reunión: " . $e->getMessage();
    }
}
?>

<div class="container" style="max-width: 600px; margin: 0 auto;">
    
    <div class="flex-between" style="margin-bottom: 20px;">
        <a href="lista.php?ciclo_id=<?php echo $ciclo_id; ?>" class="btn btn-secondary">
            <i class='bx bx-arrow-back'></i> Cancelar
        </a>
        <h2>Apertura de Caja</h2>
    </div>

    <?php if($mensaje): ?>
        <div class="badge badge-danger" style="display:block; padding:15px; margin-bottom:20px; text-align:center;">
            <?php echo $mensaje; ?>
        </div>
    <?php endif; ?>

    <div class="card" style="border-top: 5px solid var(--color-success);">
        
        <div class="text-center" style="margin-bottom: 25px;">
            <div style="background: #E8F5E9; width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px auto;">
                <i class='bx bx-box' style="font-size: 3rem; color: var(--color-success);"></i>
            </div>
            <h3 style="margin: 0;">Reunión #<?php echo $numero_reunion; ?></h3>
            <p style="color: var(--text-muted);">Prepare la caja física y confirme los montos.</p>
        </div>

        <form method="POST" action="">
            
            <div class="form-group">
                <label>Fecha de la Reunión:</label>
                <input type="date" name="fecha" required value="<?php echo date('Y-m-d'); ?>" style="font-size: 1.2rem; font-weight: bold;">
            </div>

            <div class="form-group">
                <label>Saldo Inicial en Caja ($):</label>
                <p style="font-size: 0.85rem; color: #666; margin-bottom: 5px;">
                    Dinero físico encontrado en la caja al abrirla hoy.
                </p>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="font-size: 1.5rem; font-weight: bold; color: var(--color-success);">$</span>
                    <input type="number" name="saldo_inicial" step="0.01" min="0" 
                           value="<?php echo $saldo_sugerido; ?>" 
                           required 
                           style="font-size: 1.5rem; font-weight: bold; color: var(--color-success);">
                </div>
                <?php if ($saldo_sugerido > 0): ?>
                    <small style="color: var(--color-warning); display: block; margin-top: 5px;">
                        <i class='bx bx-info-circle'></i> Sugerido según cierre anterior.
                    </small>
                <?php endif; ?>
            </div>

            <br>

            <button type="submit" class="btn btn-success btn-block" style="padding: 15px; font-size: 1.2rem;">
                ABRIR REUNIÓN Y CAJA <i class='bx bx-right-arrow-alt'></i>
            </button>

        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>