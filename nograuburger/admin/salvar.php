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

set_exception_handler(function(Throwable $e) {
    error_log('Erro inesperado no admin/salvar.php: ' . $e->getMessage());
    $_SESSION['admin_erro'] = 'Não foi possível concluir a operação. Verifique a configuração do banco e tente novamente.';
    if (!headers_sent()) {
        header('Location: index.php');
    }
    exit;
});

function admin_flash_erro($mensagem) {
    $_SESSION['admin_erro'] = $mensagem;
    header('Location: index.php');
    exit;
}

function admin_flash_sucesso($mensagem) {
    $_SESSION['admin_sucesso'] = $mensagem;
}

function admin_pdo_loja_id() {
    global $dados;

    try {
        $pdo = cardapio_conectar_banco();
        $lojaId = cardapio_obter_loja_id($pdo, $dados['loja'] ?? []);
        return [$pdo, $lojaId];
    } catch (Throwable $e) {
        error_log('Erro no painel admin ao acessar banco: ' . $e->getMessage());
        admin_flash_erro('Banco de dados indisponível. Confira config/conexao.php e execute migrar_produtos.php.');
    }
}

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
        return 'uploads/' . $nome;
    }

    return null;
}

function validar_nome_produto() {
    $nome = limpar($_POST['nome'] ?? '');
    if ($nome === '') {
        admin_flash_erro('Informe o nome do produto.');
    }
    return $nome;
}

function validar_preco_produto($campo = 'preco', $obrigatorio = true) {
    $valorOriginal = $_POST[$campo] ?? '';
    $preco = cardapio_preco_decimal($valorOriginal);

    if ($preco === null && $obrigatorio) {
        admin_flash_erro('Informe um preço válido para o produto.');
    }

    return $preco;
}

function proxima_ordem_categoria(PDO $pdo, $lojaId) {
    $stmt = $pdo->prepare('SELECT COALESCE(MAX(ordem), -1) + 1 FROM categorias WHERE loja_id = :loja_id');
    $stmt->execute([':loja_id' => $lojaId]);
    return (int)$stmt->fetchColumn();
}

function proxima_ordem_produto(PDO $pdo, $lojaId, $categoriaId) {
    $stmt = $pdo->prepare('SELECT COALESCE(MAX(ordem), -1) + 1 FROM produtos WHERE loja_id = :loja_id AND categoria_id = :categoria_id');
    $stmt->execute([':loja_id' => $lojaId, ':categoria_id' => $categoriaId]);
    return (int)$stmt->fetchColumn();
}

