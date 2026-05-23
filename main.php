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
<script>
function cargarSeccion(url, el) {
    // Quitar active de todos
    document.querySelectorAll('.menu a').forEach(a => a.classList.remove('active'));
    el.classList.add('active');

    // Cargar contenido
    fetch(url)
        .then(r => r.text())
        .then(html => {
            document.getElementById('main-content').innerHTML = html;
        });
}
</script>
</div>
            </div>
            <a href="logout.php" class="logout">
                Cerrar Sesión
            </a>
        </div>

        <!-- MAIN -->
        <!-- MAIN -->
<div class="main" id="main-content">

    <!-- TOPBAR -->

    <div class="topbar">

        <div class="welcome">

            <h1>Agenda</h1>

            <p>
                Organización y planificación EXORA
            </p>

        </div>

        <div class="profile">
            E
        </div>

    </div>

    <!-- CONTENIDO -->

    <div class="agenda-layout">

        <!-- IZQUIERDA -->

        <div class="calendar-box">

            <div class="calendar-header">

                <h2>Calendario</h2>

                <span>Abril 2026</span>

            </div>

           
        </div>

        <!-- DERECHA -->

        <div class="agenda-content">

            <div class="agenda-box">

                <div class="agenda-header">

                    <h2>Agenda del Día</h2>

                </div>

                <div class="task">

                    <div class="task-time">
                        08:00 AM
                    </div>

                    <div class="task-info">

                        <h3>Reunión Clientes</h3>

                        <p>Revisar solicitudes pendientes</p>

                    </div>

                </div>

                <div class="task">

                    <div class="task-time">
                        11:30 AM
                    </div>

                    <div class="task-info">

                        <h3>Actualizar Inventario</h3>

                        <p>Verificar productos nuevos</p>

                    </div>

                </div>

                <div class="task">

                    <div class="task-time">
                        03:00 PM
                    </div>

                    <div class="task-info">

                        <h3>Ventas del Día</h3>

                        <p>Analizar estadísticas</p>

                    </div>

                </div>

            </div>

            <!-- ESPACIO EXTRA -->

            <div class="table-container">

                <h2>Próximos Eventos</h2>

                <p style="color:#8c8878;">
                    Aquí podrás agregar más contenido después.
                </p>

            </div>

        </div>




</div>
    
    <script src="JavaScript/dashboard.js"></script>

</body>
</html>