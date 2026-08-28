<?php
require_once __DIR__ . '/../config/config.php';
requerir_login();
header('Content-Type: application/json; charset=utf-8');

$raw = json_decode(file_get_contents('php://input'), true) ?? [];
$post = array_merge($_POST, is_array($raw) ? $raw : []);

if (!csrf_validar($post['csrf_token'] ?? null)) {
    json_out(['ok' => false, 'error' => 'Token de seguridad inválido.'], 403);
}

$id     = (int) ($post['id'] ?? 0);
$placa  = strtoupper(limpiar($post['placa'] ?? ''));
$marca  = limpiar($post['marca'] ?? '');
$modelo = limpiar($post['modelo'] ?? '');
$color  = limpiar($post['color'] ?? '');

if ($id <= 0) json_out(['ok' => false, 'error' => 'Vehículo inválido.']);
if (!validar_placa($placa)) json_out(['ok' => false, 'error' => 'Placa inválida.']);

$pdo = getConexion();

$stmt = $pdo->prepare('SELECT id FROM vehiculos WHERE placa = ? AND id <> ? LIMIT 1');
$stmt->execute([$placa, $id]);
if ($stmt->fetch()) {
    json_out(['ok' => false, 'error' => 'Ya existe otro vehículo con esa placa.']);
}

try {
    $stmt = $pdo->prepare('UPDATE vehiculos SET placa=?, marca=?, modelo=?, color=? WHERE id=?');
    $stmt->execute([$placa, $marca ?: null, $modelo ?: null, $color ?: null, $id]);
    json_out(['ok' => true, 'mensaje' => 'Vehículo actualizado correctamente.']);
} catch (Throwable $e) {
    error_log('Error guardar vehiculo: ' . $e->getMessage());
    json_out(['ok' => false, 'error' => 'Error interno al guardar el vehículo.'], 500);
}
