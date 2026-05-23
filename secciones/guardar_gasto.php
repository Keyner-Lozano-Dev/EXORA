<?php
session_start();
include('../connection.php');
$con = connection();
header('Content-Type: application/json');
$usuario_id = $_SESSION['user_id'] ?? 0;
if (!$usuario_id) { echo json_encode(['success'=>false,'message'=>'No autenticado']); exit; }
$data = json_decode(file_get_contents('php://input'), true);
$desc  = trim($data['descripcion'] ?? '');
$monto = floatval($data['monto'] ?? 0);
if (!$monto) { echo json_encode(['success'=>false,'message'=>'Monto requerido']); exit; }
$stmt = $con->prepare("INSERT INTO gastos (id_usuario, descripcion, monto) VALUES (?,?,?)");
$stmt->bind_param("isd", $usuario_id, $desc, $monto);
echo $stmt->execute() ? json_encode(['success'=>true]) : json_encode(['success'=>false,'message'=>$con->error]);