<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Netto Sonorização</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="d-flex" id="wrapper">
        <div class="bg-dark-secondary border-right" id="sidebar-wrapper">
            <div class="sidebar-heading">Netto Sonorização</div>
            <div class="list-group list-group-flush">
                <a href="dashboard.php" class="list-group-item list-group-item-action menu-item-dashboard">
                    <i class="bi bi-speedometer2 me-2"></i><span>Dashboard</span>
                </a>
                
                <div class="menu-divider"></div>
                <div class="menu-section-title">Clientes</div>
                
                <a href="cadastrar_cliente.php" class="list-group-item list-group-item-action menu-item">
                    <i class="bi bi-person-plus-fill me-2"></i><span>Cadastrar Cliente</span>
                </a>
                <a href="listar_clientes.php" class="list-group-item list-group-item-action menu-item">
                    <i class="bi bi-people-fill me-2"></i><span>Listar Clientes</span>
                </a>
                
                <div class="menu-divider"></div>
                <div class="menu-section-title">Serviços</div>
                
                <a href="cadastrar_servico.php" class="list-group-item list-group-item-action menu-item">
                    <i class="bi bi-tools me-2"></i><span>Registrar Serviço</span>
                </a>
                <a href="listar_servicos.php" class="list-group-item list-group-item-action menu-item">
                    <i class="bi bi-card-checklist me-2"></i><span>Listar Serviços</span>
                </a>
                
                <?php if (isset($_SESSION['admin_nome'])): ?>
                    <div class="menu-divider"></div>
                    <div class="menu-section-title">Administração</div>
                    
                    <a href="gerenciar_usuarios.php" class="list-group-item list-group-item-action menu-item">
                        <i class="bi bi-person-gear me-2"></i><span>Gerenciar Usuários</span>
                    </a>
                    <a href="configuracoes.php" class="list-group-item list-group-item-action menu-item">
                        <i class="bi bi-gear-fill me-2"></i><span>Configurações</span>
                    </a>
                    <a href="painel_admin.php" class="list-group-item list-group-item-action menu-item">
                        <i class="bi bi-shield-lock-fill me-2"></i><span>Painel Admin</span>
                    </a>
                <?php endif; ?>
                
                <div class="menu-divider"></div>
                <a href="logout.php" class="list-group-item list-group-item-action menu-item-logout">
                    <i class="bi bi-box-arrow-right me-2"></i><span>Sair</span>
                </a>
            </div>
        </div>
        <div id="page-content-wrapper" class="d-flex flex-column flex-grow-1">
            <nav class="navbar navbar-expand-lg navbar-dark border-bottom">
                <div class="container-fluid">
                    <button class="btn btn-primary" id="menu-toggle" style="background-color: #dc3545; border-color: #dc3545;">
                        <i class="bi bi-list"></i>
                    </button>
                    <div class="navbar-brand text-light ms-auto me-3">
                        <?php if (isset($_SESSION['admin_nome'])): ?>
                            Bem-vindo, <?php echo htmlspecialchars($_SESSION['admin_nome']); ?>!
                        <?php endif; ?>
                    </div>
                </div>
            </nav>

            <main id="main-content" class="container-fluid py-4 flex-grow-1">