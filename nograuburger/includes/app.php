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
