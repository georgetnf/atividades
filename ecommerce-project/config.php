<?php
// Habilita a exibição de todos os erros PHP (para depuração)
// Garante que os erros PHP serão exibidos no navegador.
ini_set('display_errors', 1);
//Garante que erros que ocorrem durante a inicialização do PHP também serão exibidos.
ini_set('display_startup_errors', 1);
//Configura o PHP para reportar todos os tipos de erros.
error_reporting(E_ALL);




//Inicia ou retoma uma sessão PHP. Deve ser a primeira coisa no script.
session_start();

//Config banco de dados
$host = "localhost"; // Host do MySQL (XAMPP usa localhost)
$usuario = "root"; // Usuário padrão do XAMPP
$senha = ""; // Senha padrão do XAMPP (vazio)
$banco = "loja"; // Nome do banco criado no PHPMyAdmin

//Conexão com MySQL usando mysqli
$conn = new mysqli($host, $usuario, $senha, $banco);

//Verificação do erro de conexão
if ($conn->connect_error) {
    die("Erro de conexão: ". $conn->connect_error);
}

// Define o conjunto de caracteres para UTF-8
$conn->set_charset("utf8mb4");
// --- Função para Sanitizar Entradas (Inputs) ---
// Esta função é fundamental para a segurança da sua aplicação.
// Ela limpa os dados recebidos de formulários e URLs, removendo caracteres indesejados
// e protegendo contra ataques como SQL Injection e Cross-Site Scripting (XSS).
function sanitize_input($conn, $data) {
    // Remove espaços em branco (ou outros caracteres pré-definidos) do início e do final da string.
    $data = trim($data);
    // Remove barras invertidas de uma string (útil para dados que vêm com magic_quotes_gpc ativado, embora raro hoje).
    $data = stripslashes($data);
    // Converte caracteres especiais em entidades HTML.
    // Isso evita que códigos maliciosos (como scripts) sejam executados no navegador do usuário.
    $data = htmlspecialchars($data);
    // Escapa caracteres especiais em uma string para uso em uma instrução SQL.
    // Isso é crucial para prevenir SQL Injection ao inserir dados no banco de dados.
    $data = $conn->real_escape_string($data);
    return $data; // Retorna a string sanitizada.
}

//--- Lógica de Logout Global ---
//Este bloco gerencia a saída do usuário do sistema de forma centralizada.
//É ativado quando o parâmetro 'logout' está presente na URL em qualquer página.
if (isset($_GET['logout'])) {
    session_unset();   // Remove todas as variáveis de sessão.
    session_destroy(); // Destrói a sessão.
    header("Location: index.php"); // Redireciona para a página inicial após o logout.
    exit(); // Encerra o script para garantir o redirecionamento imediato.
}

?>