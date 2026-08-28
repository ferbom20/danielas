<?php
require_once __DIR__ . '/../config/config.php';
requerir_login();

$pdo = getConexion();
$torres = obtener_torres($pdo);

$editId = (int) ($_GET['editar'] ?? 0);
$personaEdit = null;
if ($editId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM personas WHERE id = ?');
    $stmt->execute([$editId]);
    $personaEdit = $stmt->fetch();
}

$historialId = (int) ($_GET['historial'] ?? 0);

$q = limpiar($_GET['q'] ?? '');
$sql = 'SELECT p.*, t.nombre AS torre_nombre,
        (SELECT COUNT(*) FROM vehiculos v WHERE v.persona_id = p.id) AS total_vehiculos
        FROM personas p LEFT JOIN torres t ON t.id = p.torre_id';
$params = [];
if ($q !== '') {
    $sql .= ' WHERE p.cedula LIKE ? OR p.nombre LIKE ? OR p.apellido LIKE ?';
    $like = "%$q%";
    $params = [$like, $like, $like];
}
$sql .= ' ORDER BY p.created_at DESC LIMIT 100';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$personas = $stmt->fetchAll();

$tituloPagina = 'Personas';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/sidebar.php';
?>

<div class="topbar">
    <div>
        <h2>Personas</h2>
        <p>Residentes y visitantes registrados en el sistema</p>
    </div>
    <button class="btn btn-primary" data-modal-target="#modal-persona" id="btn-nueva-persona">➕ Nueva persona</button>
</div>

<div class="bento">
    <div class="span-12 card">
        <form class="search-box" method="GET">
            <input type="text" name="q" placeholder="Buscar por cédula o nombre..." value="<?= htmlspecialchars($q) ?>">
            <button class="btn btn-ghost" type="submit">Buscar</button>
        </form>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Cédula</th><th>Nombre</th><th>Teléfono</th><th>Tipo</th><th>Torre/Apto</th><th>Vehículos</th><th>QR</th><th>Acciones</th></tr>
                </thead>
                <tbody>
                <?php if (!$personas): ?>
                    <tr><td colspan="8"><div class="empty-state"><div class="ic">👤</div><p>No hay personas registradas.</p></div></td></tr>
                <?php endif; ?>
                <?php foreach ($personas as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['cedula']) ?></td>
                        <td><?= htmlspecialchars($p['nombre'] . ' ' . $p['apellido']) ?></td>
                        <td><?= htmlspecialchars($p['telefono']) ?></td>
                        <td><?= $p['es_residente'] ? '<span class="badge badge-ok">Residente</span>' : '<span class="badge badge-neutral">Visitante</span>' ?></td>
                        <td><?= $p['torre_nombre'] ? htmlspecialchars($p['torre_nombre'] . ' / ' . $p['apartamento']) : '—' ?></td>
                        <td><?= (int) $p['total_vehiculos'] ?></td>
                        <td>
                            <?php if ($p['qr_token']): ?>
                                <button class="btn btn-ghost btn-sm btn-ver-qr" data-token="<?= htmlspecialchars($p['qr_token']) ?>" data-nombre="<?= htmlspecialchars($p['nombre'] . ' ' . $p['apellido']) ?>">📱 Ver QR</button>
                            <?php else: ?>
                                <span class="text-dim">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="flex gap-8">
                                <a class="btn btn-ghost btn-sm" href="?historial=<?= $p['id'] ?>">📜</a>
                                <button class="btn btn-ghost btn-sm btn-editar" data-id="<?= $p['id'] ?>">✏️</button>
                                <button class="btn btn-danger btn-sm btn-eliminar" data-id="<?= $p['id'] ?>">🗑️</button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($historialId): ?>
        <?php
            $stmt = $pdo->prepare('SELECT nombre, apellido FROM personas WHERE id = ?');
            $stmt->execute([$historialId]);
            $per = $stmt->fetch();

            $stmt = $pdo->prepare(
                "SELECT m.*, v.placa, pu.numero
                 FROM movimientos m
                 JOIN vehiculos v ON v.id = m.vehiculo_id
                 JOIN puestos pu ON pu.id = m.puesto_id
                 WHERE m.persona_id = ? ORDER BY m.fecha_entrada DESC LIMIT 50"
            );
            $stmt->execute([$historialId]);
            $movs = $stmt->fetchAll();
        ?>
        <div class="span-12 card">
            <h3><span class="ic">📜</span> Historial de <?= htmlspecialchars($per['nombre'] . ' ' . $per['apellido']) ?></h3>
            <div class="table-wrap"><table>
                <thead><tr><th>Placa</th><th>Puesto</th><th>Tipo</th><th>Entrada</th><th>Salida</th><th>Monto</th><th>Estado</th></tr></thead>
                <tbody>
                <?php foreach ($movs as $m): ?>
                    <tr>
                        <td><?= htmlspecialchars($m['placa']) ?></td>
                        <td><?= htmlspecialchars($m['numero']) ?></td>
                        <td><?= etiqueta_tipo($m['tipo_entrada']) ?></td>
                        <td><?= (new DateTime($m['fecha_entrada']))->format('d/m/Y h:i A') ?></td>
                        <td><?= $m['fecha_salida'] ? (new DateTime($m['fecha_salida']))->format('d/m/Y h:i A') : '—' ?></td>
                        <td><?= formatear_dinero((float) $m['monto']) ?></td>
                        <td><?= $m['estado'] === 'activo' ? '<span class="badge badge-ok">Activo</span>' : '<span class="badge badge-neutral">Finalizado</span>' ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Crear/Editar persona -->
