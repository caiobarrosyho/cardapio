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
        if (!isset($dados['categorias'][$catIdx]['produtos'][$prodIdx])) {
            return null;
        }

        $produto = $dados['categorias'][$catIdx]['produtos'][$prodIdx];

        return cardapio_produto_ativo($produto) ? $produto : null;
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
