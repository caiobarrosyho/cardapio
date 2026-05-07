<?php
session_start();
require __DIR__ . '/funcoes_carrinho.php';
require_once __DIR__ . '/includes/app.php';

$dados = cardapio_carregar_produtos();
$loja  = $dados['loja'] ?? [];


$totais = carrinho_totais();
if ($totais['itens'] === 0) {
    header('Location: index.php');
    exit;
}

// valores padrão
$taxa_padrao = isset($loja['taxa_padrao']) ? (float)$loja['taxa_padrao'] : 5.99;

// pega dados do POST (se já enviou)
$nome      = $_POST['nome']      ?? '';
$telefone  = $_POST['telefone']  ?? '';
$endereco  = $_POST['endereco']  ?? '';
$bairro    = $_POST['bairro']    ?? '';
$cidade    = $_POST['cidade']    ?? '';
$complemento = $_POST['complemento'] ?? '';

$tipoEntrega = $_POST['tipo_entrega'] ?? 'Entrega';
$taxaEntrega = $_POST['taxa_entrega'] !== '' ? (float)$_POST['taxa_entrega'] : $taxa_padrao;
$pagamento   = $_POST['pagamento']   ?? 'Dinheiro';
$trocoPara   = $_POST['troco_para']  ?? '';
$observacoes = $_POST['observacoes'] ?? '';

$pedidoEnviado = ($_SERVER['REQUEST_METHOD'] === 'POST');
$totalFinal = $totais['subtotal'] + $taxaEntrega;
?>
<!doctype html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<title>Fechar pedido – <?php echo h($loja['nome'] ?? 'Loja'); ?></title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="assets/style.css?v=6">
</head>
<body>

<header class="top-nav">
  <div class="top-nav-inner">
    <div class="top-left">
      <div class="top-logo">cardápio</div>
      <div class="top-menu">
        <span class="active">Fechar pedido</span>
      </div>
    </div>
    <div class="top-searchbox">
      <span></span>
    </div>
    <div class="top-right">
      <a href="carrinho.php" style="font-size:12px;">Voltar à sacola</a>
    </div>
  </div>
</header>

