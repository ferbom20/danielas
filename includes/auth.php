<?php
/**
 * Autenticación y control de acceso.
 */

function usuario_actual(): ?array
{
    return $_SESSION['usuario'] ?? null;
}

function esta_autenticado(): bool
{
    return isset($_SESSION['usuario']);
}

function requerir_login(): void
{
    if (!esta_autenticado()) {
        $destino = BASE_URL . '/public/login.php';
        header('Location: ' . $destino);
        exit;
    }
    // Regenerar id de sesión periódicamente para mitigar fijación de sesión
    if (empty($_SESSION['ultima_regeneracion']) || (time() - $_SESSION['ultima_regeneracion']) > 900) {
        session_regenerate_id(true);
        $_SESSION['ultima_regeneracion'] = time();
    }
}

function requerir_rol(array $roles): void
{
    requerir_login();
    $u = usuario_actual();
    if (!in_array($u['rol'], $roles, true)) {
        http_response_code(403);
        die('No tiene permisos para acceder a este módulo.');
    }
}

function intentar_login(string $username, string $password): bool
{
    $pdo = getConexion();
    $stmt = $pdo->prepare('SELECT id, username, password_hash, nombre, rol, activo FROM usuarios WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $u = $stmt->fetch();

    if (!$u || !$u['activo'] || !password_verify($password, $u['password_hash'])) {
        usleep(300000); // mitiga ataques de tiempo / fuerza bruta básica
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['usuario'] = [
        'id'       => $u['id'],
        'username' => $u['username'],
        'nombre'   => $u['nombre'],
        'rol'      => $u['rol'],
    ];
    $_SESSION['ultima_regeneracion'] = time();
    return true;
}

function cerrar_sesion(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}
