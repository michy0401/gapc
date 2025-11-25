<?php
// modules/reportes/index.php
require_once '../../includes/header.php';
require_once '../../config/db.php';

$rol = $_SESSION['rol_usuario'];
$uid = $_SESSION['user_id'];

// 1. CONFIGURACIÓN DE FILTROS
// Por defecto mostramos 'ACTIVO', pero permitimos cambiar a 'LIQUIDADO' o 'TODOS'
$filtro_estado = isset($_GET['estado']) ? $_GET['estado'] : 'ACTIVO'; 

$sql = "SELECT c.id, c.nombre, c.estado, g.nombre as grupo, c.fecha_inicio 
        FROM Ciclo c 
        JOIN Grupo g ON c.grupo_id = g.id 
        WHERE 1=1"; // Base verdadera para concatenar

// Filtro de Rol (Seguridad)
if ($rol != 1) { 
    $sql .= " AND g.promotora_id = $uid"; 
}

// Filtro de Estado
if ($filtro_estado != 'TODOS') {
    $sql .= " AND c.estado = '$filtro_estado'";
}

$sql .= " ORDER BY g.nombre ASC, c.fecha_inicio DESC";

$ciclos = $pdo->query($sql)->fetchAll();
?>

<div class="flex-between" style="margin-bottom: 20px;">
    <div>
        <h2>Centro de Reportes</h2>
        <p style="color: var(--text-muted);">Consulte la información histórica y actual de sus ciclos.</p>
    </div>
</div>

<div style="margin-bottom: 20px; display: flex; gap: 10px;">
    <a href="?estado=ACTIVO" class="btn <?php echo $filtro_estado == 'ACTIVO' ? 'btn-success' : 'btn-secondary'; ?>">
        <i class='bx bx-play-circle'></i> Activos
    </a>
    <a href="?estado=LIQUIDADO" class="btn <?php echo $filtro_estado == 'LIQUIDADO' ? 'btn-primary' : 'btn-secondary'; ?>">
        <i class='bx bx-archive-in'></i> Historial / Cerrados
    </a>
    <a href="?estado=TODOS" class="btn <?php echo $filtro_estado == 'TODOS' ? 'btn-brand' : 'btn-secondary'; ?>">
        <i class='bx bx-list-ul'></i> Ver Todos
    </a>
</div>

<?php if (count($ciclos) > 0): ?>
    <div class="grid-2">
        <?php foreach($ciclos as $c): ?>
            <div class="card" style="border-left: 5px solid <?php echo ($c['estado']=='ACTIVO') ? 'var(--color-success)' : '#607D8B'; ?>;">
                
                <div class="flex-between">
                    <h3 style="margin:0; color: var(--color-brand);"><?php echo htmlspecialchars($c['grupo']); ?></h3>
                    <?php if($c['estado'] == 'LIQUIDADO'): ?>
                        <span class="badge" style="background: #ECEFF1; color: #455A64;">CERRADO</span>
                    <?php else: ?>
                        <span class="badge badge-success">EN CURSO</span>
                    <?php endif; ?>
                </div>

                <p style="color: var(--text-muted); margin-bottom: 20px;">
                    <strong><?php echo htmlspecialchars($c['nombre']); ?></strong>
                    <br>
                    <small>Fecha Inicio: <?php echo date('d/m/Y', strtotime($c['fecha_inicio'])); ?></small>
                </p>

                <div style="display: grid; gap: 10px;">
                    <a href="balance.php?ciclo_id=<?php echo $c['id']; ?>&origen=<?php echo $filtro_estado; ?>" class="btn btn-secondary btn-block" style="text-align: left;">
                        <i class='bx bx-pie-chart-alt-2'></i> Balance General
                    </a>
                    
                    <a href="cartera.php?ciclo_id=<?php echo $c['id']; ?>&origen=<?php echo $filtro_estado; ?>" class="btn btn-secondary btn-block" style="text-align: left;">
                        <i class='bx bx-money'></i> Cartera de Préstamos
                    </a>

                    <a href="ahorros_lista.php?ciclo_id=<?php echo $c['id']; ?>&origen=<?php echo $filtro_estado; ?>" class="btn btn-secondary btn-block" style="text-align: left;">
                        <i class='bx bx-list-ol'></i> Sábana de Ahorros
                    </a>

                    <a href="caja.php?ciclo_id=<?php echo $c['id']; ?>&origen=<?php echo $filtro_estado; ?>" class="btn btn-secondary btn-block" style="text-align: left;">
                        <i class='bx bx-transfer'></i> Historial de Flujo de Caja
                    </a>

                    <a href="utilidades.php?ciclo_id=<?php echo $c['id']; ?>&origen=<?php echo $filtro_estado; ?>" class="btn btn-secondary btn-block" style="text-align: left;">
                        <i class='bx bx-money-withdraw'></i> Distribución de Utilidades
                    </a>

                    <?php if($c['estado'] == 'LIQUIDADO'): ?>
                            <a href="../grupos/acta_cierre.php?ciclo_id=<?php echo $c['id']; ?>&origen=reportes_<?php echo $filtro_estado; ?>" class="btn btn-primary btn-block" style="text-align: left; margin-top: 10px;">
                            <i class='bx bxs-file-doc'></i> Ver Acta de Liquidación
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="card text-center" style="padding: 50px;">
        <i class='bx bx-folder-open' style="font-size: 4rem; color: #ccc;"></i>
        <h3>No se encontraron reportes</h3>
        <p>No hay ciclos en este estado.</p>
    </div>
<?php endif; ?>

<?php require_once '../../includes/footer.php'; ?>