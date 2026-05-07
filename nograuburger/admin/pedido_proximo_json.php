<?php
// admin/pedido_proximo_json.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/app.php';

$token = $_GET['token'] ?? '';
if ($token !== 'TESTE123') {
    echo json_encode(['erro' => 'token_invalido'], JSON_UNESCAPED_UNICODE);
    exit;
}

$dadosProd = cardapio_carregar_produtos();
$dadosLoja = $dadosProd['loja'] ?? [];

try {
    $pedido = cardapio_proximo_pedido_banco(true);
} catch (Throwable $e) {
    error_log('Erro ao buscar próximo pedido JSON: ' . $e->getMessage());
    echo json_encode([
        'status' => 'erro',
        'mensagem' => 'Não foi possível consultar pedidos agora.',
        'loja' => $dadosLoja,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!$pedido) {
    echo json_encode([
        'status' => 'sem_pedido',
        'loja'   => $dadosLoja,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'status' => 'ok',
    'pedido' => $pedido,
    'loja'   => $dadosLoja,
], JSON_UNESCAPED_UNICODE);
