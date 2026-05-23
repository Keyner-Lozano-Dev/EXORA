<?php
session_start();
include('../connection.php');
$con = connection();
$id_usuario = $_SESSION['user_id'];

$clientes = [];
$r = mysqli_query($con, "SELECT * FROM clientes WHERE id_usuario = $id_usuario ORDER BY fecha_registro DESC");
while ($f = mysqli_fetch_assoc($r)) $clientes[] = $f;
?>

<div class="topbar">
    <div class="topbar-top">
        <div class="welcome">
            <h1>Clientes</h1></div>
        <a href="secciones/perfil.php" class="profile" style="text-decoration:none;">logo</a>
    </div>
</div>

<div class="table-container">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;gap:16px;flex-wrap:wrap;">
        <h2 style="margin:0;">Lista de Clientes (<?= count($clientes) ?>)</h2>
        <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
            <div style="position:relative;">
                <i class="fa-solid fa-magnifying-glass" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--muted);font-size:13px;"></i>
                <input type="text" id="buscador-clientes" placeholder="Buscar cliente..."
                    oninput="filtrarClientes(this.value)"
                    style="padding:10px 14px 10px 36px;border:2px solid var(--black);border-radius:10px;background:var(--bg);font-family:var(--font);font-size:14px;color:var(--black);outline:none;width:220px;">
            </div>
            <button onclick="abrirModalCliente()" style="background:var(--black);color:var(--card);border:2px solid var(--black);border-radius:10px;padding:10px 18px;font-family:var(--font);font-size:14px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:8px;box-shadow:3px 3px 0 #6c63ff;transition:all .15s;">
                <i class="fa-solid fa-user-plus"></i> Nuevo Cliente
            </button>
        </div>
    </div>

    <table id="tabla-clientes">
        <thead>
            <tr>
                <th>#</th>
                <th>Nombre</th>
                <th>Email</th>
                <th>Teléfono</th>
                <th>Fecha Registro</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody id="tbody-clientes">
            <?php if (empty($clientes)): ?>
            <tr><td colspan="6" style="text-align:center;color:var(--muted);padding:32px;">No hay clientes registrados</td></tr>
            <?php else: ?>
            <?php foreach ($clientes as $i => $c): ?>
            <tr data-nombre="<?= strtolower(htmlspecialchars($c['nombre'])) ?>"
                data-email="<?= strtolower(htmlspecialchars($c['email'] ?? '')) ?>"
                data-tel="<?= htmlspecialchars($c['telefono'] ?? '') ?>">
                <td><?= $i + 1 ?></td>
                <td><strong><?= htmlspecialchars($c['nombre']) ?></strong></td>
                <td><?= htmlspecialchars($c['email'] ?? '—') ?></td>
                <td><?= htmlspecialchars($c['telefono'] ?? '—') ?></td>
                <td><?= date('d/m/Y', strtotime($c['fecha_registro'])) ?></td>
                <td>
                    <button onclick="eliminarCliente(<?= $c['id'] ?>)"
                        style="background:none;border:1.5px solid #e04e1a;color:#e04e1a;border-radius:8px;padding:5px 10px;cursor:pointer;font-size:12px;font-family:var(--font);font-weight:600;transition:all .15s;">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div id="sin-resultados" style="display:none;text-align:center;padding:32px;color:var(--muted);font-family:var(--font);">
        <i class="fa-solid fa-magnifying-glass" style="font-size:24px;margin-bottom:8px;display:block;"></i>
        No se encontraron clientes
    </div>
</div>