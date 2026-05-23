<?php
include('../connection.php');
$con = connection();

// Obtener usuario de sesión
session_start();
$usuario_id = $_SESSION['user_id'] ?? 0;

// Fecha actual o la seleccionada
$fecha = $_GET['fecha'] ?? date('Y-m-d');

// Obtener citas del día
$citas = [];
if ($usuario_id) {
    $stmt = $con->prepare("SELECT * FROM citas WHERE usuario_id = ? AND fecha = ? ORDER BY hora_inicio ASC");
    $stmt->bind_param("is", $usuario_id, $fecha);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $citas[] = $row;
    }
}

// Organizar citas por hora
$citasPorHora = [];
foreach ($citas as $cita) {
    $hora = (int)explode(':', $cita['hora_inicio'])[0];
    $citasPorHora[$hora][] = $cita;
}
?>

<!-- TOPBAR -->
<div class="topbar">
    <div class="welcome">
        <h1>Agenda</h1>
        <p>Organización y planificación EXORA</p>
    </div>
    <div class="profile">E</div>
</div>

<!-- CONTENIDO AGENDA -->
<div class="agenda-section">

    <!-- HEADER AGENDA -->
    <div class="agenda-toolbar">
        <div class="agenda-fecha-nav">
            <button class="btn-nav" onclick="cambiarFecha(-1)">
                <i class="fa-solid fa-chevron-left"></i>
            </button>
            <span class="fecha-actual" id="fechaDisplay">
                <?php echo date('d \d\e F \d\e Y', strtotime($fecha)); ?>
            </span>
            <button class="btn-nav" onclick="cambiarFecha(1)">
                <i class="fa-solid fa-chevron-right"></i>
            </button>
        </div>
        <button class="btn-nueva-cita" onclick="abrirModal()">
            <i class="fa-solid fa-plus"></i>
            Nueva Cita
        </button>
    </div>

    <!-- CUADRÍCULA DE HORAS -->
    <div class="agenda-grid-wrapper">
        <div class="agenda-grid">
            <?php for ($h = 6; $h <= 22; $h++): ?>
                <?php
                $horaStr = str_pad($h, 2, '0', STR_PAD_LEFT) . ':00';
                $horaDisplay = $h < 12 ? $horaStr . ' AM' : ($h == 12 ? '12:00 PM' : str_pad($h - 12, 2, '0', STR_PAD_LEFT) . ':00 PM');
                $tieneCitas = isset($citasPorHora[$h]);
                ?>
                <div class="hora-fila <?php echo $tieneCitas ? 'tiene-citas' : ''; ?>">
                    <div class="hora-label"><?php echo $horaDisplay; ?></div>
                    <div class="hora-contenido">
                        <?php if ($tieneCitas): ?>
                            <?php foreach ($citasPorHora[$h] as $cita): ?>
                                <div class="cita-bloque" style="border-left: 4px solid <?php echo htmlspecialchars($cita['color']); ?>">
                                    <div class="cita-titulo"><?php echo htmlspecialchars($cita['titulo']); ?></div>
                                    <div class="cita-horario">
                                        <?php echo substr($cita['hora_inicio'], 0, 5); ?> - <?php echo substr($cita['hora_fin'], 0, 5); ?>
                                    </div>
                                    <?php if ($cita['descripcion']): ?>
                                        <div class="cita-desc"><?php echo htmlspecialchars($cita['descripcion']); ?></div>
                                    <?php endif; ?>
                                    <button class="btn-eliminar-cita" onclick="eliminarCita(<?php echo $cita['id']; ?>)">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <button class="btn-add-hora" onclick="abrirModal('<?php echo $horaStr; ?>')">
                            <i class="fa-solid fa-plus"></i>
                        </button>
                    </div>
                </div>
            <?php endfor; ?>
        </div>
    </div>
</div>

