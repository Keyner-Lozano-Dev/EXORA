<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
include('connection.php');
include('config.php');

$con = connection();

require_once 'vendor/autoload.php';

// ── Configurar cliente Google ─────────────────────────────────────────────────
$client = new Google_Client();
$client->setClientId($clientID);
$client->setClientSecret($clientSecret);
$client->setRedirectUri($redirectUri);

// ── 1. Google nos devuelve un "code" ─────────────────────────────────────────
if (!isset($_GET['code'])) {
    header("Location: index.php?error=token");
    exit();
}

// ── 2. Intercambiar code por token ───────────────────────────────────────────
$token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

if (isset($token['error'])) {
    header("Location: index.php?error=token");
    exit();
}

$client->setAccessToken($token);

// ── 3. Obtener datos del usuario ─────────────────────────────────────────────
$google_service = new Google_Service_Oauth2($client);
$googleUser     = $google_service->userinfo->get();

$googleId = $googleUser->id;
$email    = $googleUser->email;
$name     = $googleUser->name;
$foto     = $googleUser->picture;

// ── 4. ¿El email existe como cuenta LOCAL? ───────────────────────────────────
$emailEscaped = mysqli_real_escape_string($con, $email);
$googleIdEsc  = mysqli_real_escape_string($con, $googleId);
$nameEscaped  = mysqli_real_escape_string($con, $name);

$localCheck = mysqli_query($con, "SELECT id FROM users WHERE email = '$emailEscaped' AND auth_provider = 'local'");

if (mysqli_num_rows($localCheck) > 0) {
    header("Location: index.php?error=email_exists");
    exit();
}

// ── 5. ¿Ya existe como cuenta Google? ───────────────────────────────────────
$googleCheck = mysqli_query($con, "SELECT id, username FROM users WHERE google_id = '$googleIdEsc' AND auth_provider = 'google'");

if (mysqli_num_rows($googleCheck) > 0) {
    $user = mysqli_fetch_assoc($googleCheck);
    $_SESSION['user_id']  = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['provider'] = 'google';
    $_SESSION['foto']     = $foto;
    header("Location: main.php");
    exit();
}

// ── 6. Usuario nuevo con Google → registrar ──────────────────────────────────
$insertSQL = "INSERT INTO users (username, password, email, google_id, auth_provider) 
              VALUES ('$nameEscaped', NULL, '$emailEscaped', '$googleIdEsc', 'google')";

$insertQuery = mysqli_query($con, $insertSQL);

if ($insertQuery) {
    $_SESSION['user_id']  = mysqli_insert_id($con);
    $_SESSION['username'] = $name;
    $_SESSION['provider'] = 'google';
    $_SESSION['foto']     = $foto;
    header("Location: main.php");
    exit();
} else {
    header("Location: index.php?error=registro");
    exit();
}
?>