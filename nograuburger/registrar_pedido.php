<?php
// registrar_pedido.php
// Recebe os dados via POST (fetch do JS) e grava no banco MariaDB/MySQL

require_once __DIR__ . '/includes/app.php';

date_default_timezone_set('America/Sao_Paulo');

$nome       = limpar($_POST['nome']       ?? '');
$tel        = limpar($_POST['tel']        ?? '');
$tipoPedido = limpar($_POST['tipo_pedido'] ?? 'Entrega');
$bairro     = limpar($_POST['bairro']     ?? '');
$endereco   = limpar($_POST['endereco']   ?? '');
$referencia = limpar($_POST['referencia'] ?? '');
$pag_forma  = limpar($_POST['pag_forma']  ?? '');
$troco      = limpar($_POST['troco']      ?? '');
$obs        = limpar($_POST['obs']        ?? '');

$subtotal          = limpar($_POST['subtotal']          ?? '0,00');
$taxa_entrega      = limpar($_POST['taxa_entrega']      ?? '0,00');
$total_final       = limpar($_POST['total_final']       ?? $subtotal);
$entrega_consultar = limpar($_POST['entrega_consultar'] ?? '0');

if ($tipoPedido === 'Retirada no local') {
    $bairro = 'Retirada no local';
    $endereco = 'Retirada no local';
    $taxa_entrega = '0,00';
    $total_final = $subtotal;
    $entrega_consultar = '0';
}

$itensJson = $_POST['itens_json'] ?? '[]';
$itens     = json_decode($itensJson, true);

if (!is_array($itens)) {
    $itens = [];
}

// Monta novo pedido
$pedido = [
    'id'                => uniqid('ped_', true),
    'data_hora'         => date('Y-m-d H:i:s'),

    'nome'              => $nome,
    'telefone'          => $tel,

    'tipo_pedido'       => $tipoPedido,
    'bairro'            => $bairro,
    'endereco'          => $endereco,
    'referencia'        => $referencia,

    'pag_forma'         => $pag_forma,
    'troco'             => $troco,
    'obs'               => $obs,

    'subtotal'          => $subtotal,
    'taxa_entrega'      => $taxa_entrega,
    'total_final'       => $total_final,
    'entrega_consultar' => $entrega_consultar,

    'itens'             => $itens,

    'status'            => 'novo'
];

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = cardapio_conectar_banco();
    cardapio_pedido_para_banco($pdo, $pedido);

    echo json_encode([
        'ok'           => true,
        'id'           => $pedido['id'],
        'tipo_pedido'  => $tipoPedido,
        'bairro'       => $bairro,
        'taxa_entrega' => $taxa_entrega,
        'total_final'  => $total_final
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('Erro ao registrar pedido No Grau Burger: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'erro' => 'Não foi possível registrar o pedido agora. Tente novamente em instantes.'
    ], JSON_UNESCAPED_UNICODE);
}
