<?php

function connection(){
    $host = "localhost";
    $username = "u503005013_root";
    $password = "K1109381992l.";
    $bd = "u503005013_exoraD";

    // Crear conexión con timeout
    $connect = mysqli_connect($host, $username, $password, $bd);

    // Verificar si la conexión fue exitosa
    if (!$connect) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false, 
            'error' => 'Error de conexión a BD: ' . mysqli_connect_error()
        ]);
        exit;
    }

    // Configurar charset a UTF-8
    mysqli_set_charset($connect, "utf8mb4");

    return $connect;
}

?>