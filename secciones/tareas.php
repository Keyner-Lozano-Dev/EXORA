<?php
// ─── PROTECCIÓN: este archivo solo procesa POST si es llamado directamente ────
// Si viene un POST con 'accion', lo procesamos sin importar cómo fue incluido
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    header('Content-Type: application/json; charset=utf-8');

    // Iniciar sesión si no está iniciada
    if (session_status() === PHP_SESSION_NONE) session_start();

    include_once(__DIR__ . '/../connection.php');
    $con = connection();

    $id_usuario = $_SESSION['user_id'] ?? 1;
    $accion     = $_POST['accion'];

    // ── Crear tarea ──────────────────────────────────────────────────────────
    if ($accion === 'crear') {
        $titulo       = trim($_POST['titulo']       ?? '');
        $descripcion  = trim($_POST['descripcion']  ?? '');
        $prioridad    = $_POST['prioridad']          ?? 'media';
        $categoria    = trim($_POST['categoria']    ?? '');
        $fecha_limite = $_POST['fecha_limite']       ?? '';
        $hora         = !empty($_POST['hora']) ? $_POST['hora'] : null;

        if (empty($titulo)) {
            echo json_encode(['success' => false, 'error' => 'El título es obligatorio']);
            exit;
        }
        if (empty($fecha_limite) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_limite)) {
            echo json_encode(['success' => false, 'error' => 'La fecha límite es obligatoria y debe tener formato correcto']);
            exit;
        }

        $stmt = mysqli_prepare($con,
            "INSERT INTO tareas (id_usuario, titulo, descripcion, prioridad, categoria, fecha_limite, hora, completada, fecha_creacion)
             VALUES (?, ?, ?, ?, ?, ?, ?, 0, NOW())"
        );

        if (!$stmt) {
            echo json_encode(['success' => false, 'error' => 'Error BD: ' . mysqli_error($con)]);
            exit;
        }

        mysqli_stmt_bind_param($stmt, 'issssss',
            $id_usuario, $titulo, $descripcion, $prioridad, $categoria, $fecha_limite, $hora
        );

        if (mysqli_stmt_execute($stmt)) {
            $nuevo_id = mysqli_stmt_insert_id($stmt);
            mysqli_stmt_close($stmt);
            echo json_encode(['success' => true, 'id' => $nuevo_id]);
        } else {
            $err = mysqli_stmt_error($stmt);
            mysqli_stmt_close($stmt);
            echo json_encode(['success' => false, 'error' => 'Error al guardar: ' . $err]);
        }
        exit;
    }

    // ── Completar tarea ──────────────────────────────────────────────────────
    if ($accion === 'completar') {
        $id_tarea = intval($_POST['id'] ?? 0);

        if ($id_tarea <= 0) {
            echo json_encode(['success' => false, 'error' => 'ID de tarea inválido']);
            exit;
        }

        $stmt = mysqli_prepare($con,
            "UPDATE tareas SET completada = 1 WHERE id = ? AND id_usuario = ?"
        );

        if (!$stmt) {
            echo json_encode(['success' => false, 'error' => 'Error BD: ' . mysqli_error($con)]);
            exit;
        }

        mysqli_stmt_bind_param($stmt, 'ii', $id_tarea, $id_usuario);

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            echo json_encode(['success' => true]);
        } else {
            $err = mysqli_stmt_error($stmt);
            mysqli_stmt_close($stmt);
            echo json_encode(['success' => false, 'error' => $err]);
        }
        exit;
    }

    // ── Exportar tareas (CSV) ────────────────────────────────────────────────
    if ($accion === 'exportar') {
        $stmt = mysqli_prepare($con,
            "SELECT titulo, descripcion, prioridad, categoria, fecha_limite, hora, completada, fecha_creacion
             FROM tareas WHERE id_usuario = ? ORDER BY fecha_limite ASC"
        );
        mysqli_stmt_bind_param($stmt, 'i', $id_usuario);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $filas = [];
        $filas[] = ['Título','Descripción','Prioridad','Categoría','Fecha límite','Hora','Completada','Fecha creación'];
        while ($row = mysqli_fetch_assoc($result)) {
            $filas[] = [
                $row['titulo'],
                $row['descripcion'] ?? '',
                $row['prioridad'],
                $row['categoria'] ?? '',
                $row['fecha_limite'] ?? '',
                $row['hora'] ?? '',
                $row['completada'] ? 'Sí' : 'No',
                $row['fecha_creacion'] ?? '',
            ];
        }
        mysqli_stmt_close($stmt);

        echo json_encode(['success' => true, 'filas' => $filas]);
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Acción no reconocida']);
    exit;
}

