<?php
// admin/pedido_proximo_html.php
// Gera CUPOM estreito para impressão térmica (58mm) e chama window.print() automático

header('Content-Type: text/html; charset=utf-8');

$baseDir     = __DIR__ . '/..';
$pedidosArq  = $baseDir . '/data/pedidos.json';
$produtosArq = $baseDir . '/data/produtos.json';

// (opcional) token simples
$token = $_GET['token'] ?? '';
if ($token !== '' && $token !== 'TESTE123') {
    echo 'Token inválido.';
    exit;
}

// ----- Carrega dados -----
$pedidos = [];
if (file_exists($pedidosArq)) {
    $json = file_get_contents($pedidosArq);
    $pedidos = json_decode($json, true) ?: [];
}

$dadosLoja = [];
if (file_exists($produtosArq)) {
    $jsonProd  = file_get_contents($produtosArq);
    $dadosProd = json_decode($jsonProd, true) ?: [];
    $dadosLoja = $dadosProd['loja'] ?? [];
}

// ----- Escolhe pedido -----
// 1) Se vier id=... na URL, tenta esse
$pedidoId = $_GET['id'] ?? '';
$pedido   = null;

if ($pedidoId !== '') {
    foreach ($pedidos as $p) {
        if (!empty($p['id']) && $p['id'] === $pedidoId) {
            $pedido = $p;
            break;
        }
    }
} else {
    // 2) Senão, pega o primeiro com status em_impressao ou novo
    foreach ($pedidos as $p) {
        $st = $p['status'] ?? 'novo';
        if ($st === 'em_impressao' || $st === 'novo') {
            $pedido = $p;
            break;
        }
    }
}

if (!$pedido) {
    echo 'Nenhum pedido para imprimir.';
    exit;
}

// ----- Dados da loja -----
$nomeLoja   = $dadosLoja['nome']            ?? 'Minha Loja';
$end1       = $dadosLoja['endereco_linha1'] ?? '';
$end2       = $dadosLoja['endereco_linha2'] ?? '';
$cep        = $dadosLoja['cep']             ?? '';
$cnpj       = $dadosLoja['cnpj']            ?? '';
$whatsLoja  = $dadosLoja['whatsapp']        ?? '';
$logoCupom  = $dadosLoja['logo_cupom']      ?? ($dadosLoja['logo'] ?? ''); // URL completa da logo, se tiver

// ----- Dados do pedido -----
$comanda   = $pedido['id']        ?? '';
$dataHora  = $pedido['data_hora'] ?? '';
$nomeCli   = $pedido['nome']      ?? '';
$telCli    = $pedido['telefone']  ?? '';
$endereco  = $pedido['endereco']  ?? '';
$ref       = $pedido['referencia']?? '';
$pagForma  = $pedido['pag_forma'] ?? '';
$troco     = $pedido['troco']     ?? '';
$obs       = $pedido['obs']       ?? '';
$subtotal  = $pedido['subtotal']  ?? '';
$itens     = $pedido['itens']     ?? [];

$dataBr = $dataHora;
if ($dataHora) {
    $ts = strtotime($dataHora);
    if ($ts) {
        $dataBr = date('d/m/Y H:i:s', $ts);
    }
}

// total final (se tiver taxa depois você ajusta; por enquanto = subtotal)
$totalFinal = $subtotal;

// ----- Monta HTML dos itens -----
$linhasItens = '';
foreach ($itens as $it) {
    $qtd   = (int)($it['qtd']   ?? 1);
    $nome  = (string)($it['nome'] ?? '');
    $preco = (float)($it['preco'] ?? 0);

    $totalItem = $preco * $qtd;

    $precoTxt = 'R$ ' . number_format($preco, 2, ',', '.');
    $totalTxt = 'R$ ' . number_format($totalItem, 2, ',', '.');

    $linhasItens .= '
        <tr>
          <td style="padding:1px 2px; font-size:10px;">' . $qtd . 'x</td>
          <td style="padding:1px 2px; font-size:10px;">' . htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') . '</td>
          <td style="padding:1px 2px; font-size:10px; text-align:right;">' . $precoTxt . '</td>
          <td style="padding:1px 2px; font-size:10px; text-align:right;">' . $totalTxt . '</td>
        </tr>';
}

if ($linhasItens === '') {
    $linhasItens = '
        <tr>
          <td colspan="4" style="font-size:10px; padding:2px;">(sem itens)</td>
        </tr>';
}

// troco
$trocoTxt = '';
if (strtolower($pagForma) === 'dinheiro' && $troco !== '') {
    $trocoTxt = ' – Troco para R$ ' . htmlspecialchars($troco, ENT_QUOTES, 'UTF-8');
}

// observação
$obsHtml = '';
if ($obs !== '') {
    $obsHtml = '
        <tr>
          <td colspan="4" style="padding-top:3px; font-size:10px;">
            <strong>Observações:</strong> ' . nl2br(htmlspecialchars($obs, ENT_QUOTES, 'UTF-8')) . '
          </td>
        </tr>';
}

