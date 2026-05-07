<?php
session_start();
require_once __DIR__ . '/../includes/app.php';

if (empty($_SESSION['logado_cardapio'])) {
    header('Location: login.php');
    exit;
}

// dados da loja
$dados = cardapio_carregar_produtos();
$loja  = $dados['loja'] ?? [];

// pedidos
$pedidos = cardapio_carregar_pedidos();

// garante status
foreach ($pedidos as &$p) {
    if (empty($p['status'])) {
        $p['status'] = 'novo';
    }
}
unset($p);


function dinheiro_para_float($valor) {
    if ($valor === null || $valor === '') return 0.0;

    $valor = trim((string)$valor);

    if (stripos($valor, 'Aguardando') !== false || stripos($valor, 'consultar') !== false) {
        return 0.0;
    }

    $valor = str_replace(['R$', ' '], '', $valor);
    $valor = str_replace('.', '', $valor);
    $valor = str_replace(',', '.', $valor);

    return (float)$valor;
}

function formatar_dinheiro($valor) {
    return number_format((float)$valor, 2, ',', '.');
}

function logo_relativo($logo) {
    $logo = trim((string)$logo);

    if ($logo === '') return '';

    if (str_starts_with($logo, '../')) {
        return $logo;
    }

    if (str_starts_with($logo, 'uploads/')) {
        return '../' . $logo;
    }

    return '../uploads/' . $logo;
}

// tratar ações
$acao = $_GET['acao'] ?? '';

if ($acao && !empty($_GET['id'])) {
    $id    = $_GET['id'];
    $mudou = false;

    foreach ($pedidos as $k => &$p) {
        if (($p['id'] ?? '') === $id) {
            if ($acao === 'status') {
                $novoStatus = $_GET['status'] ?? '';
                $permitidos = ['novo', 'preparo', 'finalizado'];

                if (in_array($novoStatus, $permitidos, true)) {
                    $p['status'] = $novoStatus;
                    $mudou = true;
                }
            } elseif ($acao === 'excluir') {
                unset($pedidos[$k]);
                $mudou = true;
            }

            break;
        }
    }
    unset($p);

    if ($mudou) {
        $pedidos = array_values($pedidos);

        cardapio_salvar_pedidos($pedidos);

        $extra = (!empty($_GET['todos']) && $_GET['todos'] === '1') ? '?todos=1' : '';
        header('Location: pedidos.php' . $extra);
        exit;
    }
}

// ordenar pedidos do mais novo para o mais antigo
usort($pedidos, function($a, $b) {
    $da = $a['data_hora'] ?? '';
    $db = $b['data_hora'] ?? '';
    return strcmp($db, $da);
});

// filtro hoje / todos
$mostrarTodos = (isset($_GET['todos']) && $_GET['todos'] === '1');

$pedidosParaListar = $pedidos;

if (!$mostrarTodos) {
    $hoje = date('Y-m-d');

    $pedidosParaListar = array_filter($pedidos, function($p) use ($hoje) {
        $dh = $p['data_hora'] ?? '';
        $ts = strtotime($dh);

        if (!$ts) return false;

        return date('Y-m-d', $ts) === $hoje;
    });
}

// detalhe do pedido
$pedidoDetalhe = null;
$pedidoId = $_GET['id'] ?? '';

if ($pedidoId !== '') {
    $mudouStatus = false;

    foreach ($pedidos as $k => &$p) {
        if (!empty($p['id']) && $p['id'] === $pedidoId) {
            if (($p['status'] ?? 'novo') === 'novo') {
                $p['status'] = 'preparo';
                $mudouStatus = true;
            }

            $pedidoDetalhe = $p;
            break;
        }
    }
    unset($p);

    if ($mudouStatus) {
        cardapio_salvar_pedidos($pedidos);
    }
}

// logo para o cupom
$logoPath = '';
$cupomLogoRel = '../uploads/logo_cupom.png';
$cupomLogoAbs = __DIR__ . '/../uploads/logo_cupom.png';

if (file_exists($cupomLogoAbs)) {
    $logoPath = $cupomLogoRel;
} elseif (!empty($loja['logo'])) {
    $logoPath = logo_relativo($loja['logo']);
}

