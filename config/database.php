<?php
/**
 * Conexión a la base de datos usando PDO + consultas preparadas.
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'estacionamiento_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

function getConexion(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $opciones = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $opciones);
        } catch (PDOException $e) {
            error_log('Error de conexion BD: ' . $e->getMessage());
            http_response_code(500);
            die('Error interno del servidor. Intente más tarde.');
        }
    }

    return $pdo;
}