switch ($acao) {
    case 'loja':
        $dados['loja']['nome']          = $_POST['nome']          ?? '';
        $dados['loja']['tipo']          = $_POST['tipo']          ?? '';
        $dados['loja']['slogan']        = $_POST['slogan']        ?? '';
        $dados['loja']['tempo_entrega'] = $_POST['tempo_entrega'] ?? '';
        $dados['loja']['whatsapp']      = $_POST['whatsapp']      ?? '';
        $dados['loja']['cnpj']          = $_POST['cnpj']          ?? '';
        $dados['loja']['endereco']      = $_POST['endereco']      ?? '';
        $dados['loja']['complemento']   = $_POST['complemento']   ?? '';
        $dados['loja']['bairro']        = $_POST['bairro']        ?? '';
        $dados['loja']['cidade']        = $_POST['cidade']        ?? '';
        $dados['loja']['uf']            = $_POST['uf']            ?? '';
        $dados['loja']['cep']           = $_POST['cep']           ?? '';
        $dados['loja']['pedido_minimo'] = (float)($_POST['pedido_minimo'] ?? 0);
        $dados['loja']['taxa_padrao']   = (float)($_POST['taxa_padrao']   ?? 0);
        $dados['loja']['taxa_rapida']   = (float)($_POST['taxa_rapida']   ?? 0);
        $dados['loja']['texto_entrega'] = $_POST['texto_entrega']  ?? '';
        $dados['loja']['texto_retirada'] = $_POST['texto_retirada'] ?? '';

        foreach (['upsell1_ref', 'upsell2_ref', 'upsell3_ref', 'upsell4_ref'] as $chave) {
            $valor = $_POST[$chave] ?? '';
            $dados['loja'][$chave] = preg_match('/^\d+\|\d+$/', $valor) ? $valor : '';
        }

        foreach (['seg','ter','qua','qui','sex','sab','dom'] as $sigla) {
            $dados['loja']["hora_{$sigla}_abre"]    = $_POST["hora_{$sigla}_abre"]  ?? '';
            $dados['loja']["hora_{$sigla}_fecha"]   = $_POST["hora_{$sigla}_fecha"] ?? '';
            $dados['loja']["hora_{$sigla}_fechado"] = isset($_POST["hora_{$sigla}_fechado"]) ? 1 : 0;
        }

        $logo = upload_arquivo('logo', 'logo_');
        $banner = upload_arquivo('banner', 'banner_');
        if ($logo) $dados['loja']['logo'] = $logo;
        if ($banner) $dados['loja']['banner'] = $banner;

        salvar_json($dataFile, $dados);
        admin_flash_sucesso('Dados da loja salvos.');
        header('Location: index.php');
        exit;

    case 'nova_categoria':
        [$pdo, $lojaId] = admin_pdo_loja_id();
        $titulo = limpar($_POST['titulo'] ?? '');
        if ($titulo === '') admin_flash_erro('Informe o título da categoria.');

        $stmt = $pdo->prepare('INSERT INTO categorias (loja_id, titulo, ordem, ativo) VALUES (:loja_id, :titulo, :ordem, 1)');
        $stmt->execute([':loja_id' => $lojaId, ':titulo' => $titulo, ':ordem' => proxima_ordem_categoria($pdo, $lojaId)]);
        admin_flash_sucesso('Categoria adicionada.');
        header('Location: index.php');
        exit;

    case 'editar_categoria':
        [$pdo, $lojaId] = admin_pdo_loja_id();
        $id = (int)($_POST['idx'] ?? 0);
        $titulo = limpar($_POST['titulo'] ?? '');
        if ($id <= 0 || $titulo === '') admin_flash_erro('Categoria inválida.');

        $stmt = $pdo->prepare('UPDATE categorias SET titulo = :titulo WHERE id = :id AND loja_id = :loja_id');
        $stmt->execute([':titulo' => $titulo, ':id' => $id, ':loja_id' => $lojaId]);
        admin_flash_sucesso('Categoria atualizada.');
        header('Location: index.php');
        exit;

    case 'remover_categoria':
        [$pdo, $lojaId] = admin_pdo_loja_id();
        $id = (int)($_POST['idx'] ?? 0);
        if ($id <= 0) admin_flash_erro('Categoria inválida.');

        $stmt = $pdo->prepare('DELETE FROM categorias WHERE id = :id AND loja_id = :loja_id');
        $stmt->execute([':id' => $id, ':loja_id' => $lojaId]);
        admin_flash_sucesso('Categoria removida.');
        header('Location: index.php');
        exit;

    case 'mover_categoria_cima':
    case 'mover_categoria_baixo':
        [$pdo, $lojaId] = admin_pdo_loja_id();
        $id = (int)($_POST['idx'] ?? 0);
        if ($id <= 0) admin_flash_erro('Categoria inválida.');

        $stmt = $pdo->prepare('SELECT id, ordem FROM categorias WHERE id = :id AND loja_id = :loja_id');
        $stmt->execute([':id' => $id, ':loja_id' => $lojaId]);
        $atual = $stmt->fetch();
        if (!$atual) admin_flash_erro('Categoria não encontrada.');

        $operador = $acao === 'mover_categoria_cima' ? '<' : '>';
        $ordenacao = $acao === 'mover_categoria_cima' ? 'DESC' : 'ASC';
        $stmt = $pdo->prepare("SELECT id, ordem FROM categorias WHERE loja_id = :loja_id AND ordem {$operador} :ordem ORDER BY ordem {$ordenacao}, id {$ordenacao} LIMIT 1");
        $stmt->execute([':loja_id' => $lojaId, ':ordem' => (int)$atual['ordem']]);
        $vizinha = $stmt->fetch();

        if ($vizinha) {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('UPDATE categorias SET ordem = :ordem WHERE id = :id AND loja_id = :loja_id');
            $stmt->execute([':ordem' => (int)$vizinha['ordem'], ':id' => (int)$atual['id'], ':loja_id' => $lojaId]);
            $stmt->execute([':ordem' => (int)$atual['ordem'], ':id' => (int)$vizinha['id'], ':loja_id' => $lojaId]);
            $pdo->commit();
        }
        header('Location: index.php');
        exit;

    case 'novo_produto':
        [$pdo, $lojaId] = admin_pdo_loja_id();
        $catId = (int)($_POST['cat'] ?? 0);
        $nome = validar_nome_produto();
        $preco = validar_preco_produto('preco', true);
        $precoOrig = validar_preco_produto('preco_original', false);
        $foto = upload_arquivo('foto', 'prod_');

        $stmt = $pdo->prepare(
            'INSERT INTO produtos (loja_id, categoria_id, nome, descricao, detalhe, preco, preco_original, foto, ativo, ordem)
             VALUES (:loja_id, :categoria_id, :nome, :descricao, :detalhe, :preco, :preco_original, :foto, :ativo, :ordem)'
        );
        $stmt->execute([
            ':loja_id' => $lojaId,
            ':categoria_id' => $catId,
            ':nome' => $nome,
            ':descricao' => limpar($_POST['descricao'] ?? ''),
            ':detalhe' => limpar($_POST['detalhe'] ?? ''),
            ':preco' => $preco,
            ':preco_original' => $precoOrig,
            ':foto' => $foto ?: '',
            ':ativo' => isset($_POST['ativo']) ? 1 : 0,
            ':ordem' => proxima_ordem_produto($pdo, $lojaId, $catId),
        ]);
        admin_flash_sucesso('Produto adicionado.');
        header('Location: index.php');
        exit;

    case 'remover_produto':
        [$pdo, $lojaId] = admin_pdo_loja_id();
        $prodId = (int)($_POST['prod'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM produtos WHERE id = :id AND loja_id = :loja_id');
        $stmt->execute([':id' => $prodId, ':loja_id' => $lojaId]);
        admin_flash_sucesso('Produto removido.');
        header('Location: index.php');
        exit;

    case 'toggle_produto_ativo':
        [$pdo, $lojaId] = admin_pdo_loja_id();
        $prodId = (int)($_POST['prod'] ?? 0);
        $stmt = $pdo->prepare('UPDATE produtos SET ativo = CASE WHEN ativo = 1 THEN 0 ELSE 1 END WHERE id = :id AND loja_id = :loja_id');
        $stmt->execute([':id' => $prodId, ':loja_id' => $lojaId]);
        header('Location: index.php');
        exit;

    case 'salvar_produto':
        [$pdo, $lojaId] = admin_pdo_loja_id();
        $prodId = (int)($_POST['prod'] ?? 0);
        $catId = (int)($_POST['cat'] ?? 0);
        $nome = validar_nome_produto();
        $preco = validar_preco_produto('preco', true);
        $precoOrig = validar_preco_produto('preco_original', false);
        $foto = upload_arquivo('foto', 'prod_');

        $sqlFoto = $foto ? ', foto = :foto' : '';
        $stmt = $pdo->prepare(
            "UPDATE produtos
             SET categoria_id = :categoria_id, nome = :nome, descricao = :descricao, detalhe = :detalhe,
                 preco = :preco, preco_original = :preco_original, ativo = :ativo {$sqlFoto}
             WHERE id = :id AND loja_id = :loja_id"
        );
        $params = [
            ':categoria_id' => $catId,
            ':nome' => $nome,
            ':descricao' => limpar($_POST['descricao'] ?? ''),
            ':detalhe' => limpar($_POST['detalhe'] ?? ''),
            ':preco' => $preco,
            ':preco_original' => $precoOrig,
            ':ativo' => isset($_POST['ativo']) ? 1 : 0,
            ':id' => $prodId,
            ':loja_id' => $lojaId,
        ];
        if ($foto) $params[':foto'] = $foto;
        $stmt->execute($params);

        admin_flash_sucesso('Produto atualizado.');
        header('Location: index.php');
        exit;

    default:
        header('Location: index.php');
        exit;
}
