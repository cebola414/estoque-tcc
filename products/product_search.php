<?php
require_once '../db/config.php';

$search = $_GET['search'] ?? '';

if (!empty($search)) {
    // Escapando a entrada para prevenir SQL Injection
    $search = "%{$search}%";
    $stmt = $pdo->prepare("SELECT * FROM products WHERE name LIKE ?");
    $stmt->execute([$search]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($products)) {
        foreach ($products as $product) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($product['id']) . "</td>";
            echo "<td>" . htmlspecialchars($product['name']) . "</td>";
            echo "<td>" . htmlspecialchars($product['description']) . "</td>";
            echo "<td>" . htmlspecialchars($product['quantity']) . "</td>";
            echo "<td>" . htmlspecialchars($product['supplier']) . "</td>";
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='5'>Nenhum produto encontrado.</td></tr>";
    }
} else {
    echo "<tr><td colspan='5'>Por favor, insira um termo de pesquisa.</td></tr>";
}
?>
