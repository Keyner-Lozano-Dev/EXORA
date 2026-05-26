<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include_once __DIR__ . '/../connection.php';
$con = connection();
$id_usuario = (int)($_SESSION['user_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['accion'])) {
    header('Content-Type: application/json; charset=utf-8');

    if ($_POST['accion'] === 'crear') {
        $nombre    = trim($_POST['nombre']          ?? '');
        $precio    = trim($_POST['precio']          ?? '0');
        $fecha     = trim($_POST['fecha_caducidad'] ?? '');
        $stock     = trim($_POST['stock']           ?? '0');
        $categoria = trim($_POST['categoria']       ?? '');

        if ($nombre === '' || $fecha === '') {
            echo json_encode(['ok' => false, 'msg' => 'Nombre y fecha son obligatorios']);
            exit;
        }

        $sql  = "INSERT INTO productos (id_usuario, nombre, precio, fecha_caducidad, stock, categoria) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_bind_param($stmt, 'isssss', $id_usuario, $nombre, $precio, $fecha, $stock, $categoria);

        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['ok' => true]);
        } else {
            echo json_encode(['ok' => false, 'msg' => mysqli_stmt_error($stmt)]);
        }
        mysqli_stmt_close($stmt);
        exit;
    }

    if ($_POST['accion'] === 'eliminar') {
        $id   = (int)($_POST['id'] ?? 0);
        $stmt = mysqli_prepare($con, "DELETE FROM productos WHERE id=? AND id_usuario=?");
        mysqli_stmt_bind_param($stmt, 'ii', $id, $id_usuario);
        echo json_encode(['ok' => mysqli_stmt_execute($stmt)]);
        mysqli_stmt_close($stmt);
        exit;
    }

    echo json_encode(['ok' => false, 'msg' => 'Acción desconocida']);
    exit;
}

$stmt = mysqli_prepare($con, "SELECT * FROM productos WHERE id_usuario=? ORDER BY fecha_caducidad ASC");
mysqli_stmt_bind_param($stmt, 'i', $id_usuario);
mysqli_stmt_execute($stmt);
$res      = mysqli_stmt_get_result($stmt);
$productos = [];
while ($r = mysqli_fetch_assoc($res)) $productos[] = $r;
mysqli_stmt_close($stmt);

$total    = count($productos);
$hoy      = date('Y-m-d');
$proximos = array_filter($productos, fn($p) => $p['fecha_caducidad'] >= $hoy && $p['fecha_caducidad'] <= date('Y-m-d', strtotime('+7 days')));
$vencidos = array_filter($productos, fn($p) => $p['fecha_caducidad'] < $hoy);
$sin_stock= array_filter($productos, fn($p) => (int)$p['stock'] === 0);

function estado_fecha(string $f): array {
    $hoy  = date('Y-m-d');
    $dias = (strtotime($f) - strtotime($hoy)) / 86400;
    if ($dias < 0)  return ['vencido', 'Vencido'];
    if ($dias <= 7) return ['proximo', 'Vence pronto'];
    return ['ok', 'Vigente'];
}
?>

<!-- TOPBAR -->
<div class="topbar">
    <div class="topbar-top">
        <div class="welcome"><h1>Productos</h1></div>
        <?php if (!empty($_SESSION['foto'])): ?>
            <a href="secciones/perfil.php" class="profile" style="text-decoration:none;padding:0;overflow:hidden;">
                <img src="<?= htmlspecialchars($_SESSION['foto']) ?>" style="width:48px;height:48px;border-radius:50%;object-fit:cover;display:block;border:2px solid #0e0e14;box-shadow:3px 3px 0 #0e0e14;">
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
        <button class="tool-btn purple" onclick="productos.abrirModal()">
            <i class="fa-solid fa-plus"></i> Nuevo producto
        </button>
        <button class="tool-btn orange" onclick="productos.toggleFiltros()">
            <i class="fa-solid fa-filter"></i> Filtrar
        </button>
    </div>
