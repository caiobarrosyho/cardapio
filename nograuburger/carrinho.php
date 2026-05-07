<?php
session_start();
require __DIR__ . '/funcoes_carrinho.php';
require_once __DIR__ . '/includes/app.php';

$dados      = cardapio_carregar_produtos();
$loja       = $dados['loja'] ?? [];
$categorias = $dados['categorias'] ?? [];


/* =========================================================
   Ações da sacola
   Mantidas como estavam:
   - add
   - atualizar
   - limpar
   - remover
========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'add') {
        $id    = $_POST['id']    ?? '';
        $nome  = $_POST['nome']  ?? '';
        $preco = (float)($_POST['preco'] ?? 0);
        $qtd   = isset($_POST['qtd']) ? (int)$_POST['qtd'] : 1;
        carrinho_adicionar($id, $nome, $preco, $qtd);

    } elseif ($acao === 'atualizar') {
        $id  = $_POST['id']  ?? '';
        $qtd = isset($_POST['qtd']) ? (int)$_POST['qtd'] : 1;
        if ($id !== '') {
            carrinho_atualizar_qtd($id, $qtd);
        }

    } elseif ($acao === 'limpar') {
        carrinho_limpar();

    } elseif ($acao === 'remover') {
        $idRem = $_POST['id'] ?? '';
        if ($idRem !== '' && isset($_SESSION['carrinho'][$idRem])) {
            unset($_SESSION['carrinho'][$idRem]);
        }
    }

    header('Location: carrinho.php');
    exit;
}

$totais = carrinho_totais();
$taxasEntrega = $loja['taxas_entrega'] ?? [];

/* =========================================================
   WhatsApp da loja
========================================================= */
$whatsLoja    = $loja['whatsapp'] ?? '';
$whatsDigitos = preg_replace('/\D+/', '', $whatsLoja);
$whatsBase    = $whatsDigitos ? 'https://wa.me/55' . $whatsDigitos : '';

/* =========================================================
   Itens do carrinho para:
   - WhatsApp
   - exibir foto e detalhe na tabela
========================================================= */
$itensJs      = [];
$itensSacola  = [];

if (!empty($_SESSION['carrinho'])) {
    foreach ($_SESSION['carrinho'] as $id => $item) {
        $prodCompleto = localizar_produto_carrinho(
            $dados,
            (string)$id,
            (string)($item['nome'] ?? ''),
            (float)($item['preco'] ?? 0)
        );

        $foto = '';
        $detalhe = '';

        if ($prodCompleto) {
            $foto    = $prodCompleto['foto'] ?? $prodCompleto['imagem'] ?? '';
            $detalhe = $prodCompleto['detalhe'] ?? '';
        }

        $itensJs[] = [
            'nome'  => $item['nome'],
            'qtd'   => (int)$item['qtd'],
            'preco' => (float)$item['preco'],
        ];

        $itensSacola[] = [
            'id'       => $id,
            'nome'     => $item['nome'],
            'qtd'      => (int)$item['qtd'],
            'preco'    => (float)$item['preco'],
            'foto'     => $foto,
            'detalhe'  => $detalhe,
            'subtotal' => (float)$item['preco'] * (int)$item['qtd'],
        ];
    }
}

/* =========================================================
   Produtos sugeridos (upsell)
   Mantido como já estava
========================================================= */
$upsellProdutos = [];
foreach (['upsell1_ref', 'upsell2_ref', 'upsell3_ref', 'upsell4_ref'] as $chave) {
    $ref = $loja[$chave] ?? '';
    if (!preg_match('/^(\d+)\|(\d+)$/', $ref, $m)) {
        continue;
    }

    $cIdx = (int)$m[1];
    $pIdx = (int)$m[2];

    $prod = buscar_produto($dados, $cIdx, $pIdx);
    if (!$prod) continue;

    $idCarrinho = $cIdx . '-' . $pIdx;

    $upsellProdutos[] = [
        'cat'       => $cIdx,
        'prod'      => $pIdx,
        'dados'     => $prod,
        'id'        => $idCarrinho,
        'na_sacola' => !empty($_SESSION['carrinho'][$idCarrinho]),
    ];
}
$upsellProdutos = array_slice($upsellProdutos, 0, 4);
?>
<!doctype html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<title>Sacola – <?php echo h($loja['nome'] ?? 'Loja'); ?></title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="icon" type="image/png" sizes="32x32" href="assets/img/favicon-32.png">
<link rel="icon" type="image/png" sizes="16x16" href="assets/img/favicon-16.png">
<link rel="shortcut icon" href="assets/img/favicon.ico">
<link rel="apple-touch-icon" href="assets/img/apple-touch-icon.png">
<link rel="stylesheet" href="assets/style.css?v=18">
</head>
<body>

