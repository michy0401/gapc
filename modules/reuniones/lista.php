<?php
// modules/reuniones/lista.php
require_once '../../includes/header.php';
require_once '../../config/db.php';

if (!isset($_GET['ciclo_id'])) { header("Location: index.php"); exit; }
$ciclo_id = $_GET['ciclo_id'];

// 1. INFO DEL CICLO
$stmt_c = $pdo->prepare("SELECT c.*, g.nombre as grupo FROM Ciclo c JOIN Grupo g ON c.grupo_id = g.id WHERE c.id = ?");
$stmt_c->execute([$ciclo_id]);
$ciclo = $stmt_c->fetch();

// 2. LISTAR REUNIONES
$stmt_r = $pdo->prepare("SELECT * FROM Reunion WHERE ciclo_id = ? ORDER BY numero_reunion DESC");
$stmt_r->execute([$ciclo_id]);
$reuniones = $stmt_r->fetchAll();

// Calcular siguiente número de reunión
$siguiente_numero = count($reuniones) + 1;
?>

<div class="flex-between" style="margin-bottom: 20px;">
    <div>
        <a href="index.php" style="color: var(--text-muted); display: flex; align-items: center; gap: 5px;">
            <i class='bx bx-arrow-back'></i> Volver a Selección
        </a>
        <h2 style="margin-top: 5px;">Reuniones: <?php echo htmlspecialchars($ciclo['grupo']); ?></h2>
    </div>

    <a href="crear.php?ciclo_id=<?php echo $ciclo_id; ?>&num=<?php echo $siguiente_numero; ?>" class="btn btn-primary">
        <i class='bx bx-plus-circle'></i> Iniciar Reunión #<?php echo $siguiente_numero; ?>
    </a>
</div>

<div class="card">
    <div class="table-container">
        <?php if (count($reuniones) > 0): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Fecha</th>
                        <th>Estado</th>
                        <th>Caja Inicial</th>
                        <th>Caja Final</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($reuniones as $r): ?>
                        <tr>
                            <td style="font-weight: bold; font-size: 1.2rem;">
                                <?php echo $r['numero_reunion']; ?>
                            </td>
                            <td>
                                <?php echo date('d/m/Y', strtotime($r['fecha'])); ?>
                            </td>
                            <td>
                                <?php if($r['estado'] == 'ABIERTA'): ?>
                                    <span class="badge badge-success" style="background: #E8F5E9; color: #2E7D32; border: 1px solid #2E7D32;">EN CURSO</span>
                                <?php elseif($r['estado'] == 'CERRADA'): ?>
                                    <span class="badge" style="background: #ECEFF1; color: #546E7A;">FINALIZADA</span>
                                <?php else: ?>
                                    <span class="badge" style="background: #FFF3E0; color: #EF6C00;">PROGRAMADA</span>
                                <?php endif; ?>
                            </td>
                            <td>$<?php echo number_format($r['saldo_caja_inicial'], 2); ?></td>
                            <td style="font-weight: bold;">$<?php echo number_format($r['saldo_caja_actual'], 2); ?></td>
                            <td>
                                <?php if($r['estado'] == 'ABIERTA'): ?>
                                    <a href="panel.php?id=<?php echo $r['id']; ?>" class="btn btn-sm btn-success">
                                        CONTINUAR <i class='bx bx-right-arrow-alt'></i>
                                    </a>
                                <?php else: ?>
                                    <a href="ver_acta.php?id=<?php echo $r['id']; ?>" class="btn btn-sm btn-secondary" title="Ver Acta">
                                        <i class='bx bxs-file-doc'></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="text-center" style="padding: 40px; color: var(--text-muted);">
                <i class='bx bx-calendar-event' style="font-size: 3rem;"></i>
                <p>No se han registrado reuniones en este ciclo.</p>
                <p>Presiona el botón de arriba para comenzar la Reunión #1.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>