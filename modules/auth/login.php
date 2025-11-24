<?php
session_start();

// Si intenta entrar aquí pero ya está logueado, lo mandamos al home
if (isset($_SESSION['user_id'])) {
    header("Location: ../dashboard/home.php");
    exit;
}

require_once '../../config/db.php';

$mensaje_error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    try {
        $stmt = $pdo->prepare("SELECT id, nombre_completo, password, rol_id FROM Usuario WHERE email = ? AND estado = 'ACTIVO'");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($password, $usuario['password'])) {
            $_SESSION['user_id'] = $usuario['id'];
            $_SESSION['nombre_usuario'] = $usuario['nombre_completo'];
            $_SESSION['rol_usuario'] = $usuario['rol_id']; 
            
            header("Location: ../dashboard/home.php");
            exit;
        } else {
            $mensaje_error = "Credenciales incorrectas.";
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
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    
    <style>
        /* ESTILOS DIRECTOS PARA GARANTIZAR EL CENTRADO */
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', sans-serif;
            background-color: color-brand-dark; /* Azul Índigo */
            background-image: linear-gradient(135deg, #121858 0%, #121858 100%);
            height: 100vh; /* Altura completa de la ventana */
            display: flex;
            align-items: center;     /* Centrar verticalmente */
            justify-content: center; /* Centrar horizontalmente */
        }

        .login-card {
            background: white;
            padding: 40px;
            width: 90%;
            max-width: 400px;
            border-radius: 16px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3);
            text-align: center;
        }

        .login-header h1 {
            color: #1A237E;
            font-size: 2rem;
            margin: 0;
            font-weight: 800;
        }

        .login-header p {
            color: #666;
            margin-top: 5px;
            font-size: 0.95rem;
        }

        /* Inputs */
        .form-group { margin-bottom: 20px; text-align: left; }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
            font-size: 0.9rem;
        }

        .input-wrapper { position: relative; }
        
        .input-wrapper i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            font-size: 1.2rem;
        }

        input {
            width: 100%;
            padding: 12px 15px 12px 45px; /* Espacio para el icono */
            font-size: 1rem;
            border: 2px solid #e1e4e8;
            border-radius: 8px;
            background-color: #F5F7FA;
            box-sizing: border-box; /* Vital para que no se salga */
            transition: all 0.3s;
        }

        input:focus {
            outline: none;
            border-color: #1A237E;
            background-color: #fff;
            box-shadow: 0 0 0 4px rgba(26, 35, 126, 0.1);
        }

        /* Botón */
        .btn-login {
            width: 100%;
            padding: 14px;
            background-color: #1A237E;
            color: white;
            border: none;
            border-radius: 30px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
        }

        .btn-login:hover {
            background-color: #0D47A1;
            transform: translateY(-2px);
        }

        .alert {
            background: #FFEBEE;
            color: #D32F2F;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            border: 1px solid #FFCDD2;
        }
    </style>
</head>
<body>
    
    <div class="login-card">
        
        <div class="login-header">
            <div style="background: #E8EAF6; width: 70px; height: 70px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px auto;">
                <i class='bx bxs-bank' style="font-size: 2.5rem; color: #1A237E;"></i>
            </div>
            <h1>GAPC</h1>
            <p>Gestión de Ahorro Comunitario</p>
        </div>

        <?php if (!empty($mensaje_error)): ?>
            <div class="alert">
                <i class='bx bx-error-circle'></i> <?php echo $mensaje_error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Correo Electrónico</label>
                <div class="input-wrapper">
                    <i class='bx bx-envelope'></i>
                    <input type="email" id="email" name="email" required placeholder="ejemplo@correo.com">
                </div>
            </div>

            <div class="form-group">
                <label for="password">Contraseña</label>
                <div class="input-wrapper">
                    <i class='bx bx-lock-alt'></i>
                    <input type="password" id="password" name="password" required placeholder="••••••••">
                </div>
            </div>

            <button type="submit" class="btn-login">
                INGRESAR
            </button>
        </form>
        
        <div style="margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px;">
            <small style="color: #999;">¿Problemas de acceso? <br> Contacte a su Promotor@</small>
        </div>
    </div>

</body>
</html>