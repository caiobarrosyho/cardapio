<?php
/**
 * Bootstrap compartilhado da API do No Grau Burger.
 *
 * Todos os endpoints retornam JSON e usam autenticação por token configurado
 * em configuracoes_loja (chaves: api_token ou token_api). A loja pode ser
 * informada por loja_id ou slug (configuracoes_loja.chave = slug).
 */

require_once __DIR__ . '/../includes/app.php';

header('Content-Type: application/json; charset=utf-8');

function api_log_erro($mensagem) {
    $dir = dirname(__DIR__) . '/logs';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }

    $linha = '[' . date('Y-m-d H:i:s') . '] ' . $mensagem . PHP_EOL;
    @file_put_contents($dir . '/api-' . date('Y-m-d') . '.log', $linha, FILE_APPEND | LOCK_EX);
    error_log('API No Grau Burger: ' . $mensagem);
}

function api_responder($dados, $statusHttp = 200) {
    http_response_code($statusHttp);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function api_erro($codigo, $mensagem, $statusHttp = 400) {
    api_responder([
        'ok' => false,
        'erro' => [
            'codigo' => $codigo,
            'mensagem' => $mensagem,
        ],
    ], $statusHttp);
}

function api_input() {
    $raw = file_get_contents('php://input');
    $json = [];

    if ($raw !== false && trim($raw) !== '') {
        $decodificado = json_decode($raw, true);
        if (is_array($decodificado)) {
            $json = $decodificado;
        }
    }

    return array_replace_recursive($_GET, $_POST, $json);
}

function api_token_informado(array $input) {
    $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if (stripos($auth, 'Bearer ') === 0) {
        return trim(substr($auth, 7));
    }

    return limpar($input['token'] ?? $input['api_token'] ?? '');
}

function api_resolver_loja(PDO $pdo, array $input) {
    $lojaId = isset($input['loja_id']) ? (int)$input['loja_id'] : 0;
    $slug = limpar($input['slug'] ?? $input['loja_slug'] ?? '');

    if ($lojaId > 0) {
        $stmt = $pdo->prepare('SELECT * FROM lojas WHERE id = :id AND ativo = 1 LIMIT 1');
        $stmt->execute([':id' => $lojaId]);
        $loja = $stmt->fetch();
        if ($loja) {
            return $loja;
        }
    }

    if ($slug !== '') {
        $stmt = $pdo->prepare(
            "SELECT l.* FROM lojas l
             INNER JOIN configuracoes_loja c ON c.loja_id = l.id
             WHERE c.chave = 'slug' AND c.valor = :slug AND l.ativo = 1
             LIMIT 1"
        );
        $stmt->execute([':slug' => $slug]);
        $loja = $stmt->fetch();
        if ($loja) {
            return $loja;
        }
    }

    return null;
}

function api_token_valido(PDO $pdo, $lojaId, $token) {
    if ($token === '') {
        return false;
    }

    $stmt = $pdo->prepare(
        "SELECT valor FROM configuracoes_loja
         WHERE loja_id = :loja_id AND chave IN ('api_token', 'token_api')
         ORDER BY FIELD(chave, 'api_token', 'token_api')
         LIMIT 1"
    );
    $stmt->execute([':loja_id' => $lojaId]);
    $valor = (string)($stmt->fetchColumn() ?: '');

    return $valor !== '' && hash_equals($valor, $token);
}

function api_autenticar_loja() {
    $input = api_input();

    try {
        $pdo = cardapio_conectar_banco();
    } catch (Throwable $e) {
        api_log_erro('Falha de conexão: ' . $e->getMessage());
        api_erro('banco_indisponivel', 'Não foi possível acessar os dados agora.', 503);
    }

    try {
        $loja = api_resolver_loja($pdo, $input);
        if (!$loja) {
            api_erro('loja_nao_encontrada', 'Informe uma loja válida por loja_id ou slug.', 404);
        }

        $token = api_token_informado($input);
        if (!api_token_valido($pdo, (int)$loja['id'], $token)) {
            api_erro('token_invalido', 'Token de acesso inválido.', 401);
        }

        return [$pdo, $loja, $input];
    } catch (Throwable $e) {
        api_log_erro('Falha de autenticação/loja: ' . $e->getMessage());
        api_erro('erro_api', 'Não foi possível processar a solicitação.', 500);
    }
}

function api_loja_basica(array $loja) {
    return [
        'id' => (int)$loja['id'],
        'nome' => $loja['nome'] ?? '',
        'tipo' => $loja['tipo'] ?? '',
        'slogan' => $loja['slogan'] ?? '',
        'whatsapp' => $loja['whatsapp'] ?? '',
        'endereco' => $loja['endereco'] ?? '',
        'bairro' => $loja['bairro'] ?? '',
        'cidade' => $loja['cidade'] ?? '',
        'uf' => $loja['uf'] ?? '',
        'logo' => $loja['logo'] ?? '',
        'banner' => $loja['banner'] ?? '',
    ];
}
