<?php
require_once __DIR__ . '/../config/config.php';
requerir_login();
header('Content-Type: application/json; charset=utf-8');

$q = limpiar($_GET['q'] ?? '');

if ($q === '' || strlen($q) < 2) {
    json_out(['ok' => true, 'persona' => null]);
}

$pdo = getConexion();

// Buscar primero por cédula exacta, luego por placa
$stmt = $pdo->prepare('SELECT id, cedula, nombre, apellido, telefono, es_residente, torre_id, apartamento, qr_token FROM personas WHERE cedula = ? LIMIT 1');
$stmt->execute([$q]);
$persona = $stmt->fetch();

if (!$persona) {
    $stmt = $pdo->prepare(
        'SELECT p.id, p.cedula, p.nombre, p.apellido, p.telefono, p.es_residente, p.torre_id, p.apartamento, p.qr_token
         FROM vehiculos v
         JOIN personas p ON p.id = v.persona_id
         WHERE v.placa = ? LIMIT 1'
    );
    $stmt->execute([strtoupper($q)]);
    $persona = $stmt->fetch();
}

if (!$persona) {
    json_out(['ok' => true, 'persona' => null]);
}

// Vehículos de la persona
$stmt = $pdo->prepare('SELECT id, placa, marca, modelo, color FROM vehiculos WHERE persona_id = ? ORDER BY placa');
$stmt->execute([$persona['id']]);
$vehiculos = $stmt->fetchAll();

// Torre (nombre) si aplica
$torreNombre = null;
if ($persona['torre_id']) {
    $stmt = $pdo->prepare('SELECT nombre FROM torres WHERE id = ?');
    $stmt->execute([$persona['torre_id']]);
    $t = $stmt->fetch();
    $torreNombre = $t['nombre'] ?? null;
}

// ¿Tiene movimiento activo?
$stmt = $pdo->prepare("SELECT id FROM movimientos WHERE persona_id = ? AND estado = 'activo' LIMIT 1");
$stmt->execute([$persona['id']]);
$activo = $stmt->fetch();

json_out([
    'ok' => true,
    'persona' => [
        'id'            => (int) $persona['id'],
        'cedula'        => $persona['cedula'],
        'nombre'        => $persona['nombre'],
        'apellido'      => $persona['apellido'],
        'telefono'      => $persona['telefono'],
        'es_residente'  => (bool) $persona['es_residente'],
        'torre_id'      => $persona['torre_id'],
        'torre_nombre'  => $torreNombre,
        'apartamento'   => $persona['apartamento'],
        'tiene_qr'      => !empty($persona['qr_token']),
        'tiene_activo'  => (bool) $activo,
    ],
    'vehiculos' => $vehiculos,
]);
