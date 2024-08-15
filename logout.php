<?php
session_start();
session_unset(); // Limpa todas as variáveis de sessão
session_destroy(); // Destroi a sessão

// Redireciona para a página de login após o logout
header('Location: /estoque/user/login.php');
exit();