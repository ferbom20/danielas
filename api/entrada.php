<?php
require_once __DIR__ . '/../config/config.php';
requerir_login();
header('Content-Type: application/json; charset=utf-8');

$raw = json_decode(file_get_contents('php://input'), true) ?? [];
$post = array_merge($_POST, is_array($raw) ? $raw : []);

if (!csrf_validar($post['csrf_token'] ?? null)) {
    json_out(['ok' => false, 'error' => 'Token de seguridad inválido. Recargue la página.'], 403);
}

$tipoEntrada = limpiar($post['tipo_entrada'] ?? ''); // residente | visitante | mercado
if (!in_array($tipoEntrada, ['residente', 'visitante', 'mercado'], true)) {
    json_out(['ok' => false, 'error' => 'Tipo de entrada inválido.']);
}

// Datos de persona
$cedula    = limpiar($post['cedula'] ?? '');
$nombre    = limpiar($post['nombre'] ?? '');
$apellido  = limpiar($post['apellido'] ?? '');
$telefono  = limpiar($post['telefono'] ?? '');
$torreId   = (int) ($post['torre_id'] ?? 0);
$apartamento = limpiar($post['apartamento'] ?? '');

// Vehículo
$placa   = strtoupper(limpiar($post['placa'] ?? ''));
$marca   = limpiar($post['marca'] ?? '');
$modelo  = limpiar($post['modelo'] ?? '');
$color   = limpiar($post['color'] ?? '');
$vehiculoIdExistente = (int) ($post['vehiculo_id'] ?? 0);

$puestoId = (int) ($post['puesto_id'] ?? 0);

// Torre/apartamento a visitar (para visitante)
$torreVisitaId = (int) ($post['torre_visita_id'] ?? 0);
$apartamentoVisita = limpiar($post['apartamento_visita'] ?? '');

// --- Validaciones básicas ---
if (!validar_cedula($cedula)) json_out(['ok' => false, 'error' => 'Cédula inválida.']);
if ($nombre === '' || $apellido === '') json_out(['ok' => false, 'error' => 'Nombre y apellido son obligatorios.']);
if (!validar_telefono($telefono)) json_out(['ok' => false, 'error' => 'Teléfono inválido.']);
if (!validar_placa($placa)) json_out(['ok' => false, 'error' => 'Placa inválida.']);
if ($puestoId <= 0) json_out(['ok' => false, 'error' => 'Debe seleccionar un puesto.']);

if ($tipoEntrada === 'mercado') {
    // Exclusivo residentes
    if ($torreId <= 0 || $apartamento === '') {
        json_out(['ok' => false, 'error' => 'Torre y apartamento son obligatorios para residentes.']);
    }
} elseif ($tipoEntrada === 'residente') {
    if ($torreId <= 0 || $apartamento === '') {
        json_out(['ok' => false, 'error' => 'Torre y apartamento son obligatorios para residentes.']);
    }
} elseif ($tipoEntrada === 'visitante') {
    if ($torreVisitaId <= 0 || $apartamentoVisita === '') {
        json_out(['ok' => false, 'error' => 'Debe indicar torre y apartamento que visita.']);
    }
}

$pdo = getConexion();

