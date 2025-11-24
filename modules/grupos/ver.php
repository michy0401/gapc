<?php
// modules/grupos/ver.php
require_once '../../includes/header.php';
require_once '../../config/db.php';

if (!isset($_GET['id'])) { header("Location: index.php"); exit; }
$grupo_id = $_GET['id'];

// DETECTAR ORIGEN (Para saber a dónde regresar)
$origen = isset($_GET['origen']) ? $_GET['origen'] : ''; 

// Definir link de regreso
$link_volver = "index.php";
$texto_volver = "Volver a Grupos";

if ($origen == 'mis_grupos') {
    $link_volver = "../../modules/mi_perfil/mis_grupos.php";
    $texto_volver = "Volver a Mis Grupos";
} elseif ($origen == 'ciclos_global') {
    $link_volver = "ciclos_global.php";
    $texto_volver = "Volver a Ciclos";
}

// ... (Consulta de datos del grupo igual que antes) ...
$stmt = $pdo->prepare("SELECT g.*, d.nombre as distrito, u.nombre_completo as promotora FROM Grupo g JOIN Distrito d ON g.distrito_id = d.id JOIN Usuario u ON g.promotora_id = u.id WHERE g.id = ?");
$stmt->execute([$grupo_id]);
$grupo = $stmt->fetch();

// ... (Consulta de ciclos igual que antes) ...
$stmt_ciclos = $pdo->prepare("SELECT * FROM Ciclo WHERE grupo_id = ? ORDER BY id DESC");
$stmt_ciclos->execute([$grupo_id]);
$ciclos = $stmt_ciclos->fetchAll();

// Ciclo activo
$ciclo_activo_id = null;
foreach($ciclos as $c) { if($c['estado'] == 'ACTIVO') { $ciclo_activo_id = $c['id']; break; } }

// ... (Consulta de miembros igual que antes) ...
$miembros = [];
if ($ciclo_activo_id) {
    $sql_miembros = "SELECT mc.*, u.nombre_completo, u.dui, cc.nombre as cargo FROM Miembro_Ciclo mc JOIN Usuario u ON mc.usuario_id = u.id JOIN Catalogo_Cargos cc ON mc.cargo_id = cc.id WHERE mc.ciclo_id = ? ORDER BY u.nombre_completo ASC";
    $stmt_m = $pdo->prepare($sql_miembros);
    $stmt_m->execute([$ciclo_activo_id]);
    $miembros = $stmt_m->fetchAll();
}
?>

<div class="flex-between" style="margin-bottom: 20px;">
    <div class="flex-between" style="gap: 15px; justify-content: flex-start;">
        <a href="<?php echo $link_volver; ?>" class="btn btn-secondary" style="border-radius: 50%; padding: 10px 14px;">
            <i class='bx bx-arrow-back'></i>
        </a>
        <h2><?php echo htmlspecialchars($grupo['nombre']); ?></h2>
    </div>
    
    <a href="crear_ciclo.php?grupo_id=<?php echo $grupo['id']; ?>&origen=<?php echo $origen; ?>" class="btn btn-primary">
        <i class='bx bx-play-circle'></i> Iniciar Nuevo Ciclo
    </a>
</div>

<div class="grid-2">
    <div class="card">
        <h3 style="font-size: 1.1rem; border-bottom: 1px solid #eee; padding-bottom: 10px;">Datos del Grupo</h3>
        <br><p><strong>Distrito:</strong> <br> <?php echo htmlspecialchars($grupo['distrito']); ?></p>
        <br><p><strong>Promotora:</strong> <br> <?php echo htmlspecialchars($grupo['promotora']); ?></p>
        <br><p><strong>Fundación:</strong> <br> <?php echo date('d/m/Y', strtotime($grupo['fecha_creacion'])); ?></p>
    </div>
    <div class="card" style="border-left-color: <?php echo $ciclo_activo_id ? 'var(--color-success)' : '#ccc'; ?>;">
        <h3 style="font-size: 1.1rem; border-bottom: 1px solid #eee; padding-bottom: 10px;">Ciclo Actual</h3>
        <br>
        <?php if ($ciclo_activo_id): ?>
            <div class="text-center">
                <h2 style="color: var(--color-success); margin-bottom: 5px;">ACTIVO</h2>
                <span class="badge badge-success" style="font-size: 1rem; display: inline-block; margin-bottom: 10px;">
                    <?php echo count($miembros); ?> Miembros Inscritos
                </span>
            </div>
        <?php else: ?>
            <div class="text-center" style="color: var(--text-muted);">
                <i class='bx bx-sleepy' style="font-size: 3rem;"></i>
                <p>Sin actividad.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($ciclo_activo_id): ?>
