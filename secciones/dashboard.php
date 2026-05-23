<?php
session_start();
include('../connection.php');
$con = connection();
$id_usuario = $_SESSION['user_id'];
$hoy = date('Y-m-d');
$q_clientes = mysqli_query($con, "SELECT COUNT(*) as total FROM clientes WHERE id_usuario = $id_usuario AND DATE(fecha_registro) = '$hoy'");
$clientes_hoy = mysqli_fetch_assoc($q_clientes)['total'];
$q_ventas = mysqli_query($con, "SELECT SUM(monto) as total FROM ventas WHERE id_usuario = $id_usuario");
$ventas = mysqli_fetch_assoc($q_ventas)['total'] ?? 0;
$q_gastos = mysqli_query($con, "SELECT SUM(monto) as total FROM gastos WHERE id_usuario = $id_usuario");
$gastos = mysqli_fetch_assoc($q_gastos)['total'] ?? 0;
$ingresos = $ventas - $gastos;
$q_grafica = mysqli_query($con, "SELECT DATE(fecha) as dia, SUM(monto) as total FROM ventas WHERE id_usuario = $id_usuario AND fecha >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY DATE(fecha) ORDER BY dia ASC");
$labels = [];
$datos = [];
while($fila = mysqli_fetch_assoc($q_grafica)) {
    $labels[] = $fila['dia'];
    $datos[] = $fila['total'];
}
?>

<div class="topbar">
    <div class="topbar-top">
        <div class="welcome"><h1>Herramientas</h1></div>
        <a href="secciones/perfil.php" class="profile" style="text-decoration:none;">logo</a>
    </div>
    <div class="toolbar">
        <span class="toolbar-label">Acciones</span>
        <div class="tb-divider"></div>

        <button class="tool-btn orange" onclick="abrirModalCliente()">
            <i class="fa-solid fa-user-plus"></i>
            Añadir cliente
        </button>

        <button class="tool-btn yellow" onclick="abrirModalTransaccion()">
            <i class="fa-solid fa-receipt"></i>
            Registrar venta
        </button>

        <div class="date-wrap">
            <i class="fa-solid fa-calendar"></i>
            <input type="date" id="fecha-sel" title="Seleccionar día" onchange="location.href='?fecha='+this.value" />
        </div>

        <button class="tool-btn purple" onclick="location.href='secciones/reportes.php'">
            <i class="fa-solid fa-chart-bar"></i>
            Ver reporte
        </button>

        <div class="spacer"></div>

        <div class="export-wrap" id="exportWrap">
            <button class="tool-btn dark" onclick="toggleExportMenu()">
                <i class="fa-solid fa-download"></i>
                Exportar
                <i class="fa-solid fa-chevron-down" style="font-size:10px;margin-left:2px;"></i>
            </button>
        </div>
    </div>
</div>

<script>
document.getElementById('fecha-sel').value = new Date().toISOString().split('T')[0];
</script>

<style>
.export-wrap { position: relative; }
.export-opt {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    font-size: 14px;
    font-weight: 600;
    font-family: var(--font);
    color: var(--black);
    text-decoration: none;
    transition: background 0.15s;
}
.export-opt:hover { background: var(--bg); }
.export-opt:first-child { border-bottom: 1.5px solid var(--line); }
</style>

<div class="cards">
    <div class="card">
        <i class="fa-solid fa-users"></i>
        <h2><?= $clientes_hoy ?></h2>
        <p>Clientes Hoy</p>
    </div>
    <div class="card">
        <i class="fa-solid fa-chart-line"></i>
        <h2>$<?= number_format($ventas, 0, ',', '.') ?></h2>
        <p>Ventas</p>
    </div>
    <div class="card">
        <i class="fa-solid fa-money-bill-wave"></i>
        <h2>$<?= number_format($ingresos, 0, ',', '.') ?></h2>
        <p>Ingresos COP</p>
    </div>
    <div class="card">
        <i class="fa-solid fa-arrow-trend-down"></i>
        <h2>$<?= number_format($gastos, 0, ',', '.') ?></h2>
        <p>Gastos COP</p>
    </div>
</div>

<div class="table-container">
    <h2>Ventas ultimos 30 dias</h2>
    <div id="graficaVentas"
         style="width:100%; height:350px;"
         data-grafica="<?= htmlspecialchars(json_encode(array_map(function($l, $d) {
             return ['time' => $l, 'value' => (float)$d];
         }, $labels, $datos))) ?>">
    </div>
</div>