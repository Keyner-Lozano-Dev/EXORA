<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include_once __DIR__ . '/../connection.php';
$con = connection();
$id_usuario = $_SESSION['user_id'] ?? 0;

// ── AJAX ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['accion'])) {
    header('Content-Type: application/json; charset=utf-8');
    $accion = $_POST['accion'];

    if ($accion === 'crear') {
        $titulo      = trim($_POST['titulo'] ?? '');
        $desc        = trim($_POST['descripcion'] ?? '');
        $prioridad   = $_POST['prioridad'] ?? 'media';
        $categoria   = trim($_POST['categoria'] ?? '');
        $fecha       = $_POST['fecha_limite'] ?? '';
        $hora        = !empty($_POST['hora']) ? $_POST['hora'] : null;
        if (!$titulo || !$fecha) { echo json_encode(['ok'=>false,'msg'=>'Título y fecha son obligatorios']); exit; }
        $stmt = mysqli_prepare($con, "INSERT INTO tareas (id_usuario,titulo,descripcion,prioridad,categoria,fecha_limite,hora,completada,fecha_creacion) VALUES (?,?,?,?,?,?,?,0,NOW())");
        mysqli_stmt_bind_param($stmt,'issssss',$id_usuario,$titulo,$desc,$prioridad,$categoria,$fecha,$hora);
        $ok = mysqli_stmt_execute($stmt);
        echo json_encode(['ok'=>$ok,'id'=>mysqli_insert_id($con),'msg'=>mysqli_stmt_error($stmt)]);
        mysqli_stmt_close($stmt);
        exit;
    }

    if ($accion === 'completar') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = mysqli_prepare($con, "UPDATE tareas SET completada=1 WHERE id=? AND id_usuario=?");
        mysqli_stmt_bind_param($stmt,'ii',$id,$id_usuario);
        echo json_encode(['ok'=>mysqli_stmt_execute($stmt)]);
        mysqli_stmt_close($stmt);
        exit;
    }

    if ($accion === 'eliminar') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = mysqli_prepare($con, "DELETE FROM tareas WHERE id=? AND id_usuario=?");
        mysqli_stmt_bind_param($stmt,'ii',$id,$id_usuario);
        echo json_encode(['ok'=>mysqli_stmt_execute($stmt)]);
        mysqli_stmt_close($stmt);
        exit;
    }

    echo json_encode(['ok'=>false,'msg'=>'Acción desconocida']);
    exit;
}

// ── GET: cargar datos ─────────────────────────────────────────
$stmt = mysqli_prepare($con, "SELECT * FROM tareas WHERE id_usuario=? ORDER BY fecha_limite ASC, hora ASC");
mysqli_stmt_bind_param($stmt,'i',$id_usuario);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$tareas = [];
while ($r = mysqli_fetch_assoc($res)) $tareas[] = $r;
mysqli_stmt_close($stmt);

$pendientes  = array_values(array_filter($tareas, fn($t) => $t['completada'] == 0));
$completadas = array_values(array_filter($tareas, fn($t) => $t['completada'] == 1));
$total       = count($tareas);
$progreso    = $total > 0 ? round(count($completadas)*100/$total) : 0;
$alta        = count(array_filter($pendientes, fn($t) => strtolower($t['prioridad'])==='alta'));

$categorias = [];
foreach ($tareas as $t) {
    $c = trim($t['categoria']) ?: 'Sin categoría';
    $categorias[$c] = ($categorias[$c] ?? 0) + 1;
}

function fmt_fecha($f, $h) {
    if (!$f) return 'Sin fecha';
    $hoy = date('Y-m-d');
    $man = date('Y-m-d', strtotime('+1 day'));
    if ($f === $hoy) return 'Hoy' . ($h ? ' · ' . substr($h,0,5) : '');
    if ($f === $man) return 'Mañana' . ($h ? ' · ' . substr($h,0,5) : '');
    return date('d M Y', strtotime($f)) . ($h ? ' · ' . substr($h,0,5) : '');
}
$colores_cat = ['#e04e1a','#7b2cbf','#f5e100','#0e0e14','#0ea5e9','#16a34a'];
?>

