<?php
// modules/reuniones/multas.php
require_once '../../includes/header.php';
require_once '../../config/db.php';

if (!isset($_GET['id'])) { header("Location: index.php"); exit; }
$reunion_id = $_GET['id'];

// 1. OBTENER INFO DE REUNIÓN
$stmt_r = $pdo->prepare("SELECT r.*, c.nombre as ciclo, g.nombre as grupo, c.id as ciclo_id 
                         FROM Reunion r JOIN Ciclo c ON r.ciclo_id = c.id JOIN Grupo g ON c.grupo_id = g.id 
                         WHERE r.id = ?");
$stmt_r->execute([$reunion_id]);
$reunion = $stmt_r->fetch();

$mensaje = '';

// 2. PROCESAR ACCIONES (POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    try {
        $pdo->beginTransaction();

        // ACCIÓN A: COBRAR UNA MULTA (PAGAR)
        if (isset($_POST['accion']) && $_POST['accion'] == 'pagar') {
            $deuda_id = $_POST['deuda_id'];
            
            // 1. Obtener datos de la deuda para saber cuánto cobrar
            $stmt_check = $pdo->prepare("SELECT * FROM Deuda_Multa WHERE id = ?");
            $stmt_check->execute([$deuda_id]);
            $deuda = $stmt_check->fetch();

            if ($deuda && $deuda['estado'] == 'PENDIENTE') {
                // 2. Marcar como PAGADA
                $pdo->prepare("UPDATE Deuda_Multa SET estado = 'PAGADA', reunion_pago_id = ? WHERE id = ?")
                    ->execute([$reunion_id, $deuda_id]);

                // 3. Ingresar dinero a Caja (Transacción)
                $sql_trans = "INSERT INTO Transaccion_Caja (reunion_id, miembro_ciclo_id, deuda_multa_id, tipo_movimiento, monto, observacion) 
                              VALUES (?, ?, ?, 'PAGO_MULTA', ?, 'Pago de Multa')";
                $pdo->prepare($sql_trans)->execute([$reunion_id, $deuda['miembro_ciclo_id'], $deuda_id, $deuda['monto']]);
                
                $mensaje = "¡Pago registrado y dinero ingresado a caja!";
            }
        }

        // ACCIÓN B: CREAR NUEVA MULTA MANUAL (Ej: Llegada Tarde hoy)
        if (isset($_POST['accion']) && $_POST['accion'] == 'nueva_multa') {
            $miembro_id = $_POST['miembro_id'];
            $tipo_multa_id = $_POST['tipo_multa_id'];
            $monto = $_POST['monto'];

            $sql_new = "INSERT INTO Deuda_Multa (miembro_ciclo_id, reunion_generacion_id, catalogo_multa_id, monto, estado) 
                        VALUES (?, ?, ?, ?, 'PENDIENTE')";
            $pdo->prepare($sql_new)->execute([$miembro_id, $reunion_id, $tipo_multa_id, $monto]);
            
            $mensaje = "Multa asignada correctamente.";
        }

        $pdo->commit();

    } catch (Exception $e) {
        $pdo->rollBack();
        $mensaje = "Error: " . $e->getMessage();
    }
}

// 3. CONSULTAR DEUDAS PENDIENTES (COBRANZA)
// Mostramos todas las deudas pendientes de los miembros de este ciclo
$sql_deudas = "SELECT dm.*, u.nombre_completo, cm.nombre as motivo
               FROM Deuda_Multa dm
               JOIN Miembro_Ciclo mc ON dm.miembro_ciclo_id = mc.id
               JOIN Usuario u ON mc.usuario_id = u.id
               JOIN Catalogo_Multas cm ON dm.catalogo_multa_id = cm.id
               WHERE mc.ciclo_id = ? AND dm.estado = 'PENDIENTE'
               ORDER BY u.nombre_completo ASC";
$stmt_d = $pdo->prepare($sql_deudas);
$stmt_d->execute([$reunion['ciclo_id']]);
$deudas = $stmt_d->fetchAll();

// 4. DATOS PARA EL FORMULARIO DE NUEVA MULTA
// Miembros
$stmt_m = $pdo->prepare("SELECT mc.id, u.nombre_completo FROM Miembro_Ciclo mc JOIN Usuario u ON mc.usuario_id = u.id WHERE mc.ciclo_id = ? ORDER BY u.nombre_completo");
$stmt_m->execute([$reunion['ciclo_id']]);
$miembros_lista = $stmt_m->fetchAll();

