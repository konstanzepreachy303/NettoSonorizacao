<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Inclui o arquivo de conexão, que deve definir a variável $pdo
require_once 'includes/conexao.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize e valide os dados recebidos do formulário
    $cliente_id = filter_input(INPUT_POST, 'cliente_id', FILTER_SANITIZE_NUMBER_INT);
    $placa = htmlspecialchars(trim($_POST['placa']));
    $modelo = htmlspecialchars(trim($_POST['modelo']));
    $marca = htmlspecialchars(trim($_POST['marca']));
    $ano_fab = filter_input(INPUT_POST, 'ano_fab', FILTER_SANITIZE_NUMBER_INT);
    $ano_mod = filter_input(INPUT_POST, 'ano_mod', FILTER_SANITIZE_NUMBER_INT);
    $cor = htmlspecialchars(trim($_POST['cor']));
    $data_entrada = htmlspecialchars(trim($_POST['data_entrada']));
    $data_saida = htmlspecialchars(trim($_POST['data_saida']));
    $descricao_problema = htmlspecialchars(trim($_POST['descricao_problema']));
    $servico_executado = htmlspecialchars(trim($_POST['servico_executado']));
    
    // Converte o valor para formato numérico adequado (ponto como separador decimal)
    $valor_str = str_replace(['.', ','], ['', '.'], $_POST['valor']);
    $valor = filter_var($valor_str, FILTER_VALIDATE_FLOAT); 

    $garantia = filter_input(INPUT_POST, 'garantia', FILTER_SANITIZE_NUMBER_INT);
    $data_fim_garantia = htmlspecialchars(trim($_POST['data_fim_garantia']));
    $pago = htmlspecialchars(trim($_POST['pago']));
    $parcelado = htmlspecialchars(trim($_POST['parcelado']));
    $num_parcelas = ($parcelado == 'Sim') ? filter_input(INPUT_POST, 'num_parcelas', FILTER_SANITIZE_NUMBER_INT) : null;

    // Validação básica
    if (empty($cliente_id) || empty($placa) || empty($modelo) || empty($marca) || empty($data_entrada) || empty($descricao_problema) || empty($servico_executado) || !is_numeric($valor) || $valor < 0 || !is_numeric($garantia) || $garantia < 0 || empty($pago) || empty($parcelado)) {
        $_SESSION['manutencao_erro'] = "Por favor, preencha todos os campos obrigatórios e verifique os valores numéricos.";
        header("Location: cadastrar_servico.php"); // Redireciona de volta ao formulário
        exit();
    }

    try {
        // Prepara a consulta SQL para inserção
        $stmt = $pdo->prepare("INSERT INTO servicos (
                                    cliente_id, placa, modelo, marca, ano_fab, ano_mod, cor,
                                    data_entrada, data_saida, descricao_problema, servico_executado,
                                    valor, garantia, data_fim_garantia, pago, parcelado, num_parcelas,
                                    created_at, updated_at
                                ) VALUES (
                                    :cliente_id, :placa, :modelo, :marca, :ano_fab, :ano_mod, :cor,
                                    :data_entrada, :data_saida, :descricao_problema, :servico_executado,
                                    :valor, :garantia, :data_fim_garantia, :pago, :parcelado, :num_parcelas,
                                    NOW(), NOW()
                                )");

        // Vincula os parâmetros
        $stmt->bindParam(':cliente_id', $cliente_id, PDO::PARAM_INT);
        $stmt->bindParam(':placa', $placa, PDO::PARAM_STR);
        $stmt->bindParam(':modelo', $modelo, PDO::PARAM_STR);
        $stmt->bindParam(':marca', $marca, PDO::PARAM_STR);
        $stmt->bindParam(':ano_fab', $ano_fab, PDO::PARAM_INT);
        $stmt->bindParam(':ano_mod', $ano_mod, PDO::PARAM_INT);
        $stmt->bindParam(':cor', $cor, PDO::PARAM_STR);
        $stmt->bindParam(':data_entrada', $data_entrada, PDO::PARAM_STR);
        $stmt->bindParam(':data_saida', $data_saida, PDO::PARAM_STR);
        $stmt->bindParam(':descricao_problema', $descricao_problema, PDO::PARAM_STR);
        $stmt->bindParam(':servico_executado', $servico_executado, PDO::PARAM_STR);
        $stmt->bindParam(':valor', $valor); // PDO automaticamente lida com float/decimal
        $stmt->bindParam(':garantia', $garantia, PDO::PARAM_INT);
        $stmt->bindParam(':data_fim_garantia', $data_fim_garantia, PDO::PARAM_STR);
        $stmt->bindParam(':pago', $pago, PDO::PARAM_STR);
        $stmt->bindParam(':parcelado', $parcelado, PDO::PARAM_STR);
        $stmt->bindParam(':num_parcelas', $num_parcelas, PDO::PARAM_INT);


        if ($stmt->execute()) {
            $_SESSION['manutencao_sucesso'] = "Manutenção registrada com sucesso!";
        } else {
            // Em caso de erro, você pode pegar informações mais detalhadas do PDO
            $errorInfo = $stmt->errorInfo();
            $_SESSION['manutencao_erro'] = "Erro ao registrar manutenção: " . ($errorInfo[2] ?? "Erro desconhecido.");
            error_log("Erro PDO na inserção de manutenção: " . ($errorInfo[2] ?? "Erro desconhecido."));
        }

    } catch (PDOException $e) {
        $_SESSION['manutencao_erro'] = "Erro no banco de dados: " . $e->getMessage();
        error_log("Erro PDO geral ao processar manutenção: " . $e->getMessage());
    }
    
    // Redireciona para a página de listagem de serviços após o processamento
    header("Location: listar_servicos.php"); // CORRIGIDO PARA listar_servicos.php
    exit();
} else {
    // Se o acesso for direto sem POST, redireciona para o formulário de cadastro
    header("Location: cadastrar_servico.php");
    exit();
}
?>