<div class="modal-overlay" id="modal-persona">
    <div class="modal">
        <div class="modal-header">
            <h3 id="modal-persona-titulo">Nueva persona</h3>
            <button class="modal-close" data-modal-close>✕</button>
        </div>
        <form id="form-persona">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="p-id">
            <div class="form-row">
                <div class="form-group"><label>Cédula *</label><input type="text" name="cedula" id="p-cedula" required></div>
                <div class="form-group"><label>Teléfono *</label><input type="text" name="telefono" id="p-telefono" required></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Nombre *</label><input type="text" name="nombre" id="p-nombre" required></div>
                <div class="form-group"><label>Apellido *</label><input type="text" name="apellido" id="p-apellido" required></div>
            </div>
            <div class="form-group">
                <label>Tipo</label>
                <select name="es_residente" id="p-es-residente">
                    <option value="1">Residente</option>
                    <option value="0">Visitante</option>
                </select>
            </div>
            <div class="form-row" id="p-bloque-torre">
                <div class="form-group">
                    <label>Torre</label>
                    <select name="torre_id" id="p-torre">
                        <option value="">—</option>
                        <?php foreach ($torres as $t): ?>
                            <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label>Apartamento</label><input type="text" name="apartamento" id="p-apartamento"></div>
            </div>
            <button type="submit" class="btn btn-primary btn-block">💾 Guardar</button>
        </form>
    </div>
</div>

<!-- Modal QR -->
<div class="modal-overlay" id="modal-qr">
    <div class="modal" style="max-width:360px;text-align:center;">
        <div class="modal-header"><h3 id="qr-nombre">QR</h3><button class="modal-close" data-modal-close>✕</button></div>
        <div id="qr-canvas-wrap" style="display:flex;justify-content:center;padding:10px;background:#fff;border-radius:14px;"></div>
        <p class="text-dim mt-16" style="font-size:12px;">Escanee este código para consultar la estadía activa en tiempo real, sin necesidad de iniciar sesión.</p>
        <div id="qr-link" class="text-dim" style="font-size:11px;word-break:break-all;margin-top:8px;"></div>
    </div>
</div>

<script>
    window.BASE_URL = <?= json_encode(BASE_URL) ?>;
    window.CSRF_TOKEN = <?= json_encode(csrf_token()) ?>;
    window.PERSONAS_DATA = <?= json_encode($personas) ?>;
</script>
<?php $scriptsExtra = ['/assets/js/vendor/qrcode.min.js', '/assets/js/personas.js']; require __DIR__ . '/../includes/footer.php'; ?>
