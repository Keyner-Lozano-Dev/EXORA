<?php
session_start();
include('../connection.php');
$con = connection();

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: ../index.php"); // Redirigir al login si no está autenticado
    exit;
}
$id_usuario = $_SESSION['user_id'];

// Obtener tareas del usuario
$tareas = [];
$stmt = $con->prepare("SELECT * FROM tareas WHERE id_usuario = ? ORDER BY fecha_limite ASC, hora ASC");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $tareas[] = $row;
}
$stmt->close();

// Separar tareas pendientes y completadas
$tareas_pendientes = array_filter($tareas, fn($t) => $t['completada'] == 0);
$tareas_completadas = array_filter($tareas, fn($t) => $t['completada'] == 1);

// Función para mostrar prioridad con clase y texto
function mostrarPrioridad($prioridad) {
    switch (strtolower($prioridad)) {
        case 'alta': return ['alta', 'Alta'];
        case 'media': return ['media', 'Media'];
        case 'baja': return ['baja', 'Baja'];
        default: return ['baja', 'Baja'];
    }
}

// Formatear fecha y hora
function formatearFechaHora($fecha_limite, $hora) {
    if (!$fecha_limite) return "Sin fecha";
    $fecha = new DateTime($fecha_limite . ' ' . ($hora ?: '00:00:00'));
    $hoy = new DateTime('today');
    $manana = new DateTime('tomorrow');

    if ($fecha->format('Y-m-d') == $hoy->format('Y-m-d')) {
        return "Hoy · " . $fecha->format('h:i A');
    } elseif ($fecha->format('Y-m-d') == $manana->format('Y-m-d')) {
        return "Mañana";
    } else {
        return $fecha->format('d M');
    }
}
?>

<div class="topbar">
    <div class="topbar-top">
        <div class="welcome">
            <h1>Tareas</h1>
        </div>
        <!-- Aquí puedes agregar perfil o avatar si tienes -->
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

<!-- Formulario para nueva tarea (oculto inicialmente) -->
<div id="formNuevaTarea" style="display:none; border:1px solid #ccc; padding:20px; margin:20px 0;">
    <h3>Nueva Tarea</h3>
    <form id="formTarea" method="POST" action="crear_tarea.php">
        <label>Título:<br>
            <input type="text" name="titulo" required>
        </label><br><br>
        <label>Descripción:<br>
            <textarea name="descripcion"></textarea>
        </label><br><br>
        <label>Prioridad:<br>
            <select name="prioridad" required>
                <option value="alta">Alta</option>
                <option value="media" selected>Media</option>
                <option value="baja">Baja</option>
            </select>
        </label><br><br>
        <label>Categoría:<br>
            <input type="text" name="categoria">
        </label><br><br>
        <label>Fecha límite:<br>
            <input type="date" name="fecha_limite" required>
        </label><br><br>
        <label>Hora:<br>
            <input type="time" name="hora">
        </label><br><br>
        <button type="submit">Crear tarea</button>
        <button type="button" id="cancelarForm">Cancelar</button>
    </form>
</div>

<div class="tareas-layout">

    <div class="tareas-col">
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
                <div class="tarea-item" data-id="<?= $tarea['id'] ?>">
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

        <div class="tareas-box">
            <div class="tareas-box-title">Completadas</div>

            <?php if (empty($tareas_completadas)): ?>
                <p style="padding: 20px; color: #666;">No hay tareas completadas.</p>
            <?php else: ?>
                <?php foreach ($tareas_completadas as $tarea):
                    list($clase_prioridad, $texto_prioridad) = mostrarPrioridad($tarea['prioridad']);
                    $fecha_formateada = formatearFechaHora($tarea['fecha_limite'], $tarea['hora']);
                ?>
                <div class="tarea-item" data-id="<?= $tarea['id'] ?>">
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

    <div class="tareas-col">
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
                    <h2><?= count(array_filter($tareas_pendientes, fn($t) => strtolower($t['prioridad']) == 'alta')) ?></h2>
                    <p>Alta prioridad</p>
                </div>
                <div class="tareas-stat">
                    <?php 
                    $total = count($tareas);
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

        <div class="tareas-box">
            <div class="tareas-box-title">Por categoría</div>
            <?php
            $categorias = [];
            foreach ($tareas as $t) {
                $cat = $t['categoria'] ?: 'Sin categoría';
                if (!isset($categorias[$cat])) $categorias[$cat] = 0;
                $categorias[$cat]++;
            }
            $colores = ['Ventas' => '#e04e1a', 'Inventario' => '#7b2cbf', 'Proveedores' => '#f5e100', 'Precios' => '#0e0e14', 'Sin categoría' => '#999'];
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

