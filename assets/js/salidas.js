/* Módulo Salidas */

const inputBuscarSalida = document.getElementById('input-buscar-salida');
const resultadoSalida = document.getElementById('resultado-salida');
let intervaloContador = null;
let estadiaActual = null;

function minutosATexto(min) {
    const h = Math.floor(min / 60);
    const m = min % 60;
    return h > 0 ? `${h}h ${String(m).padStart(2, '0')}m` : `${m}m`;
}

function pintarEstadia(e) {
    estadiaActual = e;
    const excedidoClass = e.excedido ? 'badge-danger' : 'badge-ok';
    const excedidoTexto = e.excedido ? 'Excedido' : 'Dentro del límite';

    resultadoSalida.innerHTML = `
        <div class="bento">
            <div class="span-8 card">
                <h3><span class="ic">🚗</span> Estadía activa</h3>
                <div class="flex justify-between items-center mt-8"><span class="text-dim">Persona</span><b>${e.persona}</b></div>
                <div class="flex justify-between items-center mt-8"><span class="text-dim">Cédula</span><b>${e.cedula}</b></div>
                <div class="flex justify-between items-center mt-8"><span class="text-dim">Placa</span><b>${e.placa}</b></div>
                <div class="flex justify-between items-center mt-8"><span class="text-dim">Puesto</span><b>${e.puesto}</b></div>
                <div class="flex justify-between items-center mt-8"><span class="text-dim">Tipo</span><b>${e.tipo_label}</b></div>
                ${e.torre_visita ? `<div class="flex justify-between items-center mt-8"><span class="text-dim">Visita</span><b>${e.torre_visita}</b></div>` : ''}
                <div class="flex justify-between items-center mt-8"><span class="text-dim">Entrada</span><b>${e.fecha_entrada}</b></div>
            </div>

            <div class="span-4 card text-center">
                <h3 style="justify-content:center;"><span class="ic">⏱️</span> Tiempo transcurrido</h3>
                <div id="contador-tiempo" style="font-size:32px;font-weight:800;">${minutosATexto(e.minutos_transcurridos)}</div>
                <span class="badge ${excedidoClass} mt-8" id="badge-estado">${excedidoTexto}</span>

                <div class="mt-16">
                    <div class="text-dim" style="font-size:12.5px;">Costo a cobrar</div>
                    <div id="monto-actual" style="font-size:28px;font-weight:800;">${e.monto_formateado}</div>
                </div>
                <div class="text-dim mt-8" id="restante-texto" style="font-size:12.5px;">
                    ${e.excedido ? 'Superó el tiempo gratuito' : 'Restante: ' + minutosATexto(e.minutos_restantes)}
                </div>

                <button class="btn btn-danger btn-block mt-16" id="btn-registrar-salida">🚪 Registrar salida</button>
            </div>
        </div>`;

    document.getElementById('btn-registrar-salida').addEventListener('click', registrarSalida);

    clearInterval(intervaloContador);
    intervaloContador = setInterval(() => {
        estadiaActual.minutos_transcurridos += 1;
        const excedido = estadiaActual.minutos_transcurridos > estadiaActual.limite_minutos;
        document.getElementById('contador-tiempo').textContent = minutosATexto(estadiaActual.minutos_transcurridos);
        const badge = document.getElementById('badge-estado');
        badge.textContent = excedido ? 'Excedido' : 'Dentro del límite';
        badge.className = 'badge mt-8 ' + (excedido ? 'badge-danger' : 'badge-ok');
        document.getElementById('monto-actual').textContent = excedido ? '$1.00' : '$0.00';
        document.getElementById('restante-texto').textContent = excedido
            ? 'Superó el tiempo gratuito'
            : 'Restante: ' + minutosATexto(Math.max(0, estadiaActual.limite_minutos - estadiaActual.minutos_transcurridos));
    }, 60000);
}

async function buscarEstadia() {
    const q = inputBuscarSalida.value.trim();
    clearInterval(intervaloContador);
    if (q.length < 2) { resultadoSalida.innerHTML = ''; return; }

    const data = await apiGet(`${BASE_URL}/api/buscar_estadia.php?q=${encodeURIComponent(q)}`);
    if (data.ok) {
        if (!data.estadia) {
            resultadoSalida.innerHTML = `<div class="empty-state"><div class="ic">🔍</div><p>No se encontró una estadía activa con ese dato.</p></div>`;
        } else {
            pintarEstadia(data.estadia);
        }
    }
}

inputBuscarSalida.addEventListener('input', debounce(buscarEstadia, 400));

async function registrarSalida() {
    if (!estadiaActual) return;
    const btn = document.getElementById('btn-registrar-salida');
    btn.disabled = true;
    btn.textContent = 'Procesando...';

    const res = await apiPost(`${BASE_URL}/api/salida.php`, { movimiento_id: estadiaActual.movimiento_id });

    if (res.ok) {
        toast(res.mensaje, 'success');
        clearInterval(intervaloContador);
        resultadoSalida.innerHTML = `<div class="empty-state"><div class="ic">✅</div><p>Salida registrada correctamente.</p></div>`;
        inputBuscarSalida.value = '';
    } else {
        btn.disabled = false;
        btn.textContent = '🚪 Registrar salida';
    }
}
