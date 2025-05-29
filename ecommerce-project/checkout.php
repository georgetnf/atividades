<?php

include 'config.php';

// Redireciona o usuário para a página do carrinho se o carrinho estiver vazio.
// Não faz sentido ir para o checkout se não há itens para comprar.
if (empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit(); // Encerra o script para garantir o redirecionamento.
}

// Redireciona o usuário para a página de login se ele não estiver logado.
// A página de checkout exige que o usuário esteja autenticado para prosseguir com o pedido.
if (!isset($_SESSION['user_id'])) {
    // Salva a URL atual na sessão para que o usuário seja redirecionado de volta para o checkout
    // após fazer login ou se cadastrar na página de login.
    $_SESSION['redirect_after_login'] = 'checkout.php';
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_data = [];
$total_carrinho = 0;

//Lógica para buscar dados do usuário logado (para endereço de entrega)
$stmt_user_data = $conn->prepare("SELECT nome, email, endereco, cidade, estado, cep, telefone FROM usuarios WHERE id = ?");
$stmt_user_data->bind_param("i", $user_id);
$stmt_user_data->execute();
$result_user_data = $stmt_user_data->get_result();
if ($result_user_data->num_rows > 0) {
    $user_data = $result_user_data->fetch_assoc();
}
$stmt_user_data->close();


// =============================================================
//Metodo de pagamentos salvos
$default_payment_method = null;
$user_payment_methods = [];

// Lógica para buscar TODOS os métodos de pagamento do usuário.
// Necessário para permitir que o cliente troque o método de pagamento ou que o padrão seja selecionado.
$stmt_all_payments = $conn->prepare("SELECT id, tipo, ultimos_digitos, bandeira, data_expiracao, chave_pix, codigo_boleto, padrao 
                                    FROM metodos_pagamento 
                                    WHERE usuario_id = ? ORDER BY padrao DESC, id DESC");
$stmt_all_payments->bind_param("i", $user_id);
$stmt_all_payments->execute();
$result_all_payments = $stmt_all_payments->get_result();
while ($method = $result_all_payments->fetch_assoc()) {
    $user_payment_methods[] = $method;
    // Se for o método padrão, armazena para pré-seleção no HTML
    if ($method['padrao'] == 1) { // Assumindo que 'padrao' é um boolean/tinyint
        $default_payment_method = $method;
    }
}
$stmt_all_payments->close();

//Lógica para buscar o método de pagamento padrão do usuário
$stmt_default_payment = $conn->prepare("SELECT id, tipo, ultimos_digitos, bandeira, data_expiracao, chave_pix, codigo_boleto 
                                       FROM metodos_pagamento 
                                       WHERE usuario_id = ? AND padrao = TRUE LIMIT 1");
$stmt_default_payment->bind_param("i", $user_id);
$stmt_default_payment->execute();
$result_default_payment = $stmt_default_payment->get_result();
if ($result_default_payment->num_rows > 0) {
    $default_payment_method = $result_default_payment->fetch_assoc();
}
$stmt_default_payment->close();
// =============================================================

//Logia para calculo do total do carrinho
//Percorre os itens do carrinho na sessão para calcular
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    //Assegura que o preco e quantidade são numeros para evitar erros
    $preco_item = isset($item['preco']) ? intval($item['preco']) : 0;
    $quantidadeo_item = isset($item['quantity']) ? intval($item['quantity']) : 0;
    $total_carrinho += ($preco_item * $quantidadeo_item);
}

// Lógica de cálculo de frete (exemplo simplificado)
$frete = 25.00; // Valor fixo de exemplo. Em um cenário real, calcularia com base no CEP.

// Calcula o total final do carrinho, incluindo o frete.
$total_carrinho_com_frete = $total_carrinho + $frete;

// --- LINHAS DE DEBUG (MANTENHA PARA TESTE, REMOVA DEPOIS) ---
echo "DEBUG: Conteúdo de _SESSION['cart']: <pre>";
print_r($_SESSION['cart']);
echo "</pre>";
echo "DEBUG: total_carrinho (apenas produtos) = R$ " . number_format($total_carrinho, 2, ',', '.') . "<br>";
echo "DEBUG: frete = R$ " . number_format($frete, 2, ',', '.') . "<br>";
echo "DEBUG: total_carrinho_com_frete (com frete) = R$ " . number_format($total_carrinho_com_frete, 2, ',', '.') . "<br>";
// --- FIM DAS LINHAS DE DEBUG ---

// Certifica-se que o carrinho não está vazio
if (empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit();
}

// Calcula o total do carrinho
/*foreach ($_SESSION['cart'] as $item) {
    $total_carrinho += $item['preco'] * $item['quantity'];
}*/

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
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finalizar Pedido - KaBuM! Clone</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Estilo para a badge de desconto, se você tiver uma */
        .discount-badge {
            background-color: #ff6600; /* Laranja KaBuM! */
        }
        .bg-orange {
            background-color: #ff6600 !important;
        }
    </style>
</head>
<body>

<div class="container my-4">
    <h2 class="mb-4">Finalizar Pedido</h2>

    <?php
    // Exibe mensagem de erro se houver
    if (isset($_GET['error'])) {
        $error_message = htmlspecialchars($_GET['message'] ?? 'Ocorreu um erro.');
        echo '<div class="alert alert-danger text-center" role="alert">' . $error_message . '</div>';
    }
    ?>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-orange text-white">
                    <h5 class="mb-0">Seus Dados de Entrega</h5>
                </div>
                <div class="card-body">
                    <form action="process_order.php" method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nome" class="form-label">Nome Completo</label>
                                <input type="text" class="form-control" id="nome" name="nome" value="<?= htmlspecialchars($user_data['nome'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user_data['email'] ?? '') ?>" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="endereco" class="form-label">Endereço (Rua, Número, Complemento)</label>
                            <input type="text" class="form-control" id="endereco" name="endereco" value="<?= htmlspecialchars($user_data['endereco'] ?? '') ?>" required>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="cidade" class="form-label">Cidade</label>
                                <input type="text" class="form-control" id="cidade" name="cidade" value="<?= htmlspecialchars($user_data['cidade'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="estado" class="form-label">Estado</label>
                                <input type="text" class="form-control" id="estado" name="estado" value="<?= htmlspecialchars($user_data['estado'] ?? '') ?>" required maxlength="2" placeholder="Ex: SP">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="cep" class="form-label">CEP</label>
                                <input type="text" class="form-control" id="cep" name="cep" value="<?= htmlspecialchars($user_data['cep'] ?? '') ?>" required pattern="\d{5}-?\d{3}" placeholder="Ex: 12345-678">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="telefone" class="form-label">Telefone</label>
                            <input type="text" class="form-control" id="telefone" name="telefone" value="<?= htmlspecialchars($user_data['telefone'] ?? '') ?>" required pattern="\(\d{2}\)\s*\d{4,5}-?\d{4}" placeholder="Ex: (11) 98765-4321">
                        </div>

                        <hr class="my-4">

                        <h5>Método de Pagamento</h5>
                        <div class="mb-3">
                            <?php if (!empty($user_payment_methods)): ?>
                                <?php foreach ($user_payment_methods as $method): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment_type" id="payment_<?= htmlspecialchars($method['id']) ?>" value="<?= htmlspecialchars($method['tipo']) ?>"
                                            <?= ($default_payment_method && $default_payment_method['id'] == $method['id']) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="payment_<?= htmlspecialchars($method['id']) ?>">
                                            <?php 
                                            // Exibe o tipo de método de pagamento com detalhes
                                            if ($method['tipo'] == 'credito') {
                                                echo '<i class="bi bi-credit-card me-2"></i>Cartão de Crédito (' . htmlspecialchars($method['bandeira'] ?? '') . ' - **** ' . htmlspecialchars($method['ultimos_digitos'] ?? '') . ')';
                                            } elseif ($method['tipo'] == 'pix') {
                                                echo '<i class="bi bi-qr-code me-2"></i>PIX (Chave: ' . htmlspecialchars($method['chave_pix'] ?? 'N/A') . ')';
                                            } elseif ($method['tipo'] == 'boleto') {
                                                echo '<i class="bi bi-upc-scan me-2"></i>Boleto Bancário';
                                            }
                                            ?>
                                            <?= ($method['padrao'] == 1) ? ' (Padrão)' : '' ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                                <hr class="my-3">
                                <p class="text-muted small">Ou selecione uma nova opção:</p>
                            <?php endif; ?>

                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_type" id="payment_pix_new" value="pix"
                                    <?php 
                                    // Marca PIX como padrão se não houver método padrão salvo,
                                    // ou se o método padrão salvo não for PIX/Boleto/Crédito.
                                    $is_pix_checked = false;
                                    if (!$default_payment_method) {
                                        $is_pix_checked = true; // Marca se não houver padrão
                                    } elseif ($default_payment_method['tipo'] !== 'pix' && $default_payment_method['tipo'] !== 'boleto' && $default_payment_method['tipo'] !== 'credito') {
                                        $is_pix_checked = true; // Marca se o padrão for de outro tipo
                                    }
                                    echo $is_pix_checked ? 'checked' : '';
                                    ?>
                                >
                                <label class="form-check-label" for="payment_pix_new">
                                    <i class="bi bi-qr-code me-2"></i>PIX
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_type" id="payment_boleto_new" value="boleto"
                                    <?php 
                                    // Marca Boleto se não houver padrão e PIX não for marcado
                                    if (!$default_payment_method && !$is_pix_checked) {
                                        echo 'checked';
                                    }
                                    ?>
                                >
                                <label class="form-check-label" for="payment_boleto_new">
                                    <i class="bi bi-upc-scan me-2"></i>Boleto Bancário
                                </label>
                            </div>
                            </div>

                        <input type="hidden" name="total_geral" value="<?= number_format($total_carrinho_com_frete, 2, '.', '') ?>">

                        <button type="submit" class="btn btn-primary btn-lg w-100 mt-4">Confirmar Pedido</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Resumo do Pedido</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <?php if (!empty($_SESSION['cart'])): ?>
                            <?php foreach ($_SESSION['cart'] as $item): ?>
                                <li class="list-group-item d-flex justify-content-between lh-sm">
                                    <div>
                                        <h6 class="my-0"><?= htmlspecialchars($item['nome']) ?> (x<?= htmlspecialchars($item['quantity']) ?>)</h6>
                                        <small class="text-muted">R$ <?= number_format($item['preco'], 2, ',', '.') ?> cada</small>
                                    </div>
                                    <span class="text-muted">R$ <?= number_format($item['preco'] * $item['quantity'], 2, ',', '.') ?></span>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="list-group-item text-center">Seu carrinho está vazio.</li>
                        <?php endif; ?>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Subtotal dos Produtos</span>
                            <strong>R$ <?= number_format($total_carrinho, 2, ',', '.') ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Frete</span>
                            <strong>R$ <?= number_format($frete, 2, ',', '.') ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between bg-light">
                            <span class="fw-bold">Total Geral (BRL)</span>
                            <strong class="fw-bold">R$ <?= number_format($total_carrinho_com_frete, 2, ',', '.') ?></strong>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'HTML/footer.php'; // Inclui o rodapé, ajuste o caminho se necessário ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>