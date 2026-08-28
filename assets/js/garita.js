/* Módulo Garita: búsqueda de persona/vehículo y registro de entrada */

const inputBuscar = document.getElementById('input-buscar');
const resultadoBusqueda = document.getElementById('resultado-busqueda');
const cardFormulario = document.getElementById('card-formulario');
const form = document.getElementById('form-entrada');
const selectorVehiculos = document.getElementById('selector-vehiculos');

let personaActual = null;
let vehiculosPersona = [];

function limpiarFormulario() {
    form.reset();
    document.getElementById('f-vehiculo-id').value = '';
    document.getElementById('bloque-residente').classList.add('hidden');
    document.getElementById('bloque-visitante').classList.add('hidden');
    selectorVehiculos.classList.add('hidden');
    selectorVehiculos.innerHTML = '';
    document.getElementById('f-puesto').innerHTML = '<option value="">Seleccione el tipo de entrada primero...</option>';
    personaActual = null;
    vehiculosPersona = [];
}

function mostrarFormularioNuevo() {
    limpiarFormulario();
    cardFormulario.classList.remove('hidden');
    document.getElementById('f-cedula').removeAttribute('readonly');
    document.getElementById('f-cedula').focus();
    document.getElementById('f-cedula').value = document.getElementById('input-buscar').value;
    cardFormulario.scrollIntoView({ behavior: 'smooth' });
}

function pintarResultado(persona, vehiculos) {
    if (!persona) {
        resultadoBusqueda.innerHTML = `
            <div class="empty-state">
                <div class="ic">🔍</div>
                <p>No se encontró ninguna persona. Puede registrarla como nueva.</p>
            </div>`;
        return;
    }

    resultadoBusqueda.innerHTML = `
        <div class="persona-card">
            <div class="flex justify-between items-center">
                <div>
                    <div class="nombre">${persona.nombre} ${persona.apellido}</div>
                    <div class="meta">CC ${persona.cedula} · ${persona.telefono} ${persona.torre_nombre ? '· ' + persona.torre_nombre + ' Apto ' + persona.apartamento : ''}</div>
                </div>
                <div>
                    ${persona.tiene_activo
                        ? '<span class="badge badge-warn">Ya tiene una entrada activa</span>'
                        : `<button class="btn btn-primary btn-sm" id="btn-usar-persona">Usar esta persona</button>`}
                </div>
            </div>
        </div>`;

    if (!persona.tiene_activo) {
        document.getElementById('btn-usar-persona').addEventListener('click', () => cargarPersonaEnFormulario(persona, vehiculos));
    }
}

function cargarPersonaEnFormulario(persona, vehiculos) {
    limpiarFormulario();
    personaActual = persona;
    vehiculosPersona = vehiculos || [];

    document.getElementById('f-cedula').value = persona.cedula;
    document.getElementById('f-cedula').setAttribute('readonly', true);
    document.getElementById('f-nombre').value = persona.nombre;
    document.getElementById('f-apellido').value = persona.apellido;
    document.getElementById('f-telefono').value = persona.telefono;

    if (persona.es_residente) {
        document.getElementById('f-torre').value = persona.torre_id || '';
        document.getElementById('f-apartamento').value = persona.apartamento || '';
    }

    if (vehiculosPersona.length) {
        selectorVehiculos.classList.remove('hidden');
        selectorVehiculos.innerHTML = `
            <div class="form-group">
                <label>Seleccione un vehículo registrado o ingrese uno nuevo abajo</label>
                <select id="select-vehiculo-existente">
                    <option value="">-- Vehículo nuevo --</option>
                    ${vehiculosPersona.map(v => `<option value="${v.id}">${v.placa} ${v.marca ? '· ' + v.marca : ''} ${v.modelo || ''}</option>`).join('')}
                </select>
            </div>`;
        document.getElementById('select-vehiculo-existente').addEventListener('change', (e) => {
            const v = vehiculosPersona.find(x => String(x.id) === e.target.value);
            document.getElementById('f-vehiculo-id').value = v ? v.id : '';
            document.getElementById('f-placa').value = v ? v.placa : '';
            document.getElementById('f-marca').value = v ? (v.marca || '') : '';
            document.getElementById('f-modelo').value = v ? (v.modelo || '') : '';
            document.getElementById('f-color').value = v ? (v.color || '') : '';
        });
    }

    cardFormulario.classList.remove('hidden');
    cardFormulario.scrollIntoView({ behavior: 'smooth' });
}

const buscar = debounce(async () => {
    const q = inputBuscar.value.trim();
    if (q.length < 2) { resultadoBusqueda.innerHTML = ''; return; }
    const data = await apiGet(`${BASE_URL}/api/buscar_persona.php?q=${encodeURIComponent(q)}`);
    if (data.ok) pintarResultado(data.persona, data.vehiculos);
}, 400);

inputBuscar.addEventListener('input', buscar);
document.getElementById('btn-nuevo').addEventListener('click', mostrarFormularioNuevo);
document.getElementById('btn-cancelar').addEventListener('click', () => {
    cardFormulario.classList.add('hidden');
    limpiarFormulario();
});

/* Mostrar/ocultar bloques según tipo de entrada + cargar puestos disponibles */
document.getElementById('f-tipo').addEventListener('change', async (e) => {
    const tipo = e.target.value;
    document.getElementById('bloque-residente').classList.toggle('hidden', !(tipo === 'residente' || tipo === 'mercado'));
    document.getElementById('bloque-visitante').classList.toggle('hidden', tipo !== 'visitante');

    const selPuesto = document.getElementById('f-puesto');
    if (!tipo) {
        selPuesto.innerHTML = '<option value="">Seleccione el tipo de entrada primero...</option>';
        return;
    }
    selPuesto.innerHTML = '<option value="">Cargando puestos...</option>';
    const tipoPuesto = tipo === 'visitante' ? 'visitante' : 'residente';
    const data = await apiGet(`${BASE_URL}/api/puestos_disponibles.php?tipo=${tipoPuesto}`);
    if (data.ok) {
        if (!data.puestos.length) {
            selPuesto.innerHTML = '<option value="">No hay puestos disponibles</option>';
        } else {
            selPuesto.innerHTML = '<option value="">Seleccione...</option>' +
                data.puestos.map(p => `<option value="${p.id}">${p.numero} ${p.torre_nombre ? '· ' + p.torre_nombre : ''}</option>`).join('');
        }
    }
});

form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(form);
    const payload = Object.fromEntries(fd.entries());

    const btn = form.querySelector('button[type=submit]');
    btn.disabled = true;
    btn.textContent = 'Registrando...';

    const res = await apiPost(`${BASE_URL}/api/entrada.php`, payload);

    btn.disabled = false;
    btn.textContent = '✅ Registrar entrada';

    if (res.ok) {
        toast(res.mensaje, 'success');
        cardFormulario.classList.add('hidden');
        limpiarFormulario();
        inputBuscar.value = '';
        resultadoBusqueda.innerHTML = '';
    }
});
