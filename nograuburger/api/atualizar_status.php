<?php
require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_erro('metodo_invalido', 'Use o método POST.', 405);
}

[$pdo, $loja, $input] = api_autenticar_loja();
$pedidoId = limpar($input['pedido_id'] ?? $input['id'] ?? '');
$status = limpar($input['status'] ?? '');
$permitidos = ['novo', 'preparo', 'finalizado', 'cancelado', 'em_impressao'];

if ($pedidoId === '') {
    api_erro('pedido_obrigatorio', 'Informe pedido_id.', 422);
}

if (!in_array($status, $permitidos, true)) {
    api_erro('status_invalido', 'Informe um status válido.', 422);
}

try {
    $stmt = $pdo->prepare('SELECT id FROM pedidos WHERE loja_id = :loja_id AND (codigo_publico = :codigo OR id = :id) LIMIT 1');
    $stmt->execute([
        ':loja_id' => (int)$loja['id'],
        ':codigo' => $pedidoId,
        ':id' => ctype_digit($pedidoId) ? (int)$pedidoId : 0,
    ]);
    $dbId = $stmt->fetchColumn();

    if (!$dbId) {
        api_erro('pedido_nao_encontrado', 'Pedido não encontrado.', 404);
    }

    $impresso = array_key_exists('impresso', $input) ? (int)$input['impresso'] : null;
    cardapio_atualizar_status_pedido_banco((string)$dbId, $status, $impresso);

    api_responder([
        'ok' => true,
        'pedido' => [
            'id' => $pedidoId,
            'db_id' => (int)$dbId,
            'status' => $status,
            'impresso' => $impresso,
        ],
    ]);
} catch (Throwable $e) {
    api_log_erro('atualizar_status.php: ' . $e->getMessage());
    api_erro('erro_atualizar_status', 'Não foi possível atualizar o status.', 500);
}
