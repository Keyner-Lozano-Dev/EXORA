<?php
// ══════════════════════════════════════════════════════════════
//  TAREAS.PHP  –  /secciones/tareas.php
//  Compatible con connection.php procedimental (mysqli_*)
// ══════════════════════════════════════════════════════════════

if (session_status() === PHP_SESSION_NONE) session_start();
include_once __DIR__ . '/../connection.php';
$con = connection();

$id_usuario = $_SESSION['user_id'] ?? 1;

// ── AJAX: recibe POST ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['accion'])) {
    header('Content-Type: application/json; charset=utf-8');
    $accion = $_POST['accion'];

    // Crear tarea
    if ($accion === 'crear') {
        $titulo       = trim($_POST['titulo']      ?? '');
        $descripcion  = trim($_POST['descripcion'] ?? '');
        $prioridad    = $_POST['prioridad']         ?? 'media';
        $categoria    = trim($_POST['categoria']   ?? '');
        $fecha_limite = $_POST['fecha_limite']      ?? '';
        $hora         = !empty($_POST['hora']) ? $_POST['hora'] : null;

        if (!$titulo || !$fecha_limite) {
            echo json_encode(['ok' => false, 'msg' => 'Título y fecha son obligatorios']);
            exit;
        }

        $sql  = "INSERT INTO tareas (id_usuario,titulo,descripcion,prioridad,categoria,fecha_limite,hora,completada,fecha_creacion) VALUES (?,?,?,?,?,?,?,0,NOW())";
        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_bind_param($stmt, 'issssss', $id_usuario, $titulo, $descripcion, $prioridad, $categoria, $fecha_limite, $hora);

        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['ok' => true, 'id' => mysqli_stmt_insert_id($stmt)]);
        } else {
            echo json_encode(['ok' => false, 'msg' => mysqli_stmt_error($stmt)]);
        }
        mysqli_stmt_close($stmt);
        exit;
    }

    // Completar tarea
    if ($accion === 'completar') {
        $id = intval($_POST['id'] ?? 0);
        $stmt = mysqli_prepare($con, "UPDATE tareas SET completada=1 WHERE id=? AND id_usuario=?");
        mysqli_stmt_bind_param($stmt, 'ii', $id, $id_usuario);
        echo json_encode(['ok' => mysqli_stmt_execute($stmt)]);
        mysqli_stmt_close($stmt);
        exit;
    }

    // Exportar CSV
    if ($accion === 'exportar') {
        $stmt = mysqli_prepare($con, "SELECT titulo,descripcion,prioridad,categoria,fecha_limite,hora,completada FROM tareas WHERE id_usuario=? ORDER BY fecha_limite ASC");
        mysqli_stmt_bind_param($stmt, 'i', $id_usuario);
        mysqli_stmt_execute($stmt);
        $res  = mysqli_stmt_get_result($stmt);
        $rows = [['Título','Descripción','Prioridad','Categoría','Fecha límite','Hora','Completada']];
        while ($r = mysqli_fetch_assoc($res)) {
            $rows[] = [$r['titulo'], $r['descripcion'] ?? '', $r['prioridad'], $r['categoria'] ?? '', $r['fecha_limite'] ?? '', $r['hora'] ?? '', $r['completada'] ? 'Sí' : 'No'];
        }
        mysqli_stmt_close($stmt);
        echo json_encode(['ok' => true, 'filas' => $rows]);
        exit;
    }

    echo json_encode(['ok' => false, 'msg' => 'Acción desconocida']);
    exit;
}

// ── GET: cargar tareas ────────────────────────────────────────
$tareas = [];
$stmt   = mysqli_prepare($con, "SELECT * FROM tareas WHERE id_usuario=? ORDER BY fecha_limite ASC, hora ASC");
mysqli_stmt_bind_param($stmt, 'i', $id_usuario);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
while ($r = mysqli_fetch_assoc($res)) $tareas[] = $r;
mysqli_stmt_close($stmt);