<!-- MODAL NUEVA CITA -->
<div class="modal-overlay" id="modalCita" style="display:none;">
    <div class="modal-box">
        <div class="modal-header">
            <h2>Nueva Cita</h2>
            <button class="modal-close" onclick="cerrarModal()">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="campo">
                <label>Título</label>
                <input type="text" id="input-titulo" placeholder="Ej: Reunión con clientes">
            </div>
            <div class="campo">
                <label>Descripción</label>
                <textarea id="input-desc" placeholder="Detalles opcionales..."></textarea>
            </div>
            <div class="campo-row">
                <div class="campo">
                    <label>Hora inicio</label>
                    <input type="time" id="input-inicio">
                </div>
                <div class="campo">
                    <label>Hora fin</label>
                    <input type="time" id="input-fin">
                </div>
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
            <button class="btn-guardar" onclick="guardarCita()">
                <i class="fa-solid fa-check"></i>
                Guardar
            </button>
        </div>
    </div>
</div>

<style>
/* ===== AGENDA SECTION ===== */
.agenda-section {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.agenda-toolbar {
    background: var(--card);
    border: 2px solid var(--black);
    border-radius: 18px;
    box-shadow: 5px 5px 0 var(--black);
    padding: 16px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
}

.agenda-fecha-nav {
    display: flex;
    align-items: center;
    gap: 16px;
}

.btn-nav {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    border: 2px solid var(--black);
    background: var(--bg);
    cursor: pointer;
    font-size: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s;
    box-shadow: 2px 2px 0 var(--black);
}

.btn-nav:hover {
    background: var(--black);
    color: var(--card);
    transform: translateY(-1px);
}

.fecha-actual {
    font-weight: 700;
    font-size: 15px;
    color: var(--black);
    font-family: var(--font);
    min-width: 200px;
    text-align: center;
}

.btn-nueva-cita {
    background: var(--black);
    color: var(--card);
    border: 2px solid var(--black);
    border-radius: 12px;
    padding: 10px 20px;
    font-family: var(--font);
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    box-shadow: 3px 3px 0 #7b2cbf;
    transition: all 0.15s;
}

.btn-nueva-cita:hover {
    transform: translateY(-2px);
    box-shadow: 5px 5px 0 #7b2cbf;
}

/* ===== GRID ===== */
.agenda-grid-wrapper {
    background: var(--card);
    border: 2px solid var(--black);
    border-radius: 18px;
    box-shadow: 5px 5px 0 var(--black);
    overflow: hidden;
}

.agenda-grid {
    display: flex;
    flex-direction: column;
}

.hora-fila {
    display: flex;
    align-items: stretch;
    border-bottom: 1.5px solid var(--line);
    min-height: 64px;
    transition: background 0.15s;
}

.hora-fila:last-child {
    border-bottom: none;
}

.hora-fila:hover {
    background: var(--bg);
}

.hora-label {
    width: 100px;
    flex-shrink: 0;
    padding: 12px 16px;
    font-size: 12px;
    font-weight: 700;
    color: var(--muted);
    font-family: var(--font);
    border-right: 2px solid var(--line);
    display: flex;
    align-items: center;
}

.hora-contenido {
    flex: 1;
    padding: 8px 12px;
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    position: relative;
}

.cita-bloque {
    background: var(--bg);
    border: 1.5px solid var(--black);
    border-radius: 10px;
    padding: 8px 12px;
    flex: 1;
    min-width: 180px;
    position: relative;
    box-shadow: 2px 2px 0 var(--black);
}

.cita-titulo {
    font-weight: 700;
    font-size: 13px;
    color: var(--black);
    font-family: var(--font);
}

.cita-horario {
    font-size: 11px;
    color: var(--muted);
    margin-top: 2px;
    font-family: var(--font);
}

.cita-desc {
    font-size: 12px;
    color: var(--muted);
    margin-top: 4px;
    font-family: var(--font);
}

.btn-eliminar-cita {
    position: absolute;
    top: 6px;
    right: 6px;
    background: none;
    border: none;
    cursor: pointer;
    color: var(--muted);
    font-size: 12px;
    padding: 2px 4px;
    border-radius: 4px;
    transition: all 0.15s;
}

.btn-eliminar-cita:hover {
    background: #fecaca;
    color: #dc2626;
}

.btn-add-hora {
    width: 28px;
    height: 28px;
    border-radius: 8px;
    border: 1.5px dashed var(--muted);
    background: transparent;
    cursor: pointer;
    font-size: 12px;
    color: var(--muted);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: all 0.15s;
    flex-shrink: 0;
}

.hora-fila:hover .btn-add-hora {
    opacity: 1;
}

.btn-add-hora:hover {
    border-color: var(--black);
    color: var(--black);
    background: var(--card);
}

/* ===== MODAL ===== */
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(14,14,20,0.5);
    z-index: 999;
    display: flex;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(4px);
}

