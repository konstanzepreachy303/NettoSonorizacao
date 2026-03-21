<?php
$host = getenv('MYSQLHOST');
$port = getenv('MYSQLPORT');
$dbname = getenv('MYSQLDATABASE');
$user = getenv('MYSQLUSER');
$password = getenv('MYSQLPASSWORD');

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $user,
        $password
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro banco: " . $e->getMessage());
}
?>
