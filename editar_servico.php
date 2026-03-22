

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

$mensagem_sucesso = "";
$mensagem_erro = "";
$servico = null;
$servico_id = null;
$clientes_disponiveis = [];

// Carregar clientes para o campo select
try {
    $stmt_clientes = $pdo->query("SELECT id, nome FROM clientes ORDER BY nome ASC");
    $clientes_disponiveis = $stmt_clientes->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $mensagem_erro .= "<br>Erro ao carregar lista de clientes: " . $e->getMessage();
    error_log("Erro no PDO ao carregar clientes em editar_servico.php: " . $e->getMessage());
}

// --- Lógica para buscar o serviço a ser editado ---
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $servico_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
} elseif (isset($_POST['id']) && !empty($_POST['id'])) {
    $servico_id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
}

if ($servico_id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM servicos WHERE id = :id");
        $stmt->bindParam(':id', $servico_id, PDO::PARAM_INT);
        $stmt->execute();
        $servico = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$servico) {
            $_SESSION['servico_erro'] = "Serviço não encontrado.";
            header("Location: listar_servicos.php");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['servico_erro'] = "Erro ao buscar serviço: " . $e->getMessage();
        header("Location: listar_servicos.php");
        exit();
    }
} else {
    $_SESSION['servico_erro'] = "ID do serviço não fornecido ou inválido.";
    header("Location: listar_servicos.php");
    exit();
}

