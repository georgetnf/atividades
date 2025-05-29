<?php
include 'config.php';

// Redireciona se o usuário não estiver logado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_data = [];
$user_payment_methods = [];
$total_spent = 0;
$message = '';
$message_type = ''; // 'success' ou 'danger'

// --- Lógica para buscar dados do usuário ---
$stmt_user = $conn->prepare("SELECT nome, email, endereco, cidade, estado, cep, telefone FROM usuarios WHERE id = ?");
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
if ($result_user->num_rows > 0) {
    $user_data = $result_user->fetch_assoc();
}
$stmt_user->close();

// --- Lógica para atualizar dados do usuário (Endereço, Telefone, Email, Senha) ---
// Este bloco é executado quando o formulário de atualização de perfil é submetido via POST.
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $new_email = trim($_POST['email']);
    $new_endereco = trim($_POST['endereco']);
    $new_cidade = trim($_POST['cidade']);
    $new_estado = trim($_POST['estado']);
    $new_cep = trim($_POST['cep']);
    $new_telefone = trim($_POST['telefone']);

    
    // Validação básica de email usando filter_var para verificar o formato do email.
    if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $message = "Email inválido.";
        $message_type = 'danger';
    } else {
        // Verificar se o novo email já existe para outro usuário
        $stmt_check_email = $conn->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
        $stmt_check_email->bind_param("si", $new_email, $user_id);
        $stmt_check_email->execute();
        $stmt_check_email->store_result();
        
        if ($stmt_check_email->num_rows > 0) {
            $message = "Este email já está em uso por outra conta.";
            $message_type = 'danger';
        } else {
            // Se o email for válido e não estiver em uso, prepara a consulta para atualizar os dados do usuário.
            $stmt_update = $conn->prepare("UPDATE usuarios SET email = ?, endereco = ?, cidade = ?, estado = ?, cep = ?, telefone = ? WHERE id = ?");
            $stmt_update->bind_param("ssssssi", $new_email, $new_endereco, $new_cidade, $new_estado, $new_cep, $new_telefone, $user_id);

            if ($stmt_update->execute()) {
                $message = "Dados atualizados com sucesso!";
                $message_type = 'success';
                // Atualiza os dados na sessão e em $user_data
                $_SESSION['user_email'] = $new_email;
                $user_data['email'] = $new_email;
                $user_data['endereco'] = $new_endereco;
                $user_data['cidade'] = $new_cidade;
                $user_data['estado'] = $new_estado;
                $user_data['cep'] = $new_cep;
                $user_data['telefone'] = $new_telefone;
            } else {
                $message = "Erro ao atualizar dados: " . $stmt_update->error;
                $message_type = 'danger';
            }
            $stmt_update->close();
        }
        $stmt_check_email->close();
    }
}

// --- Lógica para mudar a senha ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Buscar a senha atual do usuário no BD
    $stmt_fetch_pass = $conn->prepare("SELECT senha FROM usuarios WHERE id = ?");
    $stmt_fetch_pass->bind_param("i", $user_id);
    $stmt_fetch_pass->execute();
    $result_fetch_pass = $stmt_fetch_pass->get_result();
    $row_pass = $result_fetch_pass->fetch_assoc();
    $hashed_password_db = $row_pass['senha'];
    $stmt_fetch_pass->close();


    // Verifica se a senha atual fornecida corresponde ao hash no banco de dados.
    if (!password_verify($current_password, $hashed_password_db)) {
        $message = "Senha atual incorreta.";
        $message_type = 'danger';
    // Verifica se a nova senha está vazia ou tem menos de 6 caracteres.
    } elseif (empty($new_password) || strlen($new_password) < 6) {
        $message = "A nova senha deve ter pelo menos 6 caracteres.";
        $message_type = 'danger';
    // Verifica se a nova senha e a confirmação coincidem.
    } elseif ($new_password !== $confirm_password) {
        $message = "A nova senha e a confirmação não coincidem.";
        $message_type = 'danger';
    } else {
        // Gera um novo hash para a nova senha. E atualiza na DB
        $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt_update_pass = $conn->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
        $stmt_update_pass->bind_param("si", $hashed_new_password, $user_id);
        if ($stmt_update_pass->execute()) {
            $message = "Senha alterada com sucesso!";
            $message_type = 'success';
        } else {
            $message = "Erro ao alterar senha: " . $stmt_update_pass->error;
            $message_type = 'danger';
        }
        $stmt_update_pass->close();
    }
}