$pendientes  = array_values(array_filter($tareas, fn($t) => $t['completada'] == 0));
$completadas = array_values(array_filter($tareas, fn($t) => $t['completada'] == 1));
$total       = count($tareas);
$progreso    = $total > 0 ? round(count($completadas) * 100 / $total) : 0;

$categorias = [];
foreach ($tareas as $t) {
    $c = $t['categoria'] ?: 'Sin categoría';
    $categorias[$c] = ($categorias[$c] ?? 0) + 1;
}

function badge($p) {
    return match(strtolower((string)$p)) { 'alta'=>['alta','Alta'], 'media'=>['media','Media'], default=>['baja','Baja'] };
}
function fecha_fmt($f, $h) {
    if (!$f) return 'Sin fecha';
    $dt = new DateTime($f.' '.($h?:'00:00:00'));
    $hoy = new DateTime('today'); $man = new DateTime('tomorrow');
    if ($dt->format('Y-m-d')===$hoy->format('Y-m-d')) return 'Hoy · '.$dt->format('h:i A');
    if ($dt->format('Y-m-d')===$man->format('Y-m-d')) return 'Mañana';
    return $dt->format('d M Y');
}
?>
<!-- ════════════════════════════════════════════════════════════
     HTML
════════════════════════════════════════════════════════════ -->

<!-- TOPBAR -->
<div class="topbar">
    <div class="topbar-top"><div class="welcome"><h1>Tareas</h1></div></div>
    <div class="toolbar">
        <span class="toolbar-label">Acciones</span>
        <div class="tb-divider"></div>
        <button class="tool-btn purple" onclick="tareas_abrirForm()">+ Nueva tarea</button>
        <button class="tool-btn orange" onclick="tareas_toggleFiltros()">Filtrar</button>
        <div class="spacer"></div>
        <!-- Campanita -->
        <div style="position:relative;display:inline-block;">
            <button class="tool-btn" id="tareas_btnBell" onclick="tareas_toggleBell(event)"
                style="background:#f0f0f0;border:1px solid #ddd;border-radius:8px;padding:7px 12px;cursor:pointer;display:flex;align-items:center;gap:4px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                </svg>
            </button>
            <div id="tareas_bellPanel" style="display:none;position:absolute;top:calc(100% + 8px);right:0;width:280px;background:white;border:1px solid #e0e0e0;border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,.12);z-index:9999;">
                <div style="padding:12px 16px;border-bottom:1px solid #eee;font-weight:600;font-size:14px;">Notificaciones</div>
                <div style="padding:14px 16px;font-size:13px;color:#666;">
                    <?php if (empty($pendientes)): ?>
                        ✓ No hay tareas pendientes
                    <?php else: ?>
                        Tienes <strong><?= count($pendientes) ?></strong> tarea<?= count($pendientes)!==1?'s':'' ?> pendiente<?= count($pendientes)!==1?'s':'' ?>.
                        <?php $hoy_str = date('Y-m-d'); $hoy_count = count(array_filter($pendientes, fn($t)=>$t['fecha_limite']===$hoy_str)); ?>
                        <?php if ($hoy_count > 0): ?><br><span style="color:#e04e1a;font-weight:600;">⚠ <?= $hoy_count ?> vencen hoy</span><?php endif; ?>
                    <?php endif; ?>
                </div>
                <div style="padding:10px 16px;border-top:1px solid #eee;">
                    <button onclick="tareas_activarNotif()" style="width:100%;padding:8px;background:#7b2cbf;color:white;border:none;border-radius:6px;cursor:pointer;font-size:13px;">
                        🔔 Activar notificaciones del navegador
                    </button>
                </div>
            </div>
        </div>
        <button class="tool-btn dark" onclick="tareas_exportar()">Exportar CSV</button>
    </div>
</div>