<?php include __DIR__ . '/header_topo.php'; ?>

<div class="carrinho-wrapper">
  <div class="carrinho-box">

    <div class="carrinho-header-linha">
      <h1>Sacola</h1>
      <a href="index.php" class="link-voltar-cardapio">← Voltar ao cardápio</a>
    </div>

    <?php if ($totais['itens'] === 0): ?>

      <p class="carrinho-vazio">Sua sacola está vazia.</p>

    <?php else: ?>

      <?php if (!empty($upsellProdutos)): ?>
        <section class="upsell-box">
          <h2 class="upsell-titulo">Complete seu pedido</h2>
          <p class="upsell-subtitulo">
            Que tal aproveitar e adicionar mais alguma coisa?
          </p>

          <div class="upsell-grid">
            <?php foreach ($upsellProdutos as $u): ?>
              <?php
                $p      = $u['dados'];
                $id     = $u['id'];
                $preco  = (float)$p['preco'];
                $foto   = $p['foto'] ?? $p['imagem'] ?? '';
                $jaNa   = $u['na_sacola'];
              ?>
              <div class="upsell-item">
                <?php if ($foto): ?>
                  <div class="upsell-img-wrap">
                    <img src="<?php echo h($foto); ?>" alt="<?php echo h($p['nome']); ?>">
                  </div>
                <?php endif; ?>

                <div class="upsell-info">
                  <div class="upsell-nome"><?php echo h($p['nome']); ?></div>

                  <?php if (!empty($p['detalhe'])): ?>
                    <div class="upsell-detalhe"><?php echo h($p['detalhe']); ?></div>
                  <?php endif; ?>

                  <div class="upsell-preco">
                    R$ <?php echo number_format($preco, 2, ',', '.'); ?>
                  </div>

                  <?php if ($jaNa): ?>
                    <div class="upsell-ja">Já está na sacola</div>
                  <?php else: ?>
                    <form method="post" action="carrinho.php">
                      <input type="hidden" name="acao" value="add">
                      <input type="hidden" name="id" value="<?php echo h($id); ?>">
                      <input type="hidden" name="nome" value="<?php echo h($p['nome']); ?>">
                      <input type="hidden" name="preco" value="<?php echo number_format($preco, 2, '.', ''); ?>">
                      <input type="hidden" name="qtd" value="1">
                      <button type="submit" class="btn btn-red btn-upsell">Adicionar</button>
                    </form>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </section>
      <?php endif; ?>

      <div class="carrinho-step">
        <div class="carrinho-step-titulo">1. Confira seus itens</div>

        <table class="carrinho-tabela">
          <thead>
            <tr>
              <th>Item</th>
              <th>Qtd</th>
              <th>Preço</th>
              <th>Total</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($itensSacola as $item): ?>
            <tr>
              <td>
                <!--
                  Bloco novo:
                  mini foto + nome + detalhe
                  Mantém a tabela original, só melhora a leitura.
                -->
                <div class="carrinho-item-box">
                  <div class="carrinho-item-foto">
                    <?php if (!empty($item['foto'])): ?>
                      <img src="<?php echo h($item['foto']); ?>" alt="<?php echo h($item['nome']); ?>">
                    <?php else: ?>
                      <div class="carrinho-item-sem-foto">Sem foto</div>
                    <?php endif; ?>
                  </div>

                  <div class="carrinho-item-info">
                    <div class="carrinho-item-nome"><?php echo h($item['nome']); ?></div>
                    <?php if (!empty($item['detalhe'])): ?>
                      <div class="carrinho-item-detalhe"><?php echo h($item['detalhe']); ?></div>
                    <?php endif; ?>
                  </div>
                </div>
              </td>

              <td>
                <form method="post" action="carrinho.php" class="form-qtd-inline">
                  <input type="hidden" name="acao" value="atualizar">
                  <input type="hidden" name="id" value="<?php echo h($item['id']); ?>">
                  <input type="number"
                         name="qtd"
                         value="<?php echo (int)$item['qtd']; ?>"
                         min="1"
                         onchange="this.form.submit();">
                </form>
              </td>

              <td>R$ <?php echo number_format($item['preco'], 2, ',', '.'); ?></td>
              <td>R$ <?php echo number_format($item['subtotal'], 2, ',', '.'); ?></td>
              <td>
                <form method="post" action="carrinho.php" style="display:inline;">
                  <input type="hidden" name="acao" value="remover">
                  <input type="hidden" name="id" value="<?php echo h($item['id']); ?>">
                  <button type="submit"
                          class="btn btn-outline btn-remover-item"
                          onclick="return confirmarRemocao('<?php echo addslashes(h($item['nome'])); ?>');">
                    Remover
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>

        <div class="carrinho-acoes">
          <form method="post" action="carrinho.php" style="display:inline;">
            <input type="hidden" name="acao" value="limpar">
            <button type="submit" class="btn btn-outline btn-limpar"
                    onclick="return confirm('Limpar toda a sacola?');">
              Limpar sacola
            </button>
          </form>
        </div>
      </div>

      <div class="carrinho-step">
        <div class="carrinho-step-titulo">2. Dados para entrega / retirada</div>

        <div class="carrinho-form">
          <div class="campo">
            <label for="cli_nome">Nome *</label>
            <input type="text" id="cli_nome" placeholder="Seu nome completo">
          </div>

          <div class="campo">
            <label for="cli_tel">WhatsApp / Telefone *</label>
            <input type="text" id="cli_tel" placeholder="(DDD) 90000-0000">
          </div>
          <div class="campo">
            <label for="tipo_pedido">Tipo de pedido *</label>
            <select id="tipo_pedido">
              <option value="Entrega">Entrega</option>
              <option value="Retirada no local">Retirada no local</option>
            </select>
          </div>

          <div class="campo" id="campo_bairro_entrega">
            <label for="cli_bairro">Bairro / taxa de entrega *</label>
            <select id="cli_bairro">
              <option value="">Selecione seu bairro</option>
              <?php foreach ($taxasEntrega as $t): ?>
                <?php
                  $bairroTaxa = $t['bairro'] ?? '';
                  $consultarTaxa = !empty($t['consultar']) || !isset($t['taxa']) || $t['taxa'] === null;
                  $valorTaxa = $consultarTaxa ? '' : number_format((float)$t['taxa'], 2, '.', '');
                ?>
                <option
                  value="<?php echo h($bairroTaxa); ?>"
                  data-taxa="<?php echo h($valorTaxa); ?>"
                  data-consultar="<?php echo $consultarTaxa ? '1' : '0'; ?>">
                  <?php echo h($bairroTaxa); ?>
                  <?php if ($consultarTaxa): ?>
                    - consultar taxa
                  <?php else: ?>
                    - R$ <?php echo number_format((float)$t['taxa'], 2, ',', '.'); ?>
                  <?php endif; ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="campo" id="campo_endereco_entrega">
            <label for="cli_endereco">Endereço completo *</label>
            <textarea id="cli_endereco" rows="2" placeholder="Rua, número, casa, complemento"></textarea>
          </div>

          <div class="campo">
            <label for="cli_ref">Ponto de referência</label>
            <input type="text" id="cli_ref" placeholder="Ex.: perto da escola, portão azul...">
          </div>
        </div>
      </div>

      <div class="carrinho-step">
        <div class="carrinho-step-titulo">3. Forma de pagamento</div>

        <div class="carrinho-form">
          <div class="campo">
            <label for="pag_forma">Como vai pagar? *</label>
            <select id="pag_forma">
              <option value="">Selecione</option>
              <option value="Dinheiro">Dinheiro</option>
              <option value="PIX">PIX</option>
              <option value="Cartão de débito">Cartão de débito</option>
              <option value="Cartão de crédito">Cartão de crédito</option>
            </select>
          </div>

          <div class="campo" id="campo_troco">
            <label for="pag_troco">Troco para quanto? (se for em dinheiro)</label>
            <input type="text" id="pag_troco" placeholder="Ex.: 100,00">
          </div>

          <div class="campo">
            <label for="cli_obs">Observações do pedido</label>
            <textarea id="cli_obs" rows="3"
              placeholder="Ex.: fritar bem passado, mandar maionese à parte, etc."></textarea>
          </div>
        </div>
      </div>
      <div class="carrinho-resumo">
        <div>Itens: <?php echo $totais['itens']; ?></div>
        <div>Subtotal dos produtos: <strong>R$ <?php echo number_format($totais['subtotal'], 2, ',', '.'); ?></strong></div>
        <div>Tipo de pedido: <strong id="resumoTipoPedido">Entrega</strong></div>
        <div id="linhaResumoBairro">Bairro escolhido: <strong id="resumoBairro">Selecione o bairro</strong></div>
        <div>Taxa de entrega: <strong id="resumoEntrega">R$ 0,00</strong></div>
        <div>Total final: <strong id="resumoTotalFinal">R$ <?php echo number_format($totais['subtotal'], 2, ',', '.'); ?></strong></div>
      </div>

      <div class="carrinho-botoes-finais">
        <?php if ($whatsBase): ?>
          <button type="button" class="btn btn-red btn-whats-final" id="btnEnviarWhats">
            Enviar pedido pelo WhatsApp
          </button>
        <?php else: ?>
          <p style="color:#b91c1c;font-size:13px;">
            WhatsApp da loja não configurado no painel. Configure o número para liberar o envio automático.
          </p>
        <?php endif; ?>
      </div>

    <?php endif; ?>
  </div>