// ─── CARGA NORMAL (GET): obtener datos y renderizar HTML ─────────────────────
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($con)) {
    include_once(__DIR__ . '/../connection.php');
    $con = connection();
}

$id_usuario = $_SESSION['user_id'] ?? 1;

// Ruta absoluta para el fetch — siempre apunta al archivo correcto
$tareas_url = '/secciones/tareas.php';

// Traer todas las tareas
$tareas = [];
$stmt   = mysqli_prepare($con,
    "SELECT * FROM tareas WHERE id_usuario = ? ORDER BY fecha_limite ASC, hora ASC"
);
mysqli_stmt_bind_param($stmt, 'i', $id_usuario);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($result)) {
    $tareas[] = $row;
}
mysqli_stmt_close($stmt);

$tareas_pendientes  = array_filter($tareas, fn($t) => $t['completada'] == 0);
$tareas_completadas = array_filter($tareas, fn($t) => $t['completada'] == 1);
$total              = count($tareas);
$progreso           = $total > 0 ? round(count($tareas_completadas) * 100 / $total) : 0;

// Categorías para el panel lateral
$categorias = [];
foreach ($tareas as $t) {
    $cat = !empty($t['categoria']) ? $t['categoria'] : 'Sin categoría';
    $categorias[$cat] = ($categorias[$cat] ?? 0) + 1;
}

$colores_cat = [
    'Ventas'        => '#e04e1a',
    'Inventario'    => '#7b2cbf',
    'Proveedores'   => '#f5e100',
    'Precios'       => '#0e0e14',
    'Sin categoría' => '#999',
];

// ── Helpers ──────────────────────────────────────────────────────────────────
function mostrarPrioridad($p) {
    return match(strtolower((string)$p)) {
        'alta'  => ['alta',  'Alta'],
        'media' => ['media', 'Media'],
        default => ['baja',  'Baja'],
    };
}

function formatearFechaHora($fecha, $hora) {
    if (!$fecha) return 'Sin fecha';
    $dt     = new DateTime($fecha . ' ' . ($hora ?: '00:00:00'));
    $hoy    = new DateTime('today');
    $manana = new DateTime('tomorrow');
    if ($dt->format('Y-m-d') === $hoy->format('Y-m-d'))    return 'Hoy · ' . $dt->format('h:i A');
    if ($dt->format('Y-m-d') === $manana->format('Y-m-d')) return 'Mañana';
    return $dt->format('d M Y');
}

// Tareas con fecha próxima (para campanita)
$alertas = [];
$ahora   = new DateTime();
foreach ($tareas_pendientes as $t) {
    if (!$t['fecha_limite']) continue;
    $dt   = new DateTime($t['fecha_limite'] . ' ' . ($t['hora'] ?: '23:59:00'));
    $diff = $ahora->diff($dt);
    // Dentro de las próximas 24 horas y no vencida
    if (!$diff->invert && $diff->days == 0) {
        $alertas[] = [
            'id'     => $t['id'],
            'titulo' => $t['titulo'],
            'cuando' => formatearFechaHora($t['fecha_limite'], $t['hora']),
        ];
    }
}
?>