</div>

<!-- FILTROS -->
<div id="prod-filtros" style="display:none;" class="tareas-box">
    <div style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
        <div class="campo">
            <label>Buscar</label>
            <input type="text" id="prod-fBuscar" placeholder="Nombre o categoría..." oninput="productos.aplicarFiltro()">
        </div>
        <div class="campo">
            <label>Estado</label>
            <select id="prod-fEst" onchange="productos.aplicarFiltro()">
                <option value="">Todos</option>
                <option value="ok">Vigente</option>
                <option value="proximo">Vence pronto</option>
                <option value="vencido">Vencido</option>
            </select>
        </div>
        <button class="tool-btn" onclick="productos.limpiarFiltro()">Limpiar</button>
    </div>
</div>

<!-- LAYOUT -->
<div class="tareas-layout">

    <div class="tareas-col" style="flex:2;">
        <div class="tareas-box">
            <div class="tareas-box-title">
                Inventario
                <span style="font-size:13px;font-weight:500;color:var(--muted);">(<?= $total ?>)</span>
            </div>
            <?php if (empty($productos)): ?>
                <p style="padding:16px 0;color:var(--muted);font-size:14px;">No hay productos. ¡Agrega uno!</p>
            <?php else: ?>
            <div class="prod-tabla-header">
                <span style="width:50px;">ID</span>
                <span style="flex:1;">Nombre</span>
                <span style="width:90px;">Precio</span>
                <span style="width:80px;">Stock</span>
                <span style="width:110px;">Caducidad</span>
                <span style="width:110px;">Estado</span>
                <span style="width:40px;"></span>
            </div>
            <div id="prod-lista">
            <?php foreach ($productos as $p):
                [$est_clase, $est_label] = estado_fecha($p['fecha_caducidad']);
            ?>
                <div class="prod-fila"
                     data-id="<?= $p['id'] ?>"
                     data-nombre="<?= strtolower(htmlspecialchars($p['nombre'])) ?>"
                     data-cat="<?= strtolower(htmlspecialchars($p['categoria'] ?? '')) ?>"
                     data-estado="<?= $est_clase ?>">
                    <span class="prod-id">#<?= $p['id'] ?></span>
                    <div class="prod-nombre-wrap">
                        <strong><?= htmlspecialchars($p['nombre']) ?></strong>
                        <?php if (!empty($p['categoria'])): ?>
                            <small><?= htmlspecialchars($p['categoria']) ?></small>
                        <?php endif; ?>
                    </div>
                    <span class="prod-precio">$<?= number_format($p['precio'], 0, ',', '.') ?></span>
                    <span class="prod-stock <?= (int)$p['stock'] === 0 ? 'stock-cero' : '' ?>"><?= $p['stock'] ?> un.</span>
                    <span class="prod-fecha"><?= date('d M Y', strtotime($p['fecha_caducidad'])) ?></span>
                    <span class="prod-badge <?= $est_clase ?>"><?= $est_label ?></span>
                    <button class="t-btn-eliminar" onclick="productos.eliminar(<?= $p['id'] ?>)">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="tareas-col">
        <div class="tareas-box">
            <div class="tareas-box-title">Resumen</div>
            <div class="tareas-stat-grid">
                <div class="tareas-stat"><h2><?= $total ?></h2><p>Total</p></div>
                <div class="tareas-stat"><h2><?= count($vencidos) ?></h2><p>Vencidos</p></div>
                <div class="tareas-stat"><h2><?= count($proximos) ?></h2><p>Vencen pronto</p></div>
                <div class="tareas-stat"><h2><?= count($sin_stock) ?></h2><p>Sin stock</p></div>
            </div>
        </div>

        <?php
        $cats = [];
        foreach ($productos as $p) {
            $c = trim($p['categoria'] ?? '') ?: 'Sin categoría';
            $cats[$c] = ($cats[$c] ?? 0) + 1;
        }
        $colores = ['#e04e1a','#7b2cbf','#f5e100','#0e0e14','#0ea5e9','#16a34a'];
        ?>
        <?php if (!empty($cats)): ?>
        <div class="tareas-box">
            <div class="tareas-box-title">Por categoría</div>
            <?php $ci = 0; foreach ($cats as $cat => $cnt): ?>
                <div class="tareas-cat-item">
                    <div class="tareas-cat-dot" style="background:<?= $colores[$ci % count($colores)] ?>;"></div>
                    <span><?= htmlspecialchars($cat) ?></span>
                    <small><?= $cnt ?> producto<?= $cnt !== 1 ? 's' : '' ?></small>
                </div>
            <?php $ci++; endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($proximos) || !empty($vencidos)): ?>
        <div class="tareas-box">
            <div class="tareas-box-title">⚠ Alertas</div>
            <?php foreach ($vencidos as $p): ?>
                <div class="prod-alerta vencido">
                    <strong><?= htmlspecialchars($p['nombre']) ?></strong>
                    <span>Venció el <?= date('d M Y', strtotime($p['fecha_caducidad'])) ?></span>
                </div>
            <?php endforeach; ?>
            <?php foreach ($proximos as $p): ?>
                <div class="prod-alerta proximo">
                    <strong><?= htmlspecialchars($p['nombre']) ?></strong>
                    <span>Vence <?= date('d M Y', strtotime($p['fecha_caducidad'])) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

