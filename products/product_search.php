<?php
require_once '../includes/auth_check.php';
require_once '../db/config.php';

$search = $_POST['searchQuery'] ?? '';
$userId = $_SESSION['user_id'];

if ($search) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE user_id = ? AND (name LIKE ? OR description LIKE ?)");
    $searchTerm = "%$search%";
    $stmt->execute([$userId, $searchTerm, $searchTerm]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $products = [];
}


foreach ($products as $product) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($product['id']) . "</td>";
    echo "<td>" . htmlspecialchars($product['name']) . "</td>";
    echo "<td>" . htmlspecialchars($product['description']) . "</td>";
    echo "<td>" . htmlspecialchars($product['quantity']) . "</td>";
    echo "<td>" . htmlspecialchars($product['supplier']) . "</td>";
    echo "</tr>";
}
