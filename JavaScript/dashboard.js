function cargarSeccion(seccion, elemento) {
    document.querySelectorAll('.menu a').forEach(a => a.classList.remove('active'));
    elemento.classList.add('active');

    document.getElementById('main-content').innerHTML = '<div style="padding:2rem">Cargando...</div>';

    fetch('secciones/' + seccion + '.php')
        .then(function(response) { return response.text(); })
        .then(function(html) {
            document.getElementById('main-content').innerHTML = html;
            if (seccion === 'dashboard') {
                setTimeout(iniciarGrafica, 300);
            }
        })
        .catch(function(error) {
            document.getElementById('main-content').innerHTML = '<div style="padding:2rem">Error cargando la seccion.</div>';
        });
}

function iniciarGrafica() {
    var el = document.getElementById('graficaVentas');
    if (!el) return;

    var datosGrafica = JSON.parse(el.getAttribute('data-grafica'));

    var chart = LightweightCharts.createChart(el, {
        width: el.offsetWidth,
        height: 350,
        layout: {
            background: { color: 'transparent' },
            textColor: '#888'
        },
        grid: {
            vertLines: { color: 'rgba(255,255,255,0.05)' },
            horzLines: { color: 'rgba(255,255,255,0.05)' }
        },
        crosshair: {
            mode: LightweightCharts.CrosshairMode.Normal
        },
        rightPriceScale: {
            borderColor: 'rgba(255,255,255,0.1)'
        },
        timeScale: {
            borderColor: 'rgba(255,255,255,0.1)',
            timeVisible: true
        }
    });

    var areaSeries = chart.addAreaSeries({
        lineColor: '#6c63ff',
        topColor: 'rgba(108, 99, 255, 0.4)',
        bottomColor: 'rgba(108, 99, 255, 0.0)',
        lineWidth: 2
    });

    if (datosGrafica.length > 0) {
        areaSeries.setData(datosGrafica);
        chart.timeScale().fitContent();
    } else {
        el.innerHTML = '<p style="text-align:center; padding:2rem; color:#888">Sin datos aun</p>';
    }

    window.addEventListener('resize', function() {
        chart.applyOptions({ width: el.offsetWidth });
    });
}

document.addEventListener('DOMContentLoaded', function() {
    var linkInicial = document.querySelector('.menu a.active');
    cargarSeccion('dashboard', linkInicial);
});

/* ===========================
   AGENDA
   =========================== */

var colorSeleccionado = '#6c63ff';
var fechaActual = new Date().toISOString().split('T')[0];

function abrirModal(hora) {
    document.getElementById('modalCita').style.display = 'flex';
    if (hora) {
        document.getElementById('input-inicio').value = hora;
        var partes = hora.split(':');
        var hFin = String(parseInt(partes[0]) + 1).padStart(2, '0');
        document.getElementById('input-fin').value = hFin + ':00';
    }
}

function cerrarModal() {
    document.getElementById('modalCita').style.display = 'none';
    document.getElementById('input-titulo').value = '';
    document.getElementById('input-desc').value = '';
    document.getElementById('input-inicio').value = '';
    document.getElementById('input-fin').value = '';
}

function seleccionarColor(el) {
    document.querySelectorAll('.color-opt').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');
    colorSeleccionado = el.getAttribute('data-color');
}

function guardarCita() {
    var titulo = document.getElementById('input-titulo').value.trim();
    var desc = document.getElementById('input-desc').value.trim();
    var inicio = document.getElementById('input-inicio').value;
    var fin = document.getElementById('input-fin').value;

    if (!titulo || !inicio || !fin) {
        alert('Completa título, hora inicio y hora fin.');
        return;
    }

    if (inicio >= fin) {
        alert('La hora fin debe ser mayor a la hora inicio.');
        return;
    }

    var btn = document.querySelector('.btn-guardar');
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando...';
    btn.disabled = true;

    fetch('secciones/guardar_cita.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            titulo: titulo,
            descripcion: desc,
            fecha: fechaActual,
            hora_inicio: inicio,
            hora_fin: fin,
            color: colorSeleccionado
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            cerrarModal();
            cargarSeccion('agenda', document.querySelector('.menu a.active'));
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(() => alert('Error de conexión.'))
    .finally(() => {
        btn.innerHTML = '<i class="fa-solid fa-check"></i> Guardar';
        btn.disabled = false;
    });
}

function eliminarCita(id) {
    if (!confirm('¿Eliminar esta cita?')) return;

    fetch('secciones/eliminar_cita.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            cargarSeccion('agenda', document.querySelector('.menu a.active'));
        } else {
            alert('Error al eliminar.');
        }
    });
}

function cambiarFecha(dias) {
    var d = new Date(fechaActual);
    d.setDate(d.getDate() + dias);
    fechaActual = d.toISOString().split('T')[0];

    fetch('secciones/agenda.php?fecha=' + fechaActual)
        .then(r => r.text())
        .then(html => {
            document.getElementById('main-content').innerHTML = html;
        });
}

// Leer fecha desde agenda-root cuando se carga la sección
var _cargarSeccionOriginal = cargarSeccion;
cargarSeccion = function(seccion, elemento) {
    _cargarSeccionOriginal(seccion, elemento);
    if (seccion === 'agenda') {
        setTimeout(function() {
            var root = document.getElementById('agenda-root');
            if (root) fechaActual = root.getAttribute('data-fecha');
        }, 400);
    }
};