<div class="checkout-wrapper">
  <div class="checkout-grid">
    <div class="checkout-col">
      <div class="checkout-box">
        <h2>Dados do cliente</h2>
        <form method="post" action="checkout.php">
          <div class="checkout-row">
            <div>
              <label>Nome</label>
              <input type="text" name="nome" value="<?php echo h($nome); ?>" required>
            </div>
            <div>
              <label>Telefone / WhatsApp</label>
              <input type="text" name="telefone" value="<?php echo h($telefone); ?>" required>
            </div>
          </div>

          <div class="checkout-row">
            <div>
              <label>Endereço (rua, número)</label>
              <input type="text" name="endereco" value="<?php echo h($endereco); ?>" required>
            </div>
          </div>

          <div class="checkout-row">
            <div>
              <label>Bairro</label>
              <input type="text" name="bairro" value="<?php echo h($bairro); ?>" required>
            </div>
            <div>
              <label>Cidade</label>
              <input type="text" name="cidade" value="<?php echo h($cidade); ?>" required>
            </div>
          </div>

          <div class="checkout-row">
            <div>
              <label>Complemento / ponto de referência</label>
              <input type="text" name="complemento" value="<?php echo h($complemento); ?>">
            </div>
          </div>

          <div class="checkout-row">
            <div>
              <label>Tipo de entrega</label>
              <select name="tipo_entrega">
                <option value="Entrega" <?php echo $tipoEntrega==='Entrega'?'selected':''; ?>>Entrega</option>
                <option value="Retirada" <?php echo $tipoEntrega==='Retirada'?'selected':''; ?>>Retirada</option>
              </select>
            </div>
            <div>
              <label>Taxa de entrega (pode editar)</label>
              <input type="number" step="0.01" name="taxa_entrega"
                     value="<?php echo h(number_format($taxaEntrega, 2, '.', '')); ?>">
            </div>
          </div>

          <div class="checkout-row">
            <div>
              <label>Forma de pagamento</label>
              <select name="pagamento">
                <option <?php echo $pagamento==='Dinheiro'?'selected':''; ?>>Dinheiro</option>
                <option <?php echo $pagamento==='Cartão'?'selected':''; ?>>Cartão</option>
                <option <?php echo $pagamento==='PIX'?'selected':''; ?>>PIX</option>
              </select>
            </div>
            <div>
              <label>Troco para (se dinheiro)</label>
              <input type="text" name="troco_para" value="<?php echo h($trocoPara); ?>">
            </div>
          </div>

          <div class="checkout-row">
            <div>
              <label>Observações do pedido</label>
              <textarea name="observacoes" rows="3"><?php echo h($observacoes); ?></textarea>
            </div>
          </div>

          <div class="checkout-row">
            <div>
              <button type="submit" class="btn btn-red">Gerar resumo do pedido</button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <div class="checkout-col">
      <div class="checkout-box">
        <h2>Resumo</h2>

        <ul class="checkout-itens">
          <?php foreach ($_SESSION['carrinho'] as $item): ?>
            <li>
              <span><?php echo (int)$item['qtd']; ?>x <?php echo h($item['nome']); ?></span>
              <span>R$ <?php echo number_format($item['preco'] * $item['qtd'], 2, ',', '.'); ?></span>
            </li>
          <?php endforeach; ?>
        </ul>

        <div class="checkout-resumo-valores">
          <div><span>Subtotal</span><span>R$ <?php echo number_format($totais['subtotal'], 2, ',', '.'); ?></span></div>
          <div><span>Taxa de entrega</span><span>R$ <?php echo number_format($taxaEntrega, 2, ',', '.'); ?></span></div>
          <div class="checkout-total"><span>Total</span><span>R$ <?php echo number_format($totalFinal, 2, ',', '.'); ?></span></div>
        </div>

        <?php if ($pedidoEnviado): ?>
          <div class="checkout-box resumo-final">
            <h3>Texto do pedido para copiar</h3>
            <textarea rows="8" onclick="this.select();">
Pedido para <?php echo h($loja['nome'] ?? 'Loja'); ?>


Cliente: <?php echo h($nome); ?>

Telefone: <?php echo h($telefone); ?>


Endereço:
<?php echo h($endereco); ?>

Bairro: <?php echo h($bairro); ?> - Cidade: <?php echo h($cidade); ?>

Complemento: <?php echo h($complemento); ?>


Tipo de entrega: <?php echo h($tipoEntrega); ?>

Forma de pagamento: <?php echo h($pagamento); ?><?php if ($pagamento === 'Dinheiro' && $trocoPara !== ''): ?> (troco para <?php echo h($trocoPara); ?>)<?php endif; ?>


Itens:
<?php foreach ($_SESSION['carrinho'] as $item): ?>
- <?php echo (int)$item['qtd']; ?>x <?php echo h($item['nome']); ?> (R$ <?php echo number_format($item['preco'] * $item['qtd'], 2, ',', '.'); ?>)
<?php endforeach; ?>

Subtotal: R$ <?php echo number_format($totais['subtotal'], 2, ',', '.'); ?>

Taxa de entrega: R$ <?php echo number_format($taxaEntrega, 2, ',', '.'); ?>

Total: R$ <?php echo number_format($totalFinal, 2, ',', '.'); ?>


Observações:
<?php echo h($observacoes); ?>

            </textarea>
            <p style="font-size:11px;color:#777;margin-top:6px;">
              Copie esse texto e cole no WhatsApp até ligarmos isso direto no bot.
            </p>
          </div>
        <?php else: ?>
          <p style="font-size:12px;color:#777;margin-top:10px;">
            Preencha os dados ao lado e clique em “Gerar resumo do pedido” para ver o texto pronto para envio.
          </p>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>

</body>
</html>
