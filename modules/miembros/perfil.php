<?php
require_once '../../includes/header.php';
require_once '../../config/db.php';

if (!isset($_GET['id'])) { header("Location: lista_global.php"); exit; }
$miembro_id = $_GET['id'];

// Obtener perfil completo
$sql = "SELECT mc.*, u.nombre_completo, u.dui, u.telefono, u.direccion, cc.nombre as cargo, g.nombre as grupo, c.nombre as ciclo
        FROM Miembro_Ciclo mc
        JOIN Usuario u ON mc.usuario_id = u.id
        JOIN Catalogo_Cargos cc ON mc.cargo_id = cc.id
        JOIN Ciclo c ON mc.ciclo_id = c.id
        JOIN Grupo g ON c.grupo_id = g.id
        WHERE mc.id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$miembro_id]);
$p = $stmt->fetch();
?>

<div class="flex-between" style="margin-bottom:20px;">
    <a href="javascript:history.back()" class="btn btn-secondary"><i class='bx bx-arrow-back'></i> Volver</a>
    <h2>Perfil de Socia</h2>
</div>

<div class="grid-2">
    <div class="card" style="border-left-color: var(--color-brand);">
        <div style="text-align:center; margin-bottom:20px;">
            <i class='bx bxs-user-circle' style="font-size: 4rem; color: var(--color-brand);"></i>
            <h3><?php echo htmlspecialchars($p['nombre_completo']); ?></h3>
            <span class="badge" style="background:#E3F2FD; color:#1565C0;"><?php echo $p['cargo']; ?></span>
        </div>
        
        <p><strong>DUI:</strong> <?php echo htmlspecialchars($p['dui']); ?></p>
        <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($p['telefono']); ?></p>
        <p><strong>Dirección:</strong> <?php echo htmlspecialchars($p['direccion']); ?></p>
        <hr style="margin: 15px 0; border:0; border-top:1px solid #eee;">
        <p><small><strong>Grupo:</strong> <?php echo htmlspecialchars($p['grupo']); ?></small></p>
        <p><small><strong>Ciclo:</strong> <?php echo htmlspecialchars($p['ciclo']); ?></small></p>
    </div>

    <div class="card" style="border-left-color: var(--color-success);">
        <h3>Estado Financiero</h3>
        
        <div style="background: #E8F5E9; padding: 20px; border-radius: 8px; text-align: center; margin-bottom: 20px;">
            <span style="display:block; color: #2E7D32; font-size: 0.9rem;">AHORRO ACUMULADO</span>
            <span style="display:block; color: #2E7D32; font-size: 2.5rem; font-weight: bold;">
                $<?php echo number_format($p['saldo_ahorros'], 2); ?>
            </span>
        </div>

        <div class="grid-2">
            <div style="text-align:center; padding:10px; background:#FFF3E0; border-radius:8px;">
                <span style="display:block; font-size:0.8rem;">PRÉSTAMOS ACTIVOS</span>
                <strong>$0.00</strong> </div>
            <div style="text-align:center; padding:10px; background:#FFEBEE; border-radius:8px;">
                <span style="display:block; font-size:0.8rem;">MULTAS PENDIENTES</span>
                <strong>$0.00</strong> </div>
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
                    <th>Movimiento</th>
                    <th>Monto</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="3" class="text-center">No hay movimientos registrados aún.</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>