<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

session_start();

// Verificar se está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Verificar se tem permissão para cadastrar usuários
if (!temPermissao('cadastrar_usuarios')) {
    header('Location: index.php');
    exit;
}

// Definir página atual para o menu
$current_page = 'cadastro.php';

$mensagem = '';
$tipo_mensagem = '';

// Buscar níveis de acesso
try {
    $stmt = $pdo->query("SELECT * FROM niveis_acesso ORDER BY nome");
    $niveis_acesso = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $mensagem = "Erro ao buscar níveis de acesso: " . $e->getMessage();
    $tipo_mensagem = "danger";
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = $_POST['nome'] ?? '';
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    $telefone = $_POST['telefone'] ?? '';
    $nivel_acesso_id = $_POST['nivel_acesso_id'] ?? '';
    
    try {
        // Verificar se o email já existe
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $mensagem = "Este email já está cadastrado.";
            $tipo_mensagem = "danger";
        } else {
            if ($senha === $confirmar_senha && strlen($senha) >= 6) {
                // Inserir novo usuário
                $stmt = $pdo->prepare("
                    INSERT INTO usuarios (nome, email, senha, telefone, nivel_acesso_id, status) 
                    VALUES (?, ?, ?, ?, ?, 'ativo')
                ");
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                $stmt->execute([$nome, $email, $senha_hash, $telefone, $nivel_acesso_id]);
                
                $mensagem = "Usuário cadastrado com sucesso!";
                $tipo_mensagem = "success";
            } else {
                $mensagem = "As senhas não conferem ou são muito curtas.";
                $tipo_mensagem = "danger";
            }
        }
    } catch(PDOException $e) {
        $mensagem = "Erro ao cadastrar usuário: " . $e->getMessage();
        $tipo_mensagem = "danger";
    }
}

require_once 'includes/header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card mt-5">
                <div class="card-body">
                    <h2 class="text-center mb-4">Cadastrar Novo Usuário</h2>

                    <?php if ($mensagem): ?>
                        <div class="alert alert-<?php echo $tipo_mensagem; ?>">
                            <?php echo $mensagem; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome Completo</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="nome" 
                                   name="nome" 
                                   required 
                                   value="<?php echo $_POST['nome'] ?? ''; ?>">
                            <div class="invalid-feedback">
                                Por favor, insira o nome completo.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" 
                                   class="form-control" 
                                   id="email" 
                                   name="email" 
                                   required 
                                   value="<?php echo $_POST['email'] ?? ''; ?>">
                            <div class="invalid-feedback">
                                Por favor, insira um email válido.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="telefone" class="form-label">Telefone</label>
                            <input type="tel" 
                                   class="form-control" 
                                   id="telefone" 
                                   name="telefone" 
                                   value="<?php echo $_POST['telefone'] ?? ''; ?>">
                        </div>

                        <div class="mb-3">
                            <label for="nivel_acesso_id" class="form-label">Nível de Acesso</label>
                            <select class="form-select" id="nivel_acesso_id" name="nivel_acesso_id" required>
                                <option value="">Selecione um nível de acesso</option>
                                <?php foreach ($niveis_acesso as $nivel): ?>
                                    <option value="<?php echo $nivel['id']; ?>" 
                                            <?php echo isset($_POST['nivel_acesso_id']) && $_POST['nivel_acesso_id'] == $nivel['id'] ? 'selected' : ''; ?>>
                                        <?php echo $nivel['nome']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">
                                Por favor, selecione um nível de acesso.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="senha" class="form-label">Senha</label>
                            <input type="password" 
                                   class="form-control" 
                                   id="senha" 
                                   name="senha" 
                                   required
                                   minlength="6"
                                   maxlength="20"
                                   pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{6,}"
                                   title="A senha deve conter pelo menos 6 caracteres, incluindo letras maiúsculas, minúsculas e números">
                            <div class="invalid-feedback">
                                A senha deve ter no mínimo 6 caracteres, incluindo letras maiúsculas, minúsculas e números.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="confirmar_senha" class="form-label">Confirmar Senha</label>
                            <input type="password" 
                                   class="form-control" 
                                   id="confirmar_senha" 
                                   name="confirmar_senha" 
                                   required
                                   minlength="6"
                                   maxlength="20">
                            <div class="invalid-feedback">
                                As senhas não conferem. Por favor, verifique.
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-person-plus"></i> Cadastrar Usuário
                            </button>
                            <a href="usuarios.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Voltar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Validação do formulário
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
            }
            form.classList.add('was-validated')
        }, false)
    })
})()

// Validação de senha
document.getElementById('confirmar_senha').addEventListener('input', function() {
    var senha = document.getElementById('senha').value;
    var confirmarSenha = this.value;
    var feedback = this.nextElementSibling;
    
    if (senha !== confirmarSenha) {
        this.setCustomValidity('As senhas não conferem');
        feedback.style.display = 'block';
    } else {
        this.setCustomValidity('');
        feedback.style.display = 'none';
    }
});
</script>

<?php require_once 'includes/footer.php'; ?> 