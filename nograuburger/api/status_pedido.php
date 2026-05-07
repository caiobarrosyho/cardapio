<?php
require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    api_erro('metodo_invalido', 'Use o método GET.', 405);
}

[$pdo, $loja, $input] = api_autenticar_loja();
$pedidoId = limpar($input['pedido_id'] ?? $input['id'] ?? '');

if ($pedidoId === '') {
    api_erro('pedido_obrigatorio', 'Informe pedido_id.', 422);
}

try {
    $stmt = $pdo->prepare('SELECT * FROM pedidos WHERE loja_id = :loja_id AND (codigo_publico = :codigo OR id = :id) LIMIT 1');
    $stmt->execute([
        ':loja_id' => (int)$loja['id'],
        ':codigo' => $pedidoId,
        ':id' => ctype_digit($pedidoId) ? (int)$pedidoId : 0,
    ]);
    $row = $stmt->fetch();

    if (!$row) {
        api_erro('pedido_nao_encontrado', 'Pedido não encontrado.', 404);
    }

    api_responder([
        'ok' => true,
        'pedido' => cardapio_pedido_array($row, cardapio_itens_pedido_banco($pdo, (int)$row['id'])),
    ]);
} catch (Throwable $e) {
    api_log_erro('status_pedido.php: ' . $e->getMessage());
    api_erro('erro_consulta', 'Não foi possível consultar o pedido.', 500);
}
