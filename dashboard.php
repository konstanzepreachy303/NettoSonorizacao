<?php
// Arquivo: C:\xampp\htdocs\NettoSonorizacao\dashboard.php

// ATENÇÃO: Essas linhas são para DEPURAR erros e devem ser REMOVIDAS em ambiente de produção!
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// FIM DAS LINHAS DE DEPURACAO - DEIXE-AS ATIVAS POR ENQUANTO PARA VER OS ERROS!

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

require_once 'includes/conexao.php';
include 'includes/header.php';

$error_message = '';

$total_servicos = 0;
$servicos_mes_atual = 0;
$servicos_nao_pagos = 0;
$total_clientes = 0;

// --- Lógica para o Gráfico e Filtro de Datas ---
$data_inicial = isset($_GET['data_inicial']) && !empty($_GET['data_inicial']) ? $_GET['data_inicial'] : date('Y-01-01'); // Padrão: 1º de janeiro do ano atual
$data_final = isset($_GET['data_final']) && !empty($_GET['data_final']) ? $_GET['data_final'] : date('Y-12-31');      // Padrão: 31 de dezembro do ano atual

$chart_data = [];
$total_valor_periodo = 0;
$total_valor_pago_periodo = 0;
$total_valor_nao_pago_periodo = 0;

try {
    // Total de serviços
    $stmt_total_servicos = $pdo->query("SELECT COUNT(*) AS total FROM servicos");
    $total_servicos = $stmt_total_servicos->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    // Serviços do mês atual
    $mes_atual = date('Y-m');
    $stmt_mes = $pdo->prepare("SELECT COUNT(*) AS total FROM servicos WHERE DATE_FORMAT(created_at, '%Y-%m') = :mes_atual");
    $stmt_mes->bindParam(':mes_atual', $mes_atual);
    $stmt_mes->execute();
    $servicos_mes_atual = $stmt_mes->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    // Serviços não pagos (no geral)
    $stmt_nao_pagos = $pdo->query("SELECT COUNT(*) AS total FROM servicos WHERE pago = 'Não'");
    $servicos_nao_pagos = $stmt_nao_pagos->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    // Total de clientes (se você tiver uma tabela de clientes)
    $stmt_total_clientes = $pdo->query("SELECT COUNT(*) AS total FROM clientes");
    $total_clientes = $stmt_total_clientes->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    // Consulta para dados do gráfico (serviços por tipo e valor)
    $sql_chart = "SELECT tipo_servico, SUM(valor) AS total_valor_tipo
                  FROM servicos
                  WHERE data_entrada BETWEEN :data_inicial AND :data_final
                  GROUP BY tipo_servico
                  ORDER BY total_valor_tipo DESC";
    $stmt_chart = $pdo->prepare($sql_chart);
    $stmt_chart->bindParam(':data_inicial', $data_inicial);
    $stmt_chart->bindParam(':data_final', $data_final);
    $stmt_chart->execute();
    $chart_data = $stmt_chart->fetchAll(PDO::FETCH_ASSOC);

    // Consulta para o valor total no período filtrado
    $sql_total_valor = "SELECT SUM(valor) AS total_geral FROM servicos WHERE data_entrada BETWEEN :data_inicial AND :data_final";
    $stmt_total_valor = $pdo->prepare($sql_total_valor);
    $stmt_total_valor->bindParam(':data_inicial', $data_inicial);
    $stmt_total_valor->bindParam(':data_final', $data_final);
    $stmt_total_valor->execute();
    $total_valor_periodo = $stmt_total_valor->fetch(PDO::FETCH_ASSOC)['total_geral'] ?? 0;

    // NOVA CONSULTA para o valor total PAGO no período filtrado
    $sql_valor_pago = "SELECT SUM(valor) AS total_pago FROM servicos WHERE data_entrada BETWEEN :data_inicial AND :data_final AND pago = 'Sim'";
    $stmt_valor_pago = $pdo->prepare($sql_valor_pago);
    $stmt_valor_pago->bindParam(':data_inicial', $data_inicial);
    $stmt_valor_pago->bindParam(':data_final', $data_final);
    $stmt_valor_pago->execute();
    $total_valor_pago_periodo = $stmt_valor_pago->fetch(PDO::FETCH_ASSOC)['total_pago'] ?? 0;

    // NOVA CONSULTA para o valor total NÃO PAGO no período filtrado
    $sql_valor_nao_pago = "SELECT SUM(valor) AS total_nao_pago FROM servicos WHERE data_entrada BETWEEN :data_inicial AND :data_final AND pago = 'Não'";
    $stmt_valor_nao_pago = $pdo->prepare($sql_valor_nao_pago);
    $stmt_valor_nao_pago->bindParam(':data_inicial', $data_inicial);
    $stmt_valor_nao_pago->bindParam(':data_final', $data_final);
    $stmt_valor_nao_pago->execute();
    $total_valor_nao_pago_periodo = $stmt_valor_nao_pago->fetch(PDO::FETCH_ASSOC)['total_nao_pago'] ?? 0;


} catch (PDOException $e) {
    $error_message = "Erro de banco de dados no dashboard: " . $e->getMessage();
    error_log("Erro PDO em dashboard.php: " . $e->getMessage());
}
?>

