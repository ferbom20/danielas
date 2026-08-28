/* =========================================================
   UTILIDADES GLOBALES
   ========================================================= */

const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content
    || window.CSRF_TOKEN || '';

const BASE_URL = window.BASE_URL || '';

function toast(mensaje, tipo = 'info') {
    const cont = document.getElementById('toast-container');
    if (!cont) return;
    const el = document.createElement('div');
    el.className = `toast ${tipo}`;
    const icon = tipo === 'success' ? '✅' : tipo === 'error' ? '⚠️' : 'ℹ️';
    el.innerHTML = `<span>${icon}</span><span>${mensaje}</span>`;
    cont.appendChild(el);
    setTimeout(() => {
        el.style.transition = 'opacity .3s ease, transform .3s ease';
        el.style.opacity = '0';
        el.style.transform = 'translateX(30px)';
        setTimeout(() => el.remove(), 300);
    }, 3800);
}

/**
 * Helper para llamadas a la API interna con CSRF automático.
 */
async function apiPost(url, data = {}) {
    const body = { ...data, csrf_token: CSRF_TOKEN };
    try {
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': CSRF_TOKEN,
            },
            body: JSON.stringify(body),
        });
        const json = await res.json();
        if (!json.ok) {
            toast(json.error || 'Ocurrió un error inesperado.', 'error');
        }
        return json;
    } catch (e) {
        toast('Error de conexión con el servidor.', 'error');
        return { ok: false, error: 'network' };
    }
}

async function apiGet(url) {
    try {
        const res = await fetch(url, { headers: { 'X-CSRF-Token': CSRF_TOKEN } });
        return await res.json();
    } catch (e) {
        toast('Error de conexión con el servidor.', 'error');
        return { ok: false, error: 'network' };
    }
}

/* Reloj en vivo en topbar (si existe) */
function iniciarReloj() {
    const el = document.getElementById('reloj-actual');
    if (!el) return;
    const tick = () => {
        const now = new Date();
        el.textContent = now.toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    };
    tick();
    setInterval(tick, 1000);
}
document.addEventListener('DOMContentLoaded', iniciarReloj);

/* Modales genéricos: data-modal-target / data-modal-close */
document.addEventListener('click', (e) => {
    const opener = e.target.closest('[data-modal-target]');
    if (opener) {
        const modal = document.querySelector(opener.dataset.modalTarget);
        if (modal) modal.classList.add('open');
    }
    const closer = e.target.closest('[data-modal-close]');
    if (closer) {
        closer.closest('.modal-overlay')?.classList.remove('open');
    }
    if (e.target.classList.contains('modal-overlay')) {
        e.target.classList.remove('open');
    }
});

function abrirModal(selector) {
    document.querySelector(selector)?.classList.add('open');
}
function cerrarModal(selector) {
    document.querySelector(selector)?.classList.remove('open');
}

function debounce(fn, delay = 350) {
    let t;
    return (...args) => {
        clearTimeout(t);
        t = setTimeout(() => fn(...args), delay);
    };
}
