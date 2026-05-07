<?php
/**
 * Configuração de conexão MariaDB/MySQL via PDO.
 *
 * Fase 2: este arquivo prepara o projeto para banco de dados, mas as telas
 * continuam usando os arquivos JSON atuais até a migração ser feita.
 *
 * Configure as credenciais por variáveis de ambiente para não versionar senha:
 * DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD.
 */

declare(strict_types=1);

function nograu_db_config(): array {
    return [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => getenv('DB_PORT') ?: '3306',
        'database' => getenv('DB_DATABASE') ?: 'nograuburger',
        'username' => getenv('DB_USERNAME') ?: 'nograuburger_user',
        'password' => getenv('DB_PASSWORD') ?: '',
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
