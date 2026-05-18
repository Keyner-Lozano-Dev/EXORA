<?php
include('connection.php');

$con = connection();

$errores = [
    'email_exists'    => 'Este correo ya tiene una cuenta registrada.',
    'username_exists' => 'Este nombre de usuario ya está en uso.',
    'error'           => 'Ocurrió un error al registrar. Intenta de nuevo.',
];

$error = isset($_GET['error']) && isset($errores[$_GET['error']])
         ? $errores[$_GET['error']]
         : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="CSS/styles.css">
    <link rel="stylesheet" href="CSS/login.css">
    <link rel="icon" type="image/png" href="favicon.png">
    <title>EXORA | Registrarse</title>
    <script type="module" src="https://unpkg.com/@splinetool/viewer@1.12.92/build/spline-viewer.js"></script>
</head>
<body>

    <!-- Botón modo oscuro -->
    <button class="dark-toggle" onclick="toggleDark()" title="Modo oscuro">🌙</button>

    <!-- Fondo Spline -->
    <div class="spline-bg">
        <spline-viewer url="https://prod.spline.design/1plJ4q1LCZ7G2f4T/scene.splinecode"></spline-viewer>
    </div>

    <div class="dark-overlay"></div>

    <!-- Formas decorativas -->
    <div class="deco deco-1"></div>
    <div class="deco deco-2"></div>
    <div class="deco deco-3"></div>
    <div class="deco deco-4"></div>
    <div class="deco deco-5"></div>
    <div class="deco deco-6"></div>
    <div class="deco deco-7"></div>

    <!-- Formulario -->
    <div class="form-side">
        <form action="insert_user.php" method="POST">
            <h1>EXORA</h1>

            <?php if ($error): ?>
                <div class="error-msg"><?php echo $error; ?></div>
            <?php endif; ?>

            <input type="text" name="username" placeholder="Nombre de Usuario" required>

            <div class="password-wrapper">
                <input type="password" name="password" id="password" placeholder="Contraseña" required>
                <button type="button" class="toggle-password" onclick="togglePass('password', this)">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                </button>
            </div>

            <input type="text" name="email" placeholder="Correo Electrónico" required>

            <input type="submit" value="Registrarse">

            <a>¿Ya tienes cuenta?</a><a href="index.php">Inicia Sesión</a>
        </form>
    </div>

    <script src="JavaScript/scripts.js"></script>

</body>
</html>