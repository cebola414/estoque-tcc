<?php
require_once '../db/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
    $stmt->execute([$username, $password]);

    header('Location: login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Usuário</title>
</head>
<body>

<style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            flex-direction: column;
            display: flex;
            min-height: 100vh;
            background-color: #f3f4f6;
        }


        header {
            background-color: #1e1e1e;
            color: #fff;
padding: 10px;
            text-align: center;
        }

        header h1 {
            color: #fff;
            font-family: sans-serif;
            font-size: 24px;
        }



        .nav {
            background-color: #282828;
            display: flex;
            /* Usa flexbox para alinhar os links */
            justify-content: center;
            /* Centraliza os links */
            flex-wrap: wrap;
            background-color: #282828;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
        }

        .nav a {
            color: #fff;
            text-align: center;
            padding: 10px;
            text-decoration: none;
            white-space: nowrap;

        }

        .nav a:hover {
            color: #ddd;
            color: black;
        }

        .register-container {
            width: 400px;
            padding: 20px;
            margin: 80px auto;
            background-color: #ffffff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            text-align: center;
        }

        h1 {
            font-size: 24px;
            color: #333;
        }

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

        button {
            width: 100%;
            padding: 10px;
            background-color: #282828;
            color: #fff;
            border: none;
            border-radius: 4px;
            font-size: 18px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        button:hover {
            background-color: #ddd;
            color: black;
        }

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
            color: #a6a6a6;
        }
    </style>

<header>
        <h1>CRIE UMA CONTA</h1>
    </header>   
    <nav class="nav">
        <a href="../Index.html">Início</a>
    </nav>

    <div class="register-container">
        <h1>Cadastro</h1>
        <form action="#" method="post">
            <div class="input-group">
                <label for="username">Usuário</label>
                <input type="text" id="username" name="username" required />
            </div>
            <div class="input-group">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" required />
            </div>
            <div class="input-group">
                <label for="password">Senha</label>
                <input type="password" id="password" name="password" required />
            </div>
            <div class="input-group">
                <label for="confirm-password">Confirmar Senha</label>
                <input type="password" id="confirm-password" name="confirm-password" required />
            </div>
            <button type="submit">Cadastrar</button>
        </form>
        <p>Já tem uma conta? <a href="login.php">Faça login</a></p>
    </div>
</body>
</html>
