<?php
/**
 * Configuración general del sistema.
 */

// --- Sesión segura ---
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_samesite', 'Lax');
    // Descomentar en producción con HTTPS:
    // ini_set('session.cookie_secure', 1);
    session_name('estac_sess');
    session_start();
}

date_default_timezone_set('America/Bogota');

// --- Reglas de negocio ---
define('LIMITE_MINUTOS_NORMAL', 8 * 60);   // Residente / Visitante: 8 horas
define('LIMITE_MINUTOS_MERCADO', 30);      // Mercado / Mudanza: 30 minutos
define('TARIFA_PLANA', 1.00);              // $1 fijo al superar el límite
define('TOTAL_PUESTOS', 46);
define('PUESTOS_VISITANTES', 10);

// --- Rutas base ---
// BASE_PATH: ruta física a la raíz del proyecto (carpeta que contiene /config, /modules, /public, etc.)
define('BASE_PATH', dirname(__DIR__));

/**
 * BASE_URL: ruta web (relativa al dominio) hacia la raíz del proyecto.
 * Se calcula comparando la carpeta física del proyecto con DOCUMENT_ROOT,
 * de modo que sea correcta sin importar desde qué subcarpeta (modules/, public/, api/)
 * se esté ejecutando el script actual.
 */
function calcular_base_url(): string
{
    $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'])) : '';
    $proyecto = str_replace('\\', '/', BASE_PATH);

    if ($docRoot !== '' && str_starts_with($proyecto, $docRoot)) {
        $rel = substr($proyecto, strlen($docRoot));
        return rtrim($rel, '/');
    }

    // Fallback: intenta deducirlo a partir de la URL del script actual
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    // Quita el último segmento conocido (modules/public/api) si corresponde
    foreach (['/modules', '/public', '/api'] as $sub) {
        if (str_ends_with($scriptDir, $sub)) {
            return substr($scriptDir, 0, -strlen($sub));
        }
    }
    return rtrim($scriptDir, '/');
}

define('BASE_URL', calcular_base_url());

require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/includes/csrf.php';
require_once BASE_PATH . '/includes/functions.php';
require_once BASE_PATH . '/includes/auth.php';
