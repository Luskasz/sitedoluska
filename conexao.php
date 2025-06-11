<?php
// Arquivo: conexao.php

$host = 'localhost';
$dbname = 'cadastro';
$user = 'root';
$pass = ''; // Deixe em branco se você não configurou senha no XAMPP
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // A variável $pdo é criada e definida AQUI!
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Se a conexão falhar (ex: senha errada, MySQL desligado), o script morre.
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>