</div>

<!-- MODAL — igual que tareas, directo en el HTML -->
<div id="prod-modal" class="t-modal-overlay" style="display:none;">
    <div class="t-modal-box">
        <div class="t-modal-header">
            <h2>Nuevo Producto</h2>
            <button class="modal-close" onclick="productos.cerrarModal()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="t-modal-body">
            <div class="campo"><label>Nombre *</label><input type="text" id="p-nombre" placeholder="Nombre del producto"></div>
            <div class="campo-row">
                <div class="campo"><label>Precio</label><input type="number" id="p-precio" placeholder="0" min="0" step="0.01"></div>
                <div class="campo"><label>Stock</label><input type="number" id="p-stock" placeholder="0" min="0"></div>
            </div>
            <div class="campo-row">
                <div class="campo"><label>Fecha caducidad *</label><input type="date" id="p-fecha"></div>
                <div class="campo"><label>Categoría</label><input type="text" id="p-categoria" placeholder="Ej: Lácteos"></div>
            </div>
            <p id="p-error" style="color:#e04e1a;font-size:13px;display:none;margin:0;"></p>
        </div>
        <div class="t-modal-footer">
            <button class="btn-cancelar" onclick="productos.cerrarModal()">Cancelar</button>
            <button class="btn-guardar" id="p-btnGuardar" onclick="productos.guardar()">
                <i class="fa-solid fa-check"></i> Guardar
            </button>
        </div>
    </div>
</div>

