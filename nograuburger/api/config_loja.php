<?php
require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    api_erro('metodo_invalido', 'Use o método GET.', 405);
}

[$pdo, $loja] = api_autenticar_loja();

try {
    $stmt = $pdo->prepare('SELECT chave, valor, tipo FROM configuracoes_loja WHERE loja_id = :loja_id ORDER BY chave ASC');
    $stmt->execute([':loja_id' => (int)$loja['id']]);

    $config = [];
    foreach ($stmt->fetchAll() as $row) {
        if (in_array($row['chave'], ['api_token', 'token_api'], true)) {
            continue;
        }
        $config[$row['chave']] = [
            'valor' => $row['valor'],
            'tipo' => $row['tipo'],
        ];
    }

    $taxas = [];
    $stmt = $pdo->prepare('SELECT bairro, taxa, consultar FROM taxas_entrega WHERE loja_id = :loja_id AND ativo = 1 ORDER BY bairro ASC');
    $stmt->execute([':loja_id' => (int)$loja['id']]);
    foreach ($stmt->fetchAll() as $row) {
        $taxas[] = [
            'bairro' => $row['bairro'],
            'taxa' => $row['taxa'] !== null ? (float)$row['taxa'] : null,
            'consultar' => (int)$row['consultar'],
        ];
    }

    api_responder([
        'ok' => true,
        'loja' => api_loja_basica($loja),
        'configuracoes' => $config,
        'taxas_entrega' => $taxas,
    ]);
} catch (Throwable $e) {
    api_log_erro('config_loja.php: ' . $e->getMessage());
    api_erro('erro_consulta', 'Não foi possível consultar configurações da loja.', 500);
}
