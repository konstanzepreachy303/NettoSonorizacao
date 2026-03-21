<?php
// Inicia a sessao se ainda nao estiver iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/conexao.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    $stmt = $conn->prepare("SELECT id, nome, email, senha FROM clientes WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $cliente = $result->fetch_assoc();
        if (password_verify($senha, $cliente['senha'])) {
            $_SESSION['cliente_id'] = $cliente['id'];
            $_SESSION['cliente_nome'] = $cliente['nome'];
            $_SESSION['cliente_email'] = $cliente['email'];
            
            header("Location: historico_manutencoes.php");
            exit();
        } else {
            $_SESSION['login_erro'] = "Senha incorreta.";
            header("Location: index.php");
            exit();
        }
    } else {
        $_SESSION['login_erro'] = "Email nao encontrado.";
        header("Location: index.php");
        exit();
    }
    $stmt->close();
    $conn->close();
}
?>