<!-- ─── TOPBAR ─────────────────────────────────────────────────────────────── -->
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

        <!-- Campanita de notificaciones -->
        <div class="notif-wrap" id="notifWrap">
            <button class="tool-btn notif-btn" id="btnCampanita" title="Notificaciones">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                </svg>
                <?php if (count($alertas) > 0): ?>
                    <span class="notif-badge"><?= count($alertas) ?></span>
                <?php endif; ?>
            </button>
            <!-- Panel de notificaciones -->
            <div class="notif-panel" id="notifPanel" style="display:none;">
                <div class="notif-panel-header">
                    <strong>Notificaciones</strong>
                    <span class="notif-count"><?= count($alertas) ?> pendiente<?= count($alertas) !== 1 ? 's' : '' ?> hoy</span>
                </div>
                <?php if (empty($alertas)): ?>
                    <div class="notif-empty">
                        <p>✓ Sin tareas urgentes hoy</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($alertas as $a): ?>
                        <div class="notif-item">
                            <div class="notif-dot"></div>
                            <div>
                                <p class="notif-titulo"><?= htmlspecialchars($a['titulo']) ?></p>
                                <p class="notif-cuando"><?= $a['cuando'] ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                <div class="notif-panel-footer">
                    <button id="btnActivarNotif">🔔 Activar notificaciones del navegador</button>
                </div>
            </div>
        </div>

        <button class="tool-btn dark" id="btnExportar">Exportar CSV</button>
    </div>
</div>

<!-- ─── PANEL FILTROS ─────────────────────────────────────────────────────── -->
<div id="panelFiltros" style="display:none; background:#f5f5f5; border:1px solid #ddd; border-radius:8px; padding:16px; margin:12px 0; gap:12px; flex-wrap:wrap; align-items:flex-end;">
    <div>
        <label style="font-size:13px; color:#555;">Prioridad</label><br>
        <select id="filtPrioridad" style="padding:6px 10px; border-radius:6px; border:1px solid #ccc; margin-top:4px;">
            <option value="">Todas</option>
            <option value="alta">Alta</option>
            <option value="media">Media</option>
            <option value="baja">Baja</option>
        </select>
    </div>
    <div>
        <label style="font-size:13px; color:#555;">Categoría</label><br>
        <select id="filtCategoria" style="padding:6px 10px; border-radius:6px; border:1px solid #ccc; margin-top:4px;">
            <option value="">Todas</option>
            <?php foreach (array_keys($categorias) as $cat): ?>
                <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label style="font-size:13px; color:#555;">Estado</label><br>
        <select id="filtEstado" style="padding:6px 10px; border-radius:6px; border:1px solid #ccc; margin-top:4px;">
            <option value="">Todos</option>
            <option value="pendiente">Pendientes</option>
            <option value="completada">Completadas</option>
        </select>
    </div>
    <button onclick="aplicarFiltros()" style="background:#7b2cbf; color:white; border:none; padding:8px 16px; border-radius:6px; cursor:pointer; margin-top:20px;">Aplicar</button>
    <button onclick="limpiarFiltros()" style="background:#ccc; color:#333; border:none; padding:8px 16px; border-radius:6px; cursor:pointer; margin-top:20px;">Limpiar</button>
</div>

