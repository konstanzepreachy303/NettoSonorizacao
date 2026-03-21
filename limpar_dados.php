<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

require_once 'includes/conexao.php';

$mensagem_sucesso = '';
$mensagem_erro = '';

define('SENHA_LIMPEZA', 'Gabriel021100@');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar_limpeza'])) {
    $confirmacao_usuario = strtoupper(trim($_POST['confirmacao'] ?? ''));
    $senha_usuario = trim($_POST['senha'] ?? '');

    if ($confirmacao_usuario !== 'CONFIRMAR') {
        $mensagem_erro = "Confirmação incorreta. Digite CONFIRMAR.";
    } elseif ($senha_usuario !== SENHA_LIMPEZA) {
        $mensagem_erro = "Senha incorreta.";
    } else {
        try {
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
            $pdo->exec("TRUNCATE TABLE servicos");
            $pdo->exec("TRUNCATE TABLE clientes");
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

            $mensagem_sucesso = "Todos os dados foram apagados com sucesso!";
        } catch (PDOException $e) {
            try {
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            } catch (Exception $ignore) {
            }

            $mensagem_erro = "Erro ao limpar os dados: " . $e->getMessage();
        }
    }
}

include 'includes/header.php';
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
                            <?php echo htmlspecialchars($mensagem_erro); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($mensagem_sucesso): ?>
                        <div class="alert alert-success" role="alert">
                            <?php echo htmlspecialchars($mensagem_sucesso); ?>
                        </div>

                        <div class="mt-3">
                            <a href="painel_admin.php" class="btn btn-primary me-2">Voltar ao Painel Admin</a>
                            <a href="dashboard.php" class="btn btn-secondary">Ir para Dashboard</a>
                        </div>
                    <?php else: ?>

                        <div class="alert alert-warning" role="alert">
                            <strong>ATENÇÃO:</strong> esta ação irá apagar todos os clientes e serviços.
                        </div>

                        <form method="POST" action="limpar_dados.php" onsubmit="return confirmarLimpeza();">
                            <div class="mb-3">
                                <label for="confirmacao" class="form-label">Digite <strong>CONFIRMAR</strong>:</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    id="confirmacao"
                                    name="confirmacao"
                                    required
                                    autocomplete="off"
                                >
                            </div>

                            <div class="mb-3">
                                <label for="senha" class="form-label">Senha de segurança:</label>
                                <input
                                    type="password"
                                    class="form-control"
                                    id="senha"
                                    name="senha"
                                    required
                                    autocomplete="off"
                                >
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="painel_admin.php" class="btn btn-secondary">Cancelar</a>
                                <button type="submit" name="confirmar_limpeza" class="btn btn-danger">
                                    Apagar Todos os Dados
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
    return confirm("Tem certeza absoluta que deseja apagar TODOS os dados?");
}
</script>

<?php include 'includes/footer.php'; ?>
