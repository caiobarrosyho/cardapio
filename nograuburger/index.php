<?php
session_start();
require __DIR__ . '/funcoes_carrinho.php';

$dataFile = __DIR__ . '/data/produtos.json';
$json  = file_get_contents($dataFile);
$dados = json_decode($json, true);

$loja       = $dados['loja'] ?? [];
$categorias = $dados['categorias'] ?? [];

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$tipoLoja      = $loja['tipo'] ?? 'Hamburgueria';
$tempoEntrega  = $loja['tempo_entrega'] ?? '35–50 min';
$pedidoMinimo  = isset($loja['pedido_minimo']) ? (float)$loja['pedido_minimo'] : 20.00;
$textoEntrega  = $loja['texto_entrega'] ?? 'A gente leva até você';
$textoRetirada = $loja['texto_retirada'] ?? 'Você retira no local';
$taxaPadrao    = isset($loja['taxa_padrao']) ? (float)$loja['taxa_padrao'] : 5.99;
$taxaRapida    = isset($loja['taxa_rapida']) ? (float)$loja['taxa_rapida'] : 8.99;

$slots = [
  '11:00–11:30',
  '12:00–12:30',
  '12:30–13:00',
  '13:00–13:30',
  '13:30–14:00',
  '14:00–14:30',
];

$descricaoLoja = $loja['descricao'] ?? ($loja['slogan'] ?? '');
$cnpjLoja      = $loja['cnpj'] ?? '';

$endTopo = $loja['endereco_topo']
  ?? ($loja['endereco_curto']
  ?? ($loja['endereco'] ?? 'Endereço não configurado'));

$whatsLoja    = $loja['whatsapp'] ?? '';
$whatsDigitos = preg_replace('/\D+/', '', $whatsLoja);
$whatsLink    = $whatsDigitos ? 'https://wa.me/55' . $whatsDigitos : '';

$nomeLoja   = $loja['nome'] ?? 'No Grau Burger';
$sloganLoja = $loja['slogan'] ?? 'Hambúrguer artesanal com presença de marca';
$bannerLoja = $loja['banner'] ?? '';
$logoLoja   = $loja['logo'] ?? '';

$primeiraCategoria = $categorias[0]['titulo'] ?? 'Mais pedidos';

$precos = [];
foreach ($categorias as $cat) {
  foreach (($cat['produtos'] ?? []) as $prod) {
    if (!isset($prod['ativo']) || (int)$prod['ativo'] === 1) {
      $precos[] = (float)($prod['preco'] ?? 0);
    }
  }
}
$menorPreco = $precos ? min($precos) : 0;
?>
<!doctype html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<title><?php echo h($nomeLoja); ?></title>
<meta name="viewport" content="width=device-width,initial-scale=1">

<link rel="manifest" href="manifest.json">
<meta name="theme-color" content="#b91c1c">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="<?php echo h($nomeLoja); ?>">

<link rel="icon" type="image/png" sizes="32x32" href="assets/img/favicon-32.png">
<link rel="icon" type="image/png" sizes="16x16" href="assets/img/favicon-16.png">
<link rel="shortcut icon" href="assets/img/favicon.ico">
<link rel="apple-touch-icon" href="assets/img/apple-touch-icon.png">
<link rel="stylesheet" href="assets/style.css?v=nograu03">
</head>
<body class="tema-hamburgueria">

<?php include __DIR__ . '/header_topo.php'; ?>

