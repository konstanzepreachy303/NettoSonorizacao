
// --- FUNÇÕES DE VALIDAÇÃO CPF/CNPJ EM PHP (SERVER-SIDE) ---
// Adicionadas aqui para garantir que o PHP também valide os dados
function validarCPF_PHP($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', (string) $cpf);
    if (strlen($cpf) != 11) return false;
    if (preg_match('/(\d)\1{10}/', $cpf)) return false;

    for ($i = 0, $j = 10, $soma = 0; $i < 9; $i++, $j--) {
        $soma += $cpf[$i] * $j;
    }
    $resto = $soma % 11;
    if ($cpf[9] != ($resto < 2 ? 0 : 11 - $resto)) return false;

    for ($i = 0, $j = 11, $soma = 0; $i < 10; $i++, $j--) {
        $soma += $cpf[$i] * $j;
    }
    $resto = $soma % 11;
    return $cpf[10] == ($resto < 2 ? 0 : 11 - $resto);
}

function validarCNPJ_PHP($cnpj) {
    $cnpj = preg_replace('/[^0-9]/', '', (string) $cnpj);
    if (strlen($cnpj) != 14) return false;
    if (preg_match('/(\d)\1{13}/', $cnpj)) return false;

    for ($i = 0, $j = 5, $soma = 0; $i < 12; $i++) {
        $soma += $cnpj[$i] * $j;
        $j = ($j == 2) ? 9 : $j - 1;
    }
    $resto = $soma % 11;
    if ($cnpj[12] != ($resto < 2 ? 0 : 11 - $resto)) return false;

    for ($i = 0, $j = 6, $soma = 0; $i < 13; $i++) {
        $soma += $cnpj[$i] * $j;
        $j = ($j == 2) ? 9 : $j - 1;
    }
    $resto = $soma % 11;
    return $cnpj[13] == ($resto < 2 ? 0 : 11 - $resto);
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

$mensagem = "";
$mensagem_erro = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $email = empty($email) ? null : $email;
    $cpf_cnpj = trim($_POST['cpf_cnpj']);
    $telefone = trim($_POST['telefone']);
    $cep = trim($_POST['cep']);
    $logradouro = trim($_POST['logradouro']);
    $numero = trim($_POST['numero']);
    $complemento = trim($_POST['complemento']);
    $bairro = trim($_POST['bairro']);
    $cidade = trim($_POST['cidade']);
    $estado = trim($_POST['estado']);

    // Se CPF/CNPJ vier vazio, salva como NULL no banco
    $cpf_cnpj = empty($cpf_cnpj) ? null : $cpf_cnpj;
    // Se telefone vier vazio, salva como NULL no banco
    $telefone = empty($telefone) ? null : $telefone;

    // Validação de campos obrigatórios
    if (empty($nome)) {
        $mensagem_erro = "Nome é obrigatório.";
    }

    // Validação de e-mail usando filter_var (opcional - só valida se preenchido)
    if (empty($mensagem_erro) && $email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensagem_erro = "Formato de e-mail inválido.";
    }

    // Validação do CPF/CNPJ apenas se preenchido
    if (empty($mensagem_erro) && $cpf_cnpj !== null) {
        $cpfCnpjNumeros = preg_replace('/[^0-9]/', '', $cpf_cnpj);
        $isCpf = (strlen($cpfCnpjNumeros) == 11);
        $isCnpj = (strlen($cpfCnpjNumeros) == 14);

        if (!($isCpf || $isCnpj)) {
            $mensagem_erro = "CPF ou CNPJ inválido. Digite 11 ou 14 dígitos.";
        } elseif ($isCpf && !validarCPF_PHP($cpf_cnpj)) {
            $mensagem_erro = "CPF inválido.";
        } elseif ($isCnpj && !validarCNPJ_PHP($cpf_cnpj)) {
            $mensagem_erro = "CNPJ inválido.";
        }
    }

    if (empty($mensagem_erro)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO clientes (nome, email, cpf_cnpj, telefone, cep, logradouro, numero, complemento, bairro, cidade, estado) VALUES (:nome, :email, :cpf_cnpj, :telefone, :cep, :logradouro, :numero, :complemento, :bairro, :cidade, :estado)");

            $stmt->bindParam(':nome', $nome);

            if ($email === null) {
                $stmt->bindValue(':email', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindParam(':email', $email);
            }

            if ($cpf_cnpj === null) {
                $stmt->bindValue(':cpf_cnpj', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindParam(':cpf_cnpj', $cpf_cnpj);
            }

            if ($telefone === null) {
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

            if ($stmt->execute()) {
                $_SESSION['cliente_sucesso'] = "Cliente cadastrado com sucesso!";
                header("Location: listar_clientes.php");
                exit();
            } else {
                $mensagem_erro = "Erro ao cadastrar cliente.";
            }
        } catch (PDOException $e) {
            if ($e->getCode() == '23000' && strpos($e->getMessage(), 'cpf_cnpj') !== false) {
                $mensagem_erro = "Erro: Já existe um cliente com este CPF/CNPJ cadastrado.";
            } else {
                $mensagem_erro = "Erro no banco de dados: " . $e->getMessage();
            }
        }
    }
}

include 'includes/header.php';
?>

<h1 class="mb-4 text-center text-white"><i class="bi bi-person-plus-fill me-2 text-neon-blue"></i>Cadastro de Cliente</h1>

<p class="text-white text-center mb-4">Campos marcados com <span class="text-danger">*</span> são obrigatórios.</p>

<?php if ($mensagem_erro): ?>
    <div class="alert alert-danger text-center" role="alert">
        <?php echo htmlspecialchars($mensagem_erro); ?>
    </div>
<?php endif; ?>

<div class="d-flex justify-content-between mb-3">
    <a href="listar_clientes.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left-circle me-2"></i>Voltar para Clientes
    </a>
</div>

<div class="card shadow-lg p-4 mb-5 rounded">
    <div class="card-body bg-dark text-white">
        <form id="clienteForm" action="cadastrar_cliente.php" method="POST">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="nome" class="form-label">Nome Completo <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="nome" name="nome" placeholder="Nome Completo do Cliente" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="email" class="form-label">E-mail</label>
                    <input type="email" class="form-control" id="email" name="email" placeholder="E-mail (opcional)">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="cpf_cnpj" class="form-label">CPF ou CNPJ</label>
                    <input type="text" class="form-control" id="cpf_cnpj" name="cpf_cnpj" placeholder="CPF ou CNPJ">
                    <div id="cpfCnpjError" class="text-danger mt-2" style="display:none;"></div>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="telefone" class="form-label">Telefone</label>
                    <input type="text" class="form-control" id="telefone" name="telefone" placeholder="(99) 99999-9999">
                </div>
            </div>

            <hr class="my-4 border-light">
            <h5 class="text-white mb-3">Endereço</h5>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="cep" class="form-label">CEP</label>
                    <input type="text" class="form-control" id="cep" name="cep" placeholder="00000-000">
                </div>
                <div class="col-md-8 mb-3">
                    <label for="logradouro" class="form-label">Logradouro</label>
                    <input type="text" class="form-control" id="logradouro" name="logradouro" placeholder="Rua, Avenida, etc.">
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="numero" class="form-label">Número</label>
                    <input type="text" class="form-control" id="numero" name="numero" placeholder="Ex: 123">
                </div>
                <div class="col-md-8 mb-3">
                    <label for="complemento" class="form-label">Complemento</label>
                    <input type="text" class="form-control" id="complemento" name="complemento" placeholder="Apartamento, Bloco, etc.">
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="bairro" class="form-label">Bairro</label>
                    <input type="text" class="form-control" id="bairro" name="bairro" placeholder="Bairro">
                </div>
                <div class="col-md-5 mb-3">
                    <label for="cidade" class="form-label">Cidade</label>
                    <input type="text" class="form-control" id="cidade" name="cidade" placeholder="Cidade">
                </div>
                <div class="col-md-3 mb-3">
                    <label for="estado" class="form-label">Estado</label>
                    <select class="form-control" id="estado" name="estado">
                        <option value="">Selecione</option>
                        <option value="AC">AC - Acre</option>
                        <option value="AL">AL - Alagoas</option>
                        <option value="AP">AP - Amapá</option>
                        <option value="AM">AM - Amazonas</option>
                        <option value="BA">BA - Bahia</option>
                        <option value="CE">CE - Ceará</option>
                        <option value="DF">DF - Distrito Federal</option>
                        <option value="ES">ES - Espírito Santo</option>
                        <option value="GO">GO - Goiás</option>
                        <option value="MA">MA - Maranhão</option>
                        <option value="MT">MT - Mato Grosso</option>
                        <option value="MS">MS - Mato Grosso do Sul</option>
                        <option value="MG">MG - Minas Gerais</option>
                        <option value="PA">PA - Pará</option>
                        <option value="PB">PB - Paraíba</option>
                        <option value="PR">PR - Paraná</option>
                        <option value="PE">PE - Pernambuco</option>
                        <option value="PI">PI - Piauí</option>
                        <option value="RJ">RJ - Rio de Janeiro</option>
                        <option value="RN">RN - Rio Grande do Norte</option>
                        <option value="RS">RS - Rio Grande do Sul</option>
                        <option value="RO">RO - Rondônia</option>
                        <option value="RR">RR - Roraima</option>
                        <option value="SC">SC - Santa Catarina</option>
                        <option value="SP">SP - São Paulo</option>
                        <option value="SE">SE - Sergipe</option>
                        <option value="TO">TO - Tocantins</option>
                    </select>
                </div>
            </div>

            <div class="d-grid gap-2 mt-4">
                <button type="submit" class="btn btn-success btn-lg"><i class="bi bi-save me-2"></i>Cadastrar Cliente</button>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
<script>
    $(document).ready(function() {
        $('#telefone').mask('(00) 00000-0000');
        $('#cep').mask('00000-000');

        const cpfCnpjField = $('#cpf_cnpj');

        cpfCnpjField.on('input', function() {
            let value = this.value.replace(/\D/g, '');

            if (value.length <= 11) {
                cpfCnpjField.mask('000.000.000-009');
            } else {
                cpfCnpjField.mask('00.000.000/0000-00');
            }
        });

        $('#cpf_cnpj').on('blur', function() {
            let cpfCnpjValue = $(this).val();
            let cleanedValue = cpfCnpjValue.replace(/\D/g, '');
            const errorDiv = $('#cpfCnpjError');

            if (cleanedValue.length === 0) {
                errorDiv.hide();
                return true;
            }

            if (cleanedValue.length === 11) {
                if (!validarCPF_JS(cleanedValue)) {
                    errorDiv.text('CPF inválido. Por favor, verifique os números.').show();
                    return false;
                }
            } else if (cleanedValue.length === 14) {
                if (!validarCNPJ_JS(cleanedValue)) {
                    errorDiv.text('CNPJ inválido. Por favor, verifique os números.').show();
                    return false;
                }
            } else {
                errorDiv.text('CPF/CNPJ deve ter 11 (CPF) ou 14 (CNPJ) dígitos.').show();
                return false;
            }
            errorDiv.hide();
        });

        function validarCPF_JS(cpf) {
            cpf = cpf.replace(/[^\d]+/g, '');
            if (cpf.length !== 11 || !!cpf.match(/(\d)\1{10}/)) return false;
            let sum = 0;
            let remainder;
            for (let i = 1; i <= 9; i++) sum = sum + parseInt(cpf.substring(i-1, i)) * (11 - i);
            remainder = (sum * 10) % 11;
            if ((remainder === 10) || (remainder === 11)) remainder = 0;
            if (remainder !== parseInt(cpf.substring(9, 10))) return false;
            sum = 0;
            for (let i = 1; i <= 10; i++) sum = sum + parseInt(cpf.substring(i-1, i)) * (12 - i);
            remainder = (sum * 10) % 11;
            if ((remainder === 10) || (remainder === 11)) remainder = 0;
            return remainder === parseInt(cpf.substring(10, 11));
        }

        function validarCNPJ_JS(cnpj) {
            cnpj = cnpj.replace(/[^\d]+/g, '');
            if (cnpj.length !== 14 || !!cnpj.match(/(\d)\1{13}/)) return false;
            let size = cnpj.length - 2;
            let numbers = cnpj.substring(0, size);
            let digits = cnpj.substring(size);
            let sum = 0;
            let pos = size - 7;
            for (let i = size; i >= 1; i--) {
                sum += numbers.charAt(size - i) * pos--;
                if (pos < 2) pos = 9;
            }
            let result = sum % 11 < 2 ? 0 : 11 - sum % 11;
            if (result !== parseInt(digits.charAt(0))) return false;
            size = size + 1;
            numbers = cnpj.substring(0, size);
            sum = 0;
            pos = size - 7;
            for (let i = size; i >= 1; i--) {
                sum += numbers.charAt(size - i) * pos--;
                if (pos < 2) pos = 9;
            }
            result = sum % 11 < 2 ? 0 : 11 - sum % 11;
            return result === parseInt(digits.charAt(1));
        }

        $('#clienteForm').on('submit', function(e) {
            let cpfCnpjValue = $('#cpf_cnpj').val();
            let cleanedValue = cpfCnpjValue.replace(/\D/g, '');
            const errorDiv = $('#cpfCnpjError');

            if (cleanedValue.length === 0) {
                errorDiv.hide();
                return true;
            }

            if (cleanedValue.length === 11) {
                if (!validarCPF_JS(cleanedValue)) {
                    e.preventDefault();
                    errorDiv.text('CPF inválido. Por favor, verifique os números.').show();
                    $('html, body').animate({
                        scrollTop: errorDiv.offset().top - 100
                    }, 500);
                    return false;
                }
            } else if (cleanedValue.length === 14) {
                if (!validarCNPJ_JS(cleanedValue)) {
                    e.preventDefault();
                    errorDiv.text('CNPJ inválido. Por favor, verifique os números.').show();
                    $('html, body').animate({
                        scrollTop: errorDiv.offset().top - 100
                    }, 500);
                    return false;
                }
            } else {
                e.preventDefault();
                errorDiv.text('CPF/CNPJ deve ter 11 (CPF) ou 14 (CNPJ) dígitos.').show();
                $('html, body').animate({
                    scrollTop: errorDiv.offset().top - 100
                }, 500);
                return false;
            }
            errorDiv.hide();
        });
    });
</script>
