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
$id = (int)($data['id'] ?? 0);

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'ID inválido']);
    exit;
}

// Solo elimina si la cita pertenece al usuario
$stmt = $con->prepare("DELETE FROM citas WHERE id = ? AND usuario_id = ?");
$stmt->bind_param("ii", $id, $usuario_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $con->error]);
}