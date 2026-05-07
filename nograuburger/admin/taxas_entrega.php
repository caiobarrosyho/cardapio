<?php
// admin/taxas_entrega.php
// Protegido por login e com visual no padrão do painel.

session_start();

if (empty($_SESSION['logado_cardapio'])) {
    header('Location: login.php');
    exit;
}

$dataFile = __DIR__ . '/../data/produtos.json';

if (!file_exists($dataFile)) {
    die('Arquivo produtos.json não encontrado.');
}

$dados = json_decode(file_get_contents($dataFile), true);

if (!is_array($dados)) {
    die('Erro ao ler produtos.json. Verifique se o JSON está válido.');
}

if (!isset($dados['loja']) || !is_array($dados['loja'])) {
    $dados['loja'] = [];
}

if (!isset($dados['loja']['taxas_entrega']) || !is_array($dados['loja']['taxas_entrega'])) {
    $dados['loja']['taxas_entrega'] = [];
}

function h($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function normalizar_taxa($taxa) {
    $taxa = trim((string)$taxa);

    if ($taxa === '') {
        return null;
    }

    $taxa = str_replace(['R$', ' '], '', $taxa);
    $taxa = str_replace('.', '', $taxa);
    $taxa = str_replace(',', '.', $taxa);

    return is_numeric($taxa) ? (float)$taxa : null;
}

function salvar_json($arquivo, $dados) {
    file_put_contents(
        $arquivo,
        json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        LOCK_EX
    );
}

$msg = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'adicionar') {
        $bairro = trim($_POST['bairro'] ?? '');
        $taxa   = normalizar_taxa($_POST['taxa'] ?? '');

        if ($bairro === '') {
            $erro = 'Informe o nome do bairro.';
        } else {
            if ($taxa === null) {
                $dados['loja']['taxas_entrega'][] = [
                    'bairro' => $bairro,
                    'taxa' => null,
                    'consultar' => 1
                ];
            } else {
                $dados['loja']['taxas_entrega'][] = [
                    'bairro' => $bairro,
                    'taxa' => $taxa
                ];
            }

            salvar_json($dataFile, $dados);
            $msg = 'Bairro adicionado com sucesso.';
        }
    }

    if ($acao === 'editar') {
        $idx    = (int)($_POST['idx'] ?? -1);
        $bairro = trim($_POST['bairro'] ?? '');
        $taxa   = normalizar_taxa($_POST['taxa'] ?? '');

        if (!isset($dados['loja']['taxas_entrega'][$idx])) {
            $erro = 'Bairro não encontrado.';
        } elseif ($bairro === '') {
            $erro = 'Informe o nome do bairro.';
        } else {
            if ($taxa === null) {
                $dados['loja']['taxas_entrega'][$idx] = [
                    'bairro' => $bairro,
                    'taxa' => null,
                    'consultar' => 1
                ];
            } else {
                $dados['loja']['taxas_entrega'][$idx] = [
                    'bairro' => $bairro,
                    'taxa' => $taxa
                ];
            }

            salvar_json($dataFile, $dados);
            $msg = 'Taxa atualizada com sucesso.';
        }
    }

    if ($acao === 'excluir') {
        $idx = (int)($_POST['idx'] ?? -1);

        if (isset($dados['loja']['taxas_entrega'][$idx])) {
            array_splice($dados['loja']['taxas_entrega'], $idx, 1);
            salvar_json($dataFile, $dados);
            $msg = 'Bairro removido com sucesso.';
        } else {
            $erro = 'Bairro não encontrado.';
        }
    }

    $dados = json_decode(file_get_contents($dataFile), true);
    if (!is_array($dados)) $dados = ['loja' => ['taxas_entrega' => []]];
}

$taxas = $dados['loja']['taxas_entrega'] ?? [];
$totalTaxas = count($taxas);
?>
<!doctype html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<title>Taxas de Entrega - Painel</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="icon" type="image/png" sizes="32x32" href="../assets/img/favicon-32.png">
<link rel="icon" type="image/png" sizes="16x16" href="../assets/img/favicon-16.png">
<link rel="stylesheet" href="../assets/style.css?v=18">
<style>
:root {
    --bg: #0f1115;
    --card: #171a21;
    --card2: #1f2430;
    --line: rgba(255,255,255,.08);
    --text: #f8fafc;
    --muted: #9ca3af;
    --red: #dc2626;
    --red2: #b91c1c;
    --orange: #f97316;
    --green: #22c55e;
    --yellow: #fbbf24;
}

