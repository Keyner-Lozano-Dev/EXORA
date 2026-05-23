<?php

include('connection.php');

$con = connection();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="favicon.png">
    <title>EXORA | Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="CSS/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/lightweight-charts@3.8.0/dist/lightweight-charts.standalone.production.js"></script>
</head>
<body>

    <!-- Formas decorativas -->
    <div class="deco deco-1"></div>
    <div class="deco deco-2"></div>
    <div class="deco deco-3"></div>
    <div class="deco deco-4"></div>
    <div class="deco deco-5"></div>
    <div class="deco deco-6"></div>
    <div class="deco deco-7"></div>

    <div class="dashboard-wrapper">

        <!-- SIDEBAR -->
        <div class="sidebar">
            <div>
                <h1 class="logo">EXORA</h1>
                <div class="menu">

                    <a href="#" class="active" onclick="cargarSeccion('dashboard', this); return false;">
                        <i class="fa-solid fa-calendar-days"></i>
                        Dashboard
                    </a>

                    <a href="#" onclick="cargarSeccion('agenda', this); return false;">
                        <i class="fa-solid fa-calendar-week"></i>
                        Agenda
                    </a>

                    <a href="#" onclick="cargarSeccion('eventos', this); return false;">
                        <i class="fa-solid fa-clock"></i>
                        Eventos
                    </a>

                    <a href="#" onclick="cargarSeccion('tareas', this); return false;">
                        <i class="fa-solid fa-list-check"></i>
                        Tareas
                    </a>

                    <a href="#" onclick="cargarSeccion('recordatorios', this); return false;">
                        <i class="fa-solid fa-bell"></i>
                        Recordatorios
                    </a>

                </div>
            </div>
            <a href="logout.php" class="logout">
                Cerrar Sesión
            </a>
        </div>

        <!-- MAIN -->
        <div class="main" id="main-content">
            <!-- El contenido se carga dinámicamente -->
        </div>

    </div>

    <script src="JavaScript/dashboard.js"></script>

</body>
</html>