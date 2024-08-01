<?php
$host = 'localhost';
$db = 'estoque';
$user = 'root';
$pass = '';

define('BASE_URL', 'http://localhost/estoque/');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro ao conectar ao banco: " . $e->getMessage());
}
?>