<div class="main">
  <section class="hero-burger">
    <div class="hero-burger-bg">
      <?php if ($bannerLoja): ?>
        <img src="<?php echo h($bannerLoja); ?>" alt="Banner da loja">
      <?php endif; ?>
      <div class="hero-burger-overlay"></div>
    </div>

    <div class="hero-burger-content">
      <div class="hero-burger-top">
        <div class="hero-badge">NO GRAU BURGER</div>
        <div class="hero-status">Aberto para pedidos</div>
      </div>

      <div class="hero-brand-box">
        <div class="hero-brand-left">
          <div class="hero-logo hero-logo-burger">
            <?php if ($logoLoja): ?>
              <img src="<?php echo h($logoLoja); ?>" alt="Logo">
            <?php endif; ?>
          </div>

          <div class="hero-brand-info">
            <div class="hero-name"><?php echo h($nomeLoja); ?></div>
            <div class="hero-type"><?php echo h($tipoLoja); ?></div>
            <div class="hero-slogan"><?php echo h($sloganLoja); ?></div>

            <div class="hero-chips">
              <span class="hero-chip">Entrega rápida</span>
              <span class="hero-chip">Pedido mínimo R$ <?php echo number_format($pedidoMinimo, 2, ',', '.'); ?></span>
              <?php if ($menorPreco > 0): ?>
                <span class="hero-chip">Preços a partir de R$ <?php echo number_format($menorPreco, 2, ',', '.'); ?></span>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <div class="hero-brand-right">
          <button type="button" class="hero-action ghost" id="btnSobreLoja">Ver loja</button>
          <button type="button" class="hero-action" id="btnTipoEntrega">
            <span id="textoTipoEntrega">Entrega</span>
            <span>▾</span>
          </button>
          <button type="button" class="hero-action" id="btnDiaEntrega">
            <span id="textoDiaEntrega">Hoje</span>
            <span>▾</span>
          </button>
        </div>
      </div>
    </div>
  </section>

  <section class="toolbar-burger">
    <div class="toolbar-burger-inner">
      <div class="search-cardapio burger-search">
        <input type="text" id="busca" placeholder="Buscar smash, combo, bacon, cheddar...">
      </div>

      <div class="toolbar-actions">
        <button type="button" class="select-box select-btn" id="btnTipoEntregaToolbar">
          <span class="toolbar-label">Recebimento:</span>
          <span id="textoTipoEntregaClone">Entrega</span>
        </button>

        <button type="button" class="select-box select-btn" id="btnDiaEntregaToolbar">
          <span class="toolbar-label">Horário:</span>
          <span id="textoDiaEntregaClone">Hoje</span>
        </button>
      </div>
    </div>
  </section>

  <div class="page-body">
    <div class="content content-burger">
      <?php foreach ($categorias as $cat): ?>
        <section class="categoria categoria-burger" data-categoria="<?php echo h($cat['titulo']); ?>">
          <div class="categoria-header">
            <div>
              <h2 class="categoria-titulo"><?php echo h($cat['titulo']); ?></h2>
              <p class="categoria-subtitulo"></p>
            </div>
          </div>

          <div class="grid-produtos">
          <?php foreach (($cat['produtos'] ?? []) as $prod): ?>
            <?php
              $ativo = !isset($prod['ativo']) || (int)$prod['ativo'] === 1;
              if (!$ativo) continue;

              $temPromo = !empty($prod['preco_original']) && $prod['preco_original'] > $prod['preco'];
              $idProd   = $prod['id'] ?? md5(($prod['nome'] ?? '') . ($prod['preco'] ?? 0));
              $foto     = $prod['foto'] ?? '';
            ?>
            <article class="card-produto card-burger"
              data-id="<?php echo h($idProd); ?>"
              data-nome="<?php echo h($prod['nome'] ?? ''); ?>"
              data-desc="<?php echo h($prod['descricao'] ?? ''); ?>"
              data-detalhe="<?php echo h($prod['detalhe'] ?? ''); ?>"
              data-preco="<?php echo h(number_format((float)($prod['preco'] ?? 0), 2, '.', '')); ?>"
              data-preco-original="<?php echo $temPromo ? h(number_format((float)$prod['preco_original'], 2, '.', '')) : ''; ?>"
              data-foto="<?php echo h($foto); ?>"
            >
              <div class="card-info">
                <div>
                  <div class="card-tag">NO GRAU</div>
                  <div class="card-nome"><?php echo h($prod['nome'] ?? ''); ?></div>
                  <div class="card-descricao"><?php echo h($prod['descricao'] ?? ''); ?></div>
                  <?php if (!empty($prod['detalhe'])): ?>
                    <div class="card-detalhe"><?php echo h($prod['detalhe']); ?></div>
                  <?php endif; ?>
                </div>

                <div class="card-bottom">
                  <div class="card-preco <?php echo $temPromo ? 'promo' : ''; ?>">
                    R$ <?php echo number_format((float)($prod['preco'] ?? 0), 2, ',', '.'); ?>
                    <?php if ($temPromo): ?>
                      <small>R$ <?php echo number_format((float)$prod['preco_original'], 2, ',', '.'); ?></small>
                    <?php endif; ?>
                  </div>
                  <div class="card-cta-mini">Clique para montar</div>
                </div>
              </div>

              <div class="card-imagem">
                <?php if ($foto): ?>
                  <img src="<?php echo h($foto); ?>" alt="<?php echo h($prod['nome'] ?? 'Produto'); ?>">
                <?php else: ?>
                  <div class="card-sem-foto">Sem foto</div>
                <?php endif; ?>
              </div>
            </article>
          <?php endforeach; ?>
          </div>
        </section>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php if ($whatsLink): ?>
