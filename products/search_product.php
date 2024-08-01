<?php
require_once '../db/config.php';

// Verificar se o usuário está autenticado
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$name = $_GET['name'] ?? '';

if ($name) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE name LIKE ? AND user_id = ?");
    $stmt->execute([$name . '%', $userId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode($product);
}
?>
