<?php
session_start();
require_once __DIR__ . '/../includes/app.php';

if (empty($_SESSION['logado_cardapio'])) {
    header('Location: login.php');
    exit;
}

$pedidos = cardapio_carregar_pedidos();

$id = $_GET['id'] ?? '';
if ($id === '') {
    echo "Pedido não informado.";
    exit;
}

// localizar pedido
$pedido = null;
$index  = null;
foreach ($pedidos as $k => $p) {
    if (!empty($p['id']) && $p['id'] === $id) {
        $pedido = $p;
        $index  = $k;
        break;
    }
}

if ($pedido === null) {
    echo "Pedido não encontrado.";
    exit;
}

// tratar POST (salvar ajuste)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo = $_POST['ajuste_tipo'] ?? '';
    $val  = $_POST['ajuste_valor'] ?? '';
    $obs  = $_POST['ajuste_obs']   ?? '';

    $tipo = in_array($tipo, ['acrescimo','desconto'], true) ? $tipo : '';
    $num  = (float)str_replace(',', '.', (string)$val);
    if ($num < 0) $num = 0;

    $pedido['ajuste_tipo']  = $tipo;
    $pedido['ajuste_valor'] = $num;
    $pedido['ajuste_obs']   = $obs;

    // atualiza no array principal
    if ($index !== null) {
        $pedidos[$index] = $pedido;
        cardapio_salvar_pedidos($pedidos);
    }

    // volta para a tela do pedido (ver/imprimir)
    $extra = '';
    if (!empty($_GET['todos']) && $_GET['todos'] === '1') {
        $extra = '&todos=1';
    }
    header('Location: pedidos.php?id=' . urlencode($id) . $extra);
    exit;
}

// valores atuais do ajuste
$ajusteTipo  = $pedido['ajuste_tipo']  ?? '';
$ajusteValor = (float)($pedido['ajuste_valor'] ?? 0);
$ajusteObs   = $pedido['ajuste_obs']   ?? '';

$subtotalItens = 0.0;
if (isset($pedido['subtotal'])) {
    $subtotalItens = (float)str_replace(',', '.', (string)$pedido['subtotal']);
}

// total final considerando ajuste
$totalFinal = $subtotalItens;
if ($ajusteTipo === 'acrescimo') {
    $totalFinal += $ajusteValor;
} elseif ($ajusteTipo === 'desconto') {
    $totalFinal -= $ajusteValor;
}
if ($totalFinal < 0) $totalFinal = 0;
?>
<!doctype html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<title>Ajustar pedido</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="../assets/style.css?v=15">
<style>
  .ajuste-box {
    max-width: 480px;
    margin: 20px auto;
  }
  .ajuste-resumo {
    font-size: 13px;
    background:#fafafa;
    border-radius:8px;
    padding:8px 10px;
    margin-bottom:12px;
  }
  .ajuste-resumo div { margin-bottom:2px; }
</style>
</head>
<body>

<div class="admin-wrapper">
  <div class="admin-header">
    <h1>Ajustar valor do pedido</h1>
    <a href="pedidos.php" class="btn btn-outline">← Voltar para pedidos</a>
  </div>

  <div class="admin-box ajuste-box">
    <div class="ajuste-resumo">
      <div><strong>Cliente:</strong> <?php echo h($pedido['nome'] ?? ''); ?></div>
      <div><strong>Subtotal dos itens:</strong>
        R$ <?php echo number_format($subtotalItens, 2, ',', '.'); ?>
      </div>
      <div><strong>Total atual (com ajuste):</strong>
        R$ <?php echo number_format($totalFinal, 2, ',', '.'); ?>
      </div>
    </div>

    <form method="post" action="">
      <div class="admin-form-row">
        <div>
          <label>Tipo de ajuste</label>
          <select name="ajuste_tipo">
            <option value="">Nenhum (remover ajuste)</option>
            <option value="acrescimo" <?php echo $ajusteTipo==='acrescimo'?'selected':''; ?>>
              Acréscimo (somar ao total)
            </option>
            <option value="desconto" <?php echo $ajusteTipo==='desconto'?'selected':''; ?>>
              Desconto (subtrair do total)
            </option>
          </select>
        </div>
        <div>
          <label>Valor (R$)</label>
          <input type="number" step="0.01" min="0" name="ajuste_valor"
                 value="<?php echo $ajusteValor > 0 ? h(number_format($ajusteValor,2,'.','')) : ''; ?>">
        </div>
      </div>

      <div class="admin-form-row">
        <div style="flex:1;">
          <label>Observação do ajuste (opcional)</label>
          <textarea name="ajuste_obs" rows="3"><?php echo h($ajusteObs); ?></textarea>
        </div>
      </div>

      <div style="margin-top:10px;display:flex;gap:8px;">
        <button type="submit" class="btn btn-red">Salvar ajuste</button>
        <a href="pedidos.php<?php echo '?id='.urlencode($id); ?><?php
          echo (!empty($_GET['todos']) && $_GET['todos']==='1') ? '&todos=1' : '';
        ?>" class="btn btn-outline">Cancelar</a>
      </div>
    </form>
  </div>
</div>

</body>
</html>
