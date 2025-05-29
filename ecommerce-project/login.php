<?php

include 'config.php';

// Inicializa variáveis para armazenar mensagens de erro ou sucesso.
// Estas mensagens serão exibidas ao usuário no formulário.
$login_error = '';
$register_error = '';
$register_success = '';

// Determina qual aba do modal deve ser ativa por padrão (login ou cadastro).
// Se 'action=register' estiver na URL, a aba de cadastro será ativada.
$active_tab = isset($_GET['action']) && $_GET['action'] == 'register' ? 'register' : 'login';

// --- Lógica de Login ---
// Este bloco é executado quando o formulário de login é submetido via método POST.
if (isset($_POST['login_submit'])) {
    // Sanitiza e obtém o email e a senha do formulário.
    // A função sanitize_input() (definida em config.php) é crucial para prevenir SQL Injection e XSS
    $email = sanitize_input($conn, $_POST['login_email']);
    $senha = sanitize_input($conn, $_POST['login_senha']);

    // Prepara uma consulta SQL para buscar o usuário no banco de dados pelo email.
    $sql_login = "SELECT id, nome, senha FROM usuarios WHERE email = ?"; 
    $stmt_login = $conn->prepare($sql_login);
    $stmt_login->bind_param("s", $email);
    $stmt_login->execute();
    $result_login = $stmt_login->get_result();

    // Verifica se um usuário foi encontrado com o email fornecido.
    if ($result_login->num_rows > 0) {
        //Obtem dados do usuario com o array associativo
        $user = $result_login->fetch_assoc();
        if (password_verify($senha, $user['senha'])) {
            // Se a senha estiver correta, armazena o ID e o nome do usuário na sessão.
            // Isso marca o usuário como logado para as próximas requisições.
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['nome'];
            $_SESSION['user_address'] = $user['endereco'];

            // Redireciona o usuário para a página de onde ele veio (se houver uma URL salva na sessão)
            // ou para a página inicial (index.php) por padrão.
            if (isset($_SESSION['redirect_after_login'])) {
                $redirect_url = $_SESSION['redirect_after_login'];
                unset($_SESSION['redirect_after_login']);
                header("Location: ". $redirect_url);
            }else{
                header("Location: index.php");
            }
            exit();
        }else{
          // Se a senha estiver incorreta, define uma mensagem de erro. 
          $login_error = "Email ou senha incorretos.";
          $active_tab = 'login'; //mantem aba de login ative em caso de erro 
        }
        $stmt_login->close();
    }

}

// --- Lógica de Cadastro ---
// Este bloco é executado quando o formulário de cadastro é submetido via método POST.
if (isset($_POST['register_submit'])) {
    // Sanitiza e obtém os dados do formulário de cadastro (nome, email, senha, confirmação de senha).
    $nome = sanitize_input($conn, $_POST['register_nome']);
    $email = sanitize_input($conn, $_POST['register_email']);
    $senha = sanitize_input($conn, $_POST['register_senha']);
    $confirm_senha = sanitize_input($conn, $_POST['register_confirm_senha']);
    $endereco = sanitize_input($conn, $_POST['register_endereco'] ?? '');

    // Verifica se as senhas digitadas nos campos 'senha' e 'confirmar senha' coincidem.
    // Se não coincidirem, define uma mensagem de erro.
    if ($senha !== $confirm_senha) {
        $register_error = "As senhas não coincidem!";
        $active_tab = 'register';  // Mantém a aba de cadastro ativa em caso de erro.
    }else if (strlen($senha) < 6) { // CORREÇÃO: Adicionada validação de comprimento mínimo para a senha(opcional)
        $register_error = "A senha deve ter pelo menos 6 caracteres.";
        $active_tab = 'register';
    } else {
        // Prepara uma consulta para verificar se o email já está cadastrado no banco de dados.
        // Isso evita que múltiplos usuários se cadastrem com o mesmo email.
        $sql_check_email = "SELECT id FROM usuarios WHERE email = ?";
        $stmt_check_email = $conn->prepare($sql_check_email);
        $stmt_check_email->bind_param("s", $email);
        $stmt_check_email->execute();
        $result_check_email = $stmt_check_email->get_result();
        

        // Se o email já existe, define uma mensagem de erro.
        if ($result_check_email->num_rows > 0) {
            $register_error = "Este email já está cadastrado.";
            $active_tab = 'register';// Mantém a aba de cadastro ativa
        } else {
            // Gera um hash da senha usando password_hash() antes de armazená-la no banco de dados.
            // É fundamental para a segurança que as senhas nunca sejam armazenadas em texto puro.
            $hashed_password = password_hash($senha, PASSWORD_DEFAULT);
            $sql_register = "INSERT INTO usuarios (nome, email, senha, endereco) VALUES (?, ?, ?, ?)";
            $stmt_register = $conn->prepare($sql_register);
            $stmt_register->bind_param("ssss",$nome, $email, $hashed_password, $endereco);

            //Faz a inserção do usuário
            if ($stmt_register->execute()) {
                // Se a inserção for bem-sucedida, define uma mensagem de sucesso.
                $register_success = "Cadastro realizado com sucesso! Faça login.";
                $active_tab = 'login'; // Redireciona para a aba de login após o cadastro bem-sucedido.
            } else {
                // Se houver um problema na inserção, define uma mensagem de erro.
                $register_error = "Erro ao cadastrar. Tente novamente.";
                $active_tab = 'register'; // Mantém a aba de cadastro ativa em caso de erro.
            }
        }
        // Fecha as declarações preparadas para liberar recursos do banco de dados.
        $stmt_check_email->close();
        if (isset($stmt_register)) $stmt_register->close();
    }
}