<!-- TOPBAR -->
<div class="topbar">
    <div class="topbar-top">
        <div class="welcome"><h1>Tareas</h1></div>
        <?php if (!empty($_SESSION['foto'])): ?>
            <a href="secciones/perfil.php" class="profile" style="text-decoration:none;padding:0;overflow:hidden;">
                <img src="<?= $_SESSION['foto'] ?>" style="width:48px;height:48px;border-radius:50%;object-fit:cover;display:block;border:2px solid #0e0e14;box-shadow:3px 3px 0 #0e0e14;">
            </a>
        <?php else: ?>
            <a href="secciones/perfil.php" class="profile" style="text-decoration:none;">
                <?= strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)) ?>
            </a>
        <?php endif; ?>
    </div>
    <div class="toolbar">
        <span class="toolbar-label">Acciones</span>
        <div class="tb-divider"></div>
        <button class="tool-btn purple" onclick="t_abrirModal()">
            <i class="fa-solid fa-plus"></i> Nueva tarea
        </button>
        <button class="tool-btn orange" onclick="t_toggleFiltros()">
            <i class="fa-solid fa-filter"></i> Filtrar
        </button>
        <div class="spacer"></div>
        <button class="tool-btn dark" onclick="t_exportar()">
            <i class="fa-solid fa-download"></i> Exportar CSV
        </button>
    </div>
</div>

<!-- FILTROS (ocultos por defecto) -->
<div id="t_filtros" style="display:none;" class="tareas-box" style="padding:16px;">
    <div style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
        <div>
            <label style="font-size:12px;font-weight:700;color:var(--black);text-transform:uppercase;letter-spacing:.5px;">Prioridad</label><br>
            <select id="t_fPrio" style="margin-top:6px;padding:9px 12px;border:2px solid var(--black);border-radius:10px;background:var(--bg);font-family:var(--font);font-size:14px;outline:none;">
                <option value="">Todas</option>
                <option value="alta">Alta</option>
                <option value="media">Media</option>
                <option value="baja">Baja</option>
            </select>
        </div>
        <div>
            <label style="font-size:12px;font-weight:700;color:var(--black);text-transform:uppercase;letter-spacing:.5px;">Estado</label><br>
            <select id="t_fEst" style="margin-top:6px;padding:9px 12px;border:2px solid var(--black);border-radius:10px;background:var(--bg);font-family:var(--font);font-size:14px;outline:none;">
                <option value="">Todos</option>
                <option value="pendiente">Pendientes</option>
                <option value="completada">Completadas</option>
            </select>
        </div>
        <button class="tool-btn purple" onclick="t_aplicarFiltro()" style="margin-bottom:0;">Aplicar</button>
        <button class="tool-btn" onclick="t_limpiarFiltro()" style="margin-bottom:0;">Limpiar</button>
    </div>
</div>

