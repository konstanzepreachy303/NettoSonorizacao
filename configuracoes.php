<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

require_once 'includes/conexao.php';
include 'includes/header.php';

$mensagem_sucesso = '';
$mensagem_erro = '';

// Processa o formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_configuracoes'])) {
    $exibir_assinaturas = isset($_POST['exibir_assinaturas']) ? trim($_POST['exibir_assinaturas']) : 'Não';
    $cnpj_empresa = isset($_POST['cnpj_empresa']) ? trim($_POST['cnpj_empresa']) : '';

    try {
        if ($exibir_assinaturas === 'Sim' || $exibir_assinaturas === 'Não') {
            $stmt = $pdo->prepare("
                INSERT INTO configuracoes (chave, valor, descricao)
                VALUES ('exibir_assinaturas', :valor, 'Exibir campos de assinatura na impressão de serviços')
                ON DUPLICATE KEY UPDATE valor = :valor, updated_at = CURRENT_TIMESTAMP
            ");
            $stmt->bindParam(':valor', $exibir_assinaturas);
            $stmt->execute();
        }

        $stmt_cnpj = $pdo->prepare("
            INSERT INTO configuracoes (chave, valor, descricao)
            VALUES ('cnpj_empresa', :cnpj, 'CNPJ da empresa para exibição nas assinaturas')
            ON DUPLICATE KEY UPDATE valor = :cnpj, updated_at = CURRENT_TIMESTAMP
        ");
        $stmt_cnpj->bindParam(':cnpj', $cnpj_empresa);
        $stmt_cnpj->execute();

        $mensagem_sucesso = "✅ Configurações salvas com sucesso!";
    } catch (PDOException $e) {
        $mensagem_erro = "❌ Erro ao salvar configurações: " . $e->getMessage();
    }
}

// Busca as configurações atuais
$exibir_assinaturas_atual = 'Não';
$cnpj_empresa_atual = '';

try {
    $stmt = $pdo->prepare("SELECT valor FROM configuracoes WHERE chave = 'exibir_assinaturas'");
    $stmt->execute();
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($resultado) {
        $exibir_assinaturas_atual = $resultado['valor'];
    }

    $stmt_cnpj = $pdo->prepare("SELECT valor FROM configuracoes WHERE chave = 'cnpj_empresa'");
    $stmt_cnpj->execute();
    $resultado_cnpj = $stmt_cnpj->fetch(PDO::FETCH_ASSOC);
    if ($resultado_cnpj) {
        $cnpj_empresa_atual = $resultado_cnpj['valor'];
    }
} catch (PDOException $e) {
    if (strpos($e->getMessage(), "doesn't exist") !== false) {
        $mensagem_erro = "Tabela de configurações não encontrada. <a href='criar_tabela_configuracoes.php' class='alert-link'>Clique aqui para criá-la</a>.";
    } else {
        $mensagem_erro = "❌ Erro ao carregar configurações: " . $e->getMessage();
    }
}
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card bg-dark text-white shadow-lg">
                <div class="card-header bg-info text-white">
                    <h2 class="card-title mb-0">
                        <i class="bi bi-gear-fill me-2"></i>Configurações do Sistema
                    </h2>
                </div>

                <div class="card-body">

                    <?php if ($mensagem_sucesso): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i><?php echo htmlspecialchars($mensagem_sucesso); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($mensagem_erro): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo $mensagem_erro; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <ul class="nav nav-tabs mb-4" id="configTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="geral-tab" data-bs-toggle="tab" data-bs-target="#geral" type="button" role="tab" aria-controls="geral" aria-selected="true">
                                <i class="bi bi-gear-fill me-2"></i>Geral
                            </button>
                        </li>
                    </ul>

                    <form method="POST" action="configuracoes.php" id="formConfiguracoes">
                        <div class="tab-content" id="configTabsContent">
                            <div class="tab-pane fade show active" id="geral" role="tabpanel" aria-labelledby="geral-tab">
                                <div class="card bg-dark-secondary mb-4">
                                    <div class="card-body">
                                        <h5 class="card-title text-white mb-4">
                                            <i class="bi bi-printer-fill me-2"></i>Configurações de Impressão
                                        </h5>

                                        <div class="mb-4">
                                            <label for="exibir_assinaturas" class="form-label text-white">
                                                <strong>1. Deseja que a assinatura apareça na impressão de serviço?</strong>
                                            </label>
                                            <select class="form-select form-select-lg" id="exibir_assinaturas" name="exibir_assinaturas" required>
                                                <option value="Não" <?php echo ($exibir_assinaturas_atual === 'Não') ? 'selected' : ''; ?>>Não</option>
                                                <option value="Sim" <?php echo ($exibir_assinaturas_atual === 'Sim') ? 'selected' : ''; ?>>Sim</option>
                                            </select>
                                            <small class="form-text text-light" style="opacity: 0.9;">
                                                Quando ativado, a impressão de ordem de serviço exibirá campos para assinatura da loja prestadora de serviço e do cliente.
                                            </small>
                                        </div>

                                        <div class="mb-4">
                                            <label for="cnpj_empresa" class="form-label text-white">
                                                <strong>2. CNPJ da Empresa:</strong>
                                            </label>
                                            <input
                                                type="text"
                                                class="form-control form-control-lg"
                                                id="cnpj_empresa"
                                                name="cnpj_empresa"
                                                value="<?php echo htmlspecialchars($cnpj_empresa_atual); ?>"
                                                placeholder="00.000.000/0000-00"
                                            >
                                            <small class="form-text text-light" style="opacity: 0.9;">
                                                CNPJ da empresa que será exibido na assinatura da impressão de ordem de serviço.
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mt-4 border-top pt-4">
                            <a href="painel_admin.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left-circle-fill me-2"></i>Voltar
                            </a>
                            <button type="submit" name="salvar_configuracoes" class="btn btn-primary">
                                <i class="bi bi-save-fill me-2"></i>Salvar Configurações
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>