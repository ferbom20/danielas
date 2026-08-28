/* Módulo Vehículos: editar y eliminar */

document.querySelectorAll('.btn-editar-veh').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('v-id').value = btn.dataset.id;
        document.getElementById('v-placa').value = btn.dataset.placa;
        document.getElementById('v-marca').value = btn.dataset.marca;
        document.getElementById('v-modelo').value = btn.dataset.modelo;
        document.getElementById('v-color').value = btn.dataset.color;
        abrirModal('#modal-vehiculo');
    });
});

document.querySelectorAll('.btn-eliminar-veh').forEach(btn => {
    btn.addEventListener('click', async () => {
        if (!confirm('¿Eliminar este vehículo?')) return;
        const res = await apiPost(`${BASE_URL}/api/vehiculo_eliminar.php`, { id: btn.dataset.id });
        if (res.ok) {
            toast(res.mensaje, 'success');
            setTimeout(() => location.reload(), 800);
        }
    });
});

document.getElementById('form-vehiculo').addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    const payload = Object.fromEntries(fd.entries());
    const res = await apiPost(`${BASE_URL}/api/vehiculo_guardar.php`, payload);
    if (res.ok) {
        toast(res.mensaje, 'success');
        cerrarModal('#modal-vehiculo');
        setTimeout(() => location.reload(), 800);
    }
});