<a href="<?php echo h($whatsLink); ?>" target="_blank" class="whats-float" aria-label="Falar no WhatsApp">
  <span class="whats-float-icon">✆</span>
  <span class="whats-float-text">
    <strong>WhatsApp</strong>
    <small>Peça agora</small>
  </span>
</a>
<?php endif; ?>

<div id="pwaInstallBox" class="pwa-install-box" style="display:none;">
  <div class="pwa-install-content">
    <div class="pwa-install-text">
      <strong>Instale nosso app</strong>
      <small>Abra mais rápido direto da tela do celular</small>
    </div>
    <div class="pwa-install-actions">
      <button type="button" id="btnPwaInstall">Instalar</button>
      <button type="button" id="btnPwaClose">×</button>
    </div>
  </div>
</div>

<!-- MODAL COMO QUER RECEBER -->
<div class="modal-backdrop" id="modalEntrega">
  <div class="modal">
    <div class="modal-title">Como quer receber o pedido?</div>
    <div class="modal-options" id="opcoesEntrega">
      <div class="modal-option active" data-tipo="Entrega">
        <div class="modal-option-left">
          <div class="modal-option-title">Entrega</div>
          <div class="modal-option-desc"><?php echo h($textoEntrega); ?></div>
        </div>
        <div class="modal-option-right"></div>
      </div>
      <div class="modal-option" data-tipo="Retirada">
        <div class="modal-option-left">
          <div class="modal-option-title">Retirada</div>
          <div class="modal-option-desc"><?php echo h($textoRetirada); ?></div>
        </div>
        <div class="modal-option-right"></div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-red" id="btnConfirmarEntrega">Confirmar</button>
    </div>
  </div>
</div>

<!-- MODAL HORÁRIO -->
<div class="modal-backdrop" id="modalHorario">
  <div class="modal">
    <div class="modal-horario-header">
      <div class="modal-dia-atual">Hoje</div>
      <div class="modal-horario-col">
        <div style="font-size:12px;font-weight:600;">Horário do pedido</div>
        <div style="font-size:11px;color:#777;">Agora ou agendamento</div>
      </div>
    </div>

    <div class="modal-subtitle">Para agora</div>
    <div class="modal-horario-lista">
      <div class="modal-horario-item">
        <span>Padrão<br><span style="color:#777;">49–59 min</span></span>
        <span>R$ <?php echo number_format($taxaPadrao, 2, ',', '.'); ?></span>
      </div>
      <div class="modal-horario-item">
        <span>Rápida<br><span style="color:#777;">40–50 min</span></span>
        <span>R$ <?php echo number_format($taxaRapida, 2, ',', '.'); ?></span>
      </div>

      <div class="modal-subtitle" style="margin-top:10px;">Agendamento</div>
      <?php foreach ($slots as $slot): ?>
      <div class="modal-horario-item">
        <span><?php echo h($slot); ?></span>
        <span>R$ <?php echo number_format($taxaPadrao, 2, ',', '.'); ?></span>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="modal-footer" style="margin-top:12px;">
      <button class="btn btn-red" id="btnConfirmarHorario">Confirmar</button>
    </div>
  </div>
