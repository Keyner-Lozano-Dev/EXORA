<?php
session_start();
include('../connection.php');
$con = connection();
$id_usuario = $_SESSION['user_id'];
?>

<!-- TOPBAR + TOOLBAR -->
<div class="topbar">
    <div class="topbar-top">
        <div class="welcome">
            <h1>Tareas</h1>
        </div>
        <a href="secciones/perfil.php" class="profile" style="text-decoration:none;">E</a>
    </div>
    <div class="toolbar">
        <span class="toolbar-label">Acciones</span>
        <div class="tb-divider"></div>
        <button class="tool-btn purple">
            <i class="fa-solid fa-plus"></i>
            Nueva tarea
        </button>
        <button class="tool-btn orange">
            <i class="fa-solid fa-filter"></i>
            Filtrar
        </button>
        <div class="spacer"></div>
        <button class="tool-btn dark">
            <i class="fa-solid fa-download"></i>
            Exportar
        </button>
    </div>
</div>

<!-- LAYOUT TAREAS -->
<div class="tareas-layout">

    <!-- COLUMNA IZQUIERDA -->
    <div class="tareas-col">

        <!-- PENDIENTES -->
        <div class="tareas-box">
            <div class="tareas-box-title">
                Pendientes
                <button class="tareas-add-btn">
                    <i class="fa-solid fa-plus"></i>
                    Agregar
                </button>
            </div>

            <div class="tarea-item">
                <div class="tarea-check"></div>
                <div class="tarea-info">
                    <h3>Llamar a proveedor Almacenes XYZ</h3>
                    <p><i class="fa-solid fa-calendar"></i> Hoy · 10:00 AM</p>
                </div>
                <span class="tarea-badge alta">Alta</span>
            </div>

            <div class="tarea-item">
                <div class="tarea-check"></div>
                <div class="tarea-info">
                    <h3>Revisar inventario bodega 2</h3>
                    <p><i class="fa-solid fa-calendar"></i> Hoy · 02:00 PM</p>
                </div>
                <span class="tarea-badge media">Media</span>
            </div>

            <div class="tarea-item">
                <div class="tarea-check"></div>
                <div class="tarea-info">
                    <h3>Actualizar precios temporada</h3>
                    <p><i class="fa-solid fa-calendar"></i> Mañana</p>
                </div>
                <span class="tarea-badge baja">Baja</span>
            </div>

            <div class="tarea-item">
                <div class="tarea-check"></div>
                <div class="tarea-info">
                    <h3>Enviar cotización cliente Pérez</h3>
                    <p><i class="fa-solid fa-calendar"></i> 24 may</p>
                </div>
                <span class="tarea-badge alta">Alta</span>
            </div>
        </div>

        <!-- COMPLETADAS -->
        <div class="tareas-box">
            <div class="tareas-box-title">Completadas hoy</div>

            <div class="tarea-item">
                <div class="tarea-check done">
                    <i class="fa-solid fa-check"></i>
                </div>
                <div class="tarea-info">
                    <h3 class="tachado">Registrar ventas del lunes</h3>
                    <p>Completada · 09:15 AM</p>
                </div>
                <span class="tarea-badge baja">Baja</span>
            </div>

            <div class="tarea-item">
                <div class="tarea-check done">
                    <i class="fa-solid fa-check"></i>
                </div>
                <div class="tarea-info">
                    <h3 class="tachado">Confirmar pedido con distribuidor</h3>
                    <p>Completada · 11:40 AM</p>
                </div>
                <span class="tarea-badge media">Media</span>
            </div>
        </div>

    </div>

    <!-- COLUMNA DERECHA -->
    <div class="tareas-col">

        <!-- RESUMEN -->
        <div class="tareas-box">
            <div class="tareas-box-title">Resumen</div>
            <div class="tareas-stat-grid">
                <div class="tareas-stat">
                    <h2>6</h2>
                    <p>Total tareas</p>
                </div>
                <div class="tareas-stat">
                    <h2>2</h2>
                    <p>Completadas</p>
                </div>
                <div class="tareas-stat">
                    <h2>2</h2>
                    <p>Alta prioridad</p>
                </div>
                <div class="tareas-stat">
                    <h2>33%</h2>
                    <p>Progreso</p>
                </div>
            </div>
            <div class="tareas-prog-bar">
                <div class="tareas-prog-fill" style="width: 33%;"></div>
            </div>
        </div>

        <!-- CATEGORÍAS -->
        <div class="tareas-box">
            <div class="tareas-box-title">Por categoría</div>
            <div class="tareas-cat-item">
                <div class="tareas-cat-dot" style="background:#e04e1a;"></div>
                <span>Ventas</span>
                <small>2 tareas</small>
            </div>
            <div class="tareas-cat-item">
                <div class="tareas-cat-dot" style="background:#7b2cbf;"></div>
                <span>Inventario</span>
                <small>2 tareas</small>
            </div>
            <div class="tareas-cat-item">
                <div class="tareas-cat-dot" style="background:#f5e100;"></div>
                <span>Proveedores</span>
                <small>1 tarea</small>
            </div>
            <div class="tareas-cat-item">
                <div class="tareas-cat-dot" style="background:#0e0e14;"></div>
                <span>Precios</span>
                <small>1 tarea</small>
            </div>
        </div>

    </div>
</div>