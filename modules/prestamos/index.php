<?php
// modules/prestamos/index.php
require_once '../../includes/header.php';
require_once '../../config/db.php';

// Filtro de Rol
$rol = $_SESSION['rol_usuario'];
$uid = $_SESSION['user_id'];

// Consulta Maestra de Cartera
// Traemos: Quién, De qué grupo, Monto Original, Pagado hasta hoy, Fecha Fin.
$sql = "SELECT p.*, 
               u.nombre_completo, 
               g.nombre as grupo,
               (SELECT IFNULL(SUM(monto), 0) FROM Transaccion_Caja WHERE prestamo_id = p.id AND tipo_movimiento = 'PAGO_PRESTAMO_CAPITAL') as capital_pagado
        FROM Prestamo p
        JOIN Miembro_Ciclo mc ON p.miembro_ciclo_id = mc.id
        JOIN Usuario u ON mc.usuario_id = u.id
        JOIN Ciclo c ON mc.ciclo_id = c.id
        JOIN Grupo g ON c.grupo_id = g.id
        WHERE 1=1 ";

if ($rol != 1) { // Si es promotora, filtrar sus grupos
    $sql .= " AND g.promotora_id = $uid";
}

// Por defecto mostramos primero los activos
$sql .= " ORDER BY p.estado ASC, p.fecha_vencimiento ASC";

$prestamos = $pdo->query($sql)->fetchAll();
?>

<div class="flex-between" style="margin-bottom: 20px;">
    <div>
        <h2>Cartera de Préstamos Global</h2>
        <p style="color: var(--text-muted);">Monitoreo de todos los créditos otorgados.</p>
    </div>
</div>

<div class="card">
    <div class="table-container">
        <?php if (count($prestamos) > 0): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Estado</th>
                        <th>Socia / Grupo</th>
                        <th>Monto Original</th>
                        <th>Pagado</th>
                        <th>Saldo Pendiente</th>
                        <th>Vencimiento</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($prestamos as $p): ?>
                        <?php 
                            $saldo = $p['monto_aprobado'] - $p['capital_pagado'];
                            $porcentaje_pagado = ($p['capital_pagado'] / $p['monto_aprobado']) * 100;
                        ?>
                        <tr>
                            <td>
                                <?php if($p['estado'] == 'ACTIVO'): ?>
                                    <span class="badge" style="background:#FFF3E0; color:#EF6C00; border:1px solid #FFE0B2;">VIGENTE</span>
                                <?php elseif($p['estado'] == 'FINALIZADO'): ?>
                                    <span class="badge" style="background:#E8F5E9; color:#2E7D32; border:1px solid #C8E6C9;">PAGADO</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">MORA</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($p['nombre_completo']); ?></strong>
                                <br>
                                <small style="color:#999;"><?php echo htmlspecialchars($p['grupo']); ?></small>
                            </td>
                            <td>$<?php echo number_format($p['monto_aprobado'], 2); ?></td>
                            <td>
                                <span style="color: var(--color-success);">
                                    $<?php echo number_format($p['capital_pagado'], 2); ?>
                                </span>
                                <div style="width: 100%; background: #eee; height: 5px; border-radius: 5px; margin-top: 5px;">
                                    <div style="width: <?php echo $porcentaje_pagado; ?>%; background: var(--color-success); height: 5px; border-radius: 5px;"></div>
                                </div>
                            </td>
                            <td style="font-weight: bold; color: var(--color-brand);">
                                $<?php echo number_format($saldo, 2); ?>
                            </td>
                            <td>
                                <?php echo date('d/m/Y', strtotime($p['fecha_vencimiento'])); ?>
                            </td>
                            <td>
                                <a href="ver.php?id=<?php echo $p['id']; ?>" class="btn btn-sm btn-secondary" title="Ver Historial">
                                    <i class='bx bx-show'></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="text-center" style="padding: 40px; color: var(--text-muted);">
                <i class='bx bx-money' style="font-size: 3rem;"></i>
                <p>No hay préstamos registrados en el sistema.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>