<?php
// modules/reuniones/ahorros.php
require_once '../../includes/header.php';
require_once '../../config/db.php';

if (!isset($_GET['id'])) { header("Location: index.php"); exit; }
$reunion_id = $_GET['id'];

// 1. INFO REUNIÓN
$stmt_r = $pdo->prepare("SELECT r.*, c.nombre as ciclo, g.nombre as grupo, c.id as ciclo_id 
                         FROM Reunion r JOIN Ciclo c ON r.ciclo_id = c.id JOIN Grupo g ON c.grupo_id = g.id 
                         WHERE r.id = ?");
$stmt_r->execute([$reunion_id]);
$reunion = $stmt_r->fetch();

// 2. PROCESAR GUARDADO MASIVO
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $pdo->beginTransaction();

        // Recorremos los arrays enviados
        // $_POST['ahorro'][id_miembro] = monto
        // $_POST['otros'][id_miembro] = monto
        
        $total_ingresado = 0;

        foreach ($_POST['ahorro'] as $miembro_id => $monto_ahorro) {
            $monto_ahorro = floatval($monto_ahorro); // Asegurar que es número
            $monto_otros = floatval($_POST['otros'][$miembro_id]);

            // A. PROCESAR AHORRO (Si es mayor a 0)
            if ($monto_ahorro > 0) {
                // 1. Registrar Transacción
                $sql_t1 = "INSERT INTO Transaccion_Caja (reunion_id, miembro_ciclo_id, tipo_movimiento, monto, observacion) 
                           VALUES (?, ?, 'AHORRO', ?, 'Ahorro Ordinario')";
                $pdo->prepare($sql_t1)->execute([$reunion_id, $miembro_id, $monto_ahorro]);

                // 2. Sumar a la cuenta personal del socio
                $sql_upd = "UPDATE Miembro_Ciclo SET saldo_ahorros = saldo_ahorros + ? WHERE id = ?";
                $pdo->prepare($sql_upd)->execute([$monto_ahorro, $miembro_id]);
                
                $total_ingresado += $monto_ahorro;
            }

            // B. PROCESAR OTROS INGRESOS (Rifas, etc)
            if ($monto_otros > 0) {
                // Solo registramos transacción (es ganancia del grupo, no saldo del socio)
                $sql_t2 = "INSERT INTO Transaccion_Caja (reunion_id, miembro_ciclo_id, tipo_movimiento, monto, observacion) 
                           VALUES (?, ?, 'INGRESO_EXTRA', ?, 'Rifas/Otros')";
                $pdo->prepare($sql_t2)->execute([$reunion_id, $miembro_id, $monto_otros]);
                
                $total_ingresado += $monto_otros;
            }
        }

        $pdo->commit();
        echo "<script>window.location.href='panel.php?id=$reunion_id';</script>";
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error al guardar: " . $e->getMessage();
    }
}

// 3. OBTENER MIEMBROS PARA LA LISTA
$stmt_m = $pdo->prepare("SELECT mc.id, u.nombre_completo, mc.saldo_ahorros 
                         FROM Miembro_Ciclo mc 
                         JOIN Usuario u ON mc.usuario_id = u.id 
                         WHERE mc.ciclo_id = ? 
                         ORDER BY u.nombre_completo ASC");
$stmt_m->execute([$reunion['ciclo_id']]);
$miembros = $stmt_m->fetchAll();
?>

<div class="flex-between" style="margin-bottom: 20px;">
    <div>
        <a href="panel.php?id=<?php echo $reunion_id; ?>" class="btn btn-secondary">
            <i class='bx bx-arrow-back'></i> Volver al Panel
        </a>
        <h2 style="margin-top: 10px;">Recepción de Ahorros</h2>
    </div>
</div>

<div class="card">
    <form method="POST" id="formAhorros">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Socia</th>
                        <th>Acumulado</th>
                        <th style="width: 200px; background: #E8F5E9; color: #2E7D32;">
                            Ahorro Hoy ($)
                        </th>
                        <th style="width: 200px; background: #FFF3E0; color: #EF6C00;">
                            Otros/Rifas ($)
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($miembros as $m): ?>
                        <tr>
                            <td style="font-weight: bold;">
                                <?php echo htmlspecialchars($m['nombre_completo']); ?>
                            </td>
                            <td style="color: var(--text-muted);">
                                $<?php echo number_format($m['saldo_ahorros'], 2); ?>
                            </td>
                            
                            <td style="background: #F1F8E9;">
                                <input type="number" 
                                       name="ahorro[<?php echo $m['id']; ?>]" 
                                       step="0.01" min="0" 
                                       class="input-dinero"
                                       placeholder="0.00">
                            </td>

                            <td style="background: #FFF8E1;">
                                <input type="number" 
                                       name="otros[<?php echo $m['id']; ?>]" 
                                       step="0.01" min="0" 
                                       class="input-dinero"
                                       placeholder="0.00">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <br>
        
        <div style="padding: 20px; background: #F5F7FA; border-radius: 8px; text-align: center;">
            <p style="margin-bottom: 15px; color: var(--text-muted);">
                Verifique el dinero físico antes de guardar. Esta acción sumará al saldo de caja.
            </p>
            <button type="submit" class="btn btn-success btn-block" style="padding: 15px; font-size: 1.2rem;">
                <i class='bx bx-save'></i> REGISTRAR INGRESOS
            </button>
        </div>
    </form>
</div>

<style>
    .input-dinero {
        text-align: right;
        font-weight: bold;
        border: 1px solid #ccc;
    }
    .input-dinero:focus {
        background: #fff;
        border-color: var(--color-success);
    }
</style>

<?php require_once '../../includes/footer.php'; ?>