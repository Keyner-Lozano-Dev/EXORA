<?php
header('Content-Type: text/html; charset=utf-8');
session_start();
include('../connection.php');

// Conexión
$con = connection();
if (!$con) {
    die('Error: No se pudo conectar a la base de datos');
}

// Si no hay sesión, usar usuario 1 como prueba
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
}
$id_usuario = $_SESSION['user_id'];

// Ruta real del archivo para que el fetch de JS apunte correctamente
$url_actual = htmlspecialchars($_SERVER['PHP_SELF']);

// ─── PROCESAMIENTO AJAX ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    $accion = $_POST['accion'] ?? '';

    // Marcar tarea como completada
    if ($accion === 'completar') {
        $id_tarea = intval($_POST['id'] ?? 0);
        if ($id_tarea > 0) {
            $stmt = mysqli_prepare($con, "UPDATE tareas SET completada = 1 WHERE id = ? AND id_usuario = ?");
            if (!$stmt) {
                echo json_encode(['success' => false, 'error' => 'Error prepare: ' . mysqli_error($con)]);
                exit;
            }
            mysqli_stmt_bind_param($stmt, "ii", $id_tarea, $id_usuario);
            if (mysqli_stmt_execute($stmt)) {
                echo json_encode(['success' => true, 'message' => 'Tarea marcada como completada']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Error execute: ' . mysqli_stmt_error($stmt)]);
            }
            mysqli_stmt_close($stmt);
        } else {
            echo json_encode(['success' => false, 'error' => 'ID inválido']);
        }
        exit;
    }

    // Crear nueva tarea
    if ($accion === 'crear') {
        $titulo       = trim($_POST['titulo'] ?? '');
        $descripcion  = trim($_POST['descripcion'] ?? '');
        $prioridad    = $_POST['prioridad'] ?? 'media';
        $categoria    = trim($_POST['categoria'] ?? '');
        $fecha_limite = $_POST['fecha_limite'] ?? '';
        $hora         = $_POST['hora'] ?: null;

        if (empty($titulo) || empty($fecha_limite)) {
            echo json_encode(['success' => false, 'error' => 'Título y fecha son obligatorios']);
            exit;
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_limite)) {
            echo json_encode(['success' => false, 'error' => 'Formato de fecha inválido']);
            exit;
        }

        $stmt = mysqli_prepare($con, "INSERT INTO tareas (id_usuario, titulo, descripcion, prioridad, categoria, fecha_limite, hora, completada, fecha_creacion) VALUES (?, ?, ?, ?, ?, ?, ?, 0, NOW())");
        if (!$stmt) {
            echo json_encode(['success' => false, 'error' => 'Error prepare INSERT: ' . mysqli_error($con)]);
            exit;
        }

        mysqli_stmt_bind_param($stmt, "issssss", $id_usuario, $titulo, $descripcion, $prioridad, $categoria, $fecha_limite, $hora);

        if (mysqli_stmt_execute($stmt)) {
            $nuevo_id = mysqli_stmt_insert_id($stmt);
            echo json_encode(['success' => true, 'message' => 'Tarea creada correctamente', 'id' => $nuevo_id]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error execute INSERT: ' . mysqli_stmt_error($stmt)]);
        }
        mysqli_stmt_close($stmt);
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Acción no reconocida']);
    exit;
}

// ─── OBTENER TAREAS DEL USUARIO ───────────────────────────────────────────────
$tareas = [];
$stmt = mysqli_prepare($con, "SELECT * FROM tareas WHERE id_usuario = ? ORDER BY fecha_limite ASC, hora ASC");
mysqli_stmt_bind_param($stmt, "i", $id_usuario);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($result)) {
    $tareas[] = $row;
}
mysqli_stmt_close($stmt);

// Separar pendientes y completadas
$tareas_pendientes  = array_filter($tareas, fn($t) => $t['completada'] == 0);
$tareas_completadas = array_filter($tareas, fn($t) => $t['completada'] == 1);

