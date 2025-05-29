<?php
include 'config.php';

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
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Suporte - Kabum Clone</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header class="main-header bg-blue py-2">
        <div class="container-fluid d-flex align-items-center justify-content-between">
            <button class="btn text-white" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar" aria-controls="offcanvasNavbar">
                <i class="bi bi-list fs-3"></i>
            </button>

            <a class="navbar-brand text-white fw-bold fs-4 me-auto me-lg-0 ms-3 ms-lg-0" href="index.php">KaBuM!</a>

            <form class="search-bar d-flex flex-grow-1 mx-3 me-lg-auto" method="GET" action="index.php">
                <input type="text" class="form-control rounded-start-pill border-0" placeholder="Busque no KaBuM!" name="search">
                <button class="btn btn-orange rounded-end-circle" type="submit"><i class="bi bi-chevron-right text-white"></i></button>
            </form>

            <div class="d-none d-lg-flex align-items-center gap-3 ms-auto">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <span class="text-white">Olá, <?= htmlspecialchars($_SESSION['user_name']) ?></span>
                    <a href="?logout=true" class="btn btn-sm btn-outline-light">Sair</a>
                <?php else: ?>
                    <a href="login.php" class="text-white text-decoration-none d-flex flex-column align-items-center">
                        <i class="bi bi-person-circle fs-5"></i>
                        <small>ENTRE ou</small>
                        <small>CADASTRE-SE</small>
                    </a>
                <?php endif; ?>
                <a href="cart.php" class="text-white text-decoration-none d-flex flex-column align-items-center position-relative">
                    <i class="bi bi-cart3 fs-5"></i>
                    <small>CARRINHO</small>
                    <span class="badge bg-danger rounded-circle position-absolute top-0 start-100 translate-middle"><?= $cart_item_count ?></span>
                </a>
            </div>

            <div class="d-flex d-lg-none align-items-center gap-3 ms-auto">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <span class="text-white small">Olá, <?= htmlspecialchars($_SESSION['user_name']) ?></span>
                <?php else: ?>
                    <a href="login.php" class="text-white"><i class="bi bi-person-circle fs-4"></i></a>
                <?php endif; ?>
                <a href="cart.php" class="text-white position-relative">
                    <i class="bi bi-cart3 fs-4"></i>
                    <span class="badge bg-danger rounded-circle position-absolute top-0 start-100 translate-middle"><?= $cart_item_count ?></span>
                </a>
            </div>
        </div>
    </header>

    <div class="offcanvas offcanvas-start bg-blue text-white" tabindex="-1" id="offcanvasNavbar" aria-labelledby="offcanvasNavbarLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="offcanvasNavbarLabel">
                <?php if (isset($_SESSION['user_id'])): ?>
                    Olá, <?= htmlspecialchars($_SESSION['user_name']) ?>
                <?php else: ?>
                    Olá. Acesse sua conta
                <?php endif; ?>
            </h5>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body d-flex flex-column">
            <ul class="navbar-nav justify-content-end flex-grow-1 pe-3">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="account.php"><i class="bi bi-person me-2"></i>Minha conta</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="account.php?section=data"><i class="bi bi-file-earmark-person me-2"></i>Meus dados</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="cart.php#meus-pedidos"><i class="bi bi-box-seam me-2"></i>Meus pedidos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="support.php"><i class="bi bi-headset me-2"></i>Atendimento ao cliente</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="#"><i class="bi bi-heart me-2"></i>Favoritos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="#"><i class="bi bi-info-circle me-2"></i>Sobre</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="?logout=true"><i class="bi bi-box-arrow-right me-2"></i>Sair</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="login.php" data-bs-dismiss="offcanvas"><i class="bi bi-person me-2"></i>Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="login.php?action=register" data-bs-dismiss="offcanvas"><i class="bi bi-person-plus me-2"></i>Cadastre-se</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="support.php"><i class="bi bi-headset me-2"></i>Atendimento ao cliente</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="#"><i class="bi bi-info-circle me-2"></i>Sobre</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <main class="container my-4">
        <h2 class="section-title text-center mb-4">Atendimento ao Cliente</h2>

        <div class="card shadow-sm p-4">
            <p>Bem-vindo à nossa central de suporte. Estamos aqui para ajudar!</p>
            <p>Você pode entrar em contato conosco através dos seguintes meios:</p>
            <ul>
                <li>Email: <a href="mailto:suporte@kabumclone.com" class="text-decoration-none">suporte@kabumclone.com</a></li>
                <li>Telefone: (XX) XXXX-XXXX (Segunda a Sexta, das 9h às 18h)</li>
                <li>Chat Online: Disponível de Segunda a Sexta, das 9h às 17h (clique no ícone de chat no canto inferior direito, se houver).</li>
            </ul>
            <p>Ou, se preferir, preencha o formulário abaixo e entraremos em contato o mais breve possível:</p>

            <form>
                <div class="mb-3">
                    <label for="name" class="form-label">Seu Nome</label>
                    <input type="text" class="form-control" id="name" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Seu Email</label>
                    <input type="email" class="form-control" id="email" required>
                </div>
                <div class="mb-3">
                    <label for="subject" class="form-label">Assunto</label>
                    <input type="text" class="form-control" id="subject" required>
                </div>
                <div class="mb-3">
                    <label for="message" class="form-label">Sua Mensagem</label>
                    <textarea class="form-control" id="message" rows="5" required></textarea>
                </div>
                <button type="submit" class="btn btn-orange">Enviar Mensagem</button>
            </form>
        </div>
    </main>

    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-3 col-6">
                    <h5>KaBuM!</h5>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-white-50 text-decoration-none">Quem Somos</a></li>
                        <li><a href="#" class="text-white-50 text-decoration-none">Trabalhe Conosco</a></li>
                        <li><a href="#" class="text-white-50 text-decoration-none">Nossas Lojas</a></li>
                    </ul>
                </div>
                <div class="col-md-3 col-6">
                    <h5>Atendimento</h5>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-white-50 text-decoration-none">Central de Ajuda</a></li>
                        <li><a href="#" class="text-white-50 text-decoration-none">Política de Troca e Devolução</a></li>
                        <li><a href="#" class="text-white-50 text-decoration-none">Prazos de Entrega</a></li>
                    </ul>
                </div>
                <div class="col-md-3 col-6">
                    <h5>Sua Conta</h5>
                    <ul class="list-unstyled">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <li><a href="account.php" class="text-white-50 text-decoration-none">Minha Conta</a></li>
                            <li><a href="cart.php" class="text-white-50 text-decoration-none">Meu Carrinho</a></li>
                            <li><a href="cart.php#meus-pedidos" class="text-white-50 text-decoration-none">Meus Pedidos</a></li>
                        <?php else: ?>
                            <li><a href="login.php" class="text-white-50 text-decoration-none">Login</a></li>
                            <li><a href="login.php?action=register" class="text-white-50 text-decoration-none">Cadastre-se</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="col-md-3 col-6">
                    <h5>Redes Sociais</h5>
                    <ul class="list-unstyled d-flex gap-3">
                        <li><a href="#" class="text-white-50"><i class="bi bi-facebook fs-4"></i></a></li>
                        <li><a href="#" class="text-white-50"><i class="bi bi-instagram fs-4"></i></a></li>
                        <li><a href="#" class="text-white-50"><i class="bi bi-twitter fs-4"></i></a></li>
                    </ul>
                </div>
            </div>
            <div class="text-center mt-3 border-top border-secondary pt-3">
                <p class="mb-0">&copy; <?= date("Y") ?> KaBuM! Clone. Todos os direitos reservados.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>