// Tipos de Multa
$tipos_multa = $pdo->query("SELECT * FROM Catalogo_Multas")->fetchAll();
?>

<div class="flex-between" style="margin-bottom: 20px;">
    <div>
        <a href="panel.php?id=<?php echo $reunion_id; ?>" class="btn btn-secondary">
            <i class='bx bx-arrow-back'></i> Volver al Panel
        </a>
        <h2 style="margin-top: 10px;">Control de Multas</h2>
    </div>
</div>

<?php if($mensaje): ?>
    <div class="badge badge-success" style="display:block; padding:15px; margin-bottom:20px; text-align:center; background:#E8F5E9; color:#2E7D32; border:1px solid #C8E6C9;">
        <i class='bx bx-check-circle'></i> <?php echo $mensaje; ?>
    </div>
<?php endif; ?>

<div class="grid-2">
    
    <div class="card">
        <h3 style="color: var(--color-danger);"><i class='bx bx-list-ul'></i> Pendientes de Cobro</h3>
        <p style="color: var(--text-muted); font-size:0.9rem; margin-bottom:15px;">
            Lista de socios que deben multas (por inasistencia o sanciones previas).
        </p>

        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Socia</th>
                        <th>Motivo</th>
                        <th>Monto</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($deudas) > 0): ?>
                        <?php foreach($deudas as $d): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($d['nombre_completo']); ?></strong></td>
                                <td><small><?php echo htmlspecialchars($d['motivo']); ?></small></td>
                                <td style="color: var(--color-danger); font-weight:bold;">
                                    $<?php echo number_format($d['monto'], 2); ?>
                                </td>
                                <td>
                                    <form method="POST" style="margin:0;">
                                        <input type="hidden" name="accion" value="pagar">
                                        <input type="hidden" name="deuda_id" value="<?php echo $d['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-success" title="Cobrar Ahora">
                                            <i class='bx bx-money'></i> COBRAR
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center" style="padding: 30px; color: #999;">
                                <i class='bx bx-smile' style="font-size: 2rem;"></i>
                                <p>¡Excelente! Nadie debe multas.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card" style="border-left-color: var(--color-warning); height: fit-content;">
        <h3 style="color: var(--color-warning);"><i class='bx bx-error-circle'></i> Aplicar Nueva Sanción</h3>
        <p style="color: var(--text-muted); font-size:0.9rem; margin-bottom:15px;">
            Registre llegadas tardías o faltas al reglamento hoy.
        </p>

        <form method="POST">
            <input type="hidden" name="accion" value="nueva_multa">
            
            <div class="form-group">
                <label>Socia a Sancionar:</label>
                <select name="miembro_id" required>
                    <option value="">-- Seleccione --</option>
                    <?php foreach($miembros_lista as $m): ?>
                        <option value="<?php echo $m['id']; ?>">
                            <?php echo htmlspecialchars($m['nombre_completo']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Motivo de la Multa:</label>
                <select name="tipo_multa_id" id="tipo_multa" onchange="actualizarPrecio()" required>
                    <?php foreach($tipos_multa as $tm): ?>
                        <option value="<?php echo $tm['id']; ?>" data-precio="<?php echo $tm['monto_defecto']; ?>">
                            <?php echo htmlspecialchars($tm['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Monto a Pagar ($):</label>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="font-size: 1.2rem; font-weight: bold;">$</span>
                    <input type="number" name="monto" id="monto_input" step="0.01" required value="1.00">
                </div>
            </div>

            <button type="submit" class="btn btn-secondary btn-block">
                ASIGNAR MULTA
            </button>
        </form>
    </div>

</div>

<script>
// Pequeño script para que al cambiar el motivo, se actualice el precio sugerido
function actualizarPrecio() {
    var select = document.getElementById('tipo_multa');
    var precio = select.options[select.selectedIndex].getAttribute('data-precio');
    document.getElementById('monto_input').value = precio;
}
// Ejecutar al inicio
actualizarPrecio();
</script>

<?php require_once '../../includes/footer.php'; ?>