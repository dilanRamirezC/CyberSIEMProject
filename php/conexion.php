<?php

$servidor = "localhost";
$usuario = "root";
$password = "";
$bd = "siem_academico";

$conexion = mysqli_connect($servidor, $usuario, $password, $bd);

if(!$conexion){
    die("Conexion fallida");
}

?>