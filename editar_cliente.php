<?php

// --- FUNÇÕES DE VALIDAÇÃO CPF/CNPJ EM PHP (SERVER-SIDE) ---
function validarCPF_PHP($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', (string) $cpf);
    if (strlen($cpf) != 11) return false;
    if (preg_match('/(\d)\1{10}/', $cpf)) return false;
    for ($i = 0, $j = 10, $soma = 0; $i < 9; $i++, $j--)
        $soma += $cpf[$i] * $j;
    $resto = $soma % 11;
    if ($cpf[9] != ($resto < 2 ? 0 : 11 - $resto)) return false;
    for ($i = 0, $j = 11, $soma = 0; $i < 10; $i++, $j--)
        $soma += $cpf[$i] * $j;
    $resto = $soma % 11;
    return $cpf[10] == ($resto < 2 ? 0 : 11 - $resto);
}

function validarCNPJ_PHP($cnpj) {
    $cnpj = preg_replace('/[^0-9]/', '', (string) $cnpj);
    if (strlen($cnpj) != 14) return false;
    if (preg_match('/(\d)\1{13}/', $cnpj)) return false;
    $soma = 0;
    $peso = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
    for ($i = 0; $i < 12; $i++) {
        $soma += $cnpj[$i] * $peso[$i];
    }
    $resto = $soma % 11;
    $dv1 = ($resto < 2) ? 0 : 11 - $resto;
    if ($cnpj[12] != $dv1) return false;
    $soma = 0;
    $peso = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
    for ($i = 0; $i < 13; $i++) {
        $soma += $cnpj[$i] * $peso[$i];
    }
    $resto = $soma % 11;
    $dv2 = ($resto < 2) ? 0 : 11 - $resto;
    return $cnpj[13] == $dv2;
}

// --- FIM DAS FUNÇÕES DE VALIDAÇÃO CPF/CNPJ EM PHP ---

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

require_once 'includes/conexao.php';

$mensagem_sucesso = "";
$mensagem_erro = "";
$cliente_dados = [];

// Lógica para carregar os dados do cliente apenas ao abrir a tela
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    if (isset($_GET['id']) && !empty($_GET['id'])) {
        $cliente_id = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);

        if ($cliente_id) {
            try {
                $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = :id");
                $stmt->bindParam(':id', $cliente_id, PDO::PARAM_INT);
                $stmt->execute();
                $cliente_dados = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$cliente_dados) {
                    $mensagem_erro = "Cliente não encontrado.";
                }
            } catch (PDOException $e) {
                $mensagem_erro = "Erro ao carregar dados do cliente: " . $e->getMessage();
                error_log("Erro no PDO ao carregar cliente para edição: " . $e->getMessage());
            }
        } else {
            $mensagem_erro = "ID de cliente inválido.";
        }
    } else {
        $mensagem_erro = "Nenhum ID de cliente fornecido para edição.";
    }
}

