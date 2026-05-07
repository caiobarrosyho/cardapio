<?php
/**
 * Migra categorias e produtos de data/produtos.json para MariaDB/MySQL.
 *
 * O arquivo JSON não é removido. Antes da importação é criado um backup em
 * data/backups/. Pedidos continuam no JSON nesta fase.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/app.php';

function migracao_saida($mensagem, $ok = true) {
    $cli = PHP_SAPI === 'cli';

    if ($cli) {
        echo ($ok ? '[OK] ' : '[ERRO] ') . $mensagem . PHP_EOL;
        return;
    }

    echo '<p style="font-family:Arial,sans-serif;color:' . ($ok ? '#166534' : '#b91c1c') . ';">'
        . h(($ok ? 'OK: ' : 'ERRO: ') . $mensagem)
        . '</p>';
}

function migracao_backup_produtos_json() {
    $origem = cardapio_data_path('produtos.json');

    if (!file_exists($origem)) {
        throw new RuntimeException('Arquivo data/produtos.json não encontrado.');
    }

    $backupDir = cardapio_data_path('backups');
    if (!is_dir($backupDir)) {
        @mkdir($backupDir, 0775, true);
    }

    $destino = $backupDir . '/produtos_' . date('Ymd_His') . '.json';
    if (!copy($origem, $destino)) {
        throw new RuntimeException('Não foi possível criar o backup automático de produtos.json.');
    }

    return $destino;
}

function migracao_buscar_categoria(PDO $pdo, $lojaId, $titulo) {
    $stmt = $pdo->prepare('SELECT id FROM categorias WHERE loja_id = :loja_id AND titulo = :titulo LIMIT 1');
    $stmt->execute([
        ':loja_id' => $lojaId,
        ':titulo' => $titulo,
    ]);

    $id = $stmt->fetchColumn();
    return $id ? (int)$id : null;
}

function migracao_salvar_categoria(PDO $pdo, $lojaId, array $categoria, $ordem) {
    $titulo = limpar($categoria['titulo'] ?? '');
    if ($titulo === '') {
        return null;
    }

    $existenteId = migracao_buscar_categoria($pdo, $lojaId, $titulo);

    if ($existenteId) {
        $stmt = $pdo->prepare(
            'UPDATE categorias SET descricao = :descricao, ordem = :ordem, ativo = :ativo WHERE id = :id AND loja_id = :loja_id'
        );
        $stmt->execute([
            ':descricao' => limpar($categoria['descricao'] ?? ''),
            ':ordem' => $ordem,
            ':ativo' => isset($categoria['ativo']) ? (int)$categoria['ativo'] : 1,
            ':id' => $existenteId,
            ':loja_id' => $lojaId,
        ]);

        return $existenteId;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO categorias (loja_id, titulo, descricao, ordem, ativo) VALUES (:loja_id, :titulo, :descricao, :ordem, :ativo)'
    );
    $stmt->execute([
        ':loja_id' => $lojaId,
        ':titulo' => $titulo,
        ':descricao' => limpar($categoria['descricao'] ?? ''),
        ':ordem' => $ordem,
        ':ativo' => isset($categoria['ativo']) ? (int)$categoria['ativo'] : 1,
    ]);

    return (int)$pdo->lastInsertId();
}

function migracao_buscar_produto(PDO $pdo, $lojaId, $categoriaId, $nome) {
    $stmt = $pdo->prepare(
        'SELECT id FROM produtos WHERE loja_id = :loja_id AND categoria_id = :categoria_id AND nome = :nome LIMIT 1'
    );
    $stmt->execute([
        ':loja_id' => $lojaId,
        ':categoria_id' => $categoriaId,
        ':nome' => $nome,
    ]);

    $id = $stmt->fetchColumn();
    return $id ? (int)$id : null;
}

function migracao_salvar_produto(PDO $pdo, $lojaId, $categoriaId, array $produto, $ordem) {
    $nome = limpar($produto['nome'] ?? '');
    $preco = cardapio_preco_decimal($produto['preco'] ?? null);

    if ($nome === '' || $preco === null) {
        return false;
    }

    $dados = [
        ':loja_id' => $lojaId,
        ':categoria_id' => $categoriaId,
        ':nome' => $nome,
        ':descricao' => limpar($produto['descricao'] ?? ''),
        ':detalhe' => limpar($produto['detalhe'] ?? ''),
        ':preco' => $preco,
        ':preco_original' => cardapio_preco_decimal($produto['preco_original'] ?? '') ?: null,
        ':foto' => limpar($produto['foto'] ?? ''),
        ':imagem' => limpar($produto['imagem'] ?? ''),
        ':destaque' => isset($produto['destaque']) ? (int)$produto['destaque'] : 0,
        ':ativo' => isset($produto['ativo']) ? (int)$produto['ativo'] : 1,
        ':ordem' => $ordem,
    ];

    $existenteId = migracao_buscar_produto($pdo, $lojaId, $categoriaId, $nome);

    if ($existenteId) {
        $dados[':id'] = $existenteId;
        $stmt = $pdo->prepare(
            'UPDATE produtos
             SET descricao = :descricao, detalhe = :detalhe, preco = :preco, preco_original = :preco_original,
                 foto = :foto, imagem = :imagem, destaque = :destaque, ativo = :ativo, ordem = :ordem
             WHERE id = :id AND loja_id = :loja_id AND categoria_id = :categoria_id'
        );
        $stmt->execute($dados);
        return true;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO produtos (loja_id, categoria_id, nome, descricao, detalhe, preco, preco_original, foto, imagem, destaque, ativo, ordem)
         VALUES (:loja_id, :categoria_id, :nome, :descricao, :detalhe, :preco, :preco_original, :foto, :imagem, :destaque, :ativo, :ordem)'
    );
    $stmt->execute($dados);

    return true;
}

if (PHP_SAPI !== 'cli') {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="pt-br"><head><meta charset="utf-8"><title>Migração de produtos</title></head><body>';
    echo '<h1 style="font-family:Arial,sans-serif;">Migração de produtos</h1>';
}

try {
    $dados = cardapio_carregar_produtos();
    $categorias = $dados['categorias'] ?? [];

    if (!$categorias) {
        throw new RuntimeException('Nenhuma categoria encontrada em data/produtos.json.');
    }

    $backup = migracao_backup_produtos_json();
    migracao_saida('Backup criado em ' . str_replace(CARDAPIO_ROOT . '/', '', $backup));

    $pdo = cardapio_conectar_banco();
    $lojaId = cardapio_obter_loja_id($pdo, $dados['loja'] ?? []);

    $pdo->beginTransaction();

    $totalCategorias = 0;
    $totalProdutos = 0;

    foreach ($categorias as $catOrdem => $categoria) {
        $categoriaId = migracao_salvar_categoria($pdo, $lojaId, $categoria, $catOrdem);
        if (!$categoriaId) {
            continue;
        }

        $totalCategorias++;

        foreach (($categoria['produtos'] ?? []) as $prodOrdem => $produto) {
            if (migracao_salvar_produto($pdo, $lojaId, $categoriaId, $produto, $prodOrdem)) {
                $totalProdutos++;
            }
        }
    }

    $pdo->commit();

    migracao_saida('Categorias importadas/atualizadas: ' . $totalCategorias);
    migracao_saida('Produtos importados/atualizados: ' . $totalProdutos);
    migracao_saida('Migração concluída. O produtos.json foi mantido como backup/fonte histórica.');
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Erro na migração de produtos No Grau Burger: ' . $e->getMessage());
    migracao_saida('Não foi possível migrar os produtos. Verifique se o banco foi criado/importado e se config/conexao.php está configurado corretamente.', false);
}

if (PHP_SAPI !== 'cli') {
    echo '</body></html>';
}
