<?php
// modules/reportes/index.php
require_once '../../includes/header.php';
require_once '../../config/db.php';

// Filtrar según rol
$rol = $_SESSION['rol_usuario'];
$uid = $_SESSION['user_id'];

$sql = "SELECT c.id, c.nombre, g.nombre as grupo, c.fecha_inicio 
        FROM Ciclo c 
        JOIN Grupo g ON c.grupo_id = g.id 
        WHERE c.estado = 'ACTIVO'";

if ($rol != 1) { 
    $sql .= " AND g.promotora_id = $uid"; 
}
$sql .= " ORDER BY g.nombre ASC";

$ciclos = $pdo->query($sql)->fetchAll();
?>

<div class="flex-between" style="margin-bottom: 20px;">
    <div>
        <h2>Reportes y Transparencia</h2>
        <p style="color: var(--text-muted);">Seleccione un ciclo operativo para ver sus estados financieros.</p>
    </div>
</div>

<?php if (count($ciclos) > 0): ?>
    <div class="grid-2">
        <?php foreach($ciclos as $c): ?>
            <div class="card" style="border-left: 5px solid var(--color-brand); transition: transform 0.2s;">
                <div class="flex-between">
                    <h3 style="margin:0; color: var(--color-brand);"><?php echo htmlspecialchars($c['grupo']); ?></h3>
                </div>
                <p style="color: var(--text-muted); margin-bottom: 15px;">
                    <?php echo htmlspecialchars($c['nombre']); ?>
                    <br>
                    <small>Inicio: <?php echo date('d/m/Y', strtotime($c['fecha_inicio'])); ?></small>
                </p>

                <a href="balance.php?ciclo_id=<?php echo $c['id']; ?>" class="btn btn-primary btn-block">
                    <i class='bx bx-pie-chart-alt-2'></i> VER BALANCE GENERAL
                </a>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="card text-center" style="padding: 50px;">
        <i class='bx bx-bar-chart-alt-2' style="font-size: 4rem; color: #ccc;"></i>
        <h3>No hay información disponible</h3>
        <p>Necesita tener ciclos activos para generar reportes.</p>
    </div>
<?php endif; ?>

<?php require_once '../../includes/footer.php'; ?>