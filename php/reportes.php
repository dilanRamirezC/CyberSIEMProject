<?php

session_start();

include("../php/conexion.php");

/* CONSULTA */

$query = "

SELECT 
    Reportes.id_reporte,
    Reportes.titulo,
    Reportes.descripcion,
    Reportes.fecha_generacion,

    Usuarios.nombre,
    Usuarios.apellido,
    Usuarios.correo

FROM Reportes

INNER JOIN Usuarios
ON Reportes.id_usuario = Usuarios.id_usuario

ORDER BY Reportes.fecha_generacion DESC

";

$resultado = mysqli_query($conexion, $query);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes SIEM</title>

    <link rel="stylesheet" href="../css/style.css">

</head>
<body>

    <div class="contenedor-reportes">

        <h1>Gestión de Reportes</h1>

        <table>

            <thead>

                <tr>
                    <th>ID</th>
                    <th>Título</th>
                    <th>Descripción</th>
                    <th>Usuario</th>
                    <th>Correo</th>
                    <th>Fecha</th>
                </tr>

            </thead>

            <tbody>

                <?php
                while($fila = mysqli_fetch_assoc($resultado)){
                ?>

                <tr>

                    <td>
                        <?php echo $fila['id_reporte']; ?>
                    </td>

                    <td>
                        <?php echo $fila['titulo']; ?>
                    </td>

                    <td>
                        <?php echo $fila['descripcion']; ?>
                    </td>

                    <td>
                        <?php
                        echo $fila['nombre'] . " " . $fila['apellido'];
                        ?>
                    </td>

                    <td>
                        <?php echo $fila['correo']; ?>
                    </td>

                    <td>
                        <?php echo $fila['fecha_generacion']; ?>
                    </td>

                </tr>

                <?php
                }
                ?>

            </tbody>

        </table>

    </div>
    
<form action="" method="POST">

    <input 
        type="text"
        name="titulo"
        placeholder="Título del reporte"
        required
    >

    <input 
        type="text"
        name="descripcion"
        placeholder="Descripción"
        required
    >

    <button type="submit">
        Agregar Reporte
    </button>
    
    
<?php


include("../php/conexion.php");

/* INSERTAR REPORTE */

if($_SERVER["REQUEST_METHOD"] == "POST"){

    $titulo = $_POST['titulo'];

    $descripcion = $_POST['descripcion'];

    $id_usuario = $_SESSION['id_usuario'];
    $correo = $_SESSION['correo'];

    $query_insert = "

    INSERT INTO Reportes
    (
        id_usuario,
        titulo,
        descripcion
        
    )

    VALUES
    (
        '$id_usuario',
        '$titulo',
        '$descripcion',
        
    )

    ";

    mysqli_query($conexion, $query_insert);
}



$resultado = mysqli_query($conexion, $query);

?>

</form>
 
</body>
</html>