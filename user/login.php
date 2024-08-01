<?php
require_once '../db/config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        header('Location: ' . BASE_URL . 'products/product_register.php');
        exit();
    } else {
        $error = "Nome de usuário ou senha incorretos";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>

    <style>
        /* Reset básico e fontes */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        /* Estilo do corpo */
        body {
            display: flex;
            flex-direction: column;
            height: 100vh;
            background-color: #f3f4f6;
        }

        /* Estilo do cabeçalho */
        header {
            background-color: #1e1e1e;
            color: #fff;
            padding: 10px;
            text-align: center;
            position: relative;
        }

        header h1 {
            font-size: 24px;
        }

        nav {
            background-color: #282828;
            overflow: hidden;
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
        }

        nav a {
            color: #fff;
            text-align: center;
            padding: 10px;
            text-decoration: none;
            white-space: nowrap;
        }

        nav a:hover {
            background-color: #ddd;
            color: black;
        }

        /* Estilo do container de login */
        .login-container {
            margin: auto;
            width: 400px;
            margin-top: 150px;
            padding: 20px;
            background-color: #ffffff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            text-align: center;
        }

        .login-container h1 {
            margin-bottom: 20px;
            font-size: 24px;
            color: #333;
        }

        /* Estilo dos grupos de input */
        .input-group {
            margin-bottom: 15px;
            text-align: left;
        }

        .input-group label {
            display: block;
            margin-bottom: 5px;
            color: #555;
        }

        .input-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 16px;
        }

        /* Estilo do botão */
        button {
            width: 100%;
            padding: 10px;
            background-color: #282828;
            color: #fff;
            border: none;
            border-radius: 4px;
            font-size: 18px;
            cursor: pointer;
            transition: background-color 0.3s, color 0.3s;
        }

        button:hover {
            background-color: #ddd;
            color: black;
        }

        /* Estilo do parágrafo e links */
        p {
            margin-top: 20px;
            color: #555;
        }

        p a {
            color: #282828;
            text-decoration: none;
            transition: color 0.3s;
        }

        p a:hover {
            color: #333;
        }
    </style>

</head>

<body>



    <header>
        <h1>FAÇA O LOGIN</h1>
    </header>
    <nav>
        <a href="../Index.html">Início</a>
    </nav>
    <div class="login-container">
        <h1>Login</h1>
        <form action="#" method="post">
            <div class="input-group">
                <label for="username">Usuário</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="input-group">
                <label for="password">Senha</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">Entrar</button>
        </form>
        <p>Não tem uma conta? <a href="register.php">Registre-se</a></p>
    </div>
</body>

</html>