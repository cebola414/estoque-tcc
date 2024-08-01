<?php
require_once '../db/config.php';

$stmt = $pdo->query("DELETE FROM products");
?>