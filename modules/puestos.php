<?php
require_once __DIR__ . '/../config/config.php';
requerir_login();

$pdo = getConexion();
$puestos = $pdo->query(
    "SELECT p.*, t.nombre AS torre_nombre FROM puestos p LEFT JOIN torres t ON t.id = p.torre_id ORDER BY p.tipo DESC, p.numero"
)->fetchAll();

$tituloPagina = 'Puestos';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/sidebar.php';
?>

<div class="topbar">
    <div>
        <h2>Mapa de puestos</h2>
        <p>46 puestos totales · 10 de visitantes (marcados con V) · haga clic para ver detalle</p>
    </div>
    <div class="flex gap-12">
        <span class="badge badge-ok">🟢 Disponible</span>
        <span class="badge badge-danger">🔴 Ocupado</span>
        <span class="badge badge-warn">🟡 Mercado/Mudanza</span>
    </div>
</div>

<div class="bento">
    <div class="span-12 card">
        <div class="puestos-grid">
            <?php foreach ($puestos as $p): ?>
                <?php
                    $clase = $p['estado'] === 'disponible' ? 'puesto-disponible' : ($p['estado'] === 'mercado' ? 'puesto-mercado' : 'puesto-ocupado');
                    $tipoClase = $p['tipo'] === 'visitante' ? 'tipo-visitante' : '';
                ?>
                <button class="puesto-item <?= $clase ?> <?= $tipoClase ?>" data-puesto-id="<?= $p['id'] ?>" data-modal-target="#modal-puesto">
                    <?= htmlspecialchars($p['numero']) ?>
                    <small><?= $p['torre_nombre'] ? htmlspecialchars($p['torre_nombre']) : 'Visitantes' ?></small>
                </button>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="modal-overlay" id="modal-puesto">
    <div class="modal">
        <div class="modal-header">
            <h3 id="modal-puesto-titulo">Puesto</h3>
            <button class="modal-close" data-modal-close>✕</button>
        </div>
        <div id="modal-puesto-contenido">Cargando...</div>
    </div>
</div>

<script>window.BASE_URL = <?= json_encode(BASE_URL) ?>;</script>
<?php $scriptsExtra = ['/assets/js/puestos.js']; require __DIR__ . '/../includes/footer.php'; ?>
