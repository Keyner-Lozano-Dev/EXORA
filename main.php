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

    /* ===== AGENDA ===== */
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
        var btn = document.querySelector('#modalCita .btn-guardar');
        btn.disabled = true;
        fetch('secciones/guardar_cita.php', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ titulo, descripcion: desc, fecha: fechaActual, hora_inicio: inicio, hora_fin: fin, color: colorSeleccionado })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) { cerrarModal(); cargarSeccion('agenda', document.querySelector('.menu a.active')); }
            else { alert('Error: ' + data.message); }
        })
        .catch(() => alert('Error de conexión.'))
        .finally(() => { btn.disabled = false; });
    }
    function eliminarCita(id) {
        if (!confirm('¿Eliminar esta cita?')) return;
        fetch('secciones/eliminar_cita.php', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        })
        .then(r => r.json())
        .then(data => { if (data.success) cargarSeccion('agenda', document.querySelector('.menu a.active')); });
    }
    function cambiarFecha(dias) {
        var d = new Date(fechaActual);
        d.setDate(d.getDate() + dias);
        fechaActual = d.toISOString().split('T')[0];
        fetch('secciones/agenda.php?fecha=' + fechaActual)
            .then(r => r.text())
            .then(html => { document.getElementById('main-content').innerHTML = html; });
    }

    /* ===== CLIENTE ===== */
    function abrirModalCliente() { document.getElementById('modalCliente').style.display = 'flex'; }
    function cerrarModalCliente() {
        document.getElementById('modalCliente').style.display = 'none';
        document.getElementById('cl-nombre').value = '';
        document.getElementById('cl-email').value = '';
        document.getElementById('cl-telefono').value = '';
    }
    function guardarCliente() {
        var nombre = document.getElementById('cl-nombre').value.trim();
        var email = document.getElementById('cl-email').value.trim();
        var telefono = document.getElementById('cl-telefono').value.trim();
        if (!nombre) { alert('El nombre es obligatorio.'); return; }
        var btn = document.querySelector('#modalCliente .btn-guardar');
        btn.disabled = true;
        fetch('secciones/guardar_cliente.php', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ nombre, email, telefono })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) { cerrarModalCliente(); cargarSeccion('dashboard', document.querySelector('.menu a.active')); }
            else { alert('Error: ' + data.message); }
        })
        .catch(() => alert('Error de conexión.'))
        .finally(() => { btn.disabled = false; });
    }

    /* ===== TRANSACCION (VENTA + GASTO) ===== */
    var tabActual = 'venta';
    function abrirModalTransaccion(tab) {
        tabActual = tab || 'venta';
        document.getElementById('modalTransaccion').style.display = 'flex';
        cambiarTab(tabActual);
    }
    function cerrarModalTransaccion() {
        document.getElementById('modalTransaccion').style.display = 'none';
        document.getElementById('tr-desc').value = '';
        document.getElementById('tr-monto').value = '';
    }
    function cambiarTab(tab) {
        tabActual = tab;
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('tab-active'));
        document.getElementById('tab-' + tab).classList.add('tab-active');
        document.getElementById('modal-transaccion-titulo').textContent = tab === 'venta' ? 'Registrar Venta' : 'Registrar Gasto';
    }
    function guardarTransaccion() {
        var desc = document.getElementById('tr-desc').value.trim();
        var monto = document.getElementById('tr-monto').value.trim();
        if (!monto) { alert('El monto es obligatorio.'); return; }
        var url = tabActual === 'venta' ? 'secciones/guardar_venta.php' : 'secciones/guardar_gasto.php';
        var btn = document.querySelector('#modalTransaccion .btn-guardar');
        btn.disabled = true;
        fetch(url, {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ descripcion: desc, monto })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) { cerrarModalTransaccion(); cargarSeccion('dashboard', document.querySelector('.menu a.active')); }
            else { alert('Error: ' + data.message); }
        })
        .catch(() => alert('Error de conexión.'))
        .finally(() => { btn.disabled = false; });
    }

    /* ===== EXPORTAR ===== */
    function toggleExportMenu() {
        var menu = document.getElementById('exportMenu');
        var btn = document.getElementById('exportWrap').querySelector('button');
        var rect = btn.getBoundingClientRect();
        if (menu.style.display === 'none' || menu.style.display === '') {
            menu.style.top = (rect.bottom + 8) + 'px';
            menu.style.right = (window.innerWidth - rect.right) + 'px';
            menu.style.display = 'block';
        } else {
            menu.style.display = 'none';
        }
    }
    document.addEventListener('click', function(e) {
        var wrap = document.getElementById('exportWrap');
        if (wrap && !wrap.contains(e.target)) {
            var menu = document.getElementById('exportMenu');
            if (menu) menu.style.display = 'none';
        }
    });
    </script>
    <style>
    .modal-overlay{position:fixed;inset:0;background:rgba(14,14,20,.5);z-index:999;display:flex;align-items:center;justify-content:center;backdrop-filter:blur(4px)}
    .modal-box{background:var(--card);border:2px solid var(--black);border-radius:22px;box-shadow:8px 8px 0 var(--black);width:480px;max-width:95vw;overflow:hidden}
    .modal-header{padding:20px 24px;border-bottom:2px solid var(--line);display:flex;justify-content:space-between;align-items:center}
    .modal-header h2{font-size:18px;font-weight:800;color:var(--black);font-family:var(--font)}
    .modal-close{width:32px;height:32px;border-radius:8px;border:2px solid var(--black);background:var(--bg);cursor:pointer;font-size:14px;display:flex;align-items:center;justify-content:center;transition:all .15s}
    .modal-close:hover{background:var(--black);color:var(--card)}
    .modal-tabs{display:flex;border-bottom:2px solid var(--line);}
    .tab-btn{flex:1;padding:12px;border:none;background:transparent;font-family:var(--font);font-size:14px;font-weight:600;cursor:pointer;color:var(--muted);border-bottom:3px solid transparent;margin-bottom:-2px;transition:all .15s;}
    .tab-btn.tab-active{color:var(--black);border-bottom-color:var(--black);}
    .tab-btn:first-child{border-right:2px solid var(--line);}
    .modal-body{padding:24px;display:flex;flex-direction:column;gap:16px}
    .campo{display:flex;flex-direction:column;gap:6px;flex:1}
    .campo label{font-size:12px;font-weight:700;color:var(--black);font-family:var(--font);text-transform:uppercase;letter-spacing:.5px}
    .campo input,.campo textarea{padding:10px 14px;border:2px solid var(--black);border-radius:10px;background:var(--bg);font-family:var(--font);font-size:14px;color:var(--black);outline:none;transition:box-shadow .15s;resize:none}
    .campo input:focus{box-shadow:3px 3px 0 var(--black)}
    .campo-row{display:flex;gap:12px}
    .color-picker{display:flex;gap:10px;align-items:center}
    .color-opt{width:28px;height:28px;border-radius:50%;border:2px solid transparent;cursor:pointer;transition:all .15s;display:inline-block}
    .color-opt.selected{border:3px solid var(--black);box-shadow:2px 2px 0 var(--black);transform:scale(1.15)}
    .modal-footer{padding:16px 24px;border-top:2px solid var(--line);display:flex;justify-content:flex-end;gap:12px}
    .btn-cancelar{padding:10px 20px;border:2px solid var(--black);border-radius:10px;background:var(--bg);font-family:var(--font);font-size:14px;font-weight:600;cursor:pointer;transition:all .15s}
    .btn-cancelar:hover{background:var(--black);color:var(--card)}
    .btn-guardar{padding:10px 20px;border:2px solid var(--black);border-radius:10px;background:var(--black);color:var(--card);font-family:var(--font);font-size:14px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:8px;box-shadow:3px 3px 0 #6c63ff;transition:all .15s}
    .btn-guardar:hover{transform:translateY(-2px);box-shadow:5px 5px 0 #6c63ff}
    /* EXPORT MENU FIXED */
    #exportMenu{position:fixed;background:var(--card);border:2px solid var(--black);border-radius:12px;box-shadow:4px 4px 0 var(--black);overflow:hidden;z-index:1000;min-width:140px;display:none;}
    </style>
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
                        <i class="fa-solid fa-users"></i> Clientes
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

    <!-- EXPORT MENU FIXED (fuera del topbar) -->
    <div id="exportMenu">
        <a href="secciones/exportar.php?tipo=pdf" target="_blank" class="export-opt">
            <i class="fa-solid fa-file-pdf" style="color:#e04e1a;"></i> PDF
        </a>
        <a href="secciones/exportar.php?tipo=excel" target="_blank" class="export-opt">
            <i class="fa-solid fa-file-excel" style="color:#16a34a;"></i> Excel
        </a>
    </div>

    <!-- MODAL CITA -->
    <div class="modal-overlay" id="modalCita" style="display:none;">
        <div class="modal-box">
            <div class="modal-header">
                <h2>Nueva Cita</h2>
                <button class="modal-close" onclick="cerrarModal()"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="modal-body">
                <div class="campo"><label>Título</label><input type="text" id="input-titulo" placeholder="Ej: Reunión con clientes"></div>
                <div class="campo"><label>Descripción</label><textarea id="input-desc" placeholder="Detalles opcionales..." style="height:72px;border:2px solid var(--black);border-radius:10px;padding:10px 14px;background:var(--bg);font-family:var(--font);font-size:14px;resize:none;outline:none"></textarea></div>
                <div class="campo-row">
                    <div class="campo"><label>Hora inicio</label><input type="time" id="input-inicio"></div>
                    <div class="campo"><label>Hora fin</label><input type="time" id="input-fin"></div>
                </div>
                <div class="campo">
                    <label>Color</label>
                    <div class="color-picker">
                        <span class="color-opt selected" style="background:#6c63ff;" data-color="#6c63ff" onclick="seleccionarColor(this)"></span>
                        <span class="color-opt" style="background:#e04e1a;" data-color="#e04e1a" onclick="seleccionarColor(this)"></span>
                        <span class="color-opt" style="background:#7b2cbf;" data-color="#7b2cbf" onclick="seleccionarColor(this)"></span>
                        <span class="color-opt" style="background:#f5e100;" data-color="#f5e100" onclick="seleccionarColor(this)"></span>
                        <span class="color-opt" style="background:#16a34a;" data-color="#16a34a" onclick="seleccionarColor(this)"></span>
                        <span class="color-opt" style="background:#0ea5e9;" data-color="#0ea5e9" onclick="seleccionarColor(this)"></span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-cancelar" onclick="cerrarModal()">Cancelar</button>
                <button class="btn-guardar" onclick="guardarCita()"><i class="fa-solid fa-check"></i> Guardar</button>
            </div>
        </div>
    </div>

    <!-- MODAL CLIENTE -->
    <div class="modal-overlay" id="modalCliente" style="display:none;">
        <div class="modal-box">
            <div class="modal-header">
                <h2>Añadir Cliente</h2>
                <button class="modal-close" onclick="cerrarModalCliente()"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="modal-body">
                <div class="campo"><label>Nombre</label><input type="text" id="cl-nombre" placeholder="Nombre completo"></div>
                <div class="campo"><label>Email</label><input type="email" id="cl-email" placeholder="correo@ejemplo.com"></div>
                <div class="campo"><label>Teléfono</label><input type="tel" id="cl-telefono" placeholder="300 000 0000"></div>
            </div>
            <div class="modal-footer">
                <button class="btn-cancelar" onclick="cerrarModalCliente()">Cancelar</button>
                <button class="btn-guardar" onclick="guardarCliente()"><i class="fa-solid fa-check"></i> Guardar</button>
            </div>
        </div>
    </div>

    <!-- MODAL TRANSACCION (VENTA + GASTO) -->
    <div class="modal-overlay" id="modalTransaccion" style="display:none;">
        <div class="modal-box">
            <div class="modal-header">
                <h2 id="modal-transaccion-titulo">Registrar Venta</h2>
                <button class="modal-close" onclick="cerrarModalTransaccion()"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="modal-tabs">
                <button class="tab-btn tab-active" id="tab-venta" onclick="cambiarTab('venta')">
                    <i class="fa-solid fa-receipt"></i> Venta
                </button>
                <button class="tab-btn" id="tab-gasto" onclick="cambiarTab('gasto')">
                    <i class="fa-solid fa-arrow-trend-down"></i> Gasto
                </button>
            </div>
            <div class="modal-body">
                <div class="campo"><label>Descripción</label><input type="text" id="tr-desc" placeholder="Ej: Venta producto A"></div>
                <div class="campo"><label>Monto (COP)</label><input type="number" id="tr-monto" placeholder="0"></div>
            </div>
            <div class="modal-footer">
                <button class="btn-cancelar" onclick="cerrarModalTransaccion()">Cancelar</button>
                <button class="btn-guardar" onclick="guardarTransaccion()"><i class="fa-solid fa-check"></i> Guardar</button>
            </div>
        </div>
    </div>

    <script src="JavaScript/dashboard.js"></script>
</body>
</html>