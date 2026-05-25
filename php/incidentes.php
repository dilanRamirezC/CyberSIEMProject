<?php

session_start();
include("../php/conexion.php");

/* CREAR INCIDENTE */
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $id_alerta   = isset($_POST['id_alerta']) && $_POST['id_alerta'] !== ''
                   ? (int)$_POST['id_alerta']
                   : null;

    $titulo      = mysqli_real_escape_string($conexion, $_POST['titulo']);
    $descripcion = mysqli_real_escape_string($conexion, $_POST['descripcion']);
    $prioridad   = mysqli_real_escape_string($conexion, $_POST['prioridad']);
    $id_usuario  = $_SESSION['id_usuario'];

    // ✅ NULL sin comillas cuando id_alerta es null
    $id_alerta_sql = ($id_alerta !== null) ? $id_alerta : "NULL";

    $query_insert = "
        INSERT INTO incidentes (id_alerta, id_usuario, titulo, descripcion, prioridad)
        VALUES ($id_alerta_sql, '$id_usuario', '$titulo', '$descripcion', '$prioridad')
    ";

    mysqli_query($conexion, $query_insert);
}

/* MOSTRAR INCIDENTES */
$query = "
    SELECT
        incidentes.id_incidente,
        incidentes.id_alerta,
        incidentes.titulo,
        incidentes.descripcion,
        incidentes.prioridad,
        incidentes.estado,
        incidentes.fecha_inicio,
        usuarios.nombre,
        usuarios.apellido
    FROM incidentes
    INNER JOIN usuarios ON incidentes.id_usuario = usuarios.id_usuario
    ORDER BY incidentes.fecha_inicio DESC
";

$resultado = mysqli_query($conexion, $query);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Incidentes</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<div class="contenedor-reportes">

    <h1>Gestión de Incidentes</h1>

    <!-- FORMULARIO -->
    <form action="" method="POST">

        <input type="text" name="titulo" placeholder="Título del incidente" required>

        <input type="text" name="descripcion" placeholder="Descripción" required>
        <input
    type="number"
    name="id_alerta"
    placeholder="ID de la alerta relacionada"
    required
>

        <select name="prioridad" required>
            <option value="">Seleccione prioridad</option>
            <option value="BAJA">BAJA</option>
            <option value="MEDIA">MEDIA</option>
            <option value="ALTA">ALTA</option>
            <option value="CRITICA">CRITICA</option>
        </select>

        <button type="submit">Crear Incidente</button>

    </form>

    <!-- TABLA -->
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Alerta</th>
                <th>Título</th>
                <th>Descripción</th>
                <th>Prioridad</th>
                <th>Estado</th>
                <th>Usuario</th>
                <th>Fecha</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($fila = mysqli_fetch_assoc($resultado)): ?>
            <tr>
                <td><?php echo $fila['id_incidente']; ?></td>

                <!-- ✅ TD corregido, columna correcta -->
                <td><?php echo $fila['id_alerta'] ?? '—'; ?></td>

                <td><?php echo $fila['titulo']; ?></td>
                <td><?php echo $fila['descripcion']; ?></td>
                <td><?php echo $fila['prioridad']; ?></td>
                <td><?php echo $fila['estado']; ?></td>
                <td><?php echo $fila['nombre'] . " " . $fila['apellido']; ?></td>
                <td><?php echo $fila['fecha_inicio']; ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

</div>

</body>
</html>