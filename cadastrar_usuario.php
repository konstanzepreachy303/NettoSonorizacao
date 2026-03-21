<?php
// Ativar exibição de erros para depuração (REMOVER EM PRODUÇÃO FINAL)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Inicia a sessão para acesso às variáveis de sessão e controle de login
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Redirecionar para o login se não for um administrador logado
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

require_once 'includes/conexao.php';
include 'includes/header.php';

$mensagem_sucesso = "";
$mensagem_erro = "";

// Variáveis para preencher o formulário em caso de erro
$nome_old = '';
$email_old = '';

// --- Lógica para processar o formulário de cadastro (POST) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Coleta e sanitiza os dados do formulário
    $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $senha = $_POST['senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';

    // Salva os valores para repopular o formulário em caso de erro
    $nome_old = $nome;
    $email_old = $email;

    // Validação básica dos campos
    if (empty($nome) || empty($email) || empty($senha) || empty($confirmar_senha)) {
        $mensagem_erro = "Todos os campos são obrigatórios.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensagem_erro = "O e-mail fornecido não é válido.";
    } elseif (strlen($senha) < 6) {
        $mensagem_erro = "A senha deve ter no mínimo 6 caracteres.";
    } elseif ($senha !== $confirmar_senha) {
        $mensagem_erro = "As senhas não coincidem.";
    } else {
        // Verifica se o e-mail já está em uso
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM administradores WHERE email = :email");
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();
            if ($stmt->fetchColumn() > 0) {
                $mensagem_erro = "Este e-mail já está cadastrado. Por favor, use outro.";
            } else {
                // Hash da senha para segurança
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

                // Insere o novo usuário no banco de dados
                $sql = "INSERT INTO administradores (nome, email, senha) VALUES (:nome, :email, :senha)";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':nome', $nome, PDO::PARAM_STR);
                $stmt->bindParam(':email', $email, PDO::PARAM_STR);
                $stmt->bindParam(':senha', $senha_hash, PDO::PARAM_STR);

                if ($stmt->execute()) {
                    $_SESSION['usuario_sucesso'] = "Usuário '$nome' cadastrado com sucesso!";
                    header("Location: gerenciar_usuarios.php");
                    exit();
                } else {
                    $mensagem_erro = "Erro ao cadastrar usuário. Tente novamente.";
                }
            }
        } catch (PDOException $e) {
            $mensagem_erro = "Erro no banco de dados: " . $e->getMessage();
            error_log("Erro no PDO ao cadastrar usuário: " . $e->getMessage());
        }
    }
}

// Pega mensagens de sessão caso o redirecionamento tenha ocorrido
if (isset($_SESSION['usuario_sucesso'])) {
    $mensagem_sucesso = $_SESSION['usuario_sucesso'];
    unset($_SESSION['usuario_sucesso']);
}
if (isset($_SESSION['usuario_erro'])) {
    $mensagem_erro = $_SESSION['usuario_erro'];
    unset($_SESSION['usuario_erro']);
}
?>

<h1 class="mb-4 text-center text-white">
    <i class="bi bi-person-plus-fill me-2 text-neon-blue"></i>Cadastrar Novo Usuário
</h1>

<?php if (!empty($mensagem_sucesso)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($mensagem_sucesso); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php if (!empty($mensagem_erro)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($mensagem_erro); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card shadow-lg p-4 mb-5 rounded">
    <div class="card-body bg-dark text-white">
        <form action="cadastrar_usuario.php" method="POST">
            <div class="mb-3">
                <label for="nome" class="form-label">Nome Completo</label>
                <input type="text" class="form-control" id="nome" name="nome" value="<?php echo htmlspecialchars($nome_old); ?>" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">E-mail</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email_old); ?>" required>
            </div>
            <div class="mb-3">
                <label for="senha" class="form-label">Senha</label>
                <input type="password" class="form-control" id="senha" name="senha" required>
                <div class="form-text text-light">A senha deve ter no mínimo 6 caracteres.</div>
            </div>
            <div class="mb-3">
                <label for="confirmar_senha" class="form-label">Confirmar Senha</label>
                <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha" required>
            </div>
            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                <a href="gerenciar_usuarios.php" class="btn btn-secondary me-md-2">
                    <i class="bi bi-arrow-left-circle me-1"></i>Voltar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-person-plus me-1"></i>Cadastrar
                </button>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
