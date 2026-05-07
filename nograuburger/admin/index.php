<?php
session_start();
require_once __DIR__ . '/../includes/app.php';

if (empty($_SESSION['logado_cardapio'])) {
    header('Location: login.php');
    exit;
}

$dados = cardapio_carregar_produtos();

$loja       = $dados['loja'] ?? [];
$categorias = $dados['categorias'] ?? [];


// referências de upsell (produtos sugeridos no carrinho)
$upsell1_ref = $loja['upsell1_ref'] ?? '';
$upsell2_ref = $loja['upsell2_ref'] ?? '';
$upsell3_ref = $loja['upsell3_ref'] ?? '';
$upsell4_ref = $loja['upsell4_ref'] ?? '';
?>
<!doctype html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<title>Painel Cardápio – <?php echo h($loja['nome'] ?? 'Loja'); ?></title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="icon" type="image/png" sizes="32x32" href="assets/img/favicon-32.png">
<link rel="icon" type="image/png" sizes="16x16" href="assets/img/favicon-16.png">
<link rel="shortcut icon" href="assets/img/favicon.ico">
<link rel="apple-touch-icon" href="assets/img/apple-touch-icon.png">
<link rel="stylesheet" href="../assets/style.css?v=12">
</head>
<body>

<div class="admin-wrapper">
  <div class="admin-header">
    <h1>Painel do Cardápio</h1>

    <div style="display:flex;gap:8px;align-items:center;">
     <a href="taxas_entrega.php">Taxas de entrega</a>
      <a href="pedidos.php" class="btn btn-outline">Pedidos</a>
      <a href="logout.php">Sair</a>
    </div>
  </div>

  <!-- ===================== DADOS DA LOJA ===================== -->
  <div class="admin-box" id="bloco-loja">
    <h2>Dados da loja</h2>

    <!-- abas internas -->
    <div class="ml-tabs loja-tabs">
      <button type="button" class="ml-tab loja-tab-btn active" data-tab="dados">
        Dados / Contato / Endereço
      </button>
      <button type="button" class="ml-tab loja-tab-btn" data-tab="horario">
        Horário de funcionamento
      </button>
      <button type="button" class="ml-tab loja-tab-btn" data-tab="imagens">
        Imagens
      </button>
    </div>

    <form action="salvar.php" method="post" enctype="multipart/form-data">
      <input type="hidden" name="acao" value="loja">

      <!-- TAB: DADOS / CONTATO / ENDEREÇO / TAXAS / UPSELL -->
      <div class="ml-conteudo loja-tab-pane" id="loja-tab-dados" style="display:block;">
        <!-- Identidade -->
        <div class="admin-form-row">
          <div>
            <label>Nome da loja</label>
            <input type="text" name="nome" value="<?php echo h($loja['nome'] ?? ''); ?>">
          </div>
          <div>
            <label>Tipo / categoria (ex: Salgados)</label>
            <input type="text" name="tipo" value="<?php echo h($loja['tipo'] ?? ''); ?>">
          </div>
        </div>

        <div class="admin-form-row">
          <div>
            <label>Slogan / subtítulo</label>
            <input type="text" name="slogan" value="<?php echo h($loja['slogan'] ?? ''); ?>">
          </div>
          <div>
            <label>Tempo de entrega (ex: 45–51 min)</label>
            <input type="text" name="tempo_entrega" value="<?php echo h($loja['tempo_entrega'] ?? ''); ?>">
          </div>
        </div>

        <!-- Contato -->
        <h3 style="font-size:13px;margin-top:10px;margin-bottom:6px;">Contato</h3>
        <div class="admin-form-row">
          <div>
            <label>WhatsApp da loja (somente números ou com DDD)</label>
            <input type="text" name="whatsapp" value="<?php echo h($loja['whatsapp'] ?? ''); ?>">
          </div>
          <div>
            <label>CNPJ (opcional)</label>
            <input type="text" name="cnpj" value="<?php echo h($loja['cnpj'] ?? ''); ?>">
          </div>
        </div>

        <!-- Endereço da loja -->
        <h3 style="font-size:13px;margin-top:10px;margin-bottom:6px;">Endereço da loja</h3>

        <div class="admin-form-row">
          <div>
            <label>Endereço (Rua, nº)</label>
            <input type="text" name="endereco" value="<?php echo h($loja['endereco'] ?? ''); ?>">
          </div>
          <div>
            <label>Complemento / ponto de referência</label>
            <input type="text" name="complemento" value="<?php echo h($loja['complemento'] ?? ''); ?>">
          </div>
        </div>

        <div class="admin-form-row">
          <div>
            <label>Bairro</label>
            <input type="text" name="bairro" value="<?php echo h($loja['bairro'] ?? ''); ?>">
          </div>
          <div>
            <label>Cidade</label>
            <input type="text" name="cidade" value="<?php echo h($loja['cidade'] ?? ''); ?>">
          </div>
        </div>

        <div class="admin-form-row">
          <div>
            <label>UF</label>
            <input type="text" name="uf" value="<?php echo h($loja['uf'] ?? ''); ?>" maxlength="2">
          </div>
          <div>
            <label>CEP</label>
            <input type="text" name="cep" value="<?php echo h($loja['cep'] ?? ''); ?>">
          </div>
        </div>

        <!-- Entrega / taxas -->
        <h3 style="font-size:13px;margin-top:10px;margin-bottom:6px;">Entrega e taxas</h3>

        <div class="admin-form-row">
          <div>
            <label>Pedido mínimo (aparece no topo)</label>
            <input type="number" step="0.01" name="pedido_minimo"
                   value="<?php echo h($loja['pedido_minimo'] ?? '20.00'); ?>">
          </div>
          <div>
            <label>Taxa padrão (entrega/agendamento)</label>
            <input type="number" step="0.01" name="taxa_padrao"
                   value="<?php echo h($loja['taxa_padrao'] ?? '5.99'); ?>">
          </div>
        </div>

        <div class="admin-form-row">
          <div>
            <label>Taxa rápida</label>
            <input type="number" step="0.01" name="taxa_rapida"
                   value="<?php echo h($loja['taxa_rapida'] ?? '8.99'); ?>">
          </div>
          <div>
            <label>Texto entrega (modal)</label>
            <input type="text" name="texto_entrega"
                   value="<?php echo h($loja['texto_entrega'] ?? 'A gente leva até você'); ?>">
          </div>
        </div>

        <div class="admin-form-row">
          <div>
            <label>Texto retirada (modal)</label>
            <input type="text" name="texto_retirada"
                   value="<?php echo h($loja['texto_retirada'] ?? 'Você retira no local'); ?>">
          </div>
        </div>

        <!-- SUGESTÕES NO CARRINHO (UPSELL) -->
        <h3 style="font-size:13px;margin-top:14px;margin-bottom:6px;">
          Produtos sugeridos no carrinho (para aumentar o ticket)
        </h3>
        <p style="font-size:12px;color:#666;margin-bottom:4px;">
          Escolha até quatro produtos já cadastrados para serem oferecidos ao cliente
          no carrinho, como lembrete discreto (ex.: bebidas, doces).
        </p>

        <div class="admin-form-row">
          <div>
            <label>Produto sugerido 1</label>
            <select name="upsell1_ref">
              <option value="">Não sugerir</option>
              <?php foreach ($categorias as $cIdx => $cat): ?>
                <?php foreach ($cat['produtos'] as $pIdx => $prod): ?>
                  <?php
                    $value = $cIdx . '|' . $pIdx;
                    $sel   = ($upsell1_ref === $value) ? 'selected' : '';
                  ?>
                  <option value="<?php echo h($value); ?>" <?php echo $sel; ?>>
                    <?php echo h($cat['titulo'] . ' - ' . $prod['nome']); ?>
                  </option>
                <?php endforeach; ?>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label>Produto sugerido 2</label>
            <select name="upsell2_ref">
              <option value="">Não sugerir</option>
              <?php foreach ($categorias as $cIdx => $cat): ?>
                <?php foreach ($cat['produtos'] as $pIdx => $prod): ?>
                  <?php
                    $value = $cIdx . '|' . $pIdx;
                    $sel   = ($upsell2_ref === $value) ? 'selected' : '';
                  ?>
                  <option value="<?php echo h($value); ?>" <?php echo $sel; ?>>
                    <?php echo h($cat['titulo'] . ' - ' . $prod['nome']); ?>
                  </option>
                <?php endforeach; ?>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="admin-form-row">
          <div>
            <label>Produto sugerido 3</label>
            <select name="upsell3_ref">
              <option value="">Não sugerir</option>
              <?php foreach ($categorias as $cIdx => $cat): ?>
                <?php foreach ($cat['produtos'] as $pIdx => $prod): ?>
                  <?php
                    $value = $cIdx . '|' . $pIdx;
                    $sel   = ($upsell3_ref === $value) ? 'selected' : '';
                  ?>
                  <option value="<?php echo h($value); ?>" <?php echo $sel; ?>>
                    <?php echo h($cat['titulo'] . ' - ' . $prod['nome']); ?>
                  </option>
                <?php endforeach; ?>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label>Produto sugerido 4</label>
            <select name="upsell4_ref">
              <option value="">Não sugerir</option>
              <?php foreach ($categorias as $cIdx => $cat): ?>
                <?php foreach ($cat['produtos'] as $pIdx => $prod): ?>
                  <?php
                    $value = $cIdx . '|' . $pIdx;
                    $sel   = ($upsell4_ref === $value) ? 'selected' : '';
                  ?>
                  <option value="<?php echo h($value); ?>" <?php echo $sel; ?>>
                    <?php echo h($cat['titulo'] . ' - ' . $prod['nome']); ?>
                  </option>
                <?php endforeach; ?>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>

      <!-- TAB: HORÁRIO DE FUNCIONAMENTO -->
      <div class="ml-conteudo loja-tab-pane" id="loja-tab-horario" style="display:none;">
        <h3 style="font-size:13px;margin-top:4px;margin-bottom:6px;">Horário de funcionamento</h3>
        <p style="font-size:12px;color:#666;margin-bottom:4px;">
          Ajuste horário de abertura/fechamento. Marque “Fechado” quando não atender.
        </p>

        <table class="table-itens" style="font-size:12px;margin-bottom:10px;">
          <thead>
            <tr>
              <th>Dia</th>
              <th>Abrir</th>
              <th>Fechar</th>
              <th>Fechado</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $dias = [
              'seg' => 'Segunda-feira',
              'ter' => 'Terça-feira',
              'qua' => 'Quarta-feira',
              'qui' => 'Quinta-feira',
              'sex' => 'Sexta-feira',
              'sab' => 'Sábado',
              'dom' => 'Domingo'
            ];
            foreach ($dias as $sigla => $rotulo):
              $abre    = $loja["hora_{$sigla}_abre"]    ?? '08:00';
              $fecha   = $loja["hora_{$sigla}_fecha"]   ?? '23:59';
              $fechado = !empty($loja["hora_{$sigla}_fechado"]);
            ?>
            <tr>
              <td><?php echo h($rotulo); ?></td>
              <td>
                <input type="time" name="hora_<?php echo $sigla; ?>_abre"
                       value="<?php echo h($abre); ?>"
                       style="width:90px;">
              </td>
              <td>
                <input type="time" name="hora_<?php echo $sigla; ?>_fecha"
                       value="<?php echo h($fecha); ?>"
                       style="width:90px;">
              </td>
              <td style="text-align:center;">
                <input type="checkbox" name="hora_<?php echo $sigla; ?>_fechado"
                       value="1" <?php echo $fechado ? 'checked' : ''; ?>>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- TAB: IMAGENS -->
      <div class="ml-conteudo loja-tab-pane" id="loja-tab-imagens" style="display:none;">
        <h3 style="font-size:13px;margin-top:4px;margin-bottom:6px;">Imagens</h3>

        <div class="admin-form-row">
          <div>
            <label>Logo (redonda) – deixe vazio para não trocar</label>
            <input type="file" name="logo">
            <?php if (!empty($loja['logo'])): ?>
              <small>Atual: <?php echo h($loja['logo']); ?></small>
            <?php endif; ?>
          </div>
          <div>
            <label>Banner (topo) – deixe vazio para não trocar</label>
            <input type="file" name="banner">
            <?php if (!empty($loja['banner'])): ?>
              <small>Atual: <?php echo h($loja['banner']); ?></small>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <button class="btn btn-red" type="submit">Salvar dados da loja</button>
    </form>
  </div>

  <!-- ===================== CATEGORIAS / PRODUTOS ===================== -->
  <?php $totalCats = count($categorias); ?>
  <?php foreach ($categorias as $idx => $cat): ?>
    <div class="admin-box" id="cat-<?php echo $idx; ?>">

      <!-- Cabeçalho da categoria com ações (mover / excluir) -->
      <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;margin-bottom:6px;">
        <h2 style="margin:0;">Categoria: <?php echo h($cat['titulo']); ?></h2>
        <div style="display:flex;gap:4px;flex-wrap:wrap;">
          <!-- mover para cima -->
          <?php if ($idx > 0): ?>
            <form action="salvar.php" method="post" style="display:inline;">
              <input type="hidden" name="acao" value="mover_categoria_cima">
              <input type="hidden" name="idx" value="<?php echo $idx; ?>">
              <button type="submit" class="btn btn-outline" title="Mover categoria para cima">
                ↑
              </button>
            </form>
          <?php endif; ?>

          <!-- mover para baixo -->
          <?php if ($idx < $totalCats - 1): ?>
            <form action="salvar.php" method="post" style="display:inline;">
              <input type="hidden" name="acao" value="mover_categoria_baixo">
              <input type="hidden" name="idx" value="<?php echo $idx; ?>">
              <button type="submit" class="btn btn-outline" title="Mover categoria para baixo">
                ↓
              </button>
            </form>
          <?php endif; ?>

          <!-- remover categoria -->
          <form action="salvar.php" method="post" style="display:inline;">
            <input type="hidden" name="acao" value="remover_categoria">
            <input type="hidden" name="idx" value="<?php echo $idx; ?>">
            <button type="submit" class="btn btn-outline"
                    onclick="return confirm('Remover esta categoria e todos os produtos dela?');">
              Excluir
            </button>
          </form>
        </div>
      </div>

      <!-- editar nome da categoria -->
      <form action="salvar.php" method="post" style="margin-bottom:10px;">
        <input type="hidden" name="acao" value="editar_categoria">
        <input type="hidden" name="idx" value="<?php echo $idx; ?>">
        <div class="admin-form-row">
          <div>
            <label>Título da categoria</label>
            <input type="text" name="titulo" value="<?php echo h($cat['titulo']); ?>">
          </div>
        </div>
        <button class="btn btn-outline" type="submit">Salvar categoria</button>
      </form>

      <!-- tabela de produtos -->
      <table class="table-itens">
        <thead>
          <tr>
            <th>Nome</th>
            <th>Detalhe</th>
            <th>Preço</th>
            <th>Visível</th>
            <th class="acoes">Ações</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($cat['produtos'] as $pIdx => $prod): ?>
          <?php
            $ativo = !isset($prod['ativo']) || (int)$prod['ativo'] === 1;
          ?>
          <tr>
            <td><?php echo h($prod['nome']); ?></td>
            <td><?php echo h($prod['detalhe'] ?? ''); ?></td>
            <td>
              R$ <?php echo number_format($prod['preco'], 2, ',', '.'); ?>
              <?php if (!empty($prod['preco_original']) && $prod['preco_original'] > $prod['preco']): ?>
                <small style="text-decoration:line-through;color:#999;">
                  R$ <?php echo number_format($prod['preco_original'], 2, ',', '.'); ?>
                </small>
              <?php endif; ?>
            </td>
            <td>
              <form action="salvar.php" method="post" style="display:inline%;">
                <input type="hidden" name="acao" value="toggle_produto_ativo">
                <input type="hidden" name="cat" value="<?php echo $idx; ?>">
                <input type="hidden" name="prod" value="<?php echo $pIdx; ?>">
                <button type="submit"
                        class="btn-status <?php echo $ativo ? 'status-ativo' : 'status-inativo'; ?>">
                  <?php echo $ativo ? 'Ativo' : 'Inativo'; ?>
                </button>
              </form>
            </td>
            <td class="acoes">
              <a class="btn btn-outline"
                 href="editar_produto.php?cat=<?php echo $idx; ?>&prod=<?php echo $pIdx; ?>">
                Editar
              </a>
              <form action="salvar.php" method="post" style="display:inline;">
                <input type="hidden" name="acao" value="remover_produto">
                <input type="hidden" name="cat" value="<?php echo $idx; ?>">
                <input type="hidden" name="prod" value="<?php echo $pIdx; ?>">
                <button class="btn btn-outline" type="submit"
                        onclick="return confirm('Remover este produto?');">
                  Remover
                </button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>

      <!-- novo produto -->
      <h3 style="font-size:13px;margin-top:10px;margin-bottom:6px;">Novo produto nesta categoria</h3>
      <form action="salvar.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="acao" value="novo_produto">
        <input type="hidden" name="cat" value="<?php echo $idx; ?>">

        <div class="admin-form-row">
          <div>
            <label>Nome</label>
            <input type="text" name="nome" required>
          </div>
          <div>
            <label>Preço (use ponto, ex: 15.90)</label>
            <input type="number" step="0.01" name="preco" required>
          </div>
        </div>

        <div class="admin-form-row">
          <div>
            <label>Descrição</label>
            <textarea name="descricao" required></textarea>
          </div>
          <div>
            <label>Detalhe (ex: Serve 1 pessoa (500g))</label>
            <textarea name="detalhe"></textarea>
          </div>
        </div>

        <div class="admin-form-row">
          <div>
            <label>Preço original (opcional – aparece riscado)</label>
            <input type="number" step="0.01" name="preco_original">
          </div>
          <div>
            <label>Foto do produto</label>
            <input type="file" name="foto">
          </div>
        </div>

        <div class="admin-form-row">
          <div>
            <label>
              <input type="checkbox" name="ativo" value="1" checked>
              Produto ativo (aparecer no cardápio)
            </label>
          </div>
        </div>

        <button class="btn btn-red" type="submit">Adicionar produto</button>
      </form>
    </div>
  <?php endforeach; ?>

  <!-- nova categoria -->
  <div class="admin-box" id="nova-categoria">
    <h2>Nova categoria</h2>
    <form action="salvar.php" method="post">
      <input type="hidden" name="acao" value="nova_categoria">
      <div class="admin-form-row">
        <div>
          <label>Título da categoria</label>
          <input type="text" name="titulo" required>
        </div>
      </div>
      <button class="btn btn-red" type="submit">Adicionar categoria</button>
    </form>
  </div>

