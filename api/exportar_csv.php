<?php
require_once __DIR__ . '/../config/config.php';
requerir_login();

$pdo = getConexion();

$fDesde  = limpiar($_GET['desde'] ?? '');
$fHasta  = limpiar($_GET['hasta'] ?? '');
$fTipo   = limpiar($_GET['tipo'] ?? '');
$fTorre  = (int) ($_GET['torre'] ?? 0);
$fPlaca  = strtoupper(limpiar($_GET['placa'] ?? ''));

$where = [];
$params = [];

if ($fDesde !== '') { $where[] = 'DATE(m.fecha_entrada) >= ?'; $params[] = $fDesde; }
if ($fHasta !== '') { $where[] = 'DATE(m.fecha_entrada) <= ?'; $params[] = $fHasta; }
if ($fTipo !== '' && in_array($fTipo, ['residente','visitante','mercado'], true)) { $where[] = 'm.tipo_entrada = ?'; $params[] = $fTipo; }
if ($fTorre > 0) { $where[] = '(per.torre_id = ? OR m.torre_visita_id = ?)'; $params[] = $fTorre; $params[] = $fTorre; }
if ($fPlaca !== '') { $where[] = 'v.placa LIKE ?'; $params[] = "%$fPlaca%"; }

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$sql = "SELECT m.id, v.placa, per.nombre, per.apellido, per.cedula, pu.numero, m.tipo_entrada,
               m.fecha_entrada, m.fecha_salida, m.tiempo_total_minutos, m.monto, m.estado
        FROM movimientos m
        JOIN vehiculos v ON v.id = m.vehiculo_id
        JOIN personas per ON per.id = m.persona_id
        JOIN puestos pu ON pu.id = m.puesto_id
        $whereSql
        ORDER BY m.fecha_entrada DESC LIMIT 5000";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="historial_estacionamiento_' . date('Y-m-d_His') . '.csv"');

$out = fopen('php://output', 'w');
fputs($out, "\xEF\xBB\xBF"); // BOM para acentos en Excel
fputcsv($out, ['ID', 'Placa', 'Nombre', 'Apellido', 'Cédula', 'Puesto', 'Tipo', 'Entrada', 'Salida', 'Minutos', 'Monto', 'Estado']);

foreach ($stmt as $row) {
    fputcsv($out, [
        $row['id'], $row['placa'], $row['nombre'], $row['apellido'], $row['cedula'], $row['numero'],
        etiqueta_tipo($row['tipo_entrada']), $row['fecha_entrada'], $row['fecha_salida'] ?? '',
        $row['tiempo_total_minutos'] ?? '', $row['monto'], $row['estado'],
    ]);
}
fclose($out);
exit;