// ─── FUNCIONES AUXILIARES ─────────────────────────────────────────────────────
function mostrarPrioridad($prioridad) {
    switch (strtolower($prioridad)) {
        case 'alta':  return ['alta',  'Alta'];
        case 'media': return ['media', 'Media'];
        case 'baja':  return ['baja',  'Baja'];
        default:      return ['baja',  'Baja'];
    }
}

function formatearFechaHora($fecha_limite, $hora) {
    if (!$fecha_limite) return "Sin fecha";
    $fecha  = new DateTime($fecha_limite . ' ' . ($hora ?: '00:00:00'));
    $hoy    = new DateTime('today');
    $manana = new DateTime('tomorrow');
    if ($fecha->format('Y-m-d') === $hoy->format('Y-m-d'))   return "Hoy · " . $fecha->format('h:i A');
    if ($fecha->format('Y-m-d') === $manana->format('Y-m-d')) return "Mañana";
    return $fecha->format('d M');
}
?>

<!-- ─── TOPBAR ──────────────────────────────────────────────────────────────── -->
<div class="topbar">
    <div class="topbar-top">
        <div class="welcome">
            <h1>Tareas</h1>
        </div>
    </div>
    <div class="toolbar">
        <span class="toolbar-label">Acciones</span>
        <div class="tb-divider"></div>
        <button class="tool-btn purple" id="btnNuevaTarea">+ Nueva tarea</button>
        <button class="tool-btn orange" id="btnFiltrar">Filtrar</button>
        <div class="spacer"></div>
        <button class="tool-btn dark" id="btnExportar">Exportar</button>
    </div>
</div>

<!-- ─── FORMULARIO NUEVA TAREA ────────────────────────────────────────────────── -->
<div id="formNuevaTarea" style="display:none; border:1px solid #ccc; padding:20px; margin:20px 0; background-color:#f9f9f9; border-radius:8px;">
    <h3>Nueva Tarea</h3>
    <div>
        <label>Título:<br>
            <input type="text" id="f_titulo" required style="width:100%; padding:8px; margin:5px 0; box-sizing:border-box;">
        </label><br>

        <label>Descripción:<br>
            <textarea id="f_descripcion" style="width:100%; padding:8px; margin:5px 0; height:80px; box-sizing:border-box;"></textarea>
        </label><br>

        <label>Prioridad:<br>
            <select id="f_prioridad" style="width:100%; padding:8px; margin:5px 0; box-sizing:border-box;">
                <option value="alta">Alta</option>
                <option value="media" selected>Media</option>
                <option value="baja">Baja</option>
            </select>
        </label><br>

        <label>Categoría:<br>
            <input type="text" id="f_categoria" style="width:100%; padding:8px; margin:5px 0; box-sizing:border-box;">
        </label><br>

        <label>Fecha límite:<br>
            <input type="date" id="f_fecha_limite" required style="width:100%; padding:8px; margin:5px 0; box-sizing:border-box;">
        </label><br>

        <label>Hora:<br>
            <input type="time" id="f_hora" style="width:100%; padding:8px; margin:5px 0; box-sizing:border-box;">
        </label><br><br>

        <button type="button" id="btnCrearTarea" style="background:#7b2cbf; color:white; padding:10px 20px; border:none; border-radius:4px; cursor:pointer;">✓ Crear tarea</button>
        <button type="button" id="cancelarForm" style="background:#ccc; color:#333; padding:10px 20px; border:none; border-radius:4px; cursor:pointer; margin-left:10px;">✕ Cancelar</button>
    </div>
</div>

