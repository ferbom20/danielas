<?php
require_once __DIR__ . '/../config/config.php';

$token = limpiar($_GET['t'] ?? '');
$persona = null;

if ($token !== '' && preg_match('/^[a-f0-9]{64}$/', $token)) {
    $pdo = getConexion();
    $stmt = $pdo->prepare('SELECT id, nombre, apellido FROM personas WHERE qr_token = ? LIMIT 1');
    $stmt->execute([$token]);
    $persona = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mi estadía · Estacionamiento Residencial</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="qr-public-wrap">
    <div class="qr-public-card">
        <div class="text-center" style="margin-bottom:18px;">
            <div class="logo" style="width:56px;height:56px;border-radius:16px;background:var(--grad-primary);display:flex;align-items:center;justify-content:center;font-size:26px;margin:0 auto 10px;">🅿️</div>
            <h2 style="margin:0;font-size:18px;">Consulta de estadía</h2>
        </div>

        <?php if (!$persona): ?>
            <div class="empty-state">
                <div class="ic">❌</div>
                <p>Código QR inválido o expirado.</p>
            </div>
        <?php else: ?>
            <p class="text-center" style="font-size:15px;font-weight:600;margin-bottom:2px;">
                <?= htmlspecialchars($persona['nombre'] . ' ' . $persona['apellido']) ?>
            </p>
            <p class="text-center text-dim" style="font-size:12.5px;margin-top:0;">Actualizado en tiempo real</p>

            <div id="contenido" class="mt-16">
                <div class="empty-state"><div class="ic">⏳</div><p>Cargando...</p></div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($persona): ?>
<script>
const TOKEN = <?= json_encode($token) ?>;
const BASE_URL = <?= json_encode(BASE_URL) ?>;
</script>
<script src="<?= BASE_URL ?>/assets/js/qr.js"></script>
<?php endif; ?>
</body>
</html>
