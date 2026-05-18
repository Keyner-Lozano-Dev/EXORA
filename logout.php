<?php
session_start();

// 1. Revocar token de Google
if (isset($_SESSION['google_access_token'])) {
    $token = $_SESSION['google_access_token'];
    file_get_contents(
        'https://oauth2.googleapis.com/revoke?token=' . urlencode($token)
    );
}

// 2. Destruir sesión PHP
$_SESSION = [];
session_unset();
session_destroy();

// 3. Borrar cookie de sesión
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// 4. Redirigir al login
header("Location: index.php");
exit();
?>