<!-- ─── FORMULARIO NUEVA TAREA ────────────────────────────────────────────── -->
<div id="formNuevaTarea" style="display:none; border:1px solid #ccc; padding:20px; margin:16px 0; background:#f9f9f9; border-radius:8px;">
    <h3 style="margin-top:0;">Nueva Tarea</h3>
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
        <label style="grid-column:1/-1;">Título *<br>
            <input type="text" id="f_titulo" placeholder="Nombre de la tarea"
                   style="width:100%; padding:8px; margin-top:4px; border:1px solid #ccc; border-radius:6px; box-sizing:border-box;">
        </label>
        <label>Prioridad<br>
            <select id="f_prioridad" style="width:100%; padding:8px; margin-top:4px; border:1px solid #ccc; border-radius:6px; box-sizing:border-box;">
                <option value="alta">Alta</option>
                <option value="media" selected>Media</option>
                <option value="baja">Baja</option>
            </select>
        </label>
        <label>Categoría<br>
            <input type="text" id="f_categoria" placeholder="Ej: Ventas, Inventario"
                   style="width:100%; padding:8px; margin-top:4px; border:1px solid #ccc; border-radius:6px; box-sizing:border-box;">
        </label>
        <label>Fecha límite *<br>
            <input type="date" id="f_fecha_limite"
                   style="width:100%; padding:8px; margin-top:4px; border:1px solid #ccc; border-radius:6px; box-sizing:border-box;">
        </label>
        <label>Hora<br>
            <input type="time" id="f_hora"
                   style="width:100%; padding:8px; margin-top:4px; border:1px solid #ccc; border-radius:6px; box-sizing:border-box;">
        </label>
        <label style="grid-column:1/-1;">Descripción<br>
            <textarea id="f_descripcion" placeholder="Descripción opcional..." rows="3"
                      style="width:100%; padding:8px; margin-top:4px; border:1px solid #ccc; border-radius:6px; box-sizing:border-box; resize:vertical;"></textarea>
        </label>
    </div>
    <div style="margin-top:14px;">
        <button type="button" id="btnCrearTarea"
                style="background:#7b2cbf; color:white; padding:10px 22px; border:none; border-radius:6px; cursor:pointer; font-size:14px;">
            ✓ Crear tarea
        </button>
        <button type="button" id="cancelarForm"
                style="background:#e0e0e0; color:#333; padding:10px 22px; border:none; border-radius:6px; cursor:pointer; margin-left:10px; font-size:14px;">
            ✕ Cancelar
        </button>
    </div>
</div>

