<?php
session_start();
include('connection.php');
include('config.php');

$con = connection();

require_once 'vendor/autoload.php';

$client = new Google_Client();
$client->setClientId($clientID);
$client->setClientSecret($clientSecret);
$client->setRedirectUri($redirectUri);
$client->addScope("email");
$client->addScope("profile");

$googleAuthURL = $client->createAuthUrl();

$errores = [
    'credenciales'  => 'Usuario o contraseña incorrectos.',
    'email_exists'  => 'Este correo ya tiene una cuenta. Inicia sesión con usuario y contraseña.',
    'registro'      => 'Ocurrió un error al registrar tu cuenta de Google. Intenta de nuevo.',
    'token'         => 'Error al conectar con Google. Intenta de nuevo.',
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
    <title>EXORA | Iniciar Sesión</title>
    <script type="module" src="https://unpkg.com/@splinetool/viewer@1.12.92/build/spline-viewer.js"></script>
</head>
<body>

    <!-- Botón modo oscuro -->
    <button class="dark-toggle" onclick="toggleDark()" title="Modo oscuro">🌙</button>

    <!-- Fondo Spline -->
    <div class="spline-bg">
        <spline-viewer id="splineViewer" url="https://prod.spline.design/1plJ4q1LCZ7G2f4T/scene.splinecode"></spline-viewer>
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
        <form action="chek_users.php" method="POST">
            <h1>EXORA</h1>

            <?php if ($error): ?>
                <div class="error-msg"><?php echo $error; ?></div>
            <?php endif; ?>

            <input type="text" name="username" id="username" placeholder="Nombre de Usuario" required>

            <div class="password-wrapper">
                <input type="password" name="password" id="password" placeholder="Contraseña" required>
                <button type="button" class="toggle-password" onclick="togglePass('password', this)">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                </button>
            </div>

            <input type="submit" value="Iniciar Sesión">

            <div class="divider"><span>o continúa con</span></div>

            <a href="<?php echo $googleAuthURL; ?>" class="btn-google">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/>
                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                </svg>
                Continuar con Google
            </a>

            <a>¿No estás registrado?</a><a href="registro.php">Regístrate</a>
        </form>
    </div>

    <script src="JavaScript/scripts.js"></script>

</body>
</html>