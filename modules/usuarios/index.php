<?php
// modules/usuarios/index.php
require_once '../../includes/header.php';
require_once '../../config/db.php';

// SEGURIDAD: Solo el ADMIN (Rol 1) puede entrar aquí
if ($_SESSION['rol_usuario'] != 1) {
    header("Location: ../dashboard/home.php");
    exit;
}

// CONSULTA GLOBAL DE USUARIOS
$sql = "SELECT u.*, r.nombre as nombre_rol 
        FROM Usuario u 
        JOIN Rol r ON u.rol_id = r.id 
        ORDER BY u.id DESC";
$usuarios = $pdo->query($sql)->fetchAll();
?>

<div class="flex-between" style="margin-bottom: 20px;">
    <div>
        <h2>Gestión de Usuarios del Sistema</h2>
        <p style="color: var(--text-muted);">Administración de roles y accesos a la plataforma.</p>
    </div>
</div>

<div class="card">
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre Completo</th>
                    <th>Usuario (Email)</th>
                    <th>DUI</th>
                    <th>Rol Actual</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($usuarios as $u): ?>
                    <tr>
                        <td><?php echo $u['id']; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($u['nombre_completo']); ?></strong>
                            <br>
                            <small style="color:#999;"><?php echo htmlspecialchars($u['telefono']); ?></small>
                        </td>
                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                        <td><?php echo htmlspecialchars($u['dui']); ?></td>
                        <td>
                            <?php 
                                $color = 'gray';
                                if($u['rol_id'] == 1) $color = 'var(--color-brand)'; // Admin
                                if($u['rol_id'] == 2) $color = '#E65100'; // Promotora
                                if($u['rol_id'] == 3) $color = 'var(--color-success)'; // Miembro
                            ?>
                            <span class="badge" style="background: transparent; border: 1px solid <?php echo $color; ?>; color: <?php echo $color; ?>;">
                                <?php echo htmlspecialchars($u['nombre_rol']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if($u['estado'] == 'ACTIVO'): ?>
                                <span style="color: var(--color-success); font-weight: bold;">Activo</span>
                            <?php else: ?>
                                <span style="color: var(--color-danger); font-weight: bold;">Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="editar.php?id=<?php echo $u['id']; ?>" class="btn btn-sm btn-secondary" title="Cambiar Rol / Editar">
                                <i class='bx bx-id-card'></i> Modificar
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>