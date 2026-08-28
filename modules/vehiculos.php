<?php
require_once __DIR__ . '/../config/config.php';
requerir_login();

$pdo = getConexion();
$q = limpiar($_GET['q'] ?? '');

$sql = "SELECT v.*, p.nombre, p.apellido, p.cedula
        FROM vehiculos v JOIN personas p ON p.id = v.persona_id";
$params = [];
if ($q !== '') {
    $sql .= ' WHERE v.placa LIKE ? OR p.cedula LIKE ? OR p.nombre LIKE ?';
    $like = "%$q%";
    $params = [$like, $like, $like];
}
$sql .= ' ORDER BY v.created_at DESC LIMIT 150';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$vehiculos = $stmt->fetchAll();

$tituloPagina = 'Vehículos';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/sidebar.php';
?>

<div class="topbar">
    <div>
        <h2>Vehículos</h2>
        <p>Vehículos registrados y su propietario asociado</p>
    </div>
</div>

<div class="bento">
    <div class="span-12 card">
        <form class="search-box" method="GET">
            <input type="text" name="q" placeholder="Buscar por placa, cédula o nombre..." value="<?= htmlspecialchars($q) ?>">
            <button class="btn btn-ghost" type="submit">Buscar</button>
        </form>

        <div class="table-wrap">
            <table>
                <thead><tr><th>Placa</th><th>Marca</th><th>Modelo</th><th>Color</th><th>Propietario</th><th>Cédula</th><th>Acciones</th></tr></thead>
                <tbody>
                <?php if (!$vehiculos): ?>
                    <tr><td colspan="7"><div class="empty-state"><div class="ic">🚙</div><p>No hay vehículos registrados.</p></div></td></tr>
                <?php endif; ?>
                <?php foreach ($vehiculos as $v): ?>
                    <tr>
                        <td><b><?= htmlspecialchars($v['placa']) ?></b></td>
                        <td><?= htmlspecialchars($v['marca'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($v['modelo'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($v['color'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($v['nombre'] . ' ' . $v['apellido']) ?></td>
                        <td><?= htmlspecialchars($v['cedula']) ?></td>
                        <td>
                            <div class="flex gap-8">
                                <button class="btn btn-ghost btn-sm btn-editar-veh"
                                    data-id="<?= $v['id'] ?>" data-placa="<?= htmlspecialchars($v['placa']) ?>"
                                    data-marca="<?= htmlspecialchars($v['marca'] ?? '') ?>" data-modelo="<?= htmlspecialchars($v['modelo'] ?? '') ?>"
                                    data-color="<?= htmlspecialchars($v['color'] ?? '') ?>">✏️</button>
                                <button class="btn btn-danger btn-sm btn-eliminar-veh" data-id="<?= $v['id'] ?>">🗑️</button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal-overlay" id="modal-vehiculo">
    <div class="modal">
        <div class="modal-header"><h3>Editar vehículo</h3><button class="modal-close" data-modal-close>✕</button></div>
        <form id="form-vehiculo">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="v-id">
            <div class="form-group"><label>Placa *</label><input type="text" name="placa" id="v-placa" style="text-transform:uppercase;" required></div>
            <div class="form-row">
                <div class="form-group"><label>Marca</label><input type="text" name="marca" id="v-marca"></div>
                <div class="form-group"><label>Modelo</label><input type="text" name="modelo" id="v-modelo"></div>
            </div>
            <div class="form-group"><label>Color</label><input type="text" name="color" id="v-color"></div>
            <button type="submit" class="btn btn-primary btn-block">💾 Guardar</button>
        </form>
    </div>
</div>

<script>window.BASE_URL = <?= json_encode(BASE_URL) ?>; window.CSRF_TOKEN = <?= json_encode(csrf_token()) ?>;</script>
<?php $scriptsExtra = ['/assets/js/vehiculos.js']; require __DIR__ . '/../includes/footer.php'; ?>
