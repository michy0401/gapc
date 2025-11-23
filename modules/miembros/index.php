<?php
// modules/miembros/index.php
require_once '../../includes/header.php';
require_once '../../config/db.php';

// 1. DETERMINAR EL MODO (¿Es vista Global o de un Ciclo específico?)
$ciclo_id = isset($_GET['ciclo_id']) ? $_GET['ciclo_id'] : null;
$is_global_view = empty($ciclo_id);

// RECUPERAR EL ORIGEN (Para saber a dónde volver)
// Valores posibles: 'grupo' (default), 'ciclos_global', 'detalle_ciclo'
$origen = isset($_GET['origen']) ? $_GET['origen'] : 'grupo';

// Variables para el encabezado
$titulo_pagina = "Directorio Global de Miembros";
$subtitulo = "Todos los registros activos";
$datos_ciclo = null;

// 2. CONSTRUCCIÓN DE LA CONSULTA SQL
// Usamos mc.id simple para no confundir al HTML de abajo
$sql = "SELECT mc.id, 
               u.nombre_completo, u.dui, u.telefono, 
               cc.nombre as cargo, 
               mc.saldo_ahorros,
               g.nombre as nombre_grupo, 
               c.nombre as nombre_ciclo,
               g.id as grupo_id
        FROM Miembro_Ciclo mc
        JOIN Usuario u ON mc.usuario_id = u.id
        JOIN Catalogo_Cargos cc ON mc.cargo_id = cc.id
        JOIN Ciclo c ON mc.ciclo_id = c.id
        JOIN Grupo g ON c.grupo_id = g.id
        WHERE 1=1 ";

$params = [];

