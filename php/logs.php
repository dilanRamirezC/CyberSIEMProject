<?php

session_start();
include("../php/conexion.php");

/* =========================
   CREAR LOG
========================= */

if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['crear_log'])){

    $id_equipo = mysqli_real_escape_string($conexion, $_POST['id_equipo']);
    $tipo_log = mysqli_real_escape_string($conexion, $_POST['tipo_log']);
    $severidad = mysqli_real_escape_string($conexion, $_POST['severidad']);
    $mensaje = mysqli_real_escape_string($conexion, $_POST['mensaje']);

    $query_insert = "

    INSERT INTO logs
    (
        id_equipo,
        tipo_log,
        severidad,
        mensaje
    )

    VALUES
    (
        '$id_equipo',
        '$tipo_log',
        '$severidad',
        '$mensaje'
    )

    ";

    mysqli_query($conexion, $query_insert);
}

/* =========================
   BUSCAR LOGS
========================= */

$query = "SELECT * FROM logs";

/* Buscar por equipo */

if(isset($_GET['buscar'])){

    $id_equipo_buscar = mysqli_real_escape_string($conexion, $_GET['id_equipo']);

    $query = "

    SELECT * FROM logs

    WHERE id_equipo = '$id_equipo_buscar'

    ORDER BY fecha_evento DESC

    ";

}

/* Ver todos */

if(isset($_GET['todos'])){

    $query = "

    SELECT * FROM logs

    ORDER BY fecha_evento DESC

    ";

}

$resultado = mysqli_query($conexion, $query);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs SIEM</title>

    <link rel="stylesheet" href="../css/logs.css">

</head>
<body>

<div class="contenedor-reportes">

    <h1>Gestión de Logs</h1>

    <!-- =========================
         FORMULARIO CREAR LOG
    ========================== -->

    <form action="" method="POST">

        <input
            type="number"
            name="id_equipo"
            placeholder="ID Equipo"
            required
        >

        <input
            type="text"
            name="tipo_log"
            placeholder="Tipo de Log"
            required
        >

        <select name="severidad" required>

            <option value="">Seleccione Severidad</option>
            <option value="INFO">INFO</option>
            <option value="WARNING">WARNING</option>
            <option value="ERROR">ERROR</option>
            <option value="CRITICAL">CRITICAL</option>

        </select>

        <input
            type="text"
            name="mensaje"
            placeholder="Mensaje del evento"
            required
        >

        <button type="submit" name="crear_log">
            Crear Log
        </button>

    </form>

    <!-- =========================
         BUSCAR LOGS
    ========================== -->

    <form action="" method="GET">

        <input
            type="number"
            name="id_equipo"
            placeholder="Buscar por ID Equipo"
        >

        <button type="submit" name="buscar">
            Buscar Logs
        </button>

        <button type="submit" name="todos">
            Ver Todos
        </button>

    </form>

    <!-- =========================
         TABLA LOGS
    ========================== -->

    <table>

        <thead>

            <tr>

                <th>ID Log</th>
                <th>ID Equipo</th>
                <th>Tipo Log</th>
                <th>Severidad</th>
                <th>Mensaje</th>
                <th>Fecha Evento</th>

            </tr>

        </thead>

        <tbody>

        <?php

        if(mysqli_num_rows($resultado) > 0){

            while($fila = mysqli_fetch_assoc($resultado)){

        ?>

            <tr>

                <td>
                    <?php echo $fila['id_log']; ?>
                </td>

                <td>
                    <?php echo $fila['id_equipo']; ?>
                </td>

                <td>
                    <?php echo $fila['tipo_log']; ?>
                </td>

                <td>
                    <?php echo $fila['severidad']; ?>
                </td>

                <td>
                    <?php echo $fila['mensaje']; ?>
                </td>

                <td>
                    <?php echo $fila['fecha_evento']; ?>
                </td>

            </tr>

        <?php

            }

        } else {

            echo "

            <tr>

                <td colspan='6'>
                    No hay logs registrados
                </td>

            </tr>

            ";

        }

        ?>

        </tbody>

    </table>

</div>

</body>
</html>