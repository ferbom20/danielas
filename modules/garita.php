<?php
require_once __DIR__ . '/../config/config.php';
requerir_login();

$pdo = getConexion();
$torres = obtener_torres($pdo);

$tituloPagina = 'Garita / Entrada';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/sidebar.php';
?>

<div class="topbar">
    <div>
        <h2>Garita · Registrar entrada</h2>
        <p>Busque por cédula o placa. Si la persona no existe, se creará automáticamente.</p>
    </div>
    <div class="clock-chip"><span class="dot"></span> <span id="reloj-actual"></span></div>
</div>

<div class="bento">
    <div class="span-12 card">
        <h3><span class="ic">🔎</span> Buscar persona o vehículo</h3>
        <div class="search-box">
            <input type="text" id="input-buscar" placeholder="Escriba la cédula o la placa...">
            <button class="btn btn-ghost" id="btn-nuevo">➕ Registrar nueva persona</button>
        </div>
        <div id="resultado-busqueda"></div>
    </div>

    <div class="span-12 card hidden" id="card-formulario">
        <h3><span class="ic">🚗</span> Registrar entrada</h3>
        <form id="form-entrada">
            <?= csrf_field() ?>
            <input type="hidden" name="vehiculo_id" id="f-vehiculo-id">

            <div class="form-row">
                <div class="form-group">
                    <label>Cédula *</label>
                    <input type="text" name="cedula" id="f-cedula" required>
                </div>
                <div class="form-group">
                    <label>Teléfono *</label>
                    <input type="text" name="telefono" id="f-telefono" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Nombre *</label>
                    <input type="text" name="nombre" id="f-nombre" required>
                </div>
                <div class="form-group">
                    <label>Apellido *</label>
                    <input type="text" name="apellido" id="f-apellido" required>
                </div>
            </div>

            <div class="form-group">
                <label>Tipo de entrada *</label>
                <select name="tipo_entrada" id="f-tipo" required>
                    <option value="">Seleccione...</option>
                    <option value="residente">Residente</option>
                    <option value="visitante">Visitante</option>
                    <option value="mercado">Mercado / Mudanza (solo residentes)</option>
                </select>
            </div>

            <div id="bloque-residente" class="hidden">
                <div class="form-row">
                    <div class="form-group">
                        <label>Torre *</label>
                        <select id="f-torre" name="torre_id">
                            <option value="">Seleccione...</option>
                            <?php foreach ($torres as $t): ?>
                                <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Apartamento *</label>
                        <input type="text" id="f-apartamento" name="apartamento">
                    </div>
                </div>
            </div>

            <div id="bloque-visitante" class="hidden">
                <div class="form-row">
                    <div class="form-group">
                        <label>Torre que visita *</label>
                        <select id="f-torre-visita" name="torre_visita_id">
                            <option value="">Seleccione...</option>
                            <?php foreach ($torres as $t): ?>
                                <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Apartamento que visita *</label>
                        <input type="text" id="f-apartamento-visita" name="apartamento_visita">
                    </div>
                </div>
            </div>

            <h3 style="margin-top:6px;"><span class="ic">🚙</span> Vehículo</h3>
            <div id="selector-vehiculos" class="hidden mt-8"></div>

            <div class="form-row">
                <div class="form-group">
                    <label>Placa *</label>
                    <input type="text" id="f-placa" name="placa" style="text-transform:uppercase;" required>
                </div>
                <div class="form-group">
                    <label>Color</label>
                    <input type="text" id="f-color" name="color">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Marca</label>
                    <input type="text" id="f-marca" name="marca">
                </div>
                <div class="form-group">
                    <label>Modelo</label>
                    <input type="text" id="f-modelo" name="modelo">
                </div>
            </div>

            <div class="form-group">
                <label>Puesto disponible *</label>
                <select id="f-puesto" name="puesto_id" required>
                    <option value="">Seleccione el tipo de entrada primero...</option>
                </select>
            </div>

            <div class="flex gap-12 mt-16">
                <button type="submit" class="btn btn-primary">✅ Registrar entrada</button>
                <button type="button" class="btn btn-ghost" id="btn-cancelar">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<script>window.BASE_URL = <?= json_encode(BASE_URL) ?>; window.CSRF_TOKEN = <?= json_encode(csrf_token()) ?>;</script>
<?php $scriptsExtra = ['/assets/js/garita.js']; require __DIR__ . '/../includes/footer.php'; ?>
