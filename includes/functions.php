<?php
/**
 * Funciones utilitarias compartidas por todo el sistema.
 */

function limpiar(?string $valor): string
{
    $valor = trim($valor ?? '');
    $valor = strip_tags($valor);
    return $valor;
}

function limite_minutos_por_tipo(string $tipo): int
{
    return $tipo === 'mercado' ? LIMITE_MINUTOS_MERCADO : LIMITE_MINUTOS_NORMAL;
}

/**
 * Calcula minutos transcurridos, monto y estado (dentro de límite / excedido)
 * dado un tipo de entrada y una fecha de inicio (y opcionalmente una fecha fin).
 */
function calcular_estadia(string $tipoEntrada, string $fechaEntrada, ?string $fechaReferencia = null): array
{
    $limite = limite_minutos_por_tipo($tipoEntrada);
    $inicio = new DateTime($fechaEntrada);
    $fin = $fechaReferencia ? new DateTime($fechaReferencia) : new DateTime();

    $minutos = max(0, (int) floor(($fin->getTimestamp() - $inicio->getTimestamp()) / 60));
    $excedido = $minutos > $limite;
    $monto = $excedido ? TARIFA_PLANA : 0.00;
    $minutosRestantes = $excedido ? 0 : ($limite - $minutos);

    return [
        'minutos_transcurridos' => $minutos,
        'limite_minutos'        => $limite,
        'minutos_restantes'     => $minutosRestantes,
        'excedido'              => $excedido,
        'monto'                 => $monto,
        'hora_limite'           => (clone $inicio)->modify("+{$limite} minutes")->format('Y-m-d H:i:s'),
    ];
}

function formatear_duracion(int $minutos): string
{
    $h = intdiv($minutos, 60);
    $m = $minutos % 60;
    if ($h > 0) {
        return sprintf('%dh %02dm', $h, $m);
    }
    return sprintf('%dm', $m);
}

function formatear_dinero(float $valor): string
{
    return '$' . number_format($valor, 2);
}

function generar_token_qr(): string
{
    return bin2hex(random_bytes(32)); // 64 caracteres hexadecimales, imposible de adivinar
}

function json_out(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function es_peticion_json(): bool
{
    return isset($_SERVER['CONTENT_TYPE']) && stripos($_SERVER['CONTENT_TYPE'], 'application/json') !== false;
}

function obtener_torres(PDO $pdo): array
{
    return $pdo->query('SELECT id, nombre FROM torres ORDER BY nombre')->fetchAll();
}

function validar_placa(string $placa): bool
{
    $placa = strtoupper(trim($placa));
    return (bool) preg_match('/^[A-Z0-9\-]{4,10}$/', $placa);
}

function validar_cedula(string $cedula): bool
{
    return (bool) preg_match('/^[0-9A-Za-z\-]{5,20}$/', trim($cedula));
}

function validar_telefono(string $telefono): bool
{
    return (bool) preg_match('/^[0-9+\-\s]{7,20}$/', trim($telefono));
}

/** Etiqueta legible para tipo de entrada */
function etiqueta_tipo(string $tipo): string
{
    $map = [
        'residente'  => 'Residente',
        'visitante'  => 'Visitante',
        'mercado'    => 'Mercado / Mudanza',
    ];
    return $map[$tipo] ?? ucfirst($tipo);
}

function redirigir(string $ruta): void
{
    header('Location: ' . $ruta);
    exit;
}
