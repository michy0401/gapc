<?php

require_once '../../includes/header.php';
require_once '../../config/db.php'; 

$sql = "SELECT 
            g.id, 
            g.nombre, 
            g.fecha_creacion, 
            d.nombre AS nombre_distrito,
            u.nombre_completo AS nombre_promotora
        FROM Grupo g
        JOIN Distrito d ON g.distrito_id = d.id
        JOIN Usuario u ON g.promotora_id = u.id
        ORDER BY g.id DESC";

$stmt = $pdo->query($sql);
$grupos = $stmt->fetchAll();
?>

<div class="flex-between" style="margin-bottom: 20px;">
    <h2>Gestión de Grupos</h2>
    <a href="crear.php" class="btn btn-primary">
        <i class='bx bx-plus'></i> Nuevo Grupo
    </a>
</div>

<div class="card">
    
    <div class="form-group" style="max-width: 300px;">
        <input type="text" placeholder="Buscar grupo..." style="padding: 10px; font-size: 0.9rem;">
    </div>

    <div class="table-container">
        <?php if (count($grupos) > 0): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre del Grupo</th>
                        <th>Distrito</th>
                        <th>Promotora a Cargo</th>
                        <th>Fecha Creación</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($grupos as $grupo): ?>
                        <tr>
                            <td>#<?php echo $grupo['id']; ?></td>
                            <td style="font-weight: bold; color: var(--color-brand);">
                                <?php echo htmlspecialchars($grupo['nombre']); ?>
                            </td>
                            <td><?php echo htmlspecialchars($grupo['nombre_distrito']); ?></td>
                            <td>
                                <i class='bx bx-user' style="color: var(--text-muted);"></i>
                                <?php echo htmlspecialchars($grupo['nombre_promotora']); ?>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($grupo['fecha_creacion'])); ?></td>
                            <td>
                                <a href="ver.php?id=<?php echo $grupo['id']; ?>" class="btn btn-secondary" style="padding: 5px 10px; font-size: 0.9rem;">
                                    <i class='bx bx-show'></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="text-center" style="padding: 40px; color: var(--text-muted);">
                <i class='bx bx-folder-open' style="font-size: 3rem;"></i>
                <p>No hay grupos registrados aún.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php

require_once '../../includes/footer.php';
?>