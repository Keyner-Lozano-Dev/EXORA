<?php
session_start();
include('../connection.php');
$con = connection();
$id_usuario = $_SESSION['user_id'] ?? 0;
if (!$id_usuario) { die('No autenticado'); }
$tipo = $_GET['tipo'] ?? 'pdf';

// Obtener ventas
$ventas = [];
$r = mysqli_query($con, "SELECT descripcion, monto, fecha FROM ventas WHERE id_usuario = $id_usuario ORDER BY fecha DESC");
while ($f = mysqli_fetch_assoc($r)) $ventas[] = $f;

// Obtener gastos
$gastos = [];
$r = mysqli_query($con, "SELECT descripcion, monto, fecha FROM gastos WHERE id_usuario = $id_usuario ORDER BY fecha DESC");
while ($f = mysqli_fetch_assoc($r)) $gastos[] = $f;

$total_ventas = array_sum(array_column($ventas, 'monto'));
$total_gastos = array_sum(array_column($gastos, 'monto'));
$saldo = $total_ventas - $total_gastos;
$fecha_gen = date('d/m/Y H:i');

if ($tipo === 'excel') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="extracto_exora_' . date('Ymd') . '.xls"');
    echo '<html><head><meta charset="UTF-8"></head><body>';
    echo '<h2>Extracto EXORA - ' . $fecha_gen . '</h2>';
    echo '<h3>VENTAS</h3>';
    echo '<table border="1" cellpadding="6"><tr><th>Fecha</th><th>Descripcion</th><th>Monto</th></tr>';
    foreach ($ventas as $v) {
        echo '<tr><td>' . $v['fecha'] . '</td><td>' . htmlspecialchars($v['descripcion']) . '</td><td>$' . number_format($v['monto'], 0, ',', '.') . '</td></tr>';
    }
    echo '<tr><td colspan="2"><b>TOTAL VENTAS</b></td><td><b>$' . number_format($total_ventas, 0, ',', '.') . '</b></td></tr>';
    echo '</table><br>';
    echo '<h3>GASTOS</h3>';
    echo '<table border="1" cellpadding="6"><tr><th>Fecha</th><th>Descripcion</th><th>Monto</th></tr>';
    foreach ($gastos as $g) {
        echo '<tr><td>' . $g['fecha'] . '</td><td>' . htmlspecialchars($g['descripcion']) . '</td><td>$' . number_format($g['monto'], 0, ',', '.') . '</td></tr>';
    }
    echo '<tr><td colspan="2"><b>TOTAL GASTOS</b></td><td><b>$' . number_format($total_gastos, 0, ',', '.') . '</b></td></tr>';
    echo '<br><h3>SALDO: $' . number_format($saldo, 0, ',', '.') . '</h3>';
    echo '</body></html>';
    exit;
}

// PDF con HTML
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Extracto EXORA</title>
<style>
  body { font-family: Arial, sans-serif; padding: 40px; color: #0e0e14; }
  h1 { font-size: 28px; margin-bottom: 4px; }
  .sub { color: #8c8878; font-size: 13px; margin-bottom: 32px; }
  h3 { font-size: 16px; margin: 24px 0 10px; border-bottom: 2px solid #0e0e14; padding-bottom: 6px; }
  table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
  th { background: #0e0e14; color: white; padding: 10px 14px; text-align: left; font-size: 13px; }
  td { padding: 10px 14px; border-bottom: 1px solid #ccc8bc; font-size: 13px; }
  .total-row td { font-weight: bold; background: #f2ede3; }
  .saldo { margin-top: 24px; padding: 20px; background: #0e0e14; color: white; border-radius: 12px; font-size: 18px; font-weight: bold; }
  .saldo span { font-size: 28px; }
  @media print { body { padding: 20px; } }
</style>
</head>
<body>
<h1>Extracto EXORA</h1>
<p class="sub">Generado el <?= $fecha_gen ?></p>

<h3>Ventas</h3>
<table>
  <tr><th>Fecha</th><th>Descripción</th><th>Monto</th></tr>
  <?php foreach ($ventas as $v): ?>
  <tr><td><?= $v['fecha'] ?></td><td><?= htmlspecialchars($v['descripcion']) ?></td><td>$<?= number_format($v['monto'], 0, ',', '.') ?></td></tr>
  <?php endforeach; ?>
  <tr class="total-row"><td colspan="2">Total Ventas</td><td>$<?= number_format($total_ventas, 0, ',', '.') ?></td></tr>
</table>

<h3>Gastos</h3>
<table>
  <tr><th>Fecha</th><th>Descripción</th><th>Monto</th></tr>
  <?php foreach ($gastos as $g): ?>
  <tr><td><?= $g['fecha'] ?></td><td><?= htmlspecialchars($g['descripcion']) ?></td><td>$<?= number_format($g['monto'], 0, ',', '.') ?></td></tr>
  <?php endforeach; ?>
  <tr class="total-row"><td colspan="2">Total Gastos</td><td>$<?= number_format($total_gastos, 0, ',', '.') ?></td></tr>
</table>

<div class="saldo">Saldo neto: <span>$<?= number_format($saldo, 0, ',', '.') ?> COP</span></div>

<script>window.onload = function() { window.print(); }</script>
</body>
</html>