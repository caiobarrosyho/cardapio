<?php
require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_erro('metodo_invalido', 'Use o método POST.', 405);
}

[$pdo, $loja, $input] = api_autenticar_loja();

try {
    $cliente = is_array($input['cliente'] ?? null) ? $input['cliente'] : [];
    $nome = limpar($input['nome'] ?? $cliente['nome'] ?? '');
    $telefone = limpar($input['telefone'] ?? $input['whatsapp'] ?? $cliente['telefone'] ?? $cliente['whatsapp'] ?? '');

    if ($nome === '') {
        api_erro('cliente_obrigatorio', 'Informe o nome do cliente.', 422);
    }

    $itensEntrada = $input['itens'] ?? [];
    if (!is_array($itensEntrada) || count($itensEntrada) === 0) {
        api_erro('itens_obrigatorios', 'Informe ao menos um item no pedido.', 422);
    }

    $itens = [];
    $subtotal = 0.0;

    $stmtProduto = $pdo->prepare('SELECT id, nome, preco FROM produtos WHERE id = :id AND loja_id = :loja_id AND ativo = 1 LIMIT 1');

    foreach ($itensEntrada as $item) {
        if (!is_array($item)) {
            continue;
        }

        $qtd = max(1, (int)($item['qtd'] ?? $item['quantidade'] ?? 1));
        $produtoId = isset($item['produto_id']) ? (int)$item['produto_id'] : 0;
        $nomeItem = limpar($item['nome'] ?? '');
        $preco = cardapio_dinheiro_para_float($item['preco'] ?? 0);

        if ($produtoId > 0) {
            $stmtProduto->execute([':id' => $produtoId, ':loja_id' => (int)$loja['id']]);
            $produto = $stmtProduto->fetch();
            if (!$produto) {
                api_erro('produto_invalido', 'Um dos produtos informados não foi encontrado.', 422);
            }
            $nomeItem = $produto['nome'];
            $preco = (float)$produto['preco'];
        }

        if ($nomeItem === '' || $preco < 0) {
            api_erro('item_invalido', 'Confira nome e preço dos itens.', 422);
        }

        $subtotal += $preco * $qtd;
        $itens[] = [
            'produto_id' => $produtoId > 0 ? $produtoId : null,
            'nome' => $nomeItem,
            'qtd' => $qtd,
            'preco' => $preco,
            'observacao' => limpar($item['observacao'] ?? ''),
        ];
    }

    if (!$itens) {
        api_erro('itens_obrigatorios', 'Informe ao menos um item válido no pedido.', 422);
    }

    $tipoPedido = limpar($input['tipo_pedido'] ?? 'Entrega');
    if ($tipoPedido === 'Retirada') {
        $tipoPedido = 'Retirada no local';
    }

    $taxaEntrega = $tipoPedido === 'Retirada no local' ? 0.0 : cardapio_dinheiro_para_float($input['taxa_entrega'] ?? 0);
    $total = $subtotal + $taxaEntrega;

    $pedido = [
        'id' => uniqid('api_', true),
        'data_hora' => date('Y-m-d H:i:s'),
        'nome' => $nome,
        'telefone' => $telefone,
        'tipo_pedido' => $tipoPedido,
        'bairro' => limpar($input['bairro'] ?? ''),
        'endereco' => limpar($input['endereco'] ?? ''),
        'referencia' => limpar($input['referencia'] ?? ''),
        'pag_forma' => limpar($input['pag_forma'] ?? $input['pagamento'] ?? ''),
        'troco' => limpar($input['troco'] ?? ''),
        'obs' => limpar($input['obs'] ?? $input['observacoes'] ?? ''),
        'subtotal' => cardapio_formatar_dinheiro($subtotal),
        'taxa_entrega' => cardapio_formatar_dinheiro($taxaEntrega),
        'total_final' => cardapio_formatar_dinheiro($total),
        'entrega_consultar' => (int)($input['entrega_consultar'] ?? 0),
        'itens' => $itens,
        'status' => 'novo',
        'impresso' => 0,
    ];

    [$pedidoId, $codigo] = cardapio_pedido_para_banco($pdo, $pedido, (int)$loja['id']);

    api_responder([
        'ok' => true,
        'pedido' => [
            'id' => $codigo,
            'db_id' => $pedidoId,
            'status' => 'novo',
            'subtotal' => $pedido['subtotal'],
            'taxa_entrega' => $pedido['taxa_entrega'],
            'total_final' => $pedido['total_final'],
        ],
    ], 201);
} catch (Throwable $e) {
    api_log_erro('criar_pedido.php: ' . $e->getMessage());
    api_erro('erro_criar_pedido', 'Não foi possível criar o pedido agora.', 500);
}