// Contagem de itens no carrinho para o badge do cabeçalho.
$cart_item_count = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_item_count += $item['quantity'];
    }
}

include 'HTML/header.php';

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login / Cadastro - Kabum Clone</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header text-center">
                        <ul class="nav nav-tabs card-header-tabs" id="myTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link <?php echo ($active_tab == 'login') ? 'active' : ''; ?>" id="login-tab" data-bs-toggle="tab" data-bs-target="#login" type="button" role="tab" aria-controls="login" aria-selected="<?php echo ($active_tab == 'login') ? 'true' : 'false'; ?>">Login</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link <?php echo ($active_tab == 'register') ? 'active' : ''; ?>" id="register-tab" data-bs-toggle="tab" data-bs-target="#register" type="button" role="tab" aria-controls="register" aria-selected="<?php echo ($active_tab == 'register') ? 'true' : 'false'; ?>">Cadastrar</button>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content" id="myTabContent">
                            <div class="tab-pane fade <?php echo ($active_tab == 'login') ? 'show active' : ''; ?>" id="login" role="tabpanel" aria-labelledby="login-tab">
                                <h5 class="card-title text-center mb-4">Acesse sua conta</h5>
                                <?php if (!empty($login_error)): ?>
                                    <div class="alert alert-danger" role="alert">
                                        <?php echo $login_error; ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($register_success)): ?>
                                    <div class="alert alert-success" role="alert">
                                        <?php echo $register_success; ?>
                                    </div>
                                <?php endif; ?>
                                <form action="login.php" method="POST">
                                    <div class="mb-3">
                                        <label for="login_email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="login_email" name="login_email" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="login_senha" class="form-label">Senha</label>
                                        <input type="password" class="form-control" id="login_senha" name="login_senha" required>
                                    </div>
                                    <button type="submit" name="login_submit" class="btn btn-primary w-100">Entrar</button>
                                </form>
                            </div>
                            <div class="tab-pane fade <?php echo ($active_tab == 'register') ? 'show active' : ''; ?>" id="register" role="tabpanel" aria-labelledby="register-tab">
                                <h5 class="card-title text-center mb-4">Crie sua conta</h5>
                                <?php if (!empty($register_error)): ?>
                                    <div class="alert alert-danger" role="alert">
                                        <?php echo $register_error; ?>
                                    </div>
                                <?php endif; ?>
                                <form action="login.php" method="POST">
                                    <div class="mb-3">
                                        <label for="register_nome" class="form-label">Nome Completo</label>
                                        <input type="text" class="form-control" id="register_nome" name="register_nome" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="register_email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="register_email" name="register_email" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="register_senha" class="form-label">Senha</label>
                                        <input type="password" class="form-control" id="register_senha" name="register_senha" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="register_confirm_senha" class="form-label">Confirmar Senha</label>
                                        <input type="password" class="form-control" id="register_confirm_senha" name="register_confirm_senha" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="register_endereco" class="form-label">Endereço (Opcional)</label>
                                        <input type="text" class="form-control" id="register_endereco" name="register_endereco">
                                    </div>
                                    <button type="submit" name="register_submit" class="btn btn-success w-100">Cadastrar</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'HTML/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Ativa a aba correta com base na variável PHP $active_tab
        var activeTab = '<?php echo $active_tab; ?>';
        var someTabTriggerEl = document.querySelector('#' + activeTab + '-tab');
        var tab = new bootstrap.Tab(someTabTriggerEl);
        tab.show();
    </script>
</body>
</html>