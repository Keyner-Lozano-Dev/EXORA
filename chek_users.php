<?php
session_start();
include('connection.php');

$con = connection();

$username = $_POST['username'];
$password = $_POST['password'];

$sql   = "SELECT * FROM users 
          WHERE username = '$username' 
          AND password = '$password' 
          AND auth_provider = 'local'";

$query = mysqli_query($con, $sql);

if (mysqli_num_rows($query) > 0) {
    $user = mysqli_fetch_assoc($query);
    $_SESSION['user_id']  = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['provider'] = 'local';
    header("Location: main.php");
    exit();
} else {
    header("Location: index.php?error=credenciales");
    exit();
}
?>