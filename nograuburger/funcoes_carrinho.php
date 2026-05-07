<?php
// funcoes_carrinho.php
if (!isset($_SESSION['carrinho'])) {
    $_SESSION['carrinho'] = [];
}

function carrinho_adicionar($id, $nome, $preco, $qtd = 1) {
    $id    = (string)$id;
    $nome  = (string)$nome;
    $preco = (float)$preco;
    $qtd   = (int)$qtd;
    if ($qtd < 1) $qtd = 1;

    if (!isset($_SESSION['carrinho'][$id])) {
        $_SESSION['carrinho'][$id] = [
            'nome'  => $nome,
            'preco' => $preco,
            'qtd'   => 0,
        ];
    }
    $_SESSION['carrinho'][$id]['qtd'] += $qtd;
}

function carrinho_atualizar_qtd($id, $qtd) {
    $qtd = (int)$qtd;
    if ($qtd <= 0) {
        unset($_SESSION['carrinho'][$id]);
    } else {
        if (isset($_SESSION['carrinho'][$id])) {
            $_SESSION['carrinho'][$id]['qtd'] = $qtd;
        }
    }
}

function carrinho_limpar() {
    $_SESSION['carrinho'] = [];
}

function carrinho_totais() {
    $itens    = 0;
    $subtotal = 0.0;
    foreach ($_SESSION['carrinho'] as $item) {
        $itens    += $item['qtd'];
        $subtotal += $item['preco'] * $item['qtd'];
    }
    return ['itens' => $itens, 'subtotal' => $subtotal];
}
