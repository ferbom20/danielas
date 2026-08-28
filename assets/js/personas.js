/* Módulo Personas: CRUD + generación de QR (100% cliente, sin exponer datos) */

const formPersona = document.getElementById('form-persona');
const modalPersonaTitulo = document.getElementById('modal-persona-titulo');

document.getElementById('btn-nueva-persona').addEventListener('click', () => {
    formPersona.reset();
    document.getElementById('p-id').value = '';
    modalPersonaTitulo.textContent = 'Nueva persona';
});

function toggleBloqueTorre() {
    const esResidente = document.getElementById('p-es-residente').value === '1';
    document.getElementById('p-bloque-torre').style.display = esResidente ? 'grid' : 'none';
}
document.getElementById('p-es-residente').addEventListener('change', toggleBloqueTorre);

document.querySelectorAll('.btn-editar').forEach(btn => {
    btn.addEventListener('click', () => {
        const id = parseInt(btn.dataset.id, 10);
        const persona = window.PERSONAS_DATA.find(p => p.id === id);
        if (!persona) return;

        formPersona.reset();
        document.getElementById('p-id').value = persona.id;
        document.getElementById('p-cedula').value = persona.cedula;
        document.getElementById('p-nombre').value = persona.nombre;
        document.getElementById('p-apellido').value = persona.apellido;
        document.getElementById('p-telefono').value = persona.telefono;
        document.getElementById('p-es-residente').value = persona.es_residente ? '1' : '0';
        document.getElementById('p-torre').value = persona.torre_id || '';
        document.getElementById('p-apartamento').value = persona.apartamento || '';
        toggleBloqueTorre();

        modalPersonaTitulo.textContent = 'Editar persona';
        abrirModal('#modal-persona');
    });
});

document.querySelectorAll('.btn-eliminar').forEach(btn => {
    btn.addEventListener('click', async () => {
        if (!confirm('¿Eliminar esta persona? Esta acción no se puede deshacer.')) return;
        const res = await apiPost(`${BASE_URL}/api/persona_eliminar.php`, { id: btn.dataset.id });
        if (res.ok) {
            toast(res.mensaje, 'success');
            setTimeout(() => location.reload(), 800);
        }
    });
});

formPersona.addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(formPersona);
    const payload = Object.fromEntries(fd.entries());
    const res = await apiPost(`${BASE_URL}/api/persona_guardar.php`, payload);
    if (res.ok) {
        toast(res.mensaje, 'success');
        cerrarModal('#modal-persona');
        setTimeout(() => location.reload(), 800);
    }
});

/* --- Generación de QR (contiene solo un token opaco, jamás datos personales) --- */
document.querySelectorAll('.btn-ver-qr').forEach(btn => {
    btn.addEventListener('click', () => {
        const token = btn.dataset.token;
        const nombre = btn.dataset.nombre;
        const url = `${window.location.origin}${BASE_URL}/public/consulta.php?t=${token}`;

        document.getElementById('qr-nombre').textContent = `QR de ${nombre}`;
        document.getElementById('qr-link').textContent = url;

        const wrap = document.getElementById('qr-canvas-wrap');
        wrap.innerHTML = '';

        const qr = qrcode(0, 'M');
        qr.addData(url);
        qr.make();
        wrap.innerHTML = qr.createSvgTag(6, 8);

        abrirModal('#modal-qr');
    });
});
