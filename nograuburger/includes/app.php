<?php
/**
 * Funções compartilhadas do cardápio digital.
 *
 * Este arquivo centraliza leitura/gravação dos JSONs atuais e helpers
 * pequenos usados pelas telas públicas e pelo painel administrativo.
 */

define('CARDAPIO_ROOT', dirname(__DIR__));
define('CARDAPIO_DATA_DIR', CARDAPIO_ROOT . '/data');

if (!function_exists('h')) {
    function h($valor) {
        return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('limpar')) {
    function limpar($valor) {
        return trim((string)($valor ?? ''));
    }
}

function cardapio_data_path($arquivo) {
    return CARDAPIO_DATA_DIR . '/' . ltrim((string)$arquivo, '/');
}

function cardapio_ler_json($arquivo, $padrao = []) {
    $caminho = is_file($arquivo) ? $arquivo : cardapio_data_path($arquivo);

    if (!file_exists($caminho)) {
        return $padrao;
    }

    $conteudo = @file_get_contents($caminho);
    $dados = json_decode($conteudo, true);

    return is_array($dados) ? $dados : $padrao;
}

function cardapio_salvar_json($arquivo, array $dados) {
    $caminho = is_file($arquivo) || dirname($arquivo) !== '.' ? $arquivo : cardapio_data_path($arquivo);
    $dir = dirname($caminho);

    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }

    return file_put_contents(
        $caminho,
        json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        LOCK_EX
    );
}

function cardapio_carregar_produtos() {
    $dados = cardapio_ler_json('produtos.json', ['loja' => [], 'categorias' => []]);

    if (!isset($dados['loja']) || !is_array($dados['loja'])) {
        $dados['loja'] = [];
    }

    if (!isset($dados['categorias']) || !is_array($dados['categorias'])) {
        $dados['categorias'] = [];
    }

    return $dados;
}

function cardapio_carregar_pedidos() {
    return cardapio_ler_json('pedidos.json', []);
}

function cardapio_salvar_pedidos(array $pedidos) {
    return cardapio_salvar_json('pedidos.json', $pedidos);
}

function cardapio_produto_ativo(array $produto) {
    return !isset($produto['ativo']) || (int)$produto['ativo'] === 1;
}

if (!function_exists('buscar_produto')) {
    function buscar_produto($dados, $catIdx, $prodIdx) {
        if (isset($dados['categorias'][$catIdx]['produtos'][$prodIdx])) {
            $produto = $dados['categorias'][$catIdx]['produtos'][$prodIdx];
            return cardapio_produto_ativo($produto) ? $produto : null;
        }

        foreach (($dados['categorias'] ?? []) as $categoria) {
            $catId = (string)($categoria['id'] ?? $categoria['db_id'] ?? '');
            if ($catId !== '' && $catId !== (string)$catIdx) {
                continue;
            }

            foreach (($categoria['produtos'] ?? []) as $produto) {
                $prodId = (string)($produto['id'] ?? $produto['db_id'] ?? '');
                if ($prodId === (string)$prodIdx && cardapio_produto_ativo($produto)) {
                    return $produto;
                }
            }
        }

        return null;
    }
}

if (!function_exists('localizar_produto_carrinho')) {
    function localizar_produto_carrinho($dados, $itemId, $itemNome, $itemPreco) {
        if (empty($dados['categorias']) || !is_array($dados['categorias'])) {
            return null;
        }

        foreach ($dados['categorias'] as $catIdx => $cat) {
            foreach (($cat['produtos'] ?? []) as $prodIdx => $prod) {
                if (!cardapio_produto_ativo($prod)) {
                    continue;
                }

                $nomeProd   = (string)($prod['nome'] ?? '');
                $precoProd  = (float)($prod['preco'] ?? 0);
                $idNumerico = $catIdx . '-' . $prodIdx;
                $idProd     = (string)($prod['id'] ?? '');
                $idMd5      = md5($nomeProd . $precoProd);

                $bateNomePreco = (
                    mb_strtolower(trim($itemNome)) === mb_strtolower(trim($nomeProd))
                    && abs((float)$itemPreco - $precoProd) < 0.001
                );

                if ($itemId === $idNumerico || ($idProd !== '' && $itemId === $idProd) || $itemId === $idMd5 || $bateNomePreco) {
                    return $prod;
                }
            }
        }

        return null;
    }
}


