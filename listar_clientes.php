

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

require_once 'includes/conexao.php';
include 'includes/header.php'; // Garante que o header esteja incluído

$mensagem = "";
$mensagem_cadastro = "";

// Verifica se há uma mensagem de sucesso do cadastro e a armazena
if (isset($_SESSION['cliente_sucesso'])) {
    $mensagem_cadastro = $_SESSION['cliente_sucesso'];
    unset($_SESSION['cliente_sucesso']); // Limpa a mensagem da sessão para que não apareça novamente
}

// PHP-side delete logic
if (isset($_GET['delete_id']) && !empty($_GET['delete_id'])) {
    $cliente_id = filter_var($_GET['delete_id'], FILTER_SANITIZE_NUMBER_INT);
    if ($cliente_id) {
        try {
            $stmt = $pdo->prepare("DELETE FROM clientes WHERE id = :id");
            $stmt->bindParam(':id', $cliente_id, PDO::PARAM_INT);
            if ($stmt->execute()) {
                $mensagem = "<div class='alert alert-success alert-dismissible fade show' role='alert'>Cliente excluído com sucesso!<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button></div>";
            } else {
                $mensagem = "<div class='alert alert-danger alert-dismissible fade show' role='alert'>Erro ao excluir cliente.<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button></div>";
            }
        } catch (PDOException $e) {
            $mensagem = "<div class='alert alert-danger alert-dismissible fade show' role='alert'>Erro no banco de dados ao excluir: " . $e->getMessage() . "<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button></div>";
            error_log("Erro no PDO ao excluir cliente: " . $e->getMessage());
        }
    }
}

// Lógica de pesquisa
$busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';

// Build SQL query with filters
$sql = "SELECT id, nome, email, cpf_cnpj, telefone, logradouro, bairro, cidade, estado FROM clientes WHERE 1=1";
$params = [];

// Filtro de busca (nome, CPF/CNPJ ou telefone)
if (!empty($busca)) {
    // Remove formatação da busca
    $busca_limpa = preg_replace('/[^0-9a-zA-Z]/', '', $busca);
    
    if (!empty($busca_limpa)) {
        $sql .= " AND (
            nome LIKE :busca_nome 
            OR REPLACE(REPLACE(REPLACE(REPLACE(cpf_cnpj, '.', ''), '-', ''), '/', ''), ' ', '') LIKE :busca_cpf
            OR REPLACE(REPLACE(REPLACE(REPLACE(telefone, '(', ''), ')', ''), '-', ''), ' ', '') LIKE :busca_tel
        )";
        $params[':busca_nome'] = "%" . $busca . "%";
        $params[':busca_cpf'] = "%" . $busca_limpa . "%";
        $params[':busca_tel'] = "%" . $busca_limpa . "%";
    }
}

$sql .= " ORDER BY id DESC";

// Fetch clients
try {
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => &$val) {
        $stmt->bindParam($key, $val);
    }
    $stmt->execute();
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $mensagem = "<div class='alert alert-danger alert-dismissible fade show' role='alert'>Erro ao carregar clientes: " . $e->getMessage() . "<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button></div>";
    error_log("Erro no PDO ao listar clientes: " . $e->getMessage());
    $clientes = [];
}
?>

<h1 class="mb-4 text-center text-white">
    <i class="bi bi-people-fill me-2 text-neon-blue"></i>Listar Clientes
</h1>

<div id="loadingMessage" class="d-none text-center">
    <div class="spinner-border text-success" role="status">
        <span class="visually-hidden">Carregando...</span>
    </div>
    <p class="text-success mt-2">Carregando...</p>
</div>

<?php echo $mensagem; // Display success/error messages ?>

