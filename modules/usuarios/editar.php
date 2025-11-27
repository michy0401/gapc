<?php
// modules/usuarios/editar.php
require_once '../../includes/header.php';
require_once '../../config/db.php';

// SEGURIDAD
if ($_SESSION['rol_usuario'] != 1) { header("Location: ../dashboard/home.php"); exit; }

if (!isset($_GET['id'])) { header("Location: index.php"); exit; }
$user_id = $_GET['id'];

$mensaje = '';

// 1. PROCESAR CAMBIOS
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $dui = $_POST['dui'];
    $telefono = $_POST['telefono']; // Agregado
    $rol_id = $_POST['rol_id'];
    $estado = $_POST['estado'];
    $password_nueva = $_POST['password_nueva'];

    try {
        // Si escribieron contraseña nueva, la actualizamos. Si no, dejamos la vieja.
        if (!empty($password_nueva)) {
            $pass_hash = password_hash($password_nueva, PASSWORD_DEFAULT);
            // Agregamos telefono a la consulta
            $sql = "UPDATE Usuario SET nombre_completo=?, email=?, dui=?, telefono=?, rol_id=?, estado=?, password=? WHERE id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nombre, $email, $dui, $telefono, $rol_id, $estado, $pass_hash, $user_id]);
        } else {
            // Agregamos telefono a la consulta
            $sql = "UPDATE Usuario SET nombre_completo=?, email=?, dui=?, telefono=?, rol_id=?, estado=? WHERE id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nombre, $email, $dui, $telefono, $rol_id, $estado, $user_id]);
        }

        $mensaje = "Usuario actualizado correctamente.";

    } catch (Exception $e) {
        $mensaje = "Error: " . $e->getMessage();
    }
}

// 2. OBTENER DATOS
$stmt = $pdo->prepare("SELECT * FROM Usuario WHERE id = ?");
$stmt->execute([$user_id]);
$u = $stmt->fetch();

// 3. OBTENER ROLES
$roles = $pdo->query("SELECT * FROM Rol")->fetchAll();
?>

<div class="container" style="max-width: 700px; margin: 0 auto;">
    <div class="flex-between" style="margin-bottom: 20px;">
        <a href="index.php" class="btn btn-secondary"><i class='bx bx-arrow-back'></i> Volver</a>
        <h2>Editar Usuario #<?php echo $u['id']; ?></h2>
    </div>

    <?php if($mensaje): ?>
        <div class="badge badge-success" style="display:block; padding:15px; margin-bottom:20px; text-align:center;">
            <?php echo $mensaje; ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <form method="POST" autocomplete="off">
            
            <div class="form-group">
                <label>Nombre Completo:</label>
                <input type="text" name="nombre" value="<?php echo htmlspecialchars($u['nombre_completo']); ?>" required>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label>DUI:</label>
                    <input type="text" 
                           name="dui" 
                           value="<?php echo htmlspecialchars($u['dui']); ?>" 
                           required
                           maxlength="10"
                           oninput="validarNumeros(this, 'alert-dui')">
                    
                    <div id="alert-dui" class="floating-alert">
                        <i class='bx bx-error-circle'></i> Solo números.
                    </div>
                </div>

                <div class="form-group">
                    <label>Teléfono:</label>
                    <input type="text" 
                           name="telefono" 
                           value="<?php echo htmlspecialchars($u['telefono']); ?>"
                           maxlength="8"
                           oninput="validarNumeros(this, 'alert-tel')">

                    <div id="alert-tel" class="floating-alert">
                        <i class='bx bx-error-circle'></i> Solo números.
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>Correo (Login):</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($u['email']); ?>" required>
            </div>

            <div style="background: #E3F2FD; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #BBDEFB;">
                <h4 style="margin-top: 0; color: #1565C0;">Nivel de Acceso</h4>
                
                <div class="grid-2">
                    <div class="form-group">
                        <label>Rol del Sistema:</label>
                        <select name="rol_id" style="font-weight: bold; color: #1565C0;">
                            <?php foreach($roles as $r): ?>
                                <option value="<?php echo $r['id']; ?>" <?php echo ($r['id'] == $u['rol_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($r['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Estado:</label>
                        <select name="estado">
                            <option value="ACTIVO" <?php echo ($u['estado'] == 'ACTIVO') ? 'selected' : ''; ?>>ACTIVO</option>
                            <option value="INACTIVO" <?php echo ($u['estado'] == 'INACTIVO') ? 'selected' : ''; ?>>BLOQUEADO</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>Nueva Contraseña (Opcional):</label>
                <input type="password" name="password_nueva" placeholder="Dejar en blanco para no cambiar" autocomplete="new-password">
                <small style="color: #999;">Escriba aquí solo si desea resetear la clave del usuario.</small>
            </div>

            <button type="submit" class="btn btn-primary btn-block" style="padding: 12px;">
                <i class='bx bx-save'></i> GUARDAR CAMBIOS
            </button>

        </form>
    </div>
</div>

<style>
    .floating-alert {
        display: none;
        background: #FFEBEE;
        color: #C62828;
        padding: 5px 10px;
        border-radius: 4px;
        margin-top: 5px;
        font-size: 0.8rem;
        border: 1px solid #FFCDD2;
        animation: slideDown 0.2s ease;
    }

    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-5px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<script>
    function validarNumeros(input, idAlerta) {
        let valorOriginal = input.value;
        let valorLimpio = valorOriginal.replace(/[^0-9]/g, '');
        
        if (valorOriginal !== valorLimpio) {
            input.value = valorLimpio;
            let alerta = document.getElementById(idAlerta);
            alerta.style.display = 'block';
            setTimeout(function() {
                alerta.style.display = 'none';
            }, 2000);
        }
    }
</script>

<?php require_once '../../includes/footer.php'; ?>