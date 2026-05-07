<?php
session_start();
require_once __DIR__ . '/../includes/app.php';

if (empty($_SESSION['logado_cardapio'])) {
    header('Location: login.php');
    exit;
}

$dataFile = cardapio_data_path('produtos.json');
$dados = cardapio_carregar_produtos();

$acao = $_POST['acao'] ?? '';

// ---------- funções utilitárias ----------
function salvar_json($arquivo, $dados) {
    return cardapio_salvar_json($arquivo, $dados);
}

function upload_arquivo($campo, $prefixo = 'arq_') {
    if (empty($_FILES[$campo]['name']) || $_FILES[$campo]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $orig = basename($_FILES[$campo]['name']);
    $ext  = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
    if (!$ext) $ext = 'jpg';

    $dir = __DIR__ . '/../uploads/';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }

    $nome = $prefixo . time() . '_' . preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $orig);
    $dest = $dir . $nome;

    if (move_uploaded_file($_FILES[$campo]['tmp_name'], $dest)) {
        return 'uploads/' . $nome; // caminho relativo
    }

    return null;
}

// ---------- switch das ações ----------
switch ($acao) {

    case 'loja':
        // dados básicos
        $dados['loja']['nome']          = $_POST['nome']          ?? '';
        $dados['loja']['tipo']          = $_POST['tipo']          ?? '';
        $dados['loja']['slogan']        = $_POST['slogan']        ?? '';
        $dados['loja']['tempo_entrega'] = $_POST['tempo_entrega'] ?? '';

        // contato
        $dados['loja']['whatsapp'] = $_POST['whatsapp'] ?? '';
        $dados['loja']['cnpj']     = $_POST['cnpj']     ?? '';

        // endereço
        $dados['loja']['endereco']    = $_POST['endereco']    ?? '';
        $dados['loja']['complemento'] = $_POST['complemento'] ?? '';
        $dados['loja']['bairro']      = $_POST['bairro']      ?? '';
        $dados['loja']['cidade']      = $_POST['cidade']      ?? '';
        $dados['loja']['uf']          = $_POST['uf']          ?? '';
        $dados['loja']['cep']         = $_POST['cep']         ?? '';

        // taxas / textos
        $dados['loja']['pedido_minimo']  = (float)($_POST['pedido_minimo'] ?? 0);
        $dados['loja']['taxa_padrao']    = (float)($_POST['taxa_padrao']   ?? 0);
        $dados['loja']['taxa_rapida']    = (float)($_POST['taxa_rapida']   ?? 0);
        $dados['loja']['texto_entrega']  = $_POST['texto_entrega']  ?? '';
        $dados['loja']['texto_retirada'] = $_POST['texto_retirada'] ?? '';

        // --------- upsell no carrinho (produtos sugeridos) ---------
        // formato esperado: "catIdx|prodIdx" (ex: "0|3")
        $refs = [
            'upsell1_ref' => $_POST['upsell1_ref'] ?? '',
            'upsell2_ref' => $_POST['upsell2_ref'] ?? '',
            'upsell3_ref' => $_POST['upsell3_ref'] ?? '',
            'upsell4_ref' => $_POST['upsell4_ref'] ?? '',
        ];

        foreach ($refs as $chave => $valor) {
            if (preg_match('/^\d+\|\d+$/', $valor)) {
                $dados['loja'][$chave] = $valor;
            } else {
                $dados['loja'][$chave] = '';
            }
        }

        // --------- horários ---------
        $dias = ['seg','ter','qua','qui','sex','sab','dom'];
        foreach ($dias as $sigla) {
            $dados['loja']["hora_{$sigla}_abre"]    = $_POST["hora_{$sigla}_abre"]  ?? '';
            $dados['loja']["hora_{$sigla}_fecha"]   = $_POST["hora_{$sigla}_fecha"] ?? '';
            $dados['loja']["hora_{$sigla}_fechado"] = isset($_POST["hora_{$sigla}_fechado"]) ? 1 : 0;
        }

        // --------- imagens ---------
        $logo   = upload_arquivo('logo',   'logo_');
        $banner = upload_arquivo('banner','banner_');
        if ($logo)   $dados['loja']['logo']   = $logo;
        if ($banner) $dados['loja']['banner'] = $banner;

        salvar_json($dataFile, $dados);
        header('Location: index.php');
        exit;

    case 'nova_categoria':
        $titulo = trim($_POST['titulo'] ?? '');
        if ($titulo !== '') {
            $dados['categorias'][] = [
                'titulo'   => $titulo,
                'produtos' => []
            ];
            salvar_json($dataFile, $dados);
        }
        header('Location: index.php');
        exit;

    case 'editar_categoria':
        $idx    = (int)($_POST['idx'] ?? 0);
        $titulo = trim($_POST['titulo'] ?? '');
        if (isset($dados['categorias'][$idx]) && $titulo !== '') {
            $dados['categorias'][$idx]['titulo'] = $titulo;
            salvar_json($dataFile, $dados);
        }
        header('Location: index.php');
        exit;

    case 'remover_categoria':
        $idx = (int)($_POST['idx'] ?? -1);
        if (isset($dados['categorias'][$idx])) {
            array_splice($dados['categorias'], $idx, 1);
            salvar_json($dataFile, $dados);
        }
        header('Location: index.php');
        exit;

    case 'mover_categoria_cima':
        $idx = (int)($_POST['idx'] ?? -1);
        if ($idx > 0 && isset($dados['categorias'][$idx])) {
            $tmp = $dados['categorias'][$idx - 1];
            $dados['categorias'][$idx - 1] = $dados['categorias'][$idx];
            $dados['categorias'][$idx]     = $tmp;
            salvar_json($dataFile, $dados);
        }
        header('Location: index.php');
        exit;

    case 'mover_categoria_baixo':
        $idx = (int)($_POST['idx'] ?? -1);
        $max = count($dados['categorias']) - 1;
        if ($idx >= 0 && $idx < $max && isset($dados['categorias'][$idx])) {
            $tmp = $dados['categorias'][$idx + 1];
            $dados['categorias'][$idx + 1] = $dados['categorias'][$idx];
            $dados['categorias'][$idx]     = $tmp;
            salvar_json($dataFile, $dados);
        }
        header('Location: index.php');
        exit;

    case 'novo_produto':
        $catIdx = (int)($_POST['cat'] ?? 0);
        if (!isset($dados['categorias'][$catIdx])) {
            header('Location: index.php');
            exit;
        }

        $nome      = trim($_POST['nome'] ?? '');
        $preco     = (float)($_POST['preco'] ?? 0);
        $descricao = trim($_POST['descricao'] ?? '');
        $detalhe   = trim($_POST['detalhe'] ?? '');
        $precoOrig = $_POST['preco_original'] !== '' ? (float)$_POST['preco_original'] : null;
        $ativo     = isset($_POST['ativo']) ? 1 : 0;

        if ($nome === '' || $preco <= 0) {
            header('Location: index.php');
            exit;
        }

        $foto = upload_arquivo('foto', 'prod_');

        $produto = [
            'nome'           => $nome,
            'descricao'      => $descricao,
            'detalhe'        => $detalhe,
            'preco'          => $preco,
            'preco_original' => $precoOrig,
            'ativo'          => $ativo,
        ];
        if ($foto) {
            $produto['foto'] = $foto;
        }

        $dados['categorias'][$catIdx]['produtos'][] = $produto;
        salvar_json($dataFile, $dados);
        header('Location: index.php');
        exit;

    case 'remover_produto':
        $catIdx  = (int)($_POST['cat']  ?? 0);
        $prodIdx = (int)($_POST['prod'] ?? 0);

        if (isset($dados['categorias'][$catIdx]['produtos'][$prodIdx])) {
            array_splice($dados['categorias'][$catIdx]['produtos'], $prodIdx, 1);
            salvar_json($dataFile, $dados);
        }
        header('Location: index.php');
        exit;

    case 'toggle_produto_ativo':
        $catIdx  = (int)($_POST['cat']  ?? 0);
        $prodIdx = (int)($_POST['prod'] ?? 0);

        if (isset($dados['categorias'][$catIdx]['produtos'][$prodIdx])) {
            $atual = !empty($dados['categorias'][$catIdx]['produtos'][$prodIdx]['ativo']);
            $dados['categorias'][$catIdx]['produtos'][$prodIdx]['ativo'] = $atual ? 0 : 1;
            salvar_json($dataFile, $dados);
        }
        header('Location: index.php');
        exit;

    case 'salvar_produto':
        $catIdx  = (int)($_POST['cat']  ?? 0);
        $prodIdx = (int)($_POST['prod'] ?? 0);

        if (!isset($dados['categorias'][$catIdx]['produtos'][$prodIdx])) {
            header('Location: index.php');
            exit;
        }

        $nome      = trim($_POST['nome'] ?? '');
        $preco     = (float)($_POST['preco'] ?? 0);
        $descricao = trim($_POST['descricao'] ?? '');
        $detalhe   = trim($_POST['detalhe'] ?? '');
        $precoOrig = $_POST['preco_original'] !== '' ? (float)$_POST['preco_original'] : null;
        $ativo     = isset($_POST['ativo']) ? 1 : 0;

        if ($nome === '' || $preco <= 0) {
            header('Location: index.php');
            exit;
        }

        $prod =& $dados['categorias'][$catIdx]['produtos'][$prodIdx];
        $prod['nome']           = $nome;
        $prod['descricao']      = $descricao;
        $prod['detalhe']        = $detalhe;
        $prod['preco']          = $preco;
        $prod['preco_original'] = $precoOrig;
        $prod['ativo']          = $ativo;

        $foto = upload_arquivo('foto', 'prod_');
        if ($foto) {
            $prod['foto'] = $foto;
        }

        salvar_json($dataFile, $dados);
        header('Location: index.php');
        exit;

    default:
        header('Location: index.php');
        exit;
}
