<?php
$host = 'localhost';
$dbname = 'netto_sonorizacao'; // <--- MUDE PARA O NOME DO SEU BANCO DE DADOS
$user = 'root'; 
$password = ''; 

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Em produção, você pode remover a linha abaixo ou comentá-la.
    // echo "Conexão bem-sucedida!"; 
} catch (PDOException $e) {
    // Lança exceção em vez de usar die() para permitir tratamento adequado
    throw new Exception("Erro na conexão com o banco de dados: " . $e->getMessage());
}
?>