// dados da loja
$nomeLoja  = $loja['nome']        ?? '';
$whatsLoja = $loja['whatsapp']    ?? '';
$endRua    = $loja['endereco']    ?? '';
$endCompl  = $loja['complemento'] ?? '';
$endBairro = $loja['bairro']      ?? '';
$endCidade = $loja['cidade']      ?? '';
$endUf     = $loja['uf']          ?? '';
$endCep    = $loja['cep']         ?? '';
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <title>Pedidos – Painel</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">

  <link rel="stylesheet" href="../assets/style.css?v=17">

  <style>
    .pedido-impressao {
      max-width: 360px;
      margin: 8px auto;
      background: #fff;
      border-radius: 10px;
      padding: 10px 12px;
      box-shadow: 0 2px 6px rgba(0,0,0,.08);
      font-size: 13px;
      line-height: 1.3;
      color: #000;
    }

    .pedido-impressao h2 {
      font-size: 15px;
      margin: 0 0 6px;
      text-align: center;
    }

    .pedido-impressao .linha {
      margin-bottom: 3px;
    }

    .pedido-impressao table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 6px;
      font-size: 12px;
    }

    .pedido-impressao table th,
    .pedido-impressao table td {
      padding: 2px 0;
      text-align: left;
      vertical-align: top;
    }

    .pedido-impressao table th {
      border-bottom: 1px solid #ddd;
      font-weight: 600;
    }

    .pedido-logo {
      text-align:center;
      margin-bottom:3px;
    }

    .pedido-logo img {
      max-width: 120px;
      max-height: 60px;
    }

    .pedido-nome-loja {
      font-size: 13px;
      font-weight: 600;
      text-align: center;
      margin-bottom: 2px;
    }

    .pedido-endereco-loja {
      font-size: 11px;
      text-align: center;
      margin-bottom: 3px;
      line-height: 1.25;
    }

    .pedido-footer {
      text-align:center;
      font-size:10px;
      margin-top:3px;
      line-height:1.3;
    }

    .pedido-total-final {
      font-size: 15px;
      font-weight: bold;
      text-align: center;
      border: 2px solid #000;
      padding: 6px;
      margin-top: 6px;
    }

    .pedidos-list-box {
      max-width: 760px;
      margin: 0 auto;
    }

    .pedidos-list-box h2 {
      display:flex;
      align-items:center;
      justify-content:space-between;
      font-size:16px;
    }

    .pedidos-list-box h2 span {
      font-size:11px;
      color:#777;
    }

    .pedidos-list-box table {
      width: 100%;
    }

    .pedidos-list-box .table-itens th,
    .pedidos-list-box .table-itens td {
      font-size: 13px;
    }

    .pedido-status {
      font-size: 11px;
      margin-bottom: 2px;
    }

    .badge-status {
      display: inline-flex;
      align-items:center;
      gap:4px;
      padding: 2px 8px;
      border-radius: 999px;
      font-size: 11px;
      line-height: 1.2;
      background: #eee;
      color: #555;
      font-weight:500;
    }

    .badge-status-novo {
      background: #fff4e5;
      color: #a66300;
    }

    .badge-status-preparo {
      background: #e5f0ff;
      color: #004b9a;
    }

    .badge-status-finalizado {
      background: #e5ffe9;
      color: #0b6b2e;
    }

    .badge-dot-novo {
      width:7px;
      height:7px;
      border-radius:50%;
      background:#ff3b30;
    }

    .status-links {
      margin-top: 2px;
      font-size: 11px;
      color: #777;
    }

    .status-links a {
      color: #777;
      text-decoration: none;
    }

    .status-links a:hover {
      text-decoration: underline;
    }

    .data-separador td {
      background: #fafafa;
      font-weight: 600;
      font-size: 12px;
      padding-top: 6px;
      padding-bottom: 4px;
    }

    .pedido-row-novo td {
      background: #fffaf1;
    }

    .pedidos-list-box .acoes .btn {
      padding:4px 10px;
      font-size:11px;
    }

    @media print {
      @page {
        size: 80mm auto;
        margin: 4mm 3mm;
      }

      html, body, .admin-wrapper {
        margin:0;
        padding:0;
      }

      body {
        background: #fff !important;
      }

      .admin-header,
      .no-print {
        display: none !important;
      }

      .pedido-impressao {
        max-width: 100%;
        width: 100%;
        margin: 0 auto;
        padding: 0;
        border-radius: 0;
        box-shadow: none;
        font-size: 11px;
        line-height: 1.25;
        box-sizing: border-box;
      }

      .pedido-impressao-inner {
        padding: 2mm 2mm;
      }

      .pedido-impressao h2 {
        font-size: 13px;
        margin-bottom: 3px;
        text-align: center;
      }

      .pedido-impressao hr {
        border: 0;
        border-top: 1px dashed #000;
        margin: 3px 0;
      }

      .pedido-impressao table {
        font-size: 11px;
        margin-top: 2px;
      }

      .pedido-impressao table th,
      .pedido-impressao table td {
        padding: 1px 0;
      }

      footer, header {
        display: none !important;
      }
    }
  </style>
