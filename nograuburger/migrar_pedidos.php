<?php
/**
 * Migra pedidos de data/pedidos.json para MariaDB/MySQL.
 *
 * O pedidos.json não é removido. Antes da importação é criado um backup em
 * data/backups/. Produtos/categorias já devem ter sido migrados na fase 3.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/app.php';

function migracao_pedidos_saida($mensagem, $ok = true) {
    if (PHP_SAPI === 'cli') {
        echo ($ok ? '[OK] ' : '[ERRO] ') . $mensagem . PHP_EOL;
        return;
    }

    echo '<p style="font-family:Arial,sans-serif;color:' . ($ok ? '#166534' : '#b91c1c') . ';">'
        . h(($ok ? 'OK: ' : 'ERRO: ') . $mensagem)
        . '</p>';
}

function migracao_backup_pedidos_json() {
    $origem = cardapio_data_path('pedidos.json');

    if (!file_exists($origem)) {
        throw new RuntimeException('Arquivo data/pedidos.json não encontrado.');
    }

    $backupDir = cardapio_data_path('backups');
    if (!is_dir($backupDir)) {
        @mkdir($backupDir, 0775, true);
    }

    $destino = $backupDir . '/pedidos_' . date('Ymd_His') . '.json';
    if (!copy($origem, $destino)) {
        throw new RuntimeException('Não foi possível criar backup automático de pedidos.json.');
    }

    return $destino;
}

if (PHP_SAPI !== 'cli') {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="pt-br"><head><meta charset="utf-8"><title>Migração de pedidos</title></head><body>';
    echo '<h1 style="font-family:Arial,sans-serif;">Migração de pedidos</h1>';
}

try {
    $pedidos = cardapio_carregar_pedidos();
    if (!is_array($pedidos)) {
        $pedidos = [];
    }

    $backup = migracao_backup_pedidos_json();
    migracao_pedidos_saida('Backup criado em ' . str_replace(CARDAPIO_ROOT . '/', '', $backup));

    $pdo = cardapio_conectar_banco();
    $pdo->beginTransaction();

    $total = 0;
    foreach ($pedidos as $pedido) {
        if (!is_array($pedido)) {
            continue;
        }

        cardapio_pedido_para_banco($pdo, $pedido);
        $total++;
    }

    $pdo->commit();

    migracao_pedidos_saida('Pedidos importados/atualizados: ' . $total);
    migracao_pedidos_saida('Migração concluída. O pedidos.json foi mantido como backup/fonte histórica.');
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Erro na migração de pedidos No Grau Burger: ' . $e->getMessage());
    migracao_pedidos_saida('Não foi possível migrar os pedidos. Verifique o banco, a tabela pedidos/pedido_itens e config/conexao.php.', false);
}

if (PHP_SAPI !== 'cli') {
    echo '</body></html>';
}
