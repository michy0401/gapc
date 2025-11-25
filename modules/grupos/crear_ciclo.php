<?php
// modules/grupos/crear_ciclo.php
require_once '../../includes/header.php';
require_once '../../config/db.php';

// 1. RECIBIR ID DEL GRUPO
if (!isset($_GET['grupo_id'])) {
    header("Location: index.php");
    exit;
}
$grupo_id = $_GET['grupo_id'];

// 2. RECIBIR EL ORIGEN (CORRECCIÓN DE NAVEGACIÓN)
$origen = isset($_GET['origen']) ? $_GET['origen'] : '';

// Obtener nombre del grupo
$stmt_g = $pdo->prepare("SELECT nombre FROM Grupo WHERE id = ?");
$stmt_g->execute([$grupo_id]);
$grupo = $stmt_g->fetch();

// Obtener catálogo de multas
$multas_catalogo = $pdo->query("SELECT * FROM Catalogo_Multas")->fetchAll();

$mensaje = '';

// 3. PROCESAR FORMULARIO
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $pdo->beginTransaction();

        // A. Datos básicos
        $nombre = $_POST['nombre'];
        $fecha_inicio = $_POST['fecha_inicio'];
        $duracion = $_POST['duracion'];
        $tasa_interes = $_POST['tasa_interes'];
        
        $fecha_fin = date('Y-m-d', strtotime($fecha_inicio . " + $duracion months"));

        // B. Insertar el Ciclo
        $sql_ciclo = "INSERT INTO Ciclo (grupo_id, nombre, fecha_inicio, fecha_fin_estimada, duracion, tasa_interes_mensual, estado) 
                      VALUES (?, ?, ?, ?, ?, ?, 'ACTIVO')";
        $stmt = $pdo->prepare($sql_ciclo);
        $stmt->execute([$grupo_id, $nombre, $fecha_inicio, $fecha_fin, $duracion, $tasa_interes]);
        
        $nuevo_ciclo_id = $pdo->lastInsertId();

        // C. Guardar Configuración de Multas
        if (isset($_POST['multas_activas'])) {
            $sql_conf = "INSERT INTO Configuracion_Multas_Ciclo (ciclo_id, catalogo_multa_id, monto_aplicar) VALUES (?, ?, ?)";
            $stmt_conf = $pdo->prepare($sql_conf);

            foreach ($_POST['multas_activas'] as $multa_id) {
                $monto = $_POST['monto_multa'][$multa_id];
                $stmt_conf->execute([$nuevo_ciclo_id, $multa_id, $monto]);
            }
        }

        $pdo->commit();

        // REDIRECCIÓN CORREGIDA: Devolvemos la etiqueta de origen
        echo "<script>window.location.href='ver.php?id=$grupo_id&origen=$origen';</script>";
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        $mensaje = "Error al crear ciclo: " . $e->getMessage();
    }
}
?>

<div class="container" style="max-width: 900px; margin: 0 auto;">
    
    <div style="margin-bottom: 20px;">
        <a href="ver.php?id=<?php echo $grupo_id; ?>&origen=<?php echo $origen; ?>" class="btn btn-secondary">
            <i class='bx bx-arrow-back'></i> Volver al Grupo
        </a>
        <h2 style="margin-top: 10px;">Configurar Nuevo Ciclo</h2>
        <p style="font-size: 1.1rem; color: var(--color-brand);">
            Grupo: <strong><?php echo htmlspecialchars($grupo['nombre']); ?></strong>
        </p>
    </div>

    <?php if($mensaje): ?>
        <div class="badge badge-danger" style="display:block; margin-bottom: 20px; padding: 15px; text-align: center;">
            <?php echo $mensaje; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        
        <div class="card">
            <div class="flex-between" style="margin-bottom: 15px;">
                <h3><i class='bx bx-calendar'></i> Duración del Ciclo</h3>
                <span class="badge" style="background: #E3F2FD; color: #1565C0;">Paso 1 de 2</span>
            </div>
            
            <div class="grid-2">
                <div class="form-group">
                    <label>Nombre del Ciclo:</label>
                    <input type="text" name="nombre" required placeholder="Ej: Ciclo 2025 - Primer Semestre" value="Ciclo <?php echo date('Y'); ?>">
                </div>

                <div class="form-group">
                    <label>Fecha de Inicio (Primera Reunión):</label>
                    <input type="date" name="fecha_inicio" required value="<?php echo date('Y-m-d'); ?>">
                </div>

                <div class="form-group">
                    <label>Duración (Meses):</label>
                    <select name="duracion" required>
                        <option value="6">6 Meses</option>
                        <option value="12" selected>12 Meses (1 Año)</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="card" style="border-left-color: var(--color-warning);">
            <div class="flex-between" style="margin-bottom: 15px;">
                <h3><i class='bx bx-money'></i> Reglas Financieras</h3>
                <span class="badge" style="background: #FFF3E0; color: #EF6C00;">Paso 2 de 2</span>
            </div>

            <div class="form-group" style="background: #FFFDE7; padding: 15px; border-radius: 8px; border: 1px solid #FFF9C4;">
                <label style="color: #F57F17;">Tasa de Interés Mensual (%):</label>
                <p style="font-size: 0.9rem; color: #666; margin-bottom: 5px;">
                    ¿Cuánto pagan por cada $100 prestados al mes? (Ej: 5% = $5.00)
                </p>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <input type="number" name="tasa_interes" step="0.01" min="0" value="5.00" style="width: 150px; font-weight: bold; font-size: 1.2rem;">
                    <span style="font-size: 1.2rem; font-weight: bold;">%</span>
                </div>
            </div>

            <br>

            <label>Configuración de Multas:</label>
            <p style="font-size: 0.9rem; color: #666; margin-bottom: 10px;">Seleccione las multas que aplican y el costo para este ciclo.</p>
            
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">Activar</th>
                            <th>Tipo de Multa</th>
                            <th>Costo ($)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($multas_catalogo as $m): ?>
                            <tr>
                                <td class="text-center">
                                    <input type="checkbox" 
                                           name="multas_activas[]" 
                                           value="<?php echo $m['id']; ?>" 
                                           checked 
                                           style="width: 20px; height: 20px;">
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($m['nombre']); ?></strong>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 5px;">
                                        <span>$</span>
                                        <input type="number" 
                                               name="monto_multa[<?php echo $m['id']; ?>]" 
                                               step="0.05" 
                                               min="0" 
                                               value="<?php echo number_format($m['monto_defecto'], 2); ?>" 
                                               style="width: 100px;">
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <br>
        
        <button type="submit" class="btn btn-primary btn-block" style="padding: 15px; font-size: 1.2rem;">
            <i class='bx bx-check-circle'></i> CONFIRMAR E INICIAR CICLO
        </button>

    </form>
</div>

<?php require_once '../../includes/footer.php'; ?>