function cardapio_conectar_banco() {
    $configFile = CARDAPIO_ROOT . '/config/conexao.php';

    if (!file_exists($configFile)) {
        throw new RuntimeException('Configuração de banco não encontrada.');
    }

    require_once $configFile;

    if (!function_exists('nograu_conexao')) {
        throw new RuntimeException('Função de conexão do banco não encontrada.');
    }

    try {
        return nograu_conexao();
    } catch (Throwable $e) {
        error_log('Erro ao conectar no banco No Grau Burger: ' . $e->getMessage());
        throw new RuntimeException('Não foi possível conectar ao banco de dados. Verifique a configuração.');
    }
}

function cardapio_obter_loja_id(PDO $pdo, array $loja = []) {
    $stmt = $pdo->query('SELECT id FROM lojas ORDER BY id ASC LIMIT 1');
    $id = $stmt->fetchColumn();

    if ($id) {
        return (int)$id;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO lojas (nome, tipo, slogan, descricao, cnpj, whatsapp, endereco, endereco_topo, endereco_curto, complemento, bairro, cidade, uf, cep, logo, banner, ativo)
         VALUES (:nome, :tipo, :slogan, :descricao, :cnpj, :whatsapp, :endereco, :endereco_topo, :endereco_curto, :complemento, :bairro, :cidade, :uf, :cep, :logo, :banner, 1)'
    );

    $stmt->execute([
        ':nome' => limpar($loja['nome'] ?? 'No Grau Burger') ?: 'No Grau Burger',
        ':tipo' => limpar($loja['tipo'] ?? ''),
        ':slogan' => limpar($loja['slogan'] ?? ''),
        ':descricao' => limpar($loja['descricao'] ?? ''),
        ':cnpj' => limpar($loja['cnpj'] ?? ''),
        ':whatsapp' => limpar($loja['whatsapp'] ?? ''),
        ':endereco' => limpar($loja['endereco'] ?? ''),
        ':endereco_topo' => limpar($loja['endereco_topo'] ?? ''),
        ':endereco_curto' => limpar($loja['endereco_curto'] ?? ''),
        ':complemento' => limpar($loja['complemento'] ?? ''),
        ':bairro' => limpar($loja['bairro'] ?? ''),
        ':cidade' => limpar($loja['cidade'] ?? ''),
        ':uf' => limpar($loja['uf'] ?? ''),
        ':cep' => limpar($loja['cep'] ?? ''),
        ':logo' => limpar($loja['logo'] ?? ''),
        ':banner' => limpar($loja['banner'] ?? ''),
    ]);

    return (int)$pdo->lastInsertId();
}

