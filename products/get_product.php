<?php
require_once '../includes/auth_check.php';
require_once '../db/config.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $userId = $_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $userId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($product) {
        echo json_encode($product);
    } else {
        http_response_code(404);
        echo json_encode(["message" => "Produto não encontrado."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["message" => "ID não fornecido."]);
}
