/* Consulta pública QR - actualiza el contador sin recargar la página */

function pill(estado) {
    if (estado === 'excedido') return '<span class="qr-status-pill badge-danger">🔴 Excedido</span>';
    if (estado === 'sin_estadia') return '<span class="qr-status-pill badge-neutral">⚪ Sin estadía activa</span>';
    return '<span class="qr-status-pill badge-ok">🟢 Dentro del límite</span>';
}

function render(data) {
    const cont = document.getElementById('contenido');
    if (!data.activo) {
        cont.innerHTML = `
            <div class="empty-state">
                <div class="ic">🚗💤</div>
                <p>No tiene ningún vehículo actualmente dentro del estacionamiento.</p>
            </div>`;
        return;
    }

    const d = data.estadia;
    const estadoKey = d.excedido ? 'excedido' : 'ok';

    cont.innerHTML = `
        <div class="persona-card" style="background:transparent;border:none;padding:0;">
            <div class="flex justify-between items-center mt-8">
                <span class="text-dim" style="font-size:12.5px;">Vehículo</span>
                <b>${d.placa}</b>
            </div>
            <div class="flex justify-between items-center mt-8">
                <span class="text-dim" style="font-size:12.5px;">Puesto</span>
                <b>${d.puesto}</b>
            </div>
            <div class="flex justify-between items-center mt-8">
                <span class="text-dim" style="font-size:12.5px;">Tipo de entrada</span>
                <b>${d.tipo_label}</b>
            </div>
            <div class="flex justify-between items-center mt-8">
                <span class="text-dim" style="font-size:12.5px;">Entrada</span>
                <b>${d.fecha_entrada}</b>
            </div>
            <div class="flex justify-between items-center mt-8">
                <span class="text-dim" style="font-size:12.5px;">Hora límite gratuita</span>
                <b>${d.hora_limite}</b>
            </div>
        </div>

        <div class="qr-timer" id="tiempo-transcurrido">${d.tiempo_formateado}</div>
        <p class="text-center text-dim" style="margin-top:-6px;font-size:12px;">tiempo transcurrido</p>

        <div class="text-center mt-8">${pill(estadoKey)}</div>

        <div class="mt-16" style="text-align:center;">
            <span class="text-dim" style="font-size:12.5px;">Costo actual</span>
            <div style="font-size:26px;font-weight:800;">${d.monto_formateado}</div>
        </div>

        <div class="mt-8 text-center text-dim" style="font-size:12px;">
            ${d.excedido ? 'Superó el tiempo gratuito.' : `Tiempo gratuito restante: <b>${d.restante_formateado}</b>`}
        </div>
    `;
}

async function actualizar() {
    try {
        const res = await fetch(`${BASE_URL}/api/qr_estado.php?t=${encodeURIComponent(TOKEN)}`);
        const data = await res.json();
        render(data);
    } catch (e) {
        // silencioso: se reintenta en el próximo ciclo
    }
}

actualizar();
setInterval(actualizar, 5000);