function cardapio_carregar_catalogo_banco($incluirInativos = false) {
    $pdo = cardapio_conectar_banco();
    $json = cardapio_carregar_produtos();
    $lojaId = cardapio_obter_loja_id($pdo, $json['loja'] ?? []);

    $categoriasSql = 'SELECT id, titulo, descricao, ordem, ativo FROM categorias WHERE loja_id = :loja_id';
    if (!$incluirInativos) {
        $categoriasSql .= ' AND ativo = 1';
    }
    $categoriasSql .= ' ORDER BY ordem ASC, id ASC';

    $stmt = $pdo->prepare($categoriasSql);
    $stmt->execute([':loja_id' => $lojaId]);
    $categoriasRows = $stmt->fetchAll();

    $produtosSql = 'SELECT id, categoria_id, nome, descricao, detalhe, preco, preco_original, foto, imagem, destaque, ativo, ordem
                    FROM produtos WHERE loja_id = :loja_id';
    if (!$incluirInativos) {
        $produtosSql .= ' AND ativo = 1';
    }
    $produtosSql .= ' ORDER BY ordem ASC, id ASC';

    $stmt = $pdo->prepare($produtosSql);
    $stmt->execute([':loja_id' => $lojaId]);
    $produtosRows = $stmt->fetchAll();

    $produtosPorCategoria = [];
    foreach ($produtosRows as $produto) {
        $produtosPorCategoria[(int)$produto['categoria_id']][] = [
            'id' => (string)$produto['id'],
            'db_id' => (int)$produto['id'],
            'nome' => $produto['nome'],
            'descricao' => $produto['descricao'] ?? '',
            'detalhe' => $produto['detalhe'] ?? '',
            'preco' => (float)$produto['preco'],
            'preco_original' => $produto['preco_original'] !== null ? (float)$produto['preco_original'] : null,
            'foto' => $produto['foto'] ?? '',
            'imagem' => $produto['imagem'] ?? '',
            'destaque' => (int)($produto['destaque'] ?? 0),
            'ativo' => (int)$produto['ativo'],
            'ordem' => (int)($produto['ordem'] ?? 0),
        ];
    }

    $categorias = [];
    foreach ($categoriasRows as $categoria) {
        $catId = (int)$categoria['id'];
        $categorias[] = [
            'id' => (string)$catId,
            'db_id' => $catId,
            'titulo' => $categoria['titulo'],
            'descricao' => $categoria['descricao'] ?? '',
            'ordem' => (int)($categoria['ordem'] ?? 0),
            'ativo' => (int)$categoria['ativo'],
            'produtos' => $produtosPorCategoria[$catId] ?? [],
        ];
    }

    return [
        'loja' => $json['loja'] ?? [],
        'categorias' => $categorias,
        'loja_id' => $lojaId,
    ];
}

function cardapio_preco_decimal($valor) {
    $valor = str_replace(',', '.', trim((string)$valor));

    if ($valor === '' || !is_numeric($valor)) {
        return null;
    }

    $decimal = round((float)$valor, 2);
    return $decimal >= 0 ? $decimal : null;
}

function cardapio_dinheiro_para_float($valor) {
    if ($valor === null || $valor === '') {
        return 0.0;
    }

    if (is_int($valor) || is_float($valor)) {
        return (float)$valor;
    }

    $valor = trim((string)$valor);
    if ($valor === '' || stripos($valor, 'Aguardando') !== false || stripos($valor, 'consultar') !== false) {
        return 0.0;
    }

    $valor = str_replace(['R$', ' '], '', $valor);
    $valor = str_replace('.', '', $valor);
    $valor = str_replace(',', '.', $valor);

    return is_numeric($valor) ? (float)$valor : 0.0;
}

function cardapio_formatar_dinheiro($valor) {
    return number_format((float)$valor, 2, ',', '.');
}

function cardapio_normalizar_status_pedido($status) {
    $status = limpar($status ?: 'novo');
    $permitidos = ['novo', 'preparo', 'finalizado', 'cancelado', 'em_impressao'];

    return in_array($status, $permitidos, true) ? $status : 'novo';
}

