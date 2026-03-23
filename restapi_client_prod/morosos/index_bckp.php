<!DOCTYPE html>
<html>
<head>
    <title>Formulario de inicio de sesión</title>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f2f2f2;
        }
        
        .login-box {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        
        .login-box h2 {
            text-align: center;
        }
        
        .login-box label {
            display: block;
            margin-bottom: 5px;
        }
        
        .login-box input[type="text"],
        .login-box input[type="password"] {
            width: 88%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        
        .login-box input[type="submit"] {
            width: 100%;
            padding: 10px;
            background-color: #4CAF50;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Inicio de sesión</h2>
        <form method="POST" action="main.php">
            <label for="username">Nombre de usuario:</label>
            <input type="text" id="username" name="username" required><br><br>
            
            <label for="password">Contraseña:</label>
            <input type="password" id="password" name="password" required><br><br>
            
            <input type="submit" value="Iniciar sesión">
        </form>
    </div>
</body>
</html>