-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Tempo de geração: 26/05/2025 às 01:08
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `loja`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `categorias`
--

CREATE TABLE `categorias` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `categorias`
--

INSERT INTO `categorias` (`id`, `nome`) VALUES
(1, 'Informática');

-- --------------------------------------------------------

--
-- Estrutura para tabela `itens_pedido`
--

CREATE TABLE `itens_pedido` (
  `id` int(11) NOT NULL,
  `pedido_id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `quantidade` int(11) NOT NULL,
  `preco_unitario` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `itens_pedido`
--

INSERT INTO `itens_pedido` (`id`, `pedido_id`, `produto_id`, `quantidade`, `preco_unitario`) VALUES
(1, 1, 21, 1, 15000.00),
(2, 1, 22, 1, 3500.00);

-- --------------------------------------------------------

--
-- Estrutura para tabela `metodos_pagamento`
--

CREATE TABLE `metodos_pagamento` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `tipo` enum('credito','pix','boleto') NOT NULL DEFAULT 'credito',
  `ultimos_digitos` varchar(4) NOT NULL,
  `bandeira` varchar(50) DEFAULT NULL,
  `token_pagamento` varchar(255) DEFAULT NULL,
  `data_expiracao` varchar(7) DEFAULT NULL,
  `padrao` tinyint(1) DEFAULT 0,
  `chave_pix` varchar(140) DEFAULT NULL,
  `codigo_boleto` varchar(48) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `metodos_pagamento`
--

INSERT INTO `metodos_pagamento` (`id`, `usuario_id`, `tipo`, `ultimos_digitos`, `bandeira`, `token_pagamento`, `data_expiracao`, `padrao`, `chave_pix`, `codigo_boleto`) VALUES
(1, 1, 'credito', '1234', 'Visa', 'some_random_token_1234567890abcdef', '12/2028', 1, NULL, NULL),
(4, 2, 'credito', '1234', 'Visa', 'some_random_token_ABCDEF9876543210', '12/2028', 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `pedidos`
--

CREATE TABLE `pedidos` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `data_pedido` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(50) DEFAULT 'pendente',
  `total` decimal(10,2) NOT NULL,
  `endereco_entrega` text NOT NULL,
  `metodo_pagamento` varchar(50) NOT NULL,
  `observacoes` text DEFAULT NULL,
  `metodo_pagamento_tipo` enum('credito','pix','boleto') DEFAULT NULL,
  `chave_pix` varchar(140) DEFAULT NULL,
  `codigo_boleto` varchar(48) DEFAULT NULL,
  `vencimento_boleto` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `pedidos`
--

INSERT INTO `pedidos` (`id`, `usuario_id`, `data_pedido`, `status`, `total`, `endereco_entrega`, `metodo_pagamento`, `observacoes`, `metodo_pagamento_tipo`, `chave_pix`, `codigo_boleto`, `vencimento_boleto`) VALUES
(1, 2, '2025-05-25 21:01:40', 'pendente', 18500.00, '', '', '', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `produtos`
--

CREATE TABLE `produtos` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `preco` decimal(10,2) NOT NULL,
  `imagem_url` varchar(255) DEFAULT NULL,
  `categoria_id` int(11) NOT NULL,
  `estoque` int(11) DEFAULT 0,
  `descricao` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `produtos`
--

INSERT INTO `produtos` (`id`, `nome`, `preco`, `imagem_url`, `categoria_id`, `estoque`, `descricao`) VALUES
(21, 'Placa de Vídeo RTX 4090', 15000.00, 'img/rtx4090.jpg', 1, 9, 'A mais potente placa de vídeo para jogos.'),
(22, 'Processador Intel i9-14900K', 3500.00, 'img/i914900k.jpg', 1, 14, 'Processador de última geração para alto desempenho.'),
(23, 'SSD NVMe 2TB Samsung 990 Pro', 1200.00, 'img/ssd2tb.jpg', 1, 20, 'Armazenamento ultra-rápido para seu PC.'),
(24, 'Monitor Gamer Dell Alienware AW2725QF', 6000.00, 'img/aw2725qf.jpg', 1, 5, 'Monitor OLED de 27 polegadas, 360Hz.');

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `data_cadastro` timestamp NOT NULL DEFAULT current_timestamp(),
  `endereco` text NOT NULL,
  `telefone` varchar(20) NOT NULL DEFAULT '',
  `cidade` varchar(100) NOT NULL DEFAULT '',
  `estado` varchar(100) NOT NULL DEFAULT '',
  `cep` varchar(10) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha`, `data_cadastro`, `endereco`, `telefone`, `cidade`, `estado`, `cep`) VALUES
(1, 'George', 'george@example.com', '$2y$10$Qj2w6rWl7r.N2d2g3r.B4u7l.A5g6h.H7j8k9L0m1n2o3p4q5r6s7t8u9v0w1x2y3z4', '2025-05-25 19:29:00', 'Rua Principal, 123', '11987654321', 'São Paulo', 'SP', '01000-000'),
(2, 'George tunes', 'george@gmail.com', '$2y$10$Rc8lWHZ2oUxH2FE/i3CO5O1j5XaP1gxNhLNVjFSOHQmLv6ZEeG/4W', '2025-05-25 19:47:41', 'Rua Vasco da silvas,1', '5197194545948495945', 'Laejado', 'Rio grand edo sul', '95900000'),
(3, 'luis', 'luis@gmail.com', '$2y$10$lPy77ZPCVT85XYZZjYWg4uEyYN.mjqElnwosbqs7JYrm05d0kOyH6', '2025-05-25 21:41:46', 'Rua Sete de Setembro, 150', '', '', '', '');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nome` (`nome`);

--
-- Índices de tabela `itens_pedido`
--
ALTER TABLE `itens_pedido`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pedido_id` (`pedido_id`),
  ADD KEY `produto_id` (`produto_id`);

--
-- Índices de tabela `metodos_pagamento`
--
ALTER TABLE `metodos_pagamento`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token_pagamento` (`token_pagamento`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `pedidos`
--
ALTER TABLE `pedidos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `produtos`
--
ALTER TABLE `produtos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `categoria_id` (`categoria_id`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `itens_pedido`
--
ALTER TABLE `itens_pedido`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `metodos_pagamento`
--
ALTER TABLE `metodos_pagamento`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `pedidos`
--
ALTER TABLE `pedidos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `produtos`
--
ALTER TABLE `produtos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `itens_pedido`
--
ALTER TABLE `itens_pedido`
  ADD CONSTRAINT `itens_pedido_ibfk_1` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`),
  ADD CONSTRAINT `itens_pedido_ibfk_2` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`);

--
-- Restrições para tabelas `metodos_pagamento`
--
ALTER TABLE `metodos_pagamento`
  ADD CONSTRAINT `metodos_pagamento_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `pedidos`
--
ALTER TABLE `pedidos`
  ADD CONSTRAINT `pedidos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `produtos`
--
ALTER TABLE `produtos`
  ADD CONSTRAINT `produtos_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
