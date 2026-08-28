<?php
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json; charset=utf-8');

$token = limpiar($_GET['t'] ?? '');

if ($token === '' || !preg_match('/^[a-f0-9]{64}$/', $token)) {
    json_out(['activo' => false]);
}

$pdo = getConexion();
$stmt = $pdo->prepare('SELECT id FROM personas WHERE qr_token = ? LIMIT 1');
$stmt->execute([$token]);
$persona = $stmt->fetch();

if (!$persona) {
    json_out(['activo' => false]);
}

$sql = "SELECT m.id, m.tipo_entrada, m.fecha_entrada, m.limite_minutos,
               v.placa, p.numero AS puesto
        FROM movimientos m
        JOIN vehiculos v ON v.id = m.vehiculo_id
        JOIN puestos p ON p.id = m.puesto_id
        WHERE m.persona_id = ? AND m.estado = 'activo'
        ORDER BY m.fecha_entrada DESC LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->execute([$persona['id']]);
$mov = $stmt->fetch();

if (!$mov) {
    json_out(['activo' => false]);
}

$calc = calcular_estadia($mov['tipo_entrada'], $mov['fecha_entrada']);

json_out([
    'activo' => true,
    'estadia' => [
        'placa'              => $mov['placa'],
        'puesto'             => $mov['puesto'],
        'tipo_label'         => etiqueta_tipo($mov['tipo_entrada']),
        'fecha_entrada'      => (new DateTime($mov['fecha_entrada']))->format('d/m/Y h:i A'),
        'hora_limite'        => (new DateTime($calc['hora_limite']))->format('d/m/Y h:i A'),
        'tiempo_formateado'  => formatear_duracion($calc['minutos_transcurridos']),
        'restante_formateado'=> formatear_duracion($calc['minutos_restantes']),
        'excedido'           => $calc['excedido'],
        'monto_formateado'   => formatear_dinero($calc['monto']),
    ],
]);