<!-- ─── LAYOUT PRINCIPAL ──────────────────────────────────────────────────────── -->
<div class="tareas-layout">

    <!-- Columna izquierda: pendientes + completadas -->
    <div class="tareas-col">

        <!-- PENDIENTES -->
        <div class="tareas-box">
            <div class="tareas-box-title">
                Pendientes
                <button class="tareas-add-btn" id="btnAgregarPendiente">+ Agregar</button>
            </div>

            <?php if (empty($tareas_pendientes)): ?>
                <p style="padding: 20px; color: #666;">No hay tareas pendientes.</p>
            <?php else: ?>
                <?php foreach ($tareas_pendientes as $tarea):
                    list($clase_prioridad, $texto_prioridad) = mostrarPrioridad($tarea['prioridad']);
                    $fecha_formateada = formatearFechaHora($tarea['fecha_limite'], $tarea['hora']);
                ?>
                <div class="tarea-item" data-id="<?= intval($tarea['id']) ?>">
                    <div class="tarea-check" title="Marcar como completada"></div>
                    <div class="tarea-info">
                        <h3><?= htmlspecialchars($tarea['titulo']) ?></h3>
                        <?php if (!empty($tarea['descripcion'])): ?>
                            <p><?= nl2br(htmlspecialchars($tarea['descripcion'])) ?></p>
                        <?php endif; ?>
                        <p><i class="fa-solid fa-calendar"></i> <?= $fecha_formateada ?></p>
                    </div>
                    <button class="btn-notification" title="Recordatorio">
                        <i class="fa-regular fa-bell"></i>
                    </button>
                    <span class="tarea-badge <?= $clase_prioridad ?>"><?= $texto_prioridad ?></span>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- COMPLETADAS -->
        <div class="tareas-box">
            <div class="tareas-box-title">Completadas</div>

            <?php if (empty($tareas_completadas)): ?>
                <p style="padding: 20px; color: #666;">No hay tareas completadas.</p>
            <?php else: ?>
                <?php foreach ($tareas_completadas as $tarea):
                    list($clase_prioridad, $texto_prioridad) = mostrarPrioridad($tarea['prioridad']);
                    $fecha_formateada = formatearFechaHora($tarea['fecha_limite'], $tarea['hora']);
                ?>
                <div class="tarea-item" data-id="<?= intval($tarea['id']) ?>">
                    <div class="tarea-check done" title="Tarea completada">
                        <i class="fa-solid fa-check"></i>
                    </div>
                    <div class="tarea-info">
                        <h3 class="tachado"><?= htmlspecialchars($tarea['titulo']) ?></h3>
                        <?php if (!empty($tarea['descripcion'])): ?>
                            <p><?= nl2br(htmlspecialchars($tarea['descripcion'])) ?></p>
                        <?php endif; ?>
                        <p>Completada · <?= $fecha_formateada ?></p>
                    </div>
                    <span class="tarea-badge <?= $clase_prioridad ?>"><?= $texto_prioridad ?></span>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>

    <!-- Columna derecha: resumen + categorías -->
    <div class="tareas-col">

        <!-- RESUMEN -->
        <div class="tareas-box">
            <div class="tareas-box-title">Resumen</div>
            <div class="tareas-stat-grid">
                <div class="tareas-stat">
                    <h2><?= count($tareas) ?></h2>
                    <p>Total tareas</p>
                </div>
                <div class="tareas-stat">
                    <h2><?= count($tareas_completadas) ?></h2>
                    <p>Completadas</p>
                </div>
                <div class="tareas-stat">
                    <h2><?= count(array_filter($tareas_pendientes, fn($t) => strtolower($t['prioridad']) === 'alta')) ?></h2>
                    <p>Alta prioridad</p>
                </div>
                <div class="tareas-stat">
                    <?php
                    $total    = count($tareas);
                    $progreso = $total > 0 ? round(count($tareas_completadas) * 100 / $total) : 0;
                    ?>
                    <h2><?= $progreso ?>%</h2>
                    <p>Progreso</p>
                </div>
            </div>
            <div class="tareas-prog-bar">
                <div class="tareas-prog-fill" style="width: <?= $progreso ?>%;"></div>
            </div>
        </div>

        <!-- CATEGORÍAS -->
        <div class="tareas-box">
            <div class="tareas-box-title">Por categoría</div>
            <?php
            $categorias = [];
            foreach ($tareas as $t) {
                $cat = !empty($t['categoria']) ? $t['categoria'] : 'Sin categoría';
                $categorias[$cat] = ($categorias[$cat] ?? 0) + 1;
            }
            $colores = [
                'Ventas'       => '#e04e1a',
                'Inventario'   => '#7b2cbf',
                'Proveedores'  => '#f5e100',
                'Precios'      => '#0e0e14',
                'Sin categoría'=> '#999',
            ];
            foreach ($categorias as $cat => $cant):
                $color = $colores[$cat] ?? '#666';
            ?>
            <div class="tareas-cat-item">
                <div class="tareas-cat-dot" style="background:<?= $color ?>;"></div>
                <span><?= htmlspecialchars($cat) ?></span>
                <small><?= $cant ?> tarea<?= $cant > 1 ? 's' : '' ?></small>
            </div>
            <?php endforeach; ?>
        </div>

    </div>