function cardapio_pedido_para_banco(PDO $pdo, array $pedido, $lojaId = null) {
    if ($lojaId === null) {
        $dadosLoja = cardapio_carregar_produtos();
        $lojaId = cardapio_obter_loja_id($pdo, $dadosLoja['loja'] ?? []);
    }

    $lojaId = (int)$lojaId;

    $codigo = limpar($pedido['id'] ?? '');
    if ($codigo === '') {
        $codigo = uniqid('ped_', true);
    }

    $tipoPedido = limpar($pedido['tipo_pedido'] ?? 'Entrega');
    if ($tipoPedido === 'Retirada') {
        $tipoPedido = 'Retirada no local';
    }
    if (!in_array($tipoPedido, ['Entrega', 'Retirada no local'], true)) {
        $tipoPedido = 'Entrega';
    }

    $subtotal = cardapio_dinheiro_para_float($pedido['subtotal'] ?? 0);
    $taxa = cardapio_dinheiro_para_float($pedido['taxa_entrega'] ?? 0);
    $totalOriginal = (string)($pedido['total_final'] ?? '');
    $entregaConsultar = (int)($pedido['entrega_consultar'] ?? 0);
    $total = ($entregaConsultar === 1 || stripos($totalOriginal, 'Aguardando') !== false)
        ? 0.0
        : cardapio_dinheiro_para_float($totalOriginal !== '' ? $totalOriginal : ($subtotal + $taxa));

    $stmt = $pdo->prepare('SELECT id FROM pedidos WHERE codigo_publico = :codigo LIMIT 1');
    $stmt->execute([':codigo' => $codigo]);
    $pedidoId = $stmt->fetchColumn();

    $params = [
        ':loja_id' => $lojaId,
        ':codigo_publico' => $codigo,
        ':nome_cliente' => limpar($pedido['nome'] ?? ''),
        ':telefone' => limpar($pedido['telefone'] ?? ''),
        ':tipo_pedido' => $tipoPedido,
        ':bairro' => limpar($pedido['bairro'] ?? ''),
        ':endereco' => limpar($pedido['endereco'] ?? ''),
        ':referencia' => limpar($pedido['referencia'] ?? ''),
        ':pag_forma' => limpar($pedido['pag_forma'] ?? ''),
        ':troco' => limpar($pedido['troco'] ?? ''),
        ':obs' => limpar($pedido['obs'] ?? ''),
        ':subtotal' => $subtotal,
        ':taxa_entrega' => $taxa,
        ':ajuste_tipo' => in_array(($pedido['ajuste_tipo'] ?? ''), ['acrescimo', 'desconto'], true) ? $pedido['ajuste_tipo'] : null,
        ':ajuste_valor' => cardapio_dinheiro_para_float($pedido['ajuste_valor'] ?? 0),
        ':ajuste_obs' => limpar($pedido['ajuste_obs'] ?? ''),
        ':total_final' => $total,
        ':entrega_consultar' => $entregaConsultar,
        ':status' => cardapio_normalizar_status_pedido($pedido['status'] ?? 'novo'),
        ':impresso' => isset($pedido['impresso']) ? (int)$pedido['impresso'] : ((($pedido['status'] ?? '') === 'em_impressao') ? 1 : 0),
        ':recebido_em' => limpar($pedido['data_hora'] ?? '') ?: date('Y-m-d H:i:s'),
    ];

    if ($pedidoId) {
        $params[':id'] = (int)$pedidoId;
        $paramsUpdate = $params;
        unset($paramsUpdate[':codigo_publico']);
        $stmt = $pdo->prepare(
            'UPDATE pedidos SET loja_id = :loja_id, nome_cliente = :nome_cliente, telefone = :telefone,
             tipo_pedido = :tipo_pedido, bairro = :bairro, endereco = :endereco, referencia = :referencia,
             pag_forma = :pag_forma, troco = :troco, obs = :obs, subtotal = :subtotal,
             taxa_entrega = :taxa_entrega, ajuste_tipo = :ajuste_tipo, ajuste_valor = :ajuste_valor,
             ajuste_obs = :ajuste_obs, total_final = :total_final, entrega_consultar = :entrega_consultar,
             status = :status, impresso = :impresso, recebido_em = :recebido_em
             WHERE id = :id'
        );
        $stmt->execute($paramsUpdate);
        $pedidoId = (int)$pedidoId;
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO pedidos (loja_id, codigo_publico, nome_cliente, telefone, tipo_pedido, bairro, endereco,
             referencia, pag_forma, troco, obs, subtotal, taxa_entrega, ajuste_tipo, ajuste_valor, ajuste_obs,
             total_final, entrega_consultar, status, impresso, recebido_em)
             VALUES (:loja_id, :codigo_publico, :nome_cliente, :telefone, :tipo_pedido, :bairro, :endereco,
             :referencia, :pag_forma, :troco, :obs, :subtotal, :taxa_entrega, :ajuste_tipo, :ajuste_valor,
             :ajuste_obs, :total_final, :entrega_consultar, :status, :impresso, :recebido_em)'
        );
        $stmt->execute($params);
        $pedidoId = (int)$pdo->lastInsertId();
    }

    $pdo->prepare('DELETE FROM pedido_itens WHERE pedido_id = :pedido_id')->execute([':pedido_id' => $pedidoId]);

    $stmtItem = $pdo->prepare(
        'INSERT INTO pedido_itens (pedido_id, produto_id, nome_produto, quantidade, preco_unitario, subtotal, observacao)
         VALUES (:pedido_id, :produto_id, :nome_produto, :quantidade, :preco_unitario, :subtotal, :observacao)'
    );

    foreach (($pedido['itens'] ?? []) as $item) {
        $qtd = max(1, (int)($item['qtd'] ?? $item['quantidade'] ?? 1));
        $preco = cardapio_dinheiro_para_float($item['preco'] ?? $item['preco_unitario'] ?? 0);
        $produtoId = isset($item['produto_id']) && (int)$item['produto_id'] > 0 ? (int)$item['produto_id'] : null;

        $stmtItem->execute([
            ':pedido_id' => $pedidoId,
            ':produto_id' => $produtoId,
            ':nome_produto' => limpar($item['nome'] ?? $item['nome_produto'] ?? ''),
            ':quantidade' => $qtd,
            ':preco_unitario' => $preco,
            ':subtotal' => $preco * $qtd,
            ':observacao' => limpar($item['observacao'] ?? ''),
        ]);
    }

    return [$pedidoId, $codigo];
}