try {
    $pdo->beginTransaction();

    // 1) Buscar o crear persona (cédula única)
    $stmt = $pdo->prepare('SELECT * FROM personas WHERE cedula = ? LIMIT 1 FOR UPDATE');
    $stmt->execute([$cedula]);
    $persona = $stmt->fetch();

    if (!$persona) {
        $esResidente = in_array($tipoEntrada, ['residente', 'mercado'], true) ? 1 : 0;
        $qrToken = generar_token_qr();

        $stmt = $pdo->prepare(
            'INSERT INTO personas (cedula, nombre, apellido, telefono, es_residente, torre_id, apartamento, qr_token)
             VALUES (?,?,?,?,?,?,?,?)'
        );
        $stmt->execute([
            $cedula, $nombre, $apellido, $telefono, $esResidente,
            $esResidente ? ($torreId ?: null) : null,
            $esResidente ? ($apartamento ?: null) : null,
            $qrToken,
        ]);
        $personaId = (int) $pdo->lastInsertId();
    } else {
        $personaId = (int) $persona['id'];
        // Si es residente y aún no tenía QR (compatibilidad), generarlo
        if (empty($persona['qr_token'])) {
            $qrToken = generar_token_qr();
            $stmt = $pdo->prepare('UPDATE personas SET qr_token = ? WHERE id = ?');
            $stmt->execute([$qrToken, $personaId]);
        }
        // Actualizar datos de contacto básicos por si cambiaron
        $stmt = $pdo->prepare('UPDATE personas SET nombre = ?, apellido = ?, telefono = ? WHERE id = ?');
        $stmt->execute([$nombre, $apellido, $telefono, $personaId]);
    }

    // Validar restricción: mercado/mudanza solo para residentes
    if ($tipoEntrada === 'mercado') {
        $esResidenteActual = $persona ? (bool) $persona['es_residente'] : true;
        if (!$esResidenteActual) {
            throw new RuntimeException('El tipo Mercado/Mudanza es exclusivo para residentes.');
        }
    }

    // 2) Buscar o crear vehículo (placa única)
    $stmt = $pdo->prepare('SELECT * FROM vehiculos WHERE placa = ? LIMIT 1 FOR UPDATE');
    $stmt->execute([$placa]);
    $vehiculo = $stmt->fetch();

    if (!$vehiculo) {
        $stmt = $pdo->prepare('INSERT INTO vehiculos (persona_id, placa, marca, modelo, color) VALUES (?,?,?,?,?)');
        $stmt->execute([$personaId, $placa, $marca ?: null, $modelo ?: null, $color ?: null]);
        $vehiculoId = (int) $pdo->lastInsertId();
    } else {
        if ((int) $vehiculo['persona_id'] !== $personaId) {
            throw new RuntimeException('Esa placa ya está registrada a nombre de otra persona.');
        }
        $vehiculoId = (int) $vehiculo['id'];
    }

    // 3) Verificar que el vehículo no tenga ya una estadía activa
    $stmt = $pdo->prepare("SELECT id FROM movimientos WHERE vehiculo_id = ? AND estado = 'activo' LIMIT 1 FOR UPDATE");
    $stmt->execute([$vehiculoId]);
    if ($stmt->fetch()) {
        throw new RuntimeException('Este vehículo ya tiene una entrada activa registrada.');
    }

    // 4) Verificar y bloquear el puesto
    $stmt = $pdo->prepare("SELECT * FROM puestos WHERE id = ? LIMIT 1 FOR UPDATE");
    $stmt->execute([$puestoId]);
    $puesto = $stmt->fetch();

    if (!$puesto) throw new RuntimeException('El puesto seleccionado no existe.');
    if ($puesto['estado'] !== 'disponible') throw new RuntimeException('El puesto seleccionado ya no está disponible.');

    if ($tipoEntrada === 'visitante' && $puesto['tipo'] !== 'visitante') {
        throw new RuntimeException('Los visitantes deben usar un puesto de visitantes.');
    }
    if (in_array($tipoEntrada, ['residente', 'mercado'], true) && $puesto['tipo'] !== 'residente') {
        throw new RuntimeException('Residentes y Mercado/Mudanza deben usar un puesto de residentes.');
    }

    // 5) Insertar movimiento
    $limite = limite_minutos_por_tipo($tipoEntrada);
    $ahora = (new DateTime())->format('Y-m-d H:i:s');
    $u = usuario_actual();

    $stmt = $pdo->prepare(
        'INSERT INTO movimientos
            (persona_id, vehiculo_id, puesto_id, tipo_entrada, torre_visita_id, apartamento_visita,
             fecha_entrada, limite_minutos, monto, estado, usuario_entrada_id)
         VALUES (?,?,?,?,?,?,?,?,0.00,\'activo\',?)'
    );
    $stmt->execute([
        $personaId, $vehiculoId, $puestoId, $tipoEntrada,
        $tipoEntrada === 'visitante' ? $torreVisitaId : null,
        $tipoEntrada === 'visitante' ? $apartamentoVisita : null,
        $ahora, $limite, $u['id'],
    ]);
    $movimientoId = (int) $pdo->lastInsertId();

    // 6) Actualizar estado del puesto
    $nuevoEstadoPuesto = $tipoEntrada === 'mercado' ? 'mercado' : 'ocupado';
    $stmt = $pdo->prepare('UPDATE puestos SET estado = ? WHERE id = ?');
    $stmt->execute([$nuevoEstadoPuesto, $puestoId]);

    $pdo->commit();

    json_out([
        'ok' => true,
        'mensaje' => 'Entrada registrada correctamente en el puesto ' . $puesto['numero'] . '.',
        'movimiento_id' => $movimientoId,
    ]);

} catch (RuntimeException $e) {
    $pdo->rollBack();
    json_out(['ok' => false, 'error' => $e->getMessage()]);
} catch (Throwable $e) {
    $pdo->rollBack();
    error_log('Error registrar entrada: ' . $e->getMessage());
    json_out(['ok' => false, 'error' => 'Error interno al registrar la entrada.'], 500);
}
