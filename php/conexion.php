<?php

$servidor = "localhost";
$usuario = "root";
$password = "1234";
$bd = "SIEM";

$conexion = mysqli_connect($servidor, $usuario, $password, $bd);

if(!$conexion){
    die("Conexion fallida");
}

?>