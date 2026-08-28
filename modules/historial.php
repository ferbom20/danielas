<?php
require_once __DIR__ . '/../config/config.php';
requerir_login();

$pdo = getConexion();
$torres = obtener_torres($pdo);

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

$sql = "SELECT m.*, v.placa, per.nombre, per.apellido, per.cedula, pu.numero
        FROM movimientos m
        JOIN vehiculos v ON v.id = m.vehiculo_id
        JOIN personas per ON per.id = m.persona_id
        JOIN puestos pu ON pu.id = m.puesto_id
        $whereSql
        ORDER BY m.fecha_entrada DESC LIMIT 500";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$movimientos = $stmt->fetchAll();

$totalRecaudado = 0.0;
foreach ($movimientos as $m) { $totalRecaudado += (float) $m['monto']; }

$tituloPagina = 'Historial / Reportes';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/sidebar.php';
?>

<div class="topbar">
    <div>
        <h2>Historial y reportes</h2>
        <p>Consulte y exporte los movimientos registrados</p>
    </div>
</div>

<div class="bento">
    <div class="span-12 card">
        <form method="GET" class="form-row" style="grid-template-columns:repeat(5,1fr);gap:12px;align-items:end;">
            <div class="form-group" style="margin:0;"><label>Desde</label><input type="date" name="desde" value="<?= htmlspecialchars($fDesde) ?>"></div>
            <div class="form-group" style="margin:0;"><label>Hasta</label><input type="date" name="hasta" value="<?= htmlspecialchars($fHasta) ?>"></div>
            <div class="form-group" style="margin:0;">
                <label>Tipo</label>
                <select name="tipo">
                    <option value="">Todos</option>
                    <option value="residente" <?= $fTipo === 'residente' ? 'selected' : '' ?>>Residente</option>
                    <option value="visitante" <?= $fTipo === 'visitante' ? 'selected' : '' ?>>Visitante</option>
                    <option value="mercado" <?= $fTipo === 'mercado' ? 'selected' : '' ?>>Mercado/Mudanza</option>
                </select>
            </div>
            <div class="form-group" style="margin:0;">
                <label>Torre</label>
                <select name="torre">
                    <option value="0">Todas</option>
                    <?php foreach ($torres as $t): ?>
                        <option value="<?= $t['id'] ?>" <?= $fTorre === (int)$t['id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin:0;"><label>Placa</label><input type="text" name="placa" value="<?= htmlspecialchars($fPlaca) ?>" placeholder="ABC123"></div>
            <div class="form-group" style="grid-column:1/-1;margin:0;display:flex;gap:10px;">
                <button class="btn btn-primary" type="submit">Filtrar</button>
                <a class="btn btn-ghost" href="<?= BASE_URL ?>/modules/historial.php">Limpiar</a>
                <a class="btn btn-success" href="<?= BASE_URL ?>/api/exportar_csv.php?<?= http_build_query($_GET) ?>">⬇️ Exportar CSV</a>
            </div>
        </form>
    </div>

    <div class="span-3 card stat-card">
        <div class="top-row"><div class="stat-icon grad-2">💰</div></div>
        <div class="stat-value"><?= formatear_dinero($totalRecaudado) ?></div>
        <div class="stat-label">Total recaudado (filtro actual)</div>
    </div>
    <div class="span-3 card stat-card">
        <div class="top-row"><div class="stat-icon grad-1">📄</div></div>
        <div class="stat-value"><?= count($movimientos) ?></div>
        <div class="stat-label">Movimientos encontrados</div>
    </div>

    <div class="span-12 card">
        <div class="table-wrap">
            <table>
                <thead><tr><th>Placa</th><th>Persona</th><th>Cédula</th><th>Puesto</th><th>Tipo</th><th>Entrada</th><th>Salida</th><th>Tiempo</th><th>Monto</th><th>Estado</th></tr></thead>
                <tbody>
                <?php if (!$movimientos): ?>
                    <tr><td colspan="10"><div class="empty-state"><div class="ic">📭</div><p>No hay movimientos con esos filtros.</p></div></td></tr>
                <?php endif; ?>
                <?php foreach ($movimientos as $m): ?>
                    <tr>
                        <td><b><?= htmlspecialchars($m['placa']) ?></b></td>
                        <td><?= htmlspecialchars($m['nombre'] . ' ' . $m['apellido']) ?></td>
                        <td><?= htmlspecialchars($m['cedula']) ?></td>
                        <td><?= htmlspecialchars($m['numero']) ?></td>
                        <td><?= etiqueta_tipo($m['tipo_entrada']) ?></td>
                        <td><?= (new DateTime($m['fecha_entrada']))->format('d/m/Y h:i A') ?></td>
                        <td><?= $m['fecha_salida'] ? (new DateTime($m['fecha_salida']))->format('d/m/Y h:i A') : '—' ?></td>
                        <td><?= $m['tiempo_total_minutos'] !== null ? formatear_duracion((int)$m['tiempo_total_minutos']) : '—' ?></td>
                        <td><?= formatear_dinero((float) $m['monto']) ?></td>
                        <td><?= $m['estado'] === 'activo' ? '<span class="badge badge-ok">Activo</span>' : '<span class="badge badge-neutral">Finalizado</span>' ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