<!-- LAYOUT -->
<div class="tareas-layout">

    <!-- COLUMNA IZQUIERDA -->
    <div class="tareas-col">

        <!-- PENDIENTES -->
        <div class="tareas-box">
            <div class="tareas-box-title">
                Pendientes
                <span style="font-size:13px;font-weight:500;color:var(--muted);">(<?= count($pendientes) ?>)</span>
                <button class="tareas-add-btn" onclick="t_abrirModal()">
                    <i class="fa-solid fa-plus"></i> Agregar
                </button>
            </div>
            <div id="t_listaPend">
            <?php if (empty($pendientes)): ?>
                <p style="padding:20px 0;color:var(--muted);font-size:14px;">No hay tareas pendientes. ¡Agrega una!</p>
            <?php else: foreach ($pendientes as $t): ?>
                <div class="tarea-item" data-id="<?= $t['id'] ?>" data-prio="<?= strtolower($t['prioridad']) ?>" data-estado="pendiente">
                    <div class="tarea-check" onclick="t_completar(this)" title="Marcar completada"></div>
                    <div class="tarea-info">
                        <h3><?= htmlspecialchars($t['titulo']) ?></h3>
                        <?php if ($t['descripcion']): ?>
                            <p style="font-size:12px;color:var(--muted);margin:2px 0;"><?= htmlspecialchars($t['descripcion']) ?></p>
                        <?php endif; ?>
                        <p><i class="fa-solid fa-calendar"></i> <?= fmt_fecha($t['fecha_limite'], $t['hora']) ?>
                        <?php if ($t['categoria']): ?> &nbsp;·&nbsp; <i class="fa-solid fa-tag"></i> <?= htmlspecialchars($t['categoria']) ?><?php endif; ?></p>
                    </div>
                    <span class="tarea-badge <?= strtolower($t['prioridad']) ?>"><?= ucfirst($t['prioridad']) ?></span>
                    <button class="t-btn-del" onclick="t_eliminar(<?= $t['id'] ?>)" title="Eliminar">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            <?php endforeach; endif; ?>
            </div>
        </div>

        <!-- COMPLETADAS -->
        <div class="tareas-box">
            <div class="tareas-box-title">
                Completadas
                <span style="font-size:13px;font-weight:500;color:var(--muted);">(<?= count($completadas) ?>)</span>
            </div>
            <div id="t_listaComp">
            <?php if (empty($completadas)): ?>
                <p style="padding:20px 0;color:var(--muted);font-size:14px;">No hay tareas completadas aún.</p>
            <?php else: foreach ($completadas as $t): ?>
                <div class="tarea-item" data-id="<?= $t['id'] ?>" data-prio="<?= strtolower($t['prioridad']) ?>" data-estado="completada">
                    <div class="tarea-check done"><i class="fa-solid fa-check"></i></div>
                    <div class="tarea-info">
                        <h3 class="tachado"><?= htmlspecialchars($t['titulo']) ?></h3>
                        <p>Completada · <?= fmt_fecha($t['fecha_limite'], $t['hora']) ?></p>
                    </div>
                    <span class="tarea-badge <?= strtolower($t['prioridad']) ?>"><?= ucfirst($t['prioridad']) ?></span>
                    <button class="t-btn-del" onclick="t_eliminar(<?= $t['id'] ?>)" title="Eliminar">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            <?php endforeach; endif; ?>
            </div>
        </div>

    </div>

    <!-- COLUMNA DERECHA -->
    <div class="tareas-col">

        <!-- RESUMEN -->
        <div class="tareas-box">
            <div class="tareas-box-title">Resumen</div>
            <div class="tareas-stat-grid">
                <div class="tareas-stat"><h2><?= $total ?></h2><p>Total tareas</p></div>
                <div class="tareas-stat"><h2><?= count($completadas) ?></h2><p>Completadas</p></div>
                <div class="tareas-stat"><h2><?= $alta ?></h2><p>Alta prioridad</p></div>
                <div class="tareas-stat"><h2><?= $progreso ?>%</h2><p>Progreso</p></div>
            </div>
            <div class="tareas-prog-bar">
                <div class="tareas-prog-fill" style="width:<?= $progreso ?>%;"></div>
            </div>
        </div>

        <!-- CATEGORÍAS -->
        <div class="tareas-box">
            <div class="tareas-box-title">Por categoría</div>
            <?php if (empty($categorias)): ?>
                <p style="color:var(--muted);font-size:13px;padding:12px 0;">Sin categorías aún.</p>
            <?php else: $i=0; foreach ($categorias as $cat => $cant): ?>
                <div class="tareas-cat-item">
                    <div class="tareas-cat-dot" style="background:<?= $colores_cat[$i % count($colores_cat)] ?>;"></div>
                    <span><?= htmlspecialchars($cat) ?></span>
                    <small><?= $cant ?> tarea<?= $cant!=1?'s':'' ?></small>
                </div>
            <?php $i++; endforeach; endif; ?>
        </div>

    </div>
</div>

<!-- MODAL NUEVA TAREA -->
<div class="modal-overlay" id="t_modal" style="display:none;">
    <div class="modal-box">
        <div class="modal-header">
            <h2>Nueva Tarea</h2>
            <button class="modal-close" onclick="t_cerrarModal()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <div class="campo">
                <label>Título *</label>
                <input type="text" id="t_titulo" placeholder="Nombre de la tarea">
            </div>
            <div class="campo">
                <label>Descripción</label>
                <textarea id="t_desc" placeholder="Descripción opcional..."></textarea>
            </div>
            <div class="campo-row">
                <div class="campo">
                    <label>Prioridad</label>
                    <select id="t_prioridad">
                        <option value="alta">Alta</option>
                        <option value="media" selected>Media</option>
                        <option value="baja">Baja</option>
                    </select>
                </div>
                <div class="campo">
                    <label>Categoría</label>
                    <input type="text" id="t_categoria" placeholder="Ej: Ventas">
                </div>
            </div>
            <div class="campo-row">
                <div class="campo">
                    <label>Fecha límite *</label>
                    <input type="date" id="t_fecha">
                </div>
                <div class="campo">
                    <label>Hora</label>
                    <input type="time" id="t_hora">
                </div>
            </div>
            <p id="t_modalMsg" style="color:#e04e1a;font-size:13px;display:none;margin:0;"></p>
        </div>
        <div class="modal-footer">
            <button class="btn-cancelar" onclick="t_cerrarModal()">Cancelar</button>
            <button class="btn-guardar" id="t_btnGuardar" onclick="t_guardar()">
                <i class="fa-solid fa-check"></i> Guardar
            </button>
        </div>
    </div>
</div>