<!-- FILTROS -->
<div id="tareas_filtros" style="display:none;background:#f5f5f5;border:1px solid #ddd;border-radius:8px;padding:16px;margin:12px 0;gap:12px;flex-wrap:wrap;align-items:flex-end;">
    <div>
        <label style="font-size:13px;color:#555;">Prioridad</label><br>
        <select id="tareas_fPrio" style="padding:6px 10px;border-radius:6px;border:1px solid #ccc;margin-top:4px;">
            <option value="">Todas</option><option value="alta">Alta</option><option value="media">Media</option><option value="baja">Baja</option>
        </select>
    </div>
    <div>
        <label style="font-size:13px;color:#555;">Estado</label><br>
        <select id="tareas_fEst" style="padding:6px 10px;border-radius:6px;border:1px solid #ccc;margin-top:4px;">
            <option value="">Todos</option><option value="pendiente">Pendientes</option><option value="completada">Completadas</option>
        </select>
    </div>
    <button onclick="tareas_aplicarFiltro()" style="background:#7b2cbf;color:white;border:none;padding:8px 16px;border-radius:6px;cursor:pointer;margin-top:20px;">Aplicar</button>
    <button onclick="tareas_limpiarFiltro()" style="background:#ccc;color:#333;border:none;padding:8px 16px;border-radius:6px;cursor:pointer;margin-top:20px;">Limpiar</button>
</div>

<!-- FORMULARIO NUEVA TAREA -->
<div id="tareas_form" style="display:none;border:1px solid #ccc;padding:20px;margin:16px 0;background:#f9f9f9;border-radius:8px;">
    <h3 style="margin-top:0;">Nueva Tarea</h3>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <label style="grid-column:1/-1;font-size:13px;">Título *<br>
            <input id="tareas_titulo" type="text" placeholder="Nombre de la tarea"
                   style="width:100%;padding:9px;margin-top:4px;border:1px solid #ccc;border-radius:6px;box-sizing:border-box;font-size:14px;">
        </label>
        <label style="font-size:13px;">Prioridad<br>
            <select id="tareas_prioridad" style="width:100%;padding:9px;margin-top:4px;border:1px solid #ccc;border-radius:6px;box-sizing:border-box;font-size:14px;">
                <option value="alta">Alta</option>
                <option value="media" selected>Media</option>
                <option value="baja">Baja</option>
            </select>
        </label>
        <label style="font-size:13px;">Categoría<br>
            <input id="tareas_categoria" type="text" placeholder="Ej: Ventas"
                   style="width:100%;padding:9px;margin-top:4px;border:1px solid #ccc;border-radius:6px;box-sizing:border-box;font-size:14px;">
        </label>
        <label style="font-size:13px;">Fecha límite *<br>
            <input id="tareas_fecha" type="date"
                   style="width:100%;padding:9px;margin-top:4px;border:1px solid #ccc;border-radius:6px;box-sizing:border-box;font-size:14px;">
        </label>
        <label style="font-size:13px;">Hora<br>
            <input id="tareas_hora" type="time"
                   style="width:100%;padding:9px;margin-top:4px;border:1px solid #ccc;border-radius:6px;box-sizing:border-box;font-size:14px;">
        </label>
        <label style="grid-column:1/-1;font-size:13px;">Descripción<br>
            <textarea id="tareas_desc" rows="3" placeholder="Descripción opcional..."
                      style="width:100%;padding:9px;margin-top:4px;border:1px solid #ccc;border-radius:6px;box-sizing:border-box;font-size:14px;resize:vertical;"></textarea>
        </label>
    </div>
    <div style="margin-top:14px;display:flex;gap:10px;">
        <button id="tareas_btnGuardar" onclick="tareas_guardar()"
                style="background:#7b2cbf;color:white;padding:10px 22px;border:none;border-radius:6px;cursor:pointer;font-size:14px;">
            ✓ Crear tarea
        </button>
        <button onclick="tareas_cerrarForm()"
                style="background:#e0e0e0;color:#333;padding:10px 22px;border:none;border-radius:6px;cursor:pointer;font-size:14px;">
            ✕ Cancelar
        </button>
    </div>
    <p id="tareas_formMsg" style="margin:10px 0 0;font-size:13px;color:#e04e1a;display:none;"></p>
</div>