</div>

<script>
var CARRINHO_ITENS        = <?php echo json_encode($itensJs, JSON_UNESCAPED_UNICODE); ?>;
var CARRINHO_SUBTOTAL     = <?php echo json_encode(number_format($totais['subtotal'], 2, ',', '.')); ?>;
var CARRINHO_SUBTOTAL_NUM = <?php echo json_encode((float)$totais['subtotal']); ?>;
var WHATS_BASE            = <?php echo json_encode($whatsBase); ?>;

var TIPO_PEDIDO = 'Entrega';
var ENTREGA_BAIRRO = '';
var ENTREGA_TAXA = 0;
var ENTREGA_CONSULTAR = false;
var CARRINHO_TOTAL_FINAL = CARRINHO_SUBTOTAL_NUM;

function confirmarRemocao(nomeItem) {
  return confirm("Tem certeza que deseja remover '" + nomeItem + "' da sacola?");
}

document.getElementById('pag_forma').addEventListener('change', function () {
  const campoTroco = document.getElementById('campo_troco');
  if (this.value === 'Dinheiro') {
    campoTroco.style.display = 'block';
  } else {
    campoTroco.style.display = 'none';
  }
});

const campoTipoPedido = document.getElementById('tipo_pedido');
const campoBairro = document.getElementById('cli_bairro');
const blocoBairroEntrega = document.getElementById('campo_bairro_entrega');
const blocoEnderecoEntrega = document.getElementById('campo_endereco_entrega');
const linhaResumoBairro = document.getElementById('linhaResumoBairro');

