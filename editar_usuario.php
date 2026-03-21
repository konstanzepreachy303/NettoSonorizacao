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
$usuario = null;
$usuario_id = null;
$senha_antiga = ''; // Variável para armazenar a senha antiga

// --- Lógica para buscar o usuário a ser editado (GET) ---
if ($_SERVER["REQUEST_METHOD"] == "GET") {
    if (isset($_GET['id']) && !empty($_GET['id'])) {
        $usuario_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
        try {
            $stmt = $pdo->prepare("SELECT id, nome, email FROM administradores WHERE id = :id");
            $stmt->bindParam(':id', $usuario_id, PDO::PARAM_INT);
            $stmt->execute();
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$usuario) {
                $_SESSION['usuario_erro'] = "Usuário não encontrado.";
                header("Location: gerenciar_usuarios.php");
                exit();
            }
        } catch (PDOException $e) {
            $_SESSION['usuario_erro'] = "Erro ao buscar usuário: " . $e->getMessage();
            error_log("Erro no PDO ao buscar usuário para edição: " . $e->getMessage());
            header("Location: gerenciar_usuarios.php");
            exit();
        }
    } else {
        $_SESSION['usuario_erro'] = "ID do usuário não especificado.";
        header("Location: gerenciar_usuarios.php");
        exit();
    }
}

// --- Lógica para processar a edição do usuário (POST) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario_id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
    $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $nova_senha = $_POST['nova_senha']; // Não sanitizar a senha antes do hash
    $confirmar_senha = $_POST['confirmar_senha'];

    // Buscar a senha antiga para manter se a nova não for preenchida
    try {
        $stmt_senha = $pdo->prepare("SELECT senha FROM administradores WHERE id = :id");
        $stmt_senha->bindParam(':id', $usuario_id, PDO::PARAM_INT);
        $stmt_senha->execute();
        $senha_antiga = $stmt_senha->fetchColumn();
    } catch (PDOException $e) {
        $mensagem_erro = "Erro ao buscar a senha antiga: " . $e->getMessage();
        error_log("Erro no PDO ao buscar a senha antiga: " . $e->getMessage());
    }

    if (empty($nome) || empty($email)) {
        $mensagem_erro = "Nome e e-mail são campos obrigatórios.";
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensagem_erro = "Formato de e-mail inválido.";
    } else if (!empty($nova_senha) && $nova_senha !== $confirmar_senha) {
        $mensagem_erro = "A nova senha e a confirmação de senha não coincidem.";
    } else {
        try {
            $sql = "UPDATE administradores SET nome = :nome, email = :email";
            $params = [
                ':nome' => $nome,
                ':email' => $email,
                ':id' => $usuario_id
            ];

            // Se uma nova senha for fornecida, adicione-a à query
            if (!empty($nova_senha)) {
                $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
                $sql .= ", senha = :senha";
                $params[':senha'] = $senha_hash;
            } else {
                $params[':senha'] = $senha_antiga;
            }

            $sql .= " WHERE id = :id";
            $stmt = $pdo->prepare($sql);

            if ($stmt->execute($params)) {
                $_SESSION['usuario_sucesso'] = "Usuário atualizado com sucesso!";
                header("Location: gerenciar_usuarios.php");
                exit();
            } else {
                $mensagem_erro = "Erro ao atualizar usuário.";
            }
        } catch (PDOException $e) {
            $mensagem_erro = "Erro no banco de dados ao atualizar: " . $e->getMessage();
            error_log("Erro no PDO ao atualizar usuário: " . $e->getMessage());
        }
    }
    // Para recarregar os dados do formulário em caso de erro
    $usuario = [
        'id' => $usuario_id,
        'nome' => $nome,
        'email' => $email
    ];
}

// Pega mensagens de sessão
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
    <i class="bi bi-pencil-square me-2 text-neon-blue"></i>Editar Usuário
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
        <form action="editar_usuario.php" method="POST">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($usuario['id'] ?? ''); ?>">

            <div class="mb-3">
                <label for="nome" class="form-label">Nome de Usuário</label>
                <input type="text" class="form-control" id="nome" name="nome" value="<?php echo htmlspecialchars($usuario['nome'] ?? ''); ?>" required>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">E-mail</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($usuario['email'] ?? ''); ?>" required>
            </div>

            <hr class="my-4">
            
            <p class="text-white-50">Deixe os campos de senha em branco para não alterar a senha atual.</p>

            <div class="mb-3">
                <label for="nova_senha" class="form-label">Nova Senha</label>
                <input type="password" class="form-control" id="nova_senha" name="nova_senha">
            </div>

            <div class="mb-3">
                <label for="confirmar_senha" class="form-label">Confirmar Nova Senha</label>
                <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha">
            </div>

            <div class="d-flex justify-content-between mt-4">
                <a href="gerenciar_usuarios.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left-circle me-2"></i>Voltar
                </a>
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-save me-2"></i>Salvar Alterações
                </button>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