<!-- ESTILOS LOCALES -->
<style>
.tarea-item{display:flex;align-items:center;gap:12px;padding:14px;border-radius:14px;border:2px solid var(--line);background:var(--bg);margin-bottom:10px;transition:border-color .2s;}
.tarea-item:hover{border-color:var(--black);}
.tarea-item:last-child{margin-bottom:0;}
.tarea-check{width:22px;height:22px;min-width:22px;border:2px solid var(--black);border-radius:6px;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:all .2s;}
.tarea-check:hover:not(.done){border-color:#7b2cbf;background:#f3eeff;}
.tarea-check.done{background:#7b2cbf;border-color:#7b2cbf;color:#fff;font-size:11px;}
.tarea-info{flex:1;min-width:0;}
.tarea-info h3{font-size:13px;font-weight:700;color:var(--black);}
.tarea-info p{font-size:11px;color:var(--muted);margin-top:3px;}
.tarea-badge{padding:4px 10px;border-radius:20px;font-size:10px;font-weight:700;border:1.5px solid var(--black);flex-shrink:0;white-space:nowrap;}
.tarea-badge.alta{background:#fee2e2;color:#991b1b;}
.tarea-badge.media{background:#fef9c3;color:#854d0e;}
.tarea-badge.baja{background:#dcfce7;color:#166534;}
.tachado{text-decoration:line-through;color:var(--muted);}
.t-btn-del{background:none;border:1.5px solid var(--line);color:var(--muted);border-radius:8px;padding:5px 8px;cursor:pointer;font-size:11px;flex-shrink:0;transition:all .15s;}
.t-btn-del:hover{border-color:#e04e1a;color:#e04e1a;}
/* Modal */
.modal-overlay{position:fixed;inset:0;background:rgba(14,14,20,.5);z-index:999;display:flex;align-items:center;justify-content:center;backdrop-filter:blur(4px);}
.modal-box{background:var(--card);border:2px solid var(--black);border-radius:22px;box-shadow:8px 8px 0 var(--black);width:480px;max-width:95vw;overflow:hidden;}
.modal-header{padding:20px 24px;border-bottom:2px solid var(--line);display:flex;justify-content:space-between;align-items:center;}
.modal-header h2{font-size:18px;font-weight:800;color:var(--black);font-family:var(--font);}
.modal-close{width:32px;height:32px;border-radius:8px;border:2px solid var(--black);background:var(--bg);cursor:pointer;font-size:14px;display:flex;align-items:center;justify-content:center;transition:all .15s;}
.modal-close:hover{background:var(--black);color:var(--card);}
.modal-body{padding:24px;display:flex;flex-direction:column;gap:16px;}
.campo{display:flex;flex-direction:column;gap:6px;flex:1;}
.campo label{font-size:12px;font-weight:700;color:var(--black);font-family:var(--font);text-transform:uppercase;letter-spacing:.5px;}
.campo input,.campo textarea,.campo select{padding:10px 14px;border:2px solid var(--black);border-radius:10px;background:var(--bg);font-family:var(--font);font-size:14px;color:var(--black);outline:none;transition:box-shadow .15s;resize:none;}
.campo textarea{height:72px;}
.campo input:focus,.campo textarea:focus,.campo select:focus{box-shadow:3px 3px 0 var(--black);}
.campo-row{display:flex;gap:12px;}
.modal-footer{padding:16px 24px;border-top:2px solid var(--line);display:flex;justify-content:flex-end;gap:12px;}
.btn-cancelar{padding:10px 20px;border:2px solid var(--black);border-radius:10px;background:var(--bg);font-family:var(--font);font-size:14px;font-weight:600;cursor:pointer;transition:all .15s;}
.btn-cancelar:hover{background:var(--black);color:var(--card);}
.btn-guardar{padding:10px 20px;border:2px solid var(--black);border-radius:10px;background:var(--black);color:var(--card);font-family:var(--font);font-size:14px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:8px;box-shadow:3px 3px 0 #7b2cbf;transition:all .15s;}
.btn-guardar:hover{transform:translateY(-2px);box-shadow:5px 5px 0 #7b2cbf;}
</style>

<!-- JS -->
<script>
(function(){
    var ENDPOINT = 'secciones/tareas.php';

    function post(datos, cb) {
        var fd = new FormData();
        Object.keys(datos).forEach(function(k){ fd.append(k, datos[k]); });
        fetch(ENDPOINT, { method:'POST', body:fd })
            .then(function(r){ return r.text(); })
            .then(function(txt){
                try { cb(null, JSON.parse(txt)); }
                catch(e){ cb('Respuesta inválida: ' + txt.substring(0,100)); }
            })
            .catch(function(e){ cb('Error red: ' + e.message); });
    }

    function recargar() {
        fetch(ENDPOINT)
            .then(function(r){ return r.text(); })
            .then(function(html){
                document.getElementById('main-content').innerHTML = html;
            });
    }

    // Modal
    window.t_abrirModal = function() {
        document.getElementById('t_modal').style.display = 'flex';
        document.getElementById('t_titulo').focus();
    };
    window.t_cerrarModal = function() {
        document.getElementById('t_modal').style.display = 'none';
        ['t_titulo','t_desc','t_categoria','t_fecha','t_hora'].forEach(function(id){
            document.getElementById(id).value = '';
        });
        document.getElementById('t_prioridad').value = 'media';
        document.getElementById('t_modalMsg').style.display = 'none';
    };

    // Guardar
    window.t_guardar = function() {
        var titulo = document.getElementById('t_titulo').value.trim();
        var fecha  = document.getElementById('t_fecha').value;
        var msg    = document.getElementById('t_modalMsg');
        if (!titulo) { msg.textContent='⚠ El título es obligatorio'; msg.style.display='block'; return; }
        if (!fecha)  { msg.textContent='⚠ La fecha límite es obligatoria'; msg.style.display='block'; return; }
        var btn = document.getElementById('t_btnGuardar');
        btn.disabled = true; btn.innerHTML = 'Guardando...';
        msg.style.display = 'none';
        post({
            accion: 'crear',
            titulo: titulo,
            descripcion: document.getElementById('t_desc').value.trim(),
            prioridad: document.getElementById('t_prioridad').value,
            categoria: document.getElementById('t_categoria').value.trim(),
            fecha_limite: fecha,
            hora: document.getElementById('t_hora').value
        }, function(err, data) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-check"></i> Guardar';
            if (err) { msg.textContent = '✗ ' + err; msg.style.display='block'; return; }
            if (data.ok) { t_cerrarModal(); recargar(); }
            else { msg.textContent = '✗ ' + (data.msg || 'Error al guardar'); msg.style.display='block'; }
        });
    };

    // Completar
    window.t_completar = function(check) {
        var item = check.closest('.tarea-item');
        var id   = item.getAttribute('data-id');
        check.style.opacity = '0.4';
        post({ accion:'completar', id:id }, function(err, data) {
            if (err || !data.ok) { check.style.opacity='1'; alert('No se pudo completar'); return; }
            check.classList.add('done');
            check.innerHTML = '<i class="fa-solid fa-check"></i>';
            check.style.opacity = '1';
            item.querySelector('h3').classList.add('tachado');
            setTimeout(recargar, 600);
        });
    };

    // Eliminar
    window.t_eliminar = function(id) {
        if (!confirm('¿Eliminar esta tarea?')) return;
        post({ accion:'eliminar', id:id }, function(err, data) {
            if (err || !data.ok) { alert('No se pudo eliminar'); return; }
            recargar();
        });
    };

    // Filtros
    window.t_toggleFiltros = function() {
        var el = document.getElementById('t_filtros');
        el.style.display = el.style.display === 'flex' ? 'none' : 'flex';
    };
    window.t_aplicarFiltro = function() {
        var prio = document.getElementById('t_fPrio').value;
        var est  = document.getElementById('t_fEst').value;
        document.querySelectorAll('.tarea-item').forEach(function(item){
            var okP = !prio || item.getAttribute('data-prio') === prio;
            var okE = !est  || item.getAttribute('data-estado') === est;
            item.style.display = (okP && okE) ? '' : 'none';
        });
    };
    window.t_limpiarFiltro = function() {
        document.getElementById('t_fPrio').value = '';
        document.getElementById('t_fEst').value  = '';
        document.querySelectorAll('.tarea-item').forEach(function(i){ i.style.display=''; });
    };

    // Exportar CSV
    window.t_exportar = function() {
        var rows = [['Título','Descripción','Prioridad','Categoría','Estado']];
        document.querySelectorAll('.tarea-item').forEach(function(item){
            var titulo = item.querySelector('h3').textContent.trim();
            var prio   = item.getAttribute('data-prio');
            var estado = item.getAttribute('data-estado');
            rows.push([titulo, '', prio, '', estado]);
        });
        var csv = rows.map(function(r){
            return r.map(function(c){ return '"'+String(c).replace(/"/g,'""')+'"'; }).join(',');
        }).join('\n');
        var blob = new Blob(['\uFEFF'+csv], {type:'text/csv;charset=utf-8;'});
        var a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = 'tareas_'+new Date().toISOString().slice(0,10)+'.csv';
        document.body.appendChild(a); a.click(); document.body.removeChild(a);
    };

    // Cerrar modal con Escape
    document.addEventListener('keydown', function(e){
        if (e.key === 'Escape') t_cerrarModal();
    });
})();
</script>