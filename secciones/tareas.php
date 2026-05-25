<?php
session_start();
include('../connection.php');
$con = connection();
$id_usuario = $_SESSION['user_id'] ?? 0;

if (!$id_usuario) {
    header("Location: login.php");
    exit;
}

// Función para obtener tareas
function obtenerTareas($con, $id_usuario, $completada = 0) {
    $stmt = $con->prepare("SELECT id, titulo, descripcion, prioridad, categoria, fecha_limite, hora, completada, fecha_creacion FROM tareas WHERE id_usuario = ? AND completada = ? ORDER BY fecha_limite ASC, hora ASC");
    $stmt->bind_param("ii", $id_usuario, $completada);
    $stmt->execute();
    $result = $stmt->get_result();
    $tareas = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $tareas;
}

$tareas_pendientes = obtenerTareas($con, $id_usuario, 0);
$tareas_completadas = obtenerTareas($con, $id_usuario, 1);

function mostrarPrioridad($prioridad) {
    switch ($prioridad) {
        case 1: return ['alta', 'Alta'];
        case 2: return ['media', 'Media'];
        case 3: return ['baja', 'Baja'];
        default: return ['baja', 'Baja'];
    }
}

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

<!-- Aquí va todo tu HTML y estructura -->

<div class="topbar">
    <div class="topbar-top">
        <div class="welcome">
            <h1>Tareas</h1>
        </div>
        <?php if (!empty($_SESSION['foto'])): ?>
            <a href="secciones/perfil.php" class="profile" style="text-decoration:none; padding:0; overflow:hidden;">
                <img src="<?= $_SESSION['foto'] ?>" 
                     style="width:48px; height:48px; border-radius:50%; object-fit:cover; display:block; border:2px solid #0e0e14; box-shadow: 3px 3px 0 #0e0e14;">
            </a>
        <?php else: ?>
            <a href="secciones/perfil.php" class="profile" style="text-decoration:none;">
                <?= strtoupper(substr($_SESSION['username'], 0, 1)) ?>
            </a>
        <?php endif; ?>
    </div>
    <div class="toolbar">
        <span class="toolbar-label">Acciones</span>
        <div class="tb-divider"></div>
        <button class="tool-btn purple" id="btnNuevaTarea">
            <i class="fa-solid fa-plus"></i>
            Nueva tarea
        </button>
        <button class="tool-btn orange" id="btnFiltrar">
            <i class="fa-solid fa-filter"></i>
            Filtrar
        </button>
        <div class="spacer"></div>
        <button class="tool-btn dark" id="btnExportar">
            <i class="fa-solid fa-download"></i>
            Exportar
        </button>
    </div>
</div>

<div class="tareas-layout">

    <div class="tareas-col">

        <div class="tareas-box">
            <div class="tareas-box-title">
                Pendientes
                <button class="tareas-add-btn" id="btnAgregarPendiente">
                    <i class="fa-solid fa-plus"></i>
                    Agregar
                </button>
            </div>

            <?php foreach ($tareas_pendientes as $tarea): 
                list($clase_prioridad, $texto_prioridad) = mostrarPrioridad($tarea['prioridad']);
                $fecha_formateada = formatearFechaHora($tarea['fecha_limite'], $tarea['hora']);
            ?>
            <div class="tarea-item" data-id="<?= $tarea['id'] ?>">
                <div class="tarea-check"></div>
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
        </div>

        <div class="tareas-box">
            <div class="tareas-box-title">Completadas</div>

            <?php foreach ($tareas_completadas as $tarea): 
                list($clase_prioridad, $texto_prioridad) = mostrarPrioridad($tarea['prioridad']);
                $fecha_formateada = formatearFechaHora($tarea['fecha_limite'], $tarea['hora']);
            ?>
            <div class="tarea-item">
                <div class="tarea-check done" data-id="<?= $tarea['id'] ?>">
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
        </div>

    </div>

    <div class="tareas-col">

        <div class="tareas-box">
            <div class="tareas-box-title">Resumen</div>
            <div class="tareas-stat-grid">
                <div class="tareas-stat">
                    <h2><?= count($tareas_pendientes) + count($tareas_completadas) ?></h2>
                    <p>Total tareas</p>
                </div>
                <div class="tareas-stat">
                    <h2><?= count($tareas_completadas) ?></h2>
                    <p>Completadas</p>
                </div>
                <div class="tareas-stat">
                    <h2><?= count(array_filter($tareas_pendientes, fn($t) => $t['prioridad'] == 1)) ?></h2>
                    <p>Alta prioridad</p>
                </div>
                <div class="tareas-stat">
                    <?php 
                    $total = count($tareas_pendientes) + count($tareas_completadas);
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
            // Contar tareas por categoría
            $categorias = [];
            foreach (array_merge($tareas_pendientes, $tareas_completadas) as $t) {
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
</style>

<script>
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

        fetch('marcar_completada.php', {
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
