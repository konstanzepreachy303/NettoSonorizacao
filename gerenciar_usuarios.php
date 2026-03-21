<?php
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

// Lógica para processar exclusão de usuário
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $user_id_to_delete = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

    // Evitar que o próprio admin logado se exclua
    if ($user_id_to_delete == $_SESSION['admin_id']) {
        $_SESSION['usuario_erro'] = "Você não pode excluir seu próprio usuário logado.";
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM administradores WHERE id = :id");
            $stmt->bindParam(':id', $user_id_to_delete, PDO::PARAM_INT);
            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    $_SESSION['usuario_sucesso'] = "Usuário excluído com sucesso!";
                } else {
                    $_SESSION['usuario_erro'] = "Usuário não encontrado ou já excluído.";
                }
            } else {
                $_SESSION['usuario_erro'] = "Erro ao excluir usuário.";
            }
        } catch (PDOException $e) {
            $_SESSION['usuario_erro'] = "Erro no banco de dados ao excluir: " . $e->getMessage();
            error_log("Erro no PDO ao excluir usuário: " . $e->getMessage());
        }
    }
    header("Location: gerenciar_usuarios.php");
    exit();
}

// Lógica para buscar todos os usuários (administradores)
$usuarios = [];
try {
    // CORRIGIDO: Alterado 'nome_usuario' para 'nome'
    $stmt = $pdo->query("SELECT id, nome, email FROM administradores ORDER BY nome ASC");
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $mensagem_erro = "Erro ao carregar usuários: " . $e->getMessage();
    error_log("Erro no PDO ao listar usuários: " . $e->getMessage());
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
    <i class="bi bi-people-fill me-2 text-neon-blue"></i>Gerenciar Usuários
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
        <div class="d-flex justify-content-end mb-4">
            <a href="cadastrar_usuario.php" class="btn btn-primary">
                <i class="bi bi-person-plus-fill me-2"></i>Cadastrar Novo Usuário
            </a>
        </div>

        <?php if (empty($usuarios)): ?>
            <div class="alert alert-info text-center" role="alert">
                Não há usuários cadastrados no momento.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-dark table-striped table-hover align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome de Usuário</th> <th>E-mail</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $usuario): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($usuario['id']); ?></td>
                                <td><?php echo htmlspecialchars($usuario['nome']); ?></td>
                                <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                <td>
                                    <a href="editar_usuario.php?id=<?php echo htmlspecialchars($usuario['id']); ?>" class="btn btn-sm btn-info text-white me-1" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="#" class="btn btn-sm btn-danger excluir-btn" data-id="<?php echo htmlspecialchars($usuario['id']); ?>" title="Excluir">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content bg-dark text-white"> <div class="modal-header">
        <h5 class="modal-title" id="confirmDeleteModalLabel">Confirmação de Exclusão</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button> </div>
      <div class="modal-body">
        Tem certeza que deseja excluir este usuário? Esta ação não pode ser desfeita.
        <br><strong class="text-danger">Atenção: Você não pode excluir seu próprio usuário logado.</strong>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-danger" id="confirmDeleteButton">Excluir</button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var userIdToDelete = null;

    document.querySelectorAll('.excluir-btn').forEach(button => {
        button.addEventListener('click', function(event) {
            event.preventDefault();
            userIdToDelete = this.dataset.id;
            var confirmModal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
            confirmModal.show();
        });
    });

    document.getElementById('confirmDeleteButton').addEventListener('click', function() {
        if (userIdToDelete) {
            window.location.href = 'gerenciar_usuarios.php?action=delete&id=' + userIdToDelete;
        }
    });
});
</script>