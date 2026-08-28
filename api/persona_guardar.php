<?php
require_once __DIR__ . '/../config/config.php';
requerir_login();
header('Content-Type: application/json; charset=utf-8');

$raw = json_decode(file_get_contents('php://input'), true) ?? [];
$post = array_merge($_POST, is_array($raw) ? $raw : []);

if (!csrf_validar($post['csrf_token'] ?? null)) {
    json_out(['ok' => false, 'error' => 'Token de seguridad inválido.'], 403);
}

$id        = (int) ($post['id'] ?? 0);
$cedula    = limpiar($post['cedula'] ?? '');
$nombre    = limpiar($post['nombre'] ?? '');
$apellido  = limpiar($post['apellido'] ?? '');
$telefono  = limpiar($post['telefono'] ?? '');
$esResidente = (int) ($post['es_residente'] ?? 0) === 1 ? 1 : 0;
$torreId   = $esResidente ? ((int) ($post['torre_id'] ?? 0) ?: null) : null;
$apartamento = $esResidente ? (limpiar($post['apartamento'] ?? '') ?: null) : null;

if (!validar_cedula($cedula)) json_out(['ok' => false, 'error' => 'Cédula inválida.']);
if ($nombre === '' || $apellido === '') json_out(['ok' => false, 'error' => 'Nombre y apellido son obligatorios.']);
if (!validar_telefono($telefono)) json_out(['ok' => false, 'error' => 'Teléfono inválido.']);

$pdo = getConexion();

try {
    // Verificar cédula única (excluyendo el propio registro si es edición)
    $stmt = $pdo->prepare('SELECT id FROM personas WHERE cedula = ? AND id <> ? LIMIT 1');
    $stmt->execute([$cedula, $id]);
    if ($stmt->fetch()) {
        json_out(['ok' => false, 'error' => 'Ya existe una persona con esa cédula.']);
    }

    if ($id > 0) {
        $stmt = $pdo->prepare(
            'UPDATE personas SET cedula=?, nombre=?, apellido=?, telefono=?, es_residente=?, torre_id=?, apartamento=? WHERE id=?'
        );
        $stmt->execute([$cedula, $nombre, $apellido, $telefono, $esResidente, $torreId, $apartamento, $id]);
        json_out(['ok' => true, 'mensaje' => 'Persona actualizada correctamente.']);
    } else {
        $qrToken = generar_token_qr();
        $stmt = $pdo->prepare(
            'INSERT INTO personas (cedula, nombre, apellido, telefono, es_residente, torre_id, apartamento, qr_token) VALUES (?,?,?,?,?,?,?,?)'
        );
        $stmt->execute([$cedula, $nombre, $apellido, $telefono, $esResidente, $torreId, $apartamento, $qrToken]);
        json_out(['ok' => true, 'mensaje' => 'Persona registrada correctamente.', 'id' => (int) $pdo->lastInsertId()]);
    }
} catch (Throwable $e) {
    error_log('Error guardar persona: ' . $e->getMessage());
    json_out(['ok' => false, 'error' => 'Error interno al guardar la persona.'], 500);
}
