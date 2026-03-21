<?php
$host = 'caboose.proxy.rlwy.net';
$port = '24953';
$dbname = 'railway';
$user = 'root';
$password = 'gtveFsqKLHWtogxpzwookQtcaXGFwSDI';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    throw new Exception("Erro na conexão com o banco de dados: " . $e->getMessage());
}
?>