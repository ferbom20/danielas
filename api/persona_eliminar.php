<?php
require_once __DIR__ . '/../config/config.php';
requerir_rol(['admin']);
header('Content-Type: application/json; charset=utf-8');

$raw = json_decode(file_get_contents('php://input'), true) ?? [];
$post = array_merge($_POST, is_array($raw) ? $raw : []);

if (!csrf_validar($post['csrf_token'] ?? null)) {
    json_out(['ok' => false, 'error' => 'Token de seguridad inválido.'], 403);
}

$id = (int) ($post['id'] ?? 0);
if ($id <= 0) json_out(['ok' => false, 'error' => 'ID inválido.']);

$pdo = getConexion();

$stmt = $pdo->prepare("SELECT id FROM movimientos WHERE persona_id = ? AND estado = 'activo' LIMIT 1");
$stmt->execute([$id]);
if ($stmt->fetch()) {
    json_out(['ok' => false, 'error' => 'No se puede eliminar: la persona tiene una estadía activa.']);
}

try {
    $stmt = $pdo->prepare('DELETE FROM personas WHERE id = ?');
    $stmt->execute([$id]);
    json_out(['ok' => true, 'mensaje' => 'Persona eliminada correctamente.']);
} catch (Throwable $e) {
    error_log('Error eliminar persona: ' . $e->getMessage());
    json_out(['ok' => false, 'error' => 'No se pudo eliminar. Verifique que no tenga vehículos o historial asociado.'], 500);
}