<style>
/* Estilos básicos, adapta según tu CSS */
.btn-notification {
    background: none;
    border: none;
    cursor: pointer;
    margin-left: 10px;
    font-size: 18px;
    color: #555;
}
.btn-notification:hover {
    color: #7b2cbf;
}
.tarea-check {
    width: 24px;
    height: 24px;
    border: 2px solid #0e0e14;
    border-radius: 4px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}
.tarea-check.done {
    background-color: #7b2cbf;
    color: white;
}
.tarea-check.done i {
    display: block;
}
.tarea-check i {
    display: none;
}
.tarea-badge.alta {
    background-color: #e04e1a;
    color: white;
    padding: 2px 8px;
    border-radius: 4px;
}
.tarea-badge.media {
    background-color: #7b2cbf;
    color: white;
    padding: 2px 8px;
    border-radius: 4px;
}
.tarea-badge.baja {
    background-color: #f5e100;
    color: black;
    padding: 2px 8px;
    border-radius: 4px;
}
.tachado {
    text-decoration: line-through;
    color: gray;
}
</style>

<script>
document.getElementById('btnNuevaTarea').addEventListener('click', () => {
    document.getElementById('formNuevaTarea').style.display = 'block';
});
document.getElementById('btnAgregarPendiente').addEventListener('click', () => {
    document.getElementById('formNuevaTarea').style.display = 'block';
});
document.getElementById('cancelarForm').addEventListener('click', () => {
    document.getElementById('formNuevaTarea').style.display = 'none';
});

// Campanita recordatorio con localStorage
document.querySelectorAll('.btn-notification').forEach((btn) => {
    btn.addEventListener('click', () => {
        const tareaItem = btn.closest('.tarea-item');
        const tareaTitulo = tareaItem.querySelector('h3').innerText;
        const tareaHora = tareaItem.querySelector('p').innerText;

        const key = 'recordatorio_tarea_' + tareaItem.dataset.id;
        const activo = localStorage.getItem(key) === 'true';

        if (!activo) {
            localStorage.setItem(key, 'true');
            btn.querySelector('i').classList.remove('fa-regular');
            btn.querySelector('i').classList.add('fa-solid');
            alert(`Recordatorio activado para: ${tareaTitulo} (${tareaHora})`);
        } else {
            localStorage.setItem(key, 'false');
            btn.querySelector('i').classList.remove('fa-solid');
            btn.querySelector('i').classList.add('fa-regular');
            alert(`Recordatorio desactivado para: ${tareaTitulo}`);
        }
    });

    const tareaItem = btn.closest('.tarea-item');
    const key = 'recordatorio_tarea_' + tareaItem.dataset.id;
    if (localStorage.getItem(key) === 'true') {
        btn.querySelector('i').classList.remove('fa-regular');
        btn.querySelector('i').classList.add('fa-solid');
    }
});

// Marcar tarea como completada con AJAX
document.querySelectorAll('.tarea-check').forEach(check => {
    check.addEventListener('click', () => {
        const tareaItem = check.closest('.tarea-item');
        const tareaId = tareaItem.dataset.id;
        if (!tareaId) return;

        fetch('./marcar_completada.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'id=' + encodeURIComponent(tareaId)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                check.classList.add('done');
                check.innerHTML = '<i class="fa-solid fa-check"></i>';
                tareaItem.querySelector('h3').classList.add('tachado');
                location.reload();
            } else {
                alert('Error al marcar tarea como completada');
            }
        })
        .catch(() => alert('Error en la conexión'));
    });
});
</script>