<!-- LAYOUT -->
<div class="tareas-layout">

    <!-- Columna izquierda -->
    <div class="tareas-col">

        <!-- Pendientes -->
        <div class="tareas-box">
            <div class="tareas-box-title">
                Pendientes
                <button class="tareas-add-btn" onclick="tareas_abrirForm()">+ Agregar</button>
            </div>
            <div id="tareas_listaPend">
            <?php if (empty($pendientes)): ?>
                <p style="padding:20px;color:#888;font-size:14px;">No hay tareas pendientes. ¡Agrega una!</p>
            <?php else: foreach ($pendientes as $t):
                [$cls,$txt] = badge($t['prioridad']); $fmtf = fecha_fmt($t['fecha_limite'],$t['hora']); ?>
                <div class="tarea-item" data-id="<?= $t['id'] ?>" data-prio="<?= strtolower($t['prioridad']) ?>" data-estado="pendiente">
                    <div class="tarea-check" onclick="tareas_completar(this)" title="Marcar completada"></div>
                    <div class="tarea-info">
                        <h3><?= htmlspecialchars($t['titulo']) ?></h3>
                        <?php if ($t['descripcion']): ?><p style="font-size:13px;color:#666;margin:2px 0;"><?= nl2br(htmlspecialchars($t['descripcion'])) ?></p><?php endif; ?>
                        <p style="font-size:12px;color:#888;margin:4px 0;"><i class="fa-solid fa-calendar"></i> <?= $fmtf ?><?php if ($t['categoria']): ?> · <span style="color:#7b2cbf;"><?= htmlspecialchars($t['categoria']) ?></span><?php endif; ?></p>
                    </div>
                    <span class="tarea-badge <?= $cls ?>"><?= $txt ?></span>
                </div>
            <?php endforeach; endif; ?>
            </div>
        </div>

        <!-- Completadas -->
        <div class="tareas-box">
            <div class="tareas-box-title">Completadas</div>
            <div id="tareas_listaComp">
            <?php if (empty($completadas)): ?>
                <p style="padding:20px;color:#888;font-size:14px;">No hay tareas completadas aún.</p>
            <?php else: foreach ($completadas as $t):
                [$cls,$txt] = badge($t['prioridad']); $fmtf = fecha_fmt($t['fecha_limite'],$t['hora']); ?>
                <div class="tarea-item" data-id="<?= $t['id'] ?>" data-prio="<?= strtolower($t['prioridad']) ?>" data-estado="completada">
                    <div class="tarea-check done"><i class="fa-solid fa-check"></i></div>
                    <div class="tarea-info">
                        <h3 class="tachado"><?= htmlspecialchars($t['titulo']) ?></h3>
                        <?php if ($t['descripcion']): ?><p style="font-size:13px;color:#aaa;margin:2px 0;"><?= nl2br(htmlspecialchars($t['descripcion'])) ?></p><?php endif; ?>
                        <p style="font-size:12px;color:#aaa;margin:4px 0;">Completada · <?= $fmtf ?></p>
                    </div>
                    <span class="tarea-badge <?= $cls ?>"><?= $txt ?></span>
                </div>
            <?php endforeach; endif; ?>
            </div>
        </div>

    </div>

    <!-- Columna derecha -->
    <div class="tareas-col">

        <!-- Resumen -->
        <div class="tareas-box">
            <div class="tareas-box-title">Resumen</div>
            <div class="tareas-stat-grid">
                <div class="tareas-stat"><h2><?= $total ?></h2><p>Total tareas</p></div>
                <div class="tareas-stat"><h2><?= count($completadas) ?></h2><p>Completadas</p></div>
                <div class="tareas-stat"><h2><?= count(array_filter($pendientes, fn($t)=>strtolower($t['prioridad'])==='alta')) ?></h2><p>Alta prioridad</p></div>
                <div class="tareas-stat"><h2><?= $progreso ?>%</h2><p>Progreso</p></div>
            </div>
            <div class="tareas-prog-bar"><div class="tareas-prog-fill" style="width:<?= $progreso ?>%;"></div></div>
        </div>

        <!-- Categorías -->
        <div class="tareas-box">
            <div class="tareas-box-title">Por categoría</div>
            <?php if (empty($categorias)): ?>
                <p style="padding:16px;color:#888;font-size:14px;">Sin categorías aún.</p>
            <?php else:
                $cols=['Ventas'=>'#e04e1a','Inventario'=>'#7b2cbf','Proveedores'=>'#f5e100','Precios'=>'#0e0e14','Sin categoría'=>'#999'];
                foreach ($categorias as $cat=>$cant): $c=$cols[$cat]??'#7b2cbf'; ?>
                <div class="tareas-cat-item">
                    <div class="tareas-cat-dot" style="background:<?= $c ?>;"></div>
                    <span><?= htmlspecialchars($cat) ?></span>
                    <small><?= $cant ?> tarea<?= $cant!==1?'s':'' ?></small>
                </div>
            <?php endforeach; endif; ?>
        </div>

    </div>
