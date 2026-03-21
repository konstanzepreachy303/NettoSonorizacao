<?php
session_start();
// ATUALIZADO: Inclui o arquivo de conexão da pasta includes
include 'includes/conexao.php';

// Verifica se a requisição é POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Coleta os dados do formulário
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $telefone = $_POST['telefone'];
    $cpf_cnpj = $_POST['cpf_cnpj'];
    $endereco = $_POST['endereco'];

    // Validação básica
    if (empty($nome) || empty($email) || empty($telefone) || empty($cpf_cnpj) || empty($endereco)) {
        $_SESSION['mensagem_erro'] = "Por favor, preencha todos os campos.";
        header("Location: cadastrar_cliente.php");
        exit();
    }

    try {
        // Prepara a query SQL para inserir os dados
        $stmt = $conn->prepare("INSERT INTO clientes (nome, email, telefone, cpf_cnpj, endereco) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $nome, $email, $telefone, $cpf_cnpj, $endereco);

        // Executa a query
        if ($stmt->execute()) {
            $_SESSION['mensagem_sucesso'] = "Cliente cadastrado com sucesso!";
            header("Location: cadastrar_cliente.php");
            exit();
        } else {
            $_SESSION['mensagem_erro'] = "Erro ao cadastrar cliente: " . $stmt->error;
            header("Location: cadastrar_cliente.php");
            exit();
        }

        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        // Captura exceções de SQL, como violação de chave única (ex: CPF/CNPJ ou Email duplicado, se houver UNIQUE)
        if ($e->getCode() == 1062) { // Código de erro para entrada duplicada no MySQL
            $_SESSION['mensagem_erro'] = "Erro: CPF/CNPJ ou Email já cadastrado.";
        } else {
            $_SESSION['mensagem_erro'] = "Erro no banco de dados: " . $e->getMessage();
        }
        header("Location: cadastrar_cliente.php");
        exit();
    }
} else {
    // Redireciona se o acesso não for via POST
    header("Location: cadastrar_cliente.php");
    exit();
}

$conn->close();
?>