<h1 class="mb-4 text-center text-white">
    <i class="bi bi-speedometer2 me-2 text-neon-blue"></i>Dashboard Administrativo
</h1>

<?php if ($error_message): ?>
    <div class="alert alert-danger" role="alert">
        <?php echo $error_message; ?>
    </div>
<?php endif; ?>

<div class="row text-center mb-4">
    <div class="col-md-3 mb-3">
        <div class="card bg-dark text-white shadow-lg">
            <div class="card-body">
                <i class="bi bi-people-fill fs-1 mb-2 text-neon-blue"></i>
                <h5 class="card-title text-neon-blue">Total de Clientes</h5>
                <p class="card-text fs-2"><?php echo $total_clientes; ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card bg-dark text-white shadow-lg">
            <div class="card-body">
                <i class="bi bi-tools fs-1 mb-2 text-neon-blue"></i>
                <h5 class="card-title text-neon-blue">Total de Serviços</h5>
                <p class="card-text fs-2"><?php echo $total_servicos; ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card bg-dark text-white shadow-lg">
            <div class="card-body">
                <i class="bi bi-calendar-event fs-1 mb-2 text-neon-blue"></i>
                <h5 class="card-title text-neon-blue">Serviços Mês Atual</h5>
                <p class="card-text fs-2"><?php echo $servicos_mes_atual; ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card bg-dark text-white shadow-lg">
            <div class="card-body">
                <i class="bi bi-currency-dollar fs-1 mb-2 text-neon-blue"></i>
                <h5 class="card-title text-neon-blue">Serviços Não Pagos</h5>
                <p class="card-text fs-2"><?php echo $servicos_nao_pagos; ?></p>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card text-center bg-dark text-white shadow-lg">
            <div class="card-body">
                <i class="bi bi-person-add fs-1 mb-2 text-neon-blue"></i>
                <h5 class="card-title text-neon-blue">Cadastrar Cliente</h5>
                <p class="card-text">Adicione novos clientes ao sistema.</p>
                <a href="cadastrar_cliente.php" class="btn btn-outline-light">Ir para Cadastro</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center bg-dark text-white shadow-lg">
            <div class="card-body">
                <i class="bi bi-tools fs-1 mb-2 text-neon-blue"></i>
                <h5 class="card-title text-neon-blue">Registrar Serviço</h5>
                <p class="card-text">Cadastre uma nova manutenção/serviço para um cliente.</p>
                <a href="cadastrar_servico.php" class="btn btn-outline-light">Registrar Agora</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center bg-dark text-white shadow-lg">
            <div class="card-body">
                <i class="bi bi-clipboard-check fs-1 mb-2 text-neon-blue"></i>
                <h5 class="card-title text-neon-blue">Listar Serviços</h5>
                <p class="card-text">Visualize e gerencie todos os serviços.</p>
                <a href="listar_servicos.php" class="btn btn-outline-light">Ver Lista</a>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4 mb-4">
    <div class="col-12">
        <div class="card bg-dark text-white shadow-lg p-4">
            <h2 class="text-white mb-3 text-center">Serviços por Tipo e Resumo Financeiro</h2>
            
            <form method="GET" class="mb-4">
                <div class="row g-3 align-items-end justify-content-center">
                    <div class="col-md-auto">
                        <label for="data_inicial" class="form-label text-white">Data Inicial:</label>
                        <input type="date" class="form-control bg-dark text-white border-secondary" id="data_inicial" name="data_inicial" value="<?php echo htmlspecialchars($data_inicial); ?>">
                    </div>
                    <div class="col-md-auto">
                        <label for="data_final" class="form-label text-white">Data Final:</label>
                        <input type="date" class="form-control bg-dark text-white border-secondary" id="data_final" name="data_final" value="<?php echo htmlspecialchars($data_final); ?>">
                    </div>
                    <div class="col-md-auto">
                        <button type="submit" class="btn btn-outline-info mt-3 mt-md-0">
                            <i class="bi bi-funnel-fill me-2"></i>Filtrar Dados
                        </button>
                    </div>
                </div>
            </form>

            <?php if (!empty($chart_data)): ?>
                <div class="row mt-4 align-items-center">
                    <div class="col-lg-7">
                        <div style="max-height: 400px;"> <canvas id="servicesChart"></canvas>
                        </div>
                    </div>
                    <div class="col-lg-5 mt-4 mt-lg-0">
                        <div class="p-3 border rounded bg-input-display text-white">
                            <h4 class="text-center mb-3 text-neon-blue">Resumo Financeiro do Período</h4>
                            <p class="fs-5 text-center">
                                <i class="bi bi-cash-coin me-2"></i>Total Geral: <strong>R$ <?php echo number_format($total_valor_periodo, 2, ',', '.'); ?></strong>
                            </p>
                            <hr class="my-3">
                            <p class="fs-5 text-center text-success">
                                <i class="bi bi-check-circle-fill me-2"></i>Total Pago: <strong>R$ <?php echo number_format($total_valor_pago_periodo, 2, ',', '.'); ?></strong>
                            </p>
                            <p class="fs-5 text-center text-danger">
                                <i class="bi bi-x-circle-fill me-2"></i>Total Não Pago: <strong>R$ <?php echo number_format($total_valor_nao_pago_periodo, 2, ',', '.'); ?></strong>
                            </p>
                            <p class="text-muted text-center mt-3">
                                (Período: <?php echo date('d/m/Y', strtotime($data_inicial)); ?> a <?php echo date('d/m/Y', strtotime($data_final)); ?>)
                            </p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-info text-center mt-3" role="alert">
                    Nenhum serviço encontrado para o período selecionado.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Dados para o gráfico, passados do PHP para o JavaScript
    const chartData = <?php echo json_encode($chart_data); ?>;
    const labels = chartData.map(item => item.tipo_servico);
    const dataValues = chartData.map(item => parseFloat(item.total_valor_tipo));

    // Cores dinâmicas para o gráfico
    const backgroundColors = [
        'rgba(255, 99, 132, 0.8)', // Vermelho
        'rgba(54, 162, 235, 0.8)', // Azul
        'rgba(255, 206, 86, 0.8)', // Amarelo
        'rgba(75, 192, 192, 0.8)', // Verde
        'rgba(153, 102, 255, 0.8)',// Roxo
        'rgba(255, 159, 64, 0.8)', // Laranja
        'rgba(201, 203, 207, 0.8)' // Cinza
    ];
    const borderColors = [
        'rgba(255, 99, 132, 1)',
        'rgba(54, 162, 235, 1)',
        'rgba(255, 206, 86, 1)',
        'rgba(75, 192, 192, 1)',
        'rgba(153, 102, 255, 1)',
        'rgba(255, 159, 64, 1)',
        'rgba(201, 203, 207, 1)'
    ];

    // Criação do gráfico de pizza se houver dados
    const ctx = document.getElementById('servicesChart');
    if (ctx && labels.length > 0) {
        new Chart(ctx, {
            type: 'pie', // Tipo de gráfico: pizza
            data: {
                labels: labels,
                datasets: [{
                    label: 'Valor Total (R$)',
                    data: dataValues,
                    backgroundColor: backgroundColors.slice(0, labels.length), // Usa cores conforme o número de tipos
                    borderColor: borderColors.slice(0, labels.length),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false, // Permite controlar a altura do contêiner
                plugins: {
                    legend: {
                        position: 'right', // Posição da legenda
                        labels: {
                            color: '#f8f9fa' // Cor do texto da legenda
                        }
                    },
                    title: {
                        display: true,
                        text: 'Distribuição de Serviços por Tipo',
                        color: '#f8f9fa' // Cor do título do gráfico
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed !== null) {
                                    label += 'R$ ' + context.parsed.toFixed(2).replace('.', ',');
                                }
                                return label;
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>