function formatarMoeda(valor) {
  return Number(valor || 0).toFixed(2).replace('.', ',');
}

function atualizarTotalComEntrega() {
  TIPO_PEDIDO = campoTipoPedido ? campoTipoPedido.value : 'Entrega';
  ENTREGA_BAIRRO = '';
  ENTREGA_TAXA = 0;
  ENTREGA_CONSULTAR = false;

  const resumoTipoPedido = document.getElementById('resumoTipoPedido');
  const resumoBairro = document.getElementById('resumoBairro');
  const resumoEntrega = document.getElementById('resumoEntrega');
  const resumoTotalFinal = document.getElementById('resumoTotalFinal');

  if (TIPO_PEDIDO === 'Retirada no local') {
    ENTREGA_BAIRRO = 'Retirada no local';
    ENTREGA_TAXA = 0;
    ENTREGA_CONSULTAR = false;
    CARRINHO_TOTAL_FINAL = CARRINHO_SUBTOTAL_NUM;

    if (blocoBairroEntrega) blocoBairroEntrega.style.display = 'none';
    if (blocoEnderecoEntrega) blocoEnderecoEntrega.style.display = 'none';
    if (linhaResumoBairro) linhaResumoBairro.style.display = 'none';

    if (resumoTipoPedido) resumoTipoPedido.innerText = 'Retirada no local';
    if (resumoBairro) resumoBairro.innerText = 'Retirada no local';
    if (resumoEntrega) resumoEntrega.innerText = 'R$ 0,00';
    if (resumoTotalFinal) resumoTotalFinal.innerText = 'R$ ' + formatarMoeda(CARRINHO_TOTAL_FINAL);
    return;
  }

  if (blocoBairroEntrega) blocoBairroEntrega.style.display = '';
  if (blocoEnderecoEntrega) blocoEnderecoEntrega.style.display = '';
  if (linhaResumoBairro) linhaResumoBairro.style.display = '';

  if (campoBairro && campoBairro.value) {
    const opt = campoBairro.options[campoBairro.selectedIndex];
    ENTREGA_BAIRRO = opt.value;
    ENTREGA_CONSULTAR = opt.getAttribute('data-consultar') === '1';
    ENTREGA_TAXA = ENTREGA_CONSULTAR ? 0 : parseFloat(opt.getAttribute('data-taxa') || '0');
  }

  CARRINHO_TOTAL_FINAL = CARRINHO_SUBTOTAL_NUM + ENTREGA_TAXA;

  if (resumoTipoPedido) resumoTipoPedido.innerText = 'Entrega';
  if (resumoBairro) resumoBairro.innerText = ENTREGA_BAIRRO || 'Selecione o bairro';
  if (resumoEntrega) resumoEntrega.innerText = ENTREGA_CONSULTAR ? 'A consultar' : 'R$ ' + formatarMoeda(ENTREGA_TAXA);
  if (resumoTotalFinal) {
    resumoTotalFinal.innerText = ENTREGA_CONSULTAR
      ? 'Aguardando confirmação da loja'
      : 'R$ ' + formatarMoeda(CARRINHO_TOTAL_FINAL);
  }
}