// --- Lógica para processar a submissão do formulário de edição (POST) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $form_servico_id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);

    if ($form_servico_id != $servico['id']) {
        $mensagem_erro = "Erro de validação: O ID do formulário não corresponde ao ID do serviço.";
    } else {
        $cliente_id = filter_input(INPUT_POST, 'cliente_id', FILTER_SANITIZE_NUMBER_INT);

        // --- LÓGICA DE PADRONIZAÇÃO DA PLACA ---
        $placa_input = preg_replace('/[^a-zA-Z0-9]/', '', $_POST['placa'] ?? '');
        $placa_input = strtoupper($placa_input);

        if (strlen($placa_input) === 7) {
            if (ctype_alpha(substr($placa_input, 4, 1))) {
                $placa = $placa_input;
            } else {
                $placa = substr($placa_input, 0, 3) . '-' . substr($placa_input, 3, 4);
            }
        } else {
            $placa = $placa_input;
        }

        $placa = empty($placa) ? null : $placa;

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
        $valor_original = trim($_POST['valor'] ?? '');
        $valor_brl = str_replace(['.', ','], ['', '.'], $valor_original);
        $valor = filter_var($valor_brl, FILTER_VALIDATE_FLOAT);

        $garantia = filter_input(INPUT_POST, 'garantia', FILTER_SANITIZE_NUMBER_INT);
        $pago = htmlspecialchars(trim($_POST['pago'] ?? 'Não'));
        $parcelado = htmlspecialchars(trim($_POST['parcelado'] ?? 'Não'));
        $num_parcelas = filter_input(INPUT_POST, 'num_parcelas', FILTER_SANITIZE_NUMBER_INT);

        $data_entrada = empty($data_entrada) ? null : $data_entrada;
        $data_saida = empty($data_saida) ? null : $data_saida;

        // Mantém os dados no formulário em caso de erro
        $servico = array_merge($servico, $_POST);
        $servico['placa'] = $placa ?? '';
        $servico['data_entrada'] = $data_entrada ?? '';
        $servico['data_saida'] = $data_saida ?? '';
        $servico['valor'] = ($valor !== false && $valor !== null) ? $valor : ($servico['valor'] ?? null);

        // Validação dos campos obrigatórios
        if (
            empty($cliente_id) ||
            empty($tipo_servico) ||
            empty($descricao_problema) ||
            empty($servico_executado) ||
            $valor === null ||
            $valor === false ||
            $valor < 0 ||
            empty($pago)
        ) {
            $mensagem_erro = "Erro: Por favor, preencha todos os campos obrigatórios corretamente.";
        } elseif ($parcelado === 'Sim' && (empty($num_parcelas) || !is_numeric($num_parcelas) || $num_parcelas <= 0)) {
            $mensagem_erro = "Erro: Se o pagamento for parcelado, o número de parcelas é obrigatório e deve ser um número positivo.";
        } else {
            try {
                $data_fim_garantia = null;
                if (!empty($garantia) && is_numeric($garantia) && $garantia > 0 && !empty($data_saida)) {
                    $data_fim_garantia = date('Y-m-d', strtotime($data_saida . ' + ' . $garantia . ' days'));
                }

                $stmt = $pdo->prepare("UPDATE servicos SET
                    cliente_id = :cliente_id,
                    placa = :placa,
                    modelo = :modelo,
                    marca = :marca,
                    ano_fab = :ano_fab,
                    ano_mod = :ano_mod,
                    cor = :cor,
                    data_entrada = :data_entrada,
                    data_saida = :data_saida,
                    tipo_servico = :tipo_servico,
                    descricao_problema = :descricao_problema,
                    servico_executado = :servico_executado,
                    valor = :valor,
                    garantia = :garantia,
                    data_fim_garantia = :data_fim_garantia,
                    pago = :pago,
                    parcelado = :parcelado,
                    num_parcelas = :num_parcelas,
                    updated_at = NOW()
                WHERE id = :id");

                $stmt->bindParam(':cliente_id', $cliente_id, PDO::PARAM_INT);

                if ($placa === null) {
                    $stmt->bindValue(':placa', null, PDO::PARAM_NULL);
                } else {
                    $stmt->bindParam(':placa', $placa, PDO::PARAM_STR);
                }

                $stmt->bindValue(':modelo', empty($modelo) ? null : $modelo, PDO::PARAM_STR);
                $stmt->bindValue(':marca', empty($marca) ? null : $marca, PDO::PARAM_STR);
                $stmt->bindValue(':ano_fab', empty($ano_fab) ? null : $ano_fab, empty($ano_fab) ? PDO::PARAM_NULL : PDO::PARAM_INT);
                $stmt->bindValue(':ano_mod', empty($ano_mod) ? null : $ano_mod, empty($ano_mod) ? PDO::PARAM_NULL : PDO::PARAM_INT);
                $stmt->bindValue(':cor', empty($cor) ? null : $cor, PDO::PARAM_STR);
                $stmt->bindValue(':data_entrada', $data_entrada, $data_entrada === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                $stmt->bindValue(':data_saida', $data_saida, $data_saida === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                $stmt->bindParam(':tipo_servico', $tipo_servico, PDO::PARAM_STR);
                $stmt->bindParam(':descricao_problema', $descricao_problema, PDO::PARAM_STR);
                $stmt->bindParam(':servico_executado', $servico_executado, PDO::PARAM_STR);
                $stmt->bindParam(':valor', $valor);
                $stmt->bindValue(':garantia', empty($garantia) ? null : $garantia, empty($garantia) ? PDO::PARAM_NULL : PDO::PARAM_INT);
                $stmt->bindValue(':data_fim_garantia', $data_fim_garantia, $data_fim_garantia === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                $stmt->bindParam(':pago', $pago, PDO::PARAM_STR);
                $stmt->bindParam(':parcelado', $parcelado, PDO::PARAM_STR);
                $stmt->bindValue(':num_parcelas', empty($num_parcelas) ? null : $num_parcelas, empty($num_parcelas) ? PDO::PARAM_NULL : PDO::PARAM_INT);
                $stmt->bindParam(':id', $servico_id, PDO::PARAM_INT);

                if ($stmt->execute()) {
                    $_SESSION['servico_sucesso'] = "Serviço atualizado com sucesso!";
                    header("Location: listar_servicos.php");
                    exit();
                } else {
                    $errorInfo = $stmt->errorInfo();
                    $mensagem_erro = "Erro ao atualizar serviço: " . ($errorInfo[2] ?? "Erro desconhecido.");
                    error_log("Erro PDO (UPDATE) em editar_servico.php: " . ($errorInfo[2] ?? "Erro desconhecido."));
                }
            } catch (PDOException $e) {
                $mensagem_erro = "Erro no banco de dados ao atualizar serviço: " . $e->getMessage();
                error_log("Erro PDO geral na atualização de serviço: " . $e->getMessage());
            }
        }
    }
}

// Inclui o header APÓS processar o POST
include 'includes/header.php';

if (!$servico) {
    header("Location: listar_servicos.php");
    exit();
}

$valor_formatado = ($servico['valor'] !== null && $servico['valor'] !== '') ? number_format((float)$servico['valor'], 2, ',', '.') : '';

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
    <i class="bi bi-pencil-square me-2 text-neon-blue"></i>Editar Serviço
</h1>

<?php
if ($mensagem_sucesso) {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . htmlspecialchars($mensagem_sucesso) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
}
if ($mensagem_erro) {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . htmlspecialchars($mensagem_erro) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
}
?>

<p class="text-white text-center mb-4">Edite os campos abaixo para atualizar o serviço. Campos marcados com <span class="text-danger">*</span> são obrigatórios.</p>

<form action="editar_servico.php?id=<?php echo htmlspecialchars($servico['id']); ?>" method="POST" class="bg-dark p-4 rounded shadow-lg text-white" id="servicoForm">
    <input type="hidden" name="id" value="<?php echo htmlspecialchars($servico['id']); ?>">

    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="cliente_id" class="form-label">Cliente <span class="text-danger">*</span></label>
            <select class="form-select" id="cliente_id" name="cliente_id" required>
                <option value="">Selecione um cliente</option>
                <?php foreach ($clientes_disponiveis as $cliente): ?>
                    <option value="<?php echo htmlspecialchars($cliente['id']); ?>" <?php echo ($servico['cliente_id'] == $cliente['id']) ? 'selected' : ''; ?>>
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
                    <option value="<?php echo htmlspecialchars($tipo); ?>" <?php echo ((isset($servico['tipo_servico']) && $servico['tipo_servico'] == $tipo) ? 'selected' : ''); ?>>
                        <?php echo htmlspecialchars($tipo); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="data_entrada" class="form-label">Data de Entrada</label>
            <input type="date" class="form-control" id="data_entrada" name="data_entrada" value="<?php echo htmlspecialchars($servico['data_entrada'] ?? ''); ?>">
        </div>
        <div class="col-md-6 mb-3">
            <label for="data_saida" class="form-label">Data de Saída</label>
            <input type="date" class="form-control" id="data_saida" name="data_saida" value="<?php echo htmlspecialchars($servico['data_saida'] ?? ''); ?>">
        </div>
    </div>

    <hr class="my-4 border-secondary">
    <h5 class="mb-3 text-neon-blue"><i class="bi bi-car-fill me-2"></i>Informações do Equipamento/Veículo (Opcional)</h5>
    <div class="row">
        <div class="col-md-4 mb-3">
            <label for="placa" class="form-label">Placa</label>
            <input type="text" class="form-control" id="placa" name="placa" placeholder="ABC-1234" value="<?php echo htmlspecialchars($servico['placa'] ?? ''); ?>">
        </div>
        <div class="col-md-4 mb-3">
            <label for="modelo" class="form-label">Modelo</label>
            <input type="text" class="form-control" id="modelo" name="modelo" placeholder="Ex: Celta, Onix, Mesa de Som" value="<?php echo htmlspecialchars($servico['modelo'] ?? ''); ?>">
        </div>
        <div class="col-md-4 mb-3">
            <label for="marca" class="form-label">Marca</label>
            <input type="text" class="form-control" id="marca" name="marca" placeholder="Ex: Chevrolet, Behringer" value="<?php echo htmlspecialchars($servico['marca'] ?? ''); ?>">
        </div>
    </div>
    <div class="row">
        <div class="col-md-4 mb-3">
            <label for="ano_fab" class="form-label">Ano Fabricação</label>
            <input type="number" class="form-control" id="ano_fab" name="ano_fab" placeholder="AAAA" value="<?php echo htmlspecialchars($servico['ano_fab'] ?? ''); ?>">
        </div>
        <div class="col-md-4 mb-3">
            <label for="ano_mod" class="form-label">Ano Modelo</label>
            <input type="number" class="form-control" id="ano_mod" name="ano_mod" placeholder="AAAA" value="<?php echo htmlspecialchars($servico['ano_mod'] ?? ''); ?>">
        </div>
        <div class="col-md-4 mb-3">
            <label for="cor" class="form-label">Cor</label>
            <input type="text" class="form-control" id="cor" name="cor" placeholder="Ex: Preto, Branco" value="<?php echo htmlspecialchars($servico['cor'] ?? ''); ?>">
        </div>
    </div>

    <hr class="my-4 border-secondary">
    <h5 class="mb-3 text-neon-blue"><i class="bi bi-tools me-2"></i>Detalhes do Serviço</h5>
    <div class="mb-3">
        <label for="descricao_problema" class="form-label">Problema Relatado <span class="text-danger">*</span></label>
        <textarea class="form-control" id="descricao_problema" name="descricao_problema" rows="5" placeholder="Descrição do problema relatado pelo cliente..." required><?php echo htmlspecialchars($servico['descricao_problema'] ?? ''); ?></textarea>
    </div>
    <div class="mb-3">
        <label for="servico_executado" class="form-label">Serviço Executado <span class="text-danger">*</span></label>
        <textarea class="form-control" id="servico_executado" name="servico_executado" rows="3" placeholder="Detalhes do serviço que foi executado..." required><?php echo htmlspecialchars($servico['servico_executado'] ?? ''); ?></textarea>
    </div>
    <div class="row">
        <div class="col-md-4 mb-3">
            <label for="valor" class="form-label">Valor (R$) <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="valor" name="valor" placeholder="Ex: 150,00" value="<?php echo htmlspecialchars($valor_formatado); ?>" required>
        </div>
        <div class="col-md-4 mb-3">
            <label for="garantia" class="form-label">Garantia (dias - Opcional)</label>
            <input type="number" class="form-control" id="garantia" name="garantia" placeholder="Ex: 90" min="0" value="<?php echo htmlspecialchars($servico['garantia'] ?? ''); ?>">
        </div>
        <div class="col-md-4 mb-3">
            <label for="data_fim_garantia" class="form-label">Data Fim da Garantia</label>
            <input type="date" class="form-control" id="data_fim_garantia" name="data_fim_garantia" readonly value="<?php echo htmlspecialchars($servico['data_fim_garantia'] ?? ''); ?>">
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="pago" class="form-label">Status de Pagamento <span class="text-danger">*</span></label>
            <select class="form-select" id="pago" name="pago" required>
                <option value="Sim" <?php echo (($servico['pago'] ?? '') == 'Sim') ? 'selected' : ''; ?>>Sim</option>
                <option value="Não" <?php echo (($servico['pago'] ?? '') == 'Não') ? 'selected' : ''; ?>>Não</option>
            </select>
        </div>
        <div class="col-md-6 mb-3">
            <label for="parcelado" class="form-label">Pagamento Parcelado?</label>
            <select class="form-select" id="parcelado" name="parcelado">
                <option value="Não" <?php echo (($servico['parcelado'] ?? '') == 'Não') ? 'selected' : ''; ?>>Não</option>
                <option value="Sim" <?php echo (($servico['parcelado'] ?? '') == 'Sim') ? 'selected' : ''; ?>>Sim</option>
            </select>
        </div>
    </div>
    <div class="mb-3" id="divNumParcelas">
        <label for="num_parcelas" class="form-label">Número de Parcelas</label>
        <input type="number" class="form-control" id="num_parcelas" name="num_parcelas" placeholder="Ex: 3" min="1" value="<?php echo htmlspecialchars($servico['num_parcelas'] ?? ''); ?>">
    </div>

    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
        <button type="submit" class="btn btn-primary btn-lg"><i class="bi bi-save me-2"></i>Salvar Alterações</button>
        <a href="listar_servicos.php" class="btn btn-secondary btn-lg"><i class="bi bi-x-circle me-2"></i>Cancelar</a>
    </div>
</form>

<?php include 'includes/footer.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const placaInput = document.getElementById('placa');
        if (placaInput) {
            placaInput.addEventListener('input', function() {
                this.value = this.value.replace(/[^a-zA-Z0-9]/g, '');
                this.value = this.value.toUpperCase();
            });
        }

        const dataSaidaInput = document.getElementById('data_saida');
        const garantiaInput = document.getElementById('garantia');
        const dataFimGarantiaInput = document.getElementById('data_fim_garantia');
        const parceladoSelect = document.getElementById('parcelado');
        const divNumParcelas = document.getElementById('divNumParcelas');
        const numParcelasInput = document.getElementById('num_parcelas');

        function calcularDataFimGarantia() {
            const dataSaidaStr = dataSaidaInput.value;
            const garantiaDias = parseInt(garantiaInput.value);

            if (dataSaidaStr && !isNaN(garantiaDias) && garantiaDias > 0) {
                const dataSaida = new Date(dataSaidaStr + 'T00:00:00');
                dataSaida.setDate(dataSaida.getDate() + garantiaDias);

                const ano = dataSaida.getFullYear();
                const mes = String(dataSaida.getMonth() + 1).padStart(2, '0');
                const dia = String(dataSaida.getDate()).padStart(2, '0');
                dataFimGarantiaInput.value = `${ano}-${mes}-${dia}`;
            } else {
                dataFimGarantiaInput.value = '';
            }
        }

        if (dataSaidaInput && garantiaInput && dataFimGarantiaInput) {
            dataSaidaInput.addEventListener('change', calcularDataFimGarantia);
            garantiaInput.addEventListener('input', calcularDataFimGarantia);
            calcularDataFimGarantia();
        }

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

        if (parceladoSelect) {
            parceladoSelect.addEventListener('change', toggleNumParcelas);
            toggleNumParcelas();
        }

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
                value = value.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');

                e.target.value = value;
            });
        }
    });
</script>
