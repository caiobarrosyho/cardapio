<?php
require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    api_erro('metodo_invalido', 'Use o método GET.', 405);
}

[$pdo, $loja] = api_autenticar_loja();

try {
    $stmt = $pdo->prepare(
        'SELECT id, titulo, descricao, ordem, ativo
         FROM categorias
         WHERE loja_id = :loja_id AND ativo = 1
         ORDER BY ordem ASC, id ASC'
    );
    $stmt->execute([':loja_id' => (int)$loja['id']]);

    api_responder([
        'ok' => true,
        'loja' => api_loja_basica($loja),
        'categorias' => $stmt->fetchAll(),
    ]);
} catch (Throwable $e) {
    api_log_erro('categorias.php: ' . $e->getMessage());
    api_erro('erro_consulta', 'Não foi possível consultar categorias.', 500);
}
