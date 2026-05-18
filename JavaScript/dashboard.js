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