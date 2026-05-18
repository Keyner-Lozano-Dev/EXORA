/**
 * EXORA — scripts.js
 * Funciones compartidas: index.php y registro.php
 */

/* ================================= */
/* MODO OSCURO                       */
/* ================================= */

function toggleDark() {
    document.body.classList.toggle('dark');
    const btn = document.querySelector('.dark-toggle');
    btn.textContent = document.body.classList.contains('dark') ? '☀️' : '🌙';
    localStorage.setItem('darkMode', document.body.classList.contains('dark'));
}

// Restaurar preferencia guardada al cargar la página
(function initDarkMode() {
    if (localStorage.getItem('darkMode') === 'true') {
        document.body.classList.add('dark');
        const btn = document.querySelector('.dark-toggle');
        if (btn) btn.textContent = '☀️';
    }
})();

/* ================================= */
/* MOSTRAR / OCULTAR CONTRASEÑA      */
/* ================================= */

const EYE_OPEN = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="18" height="18">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
</svg>`;

const EYE_CLOSED = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="18" height="18">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
        d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.477 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
</svg>`;

function togglePass(fieldId, btn) {
    const input = document.getElementById(fieldId);
    const isPassword = input.type === 'password';
    input.type = isPassword ? 'text' : 'password';
    btn.innerHTML = isPassword ? EYE_CLOSED : EYE_OPEN;
}

/* ================================= */
/* FIX CURSOR SPLINE (solo index)    */
/* ================================= */

function initSplineCursorFix() {
    const viewer = document.getElementById('splineViewer');
    if (!viewer) return;

    const getIframe = () => viewer.shadowRoot
        ? viewer.shadowRoot.querySelector('iframe')
        : null;

    document.addEventListener('mousemove', (e) => {
        const iframe = getIframe();
        if (!iframe || !iframe.contentWindow) return;
        try {
            iframe.contentWindow.postMessage({
                type: 'mousemove',
                clientX: e.clientX,
                clientY: e.clientY,
            }, '*');
        } catch (err) {}
    });
}

// Esperar a que el shadow DOM de Spline esté disponible
let splineAttempts = 0;
const splineInterval = setInterval(() => {
    const viewer = document.getElementById('splineViewer');
    if (viewer && viewer.shadowRoot) {
        initSplineCursorFix();
        clearInterval(splineInterval);
    }
    if (++splineAttempts > 20) clearInterval(splineInterval);
}, 500);