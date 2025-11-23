<?php
// modules/reuniones/asistencia.php
require_once '../../includes/header.php';
require_once '../../config/db.php';

if (!isset($_GET['id'])) { header("Location: index.php"); exit; }
$reunion_id = $_GET['id'];

// 1. OBTENER DATOS DE LA REUNIÓN Y CICLO
$stmt_r = $pdo->prepare("SELECT r.*, c.id as ciclo_id, g.nombre as grupo, c.nombre as ciclo 
                         FROM Reunion r 
                         JOIN Ciclo c ON r.ciclo_id = c.id 
                         JOIN Grupo g ON c.grupo_id = g.id 
                         WHERE r.id = ?");
$stmt_r->execute([$reunion_id]);
$reunion = $stmt_r->fetch();

// 2. BUSCAR PRECIO DE LA MULTA POR INASISTENCIA (ID 1 en Catálogo)
// Necesitamos saber cuánto cobrar si faltan.
$stmt_precio = $pdo->prepare("SELECT monto_aplicar FROM Configuracion_Multas_Ciclo 
                              WHERE ciclo_id = ? AND catalogo_multa_id = 1"); // 1 = Inasistencia (según seed)
$stmt_precio->execute([$reunion['ciclo_id']]);
$config_multa = $stmt_precio->fetch();
$monto_multa = $config_multa ? $config_multa['monto_aplicar'] : 0.00;

// 3. PROCESAR GUARDADO
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $pdo->beginTransaction();
        
        // Recorremos el array de asistencia enviado por el formulario
        // $_POST['asistencia'] es un array donde la llave es el ID del miembro y el valor el estado
        foreach ($_POST['asistencia'] as $miembro_id => $estado) {
            
            // A. Guardar/Actualizar Asistencia
            // Primero borramos si ya existía para evitar duplicados (Estrategia simple)
            $pdo->prepare("DELETE FROM Asistencia WHERE reunion_id = ? AND miembro_ciclo_id = ?")->execute([$reunion_id, $miembro_id]);
            
            $stmt_ins = $pdo->prepare("INSERT INTO Asistencia (reunion_id, miembro_ciclo_id, estado) VALUES (?, ?, ?)");
            $stmt_ins->execute([$reunion_id, $miembro_id, $estado]);

            // B. Lógica de Multa Automática
            // Primero borramos cualquier multa de inasistencia generada en ESTA reunión para este miembro (por si estamos corrigiendo)
            $pdo->prepare("DELETE FROM Deuda_Multa WHERE reunion_generacion_id = ? AND miembro_ciclo_id = ? AND catalogo_multa_id = 1")->execute([$reunion_id, $miembro_id]);

            // Si está AUSENTE, creamos la deuda nueva
            if ($estado == 'AUSENTE' && $monto_multa > 0) {
                $stmt_multa = $pdo->prepare("INSERT INTO Deuda_Multa (miembro_ciclo_id, reunion_generacion_id, catalogo_multa_id, monto, estado) VALUES (?, ?, 1, ?, 'PENDIENTE')");
                $stmt_multa->execute([$miembro_id, $reunion_id, $monto_multa]);
            }
        }

        $pdo->commit();
        echo "<script>window.location.href='panel.php?id=$reunion_id';</script>";
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error al guardar: " . $e->getMessage();
    }
}

// 4. OBTENER MIEMBROS Y SU ASISTENCIA PREVIA (SI LA HAY)
$sql_lista = "SELECT mc.id, u.nombre_completo, a.estado as estado_actual
              FROM Miembro_Ciclo mc
              JOIN Usuario u ON mc.usuario_id = u.id
              LEFT JOIN Asistencia a ON a.miembro_ciclo_id = mc.id AND a.reunion_id = ?
              WHERE mc.ciclo_id = ?
              ORDER BY u.nombre_completo ASC";
$stmt_l = $pdo->prepare($sql_lista);
$stmt_l->execute([$reunion_id, $reunion['ciclo_id']]);
$miembros = $stmt_l->fetchAll();
?>

<div class="flex-between" style="margin-bottom: 20px;">
    <div>
        <a href="panel.php?id=<?php echo $reunion_id; ?>" class="btn btn-secondary">
            <i class='bx bx-arrow-back'></i> Volver al Panel
        </a>
        <h2 style="margin-top: 10px;">Registro de Asistencia</h2>
        <p style="color: var(--text-muted);">
            Reunión #<?php echo $reunion['numero_reunion']; ?> - <?php echo htmlspecialchars($reunion['grupo']); ?>
        </p>
    </div>
    
    <?php if($monto_multa > 0): ?>
        <div class="badge badge-danger" style="font-size: 1rem; padding: 10px 20px;">
            <i class='bx bx-info-circle'></i> Multa por falta: $<?php echo number_format($monto_multa, 2); ?>
        </div>
    <?php else: ?>
        <div class="badge" style="background: #FFF3E0; color: #EF6C00; font-size: 1rem; padding: 10px 20px;">
            ⚠️ Multa de inasistencia no configurada ($0.00)
        </div>
    <?php endif; ?>
</div>

<div class="card">
    <form method="POST">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Miembro</th>
                        <th class="text-center" style="color: var(--color-success);">PRESENTE</th>
                        <th class="text-center" style="color: var(--color-danger);">AUSENTE</th>
                        <th class="text-center" style="color: var(--color-warning);">PERMISO</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($miembros as $m): ?>
                        <?php 
                            // Determinar estado seleccionado (Por defecto: PRESENTE)
                            $estado = $m['estado_actual'] ? $m['estado_actual'] : 'PRESENTE';
                        ?>
                        <tr>
                            <td style="font-weight: bold; font-size: 1.1rem;">
                                <?php echo htmlspecialchars($m['nombre_completo']); ?>
                            </td>
                            
                            <td class="text-center" style="background-color: #E8F5E9;">
                                <input type="radio" 
                                       name="asistencia[<?php echo $m['id']; ?>]" 
                                       value="PRESENTE" 
                                       style="width: 25px; height: 25px; cursor: pointer;"
                                       <?php echo ($estado == 'PRESENTE') ? 'checked' : ''; ?>>
                            </td>
                            
                            <td class="text-center" style="background-color: #FFEBEE;">
                                <input type="radio" 
                                       name="asistencia[<?php echo $m['id']; ?>]" 
                                       value="AUSENTE" 
                                       style="width: 25px; height: 25px; cursor: pointer;"
                                       <?php echo ($estado == 'AUSENTE') ? 'checked' : ''; ?>>
                            </td>
                            
                            <td class="text-center" style="background-color: #FFF3E0;">
                                <input type="radio" 
                                       name="asistencia[<?php echo $m['id']; ?>]" 
                                       value="PERMISO" 
                                       style="width: 25px; height: 25px; cursor: pointer;"
                                       <?php echo ($estado == 'PERMISO') ? 'checked' : ''; ?>>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <br>
        <div style="padding: 20px; background: #F5F7FA; border-radius: 8px; text-align: center;">
            <p style="margin-bottom: 15px;">
                <i class='bx bx-error-circle'></i> 
                <strong>Nota:</strong> Marcar "AUSENTE" generará automáticamente una deuda de $<?php echo number_format($monto_multa, 2); ?>.
            </p>
            <button type="submit" class="btn btn-primary btn-block" style="padding: 15px; font-size: 1.2rem;">
                <i class='bx bx-save'></i> GUARDAR ASISTENCIA
            </button>
        </div>
    </form>
</div>

<?php require_once '../../includes/footer.php'; ?>