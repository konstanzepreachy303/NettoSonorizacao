<?php
// Configurar fuso horário para Brasil (horário de Brasília)
date_default_timezone_set('America/Sao_Paulo');

// Ativar exibição de erros para depuração (REMOVER EM PRODUÇÃO FINAL)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Inicia a sessão e verifica o login do administrador
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['admin_id'])) {
    // Redireciona para o login se não estiver logado
    header("Location: index.php");
    exit();
}

require_once 'includes/conexao.php';

$servico = null;
$mensagem_erro = '';
$exibir_assinaturas = 'Não';
$cnpj_empresa = '';

// Busca a configuração de assinaturas e CNPJ
try {
    $stmt_config = $pdo->prepare("SELECT valor FROM configuracoes WHERE chave = 'exibir_assinaturas'");
    $stmt_config->execute();
    $config_result = $stmt_config->fetch(PDO::FETCH_ASSOC);
    if ($config_result) {
        $exibir_assinaturas = $config_result['valor'];
    }
    
    $stmt_cnpj = $pdo->prepare("SELECT valor FROM configuracoes WHERE chave = 'cnpj_empresa'");
    $stmt_cnpj->execute();
    $cnpj_result = $stmt_cnpj->fetch(PDO::FETCH_ASSOC);
    if ($cnpj_result) {
        $cnpj_empresa = $cnpj_result['valor'];
    }
} catch (PDOException $e) {
    // Se não existir a tabela ou a configuração, usa o padrão 'Não'
    $exibir_assinaturas = 'Não';
}

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
        }
    } catch (PDOException $e) {
        $mensagem_erro = "Erro ao buscar serviço: " . $e->getMessage();
    }
} else {
    $mensagem_erro = "ID do serviço não fornecido ou inválido.";
}

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title></title>
    <style>
        @page {
            margin: 0;
            size: A4;
        }

        @media print {
            @page {
                margin: 0;
                size: A4;
            }

            /* Tenta remover cabeçalhos e rodapés do navegador */
            body {
                margin: 0 !important;
                padding: 15mm !important;
                height: 100vh;
            }

            html, body {
                margin: 0;
                padding: 0;
                height: 100%;
                overflow: visible;
            }

            /* Oculta elementos que possam aparecer */
            .no-print {
                display: none !important;
            }
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Arial', 'Helvetica', sans-serif;
            margin: 0;
            padding: 15mm;
            color: #000;
            background-color: #fff;
            font-size: 9pt;
            line-height: 1.3;
        }

        .header {
            border-bottom: 2px solid #000;
            padding-bottom: 5px;
            margin-bottom: 8px;
            text-align: center;
        }

        .empresa-nome {
            font-size: 18pt;
            font-weight: bold;
            margin: 0;
            padding: 0;
            letter-spacing: 1px;
        }

        .ordem-numero {
            font-size: 12pt;
            font-weight: bold;
            margin-top: 5px;
            padding: 3px 0;
            background-color: #f0f0f0;
            border: 2px solid #000;
            border-radius: 3px;
        }

        .content-wrapper {
            width: 100%;
        }

        .section {
            margin-bottom: 5px;
            page-break-inside: avoid;
        }

        .section:last-of-type {
            margin-bottom: 6px;
        }

        .section-title {
            font-size: 9pt;
            font-weight: bold;
            margin: 0 0 3px 0;
            padding: 3px 6px;
            background-color: #e8e8e8;
            border-left: 3px solid #000;
            text-transform: uppercase;
        }

        .info-grid {
            display: table;
            width: 100%;
            border-collapse: collapse;
        }

        .info-row {
            display: table-row;
        }

        .info-label {
            display: table-cell;
            font-weight: bold;
            padding: 2px 8px 2px 0;
            width: 35%;
            vertical-align: top;
            font-size: 8.5pt;
        }

        .info-value {
            display: table-cell;
            padding: 2px 0;
            vertical-align: top;
            font-size: 8.5pt;
        }

        .long-text {
            white-space: pre-wrap;
            word-wrap: break-word;
            padding: 4px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 2px;
            margin-top: 2px;
            min-height: 20px;
            font-size: 8pt;
            max-height: 50px;
            overflow: hidden;
        }

        .valor-destaque {
            font-size: 10pt;
            font-weight: bold;
            color: #000;
        }

        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 2px;
            font-weight: bold;
            font-size: 8pt;
        }

        .pago {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .nao-pago {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .footer {
            margin-top: 15px;
            padding-top: 8px;
            text-align: center;
            font-size: 8pt;
            color: #555;
            border-top: 1px solid #ccc;
        }

        <?php if ($exibir_assinaturas === 'Sim'): ?>
        .footer {
            margin-top: 8px;
            padding-top: 5px;
            font-size: 7.5pt;
        }
        <?php endif; ?>

        @media print {
            @page {
                margin: 15mm;
                margin-top: 10mm;
                margin-bottom: 20mm;
            }

            <?php if ($exibir_assinaturas === 'Sim'): ?>
            @page {
                margin: 10mm;
                margin-top: 8mm;
                margin-bottom: 15mm;
            }
            <?php endif; ?>

            body {
                padding: 0 !important;
                margin: 0 !important;
            }

            .footer {
                position: absolute;
                bottom: 5mm;
                left: 15mm;
                right: 15mm;
                text-align: center;
                font-size: 8pt;
                color: #555;
                border-top: 1px solid #ccc;
                background-color: #fff;
                padding-top: 5px;
            }

            <?php if ($exibir_assinaturas === 'Sim'): ?>
            .footer {
                left: 10mm;
                right: 10mm;
                font-size: 7.5pt;
            }
            <?php endif; ?>

            .page-container {
                padding-bottom: 25mm;
                position: relative;
                min-height: calc(100vh - 40mm);
            }

            <?php if ($exibir_assinaturas === 'Sim'): ?>
            .page-container {
                padding-bottom: 20mm;
                min-height: calc(100vh - 30mm);
            }
            <?php endif; ?>
        }

        .page-container {
            min-height: calc(100vh - 30px);
        }

        .assinaturas-container {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 2px solid #000;
            display: table;
            width: 100%;
            table-layout: fixed;
            page-break-inside: avoid;
        }

        .assinatura-box {
            display: table-cell;
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding: 8px 20px;
            height: 100%;
        }

        .assinatura-label {
            font-weight: bold;
            font-size: 9pt;
            margin-bottom: 30px;
            display: block;
            height: 20px;
        }

        .assinatura-linha {
            border-top: 1px solid #000;
            padding-top: 5px;
            margin-top: 0;
            font-size: 8.5pt;
            min-height: 45px;
            margin-bottom: 10px;
        }

        .assinatura-cpf {
            font-size: 8pt;
            margin-top: 10px;
            min-height: 15px;
        }
    </style>
</head>
<body>

<?php if ($servico): ?>
    <div class="page-container">
        <div class="header">
            <h1 class="empresa-nome">NETTO SONORIZAÇÃO</h1>
            <div class="ordem-numero">
                ORDEM DE SERVIÇO Nº <?php echo str_pad($servico['id'], 4, '0', STR_PAD_LEFT); ?>
            </div>
        </div>

        <div class="content-wrapper">
        <div class="section">
            <div class="section-title">Informações do Cliente</div>
            <div class="info-grid">
                <div class="info-row">
                    <div class="info-label">Nome:</div>
                    <div class="info-value"><?php echo htmlspecialchars($servico['nome_cliente']); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">CPF/CNPJ:</div>
                    <div class="info-value"><?php echo htmlspecialchars($servico['cpf_cnpj_cliente'] ?: 'N/A'); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Telefone:</div>
                    <div class="info-value"><?php echo htmlspecialchars($servico['telefone_cliente'] ?: 'N/A'); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">E-mail:</div>
                    <div class="info-value"><?php echo htmlspecialchars($servico['email_cliente'] ?: 'N/A'); ?></div>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">Informações do Equipamento/Veículo</div>
            <div class="info-grid">
                <div class="info-row">
                    <div class="info-label">Placa:</div>
                    <div class="info-value"><?php echo htmlspecialchars($servico['placa'] ?: 'N/A'); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Marca:</div>
                    <div class="info-value"><?php echo htmlspecialchars($servico['marca'] ?: 'N/A'); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Modelo:</div>
                    <div class="info-value"><?php echo htmlspecialchars($servico['modelo'] ?: 'N/A'); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Ano Fabricação:</div>
                    <div class="info-value"><?php echo htmlspecialchars($servico['ano_fab'] ?: 'N/A'); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Ano Modelo:</div>
                    <div class="info-value"><?php echo htmlspecialchars($servico['ano_mod'] ?: 'N/A'); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Cor:</div>
                    <div class="info-value"><?php echo htmlspecialchars($servico['cor'] ?: 'N/A'); ?></div>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">Detalhes do Serviço</div>
            <div class="info-grid">
                <div class="info-row">
                    <div class="info-label">Data de Entrada:</div>
                    <div class="info-value"><?php echo date('d/m/Y', strtotime($servico['data_entrada'])); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Data de Saída:</div>
                    <div class="info-value"><?php echo date('d/m/Y', strtotime($servico['data_saida'])); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Tipo de Serviço:</div>
                    <div class="info-value"><?php echo htmlspecialchars($servico['tipo_servico']); ?></div>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">Problema Relatado</div>
            <div class="long-text"><?php echo nl2br(htmlspecialchars($servico['descricao_problema'] ?: 'Não informado')); ?></div>
        </div>

        <div class="section">
            <div class="section-title">Serviço Executado</div>
            <div class="long-text"><?php echo nl2br(htmlspecialchars($servico['servico_executado'] ?: 'Não informado')); ?></div>
        </div>

        <div class="section">
            <div class="section-title">Informações de Pagamento e Garantia</div>
            <div class="info-grid">
                <div class="info-row">
                    <div class="info-label">Valor Total:</div>
                    <div class="info-value valor-destaque">R$ <?php echo number_format($servico['valor'], 2, ',', '.'); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Status do Pagamento:</div>
                    <div class="info-value">
                        <?php if ($servico['pago'] == 'Sim'): ?>
                            <span class="status-badge pago">Pago</span>
                        <?php else: ?>
                            <span class="status-badge nao-pago">Não Pago</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label">Parcelado:</div>
                    <div class="info-value"><?php echo htmlspecialchars($servico['parcelado']); ?></div>
                </div>
                <?php if ($servico['parcelado'] == 'Sim' && !empty($servico['num_parcelas'])): ?>
                <div class="info-row">
                    <div class="info-label">Número de Parcelas:</div>
                    <div class="info-value"><?php echo htmlspecialchars($servico['num_parcelas']); ?>x</div>
                </div>
                <?php endif; ?>
                <div class="info-row">
                    <div class="info-label">Garantia:</div>
                    <div class="info-value"><?php echo htmlspecialchars($servico['garantia'] ?: '0'); ?> dias</div>
                </div>
                <?php if ($servico['data_fim_garantia']): ?>
                <div class="info-row">
                    <div class="info-label">Fim da Garantia:</div>
                    <div class="info-value"><?php echo date('d/m/Y', strtotime($servico['data_fim_garantia'])); ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($exibir_assinaturas === 'Sim'): ?>
        <div class="section">
            <div class="assinaturas-container">
                <div class="assinatura-box">
                    <span class="assinatura-label">Assinatura da Loja Prestadora de Serviço</span>
                    <div class="assinatura-linha">
                        <br>
                        _________________________________<br>
                        NETTO SONORIZAÇÃO
                    </div>
                    <div class="assinatura-cpf">
                        CNPJ: <?php echo htmlspecialchars($cnpj_empresa ?: '_________________'); ?>
                    </div>
                </div>
                <div class="assinatura-box">
                    <span class="assinatura-label">Assinatura do Cliente</span>
                    <div class="assinatura-linha">
                        <br>
                        _________________________________<br>
                        <?php echo htmlspecialchars($servico['nome_cliente']); ?>
                    </div>
                    <div class="assinatura-cpf">
                        CPF/CNPJ: <?php echo htmlspecialchars($servico['cpf_cnpj_cliente'] ?: '_________________'); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="footer">
            <p>Data e Hora da Impressão: <?php echo date('d/m/Y H:i:s'); ?></p>
        </div>
    </div>

<?php else: ?>
    <h1 style="color: red;">Erro: Serviço não encontrado.</h1>
<?php endif; ?>

<script>
    // A função de impressão será chamada automaticamente quando a página carregar
    window.onload = function() {
        // Aguarda um momento para garantir que o CSS foi aplicado
        setTimeout(function() {
            // Tenta configurar para não mostrar cabeçalhos e rodapés (depende do navegador)
            window.print();
        }, 250);
        
        window.onafterprint = function() {
            window.close();
        };
    };

    // Adiciona um event listener para quando a impressão for aberta
    window.onbeforeprint = function() {
        // Remove qualquer título que possa aparecer
        document.title = '';
    };
</script>

</body>
</html>