function cardapio_pedido_array(array $row, array $itens = []) {
    $dataHora = $row['recebido_em'] ?? $row['criado_em'] ?? '';

    return [
        'id' => $row['codigo_publico'] ?: (string)$row['id'],
        'db_id' => (int)$row['id'],
        'data_hora' => $dataHora,
        'nome' => $row['nome_cliente'] ?? '',
        'telefone' => $row['telefone'] ?? '',
        'tipo_pedido' => $row['tipo_pedido'] ?? 'Entrega',
        'bairro' => $row['bairro'] ?? '',
        'endereco' => $row['endereco'] ?? '',
        'referencia' => $row['referencia'] ?? '',
        'pag_forma' => $row['pag_forma'] ?? '',
        'troco' => $row['troco'] ?? '',
        'obs' => $row['obs'] ?? '',
        'subtotal' => cardapio_formatar_dinheiro($row['subtotal'] ?? 0),
        'taxa_entrega' => cardapio_formatar_dinheiro($row['taxa_entrega'] ?? 0),
        'total_final' => ((int)($row['entrega_consultar'] ?? 0) === 1) ? 'Aguardando confirmação' : cardapio_formatar_dinheiro($row['total_final'] ?? 0),
        'entrega_consultar' => (string)($row['entrega_consultar'] ?? '0'),
        'ajuste_tipo' => $row['ajuste_tipo'] ?? '',
        'ajuste_valor' => (float)($row['ajuste_valor'] ?? 0),
        'ajuste_obs' => $row['ajuste_obs'] ?? '',
        'status' => cardapio_normalizar_status_pedido($row['status'] ?? 'novo'),
        'impresso' => (int)($row['impresso'] ?? 0),
        'itens' => $itens,
    ];
}

function cardapio_itens_pedido_banco(PDO $pdo, $pedidoId) {
    $stmt = $pdo->prepare('SELECT produto_id, nome_produto, quantidade, preco_unitario, subtotal, observacao FROM pedido_itens WHERE pedido_id = :pedido_id ORDER BY id ASC');
    $stmt->execute([':pedido_id' => $pedidoId]);

    $itens = [];
    foreach ($stmt->fetchAll() as $item) {
        $itens[] = [
            'produto_id' => $item['produto_id'] ? (int)$item['produto_id'] : null,
            'nome' => $item['nome_produto'],
            'qtd' => (int)$item['quantidade'],
            'preco' => (float)$item['preco_unitario'],
            'subtotal' => (float)$item['subtotal'],
            'observacao' => $item['observacao'] ?? '',
        ];
    }

    return $itens;
}