.modal-box {
    background: var(--card);
    border: 2px solid var(--black);
    border-radius: 22px;
    box-shadow: 8px 8px 0 var(--black);
    width: 480px;
    max-width: 95vw;
    overflow: hidden;
}

.modal-header {
    padding: 20px 24px;
    border-bottom: 2px solid var(--line);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h2 {
    font-size: 18px;
    font-weight: 800;
    color: var(--black);
    font-family: var(--font);
}

.modal-close {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: 2px solid var(--black);
    background: var(--bg);
    cursor: pointer;
    font-size: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s;
}

.modal-close:hover {
    background: var(--black);
    color: var(--card);
}

.modal-body {
    padding: 24px;
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.campo {
    display: flex;
    flex-direction: column;
    gap: 6px;
    flex: 1;
}

.campo label {
    font-size: 12px;
    font-weight: 700;
    color: var(--black);
    font-family: var(--font);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.campo input,
.campo textarea {
    padding: 10px 14px;
    border: 2px solid var(--black);
    border-radius: 10px;
    background: var(--bg);
    font-family: var(--font);
    font-size: 14px;
    color: var(--black);
    outline: none;
    transition: box-shadow 0.15s;
    resize: none;
}

.campo textarea {
    height: 72px;
}

.campo input:focus,
.campo textarea:focus {
    box-shadow: 3px 3px 0 var(--black);
}

.campo-row {
    display: flex;
    gap: 12px;
}

.color-picker {
    display: flex;
    gap: 10px;
    align-items: center;
}

.color-opt {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    border: 2px solid transparent;
    cursor: pointer;
    transition: all 0.15s;
    display: inline-block;
}

.color-opt.selected {
    border: 3px solid var(--black);
    box-shadow: 2px 2px 0 var(--black);
    transform: scale(1.15);
}

.modal-footer {
    padding: 16px 24px;
    border-top: 2px solid var(--line);
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}

.btn-cancelar {
    padding: 10px 20px;
    border: 2px solid var(--black);
    border-radius: 10px;
    background: var(--bg);
    font-family: var(--font);
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s;
}

.btn-cancelar:hover {
    background: var(--black);
    color: var(--card);
}

.btn-guardar {
    padding: 10px 20px;
    border: 2px solid var(--black);
    border-radius: 10px;
    background: var(--black);
    color: var(--card);
    font-family: var(--font);
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    box-shadow: 3px 3px 0 #6c63ff;
    transition: all 0.15s;
}

.btn-guardar:hover {
    transform: translateY(-2px);
    box-shadow: 5px 5px 0 #6c63ff;
}
</style>

<script>
var fechaActual = '<?php echo $fecha; ?>';
var colorSeleccionado = '#6c63ff';

function abrirModal(hora) {
    document.getElementById('modalCita').style.display = 'flex';
    if (hora) {
        document.getElementById('input-inicio').value = hora;
        // Sugerir hora fin +1
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
</script>