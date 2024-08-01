<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    // Redireciona para a página de login se o usuário não estiver autenticado
    header('Location: /estoque/user/login.php');
    exit();
}
?>