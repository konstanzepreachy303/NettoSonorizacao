<?php
// Ativar exibição de erros para depuração (REMOVER EM PRODUÇÃO FINAL)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Inicia a sessão para acesso às variáveis de sessão e controle de login
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Verifica se o usuário está logado como administrador
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

// Inclui o arquivo de conexão com o banco de dados
require_once 'includes/conexao.php';

// Definir a lista de tipos de serviço para o campo select.
$tipos_servico_disponiveis = [
    'Manutenção',
    'Instalação',
    'Orçamento',
    'Visita Técnica',
    'Outros'
];

// Carregar clientes para o campo select do formulário
$sql_clientes = "SELECT id, nome FROM clientes ORDER BY nome ASC";
try {
    $stmt_clientes = $pdo->query($sql_clientes);
    $clientes_disponiveis = $stmt_clientes->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $clientes_disponiveis = [];
    error_log("Erro ao buscar clientes em cadastrar_servico.php: " . $e->getMessage());
    $_SESSION['manutencao_erro'] = "Erro ao carregar a lista de clientes.";
}

$mensagem_sucesso = "";
$mensagem_erro = "";
$cadastro_sucesso = false; // Flag para controlar a exibição do overlay

// Variáveis para preencher o formulário em caso de erro
$cliente_id_old = '';
$placa_old = '';
$modelo_old = '';
$marca_old = '';
$ano_fab_old = '';
$ano_mod_old = '';
$cor_old = '';
$data_entrada_old = '';
$data_saida_old = '';
$tipo_servico_old = '';
$descricao_problema_old = '';
$servico_executado_old = '';
$valor_old = '';
$garantia_old = '';
$data_fim_garantia_old = '';
$pago_old = 'Não';
$parcelado_old = 'Não';
$num_parcelas_old = '';

