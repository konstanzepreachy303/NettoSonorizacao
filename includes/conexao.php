<?php
$host = getenv('MYSQLHOST') ?: 'caboose.proxy.rlwy.net';
$port = getenv('MYSQLPORT') ?: '24953';
$dbname = getenv('MYSQLDATABASE') ?: 'railway';
$user = getenv('MYSQLUSER') ?: 'root';
$password = getenv('MYSQLPASSWORD') ?: 'gtveFsqKLHWtogxpzwookQtcaXGFwSDI';

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $user,
        $password
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    throw new Exception("Erro na conexão com o banco de dados: " . $e->getMessage());
}
?>
