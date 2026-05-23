<?php
session_start();
include('../connection.php');
$con = connection();

header('Content-Type: application/json');

$usuario_id = $_SESSION['user_id'] ?? 0;
if (!$usuario_id) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$titulo     = trim($data['titulo'] ?? '');
$descripcion = trim($data['descripcion'] ?? '');
$fecha      = $data['fecha'] ?? '';
$hora_inicio = $data['hora_inicio'] ?? '';
$hora_fin   = $data['hora_fin'] ?? '';
$color      = $data['color'] ?? '#6c63ff';

if (!$titulo || !$fecha || !$hora_inicio || !$hora_fin) {
    echo json_encode(['success' => false, 'message' => 'Campos incompletos']);
    exit;
}

$stmt = $con->prepare("INSERT INTO citas (usuario_id, titulo, descripcion, fecha, hora_inicio, hora_fin, color) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("issssss", $usuario_id, $titulo, $descripcion, $fecha, $hora_inicio, $hora_fin, $color);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $con->error]);
}