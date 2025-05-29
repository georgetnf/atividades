<?php

include 'config.php';

// Obtém o ID do pedido da URL, se estiver definido, caso contrário, define como null.
$order_id = $_GET['order_id'] ?? null;
$order_details = null;
$order_items = [];

// Buscar detalhes do pedido se um ID for fornecido
if ($order_id) {
    //Ajuste para nomes de colunas 'data_pedido', 'total', 'usuario_id'
    $stmt_order = $conn->prepare("SELECT id, data_pedido, total, status, usuario_id FROM pedidos WHERE id = ?");
    $stmt_order->bind_param("i", $order_id);
    $stmt_order->execute();
    $result_order = $stmt_order->get_result();

    if ($result_order->num_rows>0) {
        $order_details = $result_order->fetch_assoc();

        // Buscar itens do pedido
        //Ajuste para nomes de tabelas 'itens_pedido', 'produtos' e colunas 'quantidade', 'preco_unitario', 'produto_id', 'pedido_id'
        $stmt_items = $conn->prepare("SELECT oi.quantidade, oi.preco_unitario, p.nome FROM itens_pedido oi JOIN produtos p ON oi.produto_id = p.id WHERE oi.pedido_id = ?");
        $stmt_items->bind_param("i", $order_id);
        $stmt_items->execute();
        $result_items = $stmt_items->get_result();
        while ($item = $result_items->fetch_assoc()) {
            $order_items[] = $item;
        }
        $stmt_items->close();

    }
    $stmt_order->close();

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

include 'HTML/header.php';

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pedido Confirmado - Kabum Clone</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container mt-5">
        <div class="card shadow-sm">
            <div class="card-body text-center">
                <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                <h1 class="card-title text-success mt-3">Pedido Confirmado!</h1>
                <p class="card-text lead">Obrigado por sua compra.</p>

                <?php if ($order_details): ?>
                    <div class="mt-4 text-start">
                        <h4>Detalhes do Pedido #<?php echo htmlspecialchars($order_details['id']); ?></h4>
                        <ul class="list-group list-group-flush mb-3">
                            <li class="list-group-item"><strong>Data do Pedido:</strong> <?php echo date('d/m/Y H:i', strtotime($order_details['data_pedido'])); ?></li>
                            <li class="list-group-item"><strong>Total:</strong> R$<?php echo number_format($order_details['total'], 2, ',', '.'); ?></li>
                            <li class="list-group-item"><strong>Status:</strong> <?php echo htmlspecialchars($order_details['status']); ?></li>
                        </ul>

                        <h5>Itens Comprados:</h5>
                        <ul class="list-group list-group-flush">
                            <?php if (empty($order_items)): ?>
                                <li class="list-group-item">Nenhum item encontrado para este pedido.</li>
                            <?php else: ?>
                                <?php foreach ($order_items as $item): ?>
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span><?php echo htmlspecialchars($item['nome']); ?> x <?php echo $item['quantidade']; ?></span>
                                        <span>R$<?php echo number_format($item['preco_unitario'], 2, ',', '.'); ?></span>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>

                    <?php
                    // Lógica para exibir instruções Pix/Boleto se o método de pagamento for um desses.
                    // Isso exigiria que o process_order.php passasse o payment_type para order_success.php ou que order_success.php buscasse o método de pagamento.
                    // Para simplificar, vou assumir que você pode passar um parâmetro adicional ou buscar essa informação aqui.
                    // Por exemplo, você pode adicionar 'payment_type' na URL de redirecionamento do process_order.php.
                    $payment_type_confirmation = $_GET['payment_type'] ?? null;
                    if ($payment_type_confirmation === 'pix' && !empty($order_details['chave_pix'])) { // Supondo que a chave pix possa ser acessada via $order_details
                        echo '<div class="alert alert-info mt-4"><strong>Informações para Pix:</strong> Realize o pagamento para a chave Pix: <strong>' . htmlspecialchars($order_details['chave_pix']) . '</strong>. O pedido será processado após a confirmação do pagamento.</div>';
                    } elseif ($payment_type_confirmation === 'boleto' && !empty($order_details['codigo_boleto'])) { // Supondo que o código do boleto possa ser acessado via $order_details
                        echo '<div class="alert alert-info mt-4"><strong>Informações para Boleto:</strong> Copie o código de barras do boleto: <strong>' . htmlspecialchars($order_details['codigo_boleto']) . '</strong> e realize o pagamento. O pedido será processado após a compensação.</div>';
                    }
                    ?>

                <?php else: ?>
                    <div class="alert alert-warning mt-4" role="alert">
                        Não foi possível encontrar os detalhes do seu pedido.
                    </div>
                <?php endif; ?>

                <a href="index.php" class="btn btn-primary mt-4">Voltar para a Página Inicial</a>
            </div>
        </div>
    </div>

    <?php include 'HTML/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>