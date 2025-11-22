
<?php
session_start();

// Si intenta entrar aquí pero ya está logueado, lo mandamos al home
if (isset($_SESSION['user_id'])) {
    header("Location: ../dashboard/home.php");
    exit;
}

// Conexión a la base de datos
require_once '../../config/db.php';

$mensaje_error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    try {
    
        $stmt = $pdo->prepare("SELECT id, nombre_completo, password, rol_id FROM Usuario WHERE email = ? AND estado = 'ACTIVO'");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();

        // Con password_verify revisa si "admin123" coincide con el hash de la BD
        if ($usuario && password_verify($password, $usuario['password'])) {
            
            $_SESSION['user_id'] = $usuario['id'];
            $_SESSION['nombre_usuario'] = $usuario['nombre_completo'];
            $_SESSION['rol_usuario'] = $usuario['rol_id']; 
            
            header("Location: ../dashboard/home.php");
            exit;
        } else {
            $mensaje_error = "Correo o contraseña incorrectos.";
        }
    } catch (PDOException $e) {
        $mensaje_error = "Error técnico: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingreso al Sistema - GAPC</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body class="login-page">
    
    <div class="login-card">
        <div class="text-center" style="margin-bottom: 20px;">
            <h1 style="font-size: 2rem; color: var(--color-brand);">GAPC</h1>
            <p style="color: var(--text-muted);">Gestión de Ahorro Comunitario</p>
        </div>

        <?php if (!empty($mensaje_error)): ?>
            <div class="badge badge-danger" style="display:block; text-align:center; margin-bottom:20px; padding:10px;">
                <?php echo $mensaje_error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Correo Electrónico:</label>
                <input type="email" id="email" name="email" required placeholder="miembro@gapc.com" autocomplete="email">
            </div>

            <div class="form-group">
                <label for="password">Contraseña:</label>
                <input type="password" id="password" name="password" required placeholder="******">
            </div>

            <button type="submit" class="btn btn-primary btn-block">
                INGRESAR AL SISTEMA
            </button>
        </form>
        
        <div class="text-center" style="margin-top: 20px;">
            <small style="color: var(--text-muted);">¿Olvidó su contraseña? Contacte a la Promotora.</small>
        </div>
    </div>

</body>
</html>