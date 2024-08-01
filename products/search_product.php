<?php
require_once '../db/config.php';

if (isset($_GET['name'])) {
    $name = $_GET['name'];
    $stmt = $pdo->prepare("SELECT * FROM products WHERE name = ?");
    $stmt->execute([$name]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode($product);
}
?>