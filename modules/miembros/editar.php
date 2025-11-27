<?php
require_once '../../includes/header.php';
require_once '../../config/db.php';

if (!isset($_GET['id'])) { header("Location: index.php"); exit; }
$miembro_ciclo_id = $_GET['id'];

$mensaje = '';

// 1. PROCESAR GUARDADO
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = $_POST['nombre'];
    $dui = $_POST['dui'];
    $telefono = $_POST['telefono'];
    $cargo_id = $_POST['cargo_id'];
    $usuario_id = $_POST['usuario_id']; // ID oculto

    try {
        $pdo->beginTransaction();
        
        // A. Actualizar datos personales (Tabla Usuario)
        $sql_u = "UPDATE Usuario SET nombre_completo = ?, dui = ?, telefono = ? WHERE id = ?";
        $stmt_u = $pdo->prepare($sql_u);
        $stmt_u->execute([$nombre, $dui, $telefono, $usuario_id]);

        // B. Actualizar Cargo (Tabla Miembro_Ciclo)
        $sql_m = "UPDATE Miembro_Ciclo SET cargo_id = ? WHERE id = ?";
        $stmt_m = $pdo->prepare($sql_m);
        $stmt_m->execute([$cargo_id, $miembro_ciclo_id]);

        $pdo->commit();
        $mensaje = "Datos actualizados correctamente.";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $mensaje = "Error: " . $e->getMessage();
    }
}

// 2. OBTENER DATOS ACTUALES
$sql = "SELECT mc.*, u.nombre_completo, u.dui, u.telefono, u.direccion, u.id as uid
        FROM Miembro_Ciclo mc
        JOIN Usuario u ON mc.usuario_id = u.id
        WHERE mc.id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$miembro_ciclo_id]);
$miembro = $stmt->fetch();

// 3. OBTENER CARGOS
$cargos = $pdo->query("SELECT * FROM Catalogo_Cargos")->fetchAll();
?>

<div class="container" style="max-width: 700px; margin: 0 auto;">
    <div class="flex-between">
        <a href="javascript:history.back()" class="btn btn-secondary"><i class='bx bx-arrow-back'></i> Volver</a>
        <h2>Editar Miembro</h2>
    </div>

    <?php if($mensaje): ?>
        <div class="badge badge-success" style="display:block; padding:15px; margin:15px 0; text-align:center;">
            <?php echo $mensaje; ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <form method="POST">
            <input type="hidden" name="usuario_id" value="<?php echo $miembro['uid']; ?>">
            
            <div class="form-group">
                <label>Nombre Completo:</label>
                <input type="text" name="nombre" value="<?php echo htmlspecialchars($miembro['nombre_completo']); ?>" required>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label>DUI:</label>
                    <input type="text" 
                           name="dui" 
                           value="<?php echo htmlspecialchars($miembro['dui']); ?>" 
                           required
                           maxlength="10"
                           oninput="validarNumeros(this, 'alert-dui')">
                    
                    <div id="alert-dui" class="floating-alert">
                        <i class='bx bx-error-circle'></i> Solo se aceptan números.
                    </div>
                </div>

                <div class="form-group">
                    <label>Teléfono:</label>
                    <input type="text" 
                           name="telefono" 
                           value="<?php echo htmlspecialchars($miembro['telefono']); ?>"
                           maxlength="8"
                           oninput="validarNumeros(this, 'alert-tel')">
                    
                    <div id="alert-tel" class="floating-alert">
                        <i class='bx bx-error-circle'></i> Solo se aceptan números.
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>Cargo en este Ciclo:</label>
                <select name="cargo_id">
                    <?php foreach($cargos as $c): ?>
                        <option value="<?php echo $c['id']; ?>" <?php echo ($c['id'] == $miembro['cargo_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($c['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn btn-primary btn-block">
                <i class='bx bx-save'></i> Guardar Cambios
            </button>
        </form>
    </div>
</div>

<style>
    .floating-alert {
        display: none; /* Oculto por defecto */
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
        // 1. Guardamos el valor original
        let valorOriginal = input.value;
        
        // 2. Limpiamos todo lo que NO sea número
        let valorLimpio = valorOriginal.replace(/[^0-9]/g, '');
        
        // 3. Si son diferentes, es porque escribió una letra o símbolo
        if (valorOriginal !== valorLimpio) {
            // Reemplazamos el valor del input
            input.value = valorLimpio;
            
            // Mostramos la alerta visual
            let alerta = document.getElementById(idAlerta);
            alerta.style.display = 'block';
            
            // La ocultamos después de 2.5 segundos
            setTimeout(function() {
                alerta.style.display = 'none';
            }, 2500);
        }
    }
</script>

<?php require_once '../../includes/footer.php'; ?>