<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login SIEM</title>

    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <div class="container-login">

        <!-- IZQUIERDA -->

        <div class="left-panel">

            <img src="imagenes/security.jpg" alt="Security">

        </div>

        <!-- DERECHA -->

        <div class="right-panel">

            <div class="login-container">

                <h1>SIEM SECURITY</h1>

                <p>Inicio de sesión</p>

                <form action="php/login.php" method="POST">

                    <input 
                        type="email" 
                        name="correo" 
                        placeholder="Correo"
                        required
                    >

                    <input 
                        type="password" 
                        name="contrasena" 
                        placeholder="Contraseña"
                        required
                    >

                    <button type="submit">
                        Iniciar Sesión
                     </button>

                     <button type="button" class="btn-register">
                     Registrarse
                     </button>

                </form>

            </div>

        </div>

    </div>

</body>
</html>