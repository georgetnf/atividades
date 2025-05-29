<?php
// Este arquivo contém o rodapé HTML que será incluído em todas as páginas.
?>
<footer class="main-footer bg-dark text-white-50 py-4 mt-5">
    <div class="container text-center">
        <div class="row">
            <div class="col-md-4 mb-3">
                <h5>Sobre Nós</h5>
                <p>Somos um clone da Kabum, oferecendo os melhores produtos de tecnologia com preços incríveis.</p>
            </div>
            <div class="col-md-4 mb-3">
                <h5>Links Úteis</h5>
                <ul class="list-unstyled">
                    <li><a href="index.php" class="text-white-50 text-decoration-none">Início</a></li>
                    <li><a href="account.php" class="text-white-50 text-decoration-none">Minha Conta</a></li>
                    <li><a href="cart.php" class="text-white-50 text-decoration-none">Carrinho</a></li>
                    <li><a href="support.php" class="text-white-50 text-decoration-none">Suporte</a></li>
                </ul>
            </div>
            <div class="col-md-4 mb-3">
                <h5>Contato</h5>
                <ul class="list-unstyled">
                    <li><i class="bi bi-envelope-fill me-2"></i> contato@kabumclone.com</li>
                    <li><i class="bi bi-telephone-fill me-2"></i> (XX) XXXX-XXXX</li>
                    <li><i class="bi bi-geo-alt-fill me-2"></i> Rua Fictícia, 123 - Cidade, Estado</li>
                </ul>
                <div class="mt-3">
                    <a href="#" class="text-white-50 me-3"><i class="bi bi-facebook fs-4"></i></a>
                    <a href="#" class="text-white-50 me-3"><i class="bi bi-instagram fs-4"></i></a>
                    <a href="#" class="text-white-50"><i class="bi bi-twitter fs-4"></i></a>
                </div>
            </div>
        </div>
        <hr class="bg-white-50">
        <p class="mb-0">&copy; <?php echo date('Y'); ?> Kabum Clone. Todos os direitos reservados.</p>
    </div>
</footer>