* { box-sizing: border-box; }

body {
    margin: 0;
    font-family: Arial, Helvetica, sans-serif;
    background: radial-gradient(circle at top, #232833 0, var(--bg) 45%, #090a0d 100%);
    color: var(--text);
}

.admin-wrap {
    max-width: 1180px;
    margin: 0 auto;
    padding: 22px;
}

.admin-topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    background: linear-gradient(135deg, #181b22, #111318);
    border: 1px solid var(--line);
    border-radius: 18px;
    padding: 18px 20px;
    margin-bottom: 18px;
    box-shadow: 0 12px 35px rgba(0,0,0,.35);
}

.admin-title h1 {
    margin: 0;
    font-size: 25px;
    line-height: 1.2;
}

.admin-title p {
    margin: 5px 0 0;
    color: var(--muted);
    font-size: 14px;
}

.admin-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.btn-admin,
button.btn-admin {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    min-height: 42px;
    border: 0;
    border-radius: 12px;
    padding: 10px 14px;
    cursor: pointer;
    text-decoration: none;
    font-weight: 700;
    font-size: 14px;
    transition: .15s ease;
}

.btn-admin:hover { transform: translateY(-1px); opacity: .95; }
.btn-dark { background: #2b3140; color: #fff; }
.btn-red { background: var(--red); color: #fff; }
.btn-orange { background: var(--orange); color: #fff; }
.btn-green { background: var(--green); color: #052e16; }

.grid-resumo {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 14px;
    margin-bottom: 18px;
}

.resumo-card {
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 16px;
    padding: 16px;
}

.resumo-card span {
    color: var(--muted);
    font-size: 13px;
}

.resumo-card strong {
    display: block;
    margin-top: 6px;
    font-size: 24px;
}

.card-admin {
    background: rgba(23,26,33,.96);
    border: 1px solid var(--line);
    border-radius: 18px;
    padding: 18px;
    margin-bottom: 18px;
    box-shadow: 0 12px 35px rgba(0,0,0,.28);
}

.card-admin h2 {
    margin: 0 0 14px;
    font-size: 19px;
}

.msg-ok,
.msg-erro {
    padding: 13px 15px;
    border-radius: 14px;
    margin-bottom: 15px;
    font-weight: 700;
}

.msg-ok { background: rgba(34,197,94,.15); color: #bbf7d0; border: 1px solid rgba(34,197,94,.35); }
.msg-erro { background: rgba(220,38,38,.15); color: #fecaca; border: 1px solid rgba(220,38,38,.35); }

.form-add {
    display: grid;
    grid-template-columns: 1fr 170px 150px;
    gap: 12px;
    align-items: end;
}

.campo label {
    display: block;
    margin-bottom: 7px;
    color: #e5e7eb;
    font-size: 13px;
    font-weight: 700;
}

input[type="text"] {
    width: 100%;
    min-height: 43px;
    border-radius: 12px;
    border: 1px solid #303746;
    background: #0f131b;
    color: #fff;
    padding: 10px 12px;
    outline: none;
}

input[type="text"]:focus {
    border-color: var(--orange);
    box-shadow: 0 0 0 3px rgba(249,115,22,.12);
}

.aviso {
    color: var(--yellow);
    font-size: 13px;
    margin: 12px 0 0;
}

.tabela-wrap {
    overflow-x: auto;
}

table.admin-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 10px;
}

.admin-table thead th {
    color: var(--muted);
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: .04em;
    text-align: left;
    padding: 0 10px 4px;
}

.admin-table tbody tr {
    background: var(--card2);
}

.admin-table tbody td {
    padding: 10px;
    border-top: 1px solid var(--line);
    border-bottom: 1px solid var(--line);
}

.admin-table tbody td:first-child {
    border-left: 1px solid var(--line);
    border-radius: 14px 0 0 14px;
}

.admin-table tbody td:last-child {
    border-right: 1px solid var(--line);
    border-radius: 0 14px 14px 0;
}

.linha-form {
    display: grid;
    grid-template-columns: 1fr 150px 105px 105px;
    gap: 8px;
    align-items: center;
}

.badge {
    display: inline-block;
    padding: 6px 10px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 800;
}

.badge-consulta { background: rgba(251,191,36,.15); color: #fde68a; border: 1px solid rgba(251,191,36,.35); }
.badge-valor { background: rgba(34,197,94,.14); color: #bbf7d0; border: 1px solid rgba(34,197,94,.32); }

@media (max-width: 800px) {
    .admin-wrap { padding: 14px; }
    .admin-topbar { align-items: flex-start; flex-direction: column; }
    .grid-resumo { grid-template-columns: 1fr; }
    .form-add { grid-template-columns: 1fr; }
    .linha-form { grid-template-columns: 1fr; }
    .admin-table thead { display: none; }
    table.admin-table, .admin-table tbody, .admin-table tr, .admin-table td { display: block; width: 100%; }
    .admin-table tbody tr { border-radius: 14px; padding: 10px; margin-bottom: 12px; }
    .admin-table tbody td { border: 0 !important; padding: 6px 0; }
}
</style>
</head>
<body>

<div class="admin-wrap">

    <div class="admin-topbar">
        <div class="admin-title">
            <h1>Taxas de Entrega</h1>
            <p>Cadastre, edite ou remova bairros. A taxa aparece clara para o cliente no carrinho.</p>
        </div>

        <div class="admin-actions">
            <a href="index.php" class="btn-admin btn-dark">← Voltar ao painel</a>
            <a href="../index.php" class="btn-admin btn-orange" target="_blank">Ver cardápio</a>
        </div>
    </div>

    <?php if ($msg): ?>
        <div class="msg-ok"><?php echo h($msg); ?></div>
    <?php endif; ?>

    <?php if ($erro): ?>
        <div class="msg-erro"><?php echo h($erro); ?></div>
    <?php endif; ?>

    <div class="grid-resumo">
        <div class="resumo-card">
            <span>Total de bairros</span>
            <strong><?php echo (int)$totalTaxas; ?></strong>
        </div>
        <div class="resumo-card">
            <span>Arquivo editado</span>
            <strong style="font-size:16px;">data/produtos.json</strong>
        </div>
        <div class="resumo-card">
            <span>Taxa vazia</span>
            <strong style="font-size:16px;">A consultar</strong>
        </div>
    </div>

    <div class="card-admin">
        <h2>Adicionar novo bairro</h2>

        <form method="post" class="form-add">
            <input type="hidden" name="acao" value="adicionar">

            <div class="campo">
                <label>Bairro</label>
                <input type="text" name="bairro" placeholder="Ex.: Jardim Nova Alvorada" required>
            </div>

            <div class="campo">
                <label>Taxa R$</label>
                <input type="text" name="taxa" placeholder="Ex.: 8,00">
            </div>

            <button type="submit" class="btn-admin btn-green">Adicionar</button>
        </form>

        <p class="aviso">Se deixar a taxa vazia, o sistema salva como <strong>A consultar</strong>.</p>
    </div>

    <div class="card-admin">
        <h2>Bairros cadastrados</h2>

        <?php if (empty($taxas)): ?>
            <p>Nenhuma taxa cadastrada.</p>
        <?php else: ?>
            <div class="tabela-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Bairro</th>
                            <th>Taxa atual</th>
                            <th>Editar</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($taxas as $idx => $t): ?>
                        <?php
                            $bairro = $t['bairro'] ?? '';
                            $taxa = $t['taxa'] ?? null;
                            $taxaValor = $taxa === null ? '' : number_format((float)$taxa, 2, ',', '');
                        ?>
                        <tr>
                            <td>
                                <form method="post" class="linha-form" id="form_taxa_<?php echo (int)$idx; ?>">
                                    <input type="hidden" name="idx" value="<?php echo (int)$idx; ?>">
                                    <input type="text" name="bairro" value="<?php echo h($bairro); ?>" required>
                                    <input type="text" name="taxa" value="<?php echo h($taxaValor); ?>" placeholder="A consultar">
                                    <button type="submit" name="acao" value="editar" class="btn-admin btn-orange">Salvar</button>
                                    <button type="submit" name="acao" value="excluir" class="btn-admin btn-red" onclick="return confirm('Excluir este bairro?');">Excluir</button>
                                </form>
                            </td>
                            <td>
                                <?php if ($taxa === null): ?>
                                    <span class="badge badge-consulta">A consultar</span>
                                <?php else: ?>
                                    <span class="badge badge-valor">R$ <?php echo number_format((float)$taxa, 2, ',', '.'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span style="color:#9ca3af;font-size:13px;">Edite na linha e clique em salvar.</span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</div>

</body>
</html>
