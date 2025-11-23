<?php
// modules/ahorros/index.php
require_once '../../includes/header.php';
require_once '../../config/db.php';

$rol = $_SESSION['rol_usuario'];
$uid = $_SESSION['user_id'];

// CONSULTA MAESTRA DE AHORRANTES
// Traemos socios activos y sus saldos
$sql = "SELECT mc.id, u.nombre_completo, u.dui, 
               g.nombre as grupo, c.nombre as ciclo, 
               mc.saldo_ahorros
        FROM Miembro_Ciclo mc
        JOIN Usuario u ON mc.usuario_id = u.id
        JOIN Ciclo c ON mc.ciclo_id = c.id
        JOIN Grupo g ON c.grupo_id = g.id
        WHERE c.estado = 'ACTIVO'";

if ($rol != 1) { // Filtro para promotora
    $sql .= " AND g.promotora_id = $uid";
}

$sql .= " ORDER BY u.nombre_completo ASC";

$socios = $pdo->query($sql)->fetchAll();
?>

<div class="flex-between" style="margin-bottom: 20px;">
    <div>
        <h2>Cartera de Ahorros Global</h2>
        <p style="color: var(--text-muted);">Monitoreo de capital social acumulado.</p>
    </div>
</div>

<div class="card">
    <div class="table-container">
        <?php if (count($socios) > 0): ?>
            <table class="table" id="tablaAhorros">
                <thead>
                    <tr>
                        <th>Socia</th>
                        <th>Grupo / Ciclo</th>
                        <th>DUI</th>
                        <th>Saldo Acumulado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($socios as $s): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($s['nombre_completo']); ?></strong>
                            </td>
                            <td>
                                <small style="font-weight:bold; color:var(--color-brand);">
                                    <?php echo htmlspecialchars($s['grupo']); ?>
                                </small>
                                <br>
                                <small style="color:#999;">
                                    <?php echo htmlspecialchars($s['ciclo']); ?>
                                </small>
                            </td>
                            <td><?php echo htmlspecialchars($s['dui']); ?></td>
                            <td>
                                <span style="font-size: 1.1rem; font-weight: bold; color: var(--color-success); background: #E8F5E9; padding: 5px 10px; border-radius: 20px;">
                                    $<?php echo number_format($s['saldo_ahorros'], 2); ?>
                                </span>
                            </td>
                            <td>
                                <a href="ver.php?id=<?php echo $s['id']; ?>" class="btn btn-sm btn-secondary" title="Ver Historial">
                                    <i class='bx bx-show'></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="text-center" style="padding: 40px; color: var(--text-muted);">
                <i class='bx bx-coin-stack' style="font-size: 3rem;"></i>
                <p>No hay registros de ahorros activos.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>