let currentPlayer= "X"; //Jogador x começa a jogar.
//este array armazena os valores das celulas do tabuleiro
let board = ["", "", "", "", "", "", "", "", "",];

//verificacao de vencedor
//verifica as combinações possiveis do vencedor
const winningCombos = [

    [0,1,2], [3,4,5], [6,7,8],  // Linhas
    [0,3,6], [1,4,7], [2,5,8],  //Colunas
    [0,4,8], [2,4,6],           //Diagonais
];

function handleCellClick(e) {
    // Obtem o indice da célula clicada
    const index = e.target.dataset.index;

    // Impede jogadas invalidas ou apos vitoria
    //board[index] : Verifica se a celula já foi preenchida
    if(board[index] || checkWinner()) return;

    // Atualiza o array com a jogada do jogador atual
    board[index] = currentPlayer;
    // Atualiza a interface com o simbolo "X" ou "O", modificando o valor de textContent para o currentPlayer
    e.target.textContent = currentPlayer;

    //Verifica a vitoria ou empate
    if (checkWinner()) {
        alert(`Jogador ${currentPlayer} venceu!`);
        return
    }

    //Verifica aqui se tem celulas vazias no tabuleiro, se for diferente cai no if
    if (!board.includes("")) {
        alert("Empate!");
        return;
    }

    //Altera a vez de jogada dos jogadores X e 0o
    currentPlayer = currentPlayer === "X" ? "O" : "X";
}

//Verifica o vencedor
//some() e every() : metodos de array que verificam se alguma combinação esta totalmente preenchida pelo jogador atual
function checkWinner() {
    return winningCombos.some(combo => {
        return combo.every(index => board[index] === currentPlayer);
    });

}   

//Funcao que reseta o jogo
function resetGame() {
    //Reseta o array do tabuleiro
    board = ["", "", "", "", "", "", "", "", "",];
    //Define o jogador inicial como "X"
    currentPlayer = "X";
    //Limpa o HTML
    //querySelectorAll(".cell") : Seleciona todas as celulas do tabuleiro para resetar a interface
    document.querySelectorAll(".cell").forEach(cell => cell.textContent = "");
}

document.querySelectorAll(".cell").forEach(cell => cell.addEventListener("click", handleCellClick));
document.getElementById("reset").addEventListener("click", resetGame);