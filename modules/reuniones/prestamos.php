<?php
// modules/reuniones/prestamos.php
require_once '../../includes/header.php';
require_once '../../config/db.php';

if (!isset($_GET['id'])) { header("Location: index.php"); exit; }
$reunion_id = $_GET['id'];

// 1. OBTENER INFO REUNIÓN Y CAJA ACTUAL
$stmt_r = $pdo->prepare("SELECT r.*, c.tasa_interes_mensual, c.id as ciclo_id 
                         FROM Reunion r JOIN Ciclo c ON r.ciclo_id = c.id 
                         WHERE r.id = ?");
$stmt_r->execute([$reunion_id]);
$reunion = $stmt_r->fetch();
$saldo_disponible_caja = $reunion['saldo_caja_actual']; 

$mensaje = '';
$error = '';

// 2. PROCESAR ACCIONES
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $pdo->beginTransaction();

        // --- CASO A: NUEVO PRÉSTAMO ---
        if ($_POST['accion'] == 'nuevo_prestamo') {
            $miembro_id = $_POST['miembro_id'];
            $monto = floatval($_POST['monto']);
            $plazo = intval($_POST['plazo']);
            $proposito = $_POST['proposito'];

            // A.1 VALIDAR FONDOS DE CAJA
            if ($monto > $saldo_disponible_caja) {
                throw new Exception("No hay suficiente efectivo en caja. Disponible: $" . number_format($saldo_disponible_caja, 2));
            }

            // A.2 VALIDAR CAPACIDAD DE ENDEUDAMIENTO (CORREGIDO) 🛑
            // 1. Obtenemos ahorro total
            $stmt_ahorro = $pdo->prepare("SELECT saldo_ahorros FROM Miembro_Ciclo WHERE id = ?");
            $stmt_ahorro->execute([$miembro_id]);
            $ahorro_socio = $stmt_ahorro->fetchColumn() ?: 0;

            // 2. Obtenemos deuda actual (Capital pendiente de préstamos activos)
            $stmt_deuda = $pdo->prepare("
                SELECT SUM(p.monto_aprobado - IFNULL((
                    SELECT SUM(monto) FROM Transaccion_Caja 
                    WHERE prestamo_id = p.id AND tipo_movimiento = 'PAGO_PRESTAMO_CAPITAL'
                ), 0)) 
                FROM Prestamo p 
                WHERE p.miembro_ciclo_id = ? AND p.estado = 'ACTIVO'
            ");
            $stmt_deuda->execute([$miembro_id]);
            $deuda_actual = $stmt_deuda->fetchColumn() ?: 0;

            // 3. Calculamos cuánto le queda disponible
            $capacidad_disponible = $ahorro_socio - $deuda_actual;

            if ($monto > $capacidad_disponible) {
                throw new Exception(
                    "El socio ya debe $" . number_format($deuda_actual, 2) . 
                    ". Su límite disponible es $" . number_format($capacidad_disponible, 2) . 
                    " (Ahorro total: $" . number_format($ahorro_socio, 2) . ")"
                );
            }

            // Cálculos del nuevo préstamo
            $tasa = $reunion['tasa_interes_mensual'];
            $interes_fijo = $monto * ($tasa / 100);
            $vencimiento = date('Y-m-d', strtotime("+$plazo months"));

            // Insertar
            $sql_p = "INSERT INTO Prestamo (miembro_ciclo_id, reunion_solicitud_id, monto_aprobado, plazo_meses, tasa_interes, monto_interes_fijo_mensual, fecha_vencimiento, estado) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, 'ACTIVO')";
            $stmt_p = $pdo->prepare($sql_p);
            $stmt_p->execute([$miembro_id, $reunion_id, $monto, $plazo, $tasa, $interes_fijo, $vencimiento]);
            $prestamo_id = $pdo->lastInsertId();

            // Registrar Salida
            $sql_t = "INSERT INTO Transaccion_Caja (reunion_id, miembro_ciclo_id, prestamo_id, tipo_movimiento, monto, observacion) 
                      VALUES (?, ?, ?, 'DESEMBOLSO_PRESTAMO', ?, ?)";
            $pdo->prepare($sql_t)->execute([$reunion_id, $miembro_id, $prestamo_id, $monto, $proposito]);

            $saldo_disponible_caja -= $monto;
            $mensaje = "Préstamo entregado correctamente.";
        }

        // --- CASO B: PAGAR PRÉSTAMO ---
        if ($_POST['accion'] == 'pagar_prestamo') {
            $prestamo_id = $_POST['prestamo_id'];
            $pago_capital = floatval($_POST['pago_capital']);
            $pago_interes = floatval($_POST['pago_interes']);
            
            $p_info = $pdo->prepare("SELECT miembro_ciclo_id, monto_aprobado FROM Prestamo WHERE id = ?");
            $p_info->execute([$prestamo_id]);
            $datos_prestamo = $p_info->fetch();

            if ($pago_capital > 0) {
                $sql_cap = "INSERT INTO Transaccion_Caja (reunion_id, miembro_ciclo_id, prestamo_id, tipo_movimiento, monto, observacion) 
                            VALUES (?, ?, ?, 'PAGO_PRESTAMO_CAPITAL', ?, 'Abono a Capital')";
                $pdo->prepare($sql_cap)->execute([$reunion_id, $datos_prestamo['miembro_ciclo_id'], $prestamo_id, $pago_capital]);
            }

            if ($pago_interes > 0) {
                $sql_int = "INSERT INTO Transaccion_Caja (reunion_id, miembro_ciclo_id, prestamo_id, tipo_movimiento, monto, observacion) 
                            VALUES (?, ?, ?, 'PAGO_PRESTAMO_INTERES', ?, 'Pago de Interés')";
                $pdo->prepare($sql_int)->execute([$reunion_id, $datos_prestamo['miembro_ciclo_id'], $prestamo_id, $pago_interes]);
            }

            // Verificar liquidación
            $stmt_pagado = $pdo->prepare("SELECT SUM(monto) FROM Transaccion_Caja WHERE prestamo_id = ? AND tipo_movimiento = 'PAGO_PRESTAMO_CAPITAL'");
            $stmt_pagado->execute([$prestamo_id]);
            $total_pagado = $stmt_pagado->fetchColumn() ?: 0;

            $saldo_pendiente = $datos_prestamo['monto_aprobado'] - $total_pagado;

            if ($saldo_pendiente <= 0.01) {
                $pdo->prepare("UPDATE Prestamo SET estado = 'FINALIZADO' WHERE id = ?")->execute([$prestamo_id]);
                $mensaje = "Pago registrado. ¡El préstamo ha sido LIQUIDADO totalmente!";
            } else {
                $mensaje = "Pago registrado correctamente. Saldo restante: $" . number_format($saldo_pendiente, 2);
            }
        }

        $pdo->commit();

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}

// 3. CARGAR DATOS VISTA
$sql_cartera = "
    SELECT p.*, u.nombre_completo,
    (SELECT IFNULL(SUM(monto), 0) FROM Transaccion_Caja WHERE prestamo_id = p.id AND tipo_movimiento = 'PAGO_PRESTAMO_CAPITAL') as capital_pagado
    FROM Prestamo p
    JOIN Miembro_Ciclo mc ON p.miembro_ciclo_id = mc.id
    JOIN Usuario u ON mc.usuario_id = u.id
    WHERE p.estado = 'ACTIVO' AND mc.ciclo_id = ?
";
$stmt_c = $pdo->prepare($sql_cartera);
$stmt_c->execute([$reunion['ciclo_id']]);
$cartera = $stmt_c->fetchAll();

// LISTA DE MIEMBROS CON CÁLCULO DE DISPONIBILIDAD (CORREGIDO) 🟢
$stmt_m = $pdo->prepare("
    SELECT mc.id, u.nombre_completo, mc.saldo_ahorros,
    (
        SELECT SUM(p.monto_aprobado - IFNULL((SELECT SUM(monto) FROM Transaccion_Caja WHERE prestamo_id = p.id AND tipo_movimiento='PAGO_PRESTAMO_CAPITAL'), 0))
        FROM Prestamo p 
        WHERE p.miembro_ciclo_id = mc.id AND p.estado = 'ACTIVO'
    ) as deuda_actual
    FROM Miembro_Ciclo mc 
    JOIN Usuario u ON mc.usuario_id = u.id 
    WHERE mc.ciclo_id = ? 
    ORDER BY u.nombre_completo
");
$stmt_m->execute([$reunion['ciclo_id']]);
$miembros = $stmt_m->fetchAll();
?>

<div class="flex-between" style="margin-bottom: 20px;">
    <div>
        <a href="panel.php?id=<?php echo $reunion_id; ?>" class="btn btn-secondary">
            <i class='bx bx-arrow-back'></i> Volver al Panel
        </a>
        <h2 style="margin-top: 10px;">Gestión de Préstamos</h2>
    </div>
    <div style="text-align: right; background: #E8F5E9; padding: 10px 20px; border-radius: 8px; border: 1px solid #C8E6C9;">
        <small style="color: #2E7D32;">DISPONIBLE EN CAJA</small>
        <div style="font-size: 1.5rem; font-weight: bold; color: #2E7D32;">
            $<?php echo number_format($saldo_disponible_caja, 2); ?>
        </div>
    </div>
</div>

<?php if($error): ?>
    <div class="badge badge-danger" style="display:block; padding:15px; margin-bottom:20px; text-align:center;">
        <i class='bx bx-error'></i> <?php echo $error; ?>
    </div>
<?php endif; ?>

<?php if($mensaje): ?>
    <div class="badge" style="display:block; padding:15px; margin-bottom:20px; text-align:center; background:#E3F2FD; color:#1565C0; border:1px solid #BBDEFB;">
        <i class='bx bx-check-circle'></i> <?php echo $mensaje; ?>
    </div>
<?php endif; ?>

<div class="grid-2">
    
    <div class="card">
        <h3 style="color: var(--color-warning);"><i class='bx bx-money'></i> Cobros del Mes</h3>
        <p style="color: var(--text-muted); font-size:0.9rem; margin-bottom:15px;">
            Registre los abonos de capital e intereses.
        </p>

        <?php if (count($cartera) > 0): ?>
            <?php foreach($cartera as $p): ?>
                <?php 
                    $saldo_pendiente = $p['monto_aprobado'] - $p['capital_pagado']; 
                    if ($saldo_pendiente <= 0.01) continue; 
                    $cuota_capital_mensual = $p['monto_aprobado'] / $p['plazo_meses'];
                ?>
                <div style="background: #FFFDE7; border: 1px solid #FFF9C4; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                    
                    <div class="flex-between">
                        <strong><?php echo htmlspecialchars($p['nombre_completo']); ?></strong>
                        <span class="badge" style="background: #FFF; border: 1px solid #ccc;">
                            Deuda: $<?php echo number_format($saldo_pendiente, 2); ?>
                        </span>
                    </div>

                    <div style="margin: 10px 0; font-size: 0.85rem; color: #666;">
                        Interés Fijo: <strong>$<?php echo $p['monto_interes_fijo_mensual']; ?></strong>
                    </div>
                    <div style="margin: 10px 0; font-size: 0.85rem; color: #666;">
                        Capital Mensual: <strong>$<?php echo number_format($cuota_capital_mensual, 2); ?></strong>
                    </div>
                    
                    <form method="POST" style="display: flex; gap: 5px; align-items: flex-end;">
                        <input type="hidden" name="accion" value="pagar_prestamo">
                        <input type="hidden" name="prestamo_id" value="<?php echo $p['id']; ?>">
                        
                        <div style="flex: 1;">
                            <label style="font-size: 0.7rem;">Capital</label>
                            <input type="number" name="pago_capital" step="0.01" min="0" placeholder="$0.00" style="padding: 5px; width: 100%;">
                        </div>
                        <div style="flex: 1;">
                            <label style="font-size: 0.7rem;">Interés</label>
                            <input type="number" name="pago_interes" step="0.01" min="0" placeholder="$0.00" style="padding: 5px; width: 100%;" value="<?php echo $p['monto_interes_fijo_mensual']; ?>">
                        </div>
                        <button type="submit" class="btn btn-success btn-sm" style="height: 38px;">
                            PAGAR
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="text-center" style="padding: 30px; color: #999;">
                <p>No hay préstamos activos para cobrar.</p>
            </div>
        <?php endif; ?>
    </div>

    <div class="card" style="height: fit-content;">
        <h3 style="color: var(--color-success);"><i class='bx bx-plus-circle'></i> Nuevo Préstamo</h3>
        <p style="color: var(--text-muted); font-size:0.9rem; margin-bottom:15px;">
            <i class='bx bx-info-circle'></i> Regla: El préstamo (deuda total) no puede superar el ahorro del socio.
        </p>

        <form method="POST">
            <input type="hidden" name="accion" value="nuevo_prestamo">
            
            <div class="form-group">
                <label>Solicitante:</label>
                <select name="miembro_id" id="select_miembro" required onchange="actualizarLimite()">
                    <option value="" data-limit="0">-- Seleccione --</option>
                    <?php foreach($miembros as $m): 
                        // El límite real es Ahorro - Deuda Actual
                        $deuda = $m['deuda_actual'] ?: 0;
                        $disponible = $m['saldo_ahorros'] - $deuda;
                        // No permitimos negativos en la visual
                        $disponible = ($disponible < 0) ? 0 : $disponible;
                    ?>
                        <option value="<?php echo $m['id']; ?>" data-limit="<?php echo $disponible; ?>">
                            <?php echo htmlspecialchars($m['nombre_completo']); ?> (Disp: $<?php echo number_format($disponible, 2); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="info_limite" style="display:none; background: #E3F2FD; padding: 10px; border-radius: 5px; margin-bottom: 15px; font-size: 0.9rem; color: #1565C0;">
                Capacidad de endeudamiento: <strong id="texto_limite">$0.00</strong>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label>Monto ($):</label>
                    <input type="number" name="monto" id="input_monto" required placeholder="0.00">
                </div>
                <div class="form-group">
                    <label>Plazo (Meses):</label>
                    <input type="number" name="plazo" min="1" max="12" value="1" required>
                </div>
            </div>

            <div class="form-group">
                <label>Propósito / Destino:</label>
                <input type="text" name="proposito" placeholder="Ej: Compra de mercadería" required>
            </div>

            <?php if ($saldo_disponible_caja > 0): ?>
                <button type="submit" class="btn btn-primary btn-block">
                    DESEMBOLSAR EFECTIVO
                </button>
            <?php else: ?>
                <button type="button" class="btn btn-secondary btn-block" disabled>
                    SIN FONDOS EN CAJA
                </button>
            <?php endif; ?>
        </form>
    </div>

</div>

<script>
function actualizarLimite() {
    var select = document.getElementById("select_miembro");
    var selectedOption = select.options[select.selectedIndex];
    
    // Este límite ya viene calculado desde PHP (Ahorro - Deuda)
    var limiteSocio = parseFloat(selectedOption.getAttribute("data-limit")) || 0;
    
    // Límite de la caja
    var limiteCaja = <?php echo $saldo_disponible_caja; ?>;
    
    // El tope es el menor de los dos
    var limiteReal = Math.min(limiteSocio, limiteCaja);
    
    var divInfo = document.getElementById("info_limite");
    var textoLimite = document.getElementById("texto_limite");
    var inputMonto = document.getElementById("input_monto");

    if (limiteSocio >= 0) {
        divInfo.style.display = "block";
        textoLimite.innerText = "$" + limiteSocio.toFixed(2);
        
        inputMonto.max = limiteReal;
        inputMonto.placeholder = "Max: $" + limiteReal.toFixed(2);
        
        if(limiteReal <= 0) {
            inputMonto.disabled = true;
            inputMonto.placeholder = "Sin cupo";
        } else {
            inputMonto.disabled = false;
        }
    } else {
        divInfo.style.display = "none";
        inputMonto.max = 0;
    }
}
</script>

<?php require_once '../../includes/footer.php'; ?>