</div>

<!-- MODAL SOBRE / HORÁRIO / PAGAMENTO DA LOJA -->
<div class="modal-backdrop" id="modalLoja">
  <div class="modal modal-loja">
    <button type="button" class="modal-close" id="mlFechar">×</button>

    <div class="ml-tabs">
      <button type="button" class="ml-tab active" data-tab="sobre">Sobre</button>
      <button type="button" class="ml-tab" data-tab="horario">Horário</button>
      <button type="button" class="ml-tab" data-tab="pagamento">Pagamento</button>
    </div>

    <div class="ml-conteudo" id="tab-sobre">
      <?php if ($descricaoLoja): ?>
      <p class="ml-texto"><?php echo nl2br(h($descricaoLoja)); ?></p>
      <?php endif; ?>

      <h3>Endereço</h3>
      <p class="ml-texto">
        <?php echo h($loja['endereco'] ?? ''); ?>
        <?php if (!empty($loja['complemento'])): ?><br><?php echo h($loja['complemento']); ?><?php endif; ?>
        <br>
        <?php echo h(($loja['bairro'] ?? '') . ' – ' . ($loja['cidade'] ?? '') . ' – ' . ($loja['uf'] ?? '')); ?><br>
        CEP: <?php echo h($loja['cep'] ?? ''); ?>
      </p>

      <?php if ($cnpjLoja): ?>
      <h3>Outras informações</h3>
      <p class="ml-texto">CNPJ: <?php echo h($cnpjLoja); ?></p>
      <?php endif; ?>

      <p class="ml-rodape">Os preços e horários são definidos pela própria loja.</p>
    </div>

    <div class="ml-conteudo" id="tab-horario" style="display:none;">
      <ul class="ml-horario-lista">
        <?php
        $dias = [
          'seg' => 'Segunda-feira',
          'ter' => 'Terça-feira',
          'qua' => 'Quarta-feira',
          'qui' => 'Quinta-feira',
          'sex' => 'Sexta-feira',
          'sab' => 'Sábado',
          'dom' => 'Domingo',
        ];
        foreach ($dias as $sigla => $rotulo):
          $abre    = $loja["hora_{$sigla}_abre"]  ?? '';
          $fecha   = $loja["hora_{$sigla}_fecha"] ?? '';
          $fechado = !empty($loja["hora_{$sigla}_fechado"]);
        ?>
          <li>
            <span><?php echo h($rotulo); ?></span>
            <span>
              <?php
              if ($fechado || !$abre || !$fecha) {
                echo 'Fechado';
              } else {
                echo h($abre) . ' às ' . h($fecha);
              }
              ?>
            </span>
          </li>
        <?php endforeach; ?>
      </ul>
      <p class="ml-rodape">Horários configurados no painel.</p>
    </div>

    <div class="ml-conteudo" id="tab-pagamento" style="display:none;">
      <h3>Pagamento</h3>
      <ul class="ml-pay-lista">
        <li>Dinheiro</li>
        <li>Cartão de débito</li>
        <li>Cartão de crédito</li>
        <li>PIX</li>
      </ul>
      <p class="ml-rodape">Confirmação final também pode ser feita no WhatsApp.</p>
    </div>
  </div>
</div>

