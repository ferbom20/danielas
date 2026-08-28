<?php
require_once __DIR__ . '/../config/config.php';

if (esta_autenticado()) {
    redirigir(BASE_URL . '/modules/dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validar($_POST['csrf_token'] ?? null)) {
        $error = 'Sesión expirada, intente nuevamente.';
    } else {
        $username = limpiar($_POST['username'] ?? '');
        $password = (string) ($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            $error = 'Ingrese usuario y contraseña.';
        } elseif (intentar_login($username, $password)) {
            redirigir(BASE_URL . '/modules/dashboard.php');
        } else {
            $error = 'Usuario o contraseña incorrectos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Iniciar sesión · Residencias Las Danielas</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div id="toast-container"></div>
<div class="login-page">
    <div class="login-card">
        <div class="logo"><img src="<?= BASE_URL ?>/assets/img/logo.png" alt="Logo" style="width:75px;border-radius:5px"></div>
        <h1>Residencias Las Danielas</h1>
        <p class="sub">Ingrese sus credenciales</p>

        <?php if ($error): ?>
            <div class="login-error">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">
            <?= csrf_field() ?>
            <div class="form-group" style="text-align:left;">
                <label>Usuario</label>
                <input type="text" name="username" placeholder="admin" required autofocus>
            </div>
            <div class="form-group" style="text-align:left;">
                <label>Contraseña</label>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Ingresar</button>
        </form>
        <p class="text-dim" style="margin-top:20px; font-size:11.5px;">
            Usuarios demo: <b>admin</b> / Admin123! · <b>garita</b> / Garita123!
        </p>
    </div>
</div>
</body>
</html>
