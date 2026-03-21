<?php
$host = getenv('MYSQLHOST');
$port = getenv('MYSQLPORT');
$dbname = getenv('MYSQLDATABASE');
$user = getenv('MYSQLUSER');
$password = getenv('MYSQLPASSWORD');

echo "HOST: $host <br>";
echo "PORT: $port <br>";
echo "DB: $dbname <br>";
echo "USER: $user <br>";

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $user, $password);
    echo "CONECTOU NO BANCO!";
} catch (PDOException $e) {
    echo "ERRO: " . $e->getMessage();
}
?>