if (campoTipoPedido) {
  campoTipoPedido.addEventListener('change', atualizarTotalComEntrega);
}

if (campoBairro) {
  campoBairro.addEventListener('change', atualizarTotalComEntrega);
}

atualizarTotalComEntrega();

const btnWhats = document.getElementById('btnEnviarWhats');
if (btnWhats && WHATS_BASE) {
  btnWhats.addEventListener('click', function () {
    const nome   = document.getElementById('cli_nome').value.trim();
    const tel    = document.getElementById('cli_tel').value.trim();
    const tipoPedido = document.getElementById('tipo_pedido').value;
    const bairro = tipoPedido === 'Retirada no local' ? 'Retirada no local' : document.getElementById('cli_bairro').value.trim();
    const end    = tipoPedido === 'Retirada no local' ? 'Retirada no local' : document.getElementById('cli_endereco').value.trim();
    const ref    = document.getElementById('cli_ref').value.trim();

    atualizarTotalComEntrega();
    const forma  = document.getElementById('pag_forma').value;
    const troco  = document.getElementById('pag_troco').value.trim();
    const obs    = document.getElementById('cli_obs').value.trim();

    if (!nome || !tel || !forma) {
      alert('Preencha nome, telefone e forma de pagamento para enviar o pedido.');
      return;
    }

    if (tipoPedido === 'Entrega' && (!bairro || !end)) {
      alert('Para entrega, selecione o bairro e preencha o endereço.');
      return;
    }

    try {
      const fd = new FormData();
      fd.append('nome', nome);
      fd.append('tel', tel);
      fd.append('tipo_pedido', tipoPedido);
      fd.append('endereco', end);
      fd.append('bairro', bairro);
      fd.append('taxa_entrega', tipoPedido === 'Retirada no local' ? '0,00' : (ENTREGA_CONSULTAR ? 'A consultar' : formatarMoeda(ENTREGA_TAXA)));
      fd.append('total_final', ENTREGA_CONSULTAR ? 'Aguardando confirmação da loja' : formatarMoeda(CARRINHO_TOTAL_FINAL));
      fd.append('entrega_consultar', ENTREGA_CONSULTAR ? '1' : '0');
      fd.append('referencia', ref);
      fd.append('pag_forma', forma);
      fd.append('troco', troco);
      fd.append('obs', obs);
      fd.append('subtotal', CARRINHO_SUBTOTAL);
      fd.append('itens_json', JSON.stringify(CARRINHO_ITENS));

      fetch('registrar_pedido.php', {
        method: 'POST',
        body: fd
      });
    } catch (e) {
      console.error('Falha ao registrar pedido para o painel', e);
    }

    let msg = '';
    msg += '🧾 Novo pedido do cardápio online%0A%0A';
    msg += '*Cliente:* ' + nome + '%0A';
    msg += '*Telefone:* ' + tel + '%0A';
    msg += '*Tipo de pedido:* ' + tipoPedido + '%0A';
    if (tipoPedido === 'Entrega') {
      msg += '*Bairro:* ' + bairro + '%0A';
      msg += '*Endereço:* ' + end + '%0A';
      msg += '*Taxa de entrega:* ' + (tipoPedido === 'Retirada no local' ? 'R$ 0,00' : (ENTREGA_CONSULTAR ? 'A consultar' : 'R$ ' + formatarMoeda(ENTREGA_TAXA))) + '%0A';
      if (ref) msg += '*Referência:* ' + ref + '%0A';
    } else {
      msg += '*Retirada:* Cliente vai retirar no local%0A';
      msg += '*Taxa de entrega:* R$ 0,00%0A';
    }
    msg += '%0A*Itens:*%0A';

    CARRINHO_ITENS.forEach(function (item) {
      const totalItem = (item.preco * item.qtd).toFixed(2).replace('.', ',');
      const precoUnit = item.preco.toFixed(2).replace('.', ',');
      msg += '- ' + item.qtd + 'x ' + item.nome +
             ' (R$ ' + precoUnit + ' cada) = R$ ' + totalItem + '%0A';
    });

    msg += '%0A*Subtotal dos produtos:* R$ ' + CARRINHO_SUBTOTAL + '%0A';
    msg += '*Taxa de entrega:* ' + (ENTREGA_CONSULTAR ? 'A consultar' : 'R$ ' + formatarMoeda(ENTREGA_TAXA)) + '%0A';
    msg += '*Total final:* ' + (ENTREGA_CONSULTAR ? 'Aguardando confirmação da loja' : 'R$ ' + formatarMoeda(CARRINHO_TOTAL_FINAL)) + '%0A';
    msg += '*Pagamento:* ' + forma;
    if (forma === 'Dinheiro' && troco) {
      msg += ' (troco para R$ ' + troco + ')';
    }
    msg += '%0A';

    if (obs) {
      msg += '%0A*Observações:* %0A' + obs.replace(/\n/g, '%0A') + '%0A';
    }

    const url = WHATS_BASE + '?text=' + msg;
    window.open(url, '_blank');
  });
}
</script>

</body>
</html>
