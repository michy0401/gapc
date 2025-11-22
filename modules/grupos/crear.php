<?php
// modules/grupos/crear.php
require_once '../../includes/header.php';
require_once '../../config/db.php';

$mensaje = '';

// 1. LÓGICA PARA GUARDAR (Cuando se presiona el botón "Guardar")
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre']);
    $distrito_id = $_POST['distrito_id'];
    $promotora_id = $_POST['promotora_id'];
    $fecha_creacion = $_POST['fecha_creacion'];

    try {
        $sql = "INSERT INTO Grupo (nombre, distrito_id, promotora_id, fecha_creacion) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nombre, $distrito_id, $promotora_id, $fecha_creacion]);

        // Redirigir al listado con éxito
        echo "<script>window.location.href='index.php';</script>";
        exit;
    } catch (PDOException $e) {
        $mensaje = "Error al guardar: " . $e->getMessage();
    }
}

// 2. CARGAR DATOS PARA LOS DROPDOWNS (Selects)
// Obtener Distritos
$distritos = $pdo->query("SELECT id, nombre FROM Distrito")->fetchAll();

// Obtener solo usuarios que sean Promotoras (rol_id = 2)
$promotoras = $pdo->query("SELECT id, nombre_completo FROM Usuario WHERE rol_id = 2 AND estado = 'ACTIVO'")->fetchAll();
?>

<div class="container" style="max-width: 800px; margin: 0 auto;"> <div class="flex-between" style="margin-bottom: 20px;">
        <h2>Registrar Nuevo Grupo</h2>
        <a href="index.php" class="btn btn-secondary">
            <i class='bx bx-arrow-back'></i> Cancelar
        </a>
    </div>

    <div class="card">
        
        <?php if($mensaje): ?>
            <div class="badge badge-danger" style="display:block; margin-bottom: 15px; text-align: center;">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            
            <div class="form-group">
                <label for="nombre">Nombre del Grupo GAPC:</label>
                <input type="text" name="nombre" id="nombre" required placeholder="Ej: Mujeres Emprendedoras" autofocus>
            </div>

            <div class="grid-2">
                
                <div class="form-group">
                    <label for="distrito_id">Distrito / Zona:</label>
                    <select name="distrito_id" id="distrito_id" required>
                        <option value="">-- Seleccione una zona --</option>
                        <?php foreach($distritos as $d): ?>
                            <option value="<?php echo $d['id']; ?>">
                                <?php echo htmlspecialchars($d['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="promotora_id">Promotora Encargada:</label>
                    <select name="promotora_id" id="promotora_id" required>
                        <option value="">-- Seleccione responsable --</option>
                        <?php foreach($promotoras as $p): ?>
                            <option value="<?php echo $p['id']; ?>">
                                <?php echo htmlspecialchars($p['nombre_completo']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="fecha_creacion">Fecha de Fundación:</label>
                <input type="date" name="fecha_creacion" id="fecha_creacion" required value="<?php echo date('Y-m-d'); ?>">
            </div>

            <br>
            
            <button type="submit" class="btn btn-primary btn-block">
                <i class='bx bx-save'></i> GUARDAR GRUPO
            </button>

        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>