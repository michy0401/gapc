<?php
// modules/prestamos/ver.php
require_once '../../includes/header.php';
require_once '../../config/db.php';

if (!isset($_GET['id'])) { header("Location: index.php"); exit; }
$prestamo_id = $_GET['id'];

// 1. INFORMACIÓN DEL PRÉSTAMO
$sql = "SELECT p.*, u.nombre_completo, u.dui, g.nombre as grupo, c.nombre as ciclo
        FROM Prestamo p
        JOIN Miembro_Ciclo mc ON p.miembro_ciclo_id = mc.id
        JOIN Usuario u ON mc.usuario_id = u.id
        JOIN Ciclo c ON mc.ciclo_id = c.id
        JOIN Grupo g ON c.grupo_id = g.id
        WHERE p.id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$prestamo_id]);
$p = $stmt->fetch();

if (!$p) { echo "Préstamo no encontrado"; exit; }

// 2. HISTORIAL DE PAGOS
$sql_hist = "SELECT t.*, r.fecha as fecha_pago, r.numero_reunion
             FROM Transaccion_Caja t
             JOIN Reunion r ON t.reunion_id = r.id
             WHERE t.prestamo_id = ?
             ORDER BY t.id ASC"; // Orden cronológico
$stmt_h = $pdo->prepare($sql_hist);
$stmt_h->execute([$prestamo_id]);
$movimientos = $stmt_h->fetchAll();

// 3. CÁLCULOS TOTALES
$capital_pagado = 0;
$interes_pagado = 0;

foreach($movimientos as $mov) {
    if ($mov['tipo_movimiento'] == 'PAGO_PRESTAMO_CAPITAL') $capital_pagado += $mov['monto'];
    if ($mov['tipo_movimiento'] == 'PAGO_PRESTAMO_INTERES') $interes_pagado += $mov['monto'];
}

// A. Capital Pendiente
$saldo_capital = $p['monto_aprobado'] - $capital_pagado;

// B. Interés Pendiente (Total esperado - Lo que ya pagó)
// Nota: Si ya pagó todo el interés o más, ponemos 0
$total_interes_esperado = $p['monto_interes_fijo_mensual'] * $p['plazo_meses'];
$saldo_interes = max(0, $total_interes_esperado - $interes_pagado);

// C. Deuda Total (Lo que tiene que sacar del bolsillo para quedar a cero hoy)
// Si el estado es FINALIZADO, forzamos a 0 por si acaso hay decimales sueltos
if ($p['estado'] == 'FINALIZADO' || $saldo_capital <= 0.01) {
    $deuda_total = 0;
    $saldo_capital = 0;
    $saldo_interes = 0;
} else {
    $deuda_total = $saldo_capital + $saldo_interes;
}

$progreso = ($capital_pagado / $p['monto_aprobado']) * 100;
?>

<div class="flex-between" style="margin-bottom: 20px;">
    <a href="index.php" class="btn btn-secondary">
        <i class='bx bx-arrow-back'></i> Volver a Cartera
    </a>
    
    <a href="estado_cuenta.php?id=<?php echo $prestamo_id; ?>" class="btn btn-primary">
        <i class='bx bx-file-blank'></i> VER ESTADO DE CUENTA OFICIAL
    </a>
</div>

