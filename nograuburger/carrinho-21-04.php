<?php
session_start();
require __DIR__ . '/funcoes_carrinho.php';

$dataFile   = __DIR__ . '/data/produtos.json';
$dados      = json_decode(file_get_contents($dataFile), true);
$loja       = $dados['loja'] ?? [];
$categorias = $dados['categorias'] ?? [];

/* =========================================================
   Função segura para saída HTML
========================================================= */
if (!function_exists('h')) {
    function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

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

/* =========================================================
   WhatsApp da loja
========================================================= */
$whatsLoja    = $loja['whatsapp'] ?? '';
$whatsDigitos = preg_replace('/\D+/', '', $whatsLoja);
$whatsBase    = $whatsDigitos ? 'https://wa.me/55' . $whatsDigitos : '';

/* =========================================================
   Busca produto pelo índice categoria/produto
   Usado no upsell já existente
========================================================= */
function buscar_produto($dados, $catIdx, $prodIdx) {
    if (!isset($dados['categorias'][$catIdx]['produtos'][$prodIdx])) {
        return null;
    }

    $p = $dados['categorias'][$catIdx]['produtos'][$prodIdx];

    if (isset($p['ativo']) && !$p['ativo']) {
        return null;
    }

    return $p;
}

/* =========================================================
   Localiza os dados completos de um item salvo no carrinho
   ---------------------------------------------------------
   Isso foi adicionado para pegar foto / detalhe do produto
   sem alterar as funções do carrinho.

   A função tenta encontrar o produto por:
   1) id no formato "categoria-produto" (upsell)
   2) id salvo no próprio produto
   3) md5(nome + preco), que já é usado no index
   4) nome + preço como fallback
========================================================= */
function localizar_produto_carrinho($dados, $itemId, $itemNome, $itemPreco) {
    if (empty($dados['categorias']) || !is_array($dados['categorias'])) {
        return null;
    }

    foreach ($dados['categorias'] as $catIdx => $cat) {
        foreach (($cat['produtos'] ?? []) as $prodIdx => $prod) {
            if (isset($prod['ativo']) && (int)$prod['ativo'] === 0) {
                continue;
            }

            $nomeProd   = (string)($prod['nome'] ?? '');
            $precoProd  = (float)($prod['preco'] ?? 0);
            $idNumerico = $catIdx . '-' . $prodIdx;
            $idProd     = (string)($prod['id'] ?? '');
            $idMd5      = md5($nomeProd . $precoProd);

            $bateIdNumerico = ($itemId === $idNumerico);
            $bateIdProduto  = ($idProd !== '' && $itemId === $idProd);
            $bateMd5        = ($itemId === $idMd5);

            $bateNomePreco  = (
                mb_strtolower(trim($itemNome)) === mb_strtolower(trim($nomeProd))
                && abs((float)$itemPreco - $precoProd) < 0.001
            );

            if ($bateIdNumerico || $bateIdProduto || $bateMd5 || $bateNomePreco) {
                return $prod;
            }
        }
    }

    return null;
}

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
            <label for="cli_endereco">Endereço completo *</label>
            <textarea id="cli_endereco" rows="2" placeholder="Rua, número, bairro, cidade"></textarea>
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
        <div>Subtotal: <strong>R$ <?php echo number_format($totais['subtotal'], 2, ',', '.'); ?></strong></div>
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
var CARRINHO_ITENS    = <?php echo json_encode($itensJs, JSON_UNESCAPED_UNICODE); ?>;
var CARRINHO_SUBTOTAL = <?php echo json_encode(number_format($totais['subtotal'], 2, ',', '.')); ?>;
var WHATS_BASE        = <?php echo json_encode($whatsBase); ?>;

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

const btnWhats = document.getElementById('btnEnviarWhats');
if (btnWhats && WHATS_BASE) {
  btnWhats.addEventListener('click', function () {
    const nome   = document.getElementById('cli_nome').value.trim();
    const tel    = document.getElementById('cli_tel').value.trim();
    const end    = document.getElementById('cli_endereco').value.trim();
    const ref    = document.getElementById('cli_ref').value.trim();
    const forma  = document.getElementById('pag_forma').value;
    const troco  = document.getElementById('pag_troco').value.trim();
    const obs    = document.getElementById('cli_obs').value.trim();

    if (!nome || !tel || !end || !forma) {
      alert('Preencha nome, telefone, endereço e forma de pagamento para enviar o pedido.');
      return;
    }

    try {
      const fd = new FormData();
      fd.append('nome', nome);
      fd.append('tel', tel);
      fd.append('endereco', end);
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
    msg += '*Endereço:* ' + end + '%0A';
    if (ref) msg += '*Referência:* ' + ref + '%0A';
    msg += '%0A*Itens:*%0A';

    CARRINHO_ITENS.forEach(function (item) {
      const totalItem = (item.preco * item.qtd).toFixed(2).replace('.', ',');
      const precoUnit = item.preco.toFixed(2).replace('.', ',');
      msg += '- ' + item.qtd + 'x ' + item.nome +
             ' (R$ ' + precoUnit + ' cada) = R$ ' + totalItem + '%0A';
    });

    msg += '%0A*Subtotal:* R$ ' + CARRINHO_SUBTOTAL + '%0A';
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
