<?php
// Ativar exibição de erros para depuração (REMOVER EM PRODUÇÃO)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Inclui o arquivo de conexão com o banco de dados ($pdo)
include_once 'includes/conexao.php';

// Verifica se um ID de cliente foi passado via URL (método GET)
if (isset($_GET['id']) && !empty($_GET['id'])) {
    // Filtra e sanitiza o ID do cliente para garantir que é um número inteiro
    $cliente_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

    // Validação extra: ID deve ser um número positivo
    if ($cliente_id === false || $cliente_id <= 0) {
        $mensagem = "ID de cliente inválido para exclusão.";
        header("Location: listar_clientes.php?erro=" . urlencode($mensagem));
        exit(); // Encerra o script
    }

    try {
        // Opcional: Você pode querer verificar se o cliente existe antes de tentar deletar,
        // mas o DELETE por si só já não fará nada se o ID não existir.
        // Adicionar a verificação pode dar uma mensagem mais específica ao usuário.
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM clientes WHERE id = :id");
        $stmt_check->bindParam(':id', $cliente_id, PDO::PARAM_INT);
        $stmt_check->execute();
        $exists = $stmt_check->fetchColumn();

        if ($exists) {
            // Prepara a consulta SQL para deletar o cliente do banco de dados
            $stmt_delete = $pdo->prepare("DELETE FROM clientes WHERE id = :id");
            // Vincula o parâmetro :id com o ID do cliente, protegendo contra SQL Injection
            $stmt_delete->bindParam(':id', $cliente_id, PDO::PARAM_INT);

            // Executa a consulta de exclusão
            if ($stmt_delete->execute()) {
                // Verifica se alguma linha foi realmente afetada (ou seja, se o cliente foi excluído)
                if ($stmt_delete->rowCount() > 0) {
                    $mensagem = "Cliente excluído com sucesso!";
                    // Redireciona de volta para a lista de clientes com uma mensagem de sucesso
                    header("Location: listar_clientes.php?sucesso=" . urlencode($mensagem));
                    exit();
                } else {
                    $mensagem = "Cliente não encontrado ou já excluído.";
                    // Redireciona com uma mensagem de erro se o cliente não foi encontrado para exclusão
                    header("Location: listar_clientes.php?erro=" . urlencode($mensagem));
                    exit();
                }
            } else {
                // Caso a execução da consulta falhe (mas sem lançar uma PDOException)
                $mensagem = "Erro ao executar a exclusão do cliente.";
                header("Location: listar_clientes.php?erro=" . urlencode($mensagem));
                exit();
            }
        } else {
            $mensagem = "Cliente não encontrado para exclusão.";
            header("Location: listar_clientes.php?erro=" . urlencode($mensagem));
            exit();
        }

    } catch (PDOException $e) {
        // Captura e exibe erros específicos do banco de dados (PDO)
        $mensagem = "Erro no banco de dados ao excluir cliente: " . $e->getMessage();
        // Opcional: Logar o erro completo para depuração no servidor
        error_log("Erro PDO em excluir_cliente.php: " . $e->getMessage());
        header("Location: listar_clientes.php?erro=" . urlencode($mensagem));
        exit();
    }
} else {
    // Se o ID do cliente não foi fornecido na URL (acesso direto ou link malformado)
    $mensagem = "ID do cliente não fornecido para exclusão.";
    header("Location: listar_clientes.php?erro=" . urlencode($mensagem));
    exit();
}
?>