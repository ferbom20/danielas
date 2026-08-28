<?php
require_once __DIR__ . '/../config/config.php';
requerir_login();
header('Content-Type: application/json; charset=utf-8');

$raw = json_decode(file_get_contents('php://input'), true) ?? [];
$post = array_merge($_POST, is_array($raw) ? $raw : []);

if (!csrf_validar($post['csrf_token'] ?? null)) {
    json_out(['ok' => false, 'error' => 'Token de seguridad inválido. Recargue la página.'], 403);
}

$movimientoId = (int) ($post['movimiento_id'] ?? 0);
if ($movimientoId <= 0) json_out(['ok' => false, 'error' => 'Movimiento inválido.']);

$pdo = getConexion();

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT * FROM movimientos WHERE id = ? AND estado = 'activo' LIMIT 1 FOR UPDATE");
    $stmt->execute([$movimientoId]);
    $mov = $stmt->fetch();

    if (!$mov) {
        throw new RuntimeException('La estadía ya fue cerrada o no existe.');
    }

    $ahora = new DateTime();
    $calc = calcular_estadia($mov['tipo_entrada'], $mov['fecha_entrada'], $ahora->format('Y-m-d H:i:s'));
    $u = usuario_actual();

    $stmt = $pdo->prepare(
        'UPDATE movimientos
         SET fecha_salida = ?, tiempo_total_minutos = ?, monto = ?, estado = \'finalizado\', usuario_salida_id = ?
         WHERE id = ?'
    );
    $stmt->execute([
        $ahora->format('Y-m-d H:i:s'),
        $calc['minutos_transcurridos'],
        $calc['monto'],
        $u['id'],
        $movimientoId,
    ]);

    // Liberar el puesto
    $stmt = $pdo->prepare("UPDATE puestos SET estado = 'disponible' WHERE id = ?");
    $stmt->execute([$mov['puesto_id']]);

    $pdo->commit();

    json_out([
        'ok' => true,
        'mensaje' => 'Salida registrada. Monto cobrado: ' . formatear_dinero($calc['monto']),
        'monto' => $calc['monto'],
    ]);
} catch (RuntimeException $e) {
    $pdo->rollBack();
    json_out(['ok' => false, 'error' => $e->getMessage()]);
} catch (Throwable $e) {
    $pdo->rollBack();
    error_log('Error registrar salida: ' . $e->getMessage());
    json_out(['ok' => false, 'error' => 'Error interno al registrar la salida.'], 500);
}
