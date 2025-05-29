<?php
include 'config.php';

// Verifica se o ID do produto foi passado na URL
// Se não houver ID, o script encerra e exibe uma mensagem de erro.
if (!isset($_GET['id'])) {
    die("Produto não especificado.");
}

// Converte o ID do produto para um inteiro.
// Isso é uma medida de segurança para garantir que o ID seja um número válido.
$id = intval($_GET['id']);

// Consulta o produto
// Prepara uma consulta SQL para buscar os detalhes do produto e sua categoria.
// Usar prepared statements previne SQL Injection.
$sql = "SELECT p.*, c.nome AS categoria
        FROM produtos p
        JOIN categorias c ON p.categoria_id = c.id
        WHERE p.id = ?"; // Ponto e vírgula adicionado aqui

// Prepara a declaração SQL para execução.
$stmt = $conn->prepare($sql);
// Vincula o parâmetro (ID do produto) à declaração. 'i' indica que é um inteiro.
$stmt->bind_param("i", $id);
// Executa a declaração preparada.
$stmt->execute();
// Obtém o resultado da consulta.
$result = $stmt->get_result();

// Verifica se o produto foi encontrado no banco de dados.
// Se não houver resultados, o script encerra e exibe uma mensagem.
if ($result->num_rows === 0) {
    die("Produto não encontrado");
}

// Obtém os dados do produto como um array associativo.
$produto = $result->fetch_assoc();
$stmt->close(); // Fecha a declaração preparada após obter os dados.

// --- Lógica de Adicionar ao Carrinho ---
// Este bloco é executado quando o usuário clica no botão "Adicionar ao Carrinho" nesta página.
// Ele adiciona o produto exibido atualmente à sessão do carrinho.
if (isset($_POST['add_to_cart'])) {
    $product_id = intval($_POST['product_id']);
    $quantity = 1; // Quantidade padrão

    // Inicializa o array do carrinho na sessão se ele ainda não existir.
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // Verifica se o produto já está no carrinho.
    if (array_key_exists($product_id, $_SESSION['cart'])) {
        $_SESSION['cart'][$product_id]['quantity'] += $quantity;
    } else {
        // Se não, adiciona o produto ao carrinho com suas informações e quantidade.
        // As informações do produto já estão disponíveis na variável $produto.
        $_SESSION['cart'][$product_id] = [
            'id' => $produto['id'],
            'nome' => $produto['nome'],
            'preco' => $produto['preco'],
            'imagem_url' => $produto['imagem_url'],
            'quantity' => $quantity
        ];
    }
    // Redireciona para a própria página do produto para evitar reenvio do formulário
    header("Location: product.php?id=" . $product_id);
    exit();
}

// Cálculo do preço original e desconto para exibição
$preco_original_produto = $produto['preco'] * 1.1;
$desconto_percentual_produto = round((($preco_original_produto - $produto['preco']) / $preco_original_produto) * 100);

// --- Contagem de Itens no Carrinho para o Badge do Cabeçalho ---
// Calcula o número total de itens (somando as quantidades) atualmente no carrinho.
// Este valor é exibido no badge do ícone do carrinho no cabeçalho.
$cart_item_count_product_page = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_item_count_product_page += $item['quantity'];
    }
}

// Variáveis para mensagens de erro/sucesso de login/cadastro.
// Estas variáveis são inicializadas vazias e não serão mais preenchidas nesta página,
// pois a lógica foi movida para 'login.php'.
$login_error = '';
$register_error = '';
$register_success = '';

include 'HTML/header.php';

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($produto['nome']); ?> - Kabum Clone</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-6">
                <img src="<?php echo htmlspecialchars($produto['imagem_url']); ?>" class="img-fluid rounded shadow-sm" alt="<?php echo htmlspecialchars($produto['nome']); ?>">
            </div>
            <div class="col-md-6">
                <span class="badge bg-secondary mb-2"><?php echo htmlspecialchars($produto['categoria']); ?></span>
                <h1 class="display-5 fw-bold"><?php echo htmlspecialchars($produto['nome']); ?></h1>
                <p class="lead text-muted"><?php echo htmlspecialchars($produto['descricao']); ?></p>

                <hr>

                <p class="text-decoration-line-through text-muted fs-5 mb-0">De: R$<?php echo number_format($preco_original_produto, 2, ',', '.'); ?></p>
                <p class="text-success fw-bold fs-4 mb-2"><?php echo $desconto_percentual_produto; ?>% OFF</p>
                <h2 class="text-primary fw-bold mb-4">Por: R$<?php echo number_format($produto['preco'], 2, ',', '.'); ?></h2>
                
                <form action="product.php?id=<?php echo $produto['id']; ?>" method="POST" class="d-grid gap-2">
                    <input type="hidden" name="product_id" value="<?php echo $produto['id']; ?>">
                    <button type="submit" name="add_to_cart" class="btn btn-warning btn-lg">
                        <i class="bi bi-cart-plus-fill"></i> Adicionar ao Carrinho
                    </button>
                    <a href="checkout.php" class="btn btn-outline-success btn-lg">
                        <i class="bi bi-bag-fill"></i> Comprar Agora
                    </a>
                </form>

                <div class="mt-4">
                    <h5>Disponibilidade:</h5>
                    <?php if ($produto['estoque'] > 0): ?>
                        <p class="text-success fw-bold">Em estoque (<?php echo $produto['estoque']; ?> unidades)</p>
                    <?php else: ?>
                        <p class="text-danger fw-bold">Produto esgotado</p>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>

    <?php include 'HTML/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>