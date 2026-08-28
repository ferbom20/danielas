<?php
require_once __DIR__ . '/../config/config.php';
requerir_login();

$pdo = getConexion();

// --- Puestos ---
$totalPuestos = (int) $pdo->query('SELECT COUNT(*) c FROM puestos')->fetch()['c'];
$ocupados = (int) $pdo->query("SELECT COUNT(*) c FROM puestos WHERE estado <> 'disponible'")->fetch()['c'];
$disponibles = $totalPuestos - $ocupados;

// --- Movimientos activos por tipo ---
$activos = $pdo->query("SELECT tipo_entrada, COUNT(*) c FROM movimientos WHERE estado = 'activo' GROUP BY tipo_entrada")->fetchAll();
$countTipo = ['residente' => 0, 'visitante' => 0, 'mercado' => 0];
foreach ($activos as $a) { $countTipo[$a['tipo_entrada']] = (int) $a['c']; }
$totalDentro = array_sum($countTipo);

// --- Próximos a exceder / excedidos (calculado en PHP) ---
$stmt = $pdo->query(
    "SELECT m.id, m.tipo_entrada, m.fecha_entrada, v.placa, pu.numero
     FROM movimientos m
     JOIN vehiculos v ON v.id = m.vehiculo_id
     JOIN puestos pu ON pu.id = m.puesto_id
     WHERE m.estado = 'activo'"
);
$proximos = [];
$excedidos = [];
foreach ($stmt->fetchAll() as $m) {
    $c = calcular_estadia($m['tipo_entrada'], $m['fecha_entrada']);
    $item = [
        'placa' => $m['placa'], 'puesto' => $m['numero'],
        'tipo' => etiqueta_tipo($m['tipo_entrada']),
        'restante' => $c['minutos_restantes'], 'transcurrido' => $c['minutos_transcurridos'],
    ];
    if ($c['excedido']) {
        $excedidos[] = $item;
    } elseif ($c['minutos_restantes'] <= 30) {
        $proximos[] = $item;
    }
}

// --- Ingresos del día ---
$stmt = $pdo->prepare("SELECT COALESCE(SUM(monto),0) total, COUNT(*) c FROM movimientos WHERE estado='finalizado' AND DATE(fecha_salida) = CURDATE()");
$stmt->execute();
$ingresosHoy = $stmt->fetch();

// --- Movimientos recientes ---
$recientes = $pdo->query(
    "SELECT m.id, m.tipo_entrada, m.estado, m.fecha_entrada, m.fecha_salida, m.monto,
            v.placa, per.nombre, per.apellido, pu.numero
     FROM movimientos m
     JOIN vehiculos v ON v.id = m.vehiculo_id
     JOIN personas per ON per.id = m.persona_id
     JOIN puestos pu ON pu.id = m.puesto_id
     ORDER BY m.id DESC LIMIT 8"
)->fetchAll();

$tituloPagina = 'Dashboard';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/sidebar.php';
?>

<div class="topbar">
    <div>
        <h2>Dashboard</h2>
        <p>Resumen general del estacionamiento en tiempo real</p>
    </div>
    <div class="clock-chip"><span class="dot"></span> <span id="reloj-actual"></span></div>
</div>

