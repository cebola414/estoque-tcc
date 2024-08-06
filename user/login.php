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
            /* Define a cor de fundo do cabeçalho */
            display: flex;
            /* Utiliza flexbox para layout */
            align-items: center;
            /* Alinha itens verticalmente no centro */
            justify-content: space-between;
            /* Distribui espaço entre itens */
            padding: 10px;
            /* Adiciona espaçamento interno */
            color: #a6a6a6;
            /* Define a cor do texto */
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
            /* Adiciona uma sombra ao cabeçalho */
            position: relative;
            /* Permite o uso de z-index */
            z-index: 10;
            /* Garante que o cabeçalho fique acima de outros elementos */
            font-family: Arial, sans-serif;
            /* Aplica a fonte Arial no cabeçalho */
        }

        /* Estilo para a imagem do logo no cabeçalho */
        header img.logo {
            max-width: 65px;
            /* Define a largura máxima do logo */
            max-height: 65px;
            /* Define a altura máxima do logo */
        }

        /* Estilo para o título principal no cabeçalho */
        header h1 {
            font-weight: 900;
            font-size: 40px;
            /* Define o tamanho da fonte do título */
            margin: 0;
            /* Remove a margem padrão */
            color: #fff;
            /* Define a cor do texto como branco */
            padding-left: 15px;
            /* Adiciona um espaço à esquerda do título */
        }

        header a {
            text-decoration: none;
            display: flex;
            align-items: center;
        }

        /* Estilo para os links de autenticação no cabeçalho */
        .auth-links {
            display: flex;
            /* Utiliza flexbox para layout */
            align-items: center;
            /* Alinha itens verticalmente no centro */
        }

        /* Estilo para os links dentro da área de autenticação */
        .auth-links a {
            background-color: #1e1e1e;
            /* Define a cor de fundo dos links */
            color: #fff;
            /* Define a cor do texto dos links */
            padding: 10px 20px;
            /* Adiciona espaçamento interno aos links */
            margin-left: 10px;
            /* Adiciona um espaço à esquerda dos links */
            text-decoration: none;
            /* Remove o sublinhado dos links */
            border-radius: 5px;
            /* Adiciona bordas arredondadas aos links */
            font-weight: bold;
            /* Define o texto como negrito */
            transition: background-color 0.3s;
            /* Adiciona uma transição suave para a cor de fundo */
        }

        /* Estilo para o estado de foco dos links de autenticação */
        .auth-links a:hover {
            background-color: #fff;
            color: #1e1e1e;
            /* Altera a cor de fundo quando o link é focalizado */
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
        <div style="display: flex; align-items: center;">
            <a href="../Index.html"> <img class="logo" src="../Imagens/Logo Supremo pequeno.png" alt="Logo Supremo Storage" />
                <h1>Supremo Storage</h1>
            </a>
        </div>
        <div class="auth-links">
            <a href="../Index.html">Página Inicial</a>
        </div>
    </header>

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