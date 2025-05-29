<?php 

include 'config.php';

// Redireciona o usuário para a página de login se ele não estiver logado.
// Um pedido só pode ser processado por um usuário autenticado.
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Redireciona o usuário para a página do carrinho se o carrinho estiver vazio.
// Não é possível processar um pedido sem itens no carrinho.
if (empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit(); // Encerra o script.
}

$user_id = $_SESSION['user_id'];
$total_pedido = $_POST['total_geral'] ?? 0;

// Este valor será usado tanto para 'metodo_pagamento' (string) quanto para 'metodo_pagamento_tipo' (enum)
$payment_type_enum = $_POST['payment_type'] ?? 'pix'; // Pega 'pix', 'boleto' ou 'credito' do rádio button no checkout.php

//Mapear o tipo de pagamento para um nome mais legível para a coluna 'metodo_pagamento' (VARCHAR)
$metodo_pagamento_nome = '';
switch ($payment_type_enum) {
    case 'pix':
        $metodo_pagamento_nome = 'PIX';
        break;
    case 'boleto':
        $metodo_pagamento_nome = 'Boleto Bancário';
        break;
    case 'credito':
        $metodo_pagamento_nome = 'Cartão de Crédito';
        break;
    default:
        $metodo_pagamento_nome = 'Desconhecido';
        break;
}

//Coletar todos os dados de endereço do formulário de checkout.php
// e concatená-los em uma única string para a coluna 'endereco_entrega' (VARCHAR(255) NOT NULL)
$nome_entrega_form = $_POST['nome'] ?? '';
$email_entrega_form = $_POST['email'] ?? '';
$endereco_rua_form = $_POST['endereco'] ?? ''; 
$cidade_entrega_form = $_POST['cidade'] ?? '';
$estado_entrega_form = $_POST['estado'] ?? '';
$cep_entrega_form = $_POST['cep'] ?? '';
$telefone_entrega_form = $_POST['telefone'] ?? '';

// Concatena todos os campos de endereço em uma única string.
// Garante que a string não seja vazia mesmo que alguns campos sejam.
// A coluna `endereco_entrega` é VARCHAR(255) NOT NULL na sua tabela.
$endereco_entrega_completo = "Nome: " . (empty($nome_entrega_form) ? 'N/A' : $nome_entrega_form) . "\n"
                           . "Email: " . (empty($email_entrega_form) ? 'N/A' : $email_entrega_form) . "\n"
                           . "Endereço: " . (empty($endereco_rua_form) ? 'N/A' : $endereco_rua_form) . "\n"
                           . "Cidade: " . (empty($cidade_entrega_form) ? 'N/A' : $cidade_entrega_form) . "\n"
                           . "Estado: " . (empty($estado_entrega_form) ? 'N/A' : $estado_entrega_form) . "\n"
                           . "CEP: " . (empty($cep_entrega_form) ? 'N/A' : $cep_entrega_form) . "\n"
                           . "Telefone: " . (empty($telefone_entrega_form) ? 'N/A' : $telefone_entrega_form);

// Remove espaços extras e quebras de linha para garantir que a string não ultrapasse VARCHAR(255)
// e seja mais limpa.
$endereco_entrega_completo = trim(str_replace(["\n", "\r"], ", ", $endereco_entrega_completo));
// Se a string ainda for muito longa, você pode truncá-la ou gerar um erro.
// Por exemplo: $endereco_entrega_completo = substr($endereco_entrega_completo, 0, 255);


// --- LINHAS DE DEBUG (MANTENHA PARA TESTE, REMOVA DEPOIS DE RESOLVER O ERRO) ---
error_log("DEBUG PROCESS_ORDER: Conteúdo de _POST: " . print_r($_POST, true));
error_log("DEBUG PROCESS_ORDER: total_pedido = " . $total_pedido);
error_log("DEBUG PROCESS_ORDER: payment_type_enum = " . $payment_type_enum);
error_log("DEBUG PROCESS_ORDER: metodo_pagamento_nome = " . $metodo_pagamento_nome);
error_log("DEBUG PROCESS_ORDER: endereco_entrega_completo = " . $endereco_entrega_completo);
// --- FIM LINHAS DE DEBUG ---


// Validar se o total é maior que zero
if ($total_pedido <= 0) {
    // Redireciona para o checkout com mensagem de erro
    header("Location: checkout.php?error=total_invalido&message=" . urlencode("O total do pedido é inválido."));
    exit();
}

// Inicia uma transação para garantir a integridade dos dados
$conn->begin_transaction();