<div class="card">
    <div class="flex-between">
        <h3>Miembros del Ciclo Activo</h3>
        <a href="../miembros/index.php?ciclo_id=<?php echo $ciclo_activo_id; ?>&origen=<?php echo $origen; ?>" class="btn btn-sm btn-secondary">
            <i class='bx bx-edit'></i> Gestionar Miembros
        </a>
    </div>
    
    <div class="table-container">
        <table class="table">
            <thead><tr><th>Nombre</th><th>Cargo</th><th>DUI</th><th>Ahorro Acumulado</th></tr></thead>
            <tbody>
                <?php if (count($miembros) > 0): ?>
                    <?php foreach($miembros as $m): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($m['nombre_completo']); ?></td>
                            <td>
                                <?php 
                                $bg_color = '#FAFAFA'; $text_color = '#616161';
                                switch($m['cargo']) {
                                    case 'Presidenta': $bg_color = '#E8F5E9'; $text_color = '#2E7D32'; break;
                                    case 'Tesorera': $bg_color = '#E3F2FD'; $text_color = '#1565C0'; break;
                                    case 'Secretaria': $bg_color = '#FFF3E0'; $text_color = '#EF6C00'; break;
                                    case 'Responsable de Llave': $bg_color = '#F3E5F5'; $text_color = '#7B1FA2'; break;
                                }
                                ?>
                                <span class="badge" style="background: <?php echo $bg_color; ?>; color: <?php echo $text_color; ?>; border: 1px solid <?php echo $text_color; ?>20;">
                                    <?php echo htmlspecialchars($m['cargo']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($m['dui']); ?></td>
                            <td style="font-weight: bold; color: var(--color-success);">$<?php echo number_format($m['saldo_ahorros'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4" class="text-center">No hay miembros inscritos aún.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<br>

<div class="card">
    <h3>Historial de Ciclos</h3>
    <div class="table-container">
        <table class="table">
            <thead><tr><th>Nombre Ciclo</th><th>Inicio - Fin</th><th>Estado</th><th>Acciones</th></tr></thead>
            <tbody>
                <?php if (count($ciclos) > 0): ?>
                    <?php foreach($ciclos as $c): ?>
                        <tr>
                            <td style="font-weight: bold;"><?php echo htmlspecialchars($c['nombre']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($c['fecha_inicio'])); ?> - <?php echo date('d/m/Y', strtotime($c['fecha_fin_estimada'])); ?></td>
                            <td><span class="badge" style="background: <?php echo ($c['estado']=='ACTIVO')?'#E8F5E9':'#eee'; ?>;"><?php echo $c['estado']; ?></span></td>
                            <td>
                                <a href="ver_ciclo.php?id=<?php echo $c['id']; ?>&origen=<?php echo $origen; ?>" class="btn btn-sm btn-secondary" title="Ver Configuración">
                                    <i class='bx bx-show'></i>
                                </a>
                                <?php if($c['estado'] == 'ACTIVO'): ?>
                                    <a href="../miembros/index.php?ciclo_id=<?php echo $c['id']; ?>&origen=<?php echo $origen; ?>" class="btn btn-sm btn-brand">
                                        <i class='bx bx-group'></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4" class="text-center">No hay ciclos registrados.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>