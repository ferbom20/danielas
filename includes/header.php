<?php
/**
 * Header compartido. Requiere que $tituloPagina esté definido antes de incluirlo.
 */
$tituloPagina = $tituloPagina ?? 'Estacionamiento';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($tituloPagina) ?> · Estacionamiento Residencial</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
<link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🅿️</text></svg>">
</head>
<body>
<div id="toast-container"></div>
<div class="app-shell">
