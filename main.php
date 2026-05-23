<?php
include('connection.php');
$con = connection();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="favicon.png">
    <title>EXORA | Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="CSS/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/lightweight-charts@3.8.0/dist/lightweight-charts.standalone.production.js"></script>
    <script>
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
        if (!titulo || !inicio || !fin) { alert('Completa título, hora inicio y hora fin.'); return; }
        if (inicio >= fin) { alert('La hora fin debe ser mayor a la hora inicio.'); return; }
        var btn = document.querySelector('.btn-guardar');
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando...';
        btn.disabled = true;
        fetch('secciones/guardar_cita.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ titulo: titulo, descripcion: desc, fecha: fechaActual, hora_inicio: inicio, hora_fin: fin, color: colorSeleccionado })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) { cerrarModal(); cargarSeccion('agenda', document.querySelector('.menu a.active')); }
            else { alert('Error: ' + data.message); }
        })
        .catch(() => alert('Error de conexión.'))
        .finally(() => { btn.innerHTML = '<i class="fa-solid fa-check"></i> Guardar'; btn.disabled = false; });
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
            if (data.success) { cargarSeccion('agenda', document.querySelector('.menu a.active')); }
            else { alert('Error al eliminar.'); }
        });
    }

    function cambiarFecha(dias) {
        var d = new Date(fechaActual);
        d.setDate(d.getDate() + dias);
        fechaActual = d.toISOString().split('T')[0];
        fetch('secciones/agenda.php?fecha=' + fechaActual)
            .then(r => r.text())
            .then(html => { document.getElementById('main-content').innerHTML = html; });
    }
    </script>
</head>
<body>
    <div class="deco deco-1"></div>
    <div class="deco deco-2"></div>
    <div class="deco deco-3"></div>
    <div class="deco deco-4"></div>
    <div class="deco deco-5"></div>
    <div class="deco deco-6"></div>
    <div class="deco deco-7"></div>

    <div class="dashboard-wrapper">
        <div class="sidebar">
            <div>
                <h1 class="logo">EXORA</h1>
                <div class="menu">
                    <a href="#" class="active" onclick="cargarSeccion('dashboard', this); return false;">
                        <i class="fa-solid fa-calendar-days"></i> Dashboard
                    </a>
                    <a href="#" onclick="cargarSeccion('agenda', this); return false;">
                        <i class="fa-solid fa-calendar-week"></i> Agenda
                    </a>
                    <a href="#" onclick="cargarSeccion('eventos', this); return false;">
                        <i class="fa-solid fa-clock"></i> Eventos
                    </a>
                    <a href="#" onclick="cargarSeccion('tareas', this); return false;">
                        <i class="fa-solid fa-list-check"></i> Tareas
                    </a>
                    <a href="#" onclick="cargarSeccion('recordatorios', this); return false;">
                        <i class="fa-solid fa-bell"></i> Recordatorios
                    </a>
                </div>
            </div>
            <a href="logout.php" class="logout">Cerrar Sesión</a>
        </div>
        <div class="main" id="main-content"></div>
    </div>

    <script src="JavaScript/dashboard.js"></script>
</body>
</html>