<!-- MODAL PRODUTO -->
<div class="modal-backdrop" id="modalProduto">
  <div class="modal modal-produto">
    <button type="button" class="modal-close" id="mpFechar">×</button>
    <div class="modal-produto-grid">
      <div class="modal-produto-img">
        <img id="mpImg" src="" alt="">
      </div>
      <div class="modal-produto-info">
        <div class="mp-nome" id="mpNome"></div>
        <div class="mp-desc" id="mpDesc"></div>
        <div class="mp-detalhe" id="mpDetalhe"></div>

        <div class="mp-preco" id="mpPrecoArea">
          <span id="mpPreco"></span>
          <small id="mpPrecoOriginal" style="display:none;"></small>
        </div>

        <form id="formProduto" method="post" action="carrinho.php">
          <input type="hidden" name="acao" value="add">
          <input type="hidden" name="id" id="mpId">
          <input type="hidden" name="nome" id="mpNomeInput">
          <input type="hidden" name="preco" id="mpPrecoInput">
          <input type="hidden" name="qtd" id="mpQtdInput">

          <label class="mp-label">Algum comentário?</label>
          <textarea name="comentario" maxlength="140"
                    placeholder="Ex: tirar cebola, molho à parte, sem picles..."></textarea>

          <div class="mp-footer">
            <div class="mp-qtd">
              <button type="button" class="mp-qtd-btn" id="mpMenos">−</button>
              <span id="mpQtd">1</span>
              <button type="button" class="mp-qtd-btn" id="mpMais">+</button>
            </div>
            <button type="submit" class="btn btn-red mp-add" id="mpBtnAdicionar">
              Adicionar • <span id="mpTotalBtn"></span>
            </button>
          </div>
        </form>

      </div>
    </div>
  </div>
</div>

<script>
function filtrar() {
  const termo1 = (document.getElementById('busca')?.value || '');
  const termo2 = (document.getElementById('buscaTopo')?.value || '');
  const termo  = (termo1 + ' ' + termo2).toLowerCase().trim();

  const cards = document.querySelectorAll('.card-produto');
  cards.forEach(card => {
    const nome = (card.dataset.nome || '').toLowerCase();
    const desc = (card.dataset.desc  || '').toLowerCase();
    if (!termo || nome.includes(termo) || desc.includes(termo)) {
      card.style.display = '';
    } else {
      card.style.display = 'none';
    }
  });
}

document.getElementById('busca').addEventListener('input', filtrar);
if (document.getElementById('buscaTopo')) {
  document.getElementById('buscaTopo').addEventListener('input', filtrar);
}

/* entrega */
const modalEntrega      = document.getElementById('modalEntrega');
const btnTipoEntrega    = document.getElementById('btnTipoEntrega');
const btnTipoEntregaToolbar = document.getElementById('btnTipoEntregaToolbar');
const textoTipoEntrega  = document.getElementById('textoTipoEntrega');
const textoTipoEntregaClone = document.getElementById('textoTipoEntregaClone');
let entregaSelecionada = 'Entrega';

function abrirEntrega() {
  modalEntrega.style.display = 'flex';
}

btnTipoEntrega.addEventListener('click', abrirEntrega);
btnTipoEntregaToolbar.addEventListener('click', abrirEntrega);

modalEntrega.addEventListener('click', (e) => {
  if (e.target === modalEntrega) modalEntrega.style.display = 'none';
});

document.querySelectorAll('#opcoesEntrega .modal-option').forEach(opt => {
  opt.addEventListener('click', () => {
    document.querySelectorAll('#opcoesEntrega .modal-option').forEach(o => o.classList.remove('active'));
    opt.classList.add('active');
    entregaSelecionada = opt.getAttribute('data-tipo');
  });
});

document.getElementById('btnConfirmarEntrega').addEventListener('click', () => {
  textoTipoEntrega.textContent = entregaSelecionada;
  if (textoTipoEntregaClone) textoTipoEntregaClone.textContent = entregaSelecionada;
  modalEntrega.style.display = 'none';
});

/* horário */
const modalHorario  = document.getElementById('modalHorario');
const btnDiaEntrega = document.getElementById('btnDiaEntrega');
const btnDiaEntregaToolbar = document.getElementById('btnDiaEntregaToolbar');
const textoDiaEntregaClone = document.getElementById('textoDiaEntregaClone');

function abrirHorario() {
  modalHorario.style.display = 'flex';
}

btnDiaEntrega.addEventListener('click', abrirHorario);
btnDiaEntregaToolbar.addEventListener('click', abrirHorario);

modalHorario.addEventListener('click', (e) => {
  if (e.target === modalHorario) modalHorario.style.display = 'none';
});