<!-- ─── LAYOUT PRINCIPAL ──────────────────────────────────────────────────── -->
<div class="tareas-layout">

    <!-- Columna izquierda -->
    <div class="tareas-col">

        <!-- PENDIENTES -->
        <div class="tareas-box">
            <div class="tareas-box-title">
                Pendientes
                <button class="tareas-add-btn" id="btnAgregarPendiente">+ Agregar</button>
            </div>
            <div id="listaPendientes">
            <?php if (empty($tareas_pendientes)): ?>
                <p style="padding:20px; color:#888; font-size:14px;">No hay tareas pendientes. ¡Agrega una!</p>
            <?php else: ?>
                <?php foreach ($tareas_pendientes as $tarea):
                    [$cls, $txt] = mostrarPrioridad($tarea['prioridad']);
                    $fecha_fmt   = formatearFechaHora($tarea['fecha_limite'], $tarea['hora']);
                    $cat_val     = htmlspecialchars($tarea['categoria'] ?? '');
                ?>
                <div class="tarea-item"
                     data-id="<?= intval($tarea['id']) ?>"
                     data-prioridad="<?= strtolower($tarea['prioridad']) ?>"
                     data-categoria="<?= $cat_val ?>"
                     data-estado="pendiente">
                    <div class="tarea-check" title="Marcar como completada"></div>
                    <div class="tarea-info">
                        <h3><?= htmlspecialchars($tarea['titulo']) ?></h3>
                        <?php if (!empty($tarea['descripcion'])): ?>
                            <p style="font-size:13px; color:#666; margin:2px 0;"><?= nl2br(htmlspecialchars($tarea['descripcion'])) ?></p>
                        <?php endif; ?>
                        <p style="font-size:12px; color:#888; margin:4px 0;">
                            <i class="fa-solid fa-calendar"></i> <?= $fecha_fmt ?>
                            <?php if (!empty($tarea['categoria'])): ?>
                                · <span style="color:#7b2cbf;"><?= $cat_val ?></span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <button class="btn-notification" title="Recordatorio individual">
                        <i class="fa-regular fa-bell"></i>
                    </button>
                    <span class="tarea-badge <?= $cls ?>"><?= $txt ?></span>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
            </div>
        </div>

        <!-- COMPLETADAS -->
        <div class="tareas-box">
            <div class="tareas-box-title">Completadas</div>
            <div id="listaCompletadas">
            <?php if (empty($tareas_completadas)): ?>
                <p style="padding:20px; color:#888; font-size:14px;">No hay tareas completadas aún.</p>
            <?php else: ?>
                <?php foreach ($tareas_completadas as $tarea):
                    [$cls, $txt] = mostrarPrioridad($tarea['prioridad']);
                    $fecha_fmt   = formatearFechaHora($tarea['fecha_limite'], $tarea['hora']);
                    $cat_val     = htmlspecialchars($tarea['categoria'] ?? '');
                ?>
                <div class="tarea-item"
                     data-id="<?= intval($tarea['id']) ?>"
                     data-prioridad="<?= strtolower($tarea['prioridad']) ?>"
                     data-categoria="<?= $cat_val ?>"
                     data-estado="completada">
                    <div class="tarea-check done" title="Completada">
                        <i class="fa-solid fa-check"></i>
                    </div>
                    <div class="tarea-info">
                        <h3 class="tachado"><?= htmlspecialchars($tarea['titulo']) ?></h3>
                        <?php if (!empty($tarea['descripcion'])): ?>
                            <p style="font-size:13px; color:#aaa; margin:2px 0;"><?= nl2br(htmlspecialchars($tarea['descripcion'])) ?></p>
                        <?php endif; ?>
                        <p style="font-size:12px; color:#aaa; margin:4px 0;">Completada · <?= $fecha_fmt ?></p>
                    </div>
                    <span class="tarea-badge <?= $cls ?>"><?= $txt ?></span>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
            </div>
        </div>

    </div><!-- /tareas-col izquierda -->

    <!-- Columna derecha -->
    <div class="tareas-col">

        <!-- RESUMEN -->
        <div class="tareas-box">
            <div class="tareas-box-title">Resumen</div>
            <div class="tareas-stat-grid">
                <div class="tareas-stat">
                    <h2><?= $total ?></h2>
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
                    <h2><?= $progreso ?>%</h2>
                    <p>Progreso</p>
                </div>
            </div>
            <div class="tareas-prog-bar">
                <div class="tareas-prog-fill" style="width:<?= $progreso ?>%;"></div>
            </div>
        </div>

        <!-- CATEGORÍAS -->
        <div class="tareas-box">
            <div class="tareas-box-title">Por categoría</div>
            <?php if (empty($categorias)): ?>
                <p style="padding:16px; color:#888; font-size:14px;">Sin categorías aún.</p>
            <?php else: ?>
                <?php foreach ($categorias as $cat => $cant):
                    $color = $colores_cat[$cat] ?? '#7b2cbf';
                ?>
                <div class="tareas-cat-item">
                    <div class="tareas-cat-dot" style="background:<?= $color ?>;"></div>
                    <span><?= htmlspecialchars($cat) ?></span>
                    <small><?= $cant ?> tarea<?= $cant !== 1 ? 's' : '' ?></small>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- TAREAS PRÓXIMAS A VENCER (hoy) -->
        <?php if (!empty($alertas)): ?>
        <div class="tareas-box" style="border-left:4px solid #e04e1a;">
            <div class="tareas-box-title" style="color:#e04e1a;">⚠ Vencen hoy</div>
            <?php foreach ($alertas as $a): ?>
            <div style="padding:8px 16px; border-bottom:1px solid #f0f0f0; font-size:13px;">
                <strong><?= htmlspecialchars($a['titulo']) ?></strong><br>
                <span style="color:#888;"><?= $a['cuando'] ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </div><!-- /tareas-col derecha -->

</div><!-- /tareas-layout -->

