<?php
// Inicia a sessão para acesso às variáveis de sessão e controle de login
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Verifica se o usuário está logado como administrador
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

// Inclui o arquivo de conexão com o banco de dados e o cabeçalho
require_once 'includes/conexao.php';
include 'includes/header.php';

// Definir a lista de tipos de serviço para o campo select de filtro
$tipos_servico_disponiveis = [
    'Manutenção',
    'Instalação',
    'Orçamento',
    'Visita Técnica',
    'Outros'
];

$mensagem_sucesso = isset($_SESSION['servico_sucesso']) ? $_SESSION['servico_sucesso'] : '';
$mensagem_erro = isset($_SESSION['servico_erro']) ? $_SESSION['servico_erro'] : '';
unset($_SESSION['servico_sucesso'], $_SESSION['servico_erro']);

$servicos = [];
$clientes_disponiveis = [];

// Carregar clientes para o campo select de filtro
try {
    $stmt_clientes = $pdo->query("SELECT id, nome FROM clientes ORDER BY nome ASC");
    $clientes_disponiveis = $stmt_clientes->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao carregar lista de clientes em listar_servicos.php: " . $e->getMessage());
    $mensagem_erro .= "Erro ao carregar a lista de clientes para o filtro.";
}

// Lógica de filtragem
$sql = "SELECT s.*, c.nome AS nome_cliente, c.cpf_cnpj AS cpf_cliente FROM servicos s JOIN clientes c ON s.cliente_id = c.id WHERE 1=1";
$params = [];

// Filtro por Cliente
$filtro_cliente = filter_input(INPUT_GET, 'filtro_cliente', FILTER_SANITIZE_NUMBER_INT);
if ($filtro_cliente) {
    $sql .= " AND s.cliente_id = :cliente_id";
    $params[':cliente_id'] = $filtro_cliente;
}

// Filtro por Tipo de Serviço
$filtro_tipo_servico = isset($_GET['filtro_tipo_servico']) && $_GET['filtro_tipo_servico'] !== '' ? htmlspecialchars($_GET['filtro_tipo_servico']) : '';
if ($filtro_tipo_servico) {
    $sql .= " AND s.tipo_servico = :tipo_servico";
    $params[':tipo_servico'] = $filtro_tipo_servico;
}

// Filtro por Status de Pagamento
$filtro_pago = isset($_GET['filtro_pago']) && $_GET['filtro_pago'] !== '' ? htmlspecialchars($_GET['filtro_pago']) : '';
if ($filtro_pago) {
    $sql .= " AND s.pago = :pago";
    $params[':pago'] = $filtro_pago;
}

// Filtro por CPF/CNPJ sem formatação
$filtro_cpf = isset($_GET['filtro_cpf']) && $_GET['filtro_cpf'] !== '' ? htmlspecialchars($_GET['filtro_cpf']) : '';
if ($filtro_cpf) {
    $filtro_cpf_sanitizado = preg_replace('/[^0-9]/', '', $filtro_cpf);
    $sql .= " AND REPLACE(REPLACE(REPLACE(REPLACE(c.cpf_cnpj, '.', ''), '-', ''), '/', ''), ' ', '') LIKE :cpf_sanitizado";
    $params[':cpf_sanitizado'] = "%" . $filtro_cpf_sanitizado . "%";
}

// Lógica para o filtro de placa (ignora formatação)
$filtro_placa = isset($_GET['filtro_placa']) ? htmlspecialchars(trim($_GET['filtro_placa'])) : '';
if (!empty($filtro_placa)) {
    $filtro_placa_sanitizada = preg_replace('/[^a-zA-Z0-9]/', '', $filtro_placa);
    $sql .= " AND REPLACE(s.placa, '-', '') LIKE :placa_sanitizada";
    $params[':placa_sanitizada'] = "%" . $filtro_placa_sanitizada . "%";
}

// Filtro por Garantia Vencida
$filtro_garantia_vencida = isset($_GET['filtro_garantia_vencida']) ? true : false;
if ($filtro_garantia_vencida) {
    $sql .= " AND s.data_fim_garantia IS NOT NULL AND s.data_fim_garantia < CURDATE()";
}

$sql .= " ORDER BY s.id DESC";

try {
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => &$val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->execute();
    $servicos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao buscar serviços: " . $e->getMessage());
    $mensagem_erro .= "Erro ao buscar os serviços no banco de dados.";
}
unset($val);
?>

<h1 class="mb-4 text-center text-white"><i class="bi bi-card-list me-2 text-neon-blue"></i>Lista de Serviços</h1>

<?php
if ($mensagem_sucesso) {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . htmlspecialchars($mensagem_sucesso) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
}
if ($mensagem_erro) {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . htmlspecialchars($mensagem_erro) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
}
?>