<div class="bento">

    <div class="span-3 card stat-card">
        <div class="top-row">
            <div class="stat-icon grad-2">🅿️</div>
        </div>
        <div class="stat-value"><?= $disponibles ?> / <?= $totalPuestos ?></div>
        <div class="stat-label">Puestos disponibles</div>
        <div class="progress-bar"><div class="fill" style="width:<?= $totalPuestos ? round($disponibles/$totalPuestos*100) : 0 ?>%;background:linear-gradient(135deg,#00b894,#55efc4);"></div></div>
    </div>

    <div class="span-3 card stat-card">
        <div class="top-row"><div class="stat-icon grad-1">🚗</div></div>
        <div class="stat-value"><?= $totalDentro ?></div>
        <div class="stat-label">Vehículos dentro</div>
        <div class="stat-sub">Residentes, visitantes y mercado</div>
    </div>

    <div class="span-3 card stat-card">
        <div class="top-row"><div class="stat-icon grad-4">⚠️</div></div>
        <div class="stat-value"><?= count($proximos) ?></div>
        <div class="stat-label">Próximos a exceder</div>
        <div class="stat-sub">≤ 30 min restantes</div>
    </div>

    <div class="span-3 card stat-card">
        <div class="top-row"><div class="stat-icon grad-3">⛔</div></div>
        <div class="stat-value"><?= count($excedidos) ?></div>
        <div class="stat-label">Vehículos excedidos</div>
        <div class="stat-sub">Tarifa de $1 aplicada</div>
    </div>

    <div class="span-3 card stat-card">
        <div class="top-row"><div class="stat-icon grad-1">🏠</div></div>
        <div class="stat-value"><?= $countTipo['residente'] ?></div>
        <div class="stat-label">Residentes activos</div>
    </div>

    <div class="span-3 card stat-card">
        <div class="top-row"><div class="stat-icon grad-2">🧑‍🤝‍🧑</div></div>
        <div class="stat-value"><?= $countTipo['visitante'] ?></div>
        <div class="stat-label">Visitantes activos</div>
    </div>

    <div class="span-3 card stat-card">
        <div class="top-row"><div class="stat-icon grad-4">📦</div></div>
        <div class="stat-value"><?= $countTipo['mercado'] ?></div>
        <div class="stat-label">Mercado / Mudanza</div>
    </div>

    <div class="span-3 card stat-card">
        <div class="top-row"><div class="stat-icon grad-2">💰</div></div>
        <div class="stat-value"><?= formatear_dinero((float) $ingresosHoy['total']) ?></div>
        <div class="stat-label">Ingresos de hoy</div>
        <div class="stat-sub"><?= (int) $ingresosHoy['c'] ?> salidas cobradas</div>
    </div>

    <div class="span-6 card">
        <h3><span class="ic">⚠️</span> Próximos a superar el límite</h3>
        <?php if (!$proximos): ?>
            <div class="empty-state"><div class="ic">✅</div><p>Ningún vehículo cerca del límite.</p></div>
        <?php else: ?>
            <div class="table-wrap"><table>
                <thead><tr><th>Placa</th><th>Puesto</th><th>Tipo</th><th>Restante</th></tr></thead>
                <tbody>
                <?php foreach ($proximos as $p): ?>
                    <tr>
                        <td><b><?= htmlspecialchars($p['placa']) ?></b></td>
                        <td><?= htmlspecialchars($p['puesto']) ?></td>
                        <td><?= htmlspecialchars($p['tipo']) ?></td>
                        <td><span class="badge badge-warn"><?= formatear_duracion($p['restante']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        <?php endif; ?>
    </div>

    <div class="span-6 card">
        <h3><span class="ic">⛔</span> Vehículos excedidos</h3>
        <?php if (!$excedidos): ?>
            <div class="empty-state"><div class="ic">✅</div><p>No hay vehículos excedidos.</p></div>
        <?php else: ?>
            <div class="table-wrap"><table>
                <thead><tr><th>Placa</th><th>Puesto</th><th>Tipo</th><th>Transcurrido</th></tr></thead>
                <tbody>
                <?php foreach ($excedidos as $p): ?>
                    <tr>
                        <td><b><?= htmlspecialchars($p['placa']) ?></b></td>
                        <td><?= htmlspecialchars($p['puesto']) ?></td>
                        <td><?= htmlspecialchars($p['tipo']) ?></td>
                        <td><span class="badge badge-danger"><?= formatear_duracion($p['transcurrido']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        <?php endif; ?>
    </div>

    <div class="span-12 card">
        <h3><span class="ic">📜</span> Movimientos recientes</h3>
        <?php if (!$recientes): ?>
            <div class="empty-state"><div class="ic">📭</div><p>Aún no hay movimientos registrados.</p></div>
        <?php else: ?>
            <div class="table-wrap"><table>
                <thead><tr><th>Placa</th><th>Persona</th><th>Puesto</th><th>Tipo</th><th>Entrada</th><th>Salida</th><th>Monto</th><th>Estado</th></tr></thead>
                <tbody>
                <?php foreach ($recientes as $r): ?>
                    <tr>
                        <td><b><?= htmlspecialchars($r['placa']) ?></b></td>
                        <td><?= htmlspecialchars($r['nombre'] . ' ' . $r['apellido']) ?></td>
                        <td><?= htmlspecialchars($r['numero']) ?></td>
                        <td><?= etiqueta_tipo($r['tipo_entrada']) ?></td>
                        <td><?= (new DateTime($r['fecha_entrada']))->format('d/m h:i A') ?></td>
                        <td><?= $r['fecha_salida'] ? (new DateTime($r['fecha_salida']))->format('d/m h:i A') : '—' ?></td>
                        <td><?= formatear_dinero((float) $r['monto']) ?></td>
                        <td>
                            <?php if ($r['estado'] === 'activo'): ?>
                                <span class="badge badge-ok">Activo</span>
                            <?php else: ?>
                                <span class="badge badge-neutral">Finalizado</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        <?php endif; ?>
    </div>

</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
