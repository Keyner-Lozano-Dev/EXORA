<?php
session_start();
include('../connection.php');
$con = connection();
header('Content-Type: application/json');
$usuario_id = $_SESSION['user_id'] ?? 0;
if (!$usuario_id) { echo json_encode(['success'=>false,'message'=>'No autenticado']); exit; }
$data = json_decode(file_get_contents('php://input'), true);
$nombre   = trim($data['nombre'] ?? '');
$email    = trim($data['email'] ?? '');
$telefono = trim($data['telefono'] ?? '');
if (!$nombre) { echo json_encode(['success'=>false,'message'=>'Nombre requerido']); exit; }
$stmt = $con->prepare("INSERT INTO clientes (id_usuario, nombre, email, telefono) VALUES (?,?,?,?)");
$stmt->bind_param("isss", $usuario_id, $nombre, $email, $telefono);
echo $stmt->execute() ? json_encode(['success'=>true]) : json_encode(['success'=>false,'message'=>$con->error]);