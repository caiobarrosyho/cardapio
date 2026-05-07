<?php
require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    api_erro('metodo_invalido', 'Use o método GET.', 405);
}

[$pdo, $loja, $input] = api_autenticar_loja();
$categoriaId = isset($input['categoria_id']) ? (int)$input['categoria_id'] : 0;

try {
    $sql = 'SELECT p.id, p.categoria_id, c.titulo AS categoria, p.nome, p.descricao, p.detalhe,
                   p.preco, p.preco_original, p.foto, p.imagem, p.destaque, p.ativo, p.ordem
            FROM produtos p
            INNER JOIN categorias c ON c.id = p.categoria_id
            WHERE p.loja_id = :loja_id AND p.ativo = 1 AND c.ativo = 1';
    $params = [':loja_id' => (int)$loja['id']];

    if ($categoriaId > 0) {
        $sql .= ' AND p.categoria_id = :categoria_id';
        $params[':categoria_id'] = $categoriaId;
    }

    $sql .= ' ORDER BY c.ordem ASC, p.ordem ASC, p.id ASC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $produtos = [];
    foreach ($stmt->fetchAll() as $p) {
        $p['id'] = (int)$p['id'];
        $p['categoria_id'] = (int)$p['categoria_id'];
        $p['preco'] = (float)$p['preco'];
        $p['preco_original'] = $p['preco_original'] !== null ? (float)$p['preco_original'] : null;
        $p['destaque'] = (int)$p['destaque'];
        $p['ativo'] = (int)$p['ativo'];
        $p['ordem'] = (int)$p['ordem'];
        $produtos[] = $p;
    }

    api_responder([
        'ok' => true,
        'loja' => api_loja_basica($loja),
        'produtos' => $produtos,
    ]);
} catch (Throwable $e) {
    api_log_erro('produtos.php: ' . $e->getMessage());
    api_erro('erro_consulta', 'Não foi possível consultar produtos.', 500);
}
