<?php
include('connection.php');
 
$con = connection();
 
$username = $_POST['username'];
$password = $_POST['password'];
$email    = $_POST['email'];
 
$usernameEsc = mysqli_real_escape_string($con, $username);
$passwordEsc = mysqli_real_escape_string($con, $password);
$emailEsc    = mysqli_real_escape_string($con, $email);
 
// ── 1. Verificar si el email ya existe (local o google) ──────────────────────
$emailCheck = mysqli_query($con, "SELECT id FROM users WHERE email = '$emailEsc'");
if (mysqli_num_rows($emailCheck) > 0) {
    header("Location: registro.php?error=email_exists");
    exit();
}
 
// ── 2. Verificar si el username ya existe ────────────────────────────────────
$userCheck = mysqli_query($con, "SELECT id FROM users WHERE username = '$usernameEsc'");
if (mysqli_num_rows($userCheck) > 0) {
    header("Location: registro.php?error=username_exists");
    exit();
}
 
// ── 3. Insertar usuario local ────────────────────────────────────────────────
$sql   = "INSERT INTO users (username, password, email, google_id, auth_provider) 
          VALUES ('$usernameEsc', '$passwordEsc', '$emailEsc', NULL, 'local')";
$query = mysqli_query($con, $sql);
 
if ($query) {
    header("Location: index.php");
    exit();
} else {
    header("Location: registro.php?error=error");
    exit();
}
?>