// Lógica para processar o formulário
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // --- LÓGICA DE PADRONIZAÇÃO DA PLACA ---
    $placa_input = preg_replace('/[^a-zA-Z0-9]/', '', $_POST['placa'] ?? '');
    $placa = strtoupper($placa_input);

    if (strlen($placa) === 7) {
        if (ctype_alpha(substr($placa, 4, 1))) {
            $placa = $placa; // Padrão Mercosul
        } else {
            $placa = substr($placa, 0, 3) . '-' . substr($placa, 3, 4); // Padrão Antigo
        }
    }

    // Filtra e sanitiza os outros dados do formulário
    $cliente_id = filter_input(INPUT_POST, 'cliente_id', FILTER_SANITIZE_NUMBER_INT);
    $modelo = htmlspecialchars(trim($_POST['modelo'] ?? ''));
    $marca = htmlspecialchars(trim($_POST['marca'] ?? ''));
    $ano_fab = filter_input(INPUT_POST, 'ano_fab', FILTER_SANITIZE_NUMBER_INT);
    $ano_mod = filter_input(INPUT_POST, 'ano_mod', FILTER_SANITIZE_NUMBER_INT);
    $cor = htmlspecialchars(trim($_POST['cor'] ?? ''));
    $data_entrada = htmlspecialchars(trim($_POST['data_entrada'] ?? ''));
    $data_saida = htmlspecialchars(trim($_POST['data_saida'] ?? ''));
    $tipo_servico = htmlspecialchars(trim($_POST['tipo_servico'] ?? ''));
    $descricao_problema = htmlspecialchars(trim($_POST['descricao_problema'] ?? ''));
    $servico_executado = htmlspecialchars(trim($_POST['servico_executado'] ?? ''));
    // AQUI: A correção do valor para salvar no banco.
    $valor_brl = str_replace(['.', ','], ['', '.'], trim($_POST['valor'] ?? ''));
    $valor = filter_var($valor_brl, FILTER_VALIDATE_FLOAT);

    $garantia = filter_input(INPUT_POST, 'garantia', FILTER_SANITIZE_NUMBER_INT);
    $pago = htmlspecialchars(trim($_POST['pago'] ?? 'Não'));
    $parcelado = htmlspecialchars(trim($_POST['parcelado'] ?? 'Não'));
    $num_parcelas = filter_input(INPUT_POST, 'num_parcelas', FILTER_SANITIZE_NUMBER_INT);

    // Validação dos campos
    if (empty($cliente_id) || empty($placa) || empty($data_entrada) || empty($data_saida) || empty($tipo_servico) || empty($descricao_problema) || empty($servico_executado) || $valor === null || $valor === false || $valor < 0 || empty($pago)) {
        $mensagem_erro = "Erro: Por favor, preencha todos os campos obrigatórios corretamente.";
    } elseif ($parcelado === 'Sim' && (empty($num_parcelas) || !is_numeric($num_parcelas) || $num_parcelas <= 0)) {
        $mensagem_erro = "Erro: Se o pagamento for parcelado, o número de parcelas é obrigatório e deve ser um número positivo.";
    } else {
        try {
            // Calcular data de fim da garantia
            $data_fim_garantia = null;
            if (!empty($garantia) && is_numeric($garantia) && $garantia > 0 && !empty($data_saida)) {
                $data_fim_garantia = date('Y-m-d', strtotime($data_saida . ' + ' . $garantia . ' days'));
            }

            $stmt = $pdo->prepare("INSERT INTO servicos (cliente_id, placa, modelo, marca, ano_fab, ano_mod, cor, data_entrada, data_saida, tipo_servico, descricao_problema, servico_executado, valor, garantia, data_fim_garantia, pago, parcelado, num_parcelas, created_at, updated_at)
            VALUES (:cliente_id, :placa, :modelo, :marca, :ano_fab, :ano_mod, :cor, :data_entrada, :data_saida, :tipo_servico, :descricao_problema, :servico_executado, :valor, :garantia, :data_fim_garantia, :pago, :parcelado, :num_parcelas, NOW(), NOW())");

            $stmt->bindParam(':cliente_id', $cliente_id, PDO::PARAM_INT);
            $stmt->bindParam(':placa', $placa, PDO::PARAM_STR);
            $stmt->bindParam(':modelo', $modelo, PDO::PARAM_STR);
            $stmt->bindParam(':marca', $marca, PDO::PARAM_STR);
            $stmt->bindParam(':ano_fab', $ano_fab, PDO::PARAM_INT);
            $stmt->bindParam(':ano_mod', $ano_mod, PDO::PARAM_INT);
            $stmt->bindParam(':cor', $cor, PDO::PARAM_STR);
            $stmt->bindParam(':data_entrada', $data_entrada, PDO::PARAM_STR);
            $stmt->bindParam(':data_saida', $data_saida, PDO::PARAM_STR);
            $stmt->bindParam(':tipo_servico', $tipo_servico, PDO::PARAM_STR);
            $stmt->bindParam(':descricao_problema', $descricao_problema, PDO::PARAM_STR);
            $stmt->bindParam(':servico_executado', $servico_executado, PDO::PARAM_STR);
            $stmt->bindParam(':valor', $valor);
            $stmt->bindParam(':garantia', $garantia, PDO::PARAM_INT);
            $stmt->bindParam(':data_fim_garantia', $data_fim_garantia, PDO::PARAM_STR);
            $stmt->bindParam(':pago', $pago, PDO::PARAM_STR);
            $stmt->bindParam(':parcelado', $parcelado, PDO::PARAM_STR);
            $stmt->bindParam(':num_parcelas', $num_parcelas, PDO::PARAM_INT);

            if ($stmt->execute()) {
                $mensagem_sucesso = "Serviço cadastrado com sucesso!";
                $cadastro_sucesso = true;
            } else {
                $errorInfo = $stmt->errorInfo();
                $mensagem_erro = "Erro ao cadastrar serviço: " . ($errorInfo[2] ?? "Erro desconhecido.");
                error_log("Erro PDO (INSERT) em cadastrar_servico.php: " . ($errorInfo[2] ?? "Erro desconhecido."));
                // Preenche as variáveis "old" para manter os dados no formulário
                $placa_old = $placa;
                $cliente_id_old = $cliente_id;
                $modelo_old = $modelo;
                $marca_old = $marca;
                $ano_fab_old = $ano_fab;
                $ano_mod_old = $ano_mod;
                $cor_old = $cor;
                $data_entrada_old = $data_entrada;
                $data_saida_old = $data_saida;
                $tipo_servico_old = $tipo_servico;
                $descricao_problema_old = $descricao_problema;
                $servico_executado_old = $servico_executado;
                $valor_old = $valor;
                $garantia_old = $garantia;
                $data_fim_garantia_old = $data_fim_garantia;
                $pago_old = $pago;
                $parcelado_old = $parcelado;
                $num_parcelas_old = $num_parcelas;
            }
        } catch (PDOException $e) {
            $mensagem_erro = "Erro no banco de dados ao cadastrar serviço: " . $e->getMessage();
            error_log("Erro PDO geral no cadastro de serviço: " . $e->getMessage());
            // Preenche as variáveis "old" em caso de erro no try-catch
            $placa_old = $placa;
            $cliente_id_old = $cliente_id;
            $modelo_old = $modelo;
            $marca_old = $marca;
            $ano_fab_old = $ano_fab;
            $ano_mod_old = $ano_mod;
            $cor_old = $cor;
            $data_entrada_old = $data_entrada;
            $data_saida_old = $data_saida;
            $tipo_servico_old = $tipo_servico;
            $descricao_problema_old = $descricao_problema;
            $servico_executado_old = $servico_executado;
            $valor_old = $valor;
            $garantia_old = $garantia;
            $data_fim_garantia_old = $data_fim_garantia;
            $pago_old = $pago;
            $parcelado_old = $parcelado;
            $num_parcelas_old = $num_parcelas;
        }
    }
}

