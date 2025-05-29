<?php
// Este arquivo contém o cabeçalho HTML que será incluído em todas as páginas.
// Ele deve ter acesso a $cart_item_count, $_SESSION['user_name'], etc.
// Lógica para obter a contagem de itens no carrinho para o badge
$cart_item_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_item_count += $item['quantity'];
    }
}
?>
<header class="main-header bg-blue py-2">
    <div class="container-fluid d-flex align-items-center justify-content-between">
        <button class="btn text-white d-md-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar" aria-controls="offcanvasNavbar">
            <i class="bi bi-list fs-3"></i> </button>

        <a class="navbar-brand text-white d-flex align-items-center" href="index.php">
            <img src="https://logodownload.org/wp-content/uploads/2017/05/kabum-logo-2.png" alt="Logo Kabum" style="height: 30px;" class="me-2">
            Kabum Clone
        </a>

        <div class="flex-grow-1 mx-md-4 d-none d-md-flex justify-content-center">
            <form class="d-flex w-100" action="index.php" method="GET">
                <input class="form-control me-2" type="search" placeholder="Buscar produtos..." aria-label="Search" name="search" value="<?php echo htmlspecialchars($search_query ?? ''); ?>">
                <button class="btn btn-warning" type="submit"><i class="bi bi-search"></i></button>
            </form>
        </div>

        <div class="d-flex align-items-center">
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="dropdown">
                    <a class="btn text-white dropdown-toggle" href="#" role="button" id="dropdownUser" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-fill me-1"></i> <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownUser">
                        <li><a class="dropdown-item" href="account.php"><i class="bi bi-person-circle me-2"></i> Minha Conta</a></li>
                        <li><a class="dropdown-item" href="cart.php"><i class="bi bi-cart-fill me-2"></i> Meu Carrinho</a></li>
                        <li><hr class="dropdown-divider"></li>
                        
                        <li><a class="dropdown-item" href="?logout=true"><i class="bi bi-box-arrow-right me-2"></i> Sair</a></li>
                    </ul>
                </div>
            <?php else: ?>
                <a href="login.php" class="btn text-white me-2">
                    <i class="bi bi-person-fill"></i> Entrar / Cadastrar
                </a>
            <?php endif; ?>

            <a href="cart.php" class="btn text-white position-relative">
                <i class="bi bi-cart-fill fs-5"></i>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                    <?php echo $cart_item_count ?? 0; ?> <span class="visually-hidden">itens no carrinho</span>
                </span>
            </a>
        </div>
    </div>

    <div class="offcanvas offcanvas-start bg-dark text-white" tabindex="-1" id="offcanvasNavbar" aria-labelledby="offcanvasNavbarLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="offcanvasNavbarLabel">Menu</h5>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <ul class="navbar-nav justify-content-end flex-grow-1 pe-3">
                <li class="nav-item">
                    <a class="nav-link text-white active" aria-current="page" href="index.php">Início</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="products.php">Produtos</a> </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="account.php">Minha Conta</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="cart.php">Meu Carrinho</a>
                </li>
                <li class="nav-item mt-3">
                    <form class="d-flex" action="index.php" method="GET">
                        <input class="form-control me-2" type="search" placeholder="Buscar produtos..." aria-label="Search" name="search">
                        <button class="btn btn-warning" type="submit"><i class="bi bi-search"></i></button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
    
</header>