<?php
require_once __DIR__ . '/../config/config.php';
requerir_login();
header('Content-Type: application/json; charset=utf-8');

$tipo = limpiar($_GET['tipo'] ?? ''); // 'residente' -> puestos residente ; 'visitante' -> puestos visitante; mercado usa residente

$pdo = getConexion();

$tipoPuesto = ($tipo === 'visitante') ? 'visitante' : 'residente';

$stmt = $pdo->prepare(
    "SELECT p.id, p.numero, p.tipo, t.nombre AS torre_nombre
     FROM puestos p
     LEFT JOIN torres t ON t.id = p.torre_id
     WHERE p.estado = 'disponible' AND p.tipo = ?
     ORDER BY p.numero"
);
$stmt->execute([$tipoPuesto]);
$puestos = $stmt->fetchAll();

json_out(['ok' => true, 'puestos' => $puestos]);
