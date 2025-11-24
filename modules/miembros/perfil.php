<?php
// modules/miembros/perfil.php
require_once '../../includes/header.php';
require_once '../../config/db.php';

if (!isset($_GET['id'])) { header("Location: index.php"); exit; }
$miembro_id = $_GET['id'];

// 1. DATOS DEL PERFIL
$sql = "SELECT mc.*, u.nombre_completo, u.dui, u.telefono, u.direccion, 
               cc.nombre as cargo, g.nombre as grupo, c.nombre as ciclo
        FROM Miembro_Ciclo mc
        JOIN Usuario u ON mc.usuario_id = u.id
        JOIN Catalogo_Cargos cc ON mc.cargo_id = cc.id
        JOIN Ciclo c ON mc.ciclo_id = c.id
        JOIN Grupo g ON c.grupo_id = g.id
        WHERE mc.id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$miembro_id]);
$p = $stmt->fetch();

// 2. CALCULAR DEUDAS ACTIVAS
// Préstamos (Capital pendiente)
$sql_prestamo = "SELECT SUM(p.monto_aprobado - IFNULL((SELECT SUM(monto) FROM Transaccion_Caja WHERE prestamo_id = p.id AND tipo_movimiento = 'PAGO_PRESTAMO_CAPITAL'), 0)) 
                 FROM Prestamo p WHERE p.miembro_ciclo_id = ? AND p.estado = 'ACTIVO'";
$stmt_pr = $pdo->prepare($sql_prestamo);
$stmt_pr->execute([$miembro_id]);
$deuda_prestamos = $stmt_pr->fetchColumn() ?: 0;

// Multas
$sql_multas = "SELECT SUM(monto) FROM Deuda_Multa WHERE miembro_ciclo_id = ? AND estado = 'PENDIENTE'";
$stmt_mu = $pdo->prepare($sql_multas);
$stmt_mu->execute([$miembro_id]);
$deuda_multas = $stmt_mu->fetchColumn() ?: 0;

// 3. OBTENER HISTORIAL DE TRANSACCIONES (¡LO NUEVO!) ✅
$sql_hist = "SELECT t.*, r.fecha as fecha_reunion, r.numero_reunion 
             FROM Transaccion_Caja t
             JOIN Reunion r ON t.reunion_id = r.id
             WHERE t.miembro_ciclo_id = ?
             ORDER BY t.id DESC LIMIT 20"; // Últimos 20 movimientos
$stmt_h = $pdo->prepare($sql_hist);
$stmt_h->execute([$miembro_id]);
$historial = $stmt_h->fetchAll();
?>

<div class="flex-between" style="margin-bottom:20px;">
    <a href="javascript:history.back()" class="btn btn-secondary"><i class='bx bx-arrow-back'></i> Volver</a>
    <h2>Perfil de Socia</h2>
</div>

<div class="grid-2">
    <div class="card" style="border-left: 5px solid var(--color-brand);">
        <div style="text-align:center; margin-bottom:20px;">
            <i class='bx bxs-user-circle' style="font-size: 4rem; color: var(--color-brand);"></i>
            <h3><?php echo htmlspecialchars($p['nombre_completo']); ?></h3>
            <span class="badge" style="background:#E3F2FD; color:#1565C0;"><?php echo $p['cargo']; ?></span>
        </div>
        
        <div style="font-size: 0.9rem; color: #555;">
            <p><strong>DUI:</strong> <?php echo htmlspecialchars($p['dui']); ?></p>
            <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($p['telefono']); ?></p>
            <p><strong>Dirección:</strong> <?php echo htmlspecialchars($p['direccion']); ?></p>
            <hr style="margin: 15px 0; border:0; border-top:1px solid #eee;">
            <p><small><strong>Grupo:</strong> <?php echo htmlspecialchars($p['grupo']); ?></small></p>
            <p><small><strong>Ciclo:</strong> <?php echo htmlspecialchars($p['ciclo']); ?></small></p>
        </div>
    </div>

    <div class="card" style="border-left: 5px solid var(--color-success);">
        <h3>Estado Financiero</h3>
        
        <div style="background: #E8F5E9; padding: 20px; border-radius: 8px; text-align: center; margin-bottom: 20px;">
            <span style="display:block; color: #2E7D32; font-size: 0.9rem;">AHORRO ACUMULADO</span>
            <span style="display:block; color: #2E7D32; font-size: 2.5rem; font-weight: bold;">
                $<?php echo number_format($p['saldo_ahorros'], 2); ?>
            </span>
        </div>

        <div class="grid-2">
            <div style="text-align:center; padding:10px; background:#FFF3E0; border-radius:8px; border: 1px solid #FFE0B2;">
                <span style="display:block; font-size:0.8rem; color: #E65100;">PRÉSTAMOS ACTIVOS</span>
                <strong style="font-size: 1.2rem; color: #E65100;">
                    $<?php echo number_format($deuda_prestamos, 2); ?>
                </strong>
            </div>
            <div style="text-align:center; padding:10px; background:#FFEBEE; border-radius:8px; border: 1px solid #FFCDD2;">
                <span style="display:block; font-size:0.8rem; color: #C62828;">MULTAS PENDIENTES</span>
                <strong style="font-size: 1.2rem; color: #C62828;">
                    $<?php echo number_format($deuda_multas, 2); ?>
                </strong>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <h3>Historial de Transacciones Recientes</h3>
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Reunión</th>
                    <th>Movimiento</th>
                    <th>Concepto</th>
                    <th>Monto</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($historial) > 0): ?>
                    <?php foreach($historial as $h): 
                        // Formatear tipo de movimiento
                        $tipo_raw = $h['tipo_movimiento'];
                        $tipo_fmt = str_replace('_', ' ', $tipo_raw);
                        
                        // Color e icono según tipo
                        $color = '#333';
                        $icono = '';
                        
                        if(strpos($tipo_raw, 'AHORRO') !== false) {
                            $color = 'var(--color-success)';
                            $icono = "<i class='bx bx-up-arrow-circle'></i>";
                        } elseif (strpos($tipo_raw, 'PAGO') !== false) {
                            $color = '#1565C0'; // Azul para pagos
                            $icono = "<i class='bx bx-check-circle'></i>";
                        } elseif (strpos($tipo_raw, 'DESEMBOLSO') !== false) {
                            $color = '#EF6C00'; // Naranja para recibir préstamo
                            $icono = "<i class='bx bx-down-arrow-circle'></i>";
                        } elseif (strpos($tipo_raw, 'MULTA') !== false) {
                            $color = 'var(--color-danger)';
                        }
                    ?>
                    <tr>
                        <td><?php echo date('d/m/Y', strtotime($h['fecha_reunion'])); ?></td>
                        <td>Reunión #<?php echo $h['numero_reunion']; ?></td>
                        <td style="font-weight:bold; color: <?php echo $color; ?>; font-size: 0.9rem;">
                            <?php echo $icono . ' ' . $tipo_fmt; ?>
                        </td>
                        <td><?php echo htmlspecialchars($h['observacion']); ?></td>
                        <td style="font-weight:bold; color: <?php echo $color; ?>;">
                            $<?php echo number_format($h['monto'], 2); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center" style="padding: 30px; color: #999;">
                            No hay movimientos registrados aún.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>