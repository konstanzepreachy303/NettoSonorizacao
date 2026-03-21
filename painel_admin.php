<?php
// Ativar exibição de erros para depuração (REMOVER EM PRODUÇÃO FINAL)
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
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card bg-dark text-white shadow-lg">
                <div class="card-header bg-primary text-white">
                    <h2 class="card-title mb-0">
                        <i class="bi bi-shield-lock-fill me-2"></i>Painel Administrativo
                    </h2>
                </div>
                <div class="card-body">
                    
                    <div class="alert alert-info" role="alert">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        <strong>Painel Administrativo</strong> - Área para configurações avançadas do sistema.
                    </div>

                    <hr class="my-4 border-secondary">

                    <h4 class="mb-4 text-neon-blue">
                        <i class="bi bi-tools me-2"></i>Ferramentas Administrativas
                    </h4>

                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <div class="card bg-dark-secondary h-100">
                                <div class="card-body">
                                    <h5 class="card-title text-warning">
                                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                        Limpar Dados
                                    </h5>
                                    <p class="card-text text-secondary">
                                        Apaga todos os dados de serviços e clientes do banco de dados. 
                                        Esta ação é <strong>irreversível</strong> e resetará os IDs para começar do zero.
                                    </p>
                                    <a href="limpar_dados.php" class="btn btn-danger">
                                        <i class="bi bi-trash-fill me-2"></i>Acessar Limpeza de Dados
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Espaço para futuras ferramentas administrativas -->
                        <div class="col-md-6 mb-4">
                            <div class="card bg-dark-secondary h-100">
                                <div class="card-body">
                                    <h5 class="card-title text-info">
                                        <i class="bi bi-plus-circle-fill me-2"></i>
                                        Mais Ferramentas
                                    </h5>
                                    <p class="card-text text-secondary">
                                        Novas ferramentas administrativas serão adicionadas aqui.
                                    </p>
                                    <button class="btn btn-secondary" disabled>
                                        <i class="bi bi-lock-fill me-2"></i>Em breve
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4 border-secondary">

                    <div class="d-flex justify-content-end mt-4">
                        <a href="dashboard.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left-circle-fill me-2"></i>Voltar ao Dashboard
                        </a>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