try {
    // A coluna `status` na sua tabela é NULL com padrão 'pendente', mas vamos defini-lo explicitamente.
    // As colunas `chave_pix`, `codigo_boleto`, `vencimento_boleto` são NULL.
    $status_inicial = 'pendente'; 
    $chave_pix_para_sucesso = null;
    $codigo_boleto_para_sucesso = null;
    $vencimento_boleto_para_sucesso = null;

    // Ajusta status e gera dados específicos para Pix/Boleto, se necessário
    if ($payment_type_enum === 'pix') {
        $status_inicial = 'pendente_pix';
        // Gerar uma chave PIX de exemplo ou usar uma estática
        // Em um ambiente real, você geraria um QR Code ou chave dinâmica aqui.
        $chave_pix_para_sucesso = "pix_exemplo_123456789"; 
    } elseif ($payment_type_enum === 'boleto') {
        $status_inicial = 'pendente_boleto';
        // Gerar um código de boleto de exemplo e data de vencimento
        // Em um ambiente real, você integraria com um gerador de boleto.
        $codigo_boleto_para_sucesso = "00099.99999 11111.111111 22222.222222 3 00000000000000";
        $vencimento_boleto_para_sucesso = date('Y-m-d', strtotime('+3 days'));
    } elseif ($payment_type_enum === 'credito') {
        $status_inicial = 'processando_cartao'; // Para cartão de crédito, status inicial enquanto processa
        // Em um ambiente real, chamaria a API do gateway de pagamento aqui.
    }

    //Consulta INSERT atualizada conforme a sua tabela 'pedidos'
    // Ordem das colunas: usuario_id, data_pedido, total, status, endereco_entrega, metodo_pagamento, metodo_pagamento_tipo, chave_pix, codigo_boleto, vencimento_boleto
    // data_pedido é NOW() no SQL, então não precisa ser passada no bind_param.
    $sql_insert_pedido = "INSERT INTO pedidos (usuario_id, data_pedido, total, status, endereco_entrega, metodo_pagamento, metodo_pagamento_tipo, chave_pix, codigo_boleto, vencimento_boleto)
                          VALUES (?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt_pedido = $conn->prepare($sql_insert_pedido);

    // Tipos: i (int) para usuario_id, d (decimal) para total, s (string) para status,
    // s (string) para endereco_entrega, s (string) para metodo_pagamento (nome legível),
    // s (string) para metodo_pagamento_tipo (enum), s (string) para chave_pix,
    // s (string) para codigo_boleto, d (date) para vencimento_boleto
    /*$stmt_pedido->bind_param("idssssssss", 
        $user_id, 
        $total_pedido, 
        $status_inicial,
        $endereco_entrega_completo, // String concatenada para 'endereco_entrega'
        $metodo_pagamento_nome,      // Nome legível do método de pagamento (ex: "PIX")
        $payment_type_enum,          // Tipo ENUM do método de pagamento (ex: "pix")
        $chave_pix_para_sucesso,     // Chave Pix (pode ser null)
        $codigo_boleto_para_sucesso, // Código do boleto (pode ser null)
        $vencimento_boleto_para_sucesso // Vencimento do boleto (pode ser null)
    );*/

          $stmt_pedido->bind_param("idsssssss", // Corrigido de "idssssssss" para "idsssssss" (9 caracteres)
            $user_id,
            $total_pedido,
            $status_inicial,
            $endereco_entrega_completo,
            $metodo_pagamento_nome,
            $payment_type_enum,
            $chave_pix_para_sucesso,
            $codigo_boleto_para_sucesso,
            $vencimento_boleto_para_sucesso // Isso é 9ª variável
        );
    
    // Executa a inserção do pedido.
    if (!$stmt_pedido->execute()) {
        throw new Exception("Erro ao inserir pedido: " . $stmt_pedido->error);
    }
    // Obtém o ID do pedido recém-inserido.
    $order_id = $conn->insert_id;
    $stmt_pedido->close();

    // Inserir os Itens do Pedido na Tabela 'itens_pedido'
    // Itera sobre cada item no carrinho da sessão.
    foreach ($_SESSION['cart'] as $item) {
        // Prepara a consulta SQL para inserir cada item do pedido.
        $stmt_item = $conn->prepare("INSERT INTO itens_pedido (pedido_id, produto_id, quantidade, preco_unitario) VALUES (?, ?, ?, ?)");
        // Vincula os parâmetros: ID do pedido (inteiro), ID do produto (inteiro), quantidade (inteiro), preço unitário (decimal).
        // Certifique-se que $item['id'], $item['quantity'] e $item['preco'] estão corretos
        $stmt_item->bind_param("iiid", $order_id, $item['id'], $item['quantity'], $item['preco']);
        // Executa a inserção do item.
        if (!$stmt_item->execute()) {
            throw new Exception("Erro ao inserir item do pedido: " . $stmt_item->error);
        }
        $stmt_item->close(); 
    }

    // Confirma a transação se tudo correu bem.
    $conn->commit();

    // Limpa o carrinho da sessão após o pedido ser processado com sucesso.
    unset($_SESSION['cart']);

    //Redireciona para a página de sucesso, passando os dados relevantes.
    $redirect_url = "order_success.php?order_id=" . $order_id . "&payment_type=" . urlencode($payment_type_enum);
    if ($chave_pix_para_sucesso) {
        $redirect_url .= "&pix_code=" . urlencode($chave_pix_para_sucesso);
    } 
    if ($codigo_boleto_para_sucesso) {
        $redirect_url .= "&boleto_code=" . urlencode($codigo_boleto_para_sucesso);
    }
    if ($vencimento_boleto_para_sucesso) {
        $redirect_url .= "&vencimento_boleto=" . urlencode($vencimento_boleto_para_sucesso);
    }

    header("Location: " . $redirect_url);
    exit();
    
} catch (Exception $e) {
    // Em caso de qualquer erro, reverte a transação.
    $conn->rollback();
    // Loga o erro para depuração (útil em ambientes de produção)
    error_log("Erro ao processar pedido: " . $e->getMessage());
    // Redireciona de volta para o checkout com uma mensagem de erro detalhada.
    header("Location: checkout.php?error=processamento_falhou&message=" . urlencode($e->getMessage()));
    exit();
}


?>