<?php
// Ativar exibição de erros para depuração (REMOVER EM PRODUÇÃO FINAL)
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

$servico = null;
$mensagem_erro = '';

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $servico_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

    try {
        $sql = "SELECT s.*, c.nome as nome_cliente, c.email as email_cliente, c.telefone as telefone_cliente, c.cpf_cnpj as cpf_cnpj_cliente
                FROM servicos s
                JOIN clientes c ON s.cliente_id = c.id
                WHERE s.id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $servico_id, PDO::PARAM_INT);
        $stmt->execute();
        $servico = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$servico) {
            $mensagem_erro = "Serviço não encontrado.";
            $_SESSION['servico_erro'] = $mensagem_erro;
            header("Location: listar_servicos.php");
            exit();
        }
    } catch (PDOException $e) {
        $mensagem_erro = "Erro ao buscar serviço: " . $e->getMessage();
        $_SESSION['servico_erro'] = $mensagem_erro;
        error_log("Erro PDO em visualizar_servico.php: " . $e->getMessage());
        header("Location: listar_servicos.php");
        exit();
    }
} else {
    $_SESSION['servico_erro'] = "ID do serviço não fornecido ou inválido.";
    header("Location: listar_servicos.php");
    exit();
}
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card bg-dark text-white shadow-lg p-4">
                <h1 class="card-title text-center text-neon-blue mb-4">
                    <i class="bi bi-eye-fill me-2"></i>Detalhes do Serviço #<?php echo htmlspecialchars($servico['id']); ?>
                </h1>

                <?php if ($servico): ?>
                    <hr class="my-4 border-secondary">
                    <h5 class="mb-3 text-neon-blue"><i class="bi bi-person-fill me-2"></i>Informações do Cliente</h5>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <p><strong>Nome:</strong> <?php echo htmlspecialchars($servico['nome_cliente']); ?></p>
                            <p><strong>E-mail:</strong> <?php echo htmlspecialchars($servico['email_cliente'] ?: 'N/A'); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Telefone:</strong> <?php echo htmlspecialchars($servico['telefone_cliente'] ?: 'N/A'); ?></p>
                            <p><strong>CPF/CNPJ:</strong> <?php echo htmlspecialchars($servico['cpf_cnpj_cliente'] ?: 'N/A'); ?></p>
                        </div>
                    </div>
                    
                    <hr class="my-4 border-secondary">
                    <h5 class="mb-3 text-neon-blue"><i class="bi bi-car-fill me-2"></i>Informações do Equipamento/Veículo</h5>
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <p><strong>Placa:</strong> <?php echo htmlspecialchars($servico['placa'] ?: 'N/A'); ?></p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Modelo:</strong> <?php echo htmlspecialchars($servico['modelo'] ?: 'N/A'); ?></p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Marca:</strong> <?php echo htmlspecialchars($servico['marca'] ?: 'N/A'); ?></p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Ano Fabricação:</strong> <?php echo htmlspecialchars($servico['ano_fab'] ?: 'N/A'); ?></p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Ano Modelo:</strong> <?php echo htmlspecialchars($servico['ano_mod'] ?: 'N/A'); ?></p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Cor:</strong> <?php echo htmlspecialchars($servico['cor'] ?: 'N/A'); ?></p>
                        </div>
                    </div>
                    
                    <hr class="my-4 border-secondary">
                    <h5 class="mb-3 text-neon-blue"><i class="bi bi-tools me-2"></i>Detalhes do Serviço</h5>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <p><strong>Data de Entrada:</strong> <?php echo date('d/m/Y', strtotime($servico['data_entrada'])); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Data de Saída:</strong> <?php echo date('d/m/Y', strtotime($servico['data_saida'])); ?></p>
                        </div>
                        <div class="col-md-12">
                            <p><strong>Tipo de Serviço:</strong> <?php echo htmlspecialchars($servico['tipo_servico']); ?></p>
                        </div>
                    </div>
                    <div class="mb-4">
                        <h6>Problema Relatado:</h6>
                        <p class="text-secondary"><?php echo nl2br(htmlspecialchars($servico['descricao_problema'])); ?></p>
                        <h6>Serviço Executado:</h6>
                        <p class="text-secondary"><?php echo nl2br(htmlspecialchars($servico['servico_executado'])); ?></p>
                    </div>

                    <hr class="my-4 border-secondary">
                    <h5 class="mb-3 text-neon-blue"><i class="bi bi-currency-dollar me-2"></i>Informações de Pagamento e Garantia</h5>
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <p><strong>Valor:</strong> R$ <?php echo number_format($servico['valor'], 2, ',', '.'); ?></p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Garantia:</strong> <?php echo htmlspecialchars($servico['garantia'] ?: '0'); ?> dias</p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Fim da Garantia:</strong> <?php echo ($servico['data_fim_garantia'] ? date('d/m/Y', strtotime($servico['data_fim_garantia'])) : 'N/A'); ?></p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Status do Pagamento:</strong>
                                <?php if ($servico['pago'] == 'Sim'): ?>
                                    <span class="badge bg-success">Pago</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Não Pago</span>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Parcelado:</strong> <?php echo htmlspecialchars($servico['parcelado']); ?></p>
                        </div>
                        <?php if ($servico['parcelado'] == 'Sim'): ?>
                            <div class="col-md-4">
                                <p><strong>Número de Parcelas:</strong> <?php echo htmlspecialchars($servico['num_parcelas'] ?: 'N/A'); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <h5 class="mb-3 text-neon-blue"><i class="bi bi-info-circle-fill me-2"></i>Informações do Registro</h5>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <p><strong>Registrado em:</strong> <?php echo date('d/m/Y H:i:s', strtotime($servico['created_at'])); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Última Atualização:</strong> <?php echo date('d/m/Y H:i:s', strtotime($servico['updated_at'])); ?></p>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-4">
                        <a href="imprimir_servico.php?id=<?php echo htmlspecialchars($servico['id']); ?>" target="_blank" class="btn btn-primary me-2">
                            <i class="bi bi-printer-fill me-2"></i> Imprimir
                        </a>
                        <a href="editar_servico.php?id=<?php echo htmlspecialchars($servico['id']); ?>" class="btn btn-info text-white me-2">
                            <i class="bi bi-pencil-square me-2"></i> Editar Serviço
                        </a>
                        <a href="listar_servicos.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left-circle-fill me-2"></i> Voltar à Lista
                        </a>
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger text-center" role="alert">
                        Serviço não encontrado.
                    </div>
                    <div class="d-flex justify-content-center mt-3">
                        <a href="listar_servicos.php" class="btn btn-secondary"><i class="bi bi-arrow-left-circle-fill me-2"></i> Voltar à Lista</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>