</div>

<script>
// abas da loja (dados / horário / imagens)
document.querySelectorAll('.loja-tab-btn').forEach(function(btn) {
  btn.addEventListener('click', function() {
    var alvo = this.getAttribute('data-tab');

    document.querySelectorAll('.loja-tab-btn').forEach(function(b) {
      b.classList.remove('active');
    });
    this.classList.add('active');

    document.querySelectorAll('.loja-tab-pane').forEach(function(p) {
      p.style.display = 'none';
    });
    var pane = document.getElementById('loja-tab-' + alvo);
    if (pane) pane.style.display = 'block';
  });
});

// manter posição de scroll após enviar formulário
document.querySelectorAll('form').forEach(function(f) {
  f.addEventListener('submit', function() {
    try {
      localStorage.setItem('adminCardapioScroll', String(window.scrollY || window.pageYOffset || 0));
    } catch(e) {}
  });
});

window.addEventListener('load', function() {
  try {
    var y = localStorage.getItem('adminCardapioScroll');
    if (y !== null) {
      localStorage.removeItem('adminCardapioScroll');
      var valor = parseFloat(y);
      if (!isNaN(valor)) {
        window.scrollTo({ top: valor, left: 0, behavior: 'instant' || 'auto' });
      }
    }
  } catch(e) {}
});
</script>

</body>
</html>
