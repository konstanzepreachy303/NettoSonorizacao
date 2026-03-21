<?php
// Inicia a sessão para acesso às variáveis de sessão e controle de login
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verifica se o usuário está logado como administrador
// Se não estiver, redireciona para a página de login
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

// Inclui o arquivo de conexão com o banco de dados
require_once 'includes/conexao.php';

// Inicializa a variável para armazenar mensagens
$mensagem = "";
$tipo_mensagem = ""; // 'sucesso' ou 'erro'

// Verifica se o ID do serviço foi passado via GET
if (isset($_GET['id']) && !empty($_GET['id'])) {
    // Filtra e sanitiza o ID para evitar injeção de SQL
    $servico_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

    // Verifica se o ID é um número inteiro válido
    if ($servico_id) {
        try {
            // Prepara a consulta SQL para excluir o serviço
            // ATENÇÃO: Alterado de 'manutencoes' para 'servicos'
            $stmt = $pdo->prepare("DELETE FROM servicos WHERE id = :id");
            $stmt->bindParam(':id', $servico_id, PDO::PARAM_INT);

            // Executa a consulta
            if ($stmt->execute()) {
                // Verifica se alguma linha foi afetada (serviço excluído)
                if ($stmt->rowCount() > 0) {
                    $_SESSION['servico_sucesso'] = "Serviço excluído com sucesso!";
                } else {
                    $_SESSION['servico_erro'] = "Nenhum serviço encontrado com o ID fornecido para exclusão.";
                }
            } else {
                // Erro ao executar a exclusão
                $errorInfo = $stmt->errorInfo();
                $_SESSION['servico_erro'] = "Erro ao excluir serviço: " . ($errorInfo[2] ?? "Erro desconhecido.");
                error_log("Erro no PDO (DELETE) em excluir_servicos.php: " . ($errorInfo[2] ?? "Erro desconhecido."));
            }
        } catch (PDOException $e) {
            // Captura exceções do PDO
            $_SESSION['servico_erro'] = "Erro no banco de dados ao excluir serviço: " . $e->getMessage();
            error_log("Erro PDO geral na exclusão de serviço: " . $e->getMessage());
        }
    } else {
        $_SESSION['servico_erro'] = "ID do serviço inválido.";
    }
} else {
    $_SESSION['servico_erro'] = "ID do serviço não fornecido.";
}

// Redireciona de volta para a página de listagem de serviços
header("Location: listar_servicos.php");
exit();
?>