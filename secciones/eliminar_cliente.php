<?php
session_start();
include('../connection.php');
$con = connection();
header('Content-Type: application/json');
$usuario_id = $_SESSION['user_id'] ?? 0;
if (!$usuario_id) { echo json_encode(['success'=>false,'message'=>'No autenticado']); exit; }
$data = json_decode(file_get_contents('php://input'), true);
$id = (int)($data['id'] ?? 0);
if (!$id) { echo json_encode(['success'=>false,'message'=>'ID inválido']); exit; }
$stmt = $con->prepare("DELETE FROM clientes WHERE id = ? AND id_usuario = ?");
$stmt->bind_param("ii", $id, $usuario_id);
echo $stmt->execute() ? json_encode(['success'=>true]) : json_encode(['success'=>false,'message'=>$con->error]);