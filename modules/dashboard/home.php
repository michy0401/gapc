<?php require_once '../../includes/header.php'; ?>

<h2 style="color: var(--color-brand); margin-bottom: 20px;">Panel de Control</h2>

<div class="grid-dashboard">
    
    <a href="../grupos/index.php" class="card-compact" style="border-left-color: var(--color-brand);">
        <div class="card-icon bg-blue">
            <i class='bx bxs-group'></i>
        </div>
        <div class="card-info">
            <h3>Grupos</h3>
            <p>Gestionar mis grupos</p>
        </div>
    </a>

    <a href="../reuniones/index.php" class="card-compact" style="border-left-color: var(--color-success);">
        <div class="card-icon bg-green">
            <i class='bx bxs-calendar-check'></i>
        </div>
        <div class="card-info">
            <h3>Reuniones</h3>
            <p>Registrar asistencia y caja</p>
        </div>
    </a>

    <a href="../prestamos/index.php" class="card-compact" style="border-left-color: var(--color-warning);">
        <div class="card-icon bg-orange">
            <i class='bx bxs-bank'></i>
        </div>
        <div class="card-info">
            <h3>Préstamos</h3>
            <p>Solicitudes y pagos</p>
        </div>
    </a>

    <a href="../reportes/index.php" class="card-compact" style="border-left-color: var(--color-danger);">
        <div class="card-icon bg-red">
            <i class='bx bxs-file-pdf'></i>
        </div>
        <div class="card-info">
            <h3>Reportes</h3>
            <p>Ver actas y cierres</p>
        </div>
    </a>

</div>

<?php require_once '../../includes/footer.php'; ?>