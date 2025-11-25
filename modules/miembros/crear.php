<?php
// modules/miembros/crear.php
require_once '../../includes/header.php';
require_once '../../config/db.php';

if (!isset($_GET['ciclo_id'])) { header("Location: ../grupos/index.php"); exit; }
$ciclo_id = $_GET['ciclo_id'];

$mensaje = '';
$tipo_mensaje = ''; // danger o success
$vista_actual = 'BUSCAR'; // Estados: BUSCAR, ENCONTRADO, FORMULARIO_NUEVO
$datos_usuario = null;
$dui_busqueda = '';

// OBTENER CARGOS
$cargos = $pdo->query("SELECT * FROM Catalogo_Cargos")->fetchAll();

// =================================================================================
// LÓGICA DE PROCESAMIENTO (POST)
// =================================================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // CASO 1: BUSCAR DUI
    if (isset($_POST['accion']) && $_POST['accion'] == 'buscar') {
        $dui_busqueda = trim($_POST['dui_search']);
        
        // Buscar si existe el usuario
        $stmt = $pdo->prepare("SELECT * FROM Usuario WHERE dui = ?");
        $stmt->execute([$dui_busqueda]);
        $usuario_encontrado = $stmt->fetch();

        if ($usuario_encontrado) {
            // Verificar si YA está en el ciclo actual
            $stmt_dup = $pdo->prepare("SELECT id FROM Miembro_Ciclo WHERE usuario_id = ? AND ciclo_id = ?");
            $stmt_dup->execute([$usuario_encontrado['id'], $ciclo_id]);
            
            if ($stmt_dup->fetch()) {
                $mensaje = "Esta persona ya está inscrita en el ciclo actual.";
                $tipo_mensaje = "danger";
                $vista_actual = 'BUSCAR';
            } else {
                $datos_usuario = $usuario_encontrado;
                $vista_actual = 'ENCONTRADO';
            }
        } else {
            $mensaje = "El DUI no está registrado. Puede crear una nueva socia.";
            $tipo_mensaje = "info";
            $vista_actual = 'FORMULARIO_NUEVO';
        }
    }

    // CASO 2: INSCRIBIR USUARIO EXISTENTE (Confirmación)
    if (isset($_POST['accion']) && $_POST['accion'] == 'inscribir_existente') {
        try {
            $usuario_id = $_POST['usuario_id'];
            $cargo_id = $_POST['cargo_id'];
            $saldo_inicial = $_POST['saldo_inicial'] ?: 0;

            // Insertar solo en Miembro_Ciclo
            $stmt_ins = $pdo->prepare("INSERT INTO Miembro_Ciclo (usuario_id, ciclo_id, cargo_id, saldo_ahorros, fecha_ingreso) VALUES (?, ?, ?, ?, CURDATE())");
            $stmt_ins->execute([$usuario_id, $ciclo_id, $cargo_id, $saldo_inicial]);
            
            echo "<script>window.location.href='index.php?ciclo_id=$ciclo_id';</script>";
            exit;
        } catch (Exception $e) {
            $mensaje = "Error: " . $e->getMessage();
            $tipo_mensaje = "danger";
        }
    }

    // CASO 3: CREAR E INSCRIBIR NUEVO (Formulario Completo)
    if (isset($_POST['accion']) && $_POST['accion'] == 'crear_nuevo') {
        try {
            $pdo->beginTransaction();

            // Datos Personales
            $nombre = trim($_POST['nombre']);
            $dui = trim($_POST['dui']);
            $telefono = trim($_POST['telefono']);
            $direccion = trim($_POST['direccion']);
            $email = trim($_POST['email']);
            $password_raw = $_POST['password'];
            
            // Datos Ciclo
            $cargo_id = $_POST['cargo_id'];
            $saldo_inicial = $_POST['saldo_inicial'] ?: 0;

            // Validar Email Duplicado
            $stmt_chk = $pdo->prepare("SELECT id FROM Usuario WHERE email = ?");
            $stmt_chk->execute([$email]);
            if ($stmt_chk->fetch()) throw new Exception("El correo electrónico ya está en uso.");

            // Insertar Usuario
            $pass_hash = password_hash($password_raw, PASSWORD_DEFAULT);
            $stmt_u = $pdo->prepare("INSERT INTO Usuario (rol_id, nombre_completo, dui, telefono, direccion, email, password, estado) VALUES (3, ?, ?, ?, ?, ?, ?, 'ACTIVO')");
            $stmt_u->execute([$nombre, $dui, $telefono, $direccion, $email, $pass_hash]);
            $uid_nuevo = $pdo->lastInsertId();

            // Insertar Miembro
            $stmt_m = $pdo->prepare("INSERT INTO Miembro_Ciclo (usuario_id, ciclo_id, cargo_id, saldo_ahorros, fecha_ingreso) VALUES (?, ?, ?, ?, CURDATE())");
            $stmt_m->execute([$uid_nuevo, $ciclo_id, $cargo_id, $saldo_inicial]);

            $pdo->commit();
            echo "<script>window.location.href='index.php?ciclo_id=$ciclo_id';</script>";
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $mensaje = "Error: " . $e->getMessage();
            $tipo_mensaje = "danger";
            $vista_actual = 'FORMULARIO_NUEVO'; // Mantenerse en el formulario
            $dui_busqueda = $_POST['dui']; // Para no perder el dato
        }
    }
}
?>