// Inclui o header APÓS processar o POST (para evitar erro de headers already sent)
include 'includes/header.php';

// Exibe mensagens de sucesso/erro da sessão
if (isset($_SESSION['servico_sucesso'])) {
    $mensagem_sucesso = $_SESSION['servico_sucesso'];
    unset($_SESSION['servico_sucesso']);
}
if (isset($_SESSION['servico_erro'])) {
    $mensagem_erro = $_SESSION['servico_erro'];
    unset($_SESSION['servico_erro']);
}
?>

<h1 class="mb-4 text-center text-white">
    <i class="bi bi-gear-fill me-2 text-neon-blue"></i>Cadastrar Novo Serviço
</h1>

<?php
if ($mensagem_sucesso) {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . htmlspecialchars($mensagem_sucesso) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
}
if ($mensagem_erro) {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . htmlspecialchars($mensagem_erro) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
}
?>

<p class="text-white text-center mb-4">Preencha os campos abaixo para registrar um novo serviço. Campos marcados com <span class="text-danger">*</span> são obrigatórios.</p>

<form action="cadastrar_servico.php" method="POST" class="bg-dark p-4 rounded shadow-lg text-white" id="servicoForm">
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="cliente_id" class="form-label">Cliente <span class="text-danger">*</span></label>
            <select class="form-select" id="cliente_id" name="cliente_id" required>
                <option value="">Selecione um cliente</option>
                <?php foreach ($clientes_disponiveis as $cliente): ?>
                    <option value="<?php echo htmlspecialchars($cliente['id']); ?>" <?php echo ($cliente_id_old == $cliente['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cliente['nome']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (empty($clientes_disponiveis)): ?>
                <div class="alert alert-warning mt-2" role="alert">
                    Nenhum cliente cadastrado. Por favor, <a href="cadastrar_cliente.php" class="alert-link">cadastre um cliente</a> primeiro.
                </div>
            <?php endif; ?>
        </div>
        <div class="col-md-6 mb-3">
            <label for="tipo_servico" class="form-label">Tipo de Serviço <span class="text-danger">*</span></label>
            <select class="form-select" id="tipo_servico" name="tipo_servico" required>
                <option value="">Selecione o tipo</option>
                <?php foreach ($tipos_servico_disponiveis as $tipo): ?>
                    <option value="<?php echo htmlspecialchars($tipo); ?>" <?php echo ($tipo_servico_old == $tipo) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($tipo); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="data_entrada" class="form-label">Data de Entrada <span class="text-danger">*</span></label>
            <input type="date" class="form-control" id="data_entrada" name="data_entrada" value="<?php echo htmlspecialchars($data_entrada_old); ?>" required>
        </div>
        <div class="col-md-6 mb-3">
            <label for="data_saida" class="form-label">Data de Saída <span class="text-danger">*</span></label>
            <input type="date" class="form-control" id="data_saida" name="data_saida" value="<?php echo htmlspecialchars($data_saida_old); ?>" required>
        </div>
    </div>

    <hr class="my-4 border-secondary">
    <h5 class="mb-3 text-neon-blue"><i class="bi bi-car-fill me-2"></i>Informações do Equipamento/Veículo (Opcional)</h5>
    <div class="row">
        <div class="col-md-4 mb-3">
            <label for="placa" class="form-label">Placa <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="placa" name="placa" placeholder="ABC-1234" value="<?php echo htmlspecialchars($placa_old); ?>" required>
        </div>
        <div class="col-md-4 mb-3">
            <label for="modelo" class="form-label">Modelo</label>
            <input type="text" class="form-control" id="modelo" name="modelo" placeholder="Ex: Celta, Onix, Mesa de Som" value="<?php echo htmlspecialchars($modelo_old); ?>">
        </div>
        <div class="col-md-4 mb-3">
            <label for="marca" class="form-label">Marca</label>
            <input type="text" class="form-control" id="marca" name="marca" placeholder="Ex: Chevrolet, Behringer" value="<?php echo htmlspecialchars($marca_old); ?>">
        </div>
    </div>
    <div class="row">
        <div class="col-md-4 mb-3">
            <label for="ano_fab" class="form-label">Ano Fabricação</label>
            <input type="number" class="form-control" id="ano_fab" name="ano_fab" placeholder="AAAA" value="<?php echo htmlspecialchars($ano_fab_old); ?>">
        </div>
        <div class="col-md-4 mb-3">
            <label for="ano_mod" class="form-label">Ano Modelo</label>
            <input type="number" class="form-control" id="ano_mod" name="ano_mod" placeholder="AAAA" value="<?php echo htmlspecialchars($ano_mod_old); ?>">
        </div>
        <div class="col-md-4 mb-3">
            <label for="cor" class="form-label">Cor</label>
            <input type="text" class="form-control" id="cor" name="cor" placeholder="Ex: Preto, Branco" value="<?php echo htmlspecialchars($cor_old); ?>">
        </div>
    </div>

    <hr class="my-4 border-secondary">
    <h5 class="mb-3 text-neon-blue"><i class="bi bi-tools me-2"></i>Detalhes do Serviço</h5>
    <div class="mb-3">
        <label for="descricao_problema" class="form-label">Problema Relatado <span class="text-danger">*</span></label>
        <textarea class="form-control" id="descricao_problema" name="descricao_problema" rows="5" placeholder="Descrição do problema relatado pelo cliente..." required><?php echo htmlspecialchars($descricao_problema_old); ?></textarea>
    </div>
    <div class="mb-3">
        <label for="servico_executado" class="form-label">Serviço Executado <span class="text-danger">*</span></label>
        <textarea class="form-control" id="servico_executado" name="servico_executado" rows="3" placeholder="Detalhes do serviço que foi executado..." required><?php echo htmlspecialchars($servico_executado_old); ?></textarea>
    </div>
    <div class="row">
        <div class="col-md-4 mb-3">
            <label for="valor" class="form-label">Valor (R$) <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="valor" name="valor" placeholder="Ex: 150,00" value="<?php echo htmlspecialchars($valor_old); ?>" required>
        </div>
        <div class="col-md-4 mb-3">
            <label for="garantia" class="form-label">Garantia (dias - Opcional)</label>
            <input type="number" class="form-control" id="garantia" name="garantia" placeholder="Ex: 90" min="0" value="<?php echo htmlspecialchars($garantia_old); ?>">
        </div>
        <div class="col-md-4 mb-3">
            <label for="data_fim_garantia" class="form-label">Data Fim da Garantia</label>
            <input type="date" class="form-control" id="data_fim_garantia" name="data_fim_garantia" readonly value="<?php echo htmlspecialchars($data_fim_garantia_old); ?>">
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="pago" class="form-label">Status de Pagamento <span class="text-danger">*</span></label>
            <select class="form-select" id="pago" name="pago" required>
                <option value="Sim" <?php echo ($pago_old == 'Sim') ? 'selected' : ''; ?>>Sim</option>
                <option value="Não" <?php echo ($pago_old == 'Não') ? 'selected' : ''; ?>>Não</option>
            </select>
        </div>
        <div class="col-md-6 mb-3">
            <label for="parcelado" class="form-label">Pagamento Parcelado?</label>
            <select class="form-select" id="parcelado" name="parcelado">
                <option value="Não" <?php echo ($parcelado_old == 'Não') ? 'selected' : ''; ?>>Não</option>
                <option value="Sim" <?php echo ($parcelado_old == 'Sim') ? 'selected' : ''; ?>>Sim</option>
            </select>
        </div>
    </div>
    <div class="mb-3" id="div_num_parcelas">
        <label for="num_parcelas" class="form-label">Número de Parcelas</label>
        <input type="number" class="form-control" id="num_parcelas" name="num_parcelas" placeholder="Ex: 3" min="1" value="<?php echo htmlspecialchars($num_parcelas_old); ?>">
    </div>

    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
        <button type="submit" class="btn btn-primary btn-lg"><i class="bi bi-plus-circle-fill me-2"></i>Cadastrar Serviço</button>
        <button type="reset" class="btn btn-secondary btn-lg"><i class="bi bi-x-circle me-2"></i>Limpar</button>
    </div>
</form>

<?php include 'includes/footer.php'; ?>

<?php if ($cadastro_sucesso): ?>
<style>
    .overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.8);
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        z-index: 1050;
    }

    .checkmark-circle {
        stroke-dasharray: 166;
        stroke-dashoffset: 166;
        stroke-width: 2;
        stroke-miterlimit: 10;
        stroke: #7ac142;
        fill: none;
        animation: stroke 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
    }

    .checkmark-path {
        transform-origin: 50% 50%;
        stroke-dasharray: 48;
        stroke-dashoffset: 48;
        stroke-width: 2;
        stroke-miterlimit: 10;
        stroke: #7ac142;
        fill: none;
        animation: stroke 0.6s cubic-bezier(0.65, 0, 0.45, 1) 0.8s forwards;
    }

    @keyframes stroke {
        100% {
            stroke-dashoffset: 0;
        }
    }