// Lógica para processar a atualização
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $cliente_id = filter_var($_POST['cliente_id'], FILTER_SANITIZE_NUMBER_INT);
    $nome = htmlspecialchars(trim($_POST['nome']));
    $email_temp = trim($_POST['email']);
    $email = empty($email_temp) ? null : filter_var($email_temp, FILTER_SANITIZE_EMAIL);
    $cpf_cnpj = htmlspecialchars(trim($_POST['cpf_cnpj']));
    $telefone = htmlspecialchars(trim($_POST['telefone']));
    $cep = htmlspecialchars(trim($_POST['cep']));
    $logradouro = htmlspecialchars(trim($_POST['logradouro']));
    $numero = htmlspecialchars(trim($_POST['numero']));
    $complemento = htmlspecialchars(trim($_POST['complemento']));
    $bairro = htmlspecialchars(trim($_POST['bairro']));
    $cidade = htmlspecialchars(trim($_POST['cidade']));
    $estado = htmlspecialchars(trim($_POST['estado']));

    $cpf_cnpj_limpo = preg_replace('/\D/', '', $cpf_cnpj);

    // Validação de campos obrigatórios
    if (empty($nome)) {
        $mensagem_erro = "Erro: Nome é obrigatório.";
    } elseif ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensagem_erro = "Erro: Formato de e-mail inválido.";
    } elseif (!empty($cpf_cnpj)) {
        if (strlen($cpf_cnpj_limpo) !== 11 && strlen($cpf_cnpj_limpo) !== 14) {
            $mensagem_erro = "Erro: CPF/CNPJ deve conter 11 ou 14 dígitos.";
        } elseif (strlen($cpf_cnpj_limpo) === 11 && !validarCPF_PHP($cpf_cnpj_limpo)) {
            $mensagem_erro = "Erro: CPF inválido. Por favor, verifique os números.";
        } elseif (strlen($cpf_cnpj_limpo) === 14 && !validarCNPJ_PHP($cpf_cnpj_limpo)) {
            $mensagem_erro = "Erro: CNPJ inválido. Por favor, verifique os números.";
        }
    }

    if (empty($mensagem_erro) && !$cliente_id) {
        $mensagem_erro = "Erro: ID do cliente para edição não fornecido ou inválido.";
    } elseif (empty($mensagem_erro)) {
        try {
            $stmt = $pdo->prepare("UPDATE clientes SET nome = :nome, email = :email, cpf_cnpj = :cpf_cnpj, telefone = :telefone, cep = :cep, logradouro = :logradouro, numero = :numero, complemento = :complemento, bairro = :bairro, cidade = :cidade, estado = :estado WHERE id = :id");

            $stmt->bindParam(':nome', $nome);

            if ($email === null) {
                $stmt->bindValue(':email', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindParam(':email', $email);
            }

            if (empty($cpf_cnpj)) {
                $stmt->bindValue(':cpf_cnpj', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindParam(':cpf_cnpj', $cpf_cnpj);
            }

            if (empty($telefone)) {
                $stmt->bindValue(':telefone', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindParam(':telefone', $telefone);
            }

            $stmt->bindValue(':cep', empty($cep) ? null : $cep, PDO::PARAM_STR);
            $stmt->bindValue(':logradouro', empty($logradouro) ? null : $logradouro, PDO::PARAM_STR);
            $stmt->bindValue(':numero', empty($numero) ? null : $numero, PDO::PARAM_STR);
            $stmt->bindValue(':complemento', empty($complemento) ? null : $complemento, PDO::PARAM_STR);
            $stmt->bindValue(':bairro', empty($bairro) ? null : $bairro, PDO::PARAM_STR);
            $stmt->bindValue(':cidade', empty($cidade) ? null : $cidade, PDO::PARAM_STR);
            $stmt->bindValue(':estado', empty($estado) ? null : $estado, PDO::PARAM_STR);
            $stmt->bindParam(':id', $cliente_id, PDO::PARAM_INT);

            if ($stmt->execute()) {
                $_SESSION['cliente_sucesso'] = "Cliente atualizado com sucesso!";
                header("Location: listar_clientes.php");
                exit();
            } else {
                $mensagem_erro = "Erro ao atualizar cliente.";
            }
        } catch (PDOException $e) {
            $mensagem_erro = "Erro no banco de dados: " . $e->getMessage();
            error_log("Erro no PDO ao atualizar cliente: " . $e->getMessage());
        }
    }
}

// Inclui o header APÓS processar o POST
include 'includes/header.php';

$nome = isset($_POST['nome']) ? htmlspecialchars($_POST['nome']) : (isset($cliente_dados['nome']) ? htmlspecialchars($cliente_dados['nome']) : '');
$email = isset($_POST['email']) ? htmlspecialchars($_POST['email']) : (isset($cliente_dados['email']) ? htmlspecialchars($cliente_dados['email']) : '');
$cpf_cnpj = isset($_POST['cpf_cnpj']) ? htmlspecialchars($_POST['cpf_cnpj']) : (isset($cliente_dados['cpf_cnpj']) ? htmlspecialchars($cliente_dados['cpf_cnpj']) : '');
$telefone = isset($_POST['telefone']) ? htmlspecialchars($_POST['telefone']) : (isset($cliente_dados['telefone']) ? htmlspecialchars($cliente_dados['telefone']) : '');
$cep = isset($_POST['cep']) ? htmlspecialchars($_POST['cep']) : (isset($cliente_dados['cep']) ? htmlspecialchars($cliente_dados['cep']) : '');
$logradouro = isset($_POST['logradouro']) ? htmlspecialchars($_POST['logradouro']) : (isset($cliente_dados['logradouro']) ? htmlspecialchars($cliente_dados['logradouro']) : '');
$numero = isset($_POST['numero']) ? htmlspecialchars($_POST['numero']) : (isset($cliente_dados['numero']) ? htmlspecialchars($cliente_dados['numero']) : '');
$complemento = isset($_POST['complemento']) ? htmlspecialchars($_POST['complemento']) : (isset($cliente_dados['complemento']) ? htmlspecialchars($cliente_dados['complemento']) : '');
$bairro = isset($_POST['bairro']) ? htmlspecialchars($_POST['bairro']) : (isset($cliente_dados['bairro']) ? htmlspecialchars($cliente_dados['bairro']) : '');
$cidade = isset($_POST['cidade']) ? htmlspecialchars($_POST['cidade']) : (isset($cliente_dados['cidade']) ? htmlspecialchars($cliente_dados['cidade']) : '');
$estado = isset($_POST['estado']) ? htmlspecialchars($_POST['estado']) : (isset($cliente_dados['estado']) ? htmlspecialchars($cliente_dados['estado']) : '');
$cliente_id_hidden = $cliente_dados['id'] ?? (isset($_POST['cliente_id']) ? $_POST['cliente_id'] : '');
?>

<h1 class="mb-4 text-center text-white">
    <i class="bi bi-pencil-square me-2 text-neon-blue"></i>Editar Cliente
</h1>

<?php if ($mensagem_sucesso): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo $mensagem_sucesso; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php if ($mensagem_erro): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $mensagem_erro; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<p class="text-white text-center mb-4">Edite as informações do cliente. Campos marcados com <span class="text-danger">*</span> são obrigatórios.</p>

<form action="editar_cliente.php?id=<?php echo urlencode($cliente_id_hidden); ?>" method="POST" class="bg-dark p-4 rounded shadow-lg text-white" id="clienteForm">
    <input type="hidden" name="cliente_id" value="<?php echo htmlspecialchars($cliente_id_hidden); ?>">

    <h5 class="mb-3 text-neon-blue"><i class="bi bi-person-fill me-2"></i>Dados Pessoais</h5>
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <label for="nome" class="form-label">Nome Completo:<span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="nome" name="nome" value="<?php echo $nome; ?>" required>
        </div>
        <div class="col-md-6">
            <label for="email" class="form-label">E-mail:</label>
            <input type="email" class="form-control" id="email" name="email" value="<?php echo $email; ?>">
        </div>
        <div class="col-md-6">
            <label for="cpf_cnpj" class="form-label">CPF/CNPJ:</label>
            <input type="text" class="form-control" id="cpf_cnpj" name="cpf_cnpj" value="<?php echo $cpf_cnpj; ?>">
            <div id="cpfCnpjError" class="text-danger mt-1" style="display: none;"></div>
        </div>
        <div class="col-md-6">
            <label for="telefone" class="form-label">Telefone:</label>
            <input type="tel" class="form-control" id="telefone" name="telefone" value="<?php echo $telefone; ?>">
        </div>
    </div>

    <h5 class="mb-3 text-neon-blue"><i class="bi bi-geo-alt-fill me-2"></i>Endereço (Opcional)</h5>
    <div class="row g-3">
        <div class="col-md-3">
            <label for="cep" class="form-label">CEP:</label>
            <input type="text" class="form-control" id="cep" name="cep" value="<?php echo $cep; ?>" placeholder="00000-000">
        </div>

        <div class="col-md-7">
            <label for="logradouro" class="form-label">Logradouro:</label>
            <input type="text" class="form-control" id="logradouro" name="logradouro" value="<?php echo $logradouro; ?>">
        </div>

        <div class="col-md-2">
            <label for="numero" class="form-label">Número:</label>
            <input type="text" class="form-control" id="numero" name="numero" value="<?php echo $numero; ?>">
        </div>

        <div class="col-md-4">
            <label for="complemento" class="form-label">Complemento:</label>
            <input type="text" class="form-control" id="complemento" name="complemento" value="<?php echo $complemento; ?>">
        </div>

        <div class="col-md-3">
            <label for="bairro" class="form-label">Bairro:</label>
            <input type="text" class="form-control" id="bairro" name="bairro" value="<?php echo $bairro; ?>">
        </div>

        <div class="col-md-3">
            <label for="cidade" class="form-label">Cidade:</label>
            <input type="text" class="form-control" id="cidade" name="cidade" value="<?php echo $cidade; ?>">
        </div>

        <div class="col-md-2">
            <label for="estado" class="form-label">Estado:</label>
            <select class="form-select" id="estado" name="estado">
                <option value="">Selecione</option>
                <?php
                $estados = [
                    "AC" => "Acre",
                    "AL" => "Alagoas",
                    "AP" => "Amapá",
                    "AM" => "Amazonas",
                    "BA" => "Bahia",
                    "CE" => "Ceará",
                    "DF" => "Distrito Federal",
                    "ES" => "Espírito Santo",
                    "GO" => "Goiás",
                    "MA" => "Maranhão",
                    "MT" => "Mato Grosso",
                    "MS" => "Mato Grosso do Sul",
                    "MG" => "Minas Gerais",
                    "PA" => "Pará",
                    "PB" => "Paraíba",
                    "PR" => "Paraná",
                    "PE" => "Pernambuco",
                    "PI" => "Piauí",
                    "RJ" => "Rio de Janeiro",
                    "RN" => "Rio Grande do Norte",
                    "RS" => "Rio Grande do Sul",
                    "RO" => "Rondônia",
                    "RR" => "Roraima",
                    "SC" => "Santa Catarina",
                    "SP" => "São Paulo",
                    "SE" => "Sergipe",
                    "TO" => "Tocantins"
                ];

                foreach ($estados as $uf => $nomeEstado) {
                    $selected = ($estado === $uf) ? 'selected' : '';
                    echo "<option value=\"{$uf}\" {$selected}>{$uf} - {$nomeEstado}</option>";
                }
                ?>
            </select>
        </div>
    </div>

    <div class="mt-4 d-flex justify-content-end gap-2 flex-wrap">
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="bi bi-floppy-fill me-2"></i>Salvar Alterações
        </button>
        <a href="listar_clientes.php" class="btn btn-secondary btn-lg">
            <i class="bi bi-x-circle me-2"></i>Cancelar
        </a>
    </div>
</form>

<?php include 'includes/footer.php'; ?>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>

<script>
    function validarCPF_JS(cpf) {
        cpf = cpf.replace(/[^\d]+/g, '');
        if (cpf.length != 11 || /^(\d)\1{10}$/.test(cpf)) return false;
        let soma = 0;
        let resto;
        for (let i = 1; i <= 9; i++) soma += parseInt(cpf.substring(i - 1, i)) * (11 - i);
        resto = (soma * 10) % 11;
        if ((resto == 10) || (resto == 11)) resto = 0;
        if (resto != parseInt(cpf.substring(9, 10))) return false;
        soma = 0;
        for (let i = 1; i <= 10; i++) soma += parseInt(cpf.substring(i - 1, i)) * (12 - i);
        resto = (soma * 10) % 11;
        if ((resto == 10) || (resto == 11)) resto = 0;
        if (resto != parseInt(cpf.substring(10, 11))) return false;
        return true;
    }

    function validarCNPJ_JS(cnpj) {
        cnpj = cnpj.replace(/[^\d]+/g, '');
        if (cnpj.length != 14) return false;
        if (/^(\d)\1{13}$/.test(cnpj)) return false;

        let tamanho = cnpj.length - 2;
        let numeros = cnpj.substring(0, tamanho);
        let digitos = cnpj.substring(tamanho);
        let soma = 0;
        let pos = tamanho - 7;
        for (let i = tamanho; i >= 1; i--) {
            soma += numeros.charAt(tamanho - i) * pos--;
            if (pos < 2) pos = 9;
        }
        let resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
        if (resultado != digitos.charAt(0)) return false;

        tamanho = tamanho + 1;
        numeros = cnpj.substring(0, tamanho);
        soma = 0;
        pos = tamanho - 7;
        for (let i = tamanho; i >= 1; i--) {
            soma += numeros.charAt(tamanho - i) * pos--;
            if (pos < 2) pos = 9;
        }
        resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
        if (resultado != digitos.charAt(1)) return false;
        return true;
    }

    function validarCpfCnpjCampo() {
        const cpfCnpjField = $('#cpf_cnpj');
        const errorDiv = $('#cpfCnpjError');
        const rawValue = cpfCnpjField.val().replace(/[^\d]+/g, '');
        errorDiv.hide().text('');

        if (rawValue.length === 0) {
            return true;
        }

        if (rawValue.length === 11) {
            if (!validarCPF_JS(rawValue)) {
                errorDiv.text('CPF inválido. Por favor, verifique os números.').show();
                cpfCnpjField.focus();
                return false;
            }
        } else if (rawValue.length === 14) {
            if (!validarCNPJ_JS(rawValue)) {
                errorDiv.text('CNPJ inválido. Por favor, verifique os números.').show();
                cpfCnpjField.focus();
                return false;
            }
        } else {
            errorDiv.text('CPF/CNPJ deve ter 11 (CPF) ou 14 (CNPJ) dígitos.').show();
            cpfCnpjField.focus();
            return false;
        }
        return true;
    }

    $(document).ready(function() {
        $('#telefone').mask('(00) 00000-0000');
        $('#cep').mask('00000-000');

        const cpfCnpjField = $('#cpf_cnpj');
        const initialValue = cpfCnpjField.val().replace(/[^\d]+/g, '');
        if (initialValue.length === 11) {
            cpfCnpjField.mask('000.000.000-009');
        } else if (initialValue.length === 14) {
            cpfCnpjField.mask('00.000.000/0000-00');
        }

        cpfCnpjField.on('input', function() {
            let value = this.value.replace(/[^\d]+/g, '');
            if (value.length <= 11) {
                $(this).mask('000.000.000-009');
            } else {
                $(this).mask('00.000.000/0000-00');
            }
        });

        $('#clienteForm').on('submit', function(e) {
            const valor = $('#cpf_cnpj').val().replace(/[^\d]+/g, '');
            if (valor.length > 0 && !validarCpfCnpjCampo()) {
                e.preventDefault();
                $('html, body').animate({
                    scrollTop: $('#cpfCnpjError').offset().top - 100
                }, 500);
                return false;
            }
            return true;
        });

        $('#cpf_cnpj').on('blur', function() {
            const valor = $(this).val().replace(/[^\d]+/g, '');
            if (valor.length > 0) {
                validarCpfCnpjCampo();
            } else {
                $('#cpfCnpjError').hide().text('');
            }
        });
    });
</script>
