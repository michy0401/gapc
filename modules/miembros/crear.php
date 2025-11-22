<?php
// modules/miembros/crear.php
require_once '../../includes/header.php';
require_once '../../config/db.php';

if (!isset($_GET['ciclo_id'])) {
    header("Location: ../grupos/index.php");
    exit;
}
$ciclo_id = $_GET['ciclo_id'];
$mensaje = '';

// OBTENER CARGOS DISPONIBLES
$cargos = $pdo->query("SELECT * FROM Catalogo_Cargos")->fetchAll();

// LÓGICA DE GUARDADO
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre']);
    $dui = trim($_POST['dui']);
    $telefono = trim($_POST['telefono']);
    $direccion = trim($_POST['direccion']);
    $cargo_id = $_POST['cargo_id'];
    $saldo_inicial = $_POST['saldo_inicial'] ?: 0;

    try {
        $pdo->beginTransaction();

        // 1. VERIFICAR SI LA PERSONA YA EXISTE (POR DUI)
        $stmt_check = $pdo->prepare("SELECT id FROM Usuario WHERE dui = ?");
        $stmt_check->execute([$dui]);
        $usuario_existente = $stmt_check->fetch();

        if ($usuario_existente) {
            // Si ya existe, usamos su ID
            $usuario_id = $usuario_existente['id'];
        } else {
            // Si no existe, la creamos como nueva USUARIA
            // Nota: rol_id = 3 es 'Usuario' (Miembro normal)
            $stmt_new = $pdo->prepare("INSERT INTO Usuario (rol_id, nombre_completo, dui, telefono, direccion, estado) VALUES (3, ?, ?, ?, ?, 'ACTIVO')");
            $stmt_new->execute([$nombre, $dui, $telefono, $direccion]);
            $usuario_id = $pdo->lastInsertId();
        }

        // 2. VERIFICAR QUE NO ESTÉ YA EN ESTE CICLO (Para no inscribirla doble)
        $stmt_dup = $pdo->prepare("SELECT id FROM Miembro_Ciclo WHERE usuario_id = ? AND ciclo_id = ?");
        $stmt_dup->execute([$usuario_id, $ciclo_id]);
        
        if ($stmt_dup->fetch()) {
            throw new Exception("Esta persona ya está inscrita en este ciclo.");
        }

        // 3. INSCRIBIR EN EL CICLO (Tabla Miembro_Ciclo)
        $stmt_ins = $pdo->prepare("INSERT INTO Miembro_Ciclo (usuario_id, ciclo_id, cargo_id, saldo_ahorros, fecha_ingreso) VALUES (?, ?, ?, ?, CURDATE())");
        $stmt_ins->execute([$usuario_id, $ciclo_id, $cargo_id, $saldo_inicial]);

        $pdo->commit();
        
        // Redirigir al listado
        echo "<script>window.location.href='index.php?ciclo_id=$ciclo_id';</script>";
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        $mensaje = "Error: " . $e->getMessage();
    }
}
?>

<div class="container" style="max-width: 800px; margin: 0 auto;">
    
    <div class="flex-between" style="margin-bottom: 20px;">
        <h2>Inscribir Nueva Socia</h2>
        <a href="index.php?ciclo_id=<?php echo $ciclo_id; ?>" class="btn btn-secondary">
            <i class='bx bx-arrow-back'></i> Cancelar
        </a>
        
    </div>

    <?php if($mensaje): ?>
        <div class="badge badge-danger" style="display:block; padding: 15px; text-align:center; margin-bottom: 20px;">
            <?php echo $mensaje; ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <form method="POST" action="">
            
            <h3 style="font-size: 1.1rem; color: var(--color-brand); border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 20px;">
                1. Datos Personales
            </h3>
            
            <div class="form-group">
                <label>Nombre Completo:</label>
                <input type="text" name="nombre" required placeholder="Ej: Juana María López">
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label>DUI (Documento Identidad):</label>
                    <input type="text" name="dui" required placeholder="00000000-0">
                    <small style="color: var(--text-muted);">Si el DUI ya existe, el sistema asociará a la persona automáticamente.</small>
                </div>
                <div class="form-group">
                    <label>Teléfono:</label>
                    <input type="text" name="telefono" placeholder="0000-0000">
                </div>
            </div>

            <div class="form-group">
                <label>Dirección / Comunidad:</label>
                <input type="text" name="direccion" placeholder="Ej: Cantón El Sunzal...">
            </div>

            <h3 style="font-size: 1.1rem; color: var(--color-success); border-bottom: 1px solid #eee; padding-bottom: 10px; margin: 30px 0 20px 0;">
                2. Datos para este Ciclo
            </h3>

            <div class="grid-2">
                <div class="form-group">
                    <label>Cargo en la Directiva:</label>
                    <select name="cargo_id" required>
                        <?php foreach($cargos as $c): ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo ($c['nombre']=='Miembro')?'selected':''; ?>>
                                <?php echo htmlspecialchars($c['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Ahorro Inicial:</label>
                    <div style="display: flex; align-items: center; gap: 5px;">
                        <span style="font-size: 1.2rem; font-weight: bold;" >$</span>
                        <input type="number" name="saldo_inicial" step="0.01" placeholder="0.00">
                    </div>
                </div>
            </div>

            <br>
            <button type="submit" class="btn btn-primary btn-block" style="padding: 15px; font-size: 1.1rem;">
                <i class='bx bx-save'></i> REGISTRAR INSCRIPCIÓN
            </button>

        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>