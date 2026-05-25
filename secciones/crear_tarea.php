<?php
session_start();
include('../connection.php');
$con = connection();

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    $_SESSION['error'] = "Debes estar autenticado para crear tareas.";
    header("Location: ../index.php");
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
        $_SESSION['error'] = "Título y fecha límite son obligatorios.";
        header("Location: tareas.php");
        exit;
    }

    $stmt = $con->prepare("INSERT INTO tareas (id_usuario, titulo, descripcion, prioridad, categoria, fecha_limite, hora, completada, fecha_creacion) VALUES (?, ?, ?, ?, ?, ?, ?, 0, NOW())");
    $stmt->bind_param("issssss", $id_usuario, $titulo, $descripcion, $prioridad, $categoria, $fecha_limite, $hora);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Tarea creada correctamente.";
    } else {
        $_SESSION['error'] = "Error al crear la tarea.";
    }
    $stmt->close();

    header("Location: tareas.php");
    exit;
} else {
    header("Location: tareas.php");
    exit;
}
?>