// WhatsApp
$whatsHtml = '';
if ($whatsLoja !== '') {
    $whatsHtml = '
      <div style="text-align:center; font-size:9px; margin-top:4px;">
        WhatsApp da loja: ' . htmlspecialchars($whatsLoja, ENT_QUOTES, 'UTF-8') . '
      </div>';
}

// logo
$logoHtml = '';
if ($logoCupom !== '') {
    $logoHtml = '
      <div style="text-align:center; margin-bottom:3px;">
        <img src="' . htmlspecialchars($logoCupom, ENT_QUOTES, 'UTF-8') . '" alt="Logo"
             style="max-width:70px; height:auto;">
      </div>';
}

?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Cupom do pedido</title>
<style>
  * {
    box-sizing: border-box;
  }
  body {
    font-family: Arial, Helvetica, sans-serif;
    font-size: 11px;
    margin: 0;
    padding: 0;
    background: #ffffff;
  }
  .cupom {
    width: 210px; /* ~58mm */
    margin: 0 auto;
    padding: 4px 6px;
  }
  hr {
    border: none;
    border-top: 1px dashed #000;
    margin: 3px 0;
  }
  .titulo-loja {
    text-align: center;
    font-size: 11px;
    font-weight: bold;
  }
  table {
    width: 100%;
    border-collapse: collapse;
  }
  th {
    text-align: left;
    font-size: 10px;
    border-bottom: 1px solid #000;
    padding: 1px 2px;
  }
  .rodape {
    text-align: center;
    font-size: 9px;
    margin-top: 4px;
  }

  @media print {
    @page {
      margin: 0;
      size: 58mm auto;
    }
    body {
      margin: 0;
      padding: 0;
    }
    .cupom {
      width: 58mm;
      padding: 2mm;
    }
  }
</style>
<script>
  window.onload = function () {
    window.print();
    // se quiser fechar a aba automaticamente depois de imprimir:
    // setTimeout(function(){ window.close(); }, 500);
  };
</script>
</head>
<body>
  <div class="cupom">
    <?php echo $logoHtml; ?>

    <div class="titulo-loja"><?php echo htmlspecialchars($nomeLoja, ENT_QUOTES, 'UTF-8'); ?></div>
    <div style="text-align:center; font-size:9px; margin-bottom:2px;">
      <?php if ($end1) echo htmlspecialchars($end1, ENT_QUOTES, 'UTF-8') . '<br>'; ?>
      <?php if ($end2) echo htmlspecialchars($end2, ENT_QUOTES, 'UTF-8') . '<br>'; ?>
      <?php if ($cep)  echo 'CEP: ' . htmlspecialchars($cep, ENT_QUOTES, 'UTF-8') . '<br>'; ?>
      <?php if ($cnpj) echo 'CNPJ: ' . htmlspecialchars($cnpj, ENT_QUOTES, 'UTF-8'); ?>
    </div>

    <hr>

    <div style="font-size:10px;">
      <strong>Comanda:</strong> <?php echo htmlspecialchars($comanda, ENT_QUOTES, 'UTF-8'); ?><br>
      <strong>Data/Hora:</strong> <?php echo htmlspecialchars($dataBr, ENT_QUOTES, 'UTF-8'); ?><br>
      <strong>Cliente:</strong> <?php echo htmlspecialchars($nomeCli, ENT_QUOTES, 'UTF-8'); ?><br>
      <strong>Telefone:</strong> <?php echo htmlspecialchars($telCli, ENT_QUOTES, 'UTF-8'); ?><br>
      <strong>Endereço:</strong> <?php echo htmlspecialchars($endereco . ' ' . $ref, ENT_QUOTES, 'UTF-8'); ?><br>
      <strong>Pagamento:</strong> <?php echo htmlspecialchars($pagForma, ENT_QUOTES, 'UTF-8') . $trocoTxt; ?>
    </div>

    <hr>

    <div style="font-size:10px; margin-bottom:2px;"><strong>Itens</strong></div>
    <table>
      <thead>
        <tr>
          <th style="width:26px;">Qtd</th>
          <th>Produto</th>
          <th style="width:50px; text-align:right;">Preço</th>
          <th style="width:55px; text-align:right;">Total</th>
        </tr>
      </thead>
      <tbody>
        <?php echo $linhasItens; ?>
        <?php echo $obsHtml; ?>
      </tbody>
    </table>

    <hr>

    <div style="font-size:10px; text-align:right;">
      Subtotal: R$ <?php echo htmlspecialchars($subtotal, ENT_QUOTES, 'UTF-8'); ?><br>
      <strong>Total: R$ <?php echo htmlspecialchars($totalFinal, ENT_QUOTES, 'UTF-8'); ?></strong>
    </div>

    <?php echo $whatsHtml; ?>

    <div class="rodape">
      <strong>Obrigado pela preferência.</strong>
    </div>
  </div>
</body>
</html>