<div class="card documento-impresion">
    
    <div style="border-bottom: 2px solid #eee; padding-bottom: 20px; margin-bottom: 20px;">
        <div class="flex-between">
            <div>
                <h2 style="margin:0; color: var(--color-brand);">Préstamo #<?php echo str_pad($p['id'], 4, '0', STR_PAD_LEFT); ?></h2>
                <p style="color: var(--text-muted); margin: 5px 0;">
                    <?php echo htmlspecialchars($p['grupo']); ?> - <?php echo htmlspecialchars($p['ciclo']); ?>
                </p>
            </div>
            <div style="text-align: right;">
                <?php if($p['estado'] == 'ACTIVO'): ?>
                    <span class="badge" style="background:#E3F2FD; color:#1565C0; font-size: 1rem;">VIGENTE</span>
                <?php elseif($p['estado'] == 'FINALIZADO'): ?>
                    <span class="badge" style="background:#E8F5E9; color:#2E7D32; font-size: 1rem;">PAGADO</span>
                <?php else: ?>
                    <span class="badge badge-danger" style="font-size: 1rem;">MORA</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="grid-2">
        <div>
            <h3 style="font-size: 1.1rem; color: var(--color-brand);">Datos del Crédito</h3>
            <table class="table" style="margin-top: 10px;">
                <tr>
                    <td style="background: #f9f9f9;"><strong>Socia:</strong></td>
                    <td><?php echo htmlspecialchars($p['nombre_completo']); ?></td>
                </tr>
                <tr>
                    <td style="background: #f9f9f9;"><strong>Monto Original:</strong></td>
                    <td style="font-weight: bold;">$<?php echo number_format($p['monto_aprobado'], 2); ?></td>
                </tr>
                <tr>
                    <td style="background: #f9f9f9;"><strong>Tasa Interés:</strong></td>
                    <td><?php echo $p['tasa_interes']; ?>% Mensual</td>
                </tr>
                <tr>
                    <td style="background: #f9f9f9;"><strong>Vencimiento:</strong></td>
                    <td><?php echo date('d/m/Y', strtotime($p['fecha_vencimiento'])); ?></td>
                </tr>
            </table>
        </div>

        <div>
            <h3 style="font-size: 1.1rem; color: var(--color-brand);">Estado Actual</h3>
            
            <div style="background: #F5F7FA; padding: 20px; border-radius: 12px; text-align: center;">
                
                <small style="text-transform: uppercase; color: #666; font-weight: bold;">Deuda Total (Capital + Interés)</small>
                <div style="font-size: 3rem; font-weight: bold; color: <?php echo $deuda_total > 0 ? 'var(--color-danger)' : 'var(--color-success)'; ?>;">
                    $<?php echo number_format($deuda_total, 2); ?>
                </div>
                
                <div style="margin-top: 5px; font-size: 0.9rem; color: #555;">
                    (Capital: <strong>$<?php echo number_format($saldo_capital, 2); ?></strong> + 
                     Interés: <strong>$<?php echo number_format($saldo_interes, 2); ?></strong>)
                </div>

                <div style="margin: 15px 0; border-top: 1px solid #ddd; padding: 10px 0;">
                    <div class="flex-between" style="font-size: 0.85rem;">
                        <span>Interés Pagado:</span>
                        <strong style="color: var(--color-warning);">$<?php echo number_format($interes_pagado, 2); ?></strong>
                    </div>
                </div>
                
                <div>
                    <small>Progreso Capital</small>
                    <div style="width: 100%; background: #ddd; height: 8px; border-radius: 5px; margin-top: 5px;">
                        <div style="width: <?php echo $progreso; ?>%; background: var(--color-success); height: 8px; border-radius: 5px;"></div>
                    </div>
                    <small style="display:block; margin-top: 2px; font-size: 0.8rem;"><?php echo number_format($progreso, 0); ?>% Pagado</small>
                </div>
            </div>
        </div>
    </div>

    <br>

    <h3 style="font-size: 1.1rem; color: var(--color-brand); border-bottom: 1px solid #eee; padding-bottom: 10px;">
        <i class='bx bx-history'></i> Historial de Transacciones
    </h3>
    
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Reunión</th>
                    <th>Descripción</th>
                    <th style="text-align: right;">Interés Pagado</th>
                    <th style="text-align: right;">Capital Abonado</th>
                    <th style="text-align: right;">Saldo Capital</th>
                </tr>
            </thead>
            <tbody>
                <tr style="background-color: #FFFDE7;">
                    <td>--</td>
                    <td>--</td>
                    <td><strong>Desembolso Inicial</strong></td>
                    <td class="text-right">-</td>
                    <td class="text-right">-</td>
                    <td class="text-right" style="font-weight: bold;">$<?php echo number_format($p['monto_aprobado'], 2); ?></td>
                </tr>

                <?php 
                $saldo_temp = $p['monto_aprobado'];
                foreach($movimientos as $m): 
                    // Si es abono a capital, restamos del saldo temporal para mostrar la historia
                    if ($m['tipo_movimiento'] == 'PAGO_PRESTAMO_CAPITAL') {
                        $saldo_temp -= $m['monto'];
                    }
                    
                    // Ignoramos el desembolso en la lista porque ya lo pusimos arriba manualmente
                    if ($m['tipo_movimiento'] == 'DESEMBOLSO_PRESTAMO') continue;
                ?>
                    <tr>
                        <td><?php echo date('d/m/Y', strtotime($m['fecha_pago'])); ?></td>
                        <td>#<?php echo $m['numero_reunion']; ?></td>
                        <td><?php echo htmlspecialchars($m['observacion']); ?></td>
                        
                        <td class="text-right" style="color: #F57F17;">
                            <?php echo ($m['tipo_movimiento'] == 'PAGO_PRESTAMO_INTERES') ? '$'.number_format($m['monto'], 2) : '-'; ?>
                        </td>

                        <td class="text-right" style="color: #2E7D32; font-weight: bold;">
                            <?php echo ($m['tipo_movimiento'] == 'PAGO_PRESTAMO_CAPITAL') ? '$'.number_format($m['monto'], 2) : '-'; ?>
                        </td>

                        <td class="text-right">
                            $<?php echo number_format($saldo_temp, 2); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                
                <?php if(count($movimientos) == 1): // Solo está el desembolso ?>
                    <tr>
                        <td colspan="6" class="text-center" style="padding: 20px; color: #999;">
                            No se han realizado abonos a este préstamo aún.
                        </td>
                    </tr>
                <?php endif; ?>

            </tbody>
            <tfoot>
                <tr style="background: #f0f0f0; font-weight: bold;">
                    <td colspan="3" class="text-right">TOTALES PAGADOS:</td>
                    <td class="text-right" style="color: #F57F17;">$<?php echo number_format($interes_pagado, 2); ?></td>
                    <td class="text-right" style="color: #2E7D32;">$<?php echo number_format($capital_pagado, 2); ?></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>

</div>

<style>
    .text-right { text-align: right; }

    /* ESTILOS PARA PANTALLA (MONITOR) */
    .documento-impresion {
        background: white; 
        padding: 30px; 
        /* CAMBIO: Quitamos el max-width fijo para que use el 100% disponible del contenedor padre */
        width: 100%; 
        margin: 0 auto;
        box-shadow: 0 0 10px rgba(0,0,0,0.05);
        /* Opcional: Si quieres que tenga un límite pero más ancho, usa max-width: 1200px; */
    }

    /* ESTILOS PARA IMPRESORA (PAPEL) */
    @media print {
        .print-hide, .sidebar, .topbar { display: none !important; }
        
        .main-content { 
            margin: 0; 
            width: 100%; 
            padding: 0; 
        }
        
        .card { 
            box-shadow: none; 
            border: none; 
            padding: 0;
        }
        
        /* AQUÍ SÍ LIMITAMOS EL ANCHO PARA QUE SALGA COMO HOJA CARTA */
        .documento-impresion {
            max-width: 100%; /* O un ancho fijo si prefieres */
            padding: 20px;
        }

        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    }
</style>

<?php require_once '../../includes/footer.php'; ?>