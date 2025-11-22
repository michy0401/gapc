<?php
// modules/miembros/index.php
require_once '../../includes/header.php';
require_once '../../config/db.php';

// 1. VALIDAR QUE RECIBIMOS UN ID DE CICLO
if (!isset($_GET['ciclo_id'])) {
    // Si no hay ID, regresamos al inicio
    header("Location: ../dashboard/home.php");
    exit;
}
$ciclo_id = $_GET['ciclo_id'];

// 2. OBTENER DATOS DEL CICLO Y GRUPO (Para el título)
$stmt_c = $pdo->prepare("
    SELECT c.nombre as ciclo, g.nombre as grupo, g.id as grupo_id 
    FROM Ciclo c 
    JOIN Grupo g ON c.grupo_id = g.id 
    WHERE c.id = ?");
$stmt_c->execute([$ciclo_id]);
$datos_ciclo = $stmt_c->fetch();

// 3. OBTENER LISTA DE MIEMBROS
// Hacemos JOIN con Usuario (para el nombre) y Catalogo_Cargos (para el rol)
$sql = "SELECT mc.*, u.nombre_completo, u.dui, u.telefono, cc.nombre as cargo
        FROM Miembro_Ciclo mc
        JOIN Usuario u ON mc.usuario_id = u.id
        JOIN Catalogo_Cargos cc ON mc.cargo_id = cc.id
        WHERE mc.ciclo_id = ?
        ORDER BY u.nombre_completo ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$ciclo_id]);
$miembros = $stmt->fetchAll();
?>

<div class="flex-between" style="margin-bottom: 20px;">
    <div>
        <a href="../grupos/ver.php?id=<?php echo $datos_ciclo['grupo_id']; ?>" style="color: var(--text-muted); display: flex; align-items: center; gap: 5px;">
            <i class='bx bx-arrow-back'></i> Volver al Grupo
        </a>
        <h2 style="margin-top: 5px;">Gestión de Miembros</h2>
        <p style="color: var(--color-brand);">
            <?php echo htmlspecialchars($datos_ciclo['grupo']); ?> - <strong><?php echo htmlspecialchars($datos_ciclo['ciclo']); ?></strong>
        </p>
    </div>

    <a href="crear.php?ciclo_id=<?php echo $ciclo_id; ?>" class="btn btn-primary">
        <i class='bx bx-user-plus'></i> Inscribir Socia
    </a>
</div>

<div class="card">
    <div class="table-container">
        <?php if (count($miembros) > 0): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Nombre Completo</th>
                        <th>Cargo / Rol</th>
                        <th>DUI / Identidad</th>
                        <th>Teléfono</th>
                        <th>Ahorro Base</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($miembros as $m): ?>
                        <tr>
                            <td style="font-weight: bold;">
                                <i class='bx bx-user-circle' style="color: #ccc;"></i>
                                <?php echo htmlspecialchars($m['nombre_completo']); ?>
                            </td>
                            <td>
                                <?php 
                                    // Definimos el estilo según el cargo
                                    $bg_color = '#f5f5f5'; // Gris por defecto (Miembro)
                                    $text_color = '#666666';
                                    
                                    switch($m['cargo']) {
                                        case 'Presidenta':
                                            $bg_color = '#E8F5E9'; $text_color = '#2E7D32'; // Verde
                                            break;
                                        case 'Tesorera':
                                            $bg_color = '#E3F2FD'; $text_color = '#1565C0'; // Azul
                                            break;
                                        case 'Secretaria':
                                            $bg_color = '#FFF3E0'; $text_color = '#EF6C00'; // Naranja
                                            break;
                                        case 'Responsable de Llave':
                                            $bg_color = '#F3E5F5'; $text_color = '#7B1FA2'; // Morado
                                            break;
                                        default:
                                            // Los miembros normales se quedan con el gris suave
                                            $bg_color = '#FAFAFA'; $text_color = '#616161';
                                            break;
                                    }
                                ?>
                                
                                <span class="badge" style="background: <?php echo $bg_color; ?>; color: <?php echo $text_color; ?>; border: 1px solid <?php echo $text_color; ?>20;">
                                    <?php echo htmlspecialchars($m['cargo']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($m['dui']); ?></td>
                            <td><?php echo htmlspecialchars($m['telefono']); ?></td>
                            <td style="color: var(--color-success); font-weight: bold;">
                                $<?php echo number_format($m['saldo_ahorros'], 2); ?>
                            </td>
                            <td>
                                <a href="#" class="btn btn-sm btn-secondary" title="Editar Datos">
                                    <i class='bx bx-pencil'></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="text-center" style="padding: 40px; color: var(--text-muted);">
                <i class='bx bx-group' style="font-size: 3rem;"></i>
                <p>Aún no hay socias inscritas en este ciclo.</p>
                <p>Presiona "Inscribir Socia" para comenzar.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>