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
                    <a href="#" class="active" onclick="cargarSeccion('secciones/dashboard.php', this)">
                        <i class="fa-solid fa-calendar-days"></i>
                        Dashboard
                    </a>
                    <a href="#" onclick="cargarSeccion('secciones/agenda.php', this)">
                        <i class="fa-solid fa-calendar-week"></i>
                        Agenda
                    </a>
                    <a href="#" onclick="cargarSeccion('secciones/eventos.php', this)">
                        <i class="fa-solid fa-clock"></i>
                        Eventos
                    </a>
                    <a href="#" onclick="cargarSeccion('secciones/tareas.php', this)">
                        <i class="fa-solid fa-list-check"></i>
                        Tareas
                    </a>
                    <a href="#" onclick="cargarSeccion('secciones/recordatorios.php', this)">
                        <i class="fa-solid fa-bell"></i>
                        Recordatorios
                    </a>
                </div>
            </div>
            <a href="logout.php" class="logout">Cerrar Sesión</a>
        </div>

        <!-- MAIN -->
        <div class="main" id="main-content">
            <?php include('secciones/dashboard.php'); ?>
        </div>

    </div>

    <script src="JavaScript/dashboard.js"></script>

    <script>
    function cargarSeccion(url, el) {
        document.querySelectorAll('.menu a').forEach(a => a.classList.remove('active'));
        el.classList.add('active');
        fetch(url)
            .then(r => r.text())
            .then(html => {
                document.getElementById('main-content').innerHTML = html;
            });
    }
    </script>

</body>
</html>