</head>
<body>

<div class="admin-wrapper">

  <div class="admin-header no-print">
    <h1>Pedidos – Painel</h1>
    <a href="index.php" class="btn btn-outline">← Voltar ao painel</a>
  </div>

  <?php if ($pedidoDetalhe): ?>

    <?php
      $rawDataHora = $pedidoDetalhe['data_hora'] ?? '';
      $cliente     = $pedidoDetalhe['nome'] ?? '';
      $tel         = $pedidoDetalhe['telefone'] ?? '';

      $tipoPedido = $pedidoDetalhe['tipo_pedido'] ?? 'Entrega';

      $bairroPedido = $pedidoDetalhe['bairro'] ?? '';
      $endBase      = $pedidoDetalhe['endereco'] ?? '';
      $referencia   = $pedidoDetalhe['referencia'] ?? '';

      $pag   = $pedidoDetalhe['pag_forma'] ?? '';
      $troco = $pedidoDetalhe['troco'] ?? '';
      $obs   = trim($pedidoDetalhe['obs'] ?? '');

      $subtotalTexto = $pedidoDetalhe['subtotal'] ?? '0,00';
      $subtotalItens = dinheiro_para_float($subtotalTexto);

      $taxaEntregaTexto = $pedidoDetalhe['taxa_entrega'] ?? '0,00';
      $totalFinalSalvo  = $pedidoDetalhe['total_final'] ?? '';
      $entregaConsultar = (string)($pedidoDetalhe['entrega_consultar'] ?? '0');

      $ajusteTipo  = $pedidoDetalhe['ajuste_tipo'] ?? '';
      $ajusteValor = (float)($pedidoDetalhe['ajuste_valor'] ?? 0);
      $ajusteObs   = trim($pedidoDetalhe['ajuste_obs'] ?? '');

      $totalBase = 0.0;
      $totalFinalEhTexto = false;

      if ($entregaConsultar === '1' || stripos($totalFinalSalvo, 'Aguardando') !== false) {
          $totalFinalEhTexto = true;
      } elseif ($totalFinalSalvo !== '') {
          $totalBase = dinheiro_para_float($totalFinalSalvo);
      } else {
          $taxaNumero = dinheiro_para_float($taxaEntregaTexto);
          $totalBase = $subtotalItens + $taxaNumero;
      }

      $totalFinal = $totalBase;

      if (!$totalFinalEhTexto) {
          if ($ajusteTipo === 'acrescimo') {
              $totalFinal += $ajusteValor;
          } elseif ($ajusteTipo === 'desconto') {
              $totalFinal -= $ajusteValor;
          }

          if ($totalFinal < 0) {
              $totalFinal = 0;
          }
      }

      $dataHoraFormatada = $rawDataHora;
      $comanda = '';

      if ($rawDataHora !== '') {
          $ts = strtotime($rawDataHora);

          if ($ts) {
              $dataHoraFormatada = date('d/m/Y H:i:s', $ts);
              $comanda = date('d-m-H-i-s-Y', $ts);
          }
      }

      if ($comanda === '' && !empty($pedidoDetalhe['id'])) {
          $comanda = $pedidoDetalhe['id'];
      }
    ?>

    <div class="pedido-impressao">
      <div class="pedido-impressao-inner">

        <?php if ($logoPath): ?>
          <div class="pedido-logo">
            <img src="<?php echo h($logoPath); ?>" alt="Logo da loja">
          </div>
        <?php endif; ?>

        <?php if ($nomeLoja): ?>
          <div class="pedido-nome-loja"><?php echo h($nomeLoja); ?></div>
        <?php endif; ?>

        <?php if ($endRua || $endBairro || $endCidade): ?>
          <div class="pedido-endereco-loja">
            <?php if ($endRua): ?>
              <?php echo h($endRua); ?><?php echo $endCompl ? ' - ' . h($endCompl) : ''; ?><br>
            <?php endif; ?>

            <?php if ($endBairro || $endCidade): ?>
              <?php echo h(trim($endBairro . ' - ' . $endCidade . ' ' . $endUf)); ?><br>
            <?php endif; ?>

            <?php if ($endCep): ?>
              CEP: <?php echo h($endCep); ?>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <?php if ($comanda): ?>
          <div class="linha"><strong>Comanda:</strong> <?php echo h($comanda); ?></div>
        <?php endif; ?>

        <div class="linha"><strong>Data/Hora:</strong> <?php echo h($dataHoraFormatada); ?></div>

        <div class="linha"><strong>Tipo:</strong> <?php echo h($tipoPedido); ?></div>

        <?php if ($cliente): ?>
          <div class="linha"><strong>Cliente:</strong> <?php echo h($cliente); ?></div>
        <?php endif; ?>

        <?php if ($tel): ?>
          <div class="linha"><strong>Telefone:</strong> <?php echo h($tel); ?></div>
        <?php endif; ?>

        <?php if ($tipoPedido === 'Entrega'): ?>
          <?php if ($bairroPedido): ?>
            <div class="linha"><strong>Bairro:</strong> <?php echo h($bairroPedido); ?></div>
          <?php endif; ?>

          <?php if ($endBase): ?>
            <div class="linha"><strong>Endereço entrega:</strong> <?php echo h($endBase); ?></div>
          <?php endif; ?>

          <?php if ($referencia): ?>
            <div class="linha"><strong>Referência:</strong> <?php echo h($referencia); ?></div>
          <?php endif; ?>
        <?php else: ?>
          <div class="linha"><strong>Retirada:</strong> Cliente vai retirar no local.</div>
        <?php endif; ?>

        <?php if ($pag): ?>
          <div class="linha"><strong>Pagamento:</strong> <?php echo h($pag); ?></div>
        <?php endif; ?>

        <?php if ($pag && stripos($pag, 'dinheiro') !== false && $troco !== ''): ?>
          <div class="linha">
            <strong>Troco para:</strong>
            R$ <?php echo formatar_dinheiro(dinheiro_para_float($troco)); ?>
          </div>
        <?php endif; ?>

        <hr>

        <strong>Itens</strong>

        <table>
          <thead>
            <tr>
              <th style="width:28px;">Qtd</th>
              <th>Produto</th>
              <th style="width:48px;">Preço</th>
              <th style="width:52px;">Total</th>
            </tr>
          </thead>

          <tbody>
          <?php foreach (($pedidoDetalhe['itens'] ?? []) as $item): ?>
            <?php
              $qtd   = (int)($item['qtd'] ?? 0);
              $nomeP = $item['nome'] ?? '';
              $preco = (float)($item['preco'] ?? 0);
              $tot   = $preco * $qtd;
            ?>

            <tr>
              <td><?php echo $qtd . 'x'; ?></td>
              <td><?php echo h($nomeP); ?></td>
              <td>R$ <?php echo formatar_dinheiro($preco); ?></td>
              <td>R$ <?php echo formatar_dinheiro($tot); ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>

        <hr>

        <div class="linha">
          <strong>Subtotal dos itens:</strong>
          R$ <?php echo h($subtotalTexto); ?>
        </div>

        <div class="linha">
          <strong>Taxa de entrega:</strong>
          <?php if ($tipoPedido === 'Retirada no local'): ?>
            R$ 0,00
          <?php elseif ($entregaConsultar === '1'): ?>
            A consultar
          <?php else: ?>
            R$ <?php echo h($taxaEntregaTexto); ?>
          <?php endif; ?>
        </div>

        <?php if ($ajusteTipo && $ajusteValor > 0): ?>
          <div class="linha">
            <strong><?php echo $ajusteTipo === 'acrescimo' ? 'Acréscimo:' : 'Desconto:'; ?></strong>
            R$ <?php echo formatar_dinheiro($ajusteValor); ?>
          </div>

          <?php if ($ajusteObs !== ''): ?>
            <div class="linha">
              <strong>Obs. ajuste:</strong> <?php echo nl2br(h($ajusteObs)); ?>
            </div>
          <?php endif; ?>
        <?php endif; ?>

        <div class="pedido-total-final">
          TOTAL FINAL<br>
          <?php if ($totalFinalEhTexto): ?>
            A confirmar
          <?php else: ?>
            R$ <?php echo formatar_dinheiro($totalFinal); ?>
          <?php endif; ?>
        </div>

        <?php if ($obs !== ''): ?>
          <hr>
          <div class="linha">
            <strong>Observações do cliente:</strong><br>
            <?php echo nl2br(h($obs)); ?>
          </div>
        <?php endif; ?>

        <hr>

        <div class="pedido-footer">
          <?php if ($whatsLoja): ?>
            <strong>WhatsApp da loja:</strong> <?php echo h($whatsLoja); ?><br>
          <?php endif; ?>

          <strong>Obrigado pela preferência.</strong>
        </div>

      </div>
    </div>

    <div class="no-print" style="text-align:center;margin-top:12px;">
      <button onclick="window.print();" class="btn btn-red">Imprimir</button>

      <?php $extra = $mostrarTodos ? '&todos=1' : ''; ?>

      <a href="pedido_ajuste.php?id=<?php echo h($pedidoId); ?><?php echo $extra; ?>"
         class="btn btn-outline">Ajustar valor</a>

      <a href="pedidos.php<?php echo $mostrarTodos ? '?todos=1' : ''; ?>"
         class="btn btn-outline">Ver lista de pedidos</a>
    </div>

  <?php else: ?>

    <div class="admin-box pedidos-list-box">
      <h2>
        Pedidos recebidos
        <span>
          <?php echo $mostrarTodos ? 'Exibindo todos os pedidos' : 'Exibindo apenas os pedidos de hoje'; ?>
        </span>
      </h2>

      <div class="no-print" style="margin-bottom:8px;font-size:12px;">
        Filtro:

        <?php if ($mostrarTodos): ?>
          <a class="btn btn-outline" href="pedidos.php">Mostrar apenas hoje</a>
        <?php else: ?>
          <a class="btn btn-outline" href="pedidos.php?todos=1">Mostrar todos</a>
        <?php endif; ?>
      </div>

      <?php if (empty($pedidosParaListar)): ?>

        <p style="font-size:13px;color:#666;">
          <?php echo $mostrarTodos ? 'Nenhum pedido registrado ainda.' : 'Nenhum pedido registrado hoje.'; ?>
        </p>

      <?php else: ?>

        <table class="table-itens">
          <thead>
            <tr>
              <th>Data/Hora</th>
              <th>Cliente</th>
              <th>Total</th>
              <th>Status</th>
              <th class="acoes">Ações</th>
            </tr>
          </thead>

          <tbody>
          <?php
            $diaAtual = '';

            foreach ($pedidosParaListar as $p):
              $pid    = $p['id'] ?? '';
              $rawDh  = $p['data_hora'] ?? '';
              $cli    = $p['nome'] ?? '';
              $status = $p['status'] ?? 'novo';

              $subtotalItens = dinheiro_para_float($p['subtotal'] ?? '0,00');
              $taxaEntrega   = dinheiro_para_float($p['taxa_entrega'] ?? '0,00');
              $totalSalvo    = $p['total_final'] ?? '';
              $consultar     = (string)($p['entrega_consultar'] ?? '0');

              $ajusteTipo  = $p['ajuste_tipo'] ?? '';
              $ajusteValor = (float)($p['ajuste_valor'] ?? 0);

              $totalFinalListaTexto = '';

              if ($consultar === '1' || stripos((string)$totalSalvo, 'Aguardando') !== false) {
                  $totalFinalListaTexto = 'A confirmar';
                  $totalFinal = 0;
              } elseif ($totalSalvo !== '') {
                  $totalFinal = dinheiro_para_float($totalSalvo);
              } else {
                  $totalFinal = $subtotalItens + $taxaEntrega;
              }

              if ($totalFinalListaTexto === '') {
                  if ($ajusteTipo === 'acrescimo') {
                      $totalFinal += $ajusteValor;
                  } elseif ($ajusteTipo === 'desconto') {
                      $totalFinal -= $ajusteValor;
                  }

                  if ($totalFinal < 0) {
                      $totalFinal = 0;
                  }
              }

              $dh = $rawDh;
              $diaLinha = '';

              if ($rawDh !== '') {
                  $ts = strtotime($rawDh);

                  if ($ts) {
                      $dh = date('d/m/Y H:i:s', $ts);
                      $diaLinha = date('d/m/Y', $ts);
                  }
              }

              if ($diaLinha && $diaLinha !== $diaAtual) {
                  $diaAtual = $diaLinha;
                  echo '<tr class="data-separador"><td colspan="5">' . h($diaAtual) . '</td></tr>';
              }

              $statusLabel = 'Novo';
              $badgeClass = 'badge-status badge-status-novo';

              if ($status === 'preparo') {
                  $statusLabel = 'Em preparo';
                  $badgeClass = 'badge-status badge-status-preparo';
              } elseif ($status === 'finalizado') {
                  $statusLabel = 'Finalizado';
                  $badgeClass = 'badge-status badge-status-finalizado';
              }

              $rowClass = ($status === 'novo') ? 'pedido-row-novo' : '';
              $extraUrl = $mostrarTodos ? '&todos=1' : '';
          ?>

            <tr class="<?php echo $rowClass; ?>">
              <td><?php echo h($dh); ?></td>

              <td><?php echo h($cli); ?></td>

              <td>
                <?php if ($totalFinalListaTexto !== ''): ?>
                  <?php echo h($totalFinalListaTexto); ?>
                <?php else: ?>
                  R$ <?php echo formatar_dinheiro($totalFinal); ?>
                <?php endif; ?>
              </td>

              <td>
                <div class="pedido-status">
                  <span class="<?php echo $badgeClass; ?>">
                    <?php if ($status === 'novo'): ?>
                      <span class="badge-dot-novo"></span>
                    <?php endif; ?>

                    <?php echo h($statusLabel); ?>
                  </span>
                </div>

                <div class="status-links">
                  <a href="pedidos.php?acao=status&id=<?php echo h($pid); ?>&status=novo<?php echo $extraUrl; ?>">Novo</a> ·
                  <a href="pedidos.php?acao=status&id=<?php echo h($pid); ?>&status=preparo<?php echo $extraUrl; ?>">Preparo</a> ·
                  <a href="pedidos.php?acao=status&id=<?php echo h($pid); ?>&status=finalizado<?php echo $extraUrl; ?>">Finalizado</a>
                </div>
              </td>

              <td class="acoes">
                <?php if ($pid): ?>
                  <a class="btn btn-outline"
                     href="pedidos.php?id=<?php echo h($pid); ?><?php echo $extraUrl; ?>">
                    Ver / Imprimir
                  </a>

                  <a class="btn btn-outline"
                     href="pedido_ajuste.php?id=<?php echo h($pid); ?><?php echo $extraUrl; ?>">
                    Ajustar valor
                  </a>

                  <a class="btn btn-outline"
                     href="pedidos.php?acao=excluir&id=<?php echo h($pid); ?><?php echo $extraUrl; ?>"
                     onclick="return confirm('Deseja realmente excluir este pedido?');">
                    Excluir
                  </a>
                <?php endif; ?>
              </td>
            </tr>

          <?php endforeach; ?>
          </tbody>
        </table>

      <?php endif; ?>
    </div>

  <?php endif; ?>

</div>

</body>
</html>