// --- Lógica para adicionar novo método de pagamento ---
// Este bloco é executado quando o formulário de adicionar método de pagamento é submetido via POST.
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_payment_method'])) {
    $card_number = trim($_POST['card_number']);
    $expiry_date = trim($_POST['expiry_date']); // MM/AAAA
    $card_bandeira = trim($_POST['card_bandeira']);

    // Validações básicas (melhorar em um ambiente real)
    // Verifica se o número do cartão é válido (comprimento e se é numérico).
    if (empty($card_number) || strlen($card_number) < 13 || strlen($card_number) > 19 || !is_numeric($card_number)) {
        $message = "Número do cartão inválido.";
        $message_type = 'danger';
    // Verifica o formato da data de validade usando uma expressão regular MM/AAAA.
    } elseif (!preg_match("/^(0[1-9]|1[0-2])\/\d{4}$/", $expiry_date)) { // Regex MM/AAAA
        $message = "Formato de data de validade inválido (MM/AAAA).";
        $message_type = 'danger';
    } else {
        // Obtém os últimos quatro dígitos do cartão para armazenamento (por segurança, não se armazena o número completo).
        $last_four = substr($card_number, -4);

        //Adicionando a lógica para inserir o método de pagamento no banco de dados.
        $stmt_add_payment = $conn->prepare("INSERT INTO metodos_pagamento (usuario_id, tipo, ultimos_digitos, bandeira, data_expiracao, padrao) VALUES (?, ?, ?, ?, ?, ?)");
        // Por simplicidade, assumindo 'credito' como tipo e definindo como padrao (TRUE) para o primeiro adicionado.
        // Em um sistema real, o usuário escolheria se é padrão ou não.
        $tipo_cartao = 'credito';
        $padrao = true; // Define como padrão por enquanto.
        $stmt_add_payment->bind_param("issssi", $user_id, $tipo_cartao, $last_four, $card_bandeira, $expiry_date, $padrao);
        
        if ($stmt_add_payment->execute()) {
            $message = "Método de pagamento adicionado com sucesso!";
            $message_type = 'success';
        } else {
            $message = "Erro ao adicionar método de pagamento: " . $stmt_add_payment->error;
            $message_type = 'danger';
        }
        $stmt_add_payment->close();
    }
}

// --- Lógica para remover método de pagamento ---
if (isset($_GET['remove_payment'])) {
    $payment_id = intval($_GET['remove_payment']);
    $stmt_remove = $conn->prepare("DELETE FROM metodos_pagamento WHERE id = ? AND usuario_id = ?");
    $stmt_remove->bind_param("ii", $payment_id, $user_id);
    if ($stmt_remove->execute()) {
        $message = "Método de pagamento removido com sucesso!";
        $message_type = 'success';
    } else {
        $message = "Erro ao remover método de pagamento: " . $stmt_remove->error;
        $message_type = 'danger';
    }
    $stmt_remove->close();
    // Redireciona para evitar reenvio do GET
    header("Location: account.php?message=" . urlencode($message) . "&type=" . $message_type);
    exit();
}

// --- Lógica para definir método de pagamento padrão ---
/*if (isset($_GET['set_default_payment'])) {
    $payment_id_default = intval($_GET['set_default_payment']);
    
    // Primeiro, desmarcar todos os outros como padrão para este usuário
    $stmt_reset_default = $conn->prepare("UPDATE metodos_pagamento SET padrao = FALSE WHERE usuario_id = ?");
    $stmt_reset_default->bind_param("i", $user_id);
    $stmt_reset_default->execute();
    $stmt_reset_default->close();

    // Depois, marcar o selecionado como padrão
    $stmt_set_default = $conn->prepare("UPDATE metodos_pagamento SET padrao = TRUE WHERE id = ? AND usuario_id = ?");
    $stmt_set_default->bind_param("ii", $payment_id_default, $user_id);
    if ($stmt_set_default->execute()) {
        $message = "Método de pagamento padrão definido!";
        $message_type = 'success';
    } else {
        $message = "Erro ao definir padrão: " . $stmt_set_default->error;
        $message_type = 'danger';
    }
    $stmt_set_default->close();
    header("Location: account.php?message=" . urlencode($message) . "&type=" . $message_type);
    exit();
}*/


