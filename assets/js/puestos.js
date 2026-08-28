/* Módulo Puestos: detalle al hacer click */

document.querySelectorAll('.puesto-item').forEach(btn => {
    btn.addEventListener('click', async () => {
        const id = btn.dataset.puestoId;
        document.getElementById('modal-puesto-contenido').innerHTML = '<div class="empty-state"><div class="ic">⏳</div><p>Cargando...</p></div>';

        const data = await apiGet(`${BASE_URL}/api/puesto_detalle.php?id=${id}`);
        if (!data.ok) return;

        const p = data.puesto;
        document.getElementById('modal-puesto-titulo').textContent = `Puesto ${p.numero}`;

        if (!data.movimiento) {
            document.getElementById('modal-puesto-contenido').innerHTML = `
                <div class="empty-state">
                    <div class="ic">🟢</div>
                    <p>Este puesto está disponible.</p>
                    <p class="text-dim" style="font-size:12px;">${p.torre_nombre ? 'Torre: ' + p.torre_nombre : 'Puesto de visitantes'}</p>
                </div>`;
            return;
        }

        const m = data.movimiento;
        document.getElementById('modal-puesto-contenido').innerHTML = `
            <div class="flex justify-between items-center mt-8"><span class="text-dim">Vehículo</span><b>${m.placa}</b></div>
            <div class="flex justify-between items-center mt-8"><span class="text-dim">Persona</span><b>${m.persona}</b></div>
            <div class="flex justify-between items-center mt-8"><span class="text-dim">Teléfono</span><b>${m.telefono}</b></div>
            <div class="flex justify-between items-center mt-8"><span class="text-dim">Tipo</span><b>${m.tipo_label}</b></div>
            <div class="flex justify-between items-center mt-8"><span class="text-dim">Entrada</span><b>${m.fecha_entrada}</b></div>
            <div class="flex justify-between items-center mt-8"><span class="text-dim">Tiempo ocupado</span><b>${m.tiempo_formateado}</b></div>
            <div class="flex justify-between items-center mt-8"><span class="text-dim">Costo actual</span><b>${m.monto_formateado}</b></div>
            <div class="mt-16 text-center">
                <span class="badge ${m.excedido ? 'badge-danger' : 'badge-ok'}">${m.excedido ? 'Excedido' : 'Dentro del límite'}</span>
            </div>`;
    });
});