</style>

<div class="overlay text-white text-center">
    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 130.2"
        style="width:100px; height:100px; animation: scaleIn 0.5s cubic-bezier(0.65, 0, 0.45, 1) forwards;">
        <circle class="checkmark-circle" cx="65.1" cy="65.1" r="62.1"/>
        <polyline class="checkmark-path" points="100.2,40.2 51.5,88.8 29.8,67.1"/>
    </svg>
    <h2 class="mt-4">Serviço cadastrado com sucesso!</h2>
    <p>Você será redirecionado para a lista de serviços em instantes...</p>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const overlay = document.querySelector('.overlay');
        if (overlay) {
            setTimeout(() => {
                window.location.href = 'listar_servicos.php';
            }, 2000); // Redireciona após 2 segundos
        }
    });
</script>
<?php endif; ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const placaInput = document.getElementById('placa');
        if (placaInput) {
            placaInput.addEventListener('input', function() {
                // Remove caracteres que não são letras ou números
                this.value = this.value.replace(/[^a-zA-Z0-9]/g, '');
                // Converte para maiúsculas
                this.value = this.value.toUpperCase();
            });
        }

        const dataSaidaInput = document.getElementById('data_saida');
        const garantiaInput = document.getElementById('garantia');
        const dataFimGarantiaInput = document.getElementById('data_fim_garantia');

        function calcularDataFimGarantia() {
            const dataSaidaStr = dataSaidaInput.value;
            const garantiaDias = parseInt(garantiaInput.value);

            if (dataSaidaStr && !isNaN(garantiaDias) && garantiaDias > 0) {
                const data = new Date(dataSaidaStr + 'T00:00:00');
                data.setDate(data.getDate() + garantiaDias);
                dataFimGarantiaInput.value = data.toISOString().split('T')[0];
            } else {
                dataFimGarantiaInput.value = '';
            }
        }

        if (dataSaidaInput && garantiaInput && dataFimGarantiaInput) {
            dataSaidaInput.addEventListener('change', calcularDataFimGarantia);
            garantiaInput.addEventListener('input', calcularDataFimGarantia);
            calcularDataFimGarantia();
        }

        const parceladoSelect = document.getElementById('parcelado');
        const divNumParcelas = document.getElementById('div_num_parcelas');
        const numParcelasInput = document.getElementById('num_parcelas');

        function toggleNumParcelas() {
            if (parceladoSelect.value === 'Sim') {
                divNumParcelas.style.display = 'block';
                numParcelasInput.setAttribute('required', 'required');
                numParcelasInput.setAttribute('min', '1');
            } else {
                divNumParcelas.style.display = 'none';
                numParcelasInput.removeAttribute('required');
                numParcelasInput.removeAttribute('min');
                numParcelasInput.value = '';
            }
        }

        if (parceladoSelect && divNumParcelas && numParcelasInput) {
            parceladoSelect.addEventListener('change', toggleNumParcelas);
            toggleNumParcelas();
        }

        // LÓGICA DO MÁSCARA DE MOEDA
        const valorInput = document.getElementById('valor');
        if (valorInput) {
            valorInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');

                if (value.length === 0) {
                    e.target.value = '';
                    return;
                }

                value = (parseInt(value) / 100).toFixed(2);
                value = value.replace('.', ',');

                // Adiciona o separador de milhar
                value = value.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');

                e.target.value = value;
            });
        }
    });
</script>