// --- Lógica para buscar métodos de pagamento do usuário ---
$stmt_payment_methods = $conn->prepare("SELECT id, tipo, ultimos_digitos, bandeira, data_expiracao, padrao FROM metodos_pagamento WHERE usuario_id = ? ORDER BY padrao DESC, id DESC");
$stmt_payment_methods->bind_param("i", $user_id);
$stmt_payment_methods->execute();
$result_payment_methods = $stmt_payment_methods->get_result();
if ($result_payment_methods->num_rows > 0) {
    while ($method = $result_payment_methods->fetch_assoc()) {
        $user_payment_methods[] = $method;
    }
}
$stmt_payment_methods->close();

// --- Lógica para buscar o total gasto pelo usuário ---
// Prepara uma consulta para somar o total de todos os pedidos finalizados do usuário.
$stmt_total_spent = $conn->prepare("SELECT SUM(total) AS total_gasto FROM pedidos WHERE usuario_id = ? AND status = 'finalizado'");
$stmt_total_spent->bind_param("i", $user_id);
$stmt_total_spent->execute();
$result_total_spent = $stmt_total_spent->get_result();
if ($result_total_spent->num_rows > 0) {
    $row_total = $result_total_spent->fetch_assoc();
    $total_spent = $row_total['total_gasto'] ?? 0; // Se não houver pedidos, define como 0.
}
$stmt_total_spent->close();


// Exibe mensagens de feedback se existirem (após redirecionamento)
if (isset($_GET['message']) && isset($_GET['type'])) {
    $message = htmlspecialchars($_GET['message']);
    $message_type = htmlspecialchars($_GET['type']);
}

// --- Contagem de Itens no Carrinho para o Badge do Cabeçalho (igual ao cart.php) ---
$cart_item_count = 0;
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
if (isset($_SESSION['cart'])) {
    foreach($_SESSION['cart'] as $item){
        $cart_item_count += $item['quantity'];
    }
}