<div class="card bg-dark text-white shadow-lg mb-4">
    <div class="card-body">
        <h5 class="card-title text-neon-blue"><i class="bi bi-funnel-fill me-2"></i>Filtros</h5>
        <form action="listar_servicos.php" method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label for="filtro_cliente" class="form-label">Cliente</label>
                <select class="form-select" id="filtro_cliente" name="filtro_cliente">
                    <option value="">Todos</option>
                    <?php foreach ($clientes_disponiveis as $cliente): ?>
                        <option value="<?php echo htmlspecialchars($cliente['id']); ?>" <?php echo ($filtro_cliente == $cliente['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cliente['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="filtro_cpf" class="form-label">CPF/CNPJ do Cliente</label>
                <input type="text" class="form-control" id="filtro_cpf" name="filtro_cpf" value="<?php echo htmlspecialchars($filtro_cpf); ?>">
            </div>
            <div class="col-md-3">
                <label for="filtro_placa" class="form-label">Placa</label>
                <input type="text" class="form-control" id="filtro_placa" name="filtro_placa" value="<?php echo htmlspecialchars($filtro_placa); ?>">
            </div>
            <div class="col-md-3">
                <label for="filtro_tipo_servico" class="form-label">Tipo de Serviço</label>
                <select class="form-select" id="filtro_tipo_servico" name="filtro_tipo_servico">
                    <option value="">Todos</option>
                    <?php foreach ($tipos_servico_disponiveis as $tipo): ?>
                        <option value="<?php echo htmlspecialchars($tipo); ?>" <?php echo ($filtro_tipo_servico == $tipo) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($tipo); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="filtro_pago" class="form-label">Status de Pagamento</label>
                <select class="form-select" id="filtro_pago" name="filtro_pago">
                    <option value="">Todos</option>
                    <option value="Sim" <?php echo ($filtro_pago == 'Sim') ? 'selected' : ''; ?>>Pago</option>
                    <option value="Não" <?php echo ($filtro_pago == 'Não') ? 'selected' : ''; ?>>Não Pago</option>
                </select>
            </div>
            <div class="col-md-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="filtro_garantia_vencida" name="filtro_garantia_vencida" value="1" <?php echo ($filtro_garantia_vencida) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="filtro_garantia_vencida">Garantia Vencida</label>
                </div>
            </div>
            <div class="col-md-12 text-end">
                <button type="submit" class="btn btn-primary"><i class="bi bi-search me-2"></i>Filtrar</button>
                <a href="listar_servicos.php" class="btn btn-secondary"><i class="bi bi-x-circle me-2"></i>Limpar</a>
            </div>
        </form>
    </div>
</div>

<div class="table-responsive bg-dark p-3 rounded shadow-lg">
    <table class="table table-dark table-striped table-hover align-middle">
        <thead>
            <tr>
                <th scope="col">ID</th>
                <th scope="col">Cliente</th>
                <th scope="col">CPF/CNPJ</th>
                <th scope="col">Placa</th>
                <th scope="col">Data Entrada</th>
                <th scope="col">Data Saída</th>
                <th scope="col">Tipo de Serviço</th>
                <th scope="col">Valor</th>
                <th scope="col">Pagamento</th>
                <th scope="col">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($servicos) > 0): ?>
                <?php foreach ($servicos as $servico): ?>
                    <?php
                        $placa_exibicao = !empty($servico['placa']) ? $servico['placa'] : 'N/A';
                        $data_entrada_exibicao = !empty($servico['data_entrada']) ? date('d/m/Y', strtotime($servico['data_entrada'])) : 'N/A';
                        $data_saida_exibicao = !empty($servico['data_saida']) ? date('d/m/Y', strtotime($servico['data_saida'])) : 'N/A';
                        $cpf_exibicao = !empty($servico['cpf_cliente']) ? $servico['cpf_cliente'] : 'N/A';
                        $tipo_servico_exibicao = !empty($servico['tipo_servico']) ? $servico['tipo_servico'] : 'N/A';
                        $valor_exibicao = is_numeric($servico['valor']) ? number_format((float)$servico['valor'], 2, ',', '.') : '0,00';
                    ?>
                    <tr>
                        <th scope="row"><?php echo htmlspecialchars($servico['id']); ?></th>
                        <td><?php echo htmlspecialchars($servico['nome_cliente']); ?></td>
                        <td><?php echo htmlspecialchars($cpf_exibicao); ?></td>
                        <td><?php echo htmlspecialchars($placa_exibicao); ?></td>
                        <td><?php echo htmlspecialchars($data_entrada_exibicao); ?></td>
                        <td><?php echo htmlspecialchars($data_saida_exibicao); ?></td>
                        <td>
                            <span class="badge
                                <?php
                                switch ($tipo_servico_exibicao) {
                                    case 'Manutenção': echo 'bg-warning text-dark'; break;
                                    case 'Instalação': echo 'bg-info'; break;
                                    case 'Orçamento': echo 'bg-secondary'; break;
                                    case 'Visita Técnica': echo 'bg-success'; break;
                                    default: echo 'bg-primary'; break;
                                }
                                ?>
                            ">
                                <?php echo htmlspecialchars($tipo_servico_exibicao); ?>
                            </span>
                        </td>
                        <td>R$ <?php echo $valor_exibicao; ?></td>
                        <td>
                            <?php if (($servico['pago'] ?? '') == 'Sim'): ?>
                                <span class="badge bg-success">Pago</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Não Pago</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="visualizar_servico.php?id=<?php echo htmlspecialchars($servico['id']); ?>" class="btn btn-sm btn-outline-primary me-1" title="Visualizar/Imprimir">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="editar_servico.php?id=<?php echo htmlspecialchars($servico['id']); ?>" class="btn btn-sm btn-outline-info me-1" title="Editar">
                                <i class="bi bi-pencil-square"></i>
                            </a>
                            <a href="excluir_servicos.php?id=<?php echo htmlspecialchars($servico['id']); ?>" class="btn btn-sm btn-outline-danger" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir este serviço?');">
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="10" class="text-center">Nenhum serviço encontrado.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include 'includes/footer.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const placaInput = document.getElementById('filtro_placa');
        if (placaInput) {
            placaInput.addEventListener('input', function() {
                this.value = this.value.toUpperCase();
            });
        }
    });
</script>
