<?php
/**
 * Exemplo de configuração PDO para MariaDB/MySQL.
 *
 * Copie este arquivo para `conexao.php` somente se quiser configurar valores
 * diretamente em PHP. A versão principal de `conexao.php` já aceita variáveis
 * de ambiente e não exige senha real no repositório.
 */

declare(strict_types=1);

function nograu_db_config(): array {
    return [
        'host' => '127.0.0.1',
        'port' => '3306',
        'database' => 'nograuburger',
        'username' => 'usuario_do_banco',
        'password' => 'troque_esta_senha_no_servidor',
        'charset' => 'utf8mb4',
    ];
}

function nograu_conexao(): PDO {
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = nograu_db_config();
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        $config['host'],
        $config['port'],
        $config['database'],
        $config['charset']
    );

    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}