// Inclui o cabeçalho do site.
include 'HTML/header.php';
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minha Conta - Kabum Clone</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php // include 'includes/header.php'; ?> 

    <div class="container mt-5">
        <h1 class="mb-4">Minha Conta</h1>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-3">
                <div class="list-group">
                    <a href="#dados-pessoais" class="list-group-item list-group-item-action active" data-bs-toggle="list">Dados Pessoais</a>
                    <a href="#seguranca" class="list-group-item list-group-item-action" data-bs-toggle="list">Segurança</a>
                    <a href="#metodos-pagamento" class="list-group-item list-group-item-action" data-bs-toggle="list">Métodos de Pagamento</a>
                    <a href="#historico-pedidos" class="list-group-item list-group-item-action" data-bs-toggle="list">Histórico de Pedidos</a>
                    <a href="#total-gasto" class="list-group-item list-group-item-action" data-bs-toggle="list">Total Gasto</a>
                </div>
            </div>

            <div class="col-md-9">
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="dados-pessoais">
                        <h2>Meus Dados</h2>
                        <form method="POST" action="account.php">
                            <div class="mb-3">
                                <label for="nome" class="form-label">Nome Completo</label>
                                <input type="text" class="form-control" id="nome" value="<?php echo htmlspecialchars($user_data['nome'] ?? ''); ?>" readonly>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="endereco" class="form-label">Endereço</label>
                                <input type="text" class="form-control" id="endereco" name="endereco" value="<?php echo htmlspecialchars($user_data['endereco'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="cidade" class="form-label">Cidade</label>
                                <input type="text" class="form-control" id="cidade" name="cidade" value="<?php echo htmlspecialchars($user_data['cidade'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="estado" class="form-label">Estado</label>
                                <input type="text" class="form-control" id="estado" name="estado" value="<?php echo htmlspecialchars($user_data['estado'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="cep" class="form-label">CEP</label>
                                <input type="text" class="form-control" id="cep" name="cep" value="<?php echo htmlspecialchars($user_data['cep'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="telefone" class="form-label">Telefone</label>
                                <input type="text" class="form-control" id="telefone" name="telefone" value="<?php echo htmlspecialchars($user_data['telefone'] ?? ''); ?>">
                            </div>
                            <button type="submit" name="update_profile" class="btn btn-primary">Atualizar Dados</button>
                        </form>
                    </div>

                    <div class="tab-pane fade" id="seguranca">
                        <h2>Segurança</h2>
                        <form method="POST" action="account.php">
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Senha Atual</label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>
                            <div class="mb-3">
                                <label for="new_password" class="form-label">Nova Senha</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirmar Nova Senha</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            <button type="submit" name="change_password" class="btn btn-primary">Alterar Senha</button>
                        </form>
                    </div>

                    <div class="tab-pane fade" id="metodos-pagamento">
                        <h2>Meus Métodos de Pagamento</h2>
                        <div class="card mb-4">
                            <div class="card-header">Adicionar Novo Cartão de Crédito</div>
                            <div class="card-body">
                                <form method="POST" action="account.php">
                                    <div class="mb-3">
                                        <label for="card_number" class="form-label">Número do Cartão</label>
                                        <input type="text" class="form-control" id="card_number" name="card_number" placeholder="XXXX XXXX XXXX XXXX" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="expiry_date" class="form-label">Data de Validade (MM/AAAA)</label>
                                        <input type="text" class="form-control" id="expiry_date" name="expiry_date" placeholder="MM/AAAA" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="card_bandeira" class="form-label">Bandeira</label>
                                        <input type="text" class="form-control" id="card_bandeira" name="card_bandeira" placeholder="Ex: Visa, Mastercard" required>
                                    </div>
                                    <button type="submit" name="add_payment_method" class="btn btn-primary">Adicionar Cartão</button>
                                </form>
                            </div>
                        </div>

                        <h3>Cartões Salvos</h3>
                        <?php if (empty($user_payment_methods)): ?>
                            <p>Nenhum método de pagamento salvo.</p>
                        <?php else: ?>
                            <ul class="list-group">
                                <?php foreach ($user_payment_methods as $method): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <?php 
                                            // Exibe o tipo de método de pagamento (Crédito, Pix, Boleto)
                                            echo htmlspecialchars(ucfirst($method['tipo'])); 
                                            // Se for cartão, exibe os últimos dígitos e a bandeira
                                            if ($method['tipo'] === 'credito') {
                                                echo ' - **** ' . htmlspecialchars($method['ultimos_digitos']) . ' (' . htmlspecialchars($method['bandeira']) . ')';
                                            }
                                            // Se for Pix, exibe a chave Pix (se disponível, idealmente não exibir a chave completa)
                                            if ($method['tipo'] === 'pix' && !empty($method['chave_pix'])) {
                                                echo ' - Chave Pix: ' . htmlspecialchars($method['chave_pix']); // Idealmente, apenas uma parte ou tipo de chave
                                            }
                                            // Se for Boleto, exibe o código do boleto (se disponível, idealmente não exibir o código completo)
                                            if ($method['tipo'] === 'boleto' && !empty($method['codigo_boleto'])) {
                                                echo ' - Código Boleto: ' . htmlspecialchars(substr($method['codigo_boleto'], 0, 10)) . '...'; // Exibe apenas uma parte
                                            }
                                        ?>
                                        <?php if ($method['padrao']): ?>
                                            <span class="badge bg-success rounded-pill">Padrão</span>
                                        <?php else: ?>
                                            <form action="account.php" method="POST" class="d-inline">
                                                <input type="hidden" name="set_default_payment_id" value="<?php echo $method['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-secondary">Definir como Padrão</button>
                                            </form>
                                        <?php endif; ?>
                                        <form action="account.php" method="POST" class="d-inline ms-2">
                                            <input type="hidden" name="remove_payment_id" value="<?php echo $method['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza que deseja remover este método de pagamento?');">Remover</button>
                                        </form>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>

                    <div class="tab-pane fade" id="historico-pedidos">
                        <h2>Histórico de Pedidos</h2>
                        <?php 
                        // Inclui a lógica de busca de pedidos que está em cart.php (ou deveria ser uma função separada)
                        // Para este exemplo, vou assumir que você tem uma função ou o código de cart.php incluído aqui que popula $user_orders
                        // CORREÇÃO: Movi a lógica de buscar pedidos para aqui ou para um arquivo `order_history_logic.php` para evitar duplicação.
                        // Por enquanto, usarei uma busca direta para demonstrar.
                        
                        $user_orders = [];
                        $stmt_orders = $conn->prepare("SELECT id, data_pedido, total, status FROM pedidos WHERE usuario_id = ? ORDER BY data_pedido DESC");
                        $stmt_orders->bind_param("i", $user_id);
                        $stmt_orders->execute();
                        $result_orders = $stmt_orders->get_result();
                        if ($result_orders->num_rows > 0) {
                            while ($order = $result_orders->fetch_assoc()) {
                                $user_orders[] = $order;
                            }
                        }
                        $stmt_orders->close();
                        ?>

                        <?php if (empty($user_orders)): ?>
                            <p>Você ainda não fez nenhum pedido.</p>
                        <?php else: ?>
                            <div class="accordion" id="ordersAccordion">
                                <?php foreach ($user_orders as $order): ?>
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="heading<?php echo $order['id']; ?>">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $order['id']; ?>" aria-expanded="false" aria-controls="collapse<?php echo $order['id']; ?>">
                                                Pedido #<?php echo $order['id']; ?> - Data: <?php echo date('d/m/Y H:i', strtotime($order['data_pedido'])); ?> - Total: R$<?php echo number_format($order['total'], 2, ',', '.'); ?> - Status: <?php echo htmlspecialchars($order['status']); ?>
                                            </button>
                                        </h2>
                                        <div id="collapse<?php echo $order['id']; ?>" class="accordion-collapse collapse" aria-labelledby="heading<?php echo $order['id']; ?>" data-bs-parent="#ordersAccordion">
                                            <div class="accordion-body">
                                                <h5>Itens do Pedido:</h5>
                                                <ul class="list-group">
                                                    <?php
                                                    // Buscar itens específicos para este pedido
                                                    $order_items_specific = [];
                                                    $stmt_order_items = $conn->prepare("SELECT oi.quantidade, oi.preco_unitario, p.nome FROM itens_pedido oi JOIN produtos p ON oi.produto_id = p.id WHERE oi.pedido_id = ?");
                                                    $stmt_order_items->bind_param("i", $order['id']);
                                                    $stmt_order_items->execute();
                                                    $result_order_items = $stmt_order_items->get_result();
                                                    while ($item = $result_order_items->fetch_assoc()) {
                                                        $order_items_specific[] = $item;
                                                    }
                                                    $stmt_order_items->close();
                                                    ?>
                                                    <?php if (empty($order_items_specific)): ?>
                                                        <li class="list-group-item">Nenhum item encontrado para este pedido.</li>
                                                    <?php else: ?>
                                                        <?php foreach ($order_items_specific as $item): ?>
                                                            <li class="list-group-item">
                                                                <?php echo htmlspecialchars($item['nome']); ?> - Quantidade: <?php echo $item['quantidade']; ?> - Preço Unitário: R$<?php echo number_format($item['preco_unitario'], 2, ',', '.'); ?>
                                                            </li>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="tab-pane fade" id="total-gasto">
                        <h2>Total Gasto em Compras</h2>
                        <p class="fs-4">Você já gastou: <span class="fw-bold text-success">R$<?php echo number_format($total_spent, 2, ',', '.'); ?></span></p>
                        <p class="text-muted">Este valor reflete o total de todos os seus pedidos finalizados.</p>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <?php include 'HTML/footer.php'; // Inclua o rodapé aqui ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>