<div class="card bg-dark text-white shadow-lg mb-4">
    <div class="card-body">
        <h5 class="card-title text-neon-blue"><i class="bi bi-search me-2"></i>Pesquisar Cliente</h5>
        <form action="listar_clientes.php" method="GET" class="row g-3 align-items-end">
            <div class="col-md-10">
                <label for="busca" class="form-label">Buscar por Nome, CPF/CNPJ ou Telefone:</label>
                <input type="text" class="form-control" id="busca" name="busca" 
                       value="<?php echo htmlspecialchars($busca); ?>" 
                       placeholder="Digite o nome, CPF/CNPJ ou telefone (com ou sem formatação)">
                <small class="form-text text-muted">
                    Você pode pesquisar usando pontos, traços, barras ou sem formatação.
                </small>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-2"></i>Pesquisar</button>
                <a href="listar_clientes.php" class="btn btn-secondary w-100 mt-2"><i class="bi bi-x-circle me-2"></i>Limpar</a>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-lg p-4 mb-5 rounded">
    <div class="card-body bg-dark text-white">
        <?php if (!empty($clientes)): ?>
        <div class="table-responsive">
            <table class="table table-dark table-striped table-hover align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>CPF/CNPJ</th>
                        <th>Telefone</th>
                        <th>Logradouro</th>
                        <th>Bairro</th>
                        <th>Cidade</th>
                        <th>Estado</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clientes as $cliente): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($cliente['id'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($cliente['nome'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($cliente['email'] ?? ''); ?></td>
                        <td class="cpf-cnpj-mask"><?php echo htmlspecialchars($cliente['cpf_cnpj'] ?? ''); ?></td>
                        <td class="telefone-mask"><?php echo htmlspecialchars($cliente['telefone'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($cliente['logradouro'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($cliente['bairro'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($cliente['cidade'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($cliente['estado'] ?? ''); ?></td>
                        <td>
                            <a href="editar_cliente.php?id=<?php echo htmlspecialchars($cliente['id']); ?>" class="btn btn-sm btn-outline-info me-1" title="Editar">
                                <i class="bi bi-pencil-square"></i>
                            </a>
                            <a href="?delete_id=<?php echo htmlspecialchars($cliente['id']); ?>" class="btn btn-sm btn-outline-danger" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir este cliente?');">
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="alert alert-info text-center" role="alert">
            Nenhum cliente cadastrado.
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const mensagemSucesso = "<?php echo $mensagem_cadastro; ?>";
        const loadingMessageDiv = document.getElementById('loadingMessage');

        if (mensagemSucesso) {
            loadingMessageDiv.classList.remove('d-none'); // Mostra a mensagem de "Carregando..."
            
            setTimeout(() => {
                loadingMessageDiv.innerHTML = `
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        ${mensagemSucesso}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `;
                // Após 3 segundos, a mensagem de sucesso vai desaparecer
                setTimeout(() => {
                    const alert = loadingMessageDiv.querySelector('.alert');
                    if (alert) {
                        alert.classList.add('fade-out');
                        alert.addEventListener('transitionend', () => {
                            loadingMessageDiv.classList.add('d-none');
                        });
                    }
                }, 3000);
            }, 1000); // 1 segundo de delay antes de mostrar a mensagem de sucesso
        }

        // Aplica a máscara para CPF/CNPJ em todas as células com a classe 'cpf-cnpj-mask'
        $('.cpf-cnpj-mask').each(function() {
            var text = $(this).text().trim().replace(/\D/g, '');
            var maskedText = text;

            if (text.length === 11) { // É um CPF
                maskedText = text.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
            } else if (text.length === 14) { // É um CNPJ
                maskedText = text.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5');
            }
            $(this).text(maskedText);
        });

        // Aplica a máscara para telefone em todas as células com a classe 'telefone-mask'
        $('.telefone-mask').each(function() {
            var text = $(this).text().trim().replace(/\D/g, '');
            var maskedText = text;

            // Verifica se é telefone com 9 dígitos (celular) ou 8 (fixo)
            if (text.length === 11) { // Ex: 99999999999
                maskedText = text.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
            } else if (text.length === 10) { // Ex: 9999999999
                maskedText = text.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
            }
            $(this).text(maskedText);
        });
    });
</script>
<style>
/* Adiciona a animação de fade-out */
.fade-out {
    opacity: 0;
    transition: opacity 1s ease-out;
}
</style>
