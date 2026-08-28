<?php
require_once __DIR__ . '/../config/config.php';
requerir_login();
header('Content-Type: application/json; charset=utf-8');

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) json_out(['ok' => false, 'error' => 'Puesto inválido.']);

$pdo = getConexion();

$stmt = $pdo->prepare('SELECT p.*, t.nombre AS torre_nombre FROM puestos p LEFT JOIN torres t ON t.id = p.torre_id WHERE p.id = ?');
$stmt->execute([$id]);
$puesto = $stmt->fetch();

if (!$puesto) json_out(['ok' => false, 'error' => 'Puesto no encontrado.']);

$movimiento = null;
if ($puesto['estado'] !== 'disponible') {
    $sql = "SELECT m.*, v.placa, per.nombre, per.apellido, per.telefono
            FROM movimientos m
            JOIN vehiculos v ON v.id = m.vehiculo_id
            JOIN personas per ON per.id = m.persona_id
            WHERE m.puesto_id = ? AND m.estado = 'activo' LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $mov = $stmt->fetch();

    if ($mov) {
        $calc = calcular_estadia($mov['tipo_entrada'], $mov['fecha_entrada']);
        $movimiento = [
            'movimiento_id'     => (int) $mov['id'],
            'placa'             => $mov['placa'],
            'persona'           => $mov['nombre'] . ' ' . $mov['apellido'],
            'telefono'          => $mov['telefono'],
            'tipo_label'        => etiqueta_tipo($mov['tipo_entrada']),
            'fecha_entrada'     => (new DateTime($mov['fecha_entrada']))->format('d/m/Y h:i A'),
            'tiempo_formateado' => formatear_duracion($calc['minutos_transcurridos']),
            'excedido'          => $calc['excedido'],
            'monto_formateado'  => formatear_dinero($calc['monto']),
        ];
    }
}

json_out([
    'ok' => true,
    'puesto' => [
        'id' => (int) $puesto['id'],
        'numero' => $puesto['numero'],
        'tipo' => $puesto['tipo'],
        'estado' => $puesto['estado'],
        'torre_nombre' => $puesto['torre_nombre'],
    ],
    'movimiento' => $movimiento,
]);
