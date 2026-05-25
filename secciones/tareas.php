<?php
header('Content-Type: text/html; charset=utf-8');
session_start();
include('../connection.php');
$con = connection();

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}
$id_usuario = $_SESSION['user_id'];

// PROCESAMIENTO DE PETICIONES AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    
    $accion = $_POST['accion'] ?? '';
    
    // Marcar tarea como completada
    if ($accion === 'completar') {
        $id_tarea = intval($_POST['id'] ?? 0);
        if ($id_tarea > 0) {
            $stmt = $con->prepare("UPDATE tareas SET completada = 1 WHERE id = ? AND id_usuario = ?");
            $stmt->bind_param("ii", $id_tarea, $id_usuario);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Tarea marcada como completada']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Error al actualizar']);
            }
            $stmt->close();
        } else {
            echo json_encode(['success' => false, 'error' => 'ID inválido']);
        }
        exit;
    }
    
    // Crear nueva tarea
    if ($accion === 'crear') {
        $titulo = trim($_POST['titulo'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $prioridad = $_POST['prioridad'] ?? 'media';
        $categoria = trim($_POST['categoria'] ?? '');
        $fecha_limite = $_POST['fecha_limite'] ?? null;
        $hora = $_POST['hora'] ?? null;
        
        if (empty($titulo) || empty($fecha_limite)) {
            echo json_encode(['success' => false, 'error' => 'Título y fecha son obligatorios']);
            exit;
        }
        
        $stmt = $con->prepare("INSERT INTO tareas (id_usuario, titulo, descripcion, prioridad, categoria, fecha_limite, hora, completada, fecha_creacion) VALUES (?, ?, ?, ?, ?, ?, ?, 0, NOW())");
        $stmt->bind_param("issssss", $id_usuario, $titulo, $descripcion, $prioridad, $categoria, $fecha_limite, $hora);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Tarea creada correctamente']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error al crear tarea']);
        }
        $stmt->close();
        exit;
    }
    
    echo json_encode(['success' => false, 'error' => 'Acción no reconocida']);
    exit;
}

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
<div id="formNuevaTarea" style="display:none; border:1px solid #ccc; padding:20px; margin:20px 0; background-color:#f9f9f9; border-radius:8px;">
    <h3>Nueva Tarea</h3>
    <form id="formTarea">
        <label>Título:<br>
            <input type="text" name="titulo" required style="width:100%; padding:8px; margin:5px 0;">
        </label><br>
        <label>Descripción:<br>
            <textarea name="descripcion" style="width:100%; padding:8px; margin:5px 0; height:80px;"></textarea>
        </label><br>
        <label>Prioridad:<br>
            <select name="prioridad" required style="width:100%; padding:8px; margin:5px 0;">
                <option value="alta">Alta</option>
                <option value="media" selected>Media</option>
                <option value="baja">Baja</option>
            </select>
        </label><br>
        <label>Categoría:<br>
            <input type="text" name="categoria" style="width:100%; padding:8px; margin:5px 0;">
        </label><br>
        <label>Fecha límite:<br>
            <input type="date" name="fecha_limite" required style="width:100%; padding:8px; margin:5px 0;">
        </label><br>
        <label>Hora:<br>
            <input type="time" name="hora" style="width:100%; padding:8px; margin:5px 0;">
        </label><br><br>
        <button type="submit" style="background:#7b2cbf; color:white; padding:10px 20px; border:none; border-radius:4px; cursor:pointer;">✓ Crear tarea</button>
        <button type="button" id="cancelarForm" style="background:#ccc; color:#333; padding:10px 20px; border:none; border-radius:4px; cursor:pointer; margin-left:10px;">✕ Cancelar</button>
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
// Mostrar/ocultar formulario de nueva tarea
document.getElementById('btnNuevaTarea').addEventListener('click', (e) => {
    e.preventDefault();
    document.getElementById('formNuevaTarea').style.display = 'block';
});

document.getElementById('btnAgregarPendiente').addEventListener('click', (e) => {
    e.preventDefault();
    document.getElementById('formNuevaTarea').style.display = 'block';
});

document.getElementById('cancelarForm').addEventListener('click', (e) => {
    e.preventDefault();
    document.getElementById('formNuevaTarea').style.display = 'none';
    document.getElementById('formTarea').reset();
});

// Enviar formulario como AJAX
document.getElementById('formTarea').addEventListener('submit', (e) => {
    e.preventDefault();
    
    const formData = new FormData(document.getElementById('formTarea'));
    formData.append('accion', 'crear');
    
    fetch('tareas.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('✓ Tarea creada correctamente');
            document.getElementById('formTarea').reset();
            document.getElementById('formNuevaTarea').style.display = 'none';
            setTimeout(() => location.reload(), 500);
        } else {
            alert('✗ Error: ' + (data.error || 'No se pudo crear'));
        }
    })
    .catch(err => {
        console.error('Error:', err);
        alert('Error en la conexión');
    });
});

// Notificación con recordatorio
function inicializarNotificaciones() {
    document.querySelectorAll('.btn-notification').forEach((btn) => {
        const tareaItem = btn.closest('.tarea-item');
        const tareaId = tareaItem.dataset.id;
        const key = 'recordatorio_' + tareaId;
        
        // Restaurar estado
        if (localStorage.getItem(key) === 'true') {
            btn.querySelector('i').classList.remove('fa-regular');
            btn.querySelector('i').classList.add('fa-solid');
            btn.style.color = '#7b2cbf';
        }
        
        // Evento click
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            
            const activo = localStorage.getItem(key) === 'true';
            const titulo = tareaItem.querySelector('h3').innerText;
            
            if (!activo) {
                localStorage.setItem(key, 'true');
                btn.querySelector('i').classList.remove('fa-regular');
                btn.querySelector('i').classList.add('fa-solid');
                btn.style.color = '#7b2cbf';
                alert('🔔 Recordatorio activado: ' + titulo);
            } else {
                localStorage.setItem(key, 'false');
                btn.querySelector('i').classList.remove('fa-solid');
                btn.querySelector('i').classList.add('fa-regular');
                btn.style.color = '#555';
                alert('🔕 Recordatorio desactivado');
            }
        });
    });
}

// Marcar tarea como completada
function inicializarCheckbox() {
    document.querySelectorAll('.tarea-check').forEach(check => {
        if (check.classList.contains('done')) return;
        
        check.addEventListener('click', () => {
            const tareaItem = check.closest('.tarea-item');
            const tareaId = tareaItem.dataset.id;
            
            if (!tareaId) return;
            
            const formData = new FormData();
            formData.append('accion', 'completar');
            formData.append('id', tareaId);
            
            fetch('tareas.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    check.classList.add('done');
                    check.innerHTML = '<i class="fa-solid fa-check"></i>';
                    tareaItem.querySelector('h3').classList.add('tachado');
                    setTimeout(() => location.reload(), 400);
                } else {
                    alert('✗ Error: ' + (data.error || 'No se pudo actualizar'));
                }
            })
            .catch(err => {
                console.error('Error:', err);
                alert('Error en la conexión');
            });
        });
    });
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    inicializarNotificaciones();
    inicializarCheckbox();
});
</script>
