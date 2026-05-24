<?php

session_start();

if(!isset($_SESSION['correo'])){
    header("Location: index.php");
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard SIEM</title>

    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <div class="container">

        <div class="sidebar">

            <h2>SIEM</h2>

            <ul>
                <li>Dashboard</li>
                <li>Usuarios</li>
                <li>Logs</li>
                <li>Alertas</li>
                <li>Incidentes</li>
                <li>Cerrar Sesión</li>
            </ul>

        </div>

        <div class="main-content">

            <h1>Bienvenido</h1>

            <p>
                Usuario:
                <?php echo $_SESSION['correo']; ?>
            </p>

            <div class="cards">

                <div class="card">
                    <h3>Logs</h3>
                    <p>1200</p>
                </div>

                <div class="card">
                    <h3>Alertas</h3>
                    <p>15</p>
                </div>

                <div class="card">
                    <h3>Incidentes</h3>
                    <p>4</p>
                </div>

            </div>

        </div>

    </div>

</body>
</html>