<?php
header('Content-Type: application/json');
session_start();
include('../connection.php');
$con = connection();

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_usuario = $_SESSION['user_id'];
    $titulo = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $prioridad = $_POST['prioridad'] ?? 'media';
    $categoria = trim($_POST['categoria'] ?? '');
    $fecha_limite = $_POST['fecha_limite'] ?? null;
    $hora = $_POST['hora'] ?? null;

    if ($titulo === '' || !$fecha_limite) {
        echo json_encode(['success' => false, 'error' => 'Título y fecha límite son obligatorios']);
        exit;
    }

    $stmt = $con->prepare("INSERT INTO tareas (id_usuario, titulo, descripcion, prioridad, categoria, fecha_limite, hora, completada, fecha_creacion) VALUES (?, ?, ?, ?, ?, ?, ?, 0, NOW())");
    $stmt->bind_param("issssss", $id_usuario, $titulo, $descripcion, $prioridad, $categoria, $fecha_limite, $hora);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Tarea creada correctamente']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al crear la tarea']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
}
?>
