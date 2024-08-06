<?php
require_once '../db/config.php';

// Verificar se o usuário está autenticado
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$productName = $_GET['name'] ?? null;

if ($productName) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE name LIKE ? AND user_id = ?");
        $stmt->execute(["%$productName%", $userId]);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode($products);
    } catch (PDOException $e) {
        // Log the error message
        error_log($e->getMessage());
        echo json_encode(['error' => 'Ocorreu um erro ao buscar os produtos.']);
    }
} else {
    echo json_encode(['error' => 'Nome do produto não fornecido.']);
}
?>