if ($is_global_view) {
    // --- MODO GLOBAL ---
    if ($_SESSION['rol_usuario'] != 1) { // Si no es Admin, filtrar por promotora
        $sql .= " AND g.promotora_id = ?";
        $params[] = $_SESSION['user_id'];
    }
    $sql .= " AND c.estado = 'ACTIVO'";
    
} else {
    // --- MODO CONTEXTO CICLO ---
    $sql .= " AND mc.ciclo_id = ?";
    $params[] = $ciclo_id;

    // Info para el título
    $stmt_info = $pdo->prepare("SELECT c.nombre as ciclo, g.nombre as grupo, g.id as grupo_id 
                                FROM Ciclo c JOIN Grupo g ON c.grupo_id = g.id WHERE c.id = ?");
    $stmt_info->execute([$ciclo_id]);
    $datos_ciclo = $stmt_info->fetch();
    
    if($datos_ciclo) {
        $titulo_pagina = "Gestión de Miembros";
        $subtitulo = $datos_ciclo['grupo'] . " - " . $datos_ciclo['ciclo'];
    }
}

$sql .= " ORDER BY u.nombre_completo ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$miembros = $stmt->fetchAll();
?>

<div class="flex-between" style="margin-bottom: 20px;">
    <div>
        <?php if (!$is_global_view && $datos_ciclo): ?>
            <?php 
                // Lógica de retorno
                $link_volver = "../grupos/ver.php?id=" . $datos_ciclo['grupo_id'];
                $texto_volver = "Volver al Grupo";

                if ($origen == 'ciclos_global') {
                    // Si vino del listado global de ciclos
                    $link_volver = "../grupos/ciclos_global.php";
                    $texto_volver = "Volver a Gestión Global de Ciclos";
                } elseif ($origen == 'detalle_ciclo') {
                    // Si vino del detalle del ciclo específico
                    // Truco: Le pasamos '&origen=global' de regreso por si quiere seguir subiendo
                    $link_volver = "../grupos/ver_ciclo.php?id=" . $ciclo_id . "&origen=global";
                    $texto_volver = "Volver al Ciclo";
                }
            ?>
            <a href="<?php echo $link_volver; ?>" style="color: var(--text-muted); display: flex; align-items: center; gap: 5px; margin-bottom: 5px;">
                <i class='bx bx-arrow-back'></i> <?php echo $texto_volver; ?>
            </a>
        <?php endif; ?>
        
        <h2><?php echo $titulo_pagina; ?></h2>
        <p style="color: var(--color-brand); font-weight: bold;">
            <?php echo $subtitulo; ?>
        </p>
    </div>

    <?php if (!$is_global_view): ?>
        <a href="crear.php?ciclo_id=<?php echo $ciclo_id; ?>" class="btn btn-primary">
            <i class='bx bx-user-plus'></i> Inscribir Socia
        </a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="form-group" style="max-width: 300px; margin-bottom: 15px;">
        <input type="text" id="buscador" placeholder="Buscar..." onkeyup="filtrarTabla()" style="padding: 8px 12px;">
    </div>

    <div class="table-container">
        <table class="table" id="tablaMiembros">
            <thead>
                <tr>
                    <th>Nombre Completo</th>
                    <?php if ($is_global_view): ?>
                        <th>Grupo / Ciclo</th>
                    <?php endif; ?>
                    <th>Cargo</th>
                    <th>DUI</th>
                    <th>Teléfono</th>
                    <th>Ahorro</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($miembros) > 0): ?>
                    <?php foreach ($miembros as $m): ?>
                        <tr>
                            <td style="font-weight: bold;">
                                <?php echo htmlspecialchars($m['nombre_completo']); ?>
                            </td>
                            
                            <?php if ($is_global_view): ?>
                                <td>
                                    <small style="display:block; font-weight:bold; color:var(--color-brand);">
                                        <?php echo htmlspecialchars($m['nombre_grupo']); ?>
                                    </small>
                                    <small style="color:var(--text-muted);">
                                        <?php echo htmlspecialchars($m['nombre_ciclo']); ?>
                                    </small>
                                </td>
                            <?php endif; ?>

                            <td>
                                <?php 
                                    $bg = '#FAFAFA'; $col = '#666';
                                    switch($m['cargo']) {
                                        case 'Presidenta': $bg='#E8F5E9'; $col='#2E7D32'; break;
                                        case 'Tesorera': $bg='#E3F2FD'; $col='#1565C0'; break;
                                        case 'Secretaria': $bg='#FFF3E0'; $col='#EF6C00'; break;
                                        case 'Responsable de Llave': $bg='#F3E5F5'; $col='#7B1FA2'; break;
                                    }
                                ?>
                                <span class="badge" style="background: <?php echo $bg; ?>; color: <?php echo $col; ?>; border: 1px solid <?php echo $col; ?>20;">
                                    <?php echo htmlspecialchars($m['cargo']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($m['dui']); ?></td>
                            <td><?php echo htmlspecialchars($m['telefono']); ?></td>
                            <td style="color: var(--color-success); font-weight: bold;">
                                $<?php echo number_format($m['saldo_ahorros'], 2); ?>
                            </td>
                            <td>
                                <div style="display:flex; gap:5px;">
                                    <a href="editar.php?id=<?php echo $m['id']; ?>" class="btn btn-sm btn-secondary" title="Editar">
                                        <i class='bx bx-pencil'></i>
                                    </a>
                                    <a href="perfil.php?id=<?php echo $m['id']; ?>" class="btn btn-sm btn-primary" title="Ver Perfil">
                                        <i class='bx bx-user'></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="<?php echo $is_global_view ? 7 : 6; ?>" class="text-center" style="padding: 40px; color: var(--text-muted);">
                            <p>No se encontraron miembros.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function filtrarTabla() {
    var input, filter, table, tr, td, i, txtValue;
    input = document.getElementById("buscador");
    filter = input.value.toUpperCase();
    table = document.getElementById("tablaMiembros");
    tr = table.getElementsByTagName("tr");
    for (i = 1; i < tr.length; i++) {
        var tdNombre = tr[i].getElementsByTagName("td")[0];
        if (tdNombre) {
            txtValue = tdNombre.textContent || tdNombre.innerText;
            if (txtValue.toUpperCase().indexOf(filter) > -1) {
                tr[i].style.display = "";
            } else {
                tr[i].style.display = "none";
            }
        }
    }
}
</script>

<?php require_once '../../includes/footer.php'; ?>