<div class="container" style="max-width: 800px; margin: 0 auto;">
    <div class="flex-between" style="margin-bottom: 20px;">
        <h2>Inscripción de Socias</h2>
        <a href="index.php?ciclo_id=<?php echo $ciclo_id; ?>" class="btn btn-secondary">
            <i class='bx bx-arrow-back'></i> Cancelar
        </a>
    </div>

    <?php if($mensaje): ?>
        <div class="badge badge-<?php echo ($tipo_mensaje=='danger')?'danger':'success'; ?>" 
             style="display:block; padding: 15px; text-align:center; margin-bottom: 20px; background-color: <?php echo ($tipo_mensaje=='info')?'#E3F2FD':''; ?>; color: <?php echo ($tipo_mensaje=='info')?'#0D47A1':''; ?>;">
            <?php echo $mensaje; ?>
        </div>
    <?php endif; ?>

    <?php if($vista_actual == 'BUSCAR'): ?>
        <div class="card" style="text-align: center; padding: 40px;">
            <i class='bx bx-search-alt' style="font-size: 4rem; color: var(--color-brand);"></i>
            <h3>Paso 1: Buscar Socia</h3>
            <p style="color: #666; margin-bottom: 20px;">Ingrese el número de DUI para verificar si la persona ya existe en el sistema.</p>
            
            <form method="POST" style="max-width: 400px; margin: 0 auto;">
                <input type="hidden" name="accion" value="buscar">
                <div class="form-group">
                    <input type="text" name="dui_search" required placeholder="Ej: 00000000-0" style="font-size: 1.2rem; text-align: center;" autofocus>
                </div>
                <button type="submit" class="btn btn-primary btn-block">
                    <i class='bx bx-search'></i> VERIFICAR DUI
                </button>
            </form>
        </div>
    <?php endif; ?>

    <?php if($vista_actual == 'ENCONTRADO'): ?>
        <div class="card" style="border-top: 5px solid var(--color-success);">
            <div style="text-align: center; margin-bottom: 20px;">
                <div style="background: #E8F5E9; width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px auto;">
                    <i class='bx bxs-user-check' style="font-size: 3rem; color: var(--color-success);"></i>
                </div>
                <h3 style="color: var(--color-success);">¡Socia Encontrada!</h3>
                <p>Esta persona ya existe en la base de datos.</p>
            </div>

            <div style="background: #f9f9f9; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                <h2 style="text-align: center; margin: 0;"><?php echo htmlspecialchars($datos_usuario['nombre_completo']); ?></h2>
                <p style="text-align: center; color: #666;">DUI: <?php echo htmlspecialchars($datos_usuario['dui']); ?></p>
            </div>

            <form method="POST">
                <input type="hidden" name="accion" value="inscribir_existente">
                <input type="hidden" name="usuario_id" value="<?php echo $datos_usuario['id']; ?>">

                <h4 style="border-bottom: 1px solid #eee; padding-bottom: 10px;">Datos para este Ciclo:</h4>
                <div class="grid-2">
                    <div class="form-group">
                        <label>Cargo:</label>
                        <select name="cargo_id" required>
                            <?php foreach($cargos as $c): ?>
                                <option value="<?php echo $c['id']; ?>" <?php echo ($c['nombre']=='Miembro')?'selected':''; ?>>
                                    <?php echo htmlspecialchars($c['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                </div>

                <div class="flex-between" style="margin-top: 20px;">
                    <a href="crear.php?ciclo_id=<?php echo $ciclo_id; ?>" class="btn btn-secondary">Buscar otro DUI</a>
                    <button type="submit" class="btn btn-success">
                        <i class='bx bx-user-plus'></i> INSCRIBIR AHORA
                    </button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <?php if($vista_actual == 'FORMULARIO_NUEVO'): ?>
        <div class="card">
            <div style="margin-bottom: 20px;">
                <h3><i class='bx bx-user-plus'></i> Registro de Nueva Socia</h3>
                <p style="color: #666;">El DUI <strong><?php echo htmlspecialchars($dui_busqueda); ?></strong> no existe. Complete el formulario para registrarla.</p>
            </div>

            <form method="POST" autocomplete="off">
                <input type="hidden" name="accion" value="crear_nuevo">

                <h4 style="color: var(--color-brand); border-bottom: 1px solid #eee; padding-bottom: 5px;">1. Información Personal</h4>
                <div class="form-group">
                    <label>Nombre Completo:</label>
                    <input type="text" name="nombre" required placeholder="Nombre Apellido">
                </div>
                <div class="grid-2">
                    <div class="form-group">
                        <label>DUI:</label>
                        <input type="text" name="dui" required value="<?php echo htmlspecialchars($dui_busqueda); ?>" readonly style="background: #eee;">
                    </div>
                    <div class="form-group">
                        <label>Teléfono:</label>
                        <input type="text" name="telefono" placeholder="0000-0000">
                    </div>
                </div>
                <div class="form-group">
                    <label>Dirección:</label>
                    <input type="text" name="direccion" placeholder="Comunidad...">
                </div>

                <h4 style="color: var(--color-warning); border-bottom: 1px solid #eee; padding-bottom: 5px; margin-top: 20px;">2. Acceso al Sistema</h4>
                <div class="grid-2">
                    <div class="form-group">
                        <label>Correo (Usuario):</label>
                        <input type="email" name="email" required placeholder="correo@ejemplo.com" autocomplete="new-password">
                    </div>
                    <div class="form-group">
                        <label>Contraseña:</label>
                        <input type="password" name="password" required placeholder="********" autocomplete="new-password">
                    </div>
                </div>

                <h4 style="color: var(--color-success); border-bottom: 1px solid #eee; padding-bottom: 5px; margin-top: 20px;">3. Datos del Ciclo</h4>
                <div class="grid-2">
                    <div class="form-group">
                        <label>Cargo:</label>
                        <select name="cargo_id" required>
                            <?php foreach($cargos as $c): ?>
                                <option value="<?php echo $c['id']; ?>" <?php echo ($c['nombre']=='Miembro')?'selected':''; ?>>
                                    <?php echo htmlspecialchars($c['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                </div>

                <br>
                <div class="flex-between">
                    <a href="crear.php?ciclo_id=<?php echo $ciclo_id; ?>" class="btn btn-secondary">Volver a buscar</a>
                    <button type="submit" class="btn btn-primary">
                        GUARDAR Y REGISTRAR
                    </button>
                </div>
            </form>
        </div>
    <?php endif; ?>

</div>

<?php require_once '../../includes/footer.php'; ?>