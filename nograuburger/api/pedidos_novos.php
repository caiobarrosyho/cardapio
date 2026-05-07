<?php
require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    api_erro('metodo_invalido', 'Use o método GET.', 405);
}

[$pdo, $loja, $input] = api_autenticar_loja();
$limite = isset($input['limite']) ? max(1, min(50, (int)$input['limite'])) : 20;

try {
    $stmt = $pdo->prepare(
        "SELECT * FROM pedidos
         WHERE loja_id = :loja_id AND status = 'novo'
         ORDER BY COALESCE(recebido_em, criado_em) ASC, id ASC
         LIMIT {$limite}"
    );
    $stmt->execute([':loja_id' => (int)$loja['id']]);

    $pedidos = [];
    foreach ($stmt->fetchAll() as $row) {
        $pedidos[] = cardapio_pedido_array($row, cardapio_itens_pedido_banco($pdo, (int)$row['id']));
    }

    api_responder([
        'ok' => true,
        'loja' => api_loja_basica($loja),
        'pedidos' => $pedidos,
    ]);
} catch (Throwable $e) {
    api_log_erro('pedidos_novos.php: ' . $e->getMessage());
    api_erro('erro_consulta', 'Não foi possível consultar pedidos novos.', 500);
}
