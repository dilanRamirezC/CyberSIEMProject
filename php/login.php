<?php

session_start();

include("conexion.php");

$correo = $_POST['correo'];

$contrasena = $_POST['contrasena'];

/* CONSULTA */

$query = "SELECT * FROM Usuarios
WHERE correo='$correo'
AND contrasena='$contrasena'";

/* EJECUTAR */

$resultado = mysqli_query($conexion, $query);

/* VALIDAR */

if(mysqli_num_rows($resultado) > 0){

    $fila = mysqli_fetch_assoc($resultado);

    $_SESSION['id_usuario'] = $fila['id_usuario'];

    $_SESSION['correo'] = $fila['correo'];

    $_SESSION['nombre'] = $fila['nombre'];

    header("Location: ../panel_admin.php");

}else{

    echo "Usuario o contraseña incorrectos";

}

?>