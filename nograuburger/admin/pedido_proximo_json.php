<?php
// admin/pedido_proximo_json.php
header('Content-Type: application/json; charset=utf-8');

// ===== 1) Validação simples de token =====
$token = $_GET['token'] ?? '';
if ($token !== 'TESTE123') { // depois você troca esse token
    echo json_encode(['erro' => 'token_invalido'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ===== 2) Arquivos base =====
$baseDir     = __DIR__ . '/..';
$pedidosArq  = $baseDir . '/data/pedidos.json';
$produtosArq = $baseDir . '/data/produtos.json';

// ===== 3) Carrega pedidos =====
$pedidos = [];
if (file_exists($pedidosArq)) {
    $json    = file_get_contents($pedidosArq);
    $pedidos = json_decode($json, true) ?: [];
}

// ===== 4) Carrega dados da loja (produtos.json → loja) =====
$dadosLoja = [];
if (file_exists($produtosArq)) {
    $jsonProd  = file_get_contents($produtosArq);
    $dadosProd = json_decode($jsonProd, true) ?: [];
    $dadosLoja = $dadosProd['loja'] ?? [];
}

// ===== 5) Procura primeiro pedido com status "novo" (ou sem status) =====
$encontrado = null;
foreach ($pedidos as &$p) {
    $st = $p['status'] ?? 'novo';
    if ($st === 'novo' || $st === '') {
        $encontrado = $p;
        // marca como "em_impressao" para não repetir
        $p['status'] = 'em_impressao';
        break;
    }
}
unset($p);

// ===== 6) Se não encontrou, responde sem_pedido =====
if (!$encontrado) {
    echo json_encode([
        'status' => 'sem_pedido',
        'loja'   => $dadosLoja,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ===== 7) Grava de volta pedidos.json com status atualizado =====
file_put_contents(
    $pedidosArq,
    json_encode($pedidos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
);

// ===== 8) Resposta final =====
echo json_encode([
    'status' => 'ok',
    'pedido' => $encontrado,
    'loja'   => $dadosLoja,
], JSON_UNESCAPED_UNICODE);
