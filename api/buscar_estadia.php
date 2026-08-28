<?php
require_once __DIR__ . '/../config/config.php';
requerir_login();
header('Content-Type: application/json; charset=utf-8');

$q = limpiar($_GET['q'] ?? '');
if ($q === '') json_out(['ok' => true, 'estadia' => null]);

$pdo = getConexion();

$sql = "SELECT m.id, m.tipo_entrada, m.fecha_entrada, m.limite_minutos, m.puesto_id,
               v.placa, per.nombre, per.apellido, per.cedula,
               pu.numero AS puesto_numero,
               t.nombre AS torre_visita
        FROM movimientos m
        JOIN vehiculos v ON v.id = m.vehiculo_id
        JOIN personas per ON per.id = m.persona_id
        JOIN puestos pu ON pu.id = m.puesto_id
        LEFT JOIN torres t ON t.id = m.torre_visita_id
        WHERE m.estado = 'activo' AND (v.placa = ? OR per.cedula = ?)
        ORDER BY m.fecha_entrada DESC
        LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->execute([strtoupper($q), $q]);
$mov = $stmt->fetch();

if (!$mov) {
    json_out(['ok' => true, 'estadia' => null]);
}

$calc = calcular_estadia($mov['tipo_entrada'], $mov['fecha_entrada']);

json_out([
    'ok' => true,
    'estadia' => [
        'movimiento_id'       => (int) $mov['id'],
        'placa'               => $mov['placa'],
        'persona'             => $mov['nombre'] . ' ' . $mov['apellido'],
        'cedula'              => $mov['cedula'],
        'puesto'              => $mov['puesto_numero'],
        'tipo_entrada'        => $mov['tipo_entrada'],
        'tipo_label'          => etiqueta_tipo($mov['tipo_entrada']),
        'torre_visita'        => $mov['torre_visita'],
        'fecha_entrada'       => (new DateTime($mov['fecha_entrada']))->format('d/m/Y h:i A'),
        'fecha_entrada_iso'   => $mov['fecha_entrada'],
        'minutos_transcurridos' => $calc['minutos_transcurridos'],
        'limite_minutos'      => $calc['limite_minutos'],
        'minutos_restantes'   => $calc['minutos_restantes'],
        'excedido'            => $calc['excedido'],
        'monto'               => $calc['monto'],
        'monto_formateado'    => formatear_dinero($calc['monto']),
        'tiempo_formateado'   => formatear_duracion($calc['minutos_transcurridos']),
    ],
]);
