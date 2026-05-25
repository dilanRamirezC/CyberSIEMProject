<?php

session_start();

include("conexion.php");

$correo = $_POST['correo'];
$contrasena = $_POST['contrasena'];

$query = "SELECT * FROM usuarios 
WHERE correo='$correo' 
AND contrasena='$contrasena'";

$resultado = mysqli_query($conexion, $query);

if(mysqli_num_rows($resultado) > 0){

    $_SESSION['correo'] = $correo;

    header("Location: ../panel_admin.php");

}else{

    echo "Usuario o contraseña incorrectos";

}

?>