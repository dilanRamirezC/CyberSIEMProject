<?php

include("conexion.php");

$nombre = $_POST['nombre'];
$correo = $_POST['correo'];
$contrasena = $_POST['contrasena'];

/* VALIDAR CORREO */

if(!preg_match("/^[a-zA-Z0-9._%+-]+@siem\.com$/", $correo)){
    die("El correo debe terminar en @siem.com");
}

/* VALIDAR CONTRASEÑA */

if(
    !preg_match(
        "/^(?=.*[A-Z])(?=(?:.*\d){2,})(?=.*[\W]).{8}$/",
        $contrasena
    )
){
    die("Contraseña inválida");
}

/* INSERTAR USUARIO */

$query = "INSERT INTO usuarios
(nombre, correo, contrasena)
VALUES
('$nombre','$correo','$contrasena')";

$resultado = mysqli_query($conexion, $query);

if($resultado){

    echo "Usuario registrado correctamente";

}else{

    echo "Error al registrar usuario";
}

?>