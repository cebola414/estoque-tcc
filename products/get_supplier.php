<?php
require_once '../db/config.php';

if (isset($_GET['id'])) {
    $supplierId = $_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM suppliers WHERE id = ?");
    $stmt->execute([$supplierId]);
    $supplier = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($supplier) {
        echo json_encode($supplier);
    } else {
        echo json_encode([]);
    }
}
?>