<?php
// modules/grupos/ciclos_global.php
require_once '../../includes/header.php';
require_once '../../config/db.php';

// 1. CONFIGURACIÓN DE FILTROS
// Por defecto mostramos solo los ACTIVOS porque es lo que se trabaja día a día
$filtro_estado = isset($_GET['estado']) ? $_GET['estado'] : 'ACTIVO'; 

// 2. PREPARAR CONSULTA SEGÚN ROL
$rol_usuario = $_SESSION['rol_usuario'];
$id_usuario = $_SESSION['user_id'];

$sql = "SELECT c.*, g.nombre as nombre_grupo, u.nombre_completo as nombre_promotora, g.id as grupo_id
        FROM Ciclo c
        JOIN Grupo g ON c.grupo_id = g.id
        JOIN Usuario u ON g.promotora_id = u.id
        WHERE 1=1";

$params = [];

// Filtro por Estado (Si no es 'TODOS', aplicamos filtro)
if ($filtro_estado != 'TODOS') {
    $sql .= " AND c.estado = ?";
    $params[] = $filtro_estado;
}

// Filtro por Rol (Si es Promotora, solo sus grupos)
if ($rol_usuario != 1) { // 1 = Admin
    $sql .= " AND g.promotora_id = ?";
    $params[] = $id_usuario;
}

$sql .= " ORDER BY c.fecha_inicio DESC"; // Los más recientes primero

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$ciclos = $stmt->fetchAll();
?>

<div class="flex-between" style="margin-bottom: 20px;">
    <div>
        <h2>Gestión Global de Ciclos</h2>
        <p style="color: var(--text-muted);">
            Monitoreo de periodos operativos de todos los grupos.
        </p>
    </div>
</div>

<div style="margin-bottom: 20px; display: flex; gap: 10px;">
    <a href="?estado=ACTIVO" class="btn <?php echo $filtro_estado == 'ACTIVO' ? 'btn-success' : 'btn-secondary'; ?>">
        <i class='bx bx-play-circle'></i> Activos
    </a>
    <a href="?estado=LIQUIDADO" class="btn <?php echo $filtro_estado == 'LIQUIDADO' ? 'btn-primary' : 'btn-secondary'; ?>">
        <i class='bx bx-lock-alt'></i> Liquidados
    </a>
    <a href="?estado=TODOS" class="btn <?php echo $filtro_estado == 'TODOS' ? 'btn-brand' : 'btn-secondary'; ?>">
        <i class='bx bx-list-ul'></i> Ver Todos
    </a>
</div>

<div class="card">
    <div class="table-container">
        <?php if (count($ciclos) > 0): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Estado</th>
                        <th>Nombre del Ciclo</th>
                        <th>Grupo GAPC</th>
                        <?php if($rol_usuario == 1): ?> 
                            <th>Promotora</th> 
                        <?php endif; ?>
                        <th>Duración</th>
                        <th>Interés</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($ciclos as $c): ?>
                        <tr>
                            <td>
                                <?php if($c['estado'] == 'ACTIVO'): ?>
                                    <span class="badge badge-success" style="background: #E8F5E9; color: #2E7D32; border:1px solid #C8E6C9;">
                                        EN CURSO
                                    </span>
                                <?php else: ?>
                                    <span class="badge" style="background: #ECEFF1; color: #546E7A; border:1px solid #CFD8DC;">
                                        FINALIZADO
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td style="font-weight: bold; font-size: 1.05rem;">
                                <?php echo htmlspecialchars($c['nombre']); ?>
                            </td>
                            <td>
                                <a href="ver.php?id=<?php echo $c['grupo_id']; ?>" style="font-weight: bold; color: var(--color-brand); text-decoration: underline;">
                                    <?php echo htmlspecialchars($c['nombre_grupo']); ?>
                                </a>
                            </td>
                            
                            <?php if($rol_usuario == 1): ?>
                                <td>
                                    <small><i class='bx bx-user'></i> <?php echo htmlspecialchars($c['nombre_promotora']); ?></small>
                                </td>
                            <?php endif; ?>

                            <td>
                                <div style="font-size: 0.9rem;">
                                    <i class='bx bx-calendar'></i> 
                                    <?php echo date('d/m/Y', strtotime($c['fecha_inicio'])); ?>
                                </div>
                                <small style="color: var(--text-muted);">
                                    Hasta: <?php echo date('d/m/Y', strtotime($c['fecha_fin_estimada'])); ?>
                                </small>
                            </td>
                            
                            <td style="color: var(--color-warning); font-weight: bold;">
                                <?php echo $c['tasa_interes_mensual']; ?>%
                            </td>
                            <td>
                                <div style="display: flex; gap: 5px;">
                                    <a href="../miembros/index.php?ciclo_id=<?php echo $c['id']; ?>&origen=ciclos_global" class="btn btn-sm btn-brand" title="Ver Miembros">
                                        <i class='bx bx-group'></i>
                                    </a>
                                    
                                    <a href="ver_ciclo.php?id=<?php echo $c['id']; ?>&origen=global" class="btn btn-sm btn-secondary" title="Ver Detalles">
                                        <i class='bx bx-show'></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="text-center" style="padding: 40px; color: var(--text-muted);">
                <i class='bx bx-recycle' style="font-size: 3rem;"></i>
                <p>No se encontraron ciclos con el filtro seleccionado.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>