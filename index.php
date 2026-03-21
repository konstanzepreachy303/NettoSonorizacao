<?php
// Ativar exibição de erros para depuração (REMOVER EM PRODUÇÃO FINAL)
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Se o usuário já estiver logado, redireciona para o painel administrativo (dashboard)
if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php"); // <<-- ALTERADO AQUI PARA O DASHBOARD
    exit();
}

// Inclui o arquivo de conexão com o banco de dados (que deve fornecer $pdo)
require_once 'includes/conexao.php'; // Este arquivo deve retornar a conexão PDO em $pdo

$erro_login = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';

    // Validação básica de campos
    if (empty($email) || empty($senha)) {
        $erro_login = "Por favor, preencha todos os campos.";
    } else {
        try {
            // Prepara a query usando PDO
            // Usamos $pdo aqui, não $conn
            $stmt = $pdo->prepare("SELECT id, nome, senha FROM administradores WHERE email = :email");
            // Vincula o parâmetro :email
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            // Executa a consulta
            $stmt->execute();

            // Pega o resultado
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verifica se o usuário existe e a senha está correta
            if ($admin && password_verify($senha, $admin['senha'])) {
                // Senha correta, cria a sessão
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_nome'] = $admin['nome'];
                // Redireciona para a página principal do painel (agora dashboard.php)
                header("Location: dashboard.php");
                exit();
            } else {
                // Credenciais inválidas
                $erro_login = "Email ou senha inválidos.";
            }
        } catch (PDOException $e) {
            // Erro na conexão ou consulta ao banco de dados
            $erro_login = "Erro ao tentar fazer login. Por favor, tente novamente mais tarde.";
            // Logar o erro para depuração (não exibir ao usuário em produção)
            error_log("Erro de login: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Netto Sonorização</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            background-color: #212529; /* Fundo escuro */
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .login-card {
            background-color: #343a40; /* Card um pouco mais claro que o fundo */
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.5);
            width: 100%;
            max-width: 400px;
            padding: 30px;
            text-align: center;
        }
        .login-card .form-control {
            background-color: #495057; /* Campo de input mais escuro */
            border: 1px solid #6c757d;
            color: #f8f9fa; /* Texto branco no input */
        }
        .login-card .form-control::placeholder {
            color: #adb5bd; /* Placeholder cinza claro */
        }
        .login-card .form-label {
            color: #f8f9fa; /* Labels brancas */
        }
        .login-card .btn-primary {
            background-color: #dc3545; /* Vermelho Netto Sonorização */
            border-color: #dc3545;
        }
        .login-card .btn-primary:hover {
            background-color: #c82333;
            border-color: #bd2130;
        }
        .text-neon-blue {
            color: #00bcd4; /* Azul vibrante para títulos/destaque */
        }
    </style>
</head>
<body>
    <div class="login-card">
        <h2 class="text-neon-blue mb-4">
            <i class="bi bi-person-circle me-2"></i>Login Administrativo
        </h2>
        <p class="text-light mb-4">Acesse o painel administrativo.</p>

        <?php if ($erro_login): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($erro_login); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form action="index.php" method="POST">
            <div class="mb-3">
                <label for="email" class="form-label text-light d-block text-start">Email:</label>
                <input type="email" class="form-control" id="email" name="email" placeholder="Seu email" required autofocus>
            </div>
            <div class="mb-4">
                <label for="senha" class="form-label text-light d-block text-start">Senha:</label>
                <input type="password" class="form-control" id="senha" name="senha" placeholder="Sua senha" required>
            </div>
            <div class="d-grid">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Entrar
                </button>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9Gkcq+pSyDoTfPR7z5l/NpHY2dhB" crossorigin="anonymous"></script>
</body>
</html>