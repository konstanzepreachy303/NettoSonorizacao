<?php
// Script para limpar todos os dados de serviços e clientes do banco de dados
// ATENÇÃO: Esta ação é IRREVERSÍVEL!

// Ativar exibição de erros para depuração
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Inicia a sessão e verifica o login do administrador
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verifica se o usuário está logado como administrador
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

require_once 'includes/conexao.php';
include 'includes/header.php';

$mensagem_sucesso = '';
$mensagem_erro = '';
$confirmacao = false;

// Senha de segurança para limpeza de dados
define('SENHA_LIMPEZA', 'Gabriel021100@');

// Processa a limpeza se confirmado
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirmar_limpeza'])) {
    // Usa filter_input com FILTER_SANITIZE_FULL_SPECIAL_CHARS (compatível com PHP 8.1+)
    $confirmacao_usuario = filter_input(INPUT_POST, 'confirmacao', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $senha_usuario = filter_input(INPUT_POST, 'senha', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    
    // Verifica se o usuário digitou "CONFIRMAR" corretamente
    if ($confirmacao_usuario !== 'CONFIRMAR') {
        $mensagem_erro = "Confirmação incorreta. Por favor, digite 'CONFIRMAR' exatamente como mostrado.";
    } elseif ($senha_usuario !== SENHA_LIMPEZA) {
        $mensagem_erro = "Senha incorreta. Acesso negado.";
    } else {
        $transacao_ativa = false;
        try {
            // Inicia uma transação para garantir que tudo seja executado ou nada
            $pdo->beginTransaction();
            $transacao_ativa = true;
            
            // Desabilita temporariamente a verificação de foreign keys
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
            
            // Limpa a tabela de serviços e reseta o auto_increment
            $pdo->exec("TRUNCATE TABLE servicos");
            
            // Limpa a tabela de clientes e reseta o auto_increment
            $pdo->exec("TRUNCATE TABLE clientes");
            
            // Reabilita a verificação de foreign keys
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            
            // Confirma a transação
            $pdo->commit();
            $transacao_ativa = false;
            
            $mensagem_sucesso = "Todos os dados de serviços e clientes foram apagados com sucesso! Os IDs foram resetados para começar do 1.";
            $confirmacao = true;
            
        } catch (PDOException $e) {
            // Em caso de erro, desfaz a transação apenas se estiver ativa
            if ($transacao_ativa) {
                try {
                    $pdo->rollBack();
                } catch (PDOException $rollbackError) {
                    // Ignora erro de rollback se não houver transação ativa
                }
            }
            // Reabilita a verificação de foreign keys em caso de erro
            try {
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            } catch (PDOException $fkError) {
                // Ignora erro se não conseguir reabilitar
            }
            $mensagem_erro = "Erro ao limpar os dados: " . $e->getMessage();
        }
    }
}
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card bg-dark text-white shadow-lg">
                <div class="card-header bg-danger text-white">
                    <h3 class="card-title mb-0">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        Limpar Todos os Dados
                    </h3>
                </div>
                <div class="card-body">
                    
                    <?php if ($mensagem_erro): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="bi bi-x-circle-fill me-2"></i><?php echo htmlspecialchars($mensagem_erro); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($mensagem_sucesso): ?>
                        <div class="alert alert-success" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i><?php echo htmlspecialchars($mensagem_sucesso); ?>
                        </div>
                        <div class="mt-4">
                            <a href="painel_admin.php" class="btn btn-primary me-2">
                                <i class="bi bi-shield-lock-fill me-2"></i>Voltar ao Painel Admin
                            </a>
                            <a href="dashboard.php" class="btn btn-secondary me-2">
                                <i class="bi bi-house-fill me-2"></i>Ir para Dashboard
                            </a>
                            <a href="listar_servicos.php" class="btn btn-secondary">
                                <i class="bi bi-list-ul me-2"></i>Listar Serviços
                            </a>
                        </div>
                    <?php else: ?>
                        
                        <div class="alert alert-warning" role="alert">
                            <h5 class="alert-heading">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                ATENÇÃO: Esta ação é IRREVERSÍVEL!
                            </h5>
                            <hr>
                            <p class="mb-0">
                                Esta operação irá <strong>apagar permanentemente</strong>:
                            </p>
                            <ul class="mt-2 mb-0">
                                <li>Todos os registros de <strong>Serviços</strong></li>
                                <li>Todos os registros de <strong>Clientes</strong></li>
                                <li>Resetar todos os <strong>IDs</strong> para começar do 1</li>
                            </ul>
                            <hr>
                            <p class="mb-0">
                                <strong>Observação:</strong> Esta ação <strong>NÃO</strong> apagará os dados de usuários/administradores.
                            </p>
                        </div>
                        
                        <form method="POST" action="limpar_dados.php" onsubmit="return confirmarLimpeza();">
                            <div class="mb-4">
                                <label for="confirmacao" class="form-label">
                                    Para confirmar, digite <strong>CONFIRMAR</strong> no campo abaixo:
                                </label>
                                <input 
                                    type="text" 
                                    class="form-control form-control-lg" 
                                    id="confirmacao" 
                                    name="confirmacao" 
                                    required 
                                    autocomplete="off"
                                    placeholder="Digite CONFIRMAR aqui"
                                    style="text-transform: uppercase;"
                                >
                            </div>
                            
                            <div class="mb-4">
                                <label for="senha" class="form-label">
                                    <i class="bi bi-shield-lock-fill me-2"></i>
                                    Digite a <strong>senha de segurança</strong>:
                                </label>
                                <input 
                                    type="password" 
                                    class="form-control form-control-lg" 
                                    id="senha" 
                                    name="senha" 
                                    required 
                                    autocomplete="off"
                                    placeholder="Digite a senha de segurança"
                                >
                                <small class="form-text text-muted">
                                    Senha de segurança necessária para executar esta ação.
                                </small>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="painel_admin.php" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left-circle-fill me-2"></i>Cancelar
                                </a>
                                <button type="submit" name="confirmar_limpeza" class="btn btn-danger">
                                    <i class="bi bi-trash-fill me-2"></i>Apagar Todos os Dados
                                </button>
                            </div>
                        </form>
                        
                    <?php endif; ?>
                    
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function confirmarLimpeza() {
    var confirmacao = document.getElementById('confirmacao').value;
    var senha = document.getElementById('senha').value;
    
    if (confirmacao !== 'CONFIRMAR') {
        alert('Por favor, digite "CONFIRMAR" exatamente como mostrado para confirmar a ação.');
        return false;
    }
    
    if (senha === '') {
        alert('Por favor, digite a senha de segurança.');
        return false;
    }
    
    return confirm('ATENÇÃO: Você tem certeza absoluta que deseja apagar TODOS os dados de serviços e clientes?\n\nEsta ação é IRREVERSÍVEL e não pode ser desfeita!');
}
</script>

<?php include 'includes/footer.php'; ?>

