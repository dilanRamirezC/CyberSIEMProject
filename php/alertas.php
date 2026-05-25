<?php
session_start();
include("../php/conexion.php");

/* OBTENER ALERTAS */
$sql = "SELECT * FROM alertas ORDER BY fecha_alerta DESC";

$result = mysqli_query($conexion, $sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Alertas</title>
<link rel="stylesheet" href="../css/alertas.css">
</head>
<body>

<h1>Sistema de Alertas</h1>

<table>

    <thead>
        <tr>
            <th>ID</th>
            <th>Título</th>
            <th>Descripción</th>
            <th>Nivel Riesgo</th>
            <th>Fecha</th>
            <th>Estado</th>
        </tr>
    </thead>

    <tbody>

    <?php

    if(mysqli_num_rows($result) > 0){

        while($row = mysqli_fetch_assoc($result)){

    ?>

        <tr>

            <td>
                <?php echo $row['id_alerta']; ?>
            </td>

            <td>
                <?php echo $row['titulo']; ?>
            </td>

            <td>
                <?php echo $row['descripcion']; ?>
            </td>

            <td>
                <?php echo $row['nivel_riesgo']; ?>
            </td>

            <td>
                <?php echo $row['fecha_alerta']; ?>
            </td>

            <td>
                <?php echo $row['estado']; ?>
            </td>

        </tr>

    <?php

        }

    } else {

        echo "
        <tr>
            <td colspan='6'>
                No hay alertas registradas
            </td>
        </tr>
        ";

    }

    ?>

    </tbody>

</table>

</body>
</html>