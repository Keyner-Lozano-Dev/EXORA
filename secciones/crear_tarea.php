<?php
session_start();
include('../connection.php');
$con = connection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_usuario = $_SESSION['user_id'] ?? 1; // Cambia si tienes sistema de login
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