function cardapio_carregar_pedidos_banco() {
    $pdo = cardapio_conectar_banco();
    $stmt = $pdo->query('SELECT * FROM pedidos ORDER BY COALESCE(recebido_em, criado_em) DESC, id DESC');
    $pedidos = [];

    foreach ($stmt->fetchAll() as $row) {
        $pedidos[] = cardapio_pedido_array($row, cardapio_itens_pedido_banco($pdo, (int)$row['id']));
    }

    return $pedidos;
}

function cardapio_buscar_pedido_banco($id) {
    $pdo = cardapio_conectar_banco();
    $stmt = $pdo->prepare('SELECT * FROM pedidos WHERE codigo_publico = :codigo OR id = :id LIMIT 1');
    $stmt->execute([
        ':codigo' => (string)$id,
        ':id' => ctype_digit((string)$id) ? (int)$id : 0,
    ]);
    $row = $stmt->fetch();

    return $row ? cardapio_pedido_array($row, cardapio_itens_pedido_banco($pdo, (int)$row['id'])) : null;
}

function cardapio_atualizar_status_pedido_banco($id, $status, $impresso = null) {
    $pdo = cardapio_conectar_banco();
    $status = cardapio_normalizar_status_pedido($status);
    $sql = 'UPDATE pedidos SET status = :status';
    $params = [':status' => $status, ':codigo' => (string)$id, ':id' => ctype_digit((string)$id) ? (int)$id : 0];

    if ($impresso !== null) {
        $sql .= ', impresso = :impresso';
        $params[':impresso'] = (int)$impresso;
    }

    $sql .= ' WHERE codigo_publico = :codigo OR id = :id';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
}

function cardapio_excluir_pedido_banco($id) {
    $pdo = cardapio_conectar_banco();
    $stmt = $pdo->prepare('DELETE FROM pedidos WHERE codigo_publico = :codigo OR id = :id');
    $stmt->execute([
        ':codigo' => (string)$id,
        ':id' => ctype_digit((string)$id) ? (int)$id : 0,
    ]);
}

function cardapio_atualizar_ajuste_pedido_banco($id, $tipo, $valor, $obs) {
    $pdo = cardapio_conectar_banco();
    $tipo = in_array($tipo, ['acrescimo', 'desconto'], true) ? $tipo : null;
    $valor = max(0, cardapio_dinheiro_para_float($valor));

    $stmt = $pdo->prepare(
        'UPDATE pedidos SET ajuste_tipo = :tipo, ajuste_valor = :valor, ajuste_obs = :obs WHERE codigo_publico = :codigo OR id = :id'
    );
    $stmt->execute([
        ':tipo' => $tipo,
        ':valor' => $valor,
        ':obs' => limpar($obs),
        ':codigo' => (string)$id,
        ':id' => ctype_digit((string)$id) ? (int)$id : 0,
    ]);
}

function cardapio_proximo_pedido_banco($marcarEmImpressao = true) {
    $pdo = cardapio_conectar_banco();
    $pdo->beginTransaction();

    try {
        $stmt = $pdo->query("SELECT * FROM pedidos WHERE status = 'novo' OR status = '' ORDER BY COALESCE(recebido_em, criado_em) ASC, id ASC LIMIT 1 FOR UPDATE");
        $row = $stmt->fetch();

        if (!$row) {
            $pdo->commit();
            return null;
        }

        if ($marcarEmImpressao) {
            $upd = $pdo->prepare("UPDATE pedidos SET status = 'em_impressao', impresso = 1 WHERE id = :id");
            $upd->execute([':id' => (int)$row['id']]);
            $row['status'] = 'em_impressao';
            $row['impresso'] = 1;
        }

        $pedido = cardapio_pedido_array($row, cardapio_itens_pedido_banco($pdo, (int)$row['id']));
        $pdo->commit();
        return $pedido;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}
