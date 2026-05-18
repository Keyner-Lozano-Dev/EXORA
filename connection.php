<?php

function connection(){
    $host = "localhost";
    $username = "u503005013_root";
    $password = "K1109381992l.";

    $bd = "u503005013_exoraD";

    $connect = mysqli_connect($host, $username, $password);

    mysqli_select_db($connect, $bd);

    return $connect;
}

?>