document.getElementById('btnConfirmarHorario').addEventListener('click', () => {
  const textoAtual = document.getElementById('textoDiaEntrega').textContent;
  if (textoDiaEntregaClone) textoDiaEntregaClone.textContent = textoAtual;
  modalHorario.style.display = 'none';
});

/* loja */
const modalLoja    = document.getElementById('modalLoja');
const btnSobreLoja = document.getElementById('btnSobreLoja');
const mlFechar     = document.getElementById('mlFechar');

btnSobreLoja.addEventListener('click', () => {
  modalLoja.style.display = 'flex';
});

mlFechar.addEventListener('click', () => {
  modalLoja.style.display = 'none';
});

modalLoja.addEventListener('click', (e) => {
  if (e.target === modalLoja) modalLoja.style.display = 'none';
});

document.querySelectorAll('.ml-tab').forEach(tab => {
  tab.addEventListener('click', () => {
    const alvo = tab.dataset.tab;
    document.querySelectorAll('.ml-tab').forEach(t => t.classList.remove('active'));
    tab.classList.add('active');
    document.querySelectorAll('.ml-conteudo').forEach(c => c.style.display = 'none');
    document.getElementById('tab-' + alvo).style.display = 'block';
  });
});
</script>

<script src="assets/produto-modal.js?v=1"></script>

<script>
if ('serviceWorker' in navigator) {
  window.addEventListener('load', function () {
    navigator.serviceWorker.register('service-worker.js')
      .then(function (reg) {
        console.log('Service Worker registrado:', reg.scope);
      })
      .catch(function (err) {
        console.log('Erro ao registrar Service Worker:', err);
      });
  });
}

let deferredPrompt = null;
let pwaTimer = null;

const installBox = document.getElementById('pwaInstallBox');
const installBtn = document.getElementById('btnPwaInstall');
const closeBtn   = document.getElementById('btnPwaClose');

function isRunningStandalone() {
  return window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
}

function hidePwaBox() {
  if (installBox) {
    installBox.style.display = 'none';
  }
  if (pwaTimer) {
    clearTimeout(pwaTimer);
    pwaTimer = null;
  }
}

function showPwaBox() {
  if (!installBox) return;
  if (isRunningStandalone()) return;

  installBox.style.display = 'block';

  if (pwaTimer) clearTimeout(pwaTimer);
  pwaTimer = setTimeout(() => {
    hidePwaBox();
  }, 20000);
}

function updateInstallButtonState() {
  if (!installBtn) return;

  if (deferredPrompt) {
    installBtn.disabled = false;
    installBtn.textContent = 'Instalar';
    installBtn.title = '';
  } else {
    installBtn.disabled = false;
    installBtn.textContent = 'Instalar';
    installBtn.title = '';
  }
}

window.addEventListener('appinstalled', () => {
  localStorage.setItem('pwa_instalado', '1');
  hidePwaBox();
});

window.addEventListener('beforeinstallprompt', (e) => {
  e.preventDefault();
  deferredPrompt = e;
  localStorage.removeItem('pwa_instalado');
  updateInstallButtonState();

  if (!isRunningStandalone()) {
    showPwaBox();
  }
});

document.addEventListener('DOMContentLoaded', () => {
  if (isRunningStandalone()) {
    localStorage.setItem('pwa_instalado', '1');
    hidePwaBox();
    return;
  }

  updateInstallButtonState();

  setTimeout(() => {
    if (!isRunningStandalone()) {
      showPwaBox();
    }
  }, 4000);
});

if (installBtn) {
  installBtn.addEventListener('click', async () => {
    if (deferredPrompt) {
      deferredPrompt.prompt();
      const choiceResult = await deferredPrompt.userChoice;

      if (choiceResult.outcome === 'accepted') {
        localStorage.setItem('pwa_instalado', '1');
      }

      deferredPrompt = null;
      updateInstallButtonState();
      hidePwaBox();
      return;
    }

    alert('No seu celular, abra o menu do navegador e toque em "Adicionar à tela inicial" ou "Instalar app".');
  });
}

if (closeBtn) {
  closeBtn.addEventListener('click', () => {
    hidePwaBox();
  });
}
</script>

</body>
</html>