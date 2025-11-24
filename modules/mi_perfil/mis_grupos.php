<?php
require_once '../../includes/header.php';
require_once '../../config/db.php';

$uid = $_SESSION['user_id'];

// CONSULTA: ¿En qué grupos estoy metido y qué cargo tengo?
// Filtramos solo ciclos ACTIVOS para la operación diaria
$sql = "SELECT g.id as grupo_id, g.nombre as nombre_grupo, 
               c.nombre as nombre_ciclo, c.id as ciclo_id,
               cc.nombre as cargo, cc.es_directiva
        FROM Miembro_Ciclo mc
        JOIN Ciclo c ON mc.ciclo_id = c.id
        JOIN Grupo g ON c.grupo_id = g.id
        JOIN Catalogo_Cargos cc ON mc.cargo_id = cc.id
        WHERE mc.usuario_id = ? AND c.estado = 'ACTIVO'";

$stmt = $pdo->prepare($sql);
$stmt->execute([$uid]);
$mis_grupos = $stmt->fetchAll();
?>

<h2 style="color: var(--color-brand); margin-bottom: 20px;">Mis Grupos Activos</h2>

<?php if (count($mis_grupos) > 0): ?>
    <div class="grid-2">
        <?php foreach($mis_grupos as $mg): ?>
            <div class="card" style="border-left: 5px solid <?php echo $mg['es_directiva'] ? 'var(--color-warning)' : 'var(--color-success)'; ?>;">
                
                <div class="flex-between">
                    <h3 style="margin:0; color: var(--color-brand);">
                        <i class='bx bxs-group'></i> <?php echo htmlspecialchars($mg['nombre_grupo']); ?>
                    </h3>
                    <?php if($mg['es_directiva']): ?>
                        <span class="badge" style="background: #FFF3E0; color: #EF6C00; border: 1px solid #FFE0B2;">
                            <i class='bx bxs-star'></i> <?php echo htmlspecialchars($mg['cargo']); ?>
                        </span>
                    <?php else: ?>
                        <span class="badge" style="background: #F5F5F5; color: #666;">
                            <?php echo htmlspecialchars($mg['cargo']); ?>
                        </span>
                    <?php endif; ?>
                </div>

                <p style="color: var(--text-muted); margin: 10px 0;">
                    Ciclo Actual: <strong><?php echo htmlspecialchars($mg['nombre_ciclo']); ?></strong>
                </p>

                <?php if($mg['es_directiva']): ?>
                    <hr style="margin: 15px 0; border: 0; border-top: 1px solid #eee;">
                    <p style="font-size: 0.9rem; color: var(--color-warning);">
                        <i class='bx bx-key'></i> Tienes permisos de administración en este grupo.
                    </p>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <a href="../reuniones/lista.php?ciclo_id=<?php echo $mg['ciclo_id']; ?>&origen=mis_grupos" class="btn btn-primary btn-sm">
                            <i class='bx bx-calendar'></i> Reuniones
                        </a>
                        
                        <a href="../grupos/ver.php?id=<?php echo $mg['grupo_id']; ?>&origen=mis_grupos" class="btn btn-secondary btn-sm">
                            <i class='bx bx-cog'></i> Detalles
                        </a>
                    </div>
                <?php else: ?>
                    <a href="mis_finanzas.php" class="btn btn-secondary btn-block">
                        <i class='bx bx-line-chart'></i> Ver Mi Estado de Cuenta
                    </a>
                <?php endif; ?>

            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="card text-center" style="padding: 50px;">
        <i class='bx bx-ghost' style="font-size: 4rem; color: #ccc;"></i>
        <h3>No tienes grupos activos</h3>
        <p>Contacta a una promotora para ser inscrita en un nuevo ciclo.</p>
    </div>
<?php endif; ?>

<?php require_once '../../includes/footer.php'; ?>