</div>

<!-- ─── ESTILOS ───────────────────────────────────────────────────────────────── -->
<style>
.btn-notification {
    background: none;
    border: none;
    cursor: pointer;
    margin-left: 10px;
    font-size: 18px;
    color: #555;
    transition: color 0.2s;
}
.btn-notification:hover { color: #7b2cbf; }

.tarea-check {
    width: 24px;
    height: 24px;
    min-width: 24px;
    border: 2px solid #0e0e14;
    border-radius: 4px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s, border-color 0.2s;
}
.tarea-check:hover:not(.done) { border-color: #7b2cbf; }
.tarea-check.done {
    background-color: #7b2cbf;
    border-color: #7b2cbf;
    color: white;
}

.tarea-badge {
    font-size: 12px;
    padding: 2px 8px;
    border-radius: 4px;
    white-space: nowrap;
}
.tarea-badge.alta  { background-color: #e04e1a; color: white; }
.tarea-badge.media { background-color: #7b2cbf; color: white; }
.tarea-badge.baja  { background-color: #f5e100; color: #333; }

.tachado { text-decoration: line-through; color: gray; }
</style>

<!-- ─── JAVASCRIPT ────────────────────────────────────────────────────────────── -->
<script>
// URL correcta del archivo, resuelta por PHP
const TAREAS_URL = '<?= $url_actual ?>';

// ── Mostrar / ocultar formulario ──────────────────────────────────────────────
function abrirForm() {
    document.getElementById('formNuevaTarea').style.display = 'block';
}

function cerrarForm() {
    document.getElementById('formNuevaTarea').style.display = 'none';
    document.getElementById('f_titulo').value       = '';
    document.getElementById('f_descripcion').value  = '';
    document.getElementById('f_prioridad').value    = 'media';
    document.getElementById('f_categoria').value    = '';
    document.getElementById('f_fecha_limite').value = '';
    document.getElementById('f_hora').value         = '';
}

document.getElementById('btnNuevaTarea').addEventListener('click', abrirForm);
document.getElementById('btnAgregarPendiente').addEventListener('click', abrirForm);
document.getElementById('cancelarForm').addEventListener('click', cerrarForm);

// Botones sin implementar aún
document.getElementById('btnFiltrar').addEventListener('click', () => {
    alert('Función de filtro aún no implementada');
});
document.getElementById('btnExportar').addEventListener('click', () => {
    alert('Función de exportar aún no implementada');
});

// ── Crear tarea ───────────────────────────────────────────────────────────────
document.getElementById('btnCrearTarea').addEventListener('click', () => {
    const titulo       = document.getElementById('f_titulo').value.trim();
    const descripcion  = document.getElementById('f_descripcion').value.trim();
    const prioridad    = document.getElementById('f_prioridad').value;
    const categoria    = document.getElementById('f_categoria').value.trim();
    const fecha_limite = document.getElementById('f_fecha_limite').value;
    const hora         = document.getElementById('f_hora').value;

    if (!titulo) {
        alert('⚠️ El título es obligatorio');
        return;
    }
    if (!fecha_limite) {
        alert('⚠️ La fecha límite es obligatoria');
        return;
    }

    const fd = new FormData();
    fd.append('accion',       'crear');
    fd.append('titulo',       titulo);
    fd.append('descripcion',  descripcion);
    fd.append('prioridad',    prioridad);
    fd.append('categoria',    categoria);
    fd.append('fecha_limite', fecha_limite);
    fd.append('hora',         hora);

    // Deshabilitar botón mientras se envía
    const btn = document.getElementById('btnCrearTarea');
    btn.disabled    = true;
    btn.textContent = 'Creando...';

    fetch(TAREAS_URL, { method: 'POST', body: fd })
        .then(res => res.text())
        .then(text => {
            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                console.error('Respuesta no es JSON:', text);
                alert('✗ Error en la respuesta del servidor. Revisa la consola.');
                btn.disabled    = false;
                btn.textContent = '✓ Crear tarea';
                return;
            }

            if (data.success) {
                cerrarForm();
                location.reload();
            } else {
                alert('✗ Error: ' + (data.error || 'No se pudo crear la tarea'));
                btn.disabled    = false;
                btn.textContent = '✓ Crear tarea';
            }
        })
        .catch(err => {
            console.error('Error fetch:', err);
            alert('✗ Error de conexión');
            btn.disabled    = false;
            btn.textContent = '✓ Crear tarea';
        });
});

// ── Marcar tarea como completada ──────────────────────────────────────────────
document.querySelectorAll('.tarea-check:not(.done)').forEach(check => {
    check.addEventListener('click', () => {
        const item    = check.closest('.tarea-item');
        const tareaId = item.dataset.id;

        if (!tareaId) {
            console.error('No se encontró el ID de la tarea');
            return;
        }

        // Feedback visual inmediato
        check.style.opacity = '0.5';
        check.style.cursor  = 'default';

        const fd = new FormData();
        fd.append('accion', 'completar');
        fd.append('id',     tareaId);

        fetch(TAREAS_URL, { method: 'POST', body: fd })
            .then(res => res.text())
            .then(text => {
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    console.error('Respuesta no es JSON:', text);
                    alert('✗ Error en la respuesta del servidor');
                    check.style.opacity = '1';
                    check.style.cursor  = 'pointer';
                    return;
                }

                if (data.success) {
                    check.classList.add('done');
                    check.innerHTML    = '<i class="fa-solid fa-check"></i>';
                    check.style.opacity = '1';
                    item.querySelector('h3').classList.add('tachado');
                    setTimeout(() => location.reload(), 800);
                } else {
                    alert('✗ Error: ' + (data.error || 'No se pudo actualizar'));
                    check.style.opacity = '1';
                    check.style.cursor  = 'pointer';
                }
            })
            .catch(err => {
                console.error('Error fetch completar:', err);
                alert('✗ Error de conexión');
                check.style.opacity = '1';
                check.style.cursor  = 'pointer';
            });
    });
});

// ── Botones de notificación / recordatorio ────────────────────────────────────
document.querySelectorAll('.btn-notification').forEach(btn => {
    btn.addEventListener('click', e => {
        e.stopPropagation();
        const titulo = btn.closest('.tarea-item').querySelector('h3').innerText;
        const icono  = btn.querySelector('i');
        const activo = icono.classList.contains('fa-solid');

        if (!activo) {
            icono.classList.remove('fa-regular');
            icono.classList.add('fa-solid');
            btn.style.color = '#7b2cbf';
            alert('🔔 Recordatorio activado para: ' + titulo);
        } else {
            icono.classList.remove('fa-solid');
            icono.classList.add('fa-regular');
            btn.style.color = '#555';
            alert('🔕 Recordatorio desactivado');
        }
    });
});
</script>