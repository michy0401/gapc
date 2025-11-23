<?php
// modules/reuniones/index.php
require_once '../../includes/header.php';
require_once '../../config/db.php';

// 1. OBTENER CICLOS ACTIVOS (Según permisos)
$rol = $_SESSION['rol_usuario'];
$uid = $_SESSION['user_id'];

$sql = "SELECT c.*, g.nombre as nombre_grupo, 
        (SELECT COUNT(*) FROM Reunion r WHERE r.ciclo_id = c.id) as total_reuniones,
        (SELECT MAX(fecha) FROM Reunion r WHERE r.ciclo_id = c.id) as ultima_reunion
        FROM Ciclo c
        JOIN Grupo g ON c.grupo_id = g.id
        WHERE c.estado = 'ACTIVO'";

$params = [];
if ($rol != 1) { // Si no es Admin, filtrar por promotora
    $sql .= " AND g.promotora_id = ?";
    $params[] = $uid;
}

$sql .= " ORDER BY g.nombre ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$ciclos = $stmt->fetchAll();
?>

<div class="flex-between" style="margin-bottom: 20px;">
    <div>
        <h2>Gestión de Reuniones</h2>
        <p style="color: var(--text-muted);">Seleccione un grupo para iniciar o gestionar sus reuniones.</p>
    </div>
</div>

<?php if (count($ciclos) > 0): ?>
    <div class="grid-2">
        <?php foreach($ciclos as $c): ?>
            <div class="card" style="border-left: 5px solid var(--color-success); transition: transform 0.2s;">
                <div class="flex-between">
                    <h3 style="margin:0; color: var(--color-brand);"><?php echo htmlspecialchars($c['nombre_grupo']); ?></h3>
                    <span class="badge badge-success">ACTIVO</span>
                </div>
                <p style="color: var(--text-muted); margin-bottom: 15px;">
                    <?php echo htmlspecialchars($c['nombre']); ?>
                </p>

                <div style="background: #F5F7FA; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <div style="display: flex; gap: 15px; align-items: center;">
                        <div class="text-center">
                            <i class='bx bx-calendar-check' style="font-size: 1.5rem; color: var(--color-brand);"></i>
                        </div>
                        <div>
                            <small style="display:block; color: #666;">Reuniones Realizadas</small>
                            <strong>#<?php echo $c['total_reuniones']; ?></strong>
                        </div>
                    </div>
                    <hr style="margin: 10px 0; border: 0; border-top: 1px solid #eee;">
                    <div style="display: flex; gap: 15px; align-items: center;">
                        <div class="text-center">
                            <i class='bx bx-time-five' style="font-size: 1.5rem; color: var(--color-warning);"></i>
                        </div>
                        <div>
                            <small style="display:block; color: #666;">Última Actividad</small>
                            <strong>
                                <?php echo $c['ultima_reunion'] ? date('d/m/Y', strtotime($c['ultima_reunion'])) : 'Sin registros'; ?>
                            </strong>
                        </div>
                    </div>
                </div>

                <a href="lista.php?ciclo_id=<?php echo $c['id']; ?>" class="btn btn-primary btn-block">
                    <i class='bx bx-door-open'></i> ENTRAR AL GRUPO
                </a>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="card text-center" style="padding: 50px;">
        <i class='bx bx-sleepy' style="font-size: 4rem; color: #ccc;"></i>
        <h3>No hay ciclos activos</h3>
        <p>Para gestionar reuniones, primero debe iniciar un ciclo en la sección de Grupos.</p>
        <br>
        <a href="../grupos/index.php" class="btn btn-secondary">Ir a Grupos</a>
    </div>
<?php endif; ?>

<?php require_once '../../includes/footer.php'; ?>