<!-- ─── ESTILOS ──────────────────────────────────────────────────────────── -->
<style>
/* Checkboxes */
.tarea-check {
    width:24px; height:24px; min-width:24px;
    border:2px solid #0e0e14; border-radius:4px;
    cursor:pointer; display:flex; align-items:center; justify-content:center;
    transition:background .2s, border-color .2s; flex-shrink:0;
}
.tarea-check:hover:not(.done) { border-color:#7b2cbf; background:#f3eeff; }
.tarea-check.done { background:#7b2cbf; border-color:#7b2cbf; color:white; }

/* Badges prioridad */
.tarea-badge { font-size:12px; padding:2px 10px; border-radius:20px; white-space:nowrap; font-weight:500; }
.tarea-badge.alta  { background:#ffe0d6; color:#b33a1a; }
.tarea-badge.media { background:#ede0ff; color:#5a1fa0; }
.tarea-badge.baja  { background:#fffbd6; color:#7a6a00; }

/* Tachado */
.tachado { text-decoration:line-through; color:#aaa; }

/* Botón campanita */
.notif-wrap { position:relative; display:inline-block; }
.notif-btn  { position:relative; background:#f0f0f0; border:1px solid #ddd; border-radius:8px; padding:7px 12px; cursor:pointer; display:flex; align-items:center; gap:6px; color:#333; }
.notif-btn:hover { background:#e8e0f7; color:#7b2cbf; border-color:#c0a8f0; }
.notif-badge {
    position:absolute; top:-6px; right:-6px;
    background:#e04e1a; color:white;
    font-size:10px; font-weight:700;
    width:18px; height:18px; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    border:2px solid white;
}

/* Panel de notificaciones */
.notif-panel {
    position:absolute; top:calc(100% + 8px); right:0;
    width:300px; background:white; border:1px solid #e0e0e0;
    border-radius:12px; box-shadow:0 8px 32px rgba(0,0,0,0.12);
    z-index:9999; overflow:hidden;
}
.notif-panel-header {
    display:flex; justify-content:space-between; align-items:center;
    padding:14px 16px; background:#f8f8f8; border-bottom:1px solid #eee;
}
.notif-panel-header strong { font-size:14px; color:#222; }
.notif-count { font-size:12px; color:#7b2cbf; font-weight:600; }
.notif-empty { padding:20px 16px; text-align:center; color:#888; font-size:14px; }
.notif-item {
    display:flex; align-items:flex-start; gap:10px;
    padding:12px 16px; border-bottom:1px solid #f5f5f5;
}
.notif-item:last-of-type { border-bottom:none; }
.notif-dot { width:8px; height:8px; min-width:8px; border-radius:50%; background:#e04e1a; margin-top:5px; }
.notif-titulo { font-size:13px; font-weight:600; color:#222; margin:0 0 2px; }
.notif-cuando { font-size:12px; color:#888; margin:0; }
.notif-panel-footer { padding:10px 16px; background:#f8f8f8; border-top:1px solid #eee; }
.notif-panel-footer button {
    width:100%; padding:8px; background:#7b2cbf; color:white;
    border:none; border-radius:6px; cursor:pointer; font-size:13px;
}
.notif-panel-footer button:hover { background:#5a1fa0; }

/* Botón notif individual */
.btn-notification { background:none; border:none; cursor:pointer; margin-left:8px; font-size:17px; color:#aaa; transition:color .2s; flex-shrink:0; }
.btn-notification:hover { color:#7b2cbf; }
.btn-notification .fa-solid { color:#7b2cbf; }

/* Panel filtros flex */
#panelFiltros { display:none; }
#panelFiltros.abierto { display:flex !important; }
</style>

<!-- ─── JAVASCRIPT ────────────────────────────────────────────────────────── -->
<script>
// ── Ruta fija al archivo PHP (siempre correcta, generada por PHP) ─────────────
const TAREAS_URL = '/secciones/tareas.php';

// ── Abrir / cerrar formulario ─────────────────────────────────────────────────
function abrirForm() {
    document.getElementById('formNuevaTarea').style.display = 'block';
    document.getElementById('f_titulo').focus();
}
function cerrarForm() {
    document.getElementById('formNuevaTarea').style.display = 'none';
    ['f_titulo','f_descripcion','f_categoria','f_fecha_limite','f_hora'].forEach(id => {
        document.getElementById(id).value = '';
    });
    document.getElementById('f_prioridad').value = 'media';
}

document.getElementById('btnNuevaTarea').addEventListener('click', abrirForm);
document.getElementById('btnAgregarPendiente').addEventListener('click', abrirForm);
document.getElementById('cancelarForm').addEventListener('click', cerrarForm);

// ── Crear tarea ───────────────────────────────────────────────────────────────
document.getElementById('btnCrearTarea').addEventListener('click', () => {
    const titulo       = document.getElementById('f_titulo').value.trim();
    const descripcion  = document.getElementById('f_descripcion').value.trim();
    const prioridad    = document.getElementById('f_prioridad').value;
    const categoria    = document.getElementById('f_categoria').value.trim();
    const fecha_limite = document.getElementById('f_fecha_limite').value;
    const hora         = document.getElementById('f_hora').value;

    if (!titulo)       { alert('⚠️ El título es obligatorio');       return; }
    if (!fecha_limite) { alert('⚠️ La fecha límite es obligatoria'); return; }

    const btn = document.getElementById('btnCrearTarea');
    btn.disabled    = true;
    btn.textContent = 'Guardando...';

    const fd = new FormData();
    fd.append('accion',       'crear');
    fd.append('titulo',       titulo);
    fd.append('descripcion',  descripcion);
    fd.append('prioridad',    prioridad);
    fd.append('categoria',    categoria);
    fd.append('fecha_limite', fecha_limite);
    fd.append('hora',         hora);

    fetch(TAREAS_URL, { method:'POST', body:fd })
        .then(r => r.text())
        .then(text => {
            let data;
            try { data = JSON.parse(text); }
            catch(e) {
                console.error('Respuesta no JSON:', text);
                alert('✗ Error del servidor. Abre la consola (F12) para ver el detalle.');
                btn.disabled = false; btn.textContent = '✓ Crear tarea';
                return;
            }
            if (data.success) {
                cerrarForm();
                location.reload();
            } else {
                alert('✗ ' + (data.error || 'No se pudo crear la tarea'));
                btn.disabled = false; btn.textContent = '✓ Crear tarea';
            }
        })
        .catch(() => {
            alert('✗ Error de conexión con el servidor');
            btn.disabled = false; btn.textContent = '✓ Crear tarea';
        });
});

// ── Completar tarea ───────────────────────────────────────────────────────────
document.querySelectorAll('.tarea-check:not(.done)').forEach(check => {
    check.addEventListener('click', function() {
        const item    = this.closest('.tarea-item');
        const tareaId = item.dataset.id;
        if (!tareaId) return;

        this.style.opacity = '0.4';
        this.style.pointerEvents = 'none';

        const fd = new FormData();
        fd.append('accion', 'completar');
        fd.append('id',     tareaId);

        fetch(TAREAS_URL, { method:'POST', body:fd })
            .then(r => r.text())
            .then(text => {
                let data;
                try { data = JSON.parse(text); }
                catch(e) {
                    console.error('Respuesta no JSON:', text);
                    this.style.opacity = '1';
                    this.style.pointerEvents = 'auto';
                    return;
                }
                if (data.success) {
                    this.classList.add('done');
                    this.innerHTML = '<i class="fa-solid fa-check"></i>';
                    this.style.opacity = '1';
                    item.querySelector('h3').classList.add('tachado');
                    setTimeout(() => location.reload(), 700);
                } else {
                    alert('✗ ' + (data.error || 'No se pudo actualizar'));
                    this.style.opacity = '1';
                    this.style.pointerEvents = 'auto';
                }
            })
            .catch(() => {
                alert('✗ Error de conexión');
                this.style.opacity = '1';
                this.style.pointerEvents = 'auto';
            });
    });
});

// ── Exportar CSV ──────────────────────────────────────────────────────────────
document.getElementById('btnExportar').addEventListener('click', () => {
    const fd = new FormData();
    fd.append('accion', 'exportar');

    fetch(TAREAS_URL, { method:'POST', body:fd })
        .then(r => r.json())
        .then(data => {
            if (!data.success) { alert('✗ No se pudo exportar'); return; }

            // Generar CSV en el navegador
            const csv = data.filas.map(fila =>
                fila.map(cel => '"' + String(cel).replace(/"/g, '""') + '"').join(',')
            ).join('\n');

            const blob = new Blob(['\uFEFF' + csv], { type:'text/csv;charset=utf-8;' });
            const url  = URL.createObjectURL(blob);
            const a    = document.createElement('a');
            a.href     = url;
            a.download = 'tareas_' + new Date().toISOString().slice(0,10) + '.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        })
        .catch(() => alert('✗ Error al exportar'));
});

// ── Filtros ───────────────────────────────────────────────────────────────────
document.getElementById('btnFiltrar').addEventListener('click', () => {
    document.getElementById('panelFiltros').classList.toggle('abierto');
});

function aplicarFiltros() {
    const prio = document.getElementById('filtPrioridad').value;
    const cat  = document.getElementById('filtCategoria').value;
    const est  = document.getElementById('filtEstado').value;

    document.querySelectorAll('.tarea-item').forEach(item => {
        const okPrio = !prio || item.dataset.prioridad === prio;
        const okCat  = !cat  || item.dataset.categoria === cat;
        const okEst  = !est  || item.dataset.estado    === est;
        item.style.display = (okPrio && okCat && okEst) ? '' : 'none';
    });
}

function limpiarFiltros() {
    document.getElementById('filtPrioridad').value = '';
    document.getElementById('filtCategoria').value = '';
    document.getElementById('filtEstado').value    = '';
    document.querySelectorAll('.tarea-item').forEach(i => i.style.display = '');
}

// ── Campanita: abrir/cerrar panel ─────────────────────────────────────────────
const btnCamp   = document.getElementById('btnCampanita');
const panel     = document.getElementById('notifPanel');

btnCamp.addEventListener('click', e => {
    e.stopPropagation();
    panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
});
document.addEventListener('click', e => {
    if (!document.getElementById('notifWrap').contains(e.target)) {
        panel.style.display = 'none';
    }
});

// ── Activar notificaciones del navegador ──────────────────────────────────────
document.getElementById('btnActivarNotif').addEventListener('click', () => {
    if (!('Notification' in window)) {
        alert('Tu navegador no soporta notificaciones');
        return;
    }
    Notification.requestPermission().then(perm => {
        if (perm === 'granted') {
            // Mostrar notificación inmediata de prueba
            new Notification('EXORA · Tareas', {
                body: '✓ Notificaciones activadas. Te avisaremos cuando una tarea esté por vencer.',
                icon: '/favicon.png',
            });

            // Notificar cada tarea urgente de hoy
            const alertas = <?= json_encode(array_values($alertas)) ?>;
            alertas.forEach(a => {
                setTimeout(() => {
                    new Notification('⚠ Tarea por vencer hoy', {
                        body: a.titulo + ' · ' + a.cuando,
                        icon: '/favicon.png',
                    });
                }, 1000);
            });

            panel.style.display = 'none';
        } else {
            alert('Permiso de notificaciones denegado. Puedes activarlo desde la configuración del navegador.');
        }
    });
});

// ── Campanita individual por tarea ────────────────────────────────────────────
document.querySelectorAll('.btn-notification').forEach(btn => {
    btn.addEventListener('click', e => {
        e.stopPropagation();
        const titulo = btn.closest('.tarea-item').querySelector('h3').innerText;
        const icono  = btn.querySelector('i');
        const activo = icono.classList.contains('fa-solid');

        icono.classList.toggle('fa-solid',  !activo);
        icono.classList.toggle('fa-regular', activo);

        if (!activo) {
            // Pedir permiso y mostrar notificación del navegador
            if ('Notification' in window && Notification.permission === 'granted') {
                new Notification('🔔 Recordatorio activado', {
                    body: 'Te recordaremos: ' + titulo,
                    icon: '/favicon.png',
                });
            } else if ('Notification' in window) {
                Notification.requestPermission().then(p => {
                    if (p === 'granted') {
                        new Notification('🔔 Recordatorio activado', {
                            body: 'Te recordaremos: ' + titulo,
                            icon: '/favicon.png',
                        });
                    }
                });
            }
        }
    });
});
</script>