<style>
/* Modal */
.t-modal-overlay{position:fixed;inset:0;background:rgba(14,14,20,.5);z-index:9999;display:flex;align-items:center;justify-content:center;backdrop-filter:blur(4px);}
.t-modal-box{background:var(--card);border:2px solid var(--black);border-radius:22px;box-shadow:8px 8px 0 var(--black);width:480px;max-width:95vw;overflow:hidden;}
.t-modal-header{padding:20px 24px;border-bottom:2px solid var(--line);display:flex;justify-content:space-between;align-items:center;}
.t-modal-header h2{font-size:18px;font-weight:800;color:var(--black);font-family:var(--font);margin:0;}
.t-modal-body{padding:24px;display:flex;flex-direction:column;gap:16px;}
.t-modal-footer{padding:16px 24px;border-top:2px solid var(--line);display:flex;justify-content:flex-end;gap:12px;}
.modal-close{width:32px;height:32px;border-radius:8px;border:2px solid var(--black);background:var(--bg);cursor:pointer;font-size:14px;display:flex;align-items:center;justify-content:center;transition:all .15s;}
.modal-close:hover{background:var(--black);color:var(--card);}
.campo{display:flex;flex-direction:column;gap:6px;flex:1;}
.campo label{font-size:12px;font-weight:700;color:var(--black);font-family:var(--font);text-transform:uppercase;letter-spacing:.5px;}
.campo input,.campo textarea,.campo select{padding:10px 14px;border:2px solid var(--black);border-radius:10px;background:var(--bg);font-family:var(--font);font-size:14px;color:var(--black);outline:none;transition:box-shadow .15s;resize:none;}
.campo input:focus,.campo select:focus{box-shadow:3px 3px 0 var(--black);}
.campo-row{display:flex;gap:12px;}
.btn-cancelar{padding:10px 20px;border:2px solid var(--black);border-radius:10px;background:var(--bg);font-family:var(--font);font-size:14px;font-weight:600;cursor:pointer;transition:all .15s;}
.btn-cancelar:hover{background:var(--black);color:var(--card);}
.btn-guardar{padding:10px 20px;border:2px solid var(--black);border-radius:10px;background:var(--black);color:var(--card);font-family:var(--font);font-size:14px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:8px;box-shadow:3px 3px 0 #7b2cbf;transition:all .15s;}
.btn-guardar:hover{transform:translateY(-2px);box-shadow:5px 5px 0 #7b2cbf;}
/* Tabla */
.prod-tabla-header{display:flex;align-items:center;gap:12px;padding:8px 14px;border-radius:10px;background:var(--line);margin-bottom:8px;font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;}
.prod-fila{display:flex;align-items:center;gap:12px;padding:12px 14px;border-radius:12px;border:2px solid var(--line);background:var(--bg);margin-bottom:8px;transition:border-color .2s;}
.prod-fila:hover{border-color:var(--black);}
.prod-id{width:50px;font-size:12px;font-weight:700;color:var(--muted);}
.prod-nombre-wrap{flex:1;min-width:0;}
.prod-nombre-wrap strong{display:block;font-size:13px;font-weight:700;color:var(--black);}
.prod-nombre-wrap small{font-size:11px;color:var(--muted);}
.prod-precio{width:90px;font-size:13px;font-weight:600;color:var(--black);}
.prod-stock{width:80px;font-size:13px;font-weight:600;}
.prod-stock.stock-cero{color:#e04e1a;}
.prod-fecha{width:110px;font-size:12px;color:var(--muted);}
.prod-badge{width:110px;padding:4px 10px;border-radius:20px;font-size:10px;font-weight:700;border:1.5px solid var(--black);text-align:center;white-space:nowrap;}
.prod-badge.ok{background:#dcfce7;color:#166534;}
.prod-badge.proximo{background:#fef9c3;color:#854d0e;}
.prod-badge.vencido{background:#fee2e2;color:#991b1b;}
.prod-alerta{display:flex;flex-direction:column;gap:2px;padding:10px 12px;border-radius:10px;margin-bottom:8px;border:1.5px solid;}
.prod-alerta strong{font-size:13px;}
.prod-alerta span{font-size:11px;}
.prod-alerta.vencido{background:#fee2e2;border-color:#fca5a5;color:#991b1b;}
.prod-alerta.proximo{background:#fef9c3;border-color:#fde68a;color:#854d0e;}
#prod-filtros .campo input,
#prod-filtros .campo select{padding:10px 14px;border:2px solid var(--black);border-radius:10px;background:var(--bg);font-family:var(--font);font-size:14px;color:var(--black);outline:none;}
.t-btn-eliminar{background:none;border:1.5px solid var(--line);color:var(--muted);border-radius:8px;padding:5px 8px;cursor:pointer;font-size:11px;flex-shrink:0;transition:all .15s;}
.t-btn-eliminar:hover{border-color:#e04e1a;color:#e04e1a;}
</style>

<script>
var productos = (function() {

    var URL_ENDPOINT = window.location.origin + '/secciones/productos.php';

    function post(datos, cb) {
        var fd = new FormData();
        Object.keys(datos).forEach(function(k) { fd.append(k, datos[k]); });
        fetch(URL_ENDPOINT, { method: 'POST', body: fd })
            .then(function(r) { return r.text(); })
            .then(function(txt) {
                try { cb(null, JSON.parse(txt)); }
                catch(e) { cb('Respuesta inesperada del servidor'); }
            })
            .catch(function(e) { cb('Error de red: ' + e.message); });
    }

    function recargar() {
        var linkActivo = document.querySelector('.menu a.active');
        if (linkActivo) cargarSeccion('productos', linkActivo);
    }

    function abrirModal() {
        document.getElementById('prod-modal').style.display = 'flex';
        document.getElementById('p-nombre').focus();
    }

    function cerrarModal() {
        document.getElementById('prod-modal').style.display = 'none';
        document.getElementById('p-error').style.display = 'none';
        ['p-nombre','p-precio','p-stock','p-fecha','p-categoria'].forEach(function(id) {
            document.getElementById(id).value = '';
        });
        var btn = document.getElementById('p-btnGuardar');
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-check"></i> Guardar';
    }

    function guardar() {
        var nombre = document.getElementById('p-nombre').value.trim();
        var fecha  = document.getElementById('p-fecha').value.trim();
        var err    = document.getElementById('p-error');

        if (!nombre) { err.textContent = '⚠ El nombre es obligatorio'; err.style.display = 'block'; return; }
        if (!fecha)  { err.textContent = '⚠ La fecha de caducidad es obligatoria'; err.style.display = 'block'; return; }

        err.style.display = 'none';
        var btn = document.getElementById('p-btnGuardar');
        btn.disabled = true;
        btn.innerHTML = 'Guardando...';

        post({
            accion:          'crear',
            nombre:          nombre,
            precio:          document.getElementById('p-precio').value || '0',
            stock:           document.getElementById('p-stock').value || '0',
            fecha_caducidad: fecha,
            categoria:       document.getElementById('p-categoria').value.trim()
        }, function(error, data) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-check"></i> Guardar';
            if (error || !data.ok) {
                err.textContent = '✗ ' + (error || data.msg || 'No se pudo guardar');
                err.style.display = 'block';
                return;
            }
            cerrarModal();
            recargar();
        });
    }

    function eliminar(id) {
        if (!confirm('¿Eliminar este producto?')) return;
        post({ accion: 'eliminar', id: id }, function(error, data) {
            if (error || !data.ok) { alert('No se pudo eliminar'); return; }
            recargar();
        });
    }

    function toggleFiltros() {
        var el = document.getElementById('prod-filtros');
        el.style.display = el.style.display === 'flex' ? 'none' : 'flex';
    }

    function aplicarFiltro() {
        var buscar = (document.getElementById('prod-fBuscar').value || '').toLowerCase();
        var est    = document.getElementById('prod-fEst').value;
        document.querySelectorAll('.prod-fila').forEach(function(fila) {
            var okB = !buscar || fila.getAttribute('data-nombre').includes(buscar) || fila.getAttribute('data-cat').includes(buscar);
            var okE = !est   || fila.getAttribute('data-estado') === est;
            fila.style.display = (okB && okE) ? '' : 'none';
        });
    }

    function limpiarFiltro() {
        document.getElementById('prod-fBuscar').value = '';
        document.getElementById('prod-fEst').value    = '';
        document.querySelectorAll('.prod-fila').forEach(function(f) { f.style.display = ''; });
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') cerrarModal();
    });

    return { abrirModal, cerrarModal, guardar, eliminar, toggleFiltros, aplicarFiltro, limpiarFiltro };
})();
</script>