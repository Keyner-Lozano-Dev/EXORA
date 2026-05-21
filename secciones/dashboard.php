<?php
session_start();
include('../connection.php');
$con = connection();
$id_usuario = $_SESSION['user_id'];

$hoy = date('Y-m-d');
$q_clientes = mysqli_query($con, "SELECT COUNT(*) as total FROM clientes 
    WHERE id_usuario = $id_usuario 
    AND DATE(fecha_registro) = '$hoy'");
$clientes_hoy = mysqli_fetch_assoc($q_clientes)['total'];

$q_ventas = mysqli_query($con, "SELECT SUM(monto) as total FROM ventas 
    WHERE id_usuario = $id_usuario");
$ventas = mysqli_fetch_assoc($q_ventas)['total'] ?? 0;

$q_gastos = mysqli_query($con, "SELECT SUM(monto) as total FROM gastos 
    WHERE id_usuario = $id_usuario");
$gastos = mysqli_fetch_assoc($q_gastos)['total'] ?? 0;

$ingresos = $ventas - $gastos;

$q_grafica = mysqli_query($con, "SELECT DATE(fecha) as dia, SUM(monto) as total 
    FROM ventas 
    WHERE id_usuario = $id_usuario 
    AND fecha >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(fecha)
    ORDER BY dia ASC");

$labels = [];
$datos = [];
while($fila = mysqli_fetch_assoc($q_grafica)) {
    $labels[] = $fila['dia'];
    $datos[] = $fila['total'];
}
?>

<div class="topbar">
    <div class="welcome">
        <h1>Herramientas</h1>
        
    </div>
    <a href="secciones/perfil.php" class="profile" style="text-decoration:none;">EXORA</a>

</div>

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