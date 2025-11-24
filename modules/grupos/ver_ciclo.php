<?php
// modules/grupos/ver_ciclo.php
require_once '../../includes/header.php';
require_once '../../config/db.php';

if (!isset($_GET['id'])) { header("Location: index.php"); exit; }
$ciclo_id = $_GET['id'];

// 1. INFO DEL CICLO + GRUPO
$stmt = $pdo->prepare("SELECT c.*, g.nombre as grupo, g.id as grupo_id 
                       FROM Ciclo c JOIN Grupo g ON c.grupo_id = g.id WHERE c.id = ?");
$stmt->execute([$ciclo_id]);
$ciclo = $stmt->fetch();

// 2. DETECTAR ORIGEN PARA EL BOTÓN VOLVER (LÓGICA CORREGIDA)
// Verificamos si en la URL viene ?origen=global
// ... 
$origen = isset($_GET['origen']) ? $_GET['origen'] : 'grupo'; 

if ($origen == 'global') {
    $link_volver = "ciclos_global.php";
    $texto_volver = "Volver a Gestión Global";
} elseif ($origen == 'mis_grupos') {
    // CORRECCIÓN: Regresar al grupo MANTENIENDO el origen
    $link_volver = "ver.php?id=" . $ciclo['grupo_id'] . "&origen=mis_grupos";
    $texto_volver = "Volver al Grupo";
} else {
    $link_volver = "ver.php?id=" . $ciclo['grupo_id'];
    $texto_volver = "Volver al Grupo";
}
// ...

// 3. INFO DE MULTAS
$stmt_m = $pdo->prepare("SELECT cm.nombre, conf.monto_aplicar 
                         FROM Configuracion_Multas_Ciclo conf
                         JOIN Catalogo_Multas cm ON conf.catalogo_multa_id = cm.id
                         WHERE conf.ciclo_id = ?");
$stmt_m->execute([$ciclo_id]);
$multas = $stmt_m->fetchAll();

// 4. ESTADÍSTICAS
$stmt_s = $pdo->prepare("SELECT COUNT(*) as total_miembros, SUM(saldo_ahorros) as total_ahorro 
                         FROM Miembro_Ciclo WHERE ciclo_id = ?");
$stmt_s->execute([$ciclo_id]);
$stats = $stmt_s->fetch();
?>

<div class="flex-between" style="margin-bottom: 20px;">
    <div>
        <a href="<?php echo $link_volver; ?>" class="btn btn-secondary">
            <i class='bx bx-arrow-back'></i> <?php echo $texto_volver; ?>
        </a>
        
        <h2 style="margin-top:10px;">Detalle de Ciclo Operativo</h2>
        <p style="color: var(--color-brand);">
            Grupo: <strong><?php echo htmlspecialchars($ciclo['grupo']); ?></strong>
        </p>
    </div>
    
    <?php 
        // Si estamos en el flujo de "Mis Grupos", le decimos a la siguiente pantalla
        // que use el modo "detalle_ciclo_mg" (MG = Mis Grupos)
        $origen_siguiente = ($origen == 'mis_grupos') ? 'detalle_ciclo_mg' : 'detalle_ciclo';
    ?>
    
    <a href="../miembros/index.php?ciclo_id=<?php echo $ciclo_id; ?>&origen=<?php echo $origen_siguiente; ?>" class="btn btn-primary">
        <i class='bx bx-group'></i> Ver Miembros Inscritos
    </a>
</div>

<div class="grid-2">
    
    <div class="card">
        <h3>Configuración del Periodo</h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
            <div>
                <small style="color:var(--text-muted);">Nombre Ciclo</small>
                <p><strong><?php echo htmlspecialchars($ciclo['nombre']); ?></strong></p>
            </div>
            <div>
                <small style="color:var(--text-muted);">Estado</small>
                <br>
                <span class="badge" style="background: <?php echo ($ciclo['estado']=='ACTIVO')?'#E8F5E9':'#eee'; ?>;">
                    <?php echo $ciclo['estado']; ?>
                </span>
            </div>
            <div>
                <small style="color:var(--text-muted);">Fecha Inicio</small>
                <p><?php echo date('d/m/Y', strtotime($ciclo['fecha_inicio'])); ?></p>
            </div>
            <div>
                <small style="color:var(--text-muted);">Fin Estimado</small>
                <p><?php echo date('d/m/Y', strtotime($ciclo['fecha_fin_estimada'])); ?></p>
            </div>
        </div>

        <hr style="margin: 15px 0; border: 0; border-top: 1px solid #eee;">
        
        <div style="background: #FFFDE7; padding: 10px; border-radius: 8px;">
            <strong style="color: #F57F17;">Tasa de Interés: <?php echo $ciclo['tasa_interes_mensual']; ?>%</strong>
            <p style="font-size: 0.8rem; margin:0;">Mensual sobre saldo prestado</p>
        </div>
    </div>

    <div class="card" style="border-left-color: var(--color-warning);">
        <h3>Multas y Tarifas</h3>
        <?php if(count($multas) > 0): ?>
            <ul style="list-style: none;">
                <?php foreach($multas as $m): ?>
                    <li style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px dashed #eee;">
                        <span><?php echo htmlspecialchars($m['nombre']); ?></span>
                        <strong style="color: var(--color-danger);">$<?php echo number_format($m['monto_aplicar'], 2); ?></strong>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p style="color: #999;">No hay multas configuradas.</p>
        <?php endif; ?>

        <br>
        <h3>Resumen Actual</h3>
        <div class="grid-2">
            <div class="text-center" style="background: #F5F7FA; padding: 10px; border-radius: 8px;">
                <span style="font-size: 1.5rem; font-weight: bold; color: var(--color-brand);">
                    <?php echo $stats['total_miembros']; ?>
                </span>
                <small style="display:block;">Socias</small>
            </div>
            <div class="text-center" style="background: #E8F5E9; padding: 10px; border-radius: 8px;">
                <span style="font-size: 1.5rem; font-weight: bold; color: var(--color-success);">
                    $<?php echo number_format($stats['total_ahorro'], 2); ?>
                </span>
                <small style="display:block;">Ahorrado</small>
            </div>
            <br>
            <?php if($ciclo['estado'] == 'ACTIVO'): ?>
                <a href="cierre_ciclo.php?ciclo_id=<?php echo $ciclo_id; ?>" class="btn btn-danger btn-block" style="margin-top: 15px;">
                    <i class='bx bx-stop-circle'></i> CERRAR Y LIQUIDAR CICLO
                </a>
            <?php elseif($ciclo['estado'] == 'LIQUIDADO'): ?>
                <a href="acta_cierre.php?ciclo_id=<?php echo $ciclo_id; ?>" class="btn btn-secondary btn-block" style="margin-top: 15px;">
                    <i class='bx bxs-file-pdf'></i> VER ACTA DE LIQUIDACIÓN
                </a>
            <?php endif; ?>

        </div>
    </div>

</div>

<?php require_once '../../includes/footer.php'; ?>