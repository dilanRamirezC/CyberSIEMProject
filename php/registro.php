<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro SIEM</title>

    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <div class="register-container">

        <h1>Crear Cuenta</h1>

        <form action="guardar_usuario.php" method="POST">

            <input 
                type="text"
                name="nombre"
                placeholder="Nombre"
                required
            >

            <input 
                type="email"
                name="correo"
                placeholder="Correo @siem.com"
                required
            >

            <input 
                type="password"
                name="contrasena"
                placeholder="Contraseña"
                required
            >

            <button type="submit">
                Registrarse
            </button>

        </form>

    </div>

</body>
</html>