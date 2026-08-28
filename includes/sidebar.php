<?php
$u = usuario_actual();
$actual = basename($_SERVER['SCRIPT_NAME']);

function nav_active(string $file, string $actual): string
{
    return $file === $actual ? 'active' : '';
}
?>
<aside class="sidebar">
    <div class="brand">
        <div class="logo"><img src="<?= BASE_URL ?>/assets/img/logo.png" alt="Logo" style="width:45px;border-radius:5px"></div>
        <div>
            <h1>Las Danielas</h1>
            <span>Residencial · 6 torres</span>
        </div>
    </div>

    <nav>
        <a class="nav-link <?= nav_active('dashboard.php', $actual) ?>" href="<?= BASE_URL ?>/modules/dashboard.php">
            <span class="ic">📊</span> Dashboard
        </a>
        <a class="nav-link <?= nav_active('garita.php', $actual) ?>" href="<?= BASE_URL ?>/modules/garita.php">
            <span class="ic">🚗</span> Garita / Entrada
        </a>
        <a class="nav-link <?= nav_active('salidas.php', $actual) ?>" href="<?= BASE_URL ?>/modules/salidas.php">
            <span class="ic">🚪</span> Salidas
        </a>
        <a class="nav-link <?= nav_active('puestos.php', $actual) ?>" href="<?= BASE_URL ?>/modules/puestos.php">
            <span class="ic">🅿️</span> Puestos
        </a>

        <div class="nav-group-title">Administración</div>
        <a class="nav-link <?= nav_active('personas.php', $actual) ?>" href="<?= BASE_URL ?>/modules/personas.php">
            <span class="ic">👤</span> Personas
        </a>
        <a class="nav-link <?= nav_active('vehiculos.php', $actual) ?>" href="<?= BASE_URL ?>/modules/vehiculos.php">
            <span class="ic">🚙</span> Vehículos
        </a>
        <a class="nav-link <?= nav_active('historial.php', $actual) ?>" href="<?= BASE_URL ?>/modules/historial.php">
            <span class="ic">📜</span> Historial / Reportes
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="user-chip">
            <div class="avatar"><?= strtoupper(substr($u['nombre'], 0, 1)) ?></div>
            <div>
                <div style="font-weight:600;"><?= htmlspecialchars($u['nombre']) ?></div>
                <div class="text-dim" style="font-size:11px;"><?= htmlspecialchars(ucfirst($u['rol'])) ?></div>
            </div>
        </div>
        <a class="logout-link" href="<?= BASE_URL ?>/public/logout.php">⎋ Cerrar sesión</a>
    </div>
</aside>
<main class="main">
