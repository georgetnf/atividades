<?php
// Inclui a conexão com o banco de dados
include 'config.php';

// Inicializa variáveis para mensagens de erro/sucesso de login/cadastro.
// Estas variáveis não serão mais preenchidas nesta página, pois a lógica foi movida para 'login.php'.
$login_error = '';
$register_error = '';
$register_success = '';

// --- Lógica de Adicionar ao Carrinho ---
// Este bloco é executado quando o usuário clica no botão "Adicionar ao Carrinho" em um produto.
// Ele adiciona o produto à sessão do carrinho
if (isset($_POST['add_to_cart'])) {
    $product_id = intval($_POST['product_id']); // Obtém e sanitiza o ID do produto.
    $quantity = 1; // Define a quantidade padrão como 1.
    
    // Inicializa o array do carrinho na sessão se ele ainda não existir.
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // Verifica se o produto já está no carrinho.
    if (array_key_exists($product_id, $_SESSION['cart'])) {
        // Se sim, apenas incrementa a quantidade.
        $_SESSION['cart'][$product_id]['quantity'] += $quantity;
    } else {
        // Se não, busca as informações completas do produto no banco de dados para adicioná-lo ao carrinho.
        $sql_product = "SELECT id, nome, preco, imagem_url FROM produtos WHERE id = ?";
        $stmt_product = $conn->prepare($sql_product);
        $stmt_product->bind_param("i", $product_id);
        $stmt_product->execute();
        $result_product = $stmt_product->get_result();

        if ($result_product->num_rows > 0) {
            $product_data = $result_product->fetch_assoc();
            // Adiciona o produto ao carrinho com suas informações e quantidade.
            $_SESSION['cart'][$product_id] = [
                'id' => $product_data['id'],
                'nome' => $product_data['nome'],
                'preco' => $product_data['preco'],
                'imagem_url' => $product_data['imagem_url'],
                'quantity' => $quantity
            ];
        }
        $stmt_product->close(); // Fecha a declaração preparada.
    }
    //Redireciona para a própria página para atualizar o badge do carrinho e evitar reenvio do formulário
    header("Location: index.php");
    exit();
}

// --- Contagem de Itens no Carrinho para o Badge do Cabeçalho ---
// Calcula o número total de itens (somando as quantidades) atualmente no carrinho.
// Este valor é exibido no badge do ícone do carrinho no cabeçalho.
$cart_item_count = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_item_count += $item['quantity'];
    }
}

// --- Lógica de Pesquisa de Produtos ---
// Este bloco processa a busca de produtos com base no termo digitado pelo usuário.
// Inicializa a variável para o termo de busca e a cláusula WHERE da consulta SQL.
$search_query = "";
$sql_where = "";
// Verifica se um termo de busca foi enviado via método GET.
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_query = sanitize_input($conn, $_GET['search']);
    // Constrói a cláusula WHERE para filtrar produtos por nome ou categoria.
    $sql_where = "WHERE (p.nome LIKE '%" . $search_query . "%' OR c.nome LIKE '%" . $search_query . "%')";
}

// Consulta todos os produtos com suas categorias, aplicando o filtro de pesquisa se houver.
$sql = "SELECT p.id, p.nome, p.preco, p.imagem_url, c.nome AS categoria
        FROM produtos p
        JOIN categorias c ON p.categoria_id = c.id " . $sql_where;
$result = $conn->query($sql);//Consulta no SQL

// Inclui o cabeçalho do site.
include 'HTML/header.php';

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kabum Clone</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4 text-center">Nossos Produtos</h1>

        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php
            // Verifica se a consulta retornou resultados
            if ($result->num_rows > 0) {
                // Itera sobre cada linha de resultado (cada produto)
                while ($produto = $result->fetch_assoc()) {
                    // Calcula o preço original com 10% de acréscimo para simular um desconto
                    $preco_original = $produto['preco'] * 1.1;
                    // Calcula o percentual de desconto
                    $desconto_percentual = round((($preco_original - $produto['preco']) / $preco_original) * 100);
                    ?>
                    <div class="col">
                        <div class="card h-100 shadow-sm">
                            <a href="product.php?id=<?php echo $produto['id']; ?>">
                                <img src="<?php echo htmlspecialchars($produto['imagem_url']); ?>" class="card-img-top p-3" alt="<?php echo htmlspecialchars($produto['nome']); ?>" style="height: 200px; object-fit: contain;">
                            </a>
                            <div class="card-body d-flex flex-column">
                                <span class="badge bg-secondary mb-2 align-self-start"><?php echo htmlspecialchars($produto['categoria']); ?></span>
                                <h5 class="card-title"><?php echo htmlspecialchars($produto['nome']); ?></h5>
                                <p class="text-decoration-line-through text-muted mb-0">R$<?php echo number_format($preco_original, 2, ',', '.'); ?></p>
                                <p class="text-success fw-bold mb-2"><?php echo $desconto_percentual; ?>% OFF</p>
                                <h4 class="text-primary fw-bold mb-3">R$<?php echo number_format($produto['preco'], 2, ',', '.'); ?></h4>
                                <form action="index.php" method="POST" class="mt-auto">
                                    <input type="hidden" name="product_id" value="<?php echo $produto['id']; ?>">
                                    <button type="submit" name="add_to_cart" class="btn btn-warning w-100">
                                        <i class="bi bi-cart-plus"></i> Adicionar ao Carrinho
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            } else {
                // Mensagem se nenhum produto for encontrado
                echo '<div class="col-12"><p class="text-center">Nenhum produto encontrado.</p></div>';
            }
            ?>
        </div>
    </div>

    <?php include 'HTML/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>