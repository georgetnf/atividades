<?php
include 'config.php';

//Lógica para remover item do carrinho 
// Este bloco de código é executado quando o usuário clica no botão "Remover"
// ao lado de um item no carrinho. Ele verifica se um ID de produto foi passado
// via URL (método GET) e se o carrinho existe na sessão.
if (isset($_GET['remove']) && isset($_SESSION['cart'])) {
    // Converte o ID do produto a ser removido para um inteiro.
    // Isso é uma medida de segurança importante para prevenir ataques de injeção de SQL
    // e garantir que estamos lidando com um ID de produto válido.
    $product_id_to_remove = intval($_GET['remove']);

    // Verifica se o produto com o ID especificado realmente existe no array do carrinho.
    // array_key_exists() é usado para verificar se a chave (ID do produto) existe.
    if (array_key_exists($product_id_to_remove, $_SESSION['cart'])) {
        // Remove o item específico do carrinho usando a função unset().
        // Isso apaga a entrada do array correspondente ao produto.
        unset($_SESSION['cart'][$product_id_to_remove]);
    }
    header("Location: cart.php"); //redirecionamento para atualizar a pagina
    exit();
}

//Logica para atualizar quantidade do item do carrinho
// Este bloco é ativado quando o usuário altera a quantidade de um item no carrinho
// e clica no botão "Atualizar". Ele verifica se a requisição é um POST
// e se o carrinho está presente na sessão.
if (isset($_POST['update_quantity']) && isset($_SESSION['cart'])) {
    // Converte o ID do produto e a nova quantidade para inteiros.
    // Novamente, isso é para segurança e validação de dados.
    $product_id_to_update = intval($_POST['product_id']);
    $new_quantity = intval($_POST['quantity']);

    // Verifica se o produto com o ID especificado existe no carrinho.
    if (array_key_exists($product_id_to_update, $_SESSION['cart'])) {
        // Se a nova quantidade for maior que zero, atualiza a quantidade do item.
        if ($new_quantity > 0) {
            $_SESSION['cart'][$product_id_to_update]['quantity'] = $new_quantity;
        } else {
            // Se a nova quantidade for zero ou um valor negativo, o item é removido do carrinho.
            // Isso simula o comportamento de remover completamente o item se a quantidade for zerada.
            unset($_SESSION['cart'][$product_id_to_update]);
        }
    }
    
    // Redireciona o navegador de volta para 'cart.php'.
    // Semelhante à remoção, isso garante a atualização da interface e evita reenvios.
    header("Location: cart.php");
    // Encerra a execução do script.
    exit();
}

// --- Contagem de Itens no Carrinho para o Badge do Cabeçalho ---
// Esta seção calcula o número total de produtos (somando as quantidades) no carrinho.
// O resultado é usado para exibir o número no ícone do carrinho no cabeçalho.
// Inicializa a variável que armazenará o número total de itens.
$cart_item_count = 0;
//Garante que $_SESSION['cart'] seja um array vazio se não estiver definido
if (!isset($_SESSION['cart'])) {
     $_SESSION['cart'] = [];
}
// Verifica se o carrinho existe na sessão. Se não existir, o contador permanece 0.
if (isset($_SESSION['cart'])) {
    foreach($_SESSION['cart'] as $item){
        $cart_item_count += $item['quantity'];
    }
}

// --- Variáveis para Mensagens de Erro/Sucesso de Login/Cadastro ---
// Estas variáveis são inicializadas vazias e serão preenchidas com mensagens
// caso ocorram erros ou sucessos durante o processamento de login ou cadastro.
// Elas são usadas para exibir feedback ao usuário nos modais.
$login_error = '';
$register_error = '';
$register_success = '';

//Logica de buscar pedidos do usuário logado
$user_orders = [];//array vazio inicializado
//So busca se estiver o usuario logado
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    // Prepara a consulta SQL para buscar os pedidos do usuário, ordenados do mais recente para o mais antigo.
    // Usamos prepared statements para segurança contra SQL Injection.
    $stmt = $conn->prepare("SELECT id, data_pedido, total, status FROM pedidos WHERE usuario_id = ? ORDER BY data_pedido DESC");
    //'i' indica que o parametro é um inteiro
    $stmt->bind_param("i", $user_id);
    // Executa a consulta SQL.
    $stmt->execute();
    // Obtem os resultados da consulta.
    $result = $stmt->get_result();

    //Se tiver pedido os adiciona no array $user_orders
    if ($result->num_rows > 0) {
        while ($order = $result->fetch_assoc()) {
            $user_orders[] = $order;
        }
        $stmt->close();
    }

}

// Inicializa a variável para o total do carrinho.
$total_carrinho = 0;
// Calcula o total do carrinho iterando sobre os itens na sessão.
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $total_carrinho += $item['preco'] * $item['quantity'];
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
    <title>Meu Carrinho - Kabum Clone</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">Meu Carrinho</h1>

        <?php if (empty($_SESSION['cart'])): ?>
            <div class="alert alert-info" role="alert">
                Seu carrinho está vazio. <a href="index.php">Continue comprando!</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th scope="col">Produto</th>
                            <th scope="col">Preço Unitário</th>
                            <th scope="col">Quantidade</th>
                            <th scope="col">Subtotal</th>
                            <th scope="col">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($_SESSION['cart'] as $product_id => $item): ?>
                            <tr>
                                <td class="align-middle">
                                    <div class="d-flex align-items-center">
                                        <img src="<?php echo htmlspecialchars($item['imagem_url']); ?>" alt="<?php echo htmlspecialchars($item['nome']); ?>" class="img-fluid me-3" style="width: 80px; height: 80px; object-fit: contain;">
                                        <span><?php echo htmlspecialchars($item['nome']); ?></span>
                                    </div>
                                </td>
                                <td class="align-middle">R$<?php echo number_format($item['preco'], 2, ',', '.'); ?></td>
                                <td class="align-middle">
                                    <form action="cart.php" method="POST" class="d-flex align-items-center">
                                        <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                                        <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" class="form-control text-center" style="width: 80px;">
                                        <button type="submit" name="update_quantity" class="btn btn-sm btn-primary ms-2">Atualizar</button>
                                    </form>
                                </td>
                                <td class="align-middle">R$<?php echo number_format($item['preco'] * $item['quantity'], 2, ',', '.'); ?></td>
                                <td class="align-middle">
                                    <a href="cart.php?remove=<?php echo $product_id; ?>" class="btn btn-danger btn-sm">Remover</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end align-items-center mt-3">
                <h4 class="me-3">Total do Carrinho:</h4>
                <h3 class="text-primary">R$<?php echo number_format($total_carrinho, 2, ',', '.'); ?></h3>
            </div>

            <div class="d-flex justify-content-between mt-4">
                <a href="index.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Continuar Comprando</a>
                <a href="checkout.php" class="btn btn-success">Finalizar Compra <i class="bi bi-arrow-right"></i></a>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'HTML/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>