<?php
// registrar_pedido.php
// Recebe os dados via POST (fetch do JS) e grava em data/pedidos.json

date_default_timezone_set('America/Sao_Paulo');

$dataDir  = __DIR__ . '/data';
$pedFile  = $dataDir . '/pedidos.json';

if (!is_dir($dataDir)) {
    @mkdir($dataDir, 0775, true);
}

$nome       = trim($_POST['nome']       ?? '');
$tel        = trim($_POST['tel']        ?? '');
$endereco   = trim($_POST['endereco']   ?? '');
$referencia = trim($_POST['referencia'] ?? '');
$pag_forma  = trim($_POST['pag_forma']  ?? '');
$troco      = trim($_POST['troco']      ?? '');
$obs        = trim($_POST['obs']        ?? '');
$subtotal   = trim($_POST['subtotal']   ?? '0,00');

$itensJson  = $_POST['itens_json'] ?? '[]';
$itens      = json_decode($itensJson, true);
if (!is_array($itens)) $itens = [];

// carrega pedidos existentes
$lista = [];
if (file_exists($pedFile)) {
    $lista = json_decode(@file_get_contents($pedFile), true);
    if (!is_array($lista)) $lista = [];
}

// monta novo pedido
$pedido = [
    'id'         => uniqid('ped_', true),
    'data_hora'  => date('Y-m-d H:i:s'),
    'nome'       => $nome,
    'telefone'   => $tel,
    'endereco'   => $endereco,
    'referencia' => $referencia,
    'pag_forma'  => $pag_forma,
    'troco'      => $troco,
    'obs'        => $obs,
    'subtotal'   => $subtotal,
    'itens'      => $itens,
];

// coloca no início da lista (pedido mais recente primeiro)
array_unshift($lista, $pedido);

// salva
file_put_contents(
    $pedFile,
    json_encode($lista, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
);

// devolve um JSON simples pro JS (se ele quiser usar)
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['ok' => true]);
