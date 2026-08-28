<?php
require_once __DIR__ . '/../config/config.php';
requerir_login();

$tituloPagina = 'Salidas';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/sidebar.php';
?>

<div class="topbar">
    <div>
        <h2>Registrar salida</h2>
        <p>Busque por placa o cédula para ver la estadía activa y cobrar automáticamente</p>
    </div>
    <div class="clock-chip"><span class="dot"></span> <span id="reloj-actual"></span></div>
</div>

<div class="bento">
    <div class="span-12 card">
        <div class="search-box">
            <input type="text" id="input-buscar-salida" placeholder="Placa o cédula...">
        </div>
        <div id="resultado-salida"></div>
    </div>
</div>

<script>window.BASE_URL = <?= json_encode(BASE_URL) ?>; window.CSRF_TOKEN = <?= json_encode(csrf_token()) ?>;</script>
<?php $scriptsExtra = ['/assets/js/salidas.js']; require __DIR__ . '/../includes/footer.php'; ?>