</div>

<!-- ESTILOS -->
<style>
.tarea-check{width:24px;height:24px;min-width:24px;border:2px solid #0e0e14;border-radius:4px;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:all .2s;}
.tarea-check:hover:not(.done){border-color:#7b2cbf;background:#f3eeff;}
.tarea-check.done{background:#7b2cbf;border-color:#7b2cbf;color:white;}
.tarea-badge{font-size:12px;padding:3px 10px;border-radius:20px;white-space:nowrap;font-weight:500;}
.tarea-badge.alta{background:#ffe0d6;color:#b33a1a;}
.tarea-badge.media{background:#ede0ff;color:#5a1fa0;}
.tarea-badge.baja{background:#fffbd6;color:#7a6a00;}
.tachado{text-decoration:line-through;color:#aaa;}
</style>

<!-- JAVASCRIPT — todo en funciones con prefijo "tareas_" para evitar conflictos -->
<script>
(function() {
    // ── Ruta fija al endpoint ─────────────────────────────────
    var URL = '/secciones/tareas.php';

    // ── Helpers ───────────────────────────────────────────────
    function post(datos, callback) {
        var fd = new FormData();
        for (var k in datos) fd.append(k, datos[k]);
        fetch(URL, { method: 'POST', body: fd })
            .then(function(r){ return r.text(); })
            .then(function(txt){
                try { callback(null, JSON.parse(txt)); }
                catch(e){ callback('Respuesta inválida del servidor: ' + txt.substring(0,200)); }
            })
            .catch(function(e){ callback('Error de red: ' + e.message); });
    }

    // ── Formulario ────────────────────────────────────────────
    window.tareas_abrirForm = function() {
        document.getElementById('tareas_form').style.display = 'block';
        document.getElementById('tareas_titulo').focus();
    };
    window.tareas_cerrarForm = function() {
        document.getElementById('tareas_form').style.display = 'none';
        document.getElementById('tareas_formMsg').style.display = 'none';
        ['tareas_titulo','tareas_desc','tareas_categoria','tareas_fecha','tareas_hora'].forEach(function(id){
            document.getElementById(id).value = '';
        });
        document.getElementById('tareas_prioridad').value = 'media';
    };

    // ── Guardar tarea ─────────────────────────────────────────
    window.tareas_guardar = function() {
        var titulo = document.getElementById('tareas_titulo').value.trim();
        var fecha  = document.getElementById('tareas_fecha').value;
        var msg    = document.getElementById('tareas_formMsg');

        if (!titulo) { msg.textContent='⚠ El título es obligatorio'; msg.style.display='block'; return; }
        if (!fecha)  { msg.textContent='⚠ La fecha límite es obligatoria'; msg.style.display='block'; return; }

        var btn = document.getElementById('tareas_btnGuardar');
        btn.disabled = true; btn.textContent = 'Guardando...';
        msg.style.display = 'none';

        post({
            accion:       'crear',
            titulo:       titulo,
            descripcion:  document.getElementById('tareas_desc').value.trim(),
            prioridad:    document.getElementById('tareas_prioridad').value,
            categoria:    document.getElementById('tareas_categoria').value.trim(),
            fecha_limite: fecha,
            hora:         document.getElementById('tareas_hora').value
        }, function(err, data) {
            btn.disabled = false; btn.textContent = '✓ Crear tarea';
            if (err) { msg.textContent = '✗ ' + err; msg.style.display='block'; return; }
            if (data.ok) {
                window.location.reload();
            } else {
                msg.textContent = '✗ ' + (data.msg || 'No se pudo crear');
                msg.style.display = 'block';
            }
        });
    };

    // ── Completar tarea ───────────────────────────────────────
    window.tareas_completar = function(check) {
        var item = check.closest('.tarea-item');
        var id   = item.getAttribute('data-id');
        check.style.opacity = '0.4';
        check.style.pointerEvents = 'none';

        post({ accion:'completar', id:id }, function(err, data) {
            if (err || !data.ok) {
                check.style.opacity = '1'; check.style.pointerEvents = 'auto';
                alert('✗ No se pudo actualizar');
                return;
            }
            check.classList.add('done');
            check.innerHTML = '<i class="fa-solid fa-check"></i>';
            check.style.opacity = '1';
            item.querySelector('h3').classList.add('tachado');
            setTimeout(function(){ window.location.reload(); }, 700);
        });
    };

    // ── Exportar CSV ──────────────────────────────────────────
    window.tareas_exportar = function() {
        post({ accion:'exportar' }, function(err, data) {
            if (err || !data.ok) { alert('✗ No se pudo exportar'); return; }
            var csv = data.filas.map(function(fila){
                return fila.map(function(c){ return '"'+String(c).replace(/"/g,'""')+'"'; }).join(',');
            }).join('\n');
            var blob = new Blob(['\uFEFF'+csv], {type:'text/csv;charset=utf-8;'});
            var a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = 'tareas_'+new Date().toISOString().slice(0,10)+'.csv';
            document.body.appendChild(a); a.click(); document.body.removeChild(a);
        });
    };

    // ── Filtros ───────────────────────────────────────────────
    window.tareas_toggleFiltros = function() {
        var el = document.getElementById('tareas_filtros');
        el.style.display = el.style.display === 'flex' ? 'none' : 'flex';
    };
    window.tareas_aplicarFiltro = function() {
        var prio = document.getElementById('tareas_fPrio').value;
        var est  = document.getElementById('tareas_fEst').value;
        document.querySelectorAll('.tarea-item').forEach(function(item){
            var okP = !prio || item.getAttribute('data-prio') === prio;
            var okE = !est  || item.getAttribute('data-estado') === est;
            item.style.display = (okP && okE) ? '' : 'none';
        });
    };
    window.tareas_limpiarFiltro = function() {
        document.getElementById('tareas_fPrio').value = '';
        document.getElementById('tareas_fEst').value  = '';
        document.querySelectorAll('.tarea-item').forEach(function(i){ i.style.display=''; });
    };

    // ── Campanita ─────────────────────────────────────────────
    window.tareas_toggleBell = function(e) {
        e.stopPropagation();
        var p = document.getElementById('tareas_bellPanel');
        p.style.display = p.style.display === 'none' ? 'block' : 'none';
    };
    document.addEventListener('click', function(e) {
        var p = document.getElementById('tareas_bellPanel');
        if (p && !p.contains(e.target) && e.target.id !== 'tareas_btnBell') {
            p.style.display = 'none';
        }
    });
    window.tareas_activarNotif = function() {
        if (!('Notification' in window)) { alert('Tu navegador no soporta notificaciones'); return; }
        Notification.requestPermission().then(function(perm){
            if (perm === 'granted') {
                new Notification('EXORA · Tareas', {
                    body: '✓ Notificaciones activadas correctamente',
                    icon: '/favicon.png'
                });
                document.getElementById('tareas_bellPanel').style.display = 'none';
            } else {
                alert('Permiso denegado. Actívalo en la configuración del navegador.');
            }
        });
    };

})(); // fin IIFE — evita contaminar el scope global
</script>