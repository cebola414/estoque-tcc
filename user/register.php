<?php
require_once '../db/config.php';

$error = ''; // Variável para armazenar a mensagem de erro

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm-password']; // Campo de confirmação de senha

    // Verifica se as senhas coincidem
    if ($password !== $confirm_password) {
        $error = 'As senhas não coincidem. Por favor, tente novamente.';
    } else {
        // Verifica se o usuário ou e-mail já existe
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        $count = $stmt->fetchColumn();

        if ($count > 0) {
            // Se o nome de usuário ou e-mail já existe, define a mensagem de erro
            $error = 'Nome de usuário ou e-mail já cadastrado. Tente novamente.';
        } else {
            $password_hash = password_hash($password, PASSWORD_BCRYPT);

            // Insere os novos dados do usuário
            $stmt = $pdo->prepare("INSERT INTO users (username, password, email) VALUES (?, ?, ?)");
            $stmt->execute([$username, $password_hash, $email]);


            mail($to, $subject, $message, $headers);

            header('Location: login.php');
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Usuário</title>
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
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px;
            color: #a6a6a6;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
            position: relative;
            z-index: 10;
        }
        header a {
            text-decoration: none;
            display: flex;
            align-items: center;
        }

        header img.logo {
            max-width: 65px;
            max-height: 65px;
        }

        header h1 {
            font-weight: 900;
            font-size: 40px;
            margin: 0;
            color: #fff;
            padding-left: 15px;
        }

        .auth-links {
            display: flex;
            align-items: center;
        }

        .auth-links a {
            background-color: #1e1e1e;
            color: #fff;
            padding: 10px 20px;
            margin-left: 10px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            transition: background-color 0.3s;
        }

        .auth-links a:hover {
            background-color: #fff;
            color: #1e1e1e;
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
</head>
<body>

<header>
    <div style="display: flex; align-items: center;">
        <a href="../Index.html"><img class="logo" src="../Imagens/Logo Supremo pequeno.png" alt="Logo Supremo Storage" />
        <h1>Supremo Storage</h1></a>
    </div>
    <div class="auth-links">
        <a href="../Index.html">Página Inicial</a>
    </div>
</header>

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
        <?php if ($error): ?>
        <div style="color: red; margin-bottom: 20px; margin-top:20px">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>
        <button type="submit">Cadastrar</button>
    </form>
    <p>Já tem uma conta? <a href="login.php">Faça login</a></p>
</div>
</body>
</html>