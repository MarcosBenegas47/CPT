

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login CPT</title>
    <style>
        body {
            font-family: Arial;
            background: #f2f2f2;
        }
        .login {
            width: 300px;
            margin: 120px auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 10px #ccc;
            text-align: center;
        }
        input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
        }
        button {
            width: 100%;
            padding: 10px;
        }
        .error {
            color: red;
        }
    </style>
</head>
<body>

<div class="login">
    <h2>Ingreso al sistema</h2>

    <?php if (isset($_GET['error'])): ?>
        <p class="error">Usuario o contraseña incorrectos</p>
    <?php endif; ?>

    <form action="validar_login.php" method="POST">
        <input type="text" name="usuario" placeholder="Usuario" required>
        <input type="password" name="clave" placeholder="Contraseña" required>
        <button type="submit">Ingresar</button>
    </form>
</div>

</body>
</html>